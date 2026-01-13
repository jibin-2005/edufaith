<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];

// Fetch Linked Children
$sql = "SELECT s.id, s.username, s.email, c.class_name 
        FROM parent_student ps 
        JOIN users s ON ps.student_id = s.id 
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE ps.parent_id = $parent_id";
$children = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Children | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .child-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .child-info h3 { margin: 0 0 5px 0; color: #2c3e50; }
        .child-info p { margin: 0; color: #7f8c8d; font-size: 0.9rem; }
        .stats { display: flex; gap: 20px; }
        .stat-box { text-align: center; }
        .stat-val { font-size: 1.2rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: #777; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_children.php" class="active"><i class="fa-solid fa-child-reaching"></i> My Children</a></li>
            <li><a href="calendar.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-message"></i> Messages</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Children</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if ($children->num_rows > 0): ?>
            <?php while($child = $children->fetch_assoc()): ?>
                <div class="child-card">
                    <div class="child-info">
                        <h3><?php echo htmlspecialchars($child['username']); ?></h3>
                        <p><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($child['class_name'] ?? 'Unassigned'); ?></p>
                        <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($child['email']); ?></p>
                    </div>
                    <div class="stats">
                        <div class="stat-box">
                            <!-- Placeholder stats - would query real attendance/grades per child -->
                            <div class="stat-val">92%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val">A</div>
                            <div class="stat-label">Grade</div>
                        </div>
                         <div class="stat-box">
                            <a href="#" style="font-size:0.9rem; color:blue;">View Report</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:50px; background:white; border-radius:10px;">
                <p>No children linked to your account yet.</p>
                <p style="color:#777; font-size:0.9rem;">Please ask the Admin to link your account to your student.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
