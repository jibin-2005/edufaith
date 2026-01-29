<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch Attendance
$sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$total = 0;
$present = 0;
$months = [];

while($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $total++;
    if ($row['status'] === 'Present' || $row['status'] === 'Late') $present++;

    // Monthly Stats
    $monthKey = date('Y-m', strtotime($row['date']));
    if (!isset($months[$monthKey])) {
        $months[$monthKey] = ['total' => 0, 'present' => 0];
    }
    $months[$monthKey]['total']++;
    if ($row['status'] === 'Present' || $row['status'] === 'Late') {
        $months[$monthKey]['present']++;
    }
}
$overall_percent = $total > 0 ? round(($present / $total) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance | Student</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .status-present { color: #2ecc71; background: #eafaf1; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
        .status-absent { color: #e74c3c; background: #fdf2f2; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
        .status-late { color: #f1c40f; background: #fef9e7; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
        
        .month-box { background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 5px solid var(--primary); }
        .progress-bg { background: #eee; height: 8px; border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .progress-bar { background: var(--primary); height: 100%; transition: width 0.5s; }
        .leave-form-panel { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
        .leave-form-panel input, .leave-form-panel textarea { width: 100%; padding: 10px; border: none; border-radius: 6px; margin-top: 5px; font-family: inherit; }
        .leave-form-panel label { font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px; }
        .leave-form-panel button { background: white; color: #667eea; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; margin-top: 15px; transition: all 0.3s; }
        .leave-form-panel button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="attendance_student.php" class="active"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="leave_student.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="view_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>My Attendance History</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="summary-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f4fd; color: #3498db;"><i class="fa-solid fa-calendar-days"></i></div>
                <div>
                    <h3 style="margin:0;"><?php echo $total; ?></h3>
                    <p style="margin:0; color:#777; font-size:13px;">Total Days</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #eafaf1; color: #2ecc71;"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <h3 style="margin:0;"><?php echo $present; ?></h3>
                    <p style="margin:0; color:#777; font-size:13px;">Days Present</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef9e7; color: #f1c40f;"><i class="fa-solid fa-percent"></i></div>
                <div>
                    <h3 style="margin:0;"><?php echo $overall_percent; ?>%</h3>
                    <p style="margin:0; color:#777; font-size:13px;">Attendance Rate</p>
                </div>
            </div>
        </div>

        <!-- Apply for Leave Panel -->
        <div class="leave-form-panel">
            <h3 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-envelope"></i> Apply for Leave
            </h3>
            <?php if (isset($_GET['leave_msg'])): ?>
                <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-check"></i> Leave request submitted successfully!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['leave_error'])): ?>
                <div style="background: rgba(255,100,100,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-exclamation"></i> Error: <?php echo htmlspecialchars($_GET['leave_error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="../includes/apply_leave_process.php" method="POST" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
                <div>
                    <label>Sunday Date</label>
                    <input type="date" name="leave_date" required>
                </div>
                <div>
                    <label>Reason for Leave</label>
                    <input type="text" name="reason" required placeholder="e.g., Family function, Illness...">
                </div>
                <button type="submit">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </form>
            <p style="margin: 10px 0 0; font-size: 12px; opacity: 0.9;">
                <i class="fa-solid fa-info-circle"></i> Leave can only be requested for Sundays. View all requests in <a href="leave_student.php" style="color: white; text-decoration: underline;">Leave Requests</a>.
            </p>
        </div>

        <div class="section-grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
            <!-- History Table -->
            <div class="panel">
                <div class="panel-header"><h3>Recent Records</h3></div>
                <table>
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
                                    <td><?php echo date('d M Y (l)', strtotime($row['date'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align:center; color:#999;">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Monthly Breakdown -->
            <div>
                <h3 style="margin-top:0; margin-bottom:15px;">Monthly Summary</h3>
                <?php foreach ($months as $ym => $stats): ?>
                    <?php 
                        $monthName = date('F Y', strtotime($ym . '-01'));
                        $mPercent = round(($stats['present'] / $stats['total']) * 100, 1);
                    ?>
                    <div class="month-box">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:600;"><?php echo $monthName; ?></span>
                            <span style="font-size:14px; color:var(--primary); font-weight:700;"><?php echo $mPercent; ?>%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-bar" style="width: <?php echo $mPercent; ?>%;"></div>
                        </div>
                        <p style="margin: 5px 0 0; font-size:11px; color:#888;">
                            <?php echo $stats['present']; ?> / <?php echo $stats['total']; ?> days
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
