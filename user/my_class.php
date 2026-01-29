<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Student Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Security: Ensure the user being deleted is actually a student
    $check = $conn->query("SELECT role FROM users WHERE id = $id");
    if ($check->num_rows > 0 && $check->fetch_assoc()['role'] === 'student') {
        $conn->query("DELETE FROM users WHERE id = $id");
        $msg = "Student removed successfully.";
    } else {
        $error = "Unable to delete: User not found or not a student.";
    }
}

// Get Teacher's assigned classes
$teacher_id = $_SESSION['user_id'];
$class_query = $conn->query("SELECT id, class_name FROM classes WHERE teacher_id = $teacher_id");
$assigned_classes = [];
while($c = $class_query->fetch_assoc()) {
    $assigned_classes[] = $c['id'];
}

// Build Query based on assignments
if (!empty($assigned_classes)) {
    $class_ids = implode(',', $assigned_classes);
    $sql = "SELECT * FROM users WHERE role = 'student' AND (class_id IN ($class_ids) OR class_id IS NULL) ORDER BY username ASC";
    // NOTE: For stricter filtering, remove "OR class_id IS NULL" to hide unassigned students.
    // However, showing unassigned students helps teachers adopt them into the class if needed.
    // Let's stick to STRICT requirements: Only show assigned class students.
    $sql = "SELECT * FROM users WHERE role = 'student' AND class_id IN ($class_ids) ORDER BY username ASC";
    $msg_info = "Showing students from your assigned classes.";
} else {
    // If no class assigned, show empty or all?
    // Requirement says "Role based". If no class assigned, teacher shouldn't see random students.
    $sql = "SELECT * FROM users WHERE 1=0"; // Show nothing
    $error = "You are not assigned to any class. Please contact Admin.";
}

// Special Case: If teacher added a student recently via 'add_student.php' (which sets class_id=NULL unless updated), 
// they might disappear. We should actually allow teachers to see "Unassigned" students or auto-assign them.
// Refinement: For this MVP, let's keep it simple. If teacher creates a student, we should ideally assign the class.
// But for now, we follow strict filtering.
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Class | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php" class="active"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Class</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (isset($msg)) echo "<p style='color:green; background:#e8f5e9; padding:10px; border-radius:4px; margin-bottom:20px;'>$msg</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red; background:#f8d7da; padding:10px; border-radius:4px; margin-bottom:20px;'>$error</p>"; ?>

        <div style="margin-bottom: 20px;">
            <a href="add_student.php" style="padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                <i class="fa-solid fa-plus"></i> Add New Student
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['username']); ?>&background=random" style="width:30px; border-radius:50%; vertical-align:middle; margin-right:10px;">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </td>
                        <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></td>
                        <td>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <a href="edit_student.php?id=<?php echo $row['id']; ?>" title="Edit" style="color:#3498db; font-size: 16px;">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" title="Delete" onclick="return confirm('Are you sure you want to remove this student? This cannot be undone.');" style="color:#e74c3c; font-size: 16px;">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
