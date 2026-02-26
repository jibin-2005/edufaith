<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../includes/validation_helper.php';
    
    $parent_id = $_POST['parent_id'];
    $student_ids = $_POST['student_ids'] ?? [];

    if ($parent_id && !empty($student_ids)) {
        // Check maximum children limit (10)
        $current_count = Validator::countParentChildren($conn, $parent_id);
        $new_total = $current_count + count($student_ids);
        
        if ($new_total > 10) {
            $error_msg = "Cannot link more than 10 children to a parent. Current: $current_count, Attempting to add: " . count($student_ids);
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO parent_student (parent_id, student_id) VALUES (?, ?)");
            $successCount = 0;
            $duplicateCount = 0;
            $inactiveCount = 0;
            
            foreach ($student_ids as $sid) {
                // Check if student is active
                $check_active = $conn->prepare("SELECT status FROM users WHERE id = ? AND role = 'student'");
                $check_active->bind_param("i", $sid);
                $check_active->execute();
                $student_result = $check_active->get_result();
                
                if ($student_result->num_rows === 0 || $student_result->fetch_assoc()['status'] !== 'active') {
                    $inactiveCount++;
                    $check_active->close();
                    continue;
                }
                $check_active->close();
                
                // Check if link already exists
                if (Validator::parentStudentLinkExists($conn, $parent_id, $sid)) {
                    $duplicateCount++;
                    continue;
                }
                
                $stmt->bind_param("ii", $parent_id, $sid);
                if ($stmt->execute()) $successCount++;
            }
            
            $msg = "Successfully linked $successCount student(s).";
            if ($duplicateCount > 0) $msg .= " $duplicateCount already linked.";
            if ($inactiveCount > 0) $msg .= " $inactiveCount inactive students skipped.";
        }
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
        <?php if(isset($error_msg)) echo "<p style='color:red'>$error_msg</p>"; ?>
        
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
