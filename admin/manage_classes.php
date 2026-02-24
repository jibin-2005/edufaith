<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require_once '../includes/relationship_helper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;

    if ($class_name === '') {
        $error = "Class name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO classes (class_name, teacher_id) VALUES (?, ?)");
        $stmt->bind_param("si", $class_name, $teacher_id);
        if ($stmt->execute()) {
            $msg = "Class created successfully.";
        } else {
            $error = "Error creating class: " . $conn->error;
        }
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt_unassign = $conn->prepare("UPDATE users SET class_id = NULL WHERE class_id = ?");
    $stmt_unassign->bind_param("i", $id);
    $stmt_unassign->execute();
    $stmt_unassign->close();

    $stmt_del = $conn->prepare("DELETE FROM classes WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    if ($stmt_del->execute()) {
        $msg = "Class deleted successfully.";
    } else {
        $error = "Error deleting class: " . $conn->error;
    }
    $stmt_del->close();
}

$teacherPhoneCol = rel_has_column($conn, 'users', 'phone') ? "t.phone AS teacher_phone," : "NULL AS teacher_phone,";
$teacherPicCol = rel_has_column($conn, 'users', 'profile_picture') ? "t.profile_picture AS teacher_profile_picture," : "NULL AS teacher_profile_picture,";
$overview_sql = "SELECT c.id, c.class_name,
                        t.id AS teacher_id, t.username AS teacher_name, t.email AS teacher_email,
                        $teacherPhoneCol
                        $teacherPicCol
                        COUNT(s.id) AS student_count
                 FROM classes c
                 LEFT JOIN users t ON t.id = c.teacher_id AND t.role = 'teacher'
                 LEFT JOIN users s ON s.class_id = c.id AND s.role = 'student'
                 GROUP BY c.id, c.class_name, t.id, t.username, t.email";
$overview_sql .= rel_has_column($conn, 'users', 'phone') ? ", t.phone" : "";
$overview_sql .= rel_has_column($conn, 'users', 'profile_picture') ? ", t.profile_picture" : "";
$overview_sql .= " ORDER BY c.class_name ASC";
$overview = $conn->query($overview_sql);

$teachers = $conn->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username ASC");

$view_class_id = isset($_GET['view_class']) ? (int)$_GET['view_class'] : 0;
$class_detail = null;
$class_students = [];
if ($view_class_id > 0) {
    $stmt = $conn->prepare("SELECT c.id, c.class_name,
                                   t.id AS teacher_id, t.username AS teacher_name, t.email AS teacher_email,
                                   " . (rel_has_column($conn, 'users', 'phone') ? "t.phone AS teacher_phone," : "NULL AS teacher_phone,") . "
                                   " . (rel_has_column($conn, 'users', 'profile_picture') ? "t.profile_picture AS teacher_profile_picture" : "NULL AS teacher_profile_picture") . "
                            FROM classes c
                            LEFT JOIN users t ON t.id = c.teacher_id AND t.role = 'teacher'
                            WHERE c.id = ? LIMIT 1");
    $stmt->bind_param("i", $view_class_id);
    $stmt->execute();
    $class_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($class_detail) {
        $studentSelect = [
            "s.id",
            "s.username",
            "s.email",
            "s.status"
        ];
        if (rel_has_column($conn, 'users', 'admission_number')) $studentSelect[] = "s.admission_number";
        if (rel_has_column($conn, 'users', 'profile_picture')) $studentSelect[] = "s.profile_picture";

        $sql = "SELECT " . implode(', ', $studentSelect) . "
                FROM users s
                WHERE s.role = 'student' AND s.class_id = ?
                ORDER BY s.username ASC";
        $st = $conn->prepare($sql);
        $st->bind_param("i", $view_class_id);
        $st->execute();
        $res = $st->get_result();
        while ($r = $res->fetch_assoc()) {
            $class_students[] = $r;
        }
        $st->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .overview-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-bottom:20px; }
        .class-card { text-decoration:none; color:inherit; }
        .class-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .count-badge { background:#ecf3ff; color:#1b62bf; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .detail-wrap { background:#fff; border:1px solid #e4edfa; border-radius:12px; padding:16px; margin-bottom:20px; }
        .students-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px,1fr)); gap:14px; }
        .teacher-box { display:flex; gap:12px; align-items:center; margin:10px 0 14px; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Classes</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($msg)): ?><div class="success-msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="error-msg"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

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
                <button type="submit" class="btn-primary">Create Class</button>
            </form>
        </div>

        <?php if ($class_detail): ?>
            <div class="detail-wrap">
                <div class="class-header">
                    <h3 style="margin:0;"><?php echo htmlspecialchars($class_detail['class_name']); ?></h3>
                    <a class="btn-secondary" href="manage_classes.php">Back</a>
                </div>
                <div class="teacher-box">
                    <img class="person-avatar" src="<?php echo htmlspecialchars(rel_avatar_src($class_detail['teacher_profile_picture'] ?? '', '..')); ?>" alt="Teacher">
                    <div>
                        <div class="person-name"><?php echo !empty($class_detail['teacher_name']) ? htmlspecialchars($class_detail['teacher_name']) : 'Teacher not assigned'; ?></div>
                        <div style="font-size:13px; color:#6b7280;"><?php echo !empty($class_detail['teacher_email']) ? htmlspecialchars($class_detail['teacher_email']) : '-'; ?></div>
                        <div style="font-size:13px; color:#6b7280;"><?php echo !empty($class_detail['teacher_phone']) ? htmlspecialchars($class_detail['teacher_phone']) : '-'; ?></div>
                    </div>
                </div>

                <h4 style="margin:12px 0;">Students (<?php echo count($class_students); ?>)</h4>
                <div class="students-grid">
                    <?php foreach ($class_students as $s): ?>
                        <div class="person-card">
                            <img class="person-avatar" src="<?php echo htmlspecialchars(rel_avatar_src($s['profile_picture'] ?? '', '..')); ?>" alt="Student">
                            <div class="person-name"><?php echo htmlspecialchars($s['username']); ?></div>
                            <div style="font-size:13px; color:#6b7280;"><?php echo htmlspecialchars($s['admission_number'] ?? ('ADM-' . $s['id'])); ?></div>
                            <div style="font-size:13px; color:#6b7280;"><?php echo !empty($s['email']) ? htmlspecialchars($s['email']) : '-'; ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($class_students)): ?><p style="color:#6b7280;">No students assigned.</p><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="overview-grid">
            <?php if ($overview && $overview->num_rows > 0): ?>
                <?php while($row = $overview->fetch_assoc()): ?>
                    <div class="person-card class-card" onclick="window.location.href='?view_class=<?php echo (int)$row['id']; ?>'" style="cursor:pointer;">
                        <div class="class-header">
                            <div class="person-name"><?php echo htmlspecialchars($row['class_name']); ?></div>
                            <span class="count-badge"><?php echo (int)$row['student_count']; ?> students</span>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <img class="person-avatar" src="<?php echo htmlspecialchars(rel_avatar_src($row['teacher_profile_picture'] ?? '', '..')); ?>" alt="Teacher">
                            <div>
                                <div style="font-weight:700;"><?php echo !empty($row['teacher_name']) ? htmlspecialchars($row['teacher_name']) : 'Unassigned'; ?></div>
                                <div style="font-size:12px; color:#6b7280;"><?php echo !empty($row['teacher_email']) ? htmlspecialchars($row['teacher_email']) : '-'; ?></div>
                            </div>
                        </div>
                        <div style="margin-top:10px;">
                            <a href="?delete=<?php echo (int)$row['id']; ?>" onclick="event.stopPropagation(); return confirm('Delete this class? Students will be unassigned.');" style="color:#e74c3c; font-size:13px;">Delete class</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No classes available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
