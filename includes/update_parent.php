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
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $students = isset($_POST['students']) ? $_POST['students'] : [];

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
    
    // 2. Add new links
    if (!empty($students)) {
        $link_stmt = $conn->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
        foreach ($students as $student_id) {
            $sid = intval($student_id);
            $link_stmt->bind_param("ii", $parent_id, $sid);
            $link_stmt->execute();
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
