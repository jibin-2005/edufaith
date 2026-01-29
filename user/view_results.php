<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch Results for both exams
$exam1_marks = null;
$exam1_date = null;
$exam1_by = null;
$exam2_marks = null;
$exam2_date = null;
$exam2_by = null;

$sql = "SELECT r.*, u.username as teacher_name 
        FROM results r 
        LEFT JOIN users u ON r.updated_by = u.id 
        WHERE r.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()) {
    if ($row['exam_type'] === 'exam_1') {
        $exam1_marks = $row['marks'];
        $exam1_date = date("d M Y", strtotime($row['updated_at']));
        $exam1_by = $row['teacher_name'];
    } elseif ($row['exam_type'] === 'exam_2') {
        $exam2_marks = $row['marks'];
        $exam2_date = date("d M Y", strtotime($row['updated_at']));
        $exam2_by = $row['teacher_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Exam Results | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 20px; }
        .result-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .result-card:hover { transform: translateY(-5px); }
        .result-header { padding: 20px; color: white; text-align: center; }
        .exam1-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .exam2-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .result-body { padding: 30px; text-align: center; }
        .marks-v { font-size: 64px; font-weight: 800; margin: 10px 0; display: block; }
        .marks-total { font-size: 20px; opacity: 0.7; }
        .meta-info { border-top: 1px solid #eee; padding: 15px 20px; background: #fafafa; font-size: 13px; color: #666; display: flex; justify-content: space-between; }
        .not-set { padding: 60px 20px; color: #aaa; text-align: center; font-style: italic; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="attendance_student.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="leave_student.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="view_results.php" class="active"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Exam Results</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <p style="color: #666; margin-bottom: 30px;">View your performance in the main academic examinations.</p>

        <div class="results-grid">
            <!-- Exam 1 Card -->
            <div class="result-card">
                <div class="result-header exam1-header">
                    <h3 style="margin:0;"><i class="fa-solid fa-graduation-cap"></i> Main Exam 1</h3>
                </div>
                <div class="result-body">
                    <?php if ($exam1_marks !== null): ?>
                        <span class="marks-v" style="color: #667eea;"><?= $exam1_marks ?><span class="marks-total">/100</span></span>
                        <div style="margin-top: 15px;">
                            <span class="badge" style="background:#eafaf1; color:#2ecc71; padding:5px 15px; border-radius:20px; font-weight:600;">PASSED</span>
                        </div>
                    <?php else: ?>
                        <div class="not-set">
                            <i class="fa-solid fa-clock-rotate-left" style="font-size:32px; margin-bottom:10px;"></i>
                            <p>Marks not published yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($exam1_marks !== null): ?>
                <div class="meta-info">
                    <span><i class="fa-solid fa-calendar-day"></i> <?= $exam1_date ?></span>
                    <span><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($exam1_by ?? 'Teacher') ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Exam 2 Card -->
            <div class="result-card">
                <div class="result-header exam2-header">
                    <h3 style="margin:0;"><i class="fa-solid fa-graduation-cap"></i> Main Exam 2</h3>
                </div>
                <div class="result-body">
                    <?php if ($exam2_marks !== null): ?>
                        <span class="marks-v" style="color: #f5576c;"><?= $exam2_marks ?><span class="marks-total">/100</span></span>
                        <div style="margin-top: 15px;">
                            <span class="badge" style="background:#eafaf1; color:#2ecc71; padding:5px 15px; border-radius:20px; font-weight:600;">PASSED</span>
                        </div>
                    <?php else: ?>
                        <div class="not-set">
                            <i class="fa-solid fa-clock-rotate-left" style="font-size:32px; margin-bottom:10px;"></i>
                            <p>Marks not published yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($exam2_marks !== null): ?>
                <div class="meta-info">
                    <span><i class="fa-solid fa-calendar-day"></i> <?= $exam2_date ?></span>
                    <span><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($exam2_by ?? 'Teacher') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel" style="margin-top: 40px; padding: 30px; background: #fff8f8; border: 1px dashed #f5576c; border-radius: 12px; text-align: center;">
             <h4 style="color: #f5576c; margin-bottom: 10px;"><i class="fa-solid fa-circle-info"></i> Note to Students</h4>
             <p style="color: #666; font-size: 14px;">If you have any queries regarding your marks, please contact your class teacher directly.</p>
        </div>
    </div>
</body>
</html>
