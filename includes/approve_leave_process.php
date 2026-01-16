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
        header("Location: ../user/manage_leaves.php?msg=success");
    } else {
        header("Location: ../user/manage_leaves.php?error=db_error");
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
