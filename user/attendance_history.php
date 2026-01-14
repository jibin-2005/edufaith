<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$date_default = (date('w') == 0) ? date("Y-m-d") : date("Y-m-d", strtotime("last sunday"));
$date_filter = isset($_GET['date']) ? $_GET['date'] : $date_default;

$sql = "SELECT u.username, a.status 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.attendance_date = '$date_filter' 
        ORDER BY u.username ASC";
$result = $conn->query($sql);
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Attendance History</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <div class="filter-bar">
            <form method="GET" style="display:flex; align-items:center; gap:10px;">
                <label>Select Date:</label>
                <input type="date" name="date" value="<?php echo $date_filter; ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                <button type="submit" style="padding:8px 16px; background:var(--primary); color:white; border:none; border-radius:4px; cursor:pointer;">Filter</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Attendance for: <?php echo date("F j, Y", strtotime($date_filter)); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <?php if($row['status'] == 'present'): ?>
                                    <span class="badge present">Present</span>
                                <?php else: ?>
                                    <span class="badge absent">Absent</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align:center;">No records found for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
