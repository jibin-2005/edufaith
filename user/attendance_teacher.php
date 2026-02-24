<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$teacher_id = $_SESSION['user_id'];

// Default to last Sunday if today isn't Sunday
$today = date('Y-m-d');
$is_sunday = (date('w', strtotime($today)) == 0);
$default_date = $is_sunday ? $today : date('Y-m-d', strtotime('last Sunday'));

$date_filter = $_GET['date'] ?? $default_date;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = $default_date;
}
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Fetch assigned classes for this teacher
$stmt_classes = $conn->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name ASC");
$stmt_classes->bind_param("i", $teacher_id);
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();

// Fetch students if class is selected
$students = [];
if ($class_id) {
    // Verify class belongs to this teacher
    $check_class = $conn->prepare("SELECT 1 FROM classes WHERE id = ? AND teacher_id = ?");
    $check_class->bind_param("ii", $class_id, $teacher_id);
    $check_class->execute();
    if ($check_class->get_result()->num_rows === 0) {
        $class_id = 0;
    }
    $check_class->close();
}

if ($class_id) {
    // Fetch students and their current attendance for that date
    // Also fetch leave requirements
    $stmt_students = $conn->prepare("SELECT u.id, u.username, a.status, lr.status as leave_status 
                                     FROM users u 
                                     LEFT JOIN attendance a ON u.id = a.student_id AND a.date = ? AND a.class_id = ?
                                     LEFT JOIN leave_requests lr ON u.id = lr.student_id AND lr.leave_date = ?
                                     WHERE u.role = 'student' AND u.class_id = ?
                                     ORDER BY u.username ASC");
    $stmt_students->bind_param("sisi", $date_filter, $class_id, $date_filter, $class_id);
    $stmt_students->execute();
    $res_students = $stmt_students->get_result();
    if ($res_students) {
        while ($row = $res_students->fetch_assoc()) {
            $students[] = $row;
        }
    }
    $stmt_students->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Attendance | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: #555; }
        .filter-group select, .filter-group input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 150px; }
        .btn-filter { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; height: 41px; }
        
        .radio-tile-group { display: flex; gap: 10px; }
        .radio-tile { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
        .radio-tile i { font-size: 14px; margin-bottom: 2px; }
        .radio-tile span { font-size: 11px; font-weight: 600; }
        .radio-tile-group input { display: none; }
        
        input[value="Present"]:checked + .radio-tile { border-color: #2ecc71; color: #2ecc71; background: #eafaf1; }
        input[value="Absent"]:checked + .radio-tile { border-color: #e74c3c; color: #e74c3c; background: #fdf2f2; }
        
        .sunday-warning { background: #fff4e5; color: #663c00; padding: 10px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ffa117; }
        .leave-badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 8px; font-weight: 700; text-transform: uppercase; }
        .leave-pending { background: #fff4e5; color: #ffa117; }
        .leave-approved { background: #eafaf1; color: #2ecc71; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Attendance Management</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (!$is_sunday && $date_filter == $today): ?>
            <div class="sunday-warning">
                <i class="fa-solid fa-triangle-exclamation"></i> 
                <strong>Note:</strong> Today is not Sunday. Attendance marking is typically restricted to Sundays. 
                You can select a past Sunday to edit records.
            </div>
        <?php endif; ?>

        <div class="filter-bar">
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="filter-group">
                    <label>Select Class</label>
                    <select name="class_id" required>
                        <option value="">-- Choose Class --</option>
                        <?php while($c = $result_classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($class_id == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Select Date</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>" required>
                </div>
                <button type="submit" class="btn-filter">Load Students</button>
            </form>
        </div>

        <?php if ($class_id): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3>Marking Attendance for: <?php echo date('d M Y (l)', strtotime($date_filter)); ?></h3>
                </div>
                
                <form action="../includes/save_attendance.php" method="POST">
                    <input type="hidden" name="date" value="<?php echo $date_filter; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($s['username']); ?>
                                            <?php if ($s['leave_status'] === 'pending'): ?>
                                                <span class="leave-badge leave-pending">Pending Leave</span>
                                            <?php elseif ($s['leave_status'] === 'approved'): ?>
                                                <span class="leave-badge leave-approved">Leave Approved</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="radio-tile-group">
                                                <label>
                                                    <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="Present" 
                                                        <?php echo ($s['status'] == 'Present' || (!$s['status'] && !$s['leave_status'])) ? 'checked' : ''; ?>>
                                                    <div class="radio-tile">
                                                        <i class="fa-solid fa-check"></i>
                                                        <span>Present</span>
                                                    </div>
                                                </label>
                                                <label>
                                                    <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="Absent" 
                                                        <?php echo ($s['status'] == 'Absent') ? 'checked' : ''; ?>>
                                                    <div class="radio-tile">
                                                        <i class="fa-solid fa-xmark"></i>
                                                        <span>Absent</span>
                                                    </div>
                                                </label>
                                                <?php if ($s['leave_status'] === 'approved'): ?>
                                                    <label>
                                                        <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="Leave Approved" 
                                                            <?php echo ($s['status'] == 'Leave Approved' || (!$s['status'] && $s['leave_status'] == 'approved')) ? 'checked' : ''; ?>>
                                                        <div class="radio-tile" style="border-color: #2ecc71; color: #2ecc71;">
                                                            <i class="fa-solid fa-envelope-open"></i>
                                                            <span>L. Approved</span>
                                                        </div>
                                                    </label>
                                                <?php elseif ($s['leave_status'] === 'pending'): ?>
                                                    <label>
                                                        <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="Pending Leave" 
                                                            <?php echo ($s['status'] == 'Pending Leave' || (!$s['status'] && $s['leave_status'] == 'pending')) ? 'checked' : ''; ?>>
                                                        <div class="radio-tile" style="border-color: #ffa117; color: #ffa117;">
                                                            <i class="fa-solid fa-envelope"></i>
                                                            <span>L. Pending</span>
                                                        </div>
                                                    </label>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center;">No students assigned to this class.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (count($students) > 0): ?>
                        <div style="margin-top: 25px; text-align: right;">
                            <button type="submit" class="btn-primary" style="padding: 12px 30px; font-weight: 600;">
                                <i class="fa-solid fa-floppy-disk"></i> Save Attendance
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #777;">
                <i class="fa-solid fa-users-rectangle" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p>Please select a class and date to begin marking attendance.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

