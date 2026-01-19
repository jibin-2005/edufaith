<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$class_id = $_GET['class_id'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$weekday = date('w', strtotime($date));

// Sunday Validation Logic
$is_sunday = ($weekday == 0);
$error_msg = "";
if (!$is_sunday) {
    $error_msg = "Attendance can only be marked on Sundays. Please select a Sunday.";
}

// Get Teacher's assigned classes
$teacher_id = $_SESSION['user_id'];
$classes_stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY class_name");
$classes_stmt->bind_param("i", $teacher_id);
$classes_stmt->execute();
$classes = $classes_stmt->get_result();

// Auto-select Class if only one exists
if (!$class_id && $classes->num_rows == 1) {
    $row = $classes->fetch_assoc();
    $class_id = $row['id'];
    // Reset internal pointer for the loop below
    $classes->data_seek(0);
}

// Default Date Logic: If Today is not Sunday, default to previous Sunday
if (!isset($_GET['date'])) {
    if (date('w') != 0) {
        $date = date('Y-m-d', strtotime('last sunday'));
    } else {
        $date = date('Y-m-d');
    }
}
$weekday = date('w', strtotime($date));

// Sunday Validation Logic
$is_sunday = ($weekday == 0);
$error_msg = "";
if (!$is_sunday) {
    $error_msg = "Selected date is not a Sunday. Please select a Sunday to view/mark attendance.";
}

if ($class_id && $is_sunday) {
    // Fetch students in this class
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'student' AND class_id = ? AND status='active' ORDER BY username ASC");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $students_result = $stmt->get_result();
    
    // Check if attendance already marked
    $check_stmt = $conn->prepare("SELECT user_id, status FROM attendance WHERE attendance_date = ?");
    $check_stmt->bind_param("s", $date);
    $check_stmt->execute();
    $existing = $check_stmt->get_result();
    $attendance_map = [];
    while ($row = $existing->fetch_assoc()) {
        $attendance_map[$row['user_id']] = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        RealTimeSync.checkAndTriggerFromURL();
    </script>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .controls { display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; margin-top: 20px; }
        .radio-group { display: flex; gap: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .alert-info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_history.php" class="active"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="container">
            <h2>Mark Attendance</h2>
            
            <?php if (!$is_sunday): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
                <div class="alert-info" style="background:#d4edda; color:#155724; border-color:#c3e6cb;">
                    <i class="fa-solid fa-check-circle"></i> Attendance saved successfully!
                </div>
            <?php endif; ?>

            <form method="GET" class="controls">
                <div class="form-group">
                    <label>Select Your Class</label>
                    <select name="class_id" required onchange="this.form.submit()">
                        <option value="">-- Choose Class --</option>
                        <?php while($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date (Sundays Only)</label>
                    <input type="date" name="date" value="<?= $date ?>" required onchange="this.form.submit()">
                </div>
            </form>

        <?php if ($students_result && $students_result->num_rows > 0): ?>
            <div style="margin-bottom: 10px;">
                <button type="button" onclick="markAll('present')" style="padding:5px 10px; background:#e8f4fc; border:1px solid #3498db; color:#3498db; border-radius:4px; cursor:pointer;">Mark All Present</button>
                <button type="button" onclick="markAll('absent')" style="padding:5px 10px; background:#fce8e8; border:1px solid #e74c3c; color:#e74c3c; border-radius:4px; cursor:pointer;">Mark All Absent</button>
            </div>

            <form action="../includes/save_attendance.php" method="POST">
                <input type="hidden" name="date" value="<?= $date ?>">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students_result->fetch_assoc()): ?>
                            <?php 
                                $status = $attendance_map[$student['id']] ?? 'present'; // Default to present (lowercase)
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($student['username']) ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label>
                                            <input type="radio" name="status[<?= $student['id'] ?>]" value="present" <?= $status == 'present' ? 'checked' : '' ?>> Present
                                        </label>
                                        <label style="color: red;">
                                            <input type="radio" name="status[<?= $student['id'] ?>]" value="absent" <?= $status == 'absent' ? 'checked' : '' ?>> Absent
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn">Save Attendance</button>
            </form>
            
            <script>
                function markAll(status) {
                    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
                    radios.forEach(radio => radio.checked = true);
                }
            </script>
        <?php elseif ($class_id): ?>
            <p>No students found in this class.</p>
        <?php endif; ?>
        
        <br>
        <a href="dashboard_teacher.php">Back to Dashboard</a>
    </div>
</body>
</html>
