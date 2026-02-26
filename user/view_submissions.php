<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$assignment_id = intval($_GET['assignment_id'] ?? 0);
$teacher_id = $_SESSION['user_id'];

// Verify assignment belongs to this teacher
$check = $conn->prepare("SELECT a.*, c.class_name FROM assignments a LEFT JOIN classes c ON a.class_id = c.id WHERE a.id = ? AND a.assigned_by = ?");
$check->bind_param("ii", $assignment_id, $teacher_id);
$check->execute();
$assignment = $check->get_result()->fetch_assoc();
$check->close();

if (!$assignment) {
    header("Location: manage_assignments.php");
    exit;
}

// Fetch all submissions for this assignment
$stmt = $conn->prepare("
    SELECT asub.*, u.username, u.email, 
           IF(asub.submitted_at <= a.due_date, 'On Time', 'Late') as submission_status
    FROM assignment_submissions asub
    JOIN users u ON asub.student_id = u.id
    JOIN assignments a ON asub.assignment_id = a.id
    WHERE asub.assignment_id = ?
    ORDER BY u.username ASC
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$submissions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Submissions | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .assignment-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .table-container { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .download-btn { color: #0066cc; text-decoration: none; cursor: pointer; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-ontime { background: #d1e7dd; color: #0f5132; }
        .badge-late { background: #f8d7da; color: #721c24; }
        .badge-pending { background: #fff3cd; color: #664d03; }
        .grade-input { width: 60px; padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Assignment Submissions</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <a href="manage_assignments.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>

        <div class="assignment-header">
            <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
            <p style="color: #666; margin: 5px 0;">
                <strong>Class:</strong> <?php echo htmlspecialchars($assignment['class_name'] ?? 'All'); ?>
                | <strong>Due:</strong> <?php echo date("M j, Y", strtotime($assignment['due_date'])); ?>
            </p>
            <?php if (!empty($assignment['description'])): ?>
                <p style="margin: 10px 0 0; color: #555; font-size: 14px;">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions->num_rows > 0): ?>
                        <?php while ($sub = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['username']); ?></td>
                                <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                <td>
                                    <?php if ($sub['status'] === 'submitted'): ?>
                                        <span class="badge badge-ontime">
                                            <i class="fa-solid fa-check"></i> Submitted
                                        </span>
                                        <?php if ($sub['submission_status'] === 'Late'): ?>
                                            <span class="badge badge-late" style="margin-left: 5px;">LATE</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['submitted_at']): ?>
                                        <?php echo date("M j, Y g:i A", strtotime($sub['submitted_at'])); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['submission_file']): ?>
                                        <a href="../uploads/assignments/submissions/<?php echo htmlspecialchars($sub['submission_file']); ?>" class="download-btn" download>
                                            <i class="fa-solid fa-file-pdf"></i> Download PDF
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999; padding: 30px;">
                                <p>No submissions yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
