<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $attendance_data = $_POST['attendance']; // Array of user_id => status
    $date = date("Y-m-d");
    $teacher_id = $_SESSION['user_id'];

    if (!empty($attendance_data)) {
        // Prepare check statement
        $checkStmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ?");
        
        // Prepare insert/update statement
        // For simplicity: Delete existing for today then insert new (or use INSERT ON DUPLICATE KEY UPDATE)
        // Let's use INSERT ON DUPLICATE KEY UPDATE logic if id is unknown, but since date+user_id should be unique, we can treat it as such.
        // Actually simpler: Loop through and insert.
        
        foreach ($attendance_data as $student_id => $status) {
            // Validate status
            if ($status !== 'present' && $status !== 'absent') continue;

            // Check if already marked for today
            $checkStmt->bind_param("is", $student_id, $date);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                // Update
                $updateStmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE user_id = ? AND attendance_date = ?");
                $updateStmt->bind_param("siis", $status, $teacher_id, $student_id, $date);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert
                $insertStmt = $conn->prepare("INSERT INTO attendance (user_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("issi", $student_id, $date, $status, $teacher_id);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }
        $checkStmt->close();
        
        // Redirect with success
        header("Location: ../user/dashboard_teacher.php?msg=attendance_saved");
        exit;
    }
}
header("Location: ../user/dashboard_teacher.php?msg=error");
?>
