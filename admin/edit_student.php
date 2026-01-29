<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$message = "";
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header("Location: manage_students.php");
    exit;
}

// Verify the user is a student
$check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows === 0 || $check_result->fetch_assoc()['role'] !== 'student') {
    die("Error: This user is not a student.");
}
$check->close();

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require '../includes/validation.php';
    $validator = new Validator();
    
    // Get and sanitize input
    $username = $validator->sanitize($_POST['fullname'] ?? '');
    $email = $validator->sanitize($_POST['email'] ?? '');
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : NULL;
    $status = $validator->sanitize($_POST['status'] ?? 'active');
    
    // Validate fields
    $validator->validateFullName($username, 'Full Name');
    $validator->validateEmail($email, 'Email');
    
    // Check if email already exists (excluding current user)
    if ($validator->isValid()) {
        $validator->checkEmailExists($email, $conn, $user_id, 'Email');
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $validator->addError('Status', 'Invalid status selected');
    }
    
    if ($validator->isValid()) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, class_id = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $username, $email, $class_id, $status, $user_id);

        if ($stmt->execute()) {
            $message = "Student details updated successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error: " . $validator->getFirstError();
    }
}

// Fetch current data
$stmt = $conn->prepare("SELECT username, email, class_id, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch classes
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_students.php" class="active"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Edit Student: <?= htmlspecialchars($user['username']) ?></h2>
            <div class="user-profile"><span><?= htmlspecialchars($_SESSION['username']) ?></span></div>
        </div>

        <a href="manage_students.php" style="color:#555; text-decoration:none; display:inline-block; margin-bottom:20px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Students
        </a>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Class</label>
                    <select name="class_id">
                        <option value="">-- No Class Assigned --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $user['class_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Update Student</button>
                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    <a href="manage_students.php" style="color:#777;">Cancel</a>
                </p>
            </form>
        </div>
    </div>
    <script src="../js/form_validation.js"></script>
</body>
</html>
