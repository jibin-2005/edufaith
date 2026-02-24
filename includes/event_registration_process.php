<?php
/**
 * Event Registration Handler
 * Processes event registration requests from students
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $event_id = intval($_POST['event_id'] ?? 0);
    $student_id = $_SESSION['user_id'];
    
    if (!$event_id) {
        die(json_encode(['success' => false, 'error' => 'Invalid event ID']));
    }
    
    switch ($action) {
        case 'register':
            // Check if already registered
            $check = $conn->query("SELECT id FROM event_registrations WHERE event_id = $event_id AND student_id = $student_id");
            
            if ($check->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Already registered for this event']);
                exit;
            }
            
            // Register student
            $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, student_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $event_id, $student_id);
            
            if ($stmt->execute()) {
                // Create result record
                $result_stmt = $conn->prepare("INSERT INTO event_results (event_id, student_id, result_status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE result_status = 'pending'");
                $result_stmt->bind_param("ii", $event_id, $student_id);
                $result_stmt->execute();
                $result_stmt->close();
                
                echo json_encode(['success' => true, 'message' => 'Successfully registered for event']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error registering: ' . $conn->error]);
            }
            $stmt->close();
            break;
            
        case 'unregister':
            $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $event_id, $student_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Registration cancelled']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error cancelling registration']);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
