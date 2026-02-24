<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require_once '../includes/relationship_helper.php';

$teacher_id = (int)$_SESSION['user_id'];
$view_student_id = isset($_GET['view_student']) ? (int)$_GET['view_student'] : 0;

$class_stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name ASC");
$class_stmt->bind_param("i", $teacher_id);
$class_stmt->execute();
$class_res = $class_stmt->get_result();
$assigned_classes = [];
while ($row = $class_res->fetch_assoc()) {
    $assigned_classes[] = $row;
}
$class_stmt->close();

$students = [];
$student_detail = null;
$error = '';

if (empty($assigned_classes)) {
    $error = "You are not assigned to any class. Please contact admin.";
} else {
    $class_ids = array_map(function ($r) { return (int)$r['id']; }, $assigned_classes);
    $in = implode(',', $class_ids);

    $optional = rel_build_student_select_fields($conn, 's');
    $selectCols = array_merge([
        "s.id",
        "s.username",
        "s.email",
        "c.class_name"
    ], $optional);

    $hasParentLink = rel_has_table($conn, 'parent_student');
    $guardianPhoneExpr = rel_has_column($conn, 'users', 'phone') ? "MIN(p.phone) AS guardian_phone" : "NULL AS guardian_phone";
    $guardianNameExpr = $hasParentLink ? "MIN(p.username) AS guardian_name" : "NULL AS guardian_name";
    $guardianJoin = $hasParentLink
        ? "LEFT JOIN parent_student ps ON ps.student_id = s.id
           LEFT JOIN users p ON p.id = ps.parent_id AND p.role = 'parent'"
        : "";
    $sql = "SELECT " . implode(", ", $selectCols) . ",
                   $guardianNameExpr,
                   $guardianPhoneExpr
            FROM users s
            JOIN classes c ON c.id = s.class_id
            $guardianJoin
            WHERE s.role = 'student' AND s.class_id IN ($in)
            GROUP BY s.id, s.username, s.email, c.class_name" . (in_array("s.profile_picture", $optional, true) ? ", s.profile_picture" : "") .
            (in_array("s.dob", $optional, true) ? ", s.dob" : "") .
            (in_array("s.gender", $optional, true) ? ", s.gender" : "") .
            (in_array("s.phone", $optional, true) ? ", s.phone" : "") .
            (in_array("s.address", $optional, true) ? ", s.address" : "") .
            (in_array("s.admission_number", $optional, true) ? ", s.admission_number" : "") .
            (in_array("s.academic_status", $optional, true) ? ", s.academic_status" : "") .
            " ORDER BY c.class_name ASC, s.username ASC";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
    }

    if ($view_student_id > 0) {
        foreach ($students as $s) {
            if ((int)$s['id'] === $view_student_id) {
                $student_detail = $s;
                break;
            }
        }
        if (!$student_detail) {
            $error = "Unauthorized student access attempt blocked.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Class | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .students-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:16px; }
        .student-card { text-decoration:none; color:inherit; }
        .student-meta { font-size:12px; color:#6b7280; margin-top:6px; }
        .class-pill { display:inline-block; margin-top:8px; padding:4px 10px; border-radius:999px; font-size:12px; background:#eaf3ff; color:#165fb6; }
        .detail-panel { background:#fff; border:1px solid #e5edf9; border-radius:12px; padding:18px; margin-bottom:18px; }
        .detail-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-top:14px; }
        .detail-item { background:#f8fbff; border:1px solid #e7eef9; border-radius:10px; padding:10px 12px; }
        .detail-item label { display:block; font-size:11px; color:#6b7280; text-transform:uppercase; font-weight:700; margin-bottom:4px; }
        .profile-lg { width:78px; height:78px; border-radius:50%; object-fit:cover; border:3px solid #eef3fb; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Class</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($student_detail): ?>
            <div class="detail-panel">
                <div style="display:flex; gap:14px; align-items:center;">
                    <img class="profile-lg" src="<?php echo htmlspecialchars(rel_avatar_src($student_detail['profile_picture'] ?? '', '..')); ?>" alt="Student">
                    <div>
                        <h3 style="margin:0;"><?php echo htmlspecialchars($student_detail['username']); ?></h3>
                        <span class="class-pill"><?php echo htmlspecialchars($student_detail['class_name']); ?></span>
                    </div>
                </div>
                <div class="detail-grid">
                    <div class="detail-item"><label>Admission Number</label><div><?php echo htmlspecialchars($student_detail['admission_number'] ?? ('ADM-' . $student_detail['id'])); ?></div></div>
                    <div class="detail-item"><label>Date of Birth</label><div><?php echo !empty($student_detail['dob']) ? htmlspecialchars($student_detail['dob']) : '-'; ?></div></div>
                    <div class="detail-item"><label>Gender</label><div><?php echo !empty($student_detail['gender']) ? htmlspecialchars($student_detail['gender']) : '-'; ?></div></div>
                    <div class="detail-item"><label>Parent/Guardian</label><div><?php echo !empty($student_detail['guardian_name']) ? htmlspecialchars($student_detail['guardian_name']) : '-'; ?></div></div>
                    <div class="detail-item"><label>Contact Number</label><div><?php echo !empty($student_detail['guardian_phone']) ? htmlspecialchars($student_detail['guardian_phone']) : (!empty($student_detail['phone']) ? htmlspecialchars($student_detail['phone']) : '-'); ?></div></div>
                    <div class="detail-item"><label>Address</label><div><?php echo !empty($student_detail['address']) ? nl2br(htmlspecialchars($student_detail['address'])) : '-'; ?></div></div>
                    <div class="detail-item"><label>Email</label><div><?php echo !empty($student_detail['email']) ? htmlspecialchars($student_detail['email']) : '-'; ?></div></div>
                    <div class="detail-item"><label>Academic Status</label><div><?php echo !empty($student_detail['academic_status']) ? htmlspecialchars($student_detail['academic_status']) : 'Active'; ?></div></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="students-grid">
            <?php foreach ($students as $student): ?>
                <a class="person-card student-card" href="?view_student=<?php echo (int)$student['id']; ?>">
                    <img class="person-avatar" src="<?php echo htmlspecialchars(rel_avatar_src($student['profile_picture'] ?? '', '..')); ?>" alt="Student">
                    <div class="person-name"><?php echo htmlspecialchars($student['username']); ?></div>
                    <div class="student-meta">Admission: <?php echo htmlspecialchars($student['admission_number'] ?? ('ADM-' . $student['id'])); ?></div>
                    <span class="class-pill"><?php echo htmlspecialchars($student['class_name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
