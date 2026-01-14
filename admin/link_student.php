<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id = $_POST['parent_id'];
    $student_ids = $_POST['student_ids'] ?? [];

    if ($parent_id && !empty($student_ids)) {
        // Clear existing links if you want to overwrite, or just add new ones? 
        // Usually safer to just add. But let's assume we are adding.
        // Actually, preventing duplicates is good. 
        // For this simple implementation, we will Loop and Insert Ignore.
        
        $stmt = $conn->prepare("INSERT IGNORE INTO parent_student (parent_id, student_id) VALUES (?, ?)");
        $successCount = 0;
        foreach ($student_ids as $sid) {
            $stmt->bind_param("ii", $parent_id, $sid);
            if ($stmt->execute()) $successCount++;
        }
        $msg = "Successfully linked $successCount student(s).";
    }
}

// Fetch Parents
$parents = $conn->query("SELECT id, username, email FROM users WHERE role = 'parent' AND status='active'");

// Fetch Students
$students = $conn->query("SELECT id, username, email FROM users WHERE role = 'student' AND status='active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Link Parent to Student</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        select { width: 100%; padding: 10px; margin-bottom: 20px; }
        .student-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
        .student-item { padding: 5px; border-bottom: 1px solid #eee; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Link Parent to Students</h2>
        <?php if(isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>
        
        <form method="POST">
            <label>Select Parent:</label>
            <select name="parent_id" required>
                <option value="">-- Choose Parent --</option>
                <?php while($row = $parents->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?> (<?= $row['email'] ?>)</option>
                <?php endwhile; ?>
            </select>

            <label>Select Students (Hold Ctrl to select multiple):</label>
            <div class="student-list">
                <?php while($row = $students->fetch_assoc()): ?>
                    <div class="student-item">
                        <input type="checkbox" name="student_ids[]" value="<?= $row['id'] ?>" id="s_<?= $row['id'] ?>">
                        <label for="s_<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?> (<?= $row['email'] ?>)</label>
                    </div>
                <?php endwhile; ?>
            </div>
            <br>
            <button type="submit" class="btn">Link Selected Students</button>
            <a href="dashboard_admin.php" style="margin-left: 20px;">Back</a>
        </form>
    </div>
</body>
</html>
