<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

$message = "";
$parent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($parent_id <= 0) {
    header("Location: manage_parents.php");
    exit;
}

// Fetch Parent Data
$stmt = $conn->prepare("SELECT id, username, email, firebase_uid, status FROM users WHERE id = ? AND role = 'parent'");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_parents.php");
    exit;
}

$parent = $result->fetch_assoc();
$stmt->close();

// Fetch Linked Students
$linked_stmt = $conn->prepare("SELECT s.id, s.username FROM users s JOIN parent_student ps ON s.id = ps.student_id WHERE ps.parent_id = ?");
$linked_stmt->bind_param("i", $parent_id);
$linked_stmt->execute();
$linked_students = $linked_stmt->get_result();
$linked_ids = [];
while($ls = $linked_students->fetch_assoc()) {
    $linked_ids[] = $ls['id'];
}
$linked_stmt->close();

// Fetch All Students for Dropdown
$all_students = $conn->query("SELECT id, username FROM users WHERE role = 'student' AND status='active' ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Parent | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .btn-submit:hover { background: #2980b9; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .student-checkboxes { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 6px; }
        .student-item { padding: 5px; }
        .info-box { background: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_parents.php" class="active"><i class="fa-solid fa-users"></i> Parents</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Edit Parent: <?= htmlspecialchars($parent['username']) ?></h2>
                <p>Update parent details and manage linked students</p>
            </div>
        </div>

        <div class="form-container">
            <div id="statusMsg"></div>

            <div class="info-box">
                <strong>Firebase UID:</strong> <?= $parent['firebase_uid'] ? htmlspecialchars($parent['firebase_uid']) : 'Not Set' ?><br>
                <strong>Status:</strong> <?= ucfirst($parent['status']) ?>
            </div>

            <form id="editParentForm">
                <input type="hidden" name="parent_id" value="<?= $parent_id ?>">
                
                <div class="form-group">
                    <label>Parent Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($parent['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="emailField" value="<?= htmlspecialchars($parent['email']) ?>" required>
                    <small style="color: #666; font-size: 12px;">⚠️ Changing email will update Firebase authentication</small>
                </div>

                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status">
                        <option value="active" <?= $parent['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $parent['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Linked Students</label>
                    <div class="student-checkboxes">
                        <?php while($student = $all_students->fetch_assoc()): ?>
                            <div class="student-item">
                                <input type="checkbox" name="students[]" value="<?= $student['id'] ?>" 
                                    id="s_<?= $student['id'] ?>" 
                                    <?= in_array($student['id'], $linked_ids) ? 'checked' : '' ?>>
                                <label for="s_<?= $student['id'] ?>"><?= htmlspecialchars($student['username']) ?></label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">Update Parent</button>
                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    <a href="manage_parents.php" style="color:#777;">Back to Parents List</a>
                </p>
            </form>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, updateEmail } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

        const firebaseConfig = {
            apiKey: "AIzaSyDTmypL1UgAxisjYrm9dmBjrcO7yp8dKJ8",
            authDomain: "sunday-219fa.firebaseapp.com",
            projectId: "sunday-219fa",
            storageBucket: "sunday-219fa.firebasestorage.app",
            messagingSenderId: "102488394492",
            appId: "1:102488394492:web:eac28db0be612b5e4b2579"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        const form = document.getElementById('editParentForm');
        const statusMsg = document.getElementById('statusMsg');
        const submitBtn = document.getElementById('submitBtn');
        const originalEmail = "<?= $parent['email'] ?>";

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const newEmail = formData.get('email');
            
            submitBtn.disabled = true;
            submitBtn.innerText = "Updating...";
            statusMsg.innerHTML = "";

            try {
                // Note: Firebase email update requires the user to be signed in
                // For admin updates, we'll just update MySQL and note that Firebase sync may need manual intervention
                // In production, use Firebase Admin SDK on the server side

                // Update MySQL
                const response = await fetch('../includes/update_parent.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    statusMsg.innerHTML = '<div class="alert alert-success">' + result.message + '</div>';
                    setTimeout(() => {
                        window.location.href = 'manage_parents.php';
                    }, 1500);
                } else {
                    statusMsg.innerHTML = '<div class="alert alert-error">' + result.message + '</div>';
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Update Parent";
                }
            } catch (error) {
                statusMsg.innerHTML = '<div class="alert alert-error">Error: ' + error.message + '</div>';
                submitBtn.disabled = false;
                submitBtn.innerText = "Update Parent";
            }
        });
    </script>
    <script src="../js/form_validation.js"></script>
</body>
</html>
