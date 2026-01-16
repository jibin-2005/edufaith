<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$parent_id = $_SESSION['user_id'];
$selected_student_id = $_GET['student_id'] ?? '';

// Fetch Linked Children for dropdown
$sql_children = "SELECT u.id, u.username 
                 FROM users u 
                 JOIN parent_student ps ON u.id = ps.student_id 
                 WHERE ps.parent_id = ?";
$stmt_kids = $conn->prepare($sql_children);
$stmt_kids->bind_param("i", $parent_id);
$stmt_kids->execute();
$children_res = $stmt_kids->get_result();

$children = [];
while($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}

// Default to first child if none selected
if (empty($selected_student_id) && count($children) > 0) {
    $selected_student_id = $children[0]['id'];
}

// Fetch Attendance for selected child
$rows = [];
$total = 0;
$present = 0;
$months = [];

if ($selected_student_id) {
    // Verify ownership
    $check = $conn->prepare("SELECT 1 FROM parent_student WHERE parent_id = ? AND student_id = ?");
    $check->bind_param("ii", $parent_id, $selected_student_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $selected_student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $total++;
            if ($row['status'] === 'Present' || $row['status'] === 'Late' || $row['status'] === 'Leave Approved') $present++;

            $monthKey = date('Y-m', strtotime($row['date']));
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = ['total' => 0, 'present' => 0];
            }
            $months[$monthKey]['total']++;
            if ($row['status'] === 'Present' || $row['status'] === 'Late' || $row['status'] === 'Leave Approved') {
                $months[$monthKey]['present']++;
            }
        }
        
        // Fetch Leave History for selected child
        $l_rows = [];
        $l_sql = "SELECT lr.*, u.username as teacher_name 
                  FROM leave_requests lr 
                  LEFT JOIN users u ON lr.reviewed_by = u.id 
                  WHERE lr.student_id = ? 
                  ORDER BY lr.leave_date DESC LIMIT 5";
        $l_stmt = $conn->prepare($l_sql);
        $l_stmt->bind_param("i", $selected_student_id);
        $l_stmt->execute();
        $l_result = $l_stmt->get_result();
        while($lr = $l_result->fetch_assoc()) {
            $l_rows[] = $lr;
        }
    }
}
$overall_percent = $total > 0 ? round(($present / $total) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Attendance | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .child-selector { background: white; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; border-left: 4px solid var(--primary); }
        .month-stat { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .Present { background: #eafaf1; color: #2ecc71; }
        .Absent { background: #fdf2f2; color: #e74c3c; }
        .Late { background: #fef9e7; color: #f1c40f; }
        .status-badge.approved { background: #eafaf1; color: #2ecc71; }
        .status-badge.pending { background: #fff4e5; color: #ffa117; }
        .status-badge.rejected { background: #fdf2f2; color: #e74c3c; }
        /* Map specific attendance statuses to classes */
        .Leave\.Approved { background: #eafaf1; color: #2ecc71; }
        .Pending\.Leave { background: #fff4e5; color: #ffa117; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="attendance_parent.php" class="active"><i class="fa-solid fa-calendar-check"></i> Child Attendance</a></li>
            <li><a href="my_children.php"><i class="fa-solid fa-users"></i> My Children</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Attendance Monitoring</h2>
            <div class="user-profile"><span>Parent</span></div>
        </div>

        <?php if (count($children) > 1): ?>
            <div class="child-selector">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Switch Child:</span>
                <form method="GET" style="display:inline;">
                    <select name="student_id" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                        <?php foreach($children as $child): ?>
                            <option value="<?php echo $child['id']; ?>" <?php echo ($selected_student_id == $child['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($child['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($selected_student_id): ?>
            <div class="grid-container" style="margin-bottom: 25px;">
                <div class="card">
                    <div class="card-info"><h3><?php echo $total; ?></h3><p>Total Days tracked</p></div>
                    <div class="card-icon bg-blue"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
                <div class="card" style="grid-column: span 2;">
                    <div class="card-info"><h3><?php echo $overall_percent; ?>%</h3><p>Average Attendance</p></div>
                    <div class="card-icon bg-green"><i class="fa-solid fa-chart-line"></i></div>
                </div>
            </div>

            <div class="section-grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Monthly View -->
                <div>
                    <h3 style="margin-top:0; margin-bottom:15px;">Monthly Summary</h3>
                    <?php if (count($months) > 0): ?>
                        <?php foreach ($months as $ym => $stats): ?>
                            <?php 
                                $mPercent = round(($stats['present'] / $stats['total']) * 100, 1);
                                $mName = date('F Y', strtotime($ym . '-01'));
                            ?>
                            <div class="month-stat">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?php echo $mName; ?></strong>
                                    <span style="color:var(--primary); font-weight:700;"><?php echo $mPercent; ?>%</span>
                                </div>
                                <div style="background:#eee; height:6px; border-radius:3px; margin-top:10px; overflow:hidden;">
                                    <div style="background:var(--primary); width:<?php echo $mPercent; ?>%; height:100%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#999; font-style:italic;">No monthly data available.</p>
                    <?php endif; ?>
                </div>

                <!-- Recent History -->
                <div class="panel">
                    <div class="panel-header"><h3>Recent Stats</h3></div>
                    <table style="margin-bottom: 20px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) > 0): ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                        <td><span class="status-badge <?php echo str_replace(' ', '.', $row['status']); ?>"><?php echo $row['status']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center;">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="panel-header"><h3>Leave History</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($l_rows) > 0): ?>
                                <?php foreach ($l_rows as $lr): ?>
                                    <tr>
                                        <td title="<?php echo htmlspecialchars($lr['reason']); ?>"><?php echo date('d M Y', strtotime($lr['leave_date'])); ?></td>
                                        <td><span class="status-badge <?php echo $lr['status']; ?>"><?php echo ucfirst($lr['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center;">No leave applications.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 100px; color: #999;">
                <i class="fa-solid fa-user-slash" style="font-size: 60px; margin-bottom: 20px; opacity: 0.2;"></i>
                <p>No student accounts linked to your profile.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
