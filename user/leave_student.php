<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch All Leave Requests
$sql = "SELECT lr.*, u.username as teacher_name 
        FROM leave_requests lr 
        LEFT JOIN users u ON lr.reviewed_by = u.id 
        WHERE lr.student_id = ? 
        ORDER BY lr.leave_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Leave Requests | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pending { background: #fff4e5; color: #ffa117; }
        .approved { background: #eafaf1; color: #2ecc71; }
        .rejected { background: #fdf2f2; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="attendance_student.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="leave_student.php" class="active"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="view_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Leave Application History</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Request Records</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Leave Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Submitted On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y (l)', strtotime($row['leave_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><span class="badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td><?php echo $row['teacher_name'] ? htmlspecialchars($row['teacher_name']) : '<span style="color:#aaa;">-</span>'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No leave requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
