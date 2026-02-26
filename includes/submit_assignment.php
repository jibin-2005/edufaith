<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}
require 'db.php';
require 'validation_helper.php';

$student_id = $_SESSION['user_id'];
$assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

// Validate assignment exists and get due date
$stmt = $conn->prepare("
    SELECT a.id, a.due_date, a.class_id FROM assignments a
    JOIN users u ON u.class_id = a.class_id
    WHERE a.id = ? AND u.id = ?
");
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("Assignment not found or not assigned to your class"));
    exit;
}
$assignment = $result->fetch_assoc();
$stmt->close();

// Check if due date has passed (late submission prevention)
$due_date = strtotime($assignment['due_date']);
$current_date = time();
if ($current_date > $due_date) {
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("Assignment submission deadline has passed"));
    exit;
}

// Validate file upload
if (!isset($_FILES['assignment_file'])) {
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("No file uploaded"));
    exit;
}

$file = $_FILES['assignment_file'];

// Validate file using validation helper (max 5MB, PDF/DOC/DOCX only)
$valFile = Validator::validateFile($file, 'Assignment File', ['pdf', 'doc', 'docx'], 5242880); // 5MB
if ($valFile !== true) {
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode($valFile));
    exit;
}

// Create directory if it doesn't exist
$upload_dir = dirname(__DIR__) . '/uploads/assignments';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename with proper extension
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = sprintf("assignment_%d_student_%d_%s.%s", $assignment_id, $student_id, uniqid(), $file_extension);
$filepath = $upload_dir . '/' . $filename;

// Move file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("Failed to save file"));
    exit;
}

// Check for duplicate submission (prevent resubmission without approval)
$stmt_check = $conn->prepare("
    SELECT id, status FROM assignment_submissions 
    WHERE assignment_id = ? AND student_id = ?
");
$stmt_check->bind_param("ii", $assignment_id, $student_id);
$stmt_check->execute();
$existing = $stmt_check->get_result();

if ($existing->num_rows > 0) {
    $existing_data = $existing->fetch_assoc();
    if ($existing_data['status'] === 'submitted' || $existing_data['status'] === 'graded') {
        // Delete the newly uploaded file
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $stmt_check->close();
        header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("You have already submitted this assignment. Contact your teacher to resubmit."));
        exit;
    }
}
$stmt_check->close();

// Update database - check if submission exists
$stmt = $conn->prepare("
    SELECT id FROM assignment_submissions 
    WHERE assignment_id = ? AND student_id = ?
");
$stmt->bind_param("ii", $assignment_id, $student_id);
$stmt->execute();
$existing_check = $stmt->get_result();
$stmt->close();

if ($existing_check->num_rows > 0) {
    // Update existing submission
    $stmt = $conn->prepare("
        UPDATE assignment_submissions 
        SET submission_file = ?, status = 'submitted', submitted_at = NOW()
        WHERE assignment_id = ? AND student_id = ?
    ");
    $stmt->bind_param("sii", $filename, $assignment_id, $student_id);
} else {
    // Create new submission
    $stmt = $conn->prepare("
        INSERT INTO assignment_submissions (assignment_id, student_id, submission_file, status, submitted_at)
        VALUES (?, ?, ?, 'submitted', NOW())
    ");
    $stmt->bind_param("iis", $assignment_id, $student_id, $filename);
}

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../user/my_lessons.php?msg=success");
} else {
    // Delete file if database save failed
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    header("Location: ../user/my_lessons.php?msg=error&error=" . urlencode("Failed to save submission record"));
}
exit;
?>
