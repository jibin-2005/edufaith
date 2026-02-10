<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];

// Profile picture
$profile_picture = null;
$stmt_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_pic->bind_param("i", $parent_id);
$stmt_pic->execute();
$profile_picture = $stmt_pic->get_result()->fetch_assoc()['profile_picture'] ?? null;
$stmt_pic->close();

// Fetch linked children
$sql = "SELECT u.id, u.username, u.email, c.class_name 
        FROM users u 
        LEFT JOIN classes c ON u.class_id = c.id
        JOIN parent_student ps ON u.id = ps.student_id 
        WHERE ps.parent_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children_res = $stmt->get_result();
$children = [];
while ($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

// Summary metrics
$stmt_pending = $conn->prepare("SELECT COUNT(*) AS count 
                                FROM leave_requests lr 
                                JOIN parent_student ps ON lr.student_id = ps.student_id 
                                WHERE ps.parent_id = ? AND lr.status = 'pending'");
$stmt_pending->bind_param("i", $parent_id);
$stmt_pending->execute();
$pending_leaves = (int)$stmt_pending->get_result()->fetch_assoc()['count'];
$stmt_pending->close();

$stmt_msgs = $conn->prepare("SELECT COUNT(*) AS count FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt_msgs->bind_param("i", $parent_id);
$stmt_msgs->execute();
$unread_messages = (int)$stmt_msgs->get_result()->fetch_assoc()['count'];
$stmt_msgs->close();

$events_res = $conn->query("SELECT COUNT(*) AS count FROM events WHERE event_date >= CURDATE()");
$upcoming_events = $events_res ? (int)$events_res->fetch_assoc()['count'] : 0;
$children_count = count($children);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .child-card { background: white; padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s; border: 1px solid transparent; }
        .child-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .child-avatar { width: 60px; height: 60px; border-radius: 50%; background: #e8f4fd; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #3498db; }
        .child-info h3 { margin: 0; font-size: 18px; }
        .child-info p { margin: 5px 0 0; color: #777; font-size: 13px; }
        .btn-view { padding: 8px 16px; background: #f0f4f8; color: var(--primary); border: none; border-radius: 20px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 13px; }
        .btn-view:hover { background: var(--primary); color: white; }
    </style>
    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        window.RealTimeSync = RealTimeSync;
        RealTimeSync.checkAndTriggerFromURL();
    </script>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_parent.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="attendance_parent.php"><i class="fa-solid fa-calendar-check"></i> Child Attendance</a></li>
            <li><a href="my_children.php"><i class="fa-solid fa-users"></i> My Children</a></li>
            <li><a href="results_parent.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Profile</a></li>
        </ul>
        <div class="logout">
            <a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Parent Dashboard - Linked Accounts Overview</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <?php if (!empty($profile_picture) && file_exists('../' . $profile_picture)): ?>
                        <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Parent">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Parent">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid-container" style="margin-top: 20px;">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $children_count; ?></h3>
                    <p>Linked Students</p>
                </div>
                <div class="card-icon bg-blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $pending_leaves; ?></h3>
                    <p>Pending Leaves</p>
                </div>
                <div class="card-icon bg-purple"><i class="fa-solid fa-envelope-open-text"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $upcoming_events; ?></h3>
                    <p>Upcoming Events</p>
                </div>
                <div class="card-icon bg-green"><i class="fa-solid fa-calendar-days"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $unread_messages; ?></h3>
                    <p>Unread Messages</p>
                </div>
                <div class="card-icon bg-blue"><i class="fa-solid fa-envelope"></i></div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <h3 style="margin-bottom: 20px;">Linked Students</h3>
            <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                <?php if ($children_count > 0): ?>
                    <?php foreach ($children as $child): ?>
                        <div class="child-card">
                            <div class="child-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                            <div class="child-info">
                                <h3><?php echo htmlspecialchars($child['username']); ?></h3>
                                <p><i class="fa-solid fa-chalkboard"></i> <?php echo htmlspecialchars($child['class_name'] ?? 'Not Assigned'); ?></p>
                                <?php
                                $child_id = (int)$child['id'];

                                $att_stmt = $conn->prepare("SELECT COUNT(*) AS total, 
                                                           SUM(CASE WHEN status IN ('Present','Leave Approved') THEN 1 ELSE 0 END) AS present 
                                                           FROM attendance WHERE student_id = ?");
                                $att_stmt->bind_param("i", $child_id);
                                $att_stmt->execute();
                                $att_row = $att_stmt->get_result()->fetch_assoc();
                                $att_stmt->close();
                                $total_days = (int)($att_row['total'] ?? 0);
                                $present_days = (int)($att_row['present'] ?? 0);
                                $att_percent = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;

                                $exam_stmt = $conn->prepare("SELECT exam_type, marks FROM results WHERE student_id = ?");
                                $exam_stmt->bind_param("i", $child_id);
                                $exam_stmt->execute();
                                $exam_res = $exam_stmt->get_result();
                                $exam1 = null;
                                $exam2 = null;
                                while($e = $exam_res->fetch_assoc()) {
                                    if ($e['exam_type'] === 'exam_1') $exam1 = $e['marks'];
                                    if ($e['exam_type'] === 'exam_2') $exam2 = $e['marks'];
                                }
                                $exam_stmt->close();
                                ?>
                                <div style="display: flex; gap: 15px; margin-top: 10px; font-size: 12px;">
                                    <div style="background: #eafaf1; padding: 5px 10px; border-radius: 6px;">
                                        <strong>Attendance:</strong> <?= $att_percent ?>%
                                    </div>
                                    <div style="background: #e8f4fd; padding: 5px 10px; border-radius: 6px;">
                                        <strong>Exam 1:</strong> <?= $exam1 !== null ? $exam1.'/100' : 'N/A' ?>
                                    </div>
                                    <div style="background: #fef9e7; padding: 5px 10px; border-radius: 6px;">
                                        <strong>Exam 2:</strong> <?= $exam2 !== null ? $exam2.'/100' : 'N/A' ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <a href="attendance_parent.php?student_id=<?php echo $child['id']; ?>" class="btn-view">Attendance</a>
                                <a href="results_parent.php?student_id=<?php echo $child['id']; ?>" class="btn-view">Results</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="panel" style="text-align: center; padding: 40px;">
                        <i class="fa-solid fa-link-slash" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i>
                        <p>No children are linked to your account yet. Please contact the administrator.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RECENT ANNOUNCEMENTS -->
        <div class="panel" style="margin-top: 30px;">
            <div class="panel-header">
                <h3>Church Announcements</h3>
            </div>
            <ul style="list-style: none; padding: 0;">
                <?php
                $ann_sql = "SELECT title, created_at FROM announcements 
                            WHERE target_role IN ('all','parent') 
                            ORDER BY created_at DESC LIMIT 3";
                $ann_res = $conn->query($ann_sql);
                if ($ann_res->num_rows > 0) {
                    while($row = $ann_res->fetch_assoc()) {
                        echo "<li style='border-bottom: 1px solid #f0f0f0; padding:15px 0;'>";
                        echo "<div style='display:flex; justify-content:space-between;'>";
                        echo "<strong>".htmlspecialchars($row['title'])."</strong>";
                        echo "<small style='color:#999;'>".date('M j', strtotime($row['created_at']))."</small>";
                        echo "</div></li>";
                    }
                } else {
                    echo "<p style='color:#999; text-align:center;'>No announcements at this time.</p>";
                }
                ?>
            </ul>
        </div>
    </div>

    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';

        RealTimeSync.listen('result_updates', () => {
            window.location.reload();
        });

        RealTimeSync.listen('leave_updates', () => {
            window.location.reload();
        });
    </script>
    <script>\n        setInterval(() => {\n            window.location.reload();\n        }, 60000);\n    </script>\n</body>
</html>

