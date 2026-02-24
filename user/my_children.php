<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];

$sql = "SELECT s.id, s.username, s.email, c.class_name, c.teacher_id 
        FROM parent_student ps 
        JOIN users s ON ps.student_id = s.id 
        LEFT JOIN classes c ON s.class_id = c.id
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Children | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .child-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .child-info h3 { margin: 0 0 5px 0; color: #2c3e50; }
        .child-info p { margin: 0; color: #7f8c8d; font-size: 0.9rem; }
        .stats { display: flex; gap: 20px; align-items: center; }
        .stat-box { text-align: center; }
        .stat-val { font-size: 1.2rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: #777; }
        .btn-link { padding: 6px 12px; border-radius: 16px; background: #f0f4f8; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 12px; }
        .btn-link:hover { background: var(--primary); color: #fff; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Children</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (count($children) > 0): ?>
            <?php foreach ($children as $child): ?>
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
                <div class="child-card">
                    <div class="child-info">
                        <h3><?php echo htmlspecialchars($child['username']); ?></h3>
                        <p><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($child['class_name'] ?? 'Unassigned'); ?></p>
                        <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($child['email']); ?></p>
                    </div>
                    <div class="stats">
                        <div class="stat-box">
                            <div class="stat-val"><?php echo $att_percent; ?>%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val"><?php echo $exam1 !== null ? $exam1 : 'N/A'; ?></div>
                            <div class="stat-label">Exam 1</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val"><?php echo $exam2 !== null ? $exam2 : 'N/A'; ?></div>
                            <div class="stat-label">Exam 2</div>
                        </div>
                        <div class="stat-box">
                            <a class="btn-link" href="attendance_parent.php?student_id=<?php echo $child_id; ?>">Attendance</a>
                        </div>
                        <div class="stat-box">
                            <a class="btn-link" href="results_parent.php?student_id=<?php echo $child_id; ?>">Results</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:50px; background:white; border-radius:10px;">
                <p>No children linked to your account yet.</p>
                <p style="color:#777; font-size:0.9rem;">Please ask the Admin to link your account to your student.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

