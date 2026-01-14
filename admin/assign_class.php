<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = intval($_POST['teacher_id']);
    $class_id = intval($_POST['class_id']);

    // Check if teacher valid
    $check = $conn->query("SELECT id FROM users WHERE id = $teacher_id AND role = 'teacher'");
    if ($check->num_rows == 0) {
        die("Invalid teacher ID.");
    }

    // 1. Remove this teacher from any other class first (since logic implies 1 class per teacher, or maybe teacher can have multiple?)
    // If I want to support 1 teacher - 1 class strictly? 
    // The previous code in my_class.php allows 1 teacher to have multiple classes (it does loop).
    // But `classes` table has `teacher_id` column which implies One Class can have One Teacher.
    // However, does one teacher have multiple classes?
    // If `classes` table has:
    // Class 1 -> Teacher A
    // Class 2 -> Teacher A
    // Then Teacher A has 2 classes.
    // THIS IS VALID.
    
    // BUT: If Class 1 already has Teacher B, and we assign it to Teacher A, Teacher B loses Class 1.
    // This is also consistent with `teacher_id` in `classes`.
    
    // So current logic: Update `classes` table for the selected class to set `teacher_id` = $teacher_id.

    $stmt = $conn->prepare("UPDATE classes SET teacher_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $teacher_id, $class_id);
    
    if ($stmt->execute()) {
        header("Location: manage_teachers.php?msg=assigned");
    } else {
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>
