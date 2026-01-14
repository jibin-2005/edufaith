<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (!isset($_POST['date']) || !isset($_POST['class_id'])) {
        // Fallback or error
        header("Location: ../user/mark_attendance.php?msg=error&error=missing_date_or_class");
        exit;
    }

    $date = $_POST['date'];
    $class_id = $_POST['class_id'];
    $status_array = $_POST['status'] ?? [];
    $teacher_id = $_SESSION['user_id'];

    // 1. Server-side Sunday Validation
    $weekday = date('w', strtotime($date));
    if ($weekday != 0) {
        // Not a Sunday!
        die("Error: Attendance can only be marked on Sundays.");
    }

    // 2. Process each student
    $stmt = $conn->prepare("INSERT INTO attendance (user_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?, marked_by = ?");
    
    foreach ($status_array as $student_id => $status) {
        $stmt->bind_param("issisi", $student_id, $date, $status, $teacher_id, $status, $teacher_id);
        $stmt->execute();
    }

    $stmt->close();
    
    // Redirect back
    $class_id = $_POST['class_id'] ?? '';
    header("Location: ../user/mark_attendance.php?msg=saved&date=$date&class_id=$class_id");
    exit;
}
?>
