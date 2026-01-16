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
    $class_id = intval($_POST['class_id']);
    $assigned_by = $_SESSION['user_id'];

    $sql = "INSERT INTO assignments (title, description, due_date, class_id, assigned_by, created_by) VALUES ('$title', '$description', '$due_date', '$class_id', $assigned_by, $assigned_by)";
    if ($conn->query($sql)) {
         header("Location: manage_assignments.php?msg=success");
         exit;
    } else {
         $error_msg = $conn->error;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM assignments WHERE id = $id");
    header("Location: manage_assignments.php");
    exit;
}

// Fetch Assignments with Class Name
$sql = "SELECT a.*, c.class_name FROM assignments a LEFT JOIN classes c ON a.class_id = c.id ORDER BY a.due_date DESC";
$result = $conn->query($sql);

// Fetch Classes for Dropdown
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
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
            <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="manage_assignments.php" class="active"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Lesson Plans & Assignments</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <i class="fa-solid fa-circle-check"></i> Assignment posted successfully!
            </div>
        <?php endif; ?>
        <?php if (isset($error_msg)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <i class="fa-solid fa-circle-exclamation"></i> Error: <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Assignment</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required placeholder="e.g., Read Genesis 1">
                </div>
                <div class="form-group">
                    <label>Assign to Class</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                        <?php endwhile; ?>
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
                        <th>Class</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td>
                            <?php if($row['class_name']): ?>
                                <span class="badge" style="background:#e8f4fc; color:#2c3e50; padding:4px 8px; border-radius:4px; font-size:12px;">
                                    <?php echo htmlspecialchars($row['class_name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#aaa;">All / Unassigned</span>
                            <?php endif; ?>
                        </td>
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
