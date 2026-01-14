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
$target_student_id = $_GET['student_id'] ?? $viewer_id;

// Authorization Logic
$authorized = false;
if ($viewer_role === 'admin' || $viewer_role === 'teacher') {
    $authorized = true;
} elseif ($viewer_role === 'student') {
    if ($target_student_id == $viewer_id) $authorized = true;
} elseif ($viewer_role === 'parent') {
    // Check linkage
    $stmt = $conn->prepare("SELECT 1 FROM parent_student WHERE parent_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $viewer_id, $target_student_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $authorized = true;
}

if (!$authorized) {
    die("Unauthorized Access");
}

// Fetch Student Info
$stu = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stu->bind_param("i", $target_student_id);
$stu->execute();
$student_name = $stu->get_result()->fetch_assoc()['username'] ?? 'Unknown';

// Fetch Results
$sql = "SELECT r.marks, r.updated_at, u.username as teacher_name 
        FROM results r 
        LEFT JOIN users u ON r.updated_by = u.id
        WHERE r.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $target_student_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Results</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 600px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .result-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 12px; text-align: center; margin-bottom: 20px; }
        .marks { font-size: 72px; font-weight: bold; margin: 20px 0; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 10px; }
        .back-btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-align:center; margin-bottom:30px;">
            <i class="fa-solid fa-chart-line"></i> Results: <?= htmlspecialchars($student_name) ?>
        </h2>

        <?php if ($data): ?>
            <div class="result-card">
                <div style="font-size:18px; opacity:0.9;">Total Marks</div>
                <div class="marks"><?= $data['marks'] ?></div>
                <div style="font-size:16px; opacity:0.8;">out of 100</div>
            </div>

            <div class="info-box">
                <strong>Last Updated:</strong> <?= date('d M Y, h:i A', strtotime($data['updated_at'])) ?>
            </div>

            <div class="info-box">
                <strong>Updated By:</strong> <?= htmlspecialchars($data['teacher_name'] ?? 'Teacher') ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:60px 20px; color:#999;">
                <i class="fa-solid fa-clipboard-question" style="font-size:64px; margin-bottom:20px;"></i>
                <p style="font-size:18px;">No results available yet</p>
                <p>Results will be displayed once your teacher updates them.</p>
            </div>
        <?php endif; ?>

        <div style="text-align:center;">
            <button onclick="history.back()" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </button>
        </div>
    </div>
</body>
</html>
