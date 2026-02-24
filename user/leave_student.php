<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$student_id = $_SESSION['user_id'];

// Filters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build query with filters
$sql = "SELECT lr.*, u.username as teacher_name 
        FROM leave_requests lr 
        LEFT JOIN users u ON lr.reviewed_by = u.id 
        WHERE lr.student_id = ?";
$params = [$student_id];
$types = "i";

if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND lr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_from) {
    $sql .= " AND lr.leave_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $sql .= " AND lr.leave_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY lr.leave_date DESC, lr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params) && count($params) > 1) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("i", $student_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

// Calculate Statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM leave_requests WHERE student_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave History | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pending { background: #fff4e5; color: #ffa117; }
        .approved { background: #eafaf1; color: #2ecc71; }
        .rejected { background: #fdf2f2; color: #e74c3c; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-info h3 { margin: 0; font-size: 24px; color: #333; }
        .stat-info p { margin: 5px 0 0; font-size: 13px; color: #666; }
        .filter-panel { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: #555; }
        .filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-filter { padding: 8px 20px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-filter:hover { opacity: 0.9; }
        .btn-clear { padding: 8px 20px; background: #95a5a6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-clear:hover { background: #7f8c8d; }
        .btn-apply { padding: 10px 24px; background: #2ecc71; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .btn-apply:hover { background: #27ae60; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #555; }
        .reason-cell { max-width: 300px; word-wrap: break-word; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.3; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Leave History</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f4fd; color: #3498db;">
                    <i class="fa-solid fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total'] ?? 0; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff4e5; color: #ffa117;">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #eafaf1; color: #2ecc71;">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['approved_count'] ?? 0; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fdf2f2; color: #e74c3c;">
                    <i class="fa-solid fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['rejected_count'] ?? 0; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
        </div>

        <!-- Apply for Leave Button -->
        <a href="attendance_student.php" class="btn-apply">
            <i class="fa-solid fa-plus"></i> Apply for New Leave
        </a>

        <!-- Filters -->
        <div class="filter-panel">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </div>
                <?php if ($status_filter || $date_from || $date_to): ?>
                <div class="filter-group">
                    <a href="leave_student.php" class="btn-clear">
                        <i class="fa-solid fa-times"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Leave History Table -->
        <div class="panel">
            <div class="panel-header">
                <h3>Leave Request History</h3>
            </div>
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Leave Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Reviewed By</th>
                            <th>Submitted On</th>
                            <th>Reviewed On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($row['leave_date'])); ?></strong><br>
                                    <small style="color:#999;"><?php echo date('l', strtotime($row['leave_date'])); ?></small>
                                </td>
                                <td class="reason-cell"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['teacher_name']): ?>
                                        <i class="fa-solid fa-user-check" style="color:#2ecc71;"></i>
                                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                                    <?php else: ?>
                                        <span style="color:#aaa;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($row['created_at'])); ?><br>
                                    <small style="color:#999;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php if (isset($row['updated_at']) && $row['updated_at'] && $row['status'] !== 'pending'): ?>
                                        <?php echo date('M j, Y', strtotime($row['updated_at'])); ?><br>
                                        <small style="color:#999;"><?php echo date('h:i A', strtotime($row['updated_at'])); ?></small>
                                    <?php else: ?>
                                        <span style="color:#aaa;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h3>No Leave Requests Found</h3>
                    <p><?php echo ($status_filter || $date_from || $date_to) ? 'Try adjusting your filters or' : ''; ?> <a href="attendance_student.php" style="color:var(--primary);">apply for a new leave request</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

