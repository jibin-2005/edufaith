<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];

// Fetch Linked Children
$sql = "SELECT u.id, u.username, u.email, u.class_id, 
        (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id AND a.status = 'present') as present_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.user_id = u.id) as total_attendance
        FROM users u 
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
    <title>Parent Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .child-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #fff; }
        .stats { display: flex; gap: 20px; font-weight: bold; margin-top: 10px; }
        .stat-box { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div style="background: #333; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h1>Parent Dashboard</h1>
        <div>
            <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../index.html" style="color: #ff9999; margin-left: 15px; text-decoration: none;">Logout</a>
        </div>
    </div>

    <div style="max-width: 1000px; margin: 20px auto; padding: 20px;">
        <h2>My Children</h2>
        
        <?php if ($children->num_rows > 0): ?>
            <?php while($child = $children->fetch_assoc()): ?>
                <?php 
                    $percent = ($child['total_attendance'] > 0) 
                        ? round(($child['present_count'] / $child['total_attendance']) * 100, 1) 
                        : 0; 
                ?>
                <div class="child-card">
                    <h3><?= htmlspecialchars($child['username']) ?></h3>
                    <p>Class ID: <?= htmlspecialchars($child['class_id'] ?? 'Not Assigned') ?></p>
                    
                    <div class="stats">
                        <div class="stat-box">Attendance: <?= $percent ?>%</div>
                        <div class="stat-box">Present: <?= $child['present_count'] ?></div>
                        <div class="stat-box">Total Days: <?= $child['total_attendance'] ?></div>
                    </div>
                    
                    <!-- NEW: Results Section -->
                    <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <h4 style="margin: 0 0 10px 0; color: #555;">Exam Results</h4>
                        <?php
                            $r_sql = "SELECT marks, updated_at FROM results WHERE student_id = " . $child['id'];
                            $r_res = $conn->query($r_sql);
                            if ($r_res->num_rows > 0) {
                                $r_row = $r_res->fetch_assoc();
                                echo "<div style='background: #e8f4fc; padding: 10px; border-radius: 4px; display: inline-block;'>";
                                echo "<span style='font-size: 18px; font-weight: bold; color: #2980b9;'>" . $r_row['marks'] . "/100</span>";
                                echo "<span style='font-size: 12px; color: #777; margin-left: 10px;'>(" . date('d M Y', strtotime($r_row['updated_at'])) . ")</span>";
                                echo "</div>";
                            } else {
                                echo "<p style='color: #999; font-style: italic; font-size: 13px;'>No results published yet.</p>";
                            }
                        ?>
                    </div>

                    <!-- NEW: Assignments Section -->
                    <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <h4 style="margin: 0 0 10px 0; color: #555;">Upcoming Assignments</h4>
                        <?php
                            if ($child['class_id']) {
                                $a_sql = "SELECT title, due_date FROM assignments WHERE class_id = " . $child['class_id'] . " AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 3";
                                $a_res = $conn->query($a_sql);
                                if ($a_res->num_rows > 0) {
                                    echo "<ul style='padding-left: 20px; margin: 0;'>";
                                    while($a_row = $a_res->fetch_assoc()) {
                                        echo "<li style='margin-bottom: 5px; color: #444;'>";
                                        echo "<strong>" . htmlspecialchars($a_row['title']) . "</strong> ";
                                        echo "<span style='color: #e74c3c; font-size: 12px;'>Due: " . date('M j', strtotime($a_row['due_date'])) . "</span>";
                                        echo "</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<p style='color: #999; font-style: italic; font-size: 13px;'>No impending assignments.</p>";
                                }
                            } else {
                                echo "<p style='color: #999; font-style: italic; font-size: 13px;'>Class not assigned.</p>";
                            }
                        ?>
                    </div>

                    <div style="margin-top: 15px;">
                        <a href="view_attendance.php?student_id=<?= $child['id'] ?>" class="btn-small" style="display:inline-block; padding:8px 12px; background:#f0f0f0; color:#333; text-decoration:none; border-radius:4px; font-size:13px;">View Detailed Attendance</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No children linked to your account yet. Please contact the administrator.</p>
        <?php endif; ?>
    </div>
</body>
</html>
