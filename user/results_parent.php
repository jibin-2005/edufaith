<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];
$selected_student_id = $_GET['student_id'] ?? '';

// Fetch linked children for selector
$sql_children = "SELECT u.id, u.username 
                 FROM users u 
                 JOIN parent_student ps ON u.id = ps.student_id 
                 WHERE ps.parent_id = ?";
$stmt_kids = $conn->prepare($sql_children);
$stmt_kids->bind_param("i", $parent_id);
$stmt_kids->execute();
$children_res = $stmt_kids->get_result();
$children = [];
while ($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}
$stmt_kids->close();

if (empty($selected_student_id) && count($children) > 0) {
    $selected_student_id = $children[0]['id'];
}

$results = [];
$avg = null;
if ($selected_student_id) {
    $check = $conn->prepare("SELECT 1 FROM parent_student WHERE parent_id = ? AND student_id = ?");
    $check->bind_param("ii", $parent_id, $selected_student_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("SELECT exam_type, marks, updated_at FROM results WHERE student_id = ? ORDER BY updated_at DESC");
        $stmt->bind_param("i", $selected_student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $sum = 0;
        $count = 0;
        while ($row = $res->fetch_assoc()) {
            $results[] = $row;
            $sum += (int)$row['marks'];
            $count++;
        }
        $avg = $count > 0 ? round($sum / $count, 1) : null;
        $stmt->close();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Results | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .child-selector { background: white; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; border-left: 4px solid var(--primary); }
        .stat-card { background: white; padding: 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .badge-exam { background: #e8f4fd; color: #3498db; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Results Overview</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (count($children) > 1): ?>
            <div class="child-selector">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Switch Child:</span>
                <form method="GET" style="display:inline;">
                    <select name="student_id" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                        <?php foreach($children as $child): ?>
                            <option value="<?php echo $child['id']; ?>" <?php echo ($selected_student_id == $child['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($child['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($selected_student_id): ?>
            <div class="grid-container" style="margin-bottom: 20px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eafaf1; color:#2ecc71;"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <h3 style="margin:0;"><?php echo $avg !== null ? $avg : 'N/A'; ?></h3>
                        <p style="margin:0; color:#777; font-size:13px;">Average Score</p>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>Exam Results</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($results) > 0): ?>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><span class="badge-exam"><?php echo htmlspecialchars(str_replace('_', ' ', $row['exam_type'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['marks']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['updated_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; color:#999;">No results available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 100px; color: #999;">
                <i class="fa-solid fa-user-slash" style="font-size: 60px; margin-bottom: 20px; opacity: 0.2;"></i>
                <p>No student accounts linked to your profile.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

