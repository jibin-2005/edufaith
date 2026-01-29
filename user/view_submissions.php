<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($assignment_id <= 0) {
    header("Location: manage_assignments.php");
    exit;
}

// Fetch Assignment Details
$stmt = $conn->prepare("SELECT title, description, due_date FROM assignments WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assign_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assign_details) {
    die("Assignment not found.");
}

// Fetch Submissions
$sql = "SELECT s.file_path, s.submitted_at, u.username, u.email 
        FROM submissions s 
        JOIN users u ON s.student_id = u.id 
        WHERE s.assignment_id = $assignment_id 
        ORDER BY s.submitted_at DESC";
$submissions = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Submissions | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-download { background: #3498db; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_assignments.php" class="active"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Submissions: <?php echo htmlspecialchars($assign_details['title']); ?></h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <a href="manage_assignments.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Assignments</a>

        <div class="table-container">
            <h3>Student Submissions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions->num_rows > 0): ?>
                        <?php while($row = $submissions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo date("M j, Y, h:i A", strtotime($row['submitted_at'])); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="btn-download" download target="_blank">
                                    <i class="fa-solid fa-download"></i> Download File
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#999;">No submissions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
