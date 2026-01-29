<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$role = $_SESSION['role'];

// Fetch upcoming events
$sql = "SELECT title, event_date, description FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .event-date-box {
            background: linear-gradient(135deg, var(--primary), #5a4fcf);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            min-width: 70px;
        }
        .event-date-box .day {
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }
        .event-date-box .month {
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .event-details h3 {
            margin: 0 0 8px 0;
            color: #333;
        }
        .event-details .time {
            font-size: 13px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .event-details .description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
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
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'teacher'): ?>
                <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
                <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
                <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
                <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'parent'): ?>
                <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_parent.php"><i class="fa-solid fa-calendar-check"></i> Child Attendance</a></li>
                <li><a href="my_children.php"><i class="fa-solid fa-users"></i> My Children</a></li>
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
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
                <h2>Upcoming Events</h2>
                <p>Church events and activities calendar</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="User">
                </div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-date-box">
                            <div class="day"><?php echo date("j", strtotime($row['event_date'])); ?></div>
                            <div class="month"><?php echo date("M", strtotime($row['event_date'])); ?></div>
                        </div>
                        <div class="event-details">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="time"><i class="fa-regular fa-clock"></i> <?php echo date("l, g:i A", strtotime($row['event_date'])); ?></div>
                            <?php if (!empty($row['description'])): ?>
                                <div class="description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No upcoming events scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>
