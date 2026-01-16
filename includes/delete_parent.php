<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parent_id = intval($_POST['parent_id']);
    $delete_type = isset($_POST['delete_type']) ? $_POST['delete_type'] : 'soft'; // Default to soft

    // Validate parent exists
    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'parent'");
    $check->bind_param("i", $parent_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parent not found']);
        exit;
    }
    $check->close();

    if ($delete_type === 'soft') {
        // Soft Delete: Set status to inactive
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $parent_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Parent account deactivated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
        
    } elseif ($delete_type === 'hard') {
        // Hard Delete: Remove from database
        // Only admins can hard delete
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Only admins can permanently delete accounts']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $parent_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Parent permanently deleted']);
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
