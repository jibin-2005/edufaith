<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parent - Sunday School</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container { max-width: 500px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; width: 100%; }
        .btn:hover { background: #218838; }
        .back-link { display: block; margin-top: 10px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <?php include 'navbar_admin.php'; // Assuming this exists, or I can genericize ?>

    <div class="main-content">
        <h2 style="text-align: center; margin-top: 20px;">Add New Parent</h2>
        
        <div class="form-container">
            <form id="addForm">
                <input type="hidden" name="role" value="parent">
                
                <div class="form-group">
                    <label for="fullname">Parent Name</label>
                    <input type="text" id="fullname" name="fullname" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="parent@example.com">
                </div>

                <div class="form-group">
                    <label for="password">Initial Password</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="******">
                </div>

                <button type="submit" id="submitBtn" class="btn">Add Parent</button>
            </form>
            <div id="statusMsg" style="margin-top: 10px; color: red; text-align: center;"></div>
            <a href="dashboard_admin.php" class="back-link">Back to Dashboard</a>
        </div>
    </div>

    <!-- Validation & Firebase Scripts -->
    <script src="../js/form_validation.js"></script>
    <script type="module" src="../js/add_user_sync.js"></script>
</body>
</html>
