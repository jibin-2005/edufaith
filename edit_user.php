<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

require 'db.php';

$message = "";
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header("Location: manage_teachers.php");
    exit;
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $role, $user_id);

    if ($stmt->execute()) {
        $message = "Success: User updated successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch current data
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: manage_teachers.php");
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php" class="<?php echo ($user['role'] == 'teacher' ? 'active' : ''); ?>"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php" class="<?php echo ($user['role'] == 'student' ? 'active' : ''); ?>"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Modify account details in the system.</p>
            </div>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert <?php echo (strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="student" <?php echo ($user['role'] == 'student' ? 'selected' : ''); ?>>Student</option>
                        <option value="teacher" <?php echo ($user['role'] == 'teacher' ? 'selected' : ''); ?>>Teacher</option>
                        <option value="parent" <?php echo ($user['role'] == 'parent' ? 'selected' : ''); ?>>Parent</option>
                        <option value="admin" <?php echo ($user['role'] == 'admin' ? 'selected' : ''); ?>>Administrator</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Update Details</button>
                <p style="text-align:center; margin-top:15px; font-size:14px;"><a href="manage_<?php echo $user['role']; ?>s.php" style="color:#777;">Back to List</a></p>
            </form>
        </div>
    </div>
</body>
</html>
