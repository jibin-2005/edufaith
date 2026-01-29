<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$role = $_SESSION['role'];

// Fetch bulletins targeted at this role or 'all'
$sql = "SELECT title, content, created_at FROM announcements 
        WHERE target_role = 'all' OR target_role = ? 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $role);
$stmt->execute();
$bulletins = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulletins | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bulletin-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        .bulletin-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .bulletin-card .date {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        .bulletin-card .content {
            color: #555;
            line-height: 1.6;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <?php if ($role === 'student'): ?>
                <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_student.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                <li><a href="leave_student.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
                <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
                <li><a href="view_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
                <li><a href="bulletins.php" class="active"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'teacher'): ?>
                <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
                <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
                <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
                <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
                <li><a href="bulletins.php" class="active"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'parent'): ?>
                <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_parent.php"><i class="fa-solid fa-calendar-check"></i> Child Attendance</a></li>
                <li><a href="my_children.php"><i class="fa-solid fa-users"></i> My Children</a></li>
                <li><a href="bulletins.php" class="active"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php endif; ?>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Bulletins & Announcements</h2>
                <p>Stay updated with the latest news from St. Thomas Church</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="User">
                </div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <?php if ($bulletins->num_rows > 0): ?>
                <?php while($row = $bulletins->fetch_assoc()): ?>
                    <div class="bulletin-card">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="date"><i class="fa-regular fa-calendar"></i> <?php echo date("F j, Y", strtotime($row['created_at'])); ?></div>
                        <div class="content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-bullhorn" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No bulletins available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>
