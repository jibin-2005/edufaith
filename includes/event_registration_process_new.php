<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
require 'validation_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $event_id = intval($_POST['event_id'] ?? 0);

    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }

    // Get event details including section
    $stmt_event = $conn->prepare("SELECT id, title, section_id, event_date FROM events WHERE id = ?");
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $event = $stmt_event->get_result()->fetch_assoc();
    $stmt_event->close();

    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }

    // Get student's section
    $student_section = Validator::getStudentSection($conn, $student_id);

    if (!$student_section) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to any class/section. Please contact admin.']);
        exit;
    }

    // CRITICAL VALIDATION: Check section match
    if ($student_section != $event['section_id']) {
        // Get section names for better error message
        $stmt_sections = $conn->prepare("SELECT 
            (SELECT section_name FROM sections WHERE id = ?) as student_section,
            (SELECT section_name FROM sections WHERE id = ?) as event_section");
        $stmt_sections->bind_param("ii", $student_section, $event['section_id']);
        $stmt_sections->execute();
        $section_info = $stmt_sections->get_result()->fetch_assoc();
        $stmt_sections->close();

        echo json_encode([
            'success' => false, 
            'message' => "Cross-section registration not allowed. This event is for " . 
                        $section_info['event_section'] . " section, but you belong to " . 
                        $section_info['student_section'] . " section."
        ]);
        exit;
    }

    // Check if already registered
    if (Validator::isStudentRegisteredForEvent($conn, $student_id, $event_id)) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this event']);
        exit;
    }

    // Check if event date has passed
    if (strtotime($event['event_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Registration closed. Event date has passed.']);
        exit;
    }

    // Register student
    $stmt_register = $conn->prepare("INSERT INTO event_registrations (event_id, student_id, registration_date) VALUES (?, ?, NOW())");
    $stmt_register->bind_param("ii", $event_id, $student_id);

    if ($stmt_register->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully registered for ' . htmlspecialchars($event['title'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
    }
    $stmt_register->close();
    $conn->close();
}
?>
