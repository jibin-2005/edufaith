<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}
require 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'

    if (!$request_id || !in_array($action, ['approve', 'reject'])) {
        die("Error: Invalid request ID ($request_id) or action ($action)");
    }

    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update leave request
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $teacher_id, $request_id);

    if ($stmt->execute()) {
        // Fetch student_id + leave_date for attendance sync
        $s_stmt = $conn->prepare("SELECT student_id, class_id, leave_date FROM leave_requests WHERE id = ?");
        $s_stmt->bind_param("i", $request_id);
        $s_stmt->execute();
        $res = $s_stmt->get_result();
        
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $student_id = $row['student_id'];
            $class_id = (int)$row['class_id'];
            $leave_date = $row['leave_date'];
            $s_stmt->close();

            if ($action === 'approve') {
                $stmt_att = $conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, date, status)
                                            VALUES (?, ?, ?, ?, 'Leave Approved')
                                            ON DUPLICATE KEY UPDATE status = 'Leave Approved', class_id = VALUES(class_id), teacher_id = VALUES(teacher_id)");
                $stmt_att->bind_param("iiis", $student_id, $class_id, $teacher_id, $leave_date);
                $stmt_att->execute();
                $stmt_att->close();
            } else {
                $stmt_att = $conn->prepare("UPDATE attendance SET status = 'Absent' WHERE student_id = ? AND date = ? AND status = 'Pending Leave'");
                $stmt_att->bind_param("is", $student_id, $leave_date);
                $stmt_att->execute();
                $stmt_att->close();
            }

            header("Location: ../user/manage_leaves.php?msg=success&student_id=$student_id");
            exit;
        } else {
            // Updated but student not found? Should not happen.
            die("Error: Request updated but student ID not found. ID: " . $request_id);
        }
    } else {
        die("Database Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
