<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $class_id = $_POST['class_id'] ?? 0;
    $attendance_data = $_POST['attendance'] ?? [];
    $teacher_id = $_SESSION['user_id'];

    // 1. Sunday Validation (per requirements)
    $weekday = date('w', strtotime($date));
    if ($weekday != 0) {
        // die("Error: Attendance can only be marked on Sundays.");
        // We might want to allow editing past Sundays, so we only block if it's TODAY and not Sunday?
        // Actually the prompt says "Attendance marking is allowed only on Sundays, Editing past Sundays is allowed"
        if ($date == date('Y-m-d')) {
             die("Error: Today is not Sunday. Attendance can only be marked on Sundays.");
        }
    }

    // 2. Clear existing records for this class and date to allow easy updates/re-submissions
    // The UNIQUE key is (student_id, date), but it's better to process individually or use ON DUPLICATE KEY UPDATE
    
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, date, status) 
                           VALUES (?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE status = VALUES(status), teacher_id = VALUES(teacher_id)");
    
    foreach ($attendance_data as $student_id => $status) {
        $stmt->bind_param("iiiss", $student_id, $class_id, $teacher_id, $date, $status);
        $stmt->execute();
    }

    $stmt->close();
    
    // Redirect back
    $redirect_url = ($_SESSION['role'] === 'admin') ? "../admin/attendance_admin.php" : "../user/attendance_teacher.php";
    header("Location: $redirect_url?msg=saved&date=$date&class_id=$class_id");
    exit;
}
?>
