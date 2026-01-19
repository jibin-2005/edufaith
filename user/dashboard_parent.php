<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];

// Fetch Linked Children
$sql = "SELECT u.id, u.username, u.email, c.class_name 
        FROM users u 
        LEFT JOIN classes c ON u.class_id = c.id
        JOIN parent_student ps ON u.id = ps.student_id 
        WHERE ps.parent_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children = $stmt->get_result();
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
        .btn-view { margin-left: auto; padding: 8px 16px; background: #f0f4f8; color: var(--primary); border: none; border-radius: 20px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 13px; }
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
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Parent Dashboard • Linked Accounts Overview</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Parent">
                </div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <h3 style="margin-bottom: 20px;">Linked Students</h3>
            <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                <?php if ($children->num_rows > 0): ?>
                    <?php while($child = $children->fetch_assoc()): ?>
                        <div class="child-card">
                            <div class="child-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                            <div class="child-info">
                                <h3><?php echo htmlspecialchars($child['username']); ?></h3>
                                <p><i class="fa-solid fa-chalkboard"></i> <?php echo htmlspecialchars($child['class_name'] ?? 'Not Assigned'); ?></p>
                                <?php
                                // Fetch exam results for this child
                                $child_id = $child['id'];
                                $exam_sql = "SELECT exam_type, marks FROM results WHERE student_id = $child_id";
                                $exam_res = $conn->query($exam_sql);
                                $exam1 = null;
                                $exam2 = null;
                                if ($exam_res && $exam_res->num_rows > 0) {
                                    while($e = $exam_res->fetch_assoc()) {
                                        if ($e['exam_type'] === 'exam_1') $exam1 = $e['marks'];
                                        if ($e['exam_type'] === 'exam_2') $exam2 = $e['marks'];
                                    }
                                }
                                ?>
                                <div style="display: flex; gap: 15px; margin-top: 10px; font-size: 12px;">
                                    <div style="background: #e8f4fd; padding: 5px 10px; border-radius: 6px;">
                                        <strong>Exam 1:</strong> <?= $exam1 !== null ? $exam1.'/100' : 'N/A' ?>
                                    </div>
                                    <div style="background: #fef9e7; padding: 5px 10px; border-radius: 6px;">
                                        <strong>Exam 2:</strong> <?= $exam2 !== null ? $exam2.'/100' : 'N/A' ?>
                                    </div>
                                </div>
                            </div>
                            <a href="attendance_parent.php?student_id=<?php echo $child['id']; ?>" class="btn-view">View Attendance</a>
                        </div>
                    <?php endwhile; ?>
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
                $ann_sql = "SELECT title, created_at FROM announcements WHERE target_role IN ('parent', 'all') ORDER BY created_at DESC LIMIT 3";
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

        // Listen for result updates (affects child view)
        RealTimeSync.listen('result_updates', (data) => {
            console.log('Child result updated, refreshing list...');
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newList = doc.querySelector('div[style*="display: grid; grid-template-columns: 1fr;"]');
                    if (newList) {
                        document.querySelector('div[style*="display: grid; grid-template-columns: 1fr;"]').innerHTML = newList.innerHTML;
                    }
                });
        });

        RealTimeSync.listen('leave_updates', (data) => {
            console.log('Child leave status updated, refreshing...');
            window.location.reload();
        });
    </script>
</body>
</html>
