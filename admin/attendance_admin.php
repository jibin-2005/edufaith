<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$class_filter = $_GET['class_id'] ?? '';
$teacher_filter = $_GET['teacher_id'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Analytics Queries
$total_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE status = 'Present'")->fetch_assoc()['count'];
$total_absent  = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE status = 'Absent'")->fetch_assoc()['count'];
$total_approved_leave = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE status = 'Leave Approved'")->fetch_assoc()['count'];
$total_pending_leave  = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE status = 'Pending Leave'")->fetch_assoc()['count'];

// Fetch Filters Data
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
$teachers = $conn->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username ASC");

// Main Attendance List
$sql = "SELECT a.*, u.username as student_name, c.class_name, t.username as teacher_name 
        FROM attendance a
        JOIN users u ON a.student_id = u.id
        JOIN classes c ON a.class_id = c.id
        JOIN users t ON a.teacher_id = t.id
        WHERE 1=1";

$params = [];
$types = '';

if ($class_filter !== '') {
    $sql .= " AND a.class_id = ?";
    $params[] = (int)$class_filter;
    $types .= 'i';
}
if ($teacher_filter !== '') {
    $sql .= " AND a.teacher_id = ?";
    $params[] = (int)$teacher_filter;
    $types .= 'i';
}
if ($date_filter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $sql .= " AND a.date = ?";
    $params[] = $date_filter;
    $types .= 's';
} else {
    $date_filter = '';
}

$sql .= " ORDER BY a.date DESC, c.class_name ASC LIMIT 100";
$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Attendance | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-panel { background: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .bg-present { background: #eafaf1; color: #2ecc71; }
        .bg-absent { background: #fdf2f2; color: #e74c3c; }
        .bg-leave-approved { background: #eafaf1; color: #2ecc71; }
        .bg-pending-leave { background: #fff4e5; color: #ffa117; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i> Classes</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="attendance_admin.php" class="active"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
        </ul>
        <div class="logout"><a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Attendance Analytics</h2>
            <div class="user-profile"><span>Administrator</span></div>
        </div>

        <!-- Analytics Cards -->
        <div class="stat-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="card">
                <div class="card-info"><h3><?php echo $total_present + $total_absent + $total_approved_leave + $total_pending_leave; ?></h3><p>Total Records</p></div>
                <div class="card-icon bg-blue"><i class="fa-solid fa-database"></i></div>
            </div>
            <div class="card">
                <div class="card-info"><h3 style="color:#2ecc71;"><?php echo $total_present; ?></h3><p>Present</p></div>
                <div class="card-icon bg-green"><i class="fa-solid fa-user-check"></i></div>
            </div>
            <div class="card">
                <div class="card-info"><h3 style="color:#e74c3c;"><?php echo $total_absent; ?></h3><p>Absent</p></div>
                <div class="card-icon bg-purple"><i class="fa-solid fa-user-xmark"></i></div>
            </div>

            <div class="card">
                <div class="card-info"><h3><?php echo $total_approved_leave; ?></h3><p>L. Approved</p></div>
                <div class="card-icon bg-present"><i class="fa-solid fa-envelope-open"></i></div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filter-panel" method="GET">
            <div class="filter-group">
                <label>Class</label>
                <select name="class_id">
                    <option value="">All Classes</option>
                    <?php while($c = $classes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($class_filter == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['class_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Teacher</label>
                <select name="teacher_id">
                    <option value="">All Teachers</option>
                    <?php while($t = $teachers->fetch_assoc()): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ($teacher_filter == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo $date_filter; ?>">
            </div>
            <button type="submit" class="btn-primary" style="height: 42px;">Apply Filters</button>
        </form>

        <!-- Attendance Table -->
        <div class="panel">
            <div class="panel-header"><h3>Recent Activity</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Teacher</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No records found matching filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
