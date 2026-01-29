<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['assignment_file'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $student_id = $_SESSION['user_id'];
    
    $file = $_FILES['assignment_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    $fileExt = strtolower(end(explode('.', $fileName)));
    $allowed = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt');
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) { // 5MB limit
                $fileNameNew = "assignment_" . $assignment_id . "_student_" . $student_id . "_" . uniqid('', true) . "." . $fileExt;
                $uploadDir = '../uploads/assignments/';
                
                // Create directory if not exists
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileDestination = $uploadDir . $fileNameNew;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Save to database
                    $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $assignment_id, $student_id, $fileDestination);
                    
                    if ($stmt->execute()) {
                        header("Location: my_lessons.php?msg=upload_success");
                    } else {
                        echo "Database Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    echo "Failed to move uploaded file.";
                }
            } else {
                echo "File is too big! Max 5MB.";
            }
        } else {
            echo "Error uploading file! Code: " . $fileError;
        }
    } else {
        echo "Invalid file type! Allowed: jpg, png, pdf, doc, docx, txt";
    }
} else {
    header("Location: my_lessons.php");
}
?>
