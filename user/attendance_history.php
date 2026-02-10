<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$teacher_id = $_SESSION['user_id'];
$date_default = (date('w') == 0) ? date("Y-m-d") : date("Y-m-d", strtotime("last sunday"));
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : $date_default;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = $date_default;
}
$class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

$class_name_display = '';

// Fetch teacher's classes for filter dropdown
$classes_result = $conn->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name ASC");
$classes_result->bind_param("i", $teacher_id);
$classes_result->execute();
$classes_list = $classes_result->get_result();

// Attendance table uses: student_id, date, teacher_id, class_id, status
$sql = "SELECT u.username, a.status, c.class_name 
        FROM attendance a 
        JOIN users u ON a.student_id = u.id 
        LEFT JOIN classes c ON a.class_id = c.id 
        WHERE a.teacher_id = ? AND a.date = ?";
$params = [$teacher_id, $date_filter];
$types = "is";
if ($class_filter > 0) {
    $sql .= " AND a.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}
$sql .= " ORDER BY c.class_name ASC, u.username ASC";

$stmt = $conn->prepare($sql);
$result = false;
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
    }
}

if ($class_filter > 0 && $classes_list->num_rows > 0) {
    $cn = $conn->prepare("SELECT class_name FROM classes WHERE id = ? AND teacher_id = ?");
    $cn->bind_param("ii", $class_filter, $teacher_id);
    $cn->execute();
    $cnr = $cn->get_result()->fetch_assoc();
    $class_name_display = $cnr ? $cnr['class_name'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance History | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display:flex; align-items:center; gap:10px; }
        .table-container { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .present { background: #d4edda; color: #155724; }
        .absent { background: #f8d7da; color: #721c24; }
        .leave-approved { background: #eafaf1; color: #2ecc71; }
        .pending-leave { background: #fff4e5; color: #ffa117; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-plus"></i> Mark Attendance</a></li>
            <li><a href="attendance_history.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Attendance History</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Attendance History</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="filter-bar">
            <form method="GET" style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                <label>Date:</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                <label>Class:</label>
                <select name="class_id" style="padding:8px; border:1px solid #ddd; border-radius:4px; min-width:160px;">
                    <option value="">All my classes</option>
                    <?php if ($classes_list): $classes_list->data_seek(0); while($c = $classes_list->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($class_filter == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['class_name']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
                <button type="submit" style="padding:8px 16px; background:var(--primary); color:white; border:none; border-radius:4px; cursor:pointer;">View History</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Attendance for <?php echo date("F j, Y (l)", strtotime($date_filter)); ?><?php if ($class_name_display): ?> — <?php echo htmlspecialchars($class_name_display); ?><?php endif; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <?php if (!$class_filter): ?><th>Class</th><?php endif; ?>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <?php if (!$class_filter): ?><td><?php echo htmlspecialchars($row['class_name'] ?? '—'); ?></td><?php endif; ?>
                            <td>
                                <?php
                                    $status = $row['status'];
                                    if ($status === 'Present') {
                                        echo "<span class='badge present'>Present</span>";
                                    } elseif ($status === 'Leave Approved') {
                                        echo "<span class='badge leave-approved'>Leave Approved</span>";
                                    } elseif ($status === 'Pending Leave') {
                                        echo "<span class='badge pending-leave'>Pending Leave</span>";
                                    } else {
                                        echo "<span class='badge absent'>Absent</span>";
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="<?php echo $class_filter ? 2 : 3; ?>" style="text-align:center;">No attendance records for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:15px;"><a href="attendance_teacher.php" style="color:var(--primary); text-decoration:none;"><i class="fa-solid fa-calendar-check"></i> Mark Attendance</a></p>
    </div>
</body>
</html>
