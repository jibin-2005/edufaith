<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Mock -> Real: Fetch first linked child
$child_id = 0;
$child_name = "No Child Linked";
$child_grade = "N/A";

// Get linked child
$link_sql = "SELECT s.id, s.username, c.class_name 
             FROM parent_student ps 
             JOIN users s ON ps.student_id = s.id 
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE ps.parent_id = " . $_SESSION['user_id'] . " LIMIT 1";
$link_res = $conn->query($link_sql);

if ($link_res->num_rows > 0) {
    $child = $link_res->fetch_assoc();
    $child_id = $child['id'];
    $child_name = $child['username'];
    $child_grade = $child['class_name'] ?? 'Unassigned';
}

// 1. Calculate Attendance %
if ($child_id > 0) {
    $total_days_sql = "SELECT COUNT(*) as count FROM attendance WHERE user_id = $child_id";
    $present_days_sql = "SELECT COUNT(*) as count FROM attendance WHERE user_id = $child_id AND status = 'present'";
    $total_days = $conn->query($total_days_sql)->fetch_assoc()['count'];
    $present_days = $conn->query($present_days_sql)->fetch_assoc()['count'];
    $att_percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100) : 0;
} else {
    $att_percentage = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_parent.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_children.php"><i class="fa-solid fa-child-reaching"></i> My Children</a></li>
            <li><a href="payments.php"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
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
                <p>Tracking your children's spiritual growth.</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Parent">
                </div>
            </div>
        </div>

        <!-- CHILD SELECTOR TAB -->
        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <button style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 20px; font-weight: 500;">
                <?php echo htmlspecialchars($child_name); ?> (<?php echo htmlspecialchars($child_grade); ?>)
            </button>
            <?php if ($child_id == 0): ?>
                <span style="color:red; align-self:center; font-size:0.9rem; margin-left:10px;">Please contact Admin to link your child.</span>
            <?php endif; ?>
        </div>

        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $att_percentage; ?>%</h3>
                    <p><?php echo $child_name; ?>'s Attendance</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>$0</h3>
                    <p>Fees Pending</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3>Announcements & Remarks</h3>
                </div>
                <?php
                $ann_sql = "SELECT title, content, created_at FROM announcements WHERE target_role IN ('all', 'parent') ORDER BY created_at DESC LIMIT 3";
                $ann_res = $conn->query($ann_sql);
                if ($ann_res->num_rows > 0) {
                    while($row = $ann_res->fetch_assoc()) {
                        echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary); margin-bottom:10px;'>";
                        echo "<h4>" . htmlspecialchars($row['title']) . "</h4>";
                        echo "<p style='font-size: 14px; color: #555;'>" . htmlspecialchars($row['content']) . "</p>";
                        echo "<p style='font-size: 12px; color: #aaa; margin-top: 5px;'>" . date("M j", strtotime($row['created_at'])) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No recent announcements.</p>";
                }
                ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Upcoming School Events</h3>
                </div>
                <?php
                $e_sql = "SELECT title, event_date, description FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3";
                $e_res = $conn->query($e_sql);
                if ($e_res->num_rows > 0) {
                    while($row = $e_res->fetch_assoc()) {
                        echo "<div style='border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-bottom:10px;'>";
                        echo "<h4 style='margin-bottom: 5px;'>" . htmlspecialchars($row['title']) . "</h4>";
                        echo "<p style='font-size: 13px; color: #7f8c8d; margin-bottom: 5px;'>" . date("M j, h:i A", strtotime($row['event_date'])) . "</p>";
                        echo "<p style='font-size: 13px;'>" . htmlspecialchars($row['description']) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No upcoming events.</p>";
                }
                ?>
            </div>
        </div>
        
    </div>

</body>
</html>
