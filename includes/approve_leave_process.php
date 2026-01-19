<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'

    if (!$request_id || !in_array($action, ['approve', 'reject'])) {
        header("Location: ../user/manage_leaves.php?error=invalid_action");
        exit;
    }

    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update leave request
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $teacher_id, $request_id);

    if ($stmt->execute()) {
        // Fetch student_id for targeted sync
        $s_stmt = $conn->prepare("SELECT user_id FROM leaves WHERE id = ?");
        $s_stmt->bind_param("i", $request_id);
        $s_stmt->execute();
        $student_id = $s_stmt->get_result()->fetch_assoc()['user_id'];
        $s_stmt->close();

        header("Location: ../user/manage_leaves.php?msg=success&student_id=$student_id");
    } else {
        header("Location: ../user/manage_leaves.php?error=db_error");
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
