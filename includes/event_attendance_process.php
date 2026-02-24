<?php
/**
 * Event Attendance Handler
 * Processes attendance status updates for event registrations
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $registration_id = intval($_POST['registration_id'] ?? 0);
    $attendance_status = $_POST['attendance_status'] ?? '';
    
    $valid_statuses = ['registered', 'attended', 'absent', 'cancelled'];
    
    if (!$registration_id || !in_array($attendance_status, $valid_statuses)) {
        $_GET['error'] = 'Invalid data';
    } else {
        $stmt = $conn->prepare("UPDATE event_registrations SET attendance_status = ? WHERE id = ?");
        $stmt->bind_param("si", $attendance_status, $registration_id);
        
        if ($stmt->execute()) {
            $_GET['msg'] = 'Attendance updated';
        } else {
            $_GET['error'] = 'Error updating attendance: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle removing registration
if (isset($_GET['remove_reg'])) {
    $reg_id = intval($_GET['remove_reg']);
    $event_id = intval($_GET['event_id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: manage_events.php?view=$event_id&msg=registration_removed");
    exit;
}

echo json_encode(['success' => true]);
?>
