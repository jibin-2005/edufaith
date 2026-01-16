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
$leave_sql = "SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'";
$leave_count = $conn->query($leave_sql)->fetch_assoc()['count'];

// Count Upcoming Events
$event_sql = "SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()";
$event_count = $conn->query($event_sql)->fetch_assoc()['count'];
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
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_admin.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i> Classes</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="manage_bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="attendance_admin.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Overview & Analytics</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Admin">
                </div>
            </div>
        </div>

        <!-- 1. KEY METRICS WIDGETS (Correctly Grouped) -->
        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $student_count; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="card-icon bg-blue"><i class="fa-solid fa-user-graduate"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $teacher_count; ?></h3>
                    <p>Total Teachers</p>
                </div>
                <div class="card-icon bg-purple"><i class="fa-solid fa-chalkboard-user"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $parent_count; ?></h3>
                    <p>Total Parents</p>
                </div>
                <div class="card-icon bg-green"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="card-icon bg-orange"><i class="fa-solid fa-database"></i></div>
            </div>
        </div>

        <!-- 2. ANALYTICS & QUICK ACTIONS (Addressing "Empty Space") -->
        <div class="section-grid">
            
            <!-- LEFT: Analytics Chart -->
            <div class="panel">
                <div class="panel-header">
                    <h3>User Distribution</h3>
                </div>
                <div style="position: relative; height:250px; width:100%; display:flex; justify-content:center;">
                    <canvas id="userChart"></canvas>
                </div>
            </div>

            <!-- RIGHT: Quick Shortcuts & Pending Leaves -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Pending Approvals (<?php echo $leave_count; ?>)</h3>
                     <a href="#" style="font-size:0.8rem; color:var(--primary);">View All</a>
                </div>
                <ul style="list-style:none;">
                <?php
                    $l_sql = "SELECT l.id, u.username, l.reason, l.start_date FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status='pending' LIMIT 3";
                    $l_res = $conn->query($l_sql);
                    if ($l_res->num_rows > 0) {
                        while($row = $l_res->fetch_assoc()) {
                            echo "<li style='border-bottom:1px solid #f0f0f0; padding:12px 0; display:flex; justify-content:space-between; align-items:center;'>";
                            echo "<div><strong>".htmlspecialchars($row['username'])."</strong><br><span style='font-size:0.8rem; color:#777;'>".htmlspecialchars($row['reason'])."</span></div>";
                            echo "<button style='padding:5px 10px; background:#e8f5e9; color:green; border:none; border-radius:4px; font-size:0.8rem;'>Review</button>";
                            echo "</li>";
                        }
                    } else {
                        echo "<li style='color:#999; text-align:center; padding:20px;'>No pending requests.</li>";
                    }
                ?>
                </ul>
                <div style="margin-top:20px;">
                    <button style="width:100%; padding:12px; border:1px dashed #ccc; background:#fafafa; color:#555; border-radius:8px; cursor:pointer;" onclick="alert('Quick Actions Menu')">+ Quick Action</button>
                </div>
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
                        <th>Target Group</th>
                        <th>Published Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ann_sql = "SELECT title, target_role, created_at FROM announcements ORDER BY created_at DESC LIMIT 3";
                    $ann_res = $conn->query($ann_sql);
                    if ($ann_res->num_rows > 0) {
                        while($row = $ann_res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td><span class='status present'>" . ucfirst($row['target_role']) . "</span></td>";
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
    </script>
</body>
</html>
