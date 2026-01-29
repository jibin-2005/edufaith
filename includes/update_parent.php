<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
require 'validation.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $validator = new Validator();
    
    // Get and sanitize input
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $fullname = $validator->sanitize($_POST['fullname'] ?? '');
    $email = $validator->sanitize($_POST['email'] ?? '');
    $status = $validator->sanitize($_POST['status'] ?? 'active');
    $students = isset($_POST['students']) ? $_POST['students'] : [];

    // Validate parent_id
    if ($parent_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parent ID']);
        exit;
    }

    // Validate fields
    $validator->validateFullName($fullname, 'Full Name');
    $validator->validateEmail($email, 'Email');
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $validator->addError('Status', 'Invalid status selected');
    }

    // Validate parent exists
    $check = $conn->prepare("SELECT id, email FROM users WHERE id = ? AND role = 'parent'");
    $check->bind_param("i", $parent_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parent not found']);
        exit;
    }
    
    $old_data = $result->fetch_assoc();
    $check->close();

    // Check if email already exists (excluding current user)
    if ($validator->isValid()) {
        $validator->checkEmailExists($email, $conn, $parent_id, 'Email');
    }

    // If validation fails, return errors
    if (!$validator->isValid()) {
        echo json_encode([
            'success' => false, 
            'message' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        exit;
    }

    // Update Parent Info
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullname, $email, $status, $parent_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    // Update Student Links
    // 1. Remove all existing links
    $conn->query("DELETE FROM parent_student WHERE parent_id = $parent_id");
    
    // 2. Add new links (validate student IDs)
    if (!empty($students)) {
        $link_stmt = $conn->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
        foreach ($students as $student_id) {
            $sid = intval($student_id);
            if ($sid > 0) {
                $link_stmt->bind_param("ii", $parent_id, $sid);
                $link_stmt->execute();
            }
        }
        $link_stmt->close();
    }

    $message = "Parent updated successfully.";
    
    // Note about email change
    if ($old_data['email'] !== $email) {
        $message .= " Note: Email changed. Firebase authentication may need manual sync via Firebase Console.";
    }

    echo json_encode(['success' => true, 'message' => $message]);
    $conn->close();
}
?>
