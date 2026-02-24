<?php
session_start();
require '../includes/db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

$viewer_id = $_SESSION['user_id'];
$viewer_role = $_SESSION['role'];
$target_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $viewer_id;

// Authorization Logic
$authorized = false;
if ($viewer_role === 'admin') {
    $authorized = true;
} elseif ($viewer_role === 'teacher') {
    $stmt = $conn->prepare("SELECT 1 FROM users u JOIN classes c ON u.class_id = c.id WHERE u.id = ? AND c.teacher_id = ?");
    $stmt->bind_param("ii", $target_student_id, $viewer_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $authorized = true;
    $stmt->close();
} elseif ($viewer_role === 'student') {
    if ($target_student_id == $viewer_id) $authorized = true;
} elseif ($viewer_role === 'parent') {
    // Check linkage
    $stmt = $conn->prepare("SELECT 1 FROM parent_student WHERE parent_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $viewer_id, $target_student_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $authorized = true;
    $stmt->close();
}

if (!$authorized) {
    die("Unauthorized Access to this student's records.");
}

// Fetch Student Info
$stu = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stu->bind_param("i", $target_student_id);
$stu->execute();
$student_name = $stu->get_result()->fetch_assoc()['username'] ?? 'Unknown';

// Fetch Attendance
$sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $target_student_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$present = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance View</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top:20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .present { color: #2ecc71; font-weight: bold; }
        .absent { color: #e74c3c; font-weight: bold; }
        .leave-approved { color: #2ecc71; font-weight: bold; }
        .pending-leave { color: #ffa117; font-weight: bold; }
        .summary-box { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 8px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center; }
        .summary-item .label { font-size: 13px; color: #666; display: block; margin-bottom: 5px; }
        .summary-item .value { font-size: 20px; font-weight: bold; color: #333; }
        .month-header { background: #f1f1f1; font-weight: bold; padding: 10px; margin-top: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
    <div class="container">
        <h2>Attendance Record: <?= htmlspecialchars($student_name) ?></h2>
        
        <?php 
            // Calculate Stats (Overall & Monthly)
            $rows = [];
            $total = 0;
            $present = 0;
            $months = [];

            while($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total++;
                if ($row['status'] === 'Present' || $row['status'] === 'Leave Approved') $present++;

                // Monthly Stats
                $monthKey = date('Y-m', strtotime($row['date']));
                if (!isset($months[$monthKey])) {
                    $months[$monthKey] = ['total' => 0, 'present' => 0];
                }
                $months[$monthKey]['total']++;
                if ($row['status'] === 'Present' || $row['status'] === 'Leave Approved') {
                    $months[$monthKey]['present']++;
                }
            }
            $percent = $total > 0 ? round(($present/$total)*100, 1) : 0;
        ?>

        <div class="summary-box">
            <div class="summary-item">
                <span class="label">Total Classes</span>
                <span class="value"><?= $total ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Total Present</span>
                <span class="value"><?= $present ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Overall Percentage</span>
                <span class="value" style="color: <?= $percent >= 75 ? '#2ecc71' : '#e74c3c' ?>"><?= $percent ?>%</span>
            </div>
        </div>

        <h3>Monthly Breakdown</h3>
        <?php foreach ($months as $ym => $stats): ?>
            <?php 
                $monthName = date('F Y', strtotime($ym . '-01'));
                $mPercent = $stats['total'] > 0 ? round(($stats['present']/$stats['total'])*100, 1) : 0;
            ?>
            <div style="margin-bottom: 15px; border: 1px solid #eee; padding: 10px; border-radius: 6px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong><?= $monthName ?></strong>
                    <span style="font-weight:bold; color: <?= $mPercent >= 75 ? '#2ecc71' : '#e74c3c' ?>"><?= $mPercent ?>%</span>
                </div>
                <div style="background:#eee; height:6px; border-radius:3px; margin-top:8px; overflow:hidden;">
                    <div style="background:<?= $mPercent >= 75 ? '#2ecc71' : '#e74c3c' ?>; width:<?= $mPercent ?>%; height:100%;"></div>
                </div>
            </div>
        <?php endforeach; ?>

        <h3>Detailed History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $row): ?>
                    <tr>
                        <td><?= date('d M Y (l)', strtotime($row['date'])) ?></td>
                        <td class="<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                            <?php if ($row['status'] == 'Present'): ?>
                                <i class="fa-solid fa-check-circle"></i> Present
                            <?php elseif ($row['status'] == 'Leave Approved'): ?>
                                <i class="fa-solid fa-envelope-open"></i> Leave Approved
                            <?php elseif ($row['status'] == 'Pending Leave'): ?>
                                <i class="fa-solid fa-envelope"></i> Pending Leave
                            <?php else: ?>
                                <i class="fa-solid fa-times-circle"></i> Absent
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <br>
        <button onclick="history.back()" class="btn" style="padding: 10px 20px; cursor: pointer;">Go Back</button>
    </div>
</body>
</html>

