<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$result = $conn->query("SELECT * FROM assignments ORDER BY due_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Lessons | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="view_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="achievements.php"><i class="fa-solid fa-star"></i> Achievements</a></li>
            <li><a href="calendar.php"><i class="fa-solid fa-calendar-check"></i> Calendar</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Lessons & Assignments</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Get Student Class
                    $my_id = $_SESSION['user_id'];
                    $c_res = $conn->query("SELECT class_id FROM users WHERE id = $my_id");
                    $my_class = $c_res->fetch_assoc()['class_id'] ?? 0;
                    
                    // Filter assignments by class
                    $result = $conn->query("SELECT * FROM assignments WHERE class_id = $my_class ORDER BY due_date ASC");
                    
                    while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td style="color:red; font-weight:bold;"><?php echo date("M j, Y", strtotime($row['due_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
