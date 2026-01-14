<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student | Teacher Portal</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module" src="../js/firebase_config.js"></script>
    <script type="module" src="../js/add_user_sync.js"></script>
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .btn-submit:disabled { background: #95a5a6; cursor: not-allowed; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php" class="active"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_history.php"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Add New Student</h2>
                <p>Register a new student to your class.</p>
            </div>
             <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <a href="my_class.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Class</a>

        <div class="form-container">
            <form id="addForm">
                <input type="hidden" id="role" name="role" value="student">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="fullname" name="fullname" required placeholder="e.g. John Doe">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. john@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                </div>

                <div class="form-group">
                    <label>Assign to Class</label>
                    <select name="class_id" id="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php
                        require '../includes/db.php';
                        $classes = $conn->query("SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name ASC");
                        while($c = $classes->fetch_assoc()):
                        ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="statusMsg" style="margin-bottom:15px; font-size:14px;"></div>

                <button type="submit" id="submitBtn" class="btn-submit">Add Student</button>
            </form>
        </div>
    </div>
</body>
</html>
