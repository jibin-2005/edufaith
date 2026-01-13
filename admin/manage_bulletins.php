<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Announcement Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $target_role = $conn->real_escape_string($_POST['target_role']);
    $created_by = $_SESSION['user_id'];

    $sql = "INSERT INTO announcements (title, content, target_role, created_by) VALUES ('$title', '$content', '$target_role', $created_by)";
    if ($conn->query($sql)) {
        $msg = "Announcement posted successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    header("Location: manage_bulletins.php");
    exit;
}

$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bulletins | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-delete { color: red; cursor: pointer; border: none; background: none; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Bulletins & Announcements</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (isset($msg)) echo "<p style='color:green; background:#e8f5e9; padding:10px; border-radius:4px;'>$msg</p>"; ?>

        <div class="form-container">
            <h3>Post New Announcement</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Target Group</label>
                    <select name="target_role">
                        <option value="all">Everyone</option>
                        <option value="student">Students Only</option>
                        <option value="parent">Parents Only</option>
                        <option value="teacher">Teachers Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" rows="4" required></textarea>
                </div>
                <button type="submit" name="add_announcement" class="btn-primary">Post Bulletin</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Recent Bulletins</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Target</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo ucfirst($row['target_role']); ?></td>
                        <td><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this announcement?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
