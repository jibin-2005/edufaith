<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$result = $conn->query("SELECT * FROM announcements WHERE target_role IN ('all', 'parent') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid var(--primary); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_children.php"><i class="fa-solid fa-child-reaching"></i> My Children</a></li>
            <li><a href="payments.php"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-envelope"></i> Messages</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Messages & Announcements</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="message-card">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p style="color:#777; font-size:12px; margin-bottom:10px;">
                    <?php echo date("M j, Y h:i A", strtotime($row['created_at'])); ?>
                </p>
                <p><?php echo htmlspecialchars($row['content']); ?></p>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
