<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
require 'validation_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = intval($_POST['teacher_id']);
    $delete_type = isset($_POST['delete_type']) ? $_POST['delete_type'] : 'soft';

    // Validate teacher exists
    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'teacher'");
    $check->bind_param("i", $teacher_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit;
    }
    $check->close();

    // Check if teacher has a class assigned
    if (Validator::teacherHasClass($conn, $teacher_id)) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete teacher who is assigned to a class. Please unassign the class first.']);
        exit;
    }

    // Check if teacher has pending results to grade
    if (Validator::teacherHasPendingResults($conn, $teacher_id)) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete teacher with pending results to grade. Please complete all grading first.']);
        exit;
    }

    if ($delete_type === 'soft') {
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Teacher account deactivated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
    } elseif ($delete_type === 'hard') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Teacher permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
    }

    $conn->close();
}
?>
