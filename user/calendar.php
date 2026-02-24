<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$result = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Calendar | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid var(--primary); }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Upcoming Events</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php while($row = $result->fetch_assoc()): ?>
        <div class="event-card">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p style="color:#777; margin-bottom:10px;"><i class="fa-solid fa-clock"></i> <?php echo date("l, F j, Y h:i A", strtotime($row['event_date'])); ?></p>
            <p><?php echo htmlspecialchars($row['description']); ?></p>
        </div>
        <?php endwhile; ?>
    </div>
</body>
</html>

