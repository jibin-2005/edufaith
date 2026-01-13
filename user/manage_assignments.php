<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Add Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $due_date = $conn->real_escape_string($_POST['due_date']);
    $target_grade = $conn->real_escape_string($_POST['target_grade']);
    $assigned_by = $_SESSION['user_id'];

    $sql = "INSERT INTO assignments (title, description, due_date, target_grade, assigned_by) VALUES ('$title', '$description', '$due_date', '$target_grade', $assigned_by)";
    if ($conn->query($sql)) {
         header("Location: manage_assignments.php?msg=success");
         exit;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM assignments WHERE id = $id");
    header("Location: manage_assignments.php");
    exit;
}

$result = $conn->query("SELECT * FROM assignments ORDER BY due_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lesson Plans & Assignments | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .table-container { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-delete { color: #e74c3c; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_history.php"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Lesson Plans & Assignments</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="form-container">
            <h3>Add New Assignment</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required placeholder="e.g., Read Genesis 1">
                </div>
                <div class="form-group">
                    <label>Target Grade</label>
                    <select name="target_grade">
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="All">All Grades</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required>
                </div>
                <div class="form-group">
                    <label>Details</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" name="add_assignment" class="btn-primary">Post Assignment</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Posted Assignments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Grade</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['target_grade']); ?></td>
                        <td><?php echo date("M j, Y", strtotime($row['due_date'])); ?></td>
                        <td>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
