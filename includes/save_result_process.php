<?php
/**
 * Save Result Process - Backend handler for exam results
 * Handles both exam_1 and exam_2 marks entry/update
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $exam_type = $_POST['exam_type'] ?? '';
    $marks = intval($_POST['marks'] ?? 0);
    $teacher_id = $_SESSION['user_id'];

    // Validation
    if (!$student_id || !in_array($exam_type, ['exam_1', 'exam_2'])) {
        header("Location: ../user/manage_results.php?error=invalid_data");
        exit;
    }

    if ($marks < 0 || $marks > 100) {
        header("Location: ../user/manage_results.php?exam=$exam_type&error=invalid_marks");
        exit;
    }

    // Verify teacher is assigned to student's class
    $verify_sql = "SELECT u.class_id, c.teacher_id 
                   FROM users u 
                   JOIN classes c ON u.class_id = c.id 
                   WHERE u.id = ? AND c.teacher_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $student_id, $teacher_id);
    $verify_stmt->execute();
    
    if ($verify_stmt->get_result()->num_rows === 0) {
        header("Location: ../user/manage_results.php?exam=$exam_type&error=not_your_class");
        exit;
    }

    // Insert or Update result
    $stmt = $conn->prepare("INSERT INTO results (student_id, exam_type, marks, updated_by) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE marks = VALUES(marks), updated_by = VALUES(updated_by)");
    $stmt->bind_param("isii", $student_id, $exam_type, $marks, $teacher_id);

    if ($stmt->execute()) {
        header("Location: ../user/manage_results.php?exam=$exam_type&msg=success");
    } else {
        header("Location: ../user/manage_results.php?exam=$exam_type&error=db_error");
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
