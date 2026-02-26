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

    // 1. Validation Logic
    require 'validation_helper.php';
    $valDate = Validator::validateDate($date, 'Attendance Date', 'future_only');
    if ($valDate !== true) {
        die("Error: " . $valDate);
    }
    
    // Sunday-only validation
    if (!Validator::isSunday($date)) {
        die("Error: Attendance can only be marked on Sundays.");
    }
    
    // Rule: Teachers cannot edit previous dates (User Requirement 4)
    // "Attendance marking allowed only for current date" (User Requirement 3)
    if ($_SESSION['role'] === 'teacher') {
        $today = date('Y-m-d');
        if ($date !== $today) {
             die("Error: Teachers can only mark attendance for the current date.");
        }

        // Rule: Only assigned teacher should be able to mark attendance of their class (New Requirement)
        // Verify class ownership
        $stmt_verify = $conn->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt_verify->bind_param("ii", $class_id, $teacher_id);
        $stmt_verify->execute();
        if ($stmt_verify->get_result()->num_rows === 0) {
            die("Error: You are not assigned to this class.");
        }
        $stmt_verify->close();
    }

    // 2. Clear existing records for this class and date to allow easy updates/re-submissions
    // The UNIQUE key is (student_id, date), but it's better to process individually or use ON DUPLICATE KEY UPDATE
    
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, date, status) 
                           VALUES (?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE status = VALUES(status), teacher_id = VALUES(teacher_id)");
    
    $allowed_statuses = ['Present', 'Absent']; // Allowed values

    foreach ($attendance_data as $student_id => $status) {
        // Rule: Remove Late (User Requirement 4) and strict check
        if (!in_array($status, $allowed_statuses)) {
            continue; // Skip invalid status
        }

        // Verify student is enrolled in this class
        $verify_student = $conn->prepare("SELECT id FROM users WHERE id = ? AND class_id = ? AND role = 'student'");
        $verify_student->bind_param("ii", $student_id, $class_id);
        $verify_student->execute();
        if ($verify_student->get_result()->num_rows === 0) {
            $verify_student->close();
            continue; // Skip students not in this class
        }
        $verify_student->close();

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
