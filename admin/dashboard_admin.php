<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Fetch Counts
$teacher_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")->fetch_assoc()['count'];
$student_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$parent_count  = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent'")->fetch_assoc()['count'];
$total_users   = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Count Pending Leaves
$leave_sql = "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'";
$leave_count = $conn->query($leave_sql)->fetch_assoc()['count'];

// Count Upcoming Events
$event_sql = "SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()";
$event_count = $conn->query($event_sql)->fetch_assoc()['count'];

// Attendance Trend (Last 5 Sundays)
$trend_sql = "SELECT date, COUNT(*) as total, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present 
              FROM attendance GROUP BY date ORDER BY date DESC LIMIT 5";
$trend_res = $conn->query($trend_sql);
$trend_labels = []; $trend_data = [];
if ($trend_res) {
    while($row = $trend_res->fetch_assoc()) {
        $trend_labels[] = date('M j', strtotime($row['date']));
        $trend_data[] = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100) : 0;
    }
}
$trend_labels = array_reverse($trend_labels);
$trend_data = array_reverse($trend_data);

// Class Performance
$perf_sql = "SELECT c.class_name, AVG(r.marks) as avg_marks 
             FROM results r 
             JOIN users u ON r.student_id = u.id 
             JOIN classes c ON u.class_id = c.id 
             GROUP BY c.id ORDER BY avg_marks DESC LIMIT 8";
$perf_res = $conn->query($perf_sql);
$perf_labels = []; $perf_data = [];
if ($perf_res) {
    while($row = $perf_res->fetch_assoc()) {
        $perf_labels[] = $row['class_name'];
        $perf_data[] = round($row['avg_marks'], 1);
    }
}

// Key Performance Indicators (KPIs)
$avg_attendance = $conn->query("SELECT ROUND(AVG(CASE WHEN status='Present' THEN 100 ELSE 0 END)) as rate FROM attendance")->fetch_assoc()['rate'] ?? 0;
$avg_marks = $conn->query("SELECT ROUND(AVG(marks), 1) as avg FROM results")->fetch_assoc()['avg'] ?? 0;

// Profile picture
$profile_picture = null;
$stmt_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_pic->bind_param("i", $_SESSION['user_id']);
$stmt_pic->execute();
$profile_picture = $stmt_pic->get_result()->fetch_assoc()['profile_picture'] ?? null;
$stmt_pic->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> <span class="badge-live"><span class="pulse-dot"></span>Live Analytics</span></h2>
                <p>Overview & Performance Metrics</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- 1. KEY METRICS WIDGETS -->
        <div class="grid-container" style="grid-template-columns: repeat(5, 1fr);">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $student_count; ?></h3>
                    <p>Students</p>
                </div>
                <div class="card-icon bg-blue"><i class="fa-solid fa-user-graduate"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $teacher_count; ?></h3>
                    <p>Teachers</p>
                </div>
                <div class="card-icon bg-purple"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $avg_attendance; ?>%</h3>
                    <p>Avg Attn.</p>
                </div>
                <div class="card-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $avg_marks; ?></h3>
                    <p>Avg Marks</p>
                </div>
                <div class="card-icon bg-orange"><i class="fa-solid fa-star"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $event_count; ?></h3>
                    <p>Events</p>
                </div>
                <div class="card-icon bg-blue" style="background:#e8f4fd; color:#3498db;"><i class="fa-solid fa-calendar-days"></i></div>
            </div>
        </div>

        <!-- 2. ANALYTICS GRAPHS -->
        <div class="analytics-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3>Attendance Trend</h3>
                </div>
                <div style="height:250px;"><canvas id="attendanceChart"></canvas></div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <h3>Class Performance</h3>
                </div>
                <div style="height:250px;"><canvas id="performanceChart"></canvas></div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <h3>User Base</h3>
                </div>
                <div style="height:250px;"><canvas id="userChart"></canvas></div>
            </div>
        </div>

        <!-- 3. OPERATIONAL LISTS -->
        <div class="operational-grid">
            <!-- Recent Marks Entry -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Recent Marks Entry</h3>
                     <a href="manage_results.php" style="font-size:0.8rem; color:var(--primary);">View All</a>
                </div>
                <ul style="list-style:none;">
                <?php
                    $r_sql = "SELECT u.username, r.marks, r.updated_at FROM results r JOIN users u ON r.student_id = u.id ORDER BY r.updated_at DESC LIMIT 4";
                    $r_res = $conn->query($r_sql);
                    if ($r_res && $r_res->num_rows > 0) {
                        while($row = $r_res->fetch_assoc()) {
                            echo "<li style='border-bottom:1px solid #f0f0f0; padding:12px 0; display:flex; justify-content:space-between; align-items:center;'>";
                            echo "<div><strong>".htmlspecialchars($row['username'])."</strong><br><span style='font-size:0.8rem; color:#777;'>Score: ".$row['marks']."</span></div>";
                            echo "<span style='font-size:0.8rem; color:#aaa;'>".date('M j', strtotime($row['updated_at']))."</span>";
                            echo "</li>";
                        }
                    } else { echo "<li style='color:#999; text-align:center; padding:20px;'>No records found.</li>"; }
                ?>
                </ul>
            </div>

            <!-- Pending Leaves -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Pending Approvals (<?php echo $leave_count; ?>)</h3>
                     <a href="attendance_admin.php" style="font-size:0.8rem; color:var(--primary);">View All</a>
                </div>
                <ul style="list-style:none;">
                <?php
                    $l_sql = "SELECT l.id, u.username, l.reason FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status='pending' LIMIT 4";
                    $l_res = $conn->query($l_sql);
                    if ($l_res && $l_res->num_rows > 0) {
                        while($row = $l_res->fetch_assoc()) {
                            echo "<li style='border-bottom:1px solid #f0f0f0; padding:12px 0; display:flex; justify-content:space-between; align-items:center;'>";
                            echo "<div><strong>".htmlspecialchars($row['username'])."</strong><br><span style='font-size:0.8rem; color:#777;'>".htmlspecialchars($row['reason'])."</span></div>";
                            echo "<button style='padding:5px 10px; background:#e8f5e9; color:green; border:none; border-radius:4px; font-size:0.8rem;'>Review</button>";
                            echo "</li>";
                        }
                    } else { echo "<li style='color:#999; text-align:center; padding:20px;'>No pending requests.</li>"; }
                ?>
                </ul>
            </div>
        </div>

        <!-- 3. DETAILED TABLES -->
        <div class="panel" style="margin-top:30px;">
             <div class="panel-header">
                <h3>Recent Announcements</h3>
                <a href="manage_bulletins.php" class="btn-primary" style="font-size:0.8rem;">Manage</a>
            </div>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Published Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ann_sql = "SELECT title, created_at FROM announcements ORDER BY created_at DESC LIMIT 3";
                    $ann_res = $conn->query($ann_sql);
                    if ($ann_res->num_rows > 0) {
                        while($row = $ann_res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . date("M j, Y", strtotime($row['created_at'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No recent announcements.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('userChart').getContext('2d');
        const userChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Teachers', 'Parents', 'Admins'],
                datasets: [{
                    data: [<?php echo $student_count; ?>, <?php echo $teacher_count; ?>, <?php echo $parent_count; ?>, <?php echo max(0, $total_users - $student_count - $teacher_count - $parent_count); ?>],
                    backgroundColor: [
                        '#3498db',
                        '#9b59b6',
                        '#2ecc71',
                        '#95a5a6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: "'Outfit', sans-serif" },
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Attendance Trend Chart
        new Chart(document.getElementById('attendanceChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Attendance %',
                    data: <?php echo json_encode($trend_data); ?>,
                    borderColor: '#3498db',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)'
                }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // Performance Chart
        new Chart(document.getElementById('performanceChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($perf_labels); ?>,
                datasets: [{
                    label: 'Avg Marks',
                    data: <?php echo json_encode($perf_data); ?>,
                    backgroundColor: '#2ecc71',
                    borderRadius: 5
                }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    </script>
    <script>\n        setInterval(() => {\n            window.location.reload();\n        }, 60000);\n    </script>\n</body>
</html>


