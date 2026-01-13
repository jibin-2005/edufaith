<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Add Class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $class_name = $_POST['class_name'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : NULL;

    $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id) VALUES (?, ?)");
    $stmt->bind_param("si", $class_name, $teacher_id);
    
    if ($stmt->execute()) {
        $msg = "Class created successfully.";
    } else {
        $error = "Error creating class: " . $conn->error;
    }
}

// Handle Delete Class
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM classes WHERE id = $id");
    $msg = "Class deleted successfully.";
}

// Fetch Classes with Teacher Names
$sql = "SELECT classes.id, classes.class_name, users.username as teacher_name 
        FROM classes 
        LEFT JOIN users ON classes.teacher_id = users.id 
        ORDER BY classes.class_name ASC";
$classes = $conn->query($sql);

// Fetch available Teachers for dropdown
$teachers = $conn->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_classes.php" class="active"><i class="fa-solid fa-chalkboard"></i> Classes</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Classes</h2>
            <div class="user-profile"><span>Admin</span></div>
        </div>

        <?php if (isset($msg)) echo "<p style='color:green; padding:10px; background:#e8f5e9; margin-bottom:15px;'>$msg</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red; padding:10px; background:#f8d7da; margin-bottom:15px;'>$error</p>"; ?>

        <!-- Add Class Form -->
        <div class="form-container">
            <h3>Add New Class</h3>
            <form method="POST">
                <input type="hidden" name="add_class" value="1">
                <div class="form-group">
                    <label>Class Name</label>
                    <input type="text" name="class_name" placeholder="e.g. Grade 10 - A" required>
                </div>
                <div class="form-group">
                    <label>Assign Teacher (Optional)</label>
                    <select name="teacher_id">
                        <option value="">-- Select Teacher --</option>
                        <?php while($t = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['username']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Create Class</button>
            </form>
        </div>

        <!-- Class List -->
        <div class="panel">
            <h3>Existing Classes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Assigned Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($classes->num_rows > 0): ?>
                        <?php while($row = $classes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td>
                                <?php if ($row['teacher_name']): ?>
                                    <span style="color:green; font-weight:500;"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($row['teacher_name']); ?></span>
                                <?php else: ?>
                                    <span style="color:orange;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this class? Students will be unassigned.');" style="color:#e74c3c;">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No classes found. Create one above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
