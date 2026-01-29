<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
require 'validation.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize validator
    $validator = new Validator();
    
    // Get and sanitize input
    $fullname = $validator->sanitize($_POST['fullname'] ?? '');
    $email = $validator->sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $validator->sanitize($_POST['role'] ?? '');
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $firebase_uid = isset($_POST['firebase_uid']) ? $validator->sanitize($_POST['firebase_uid']) : null;
    
    // Validate all fields
    $validator->validateFullName($fullname, 'Full Name');
    $validator->validateEmail($email, 'Email');
    $validator->validatePassword($password, true, 'Password');
    $validator->validateRole($role, ['student', 'teacher', 'parent', 'admin'], 'Role');
    
    // Validate class_id for students
    if ($role === 'student') {
        $validator->validateClassId($class_id, $conn, true, 'Class');
    }
    
    // Check if email already exists
    if ($validator->isValid()) {
        $validator->checkEmailExists($email, $conn, null, 'Email');
    }
    
    // Security Check: Teachers can ONLY add Students
    if ($_SESSION['role'] === 'teacher' && $role !== 'student') {
        $validator->addError('Role', 'Teachers can only add students.');
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
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, class_id, firebase_uid, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssss", $fullname, $email, $hashedPassword, $role, $class_id, $firebase_uid);

    if ($stmt->execute()) {
        if ($_SESSION['role'] === 'teacher') {
            $redirect = 'my_class.php?msg=success';
        } else {
            // Admin Logic
            switch ($role) {
                case 'teacher':
                    $redirect = 'manage_teachers.php?msg=success';
                    break;
                case 'parent':
                    $redirect = 'manage_parents.php?msg=success';
                    break;
                default:
                    $redirect = 'manage_students.php?msg=success';
            }
        }
        echo json_encode(['success' => true, 'redirect' => $redirect]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
