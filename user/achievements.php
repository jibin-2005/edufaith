<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Achievements | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .badge-card { background: white; padding: 30px; border-radius: 12px; text-align: center; width: 150px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .badge-card i { font-size: 40px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-star"></i> Achievements</a></li>
            <li><a href="calendar.php"><i class="fa-solid fa-calendar-check"></i> Calendar</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Achievements</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="badge-grid">
            <div class="badge-card">
                <i class="fa-solid fa-medal" style="color: gold;"></i>
                <h4>Perfect Attendance</h4>
            </div>
            <div class="badge-card">
                <i class="fa-solid fa-star" style="color: orange;"></i>
                <h4>Verse Master</h4>
            </div>
            <div class="badge-card">
                <i class="fa-solid fa-hands-praying" style="color: #3498db;"></i>
                <h4>Prayer Warrior</h4>
            </div>
            <div class="badge-card" style="opacity:0.5; background:#eee;">
                <i class="fa-solid fa-lock" style="color: #bbb;"></i>
                <h4>Quiz Champ</h4>
                <p style="font-size:12px; color:#999;">Locked</p>
            </div>
        </div>
    </div>
</body>
</html>
