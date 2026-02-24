<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$teacher_id = $_SESSION['user_id'];

// Fetch leave requests for students in the teacher's class
// Note: We join with classes to ensure the teacher only sees their class
$sql = "SELECT lr.*, u.username as student_name, c.class_name 
        FROM leave_requests lr 
        JOIN users u ON lr.student_id = u.id 
        JOIN classes c ON lr.class_id = c.id 
        WHERE c.teacher_id = ? 
        ORDER BY lr.leave_date DESC, lr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Leave Requests | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        RealTimeSync.checkAndTriggerFromURL();
    </script>
    <style>
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pending { background: #fff4e5; color: #ffa117; }
        .approved { background: #eafaf1; color: #2ecc71; }
        .rejected { background: #fdf2f2; color: #e74c3c; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-approve { background: #2ecc71; color: white; margin-right: 5px; }
        .btn-reject { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Leave Request Management</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                Action completed successfully!
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h3>Class Leave Applications</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Leave Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo date('M j, Y (l)', strtotime($row['leave_date'])); ?></td>
                                <td style="max-width: 300px;"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><span class="badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <div style="display: flex;">
                                            <form action="../includes/approve_leave_process.php" method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-action btn-approve">Approve</button>
                                            </form>
                                            <form action="../includes/approve_leave_process.php" method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-action btn-reject">Reject</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-size:12px;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No leave requests found for your class.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

