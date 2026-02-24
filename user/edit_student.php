<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$message = "";
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header("Location: my_class.php");
    exit;
}

// Security: Verify the user is a student before allowing any edits
$check = $conn->query("SELECT role FROM users WHERE id = $user_id");
if ($check->num_rows === 0 || $check->fetch_assoc()['role'] !== 'student') {
    die("Unauthorized: You can only edit student accounts.");
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['fullname'];
    $email = $_POST['email'];
    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : NULL;
    
    // Teachers can update name, email, and class
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, class_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $username, $email, $class_id, $user_id);

    if ($stmt->execute()) {
        $message = "Student details updated successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch current data
$stmt = $conn->prepare("SELECT username, email, class_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student | Teacher Portal</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-submit { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Edit Student: <?php echo htmlspecialchars($user['username']); ?></h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <a href="my_class.php" style="color:#555; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Class</a>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert <?php echo (strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Class Assignment</label>
                    <select name="class_id">
                        <option value="">-- No Class --</option>
                        <?php
                        $classes = $conn->query("SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name ASC");
                        while($c = $classes->fetch_assoc()):
                            $selected = (isset($user['class_id']) && $user['class_id'] == $c['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="background:#fff3cd; color:#856404; padding:10px; border-radius:4px; margin-bottom:15px; font-size:13px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Note: You are only editing the Database record. Password changes are not supported here.
                </div>

                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>

