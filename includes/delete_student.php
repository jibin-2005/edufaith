<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = intval($_POST['student_id']);
    $delete_type = isset($_POST['delete_type']) ? $_POST['delete_type'] : 'soft'; // Default to soft

    // Validate student exists
    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $check->bind_param("i", $student_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $check->close();

    if ($delete_type === 'soft') {
        // Soft Delete: Set status to inactive
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Student account deactivated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
    } elseif ($delete_type === 'hard') {
        // Hard Delete: Permanent removal
        // Check if student has any results
        $check_results = $conn->prepare("SELECT COUNT(*) as count FROM results WHERE student_id = ?");
        $check_results->bind_param("i", $student_id);
        $check_results->execute();
        $result_count = $check_results->get_result()->fetch_assoc()['count'];
        $check_results->close();
        
        if ($result_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete student with existing exam results. Mark student as inactive instead.']);
            exit;
        }
        
        // Check if student has any event results
        $check_event_results = $conn->prepare("SELECT COUNT(*) as count FROM event_results WHERE student_id = ?");
        $check_event_results->bind_param("i", $student_id);
        $check_event_results->execute();
        $event_result_count = $check_event_results->get_result()->fetch_assoc()['count'];
        $check_event_results->close();
        
        if ($event_result_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete student with existing event results. Mark student as inactive instead.']);
            exit;
        }
        
        // Note: attendance and other related records with CASCADE FKs will be removed automatically
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Student permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid delete type']);
    }

    $conn->close();
}
?>
