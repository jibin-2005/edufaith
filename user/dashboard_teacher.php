<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

// 1. Fetch Summary Data
// Total Students
$sql_students = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
$result_students = $conn->query($sql_students);
$total_students = $result_students->fetch_assoc()['count'];

// Present/Absent Today
$date = date("Y-m-d");
$present_count = 0;
$absent_count = 0;

$sql_att_stats = "SELECT status, COUNT(*) as count FROM attendance WHERE attendance_date = '$date' GROUP BY status";
$result_att_stats = $conn->query($sql_att_stats);
while ($row = $result_att_stats->fetch_assoc()) {
    if ($row['status'] == 'present') $present_count = $row['count'];
    if ($row['status'] == 'absent') $absent_count = $row['count'];
}

// 2. Fetch Students for Attendance Table
// We also fetch today's status if already marked
$sql_list = "SELECT u.id, u.username, u.email, a.status 
             FROM users u 
             LEFT JOIN attendance a ON u.id = a.user_id AND a.attendance_date = '$date'
             WHERE u.role = 'student' 
             ORDER BY u.username ASC";
$result_list = $conn->query($sql_list);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .radio-group { display: flex; gap: 10px; }
        .radio-label { cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px; }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_history.php"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Teacher Dashboard • <?php echo date("l, F j, Y"); ?></p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Teacher">
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="grid-container">
            <!-- Existing Stats -->
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $present_count; ?></h3>
                    <p>Present Today</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $absent_count; ?></h3>
                    <p>Absent Today</p>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'attendance_saved'): ?>
            <div class="success-msg">Attendance saved successfully!</div>
        <?php endif; ?>

        <div class="section-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
            <!-- QUICK ACTIONS -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Class Actions</h3>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button style="flex:1; padding:12px; background:#e8f4fd; color:#3498db; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Assignment creation modal will open here')">
                        <i class="fa-solid fa-file-pen"></i> Give Assignment
                    </button>
                    <button style="flex:1; padding:12px; background:#f4ecff; color:#9b59b6; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Announcement modal will open here')">
                        <i class="fa-solid fa-bullhorn"></i> Announcement
                    </button>
                    <button style="flex:1; padding:12px; background:#eafaf1; color:#2ecc71; border:none; border-radius:8px; cursor:pointer; font-weight:600;" onclick="alert('Lesson plan modal will open here')">
                        <i class="fa-solid fa-book-open"></i> Plan Lesson
                    </button>
                </div>
            </div>

            <!-- LEAVE REQUESTS -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Leave Requests</h3>
                </div>
                <ul>
                    <?php
                    // Fetch pending leaves for review (mock logic: seeing all leaves for now)
                    $l_sql = "SELECT l.id, u.username, l.reason, l.start_date FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status='pending' LIMIT 3";
                    $l_res = $conn->query($l_sql);
                    if ($l_res->num_rows > 0) {
                        while($row = $l_res->fetch_assoc()) {
                            echo "<li style='border-bottom:1px solid #eee; padding:10px 0; display:flex; justify-content:space-between; align-items:center;'>";
                            echo "<div><strong>".htmlspecialchars($row['username'])."</strong><br><small>".$row['reason']."</small></div>";
                            echo "<div>
                                    <button style='background:green; color:white; border:none; border-radius:4px; padding:4px 8px; cursor:pointer;'>✓</button>
                                    <button style='background:#e74c3c; color:white; border:none; border-radius:4px; padding:4px 8px; cursor:pointer;'>✕</button>
                                  </div>";
                            echo "</li>";
                        }
                    } else {
                         echo "<p style='color:#999; text-align:center;'>No pending requests.</p>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Mark Attendance (<?php echo date("d-m-Y"); ?>)</h3>
            </div>
            
            <form action="../includes/save_attendance.php" method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Status (Present / Absent)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_list->num_rows > 0): ?>
                            <?php while($row = $result_list->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="present" 
                                                    <?php echo ($row['status'] == 'present' || !$row['status']) ? 'checked' : ''; ?>> 
                                                <span style="color:var(--primary);">Present</span>
                                            </label>
                                            <label class="radio-label">
                                                <input type="radio" name="attendance[<?php echo $row['id']; ?>]" value="absent" 
                                                    <?php echo ($row['status'] == 'absent') ? 'checked' : ''; ?>> 
                                                <span style="color:#e74c3c;">Absent</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center;">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                        <i class="fa-solid fa-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
        
    </div>

</body>
</html>
