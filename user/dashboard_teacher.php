<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

$teacher_id = $_SESSION['user_id'];

// Profile picture
$profile_picture = null;
$stmt_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_pic->bind_param("i", $teacher_id);
$stmt_pic->execute();
$profile_picture = $stmt_pic->get_result()->fetch_assoc()['profile_picture'] ?? null;
$stmt_pic->close();

// 1. Fetch Summary Data
// Total Students in teacher's classes
$stmt_students = $conn->prepare("SELECT COUNT(*) as count
                                 FROM users u
                                 JOIN classes c ON u.class_id = c.id
                                 WHERE u.role = 'student' AND u.status = 'active' AND c.teacher_id = ?");
$stmt_students->bind_param("i", $teacher_id);
$stmt_students->execute();
$total_students = $stmt_students->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_students->close();

// Present/Absent Today
$date = date("Y-m-d");
$present_count = 0;
$absent_count = 0;

$stmt_att_stats = $conn->prepare("SELECT `status`, COUNT(*) as count
                                  FROM attendance a
                                  JOIN classes c ON a.class_id = c.id
                                  WHERE c.teacher_id = ? AND a.`date` = ?
                                  GROUP BY `status`");
if ($stmt_att_stats) {
    $stmt_att_stats->bind_param("is", $teacher_id, $date);
    $stmt_att_stats->execute();
    $result_att_stats = $stmt_att_stats->get_result();
} else {
    $result_att_stats = false;
}
if ($result_att_stats) {
    while ($row = $result_att_stats->fetch_assoc()) {
        if ($row['status'] == 'Present') $present_count = $row['count'];
        if ($row['status'] == 'Absent') $absent_count = $row['count'];
    }
}
$stmt_att_stats && $stmt_att_stats->close();

// Class Average Attendance (last 30 days)
$stmt_avg = $conn->prepare("SELECT ROUND(SUM(CASE WHEN a.`status` = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS rate
                            FROM attendance a
                            JOIN classes c ON a.class_id = c.id
                            WHERE c.teacher_id = ? AND a.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($stmt_avg) {
    $stmt_avg->bind_param("i", $teacher_id);
    $stmt_avg->execute();
    $avg_attendance = $stmt_avg->get_result()->fetch_assoc()['rate'] ?? 0;
} else {
    $avg_attendance = 0;
}
$stmt_avg && $stmt_avg->close();

// Pending leave requests for teacher's classes
$stmt_pending = $conn->prepare("SELECT COUNT(*) as count
                                FROM leave_requests lr
                                JOIN classes c ON lr.class_id = c.id
                                WHERE c.teacher_id = ? AND lr.status = 'pending'");
$stmt_pending->bind_param("i", $teacher_id);
$stmt_pending->execute();
$pending_leaves = $stmt_pending->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_pending->close();

// 2. Fetch Weekly Attendance for Chart (last 5 Sundays)
$weekly_stats = [];
$stmt_weekly = $conn->prepare("SELECT a.`date`, 
                               ROUND(SUM(CASE WHEN a.`status` = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as percentage 
                               FROM attendance a
                               JOIN classes c ON a.class_id = c.id
                               WHERE c.teacher_id = ?
                               GROUP BY a.`date` 
                               ORDER BY a.`date` DESC LIMIT 5");
if ($stmt_weekly) {
    $stmt_weekly->bind_param("i", $teacher_id);
    $stmt_weekly->execute();
    $res_weekly = $stmt_weekly->get_result();
} else {
    $res_weekly = false;
}
if ($res_weekly) {
    while ($row = $res_weekly->fetch_assoc()) {
        $weekly_stats[] = $row;
    }
}
$weekly_stats = array_reverse($weekly_stats); // Chronological order
$stmt_weekly && $stmt_weekly->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .radio-group { display: flex; gap: 10px; }
        .radio-label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px; }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .chart-panel { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Teacher Dashboard • <?php echo date("l, F j, Y"); ?></p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- STATS -->
        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>My Students</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="card" style="grid-column: span 2;">
                <div class="card-info">
                    <h3><?php echo $avg_attendance; ?>%</h3>
                    <p>Class Average Attendance</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $present_count; ?></h3>
                    <p>Present Today</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $pending_leaves; ?></h3>
                    <p>Pending Leaves</p>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'attendance_saved'): ?>
            <div class="success-msg">Attendance saved successfully!</div>
        <?php endif; ?>

        <div class="section-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
            <!-- QUICK ACTIONS -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Class Actions</h3>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button style="flex:1; padding:12px; background:#e8f4fd; color:#3498db; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Assignment creation modal will open here')">
                        <i class="fa-solid fa-file-pen"></i> Give Assignment
                    </button>
                    <button style="flex:1; padding:12px; background:#f4ecff; color:#9b59b6; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Announcement modal will open here')">
                        <i class="fa-solid fa-bullhorn"></i> Announcement
                    </button>
                    <button style="flex:1; padding:12px; background:#eafaf1; color:#2ecc71; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Lesson plan modal will open here')">
                        <i class="fa-solid fa-book-open"></i> Plan Lesson
                    </button>
                </div>
            </div>

            <!-- LEAVE REQUESTS -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Leave Requests</h3>
                </div>
                <ul>
                    <?php
                    $l_stmt = $conn->prepare("SELECT lr.id, u.username, lr.reason, lr.leave_date
                                              FROM leave_requests lr
                                              JOIN users u ON lr.student_id = u.id
                                              JOIN classes c ON lr.class_id = c.id
                                              WHERE c.teacher_id = ? AND lr.status = 'pending'
                                              ORDER BY lr.leave_date ASC LIMIT 3");
                    $l_stmt->bind_param("i", $teacher_id);
                    $l_stmt->execute();
                    $l_res = $l_stmt->get_result();
                    if ($l_res->num_rows > 0) {
                        while($row = $l_res->fetch_assoc()) {
                            echo "<li style='border-bottom:1px solid #eee; padding:10px 0; display:flex; justify-content:space-between; align-items:center;'>";
                            echo "<div><strong>".htmlspecialchars($row['username'])."</strong><br><small>".htmlspecialchars($row['reason'])."</small></div>";
                            echo "<div><a href='manage_leaves.php' style='color:var(--primary); text-decoration:none; font-weight:600;'>View</a></div>";
                            echo "</li>";
                        }
                    } else {
                         echo "<p style='color:#999; text-align:center;'>No pending requests.</p>";
                    }
                    $l_stmt->close();
                    ?>
                </ul>
            </div>
        </div>

        <div class="panel chart-panel">
            <div class="panel-header">
                <h3>Class Attendance Trend</h3>
            </div>
            <div style="height: 300px;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($weekly_stats, 'date')); ?>,
                datasets: [{
                    label: 'Attendance %',
                    data: <?php echo json_encode(array_column($weekly_stats, 'percentage')); ?>,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#2ecc71'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + "%" }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Auto-refresh dashboard every 60 seconds for live stats
        setInterval(() => {
            window.location.reload();
        }, 60000);
    </script>

</body>
</html>

