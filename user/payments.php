<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_children.php"><i class="fa-solid fa-child-reaching"></i> My Children</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Fee Payments</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div style="background:white; padding:40px; text-align:center; border-radius:8px;">
            <i class="fa-solid fa-circle-check" style="font-size:60px; color:green; margin-bottom:20px;"></i>
            <h3>No Pending Fees</h3>
            <p>You are all caught up! No dues for this semester.</p>
        </div>
    </div>
</body>
</html>
