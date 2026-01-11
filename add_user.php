<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

$role_override = isset($_GET['role']) ? $_GET['role'] : 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .btn-submit:disabled { background: #95a5a6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Register New User</h2>
                <p>This will create an account in both <b>Firebase</b> and <b>MySQL</b>.</p>
            </div>
        </div>

        <div class="form-container">
            <div id="statusMsg"></div>

            <form id="addForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter email address" required>
                </div>

                <div class="form-group">
                    <label>Initial Password</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="student" <?php echo ($role_override == 'student' ? 'selected' : ''); ?>>Student</option>
                        <option value="teacher" <?php echo ($role_override == 'teacher' ? 'selected' : ''); ?>>Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">Register Member</button>
                <p style="text-align:center; padding-top:15px; font-size:12px; color:#666;">Note: Firebase requires unique emails and min. 6 character passwords.</p>
            </form>
        </div>
    </div>

    <script type="module" src="add_user_sync.js"></script>
</body>
</html>
