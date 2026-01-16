<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $leave_date = $_POST['leave_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    // 1. Basic validation
    if (empty($leave_date) || empty($reason)) {
        header("Location: ../user/dashboard_student.php?leave_error=empty_fields");
        exit;
    }

    // 2. Sunday validation
    $weekday = date('w', strtotime($leave_date));
    if ($weekday != 0) {
        header("Location: ../user/dashboard_student.php?leave_error=not_sunday");
        exit;
    }

    // 3. Prevent duplicate requests
    $stmt_check = $conn->prepare("SELECT id FROM leave_requests WHERE student_id = ? AND leave_date = ?");
    $stmt_check->bind_param("is", $student_id, $leave_date);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: ../user/dashboard_student.php?leave_error=duplicate");
        exit;
    }

    // 4. Fetch class_id for the student
    $stmt_class = $conn->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt_class->bind_param("i", $student_id);
    $stmt_class->execute();
    $class_id = $stmt_class->get_result()->fetch_assoc()['class_id'] ?? 0;

    // 5. Insert leave request
    $stmt_insert = $conn->prepare("INSERT INTO leave_requests (student_id, class_id, leave_date, reason) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $student_id, $class_id, $leave_date, $reason);

    if ($stmt_insert->execute()) {
        header("Location: ../user/dashboard_student.php?leave_msg=success");
    } else {
        header("Location: ../user/dashboard_student.php?leave_error=db_error");
    }
    
    $stmt_insert->close();
    $conn->close();
    exit;
}
?>
