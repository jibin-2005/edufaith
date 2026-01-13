<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Security Check: Teachers can ONLY add Students
    if ($_SESSION['role'] === 'teacher' && $role !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Teachers can only add students.']);
        exit;
    }
    $firebase_uid = isset($_POST['firebase_uid']) ? $_POST['firebase_uid'] : null;

    // Check if email already exists in MySQL
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists in MySQL database.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, class_id) VALUES (?, ?, ?, ?, ?)");
    $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : NULL;
    $stmt->bind_param("ssssi", $username, $email, $password, $role, $class_id);

    if ($stmt->execute()) {
        if ($_SESSION['role'] === 'teacher') {
            $redirect = 'my_class.php?msg=success';
        } else {
            // Admin Logic
            $redirect = ($role === 'teacher') ? 'manage_teachers.php?msg=success' : 'manage_students.php?msg=success';
        }
        echo json_encode(['success' => true, 'redirect' => $redirect]);
    } else {
        echo json_encode(['success' => false, 'message' => 'MySQL Error: ' . $stmt->error]);
    }
    
    $stmt->close();
    $checkEmail->close();
    $conn->close();
}
?>
