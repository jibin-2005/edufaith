<?php
/**
 * Event Results Entry Handler - Only assigned teachers can save results
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require '../includes/db.php';
require '../includes/validation_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $marks = $_POST['marks'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    $placement = $_POST['placement'] ?? null; // expected values: 'first','second','third' or null
    $teacher_id = $_SESSION['user_id'];
    
    // Only teachers can save results
    if ($_SESSION['role'] !== 'teacher') {
        die(json_encode(['success' => false, 'error' => 'Only teachers can enter results']));
    }
    
    if (!$event_id || !$student_id) {
        die(json_encode(['success' => false, 'error' => 'Invalid data']));
    }
    
    // Verify event exists and result entry window is open
    $event_stmt = $conn->prepare("SELECT id, event_date, section_id, is_results_published FROM events WHERE id = ?");
    $event_stmt->bind_param("i", $event_id);
    $event_stmt->execute();
    $event_row = $event_stmt->get_result()->fetch_assoc();
    $event_stmt->close();

    if (!$event_row) {
        die(json_encode(['success' => false, 'error' => 'Event not found']));
    }

    if (!empty($event_row['is_results_published'])) {
        die(json_encode(['success' => false, 'error' => 'Results are already published for this event']));
    }

    if (strtotime($event_row['event_date']) > time()) {
        die(json_encode(['success' => false, 'error' => 'Marks can be submitted only on or after the event date']));
    }
    
    // Verify teacher is assigned as coordinator or co-coordinator
    $teacher_check = $conn->prepare("SELECT id FROM event_teachers WHERE event_id = ? AND teacher_id = ? AND role IN ('coordinator', 'co-coordinator')");
    $teacher_check->bind_param("ii", $event_id, $teacher_id);
    $teacher_check->execute();
    if ($teacher_check->get_result()->num_rows === 0) {
        die(json_encode(['success' => false, 'error' => 'You are not assigned to manage results for this event']));
    }
    $teacher_check->close();
    
    // Verify student is registered
    $reg_check = $conn->query("SELECT id FROM event_registrations WHERE event_id = $event_id AND student_id = $student_id");
    if (!$reg_check || $reg_check->num_rows === 0) {
        die(json_encode(['success' => false, 'error' => 'Student not registered for event']));
    }

    // Verify student belongs to event section
    $student_section = Validator::getStudentSection($conn, $student_id);
    if (!empty($event_row['section_id']) && intval($event_row['section_id']) !== intval($student_section)) {
        die(json_encode(['success' => false, 'error' => 'Student section does not match event section']));
    }
    
    // Validate marks if provided
    if ($marks !== null && $marks !== '') {
        $marks = intval($marks);
        if ($marks < 0 || $marks > 100) {
            die(json_encode(['success' => false, 'error' => 'Marks must be between 0 and 100']));
        }
    } else {
        $marks = null;
    }

    // Validate placement if provided
    $allowedPlacements = ['first', 'second', 'third', null, ''];
    if ($placement !== null && $placement !== '') {
        $placement = strtolower(trim($placement));
        if (!in_array($placement, ['first', 'second', 'third'])) {
            die(json_encode(['success' => false, 'error' => 'Invalid placement value']));
        }
    } else {
        $placement = null;
    }

    // Ensure event_results table has placement column (backwards-compatible)
    $col_check = $conn->query("SHOW COLUMNS FROM event_results LIKE 'placement'");
    if ($col_check && $col_check->num_rows === 0) {
        // Try to add the column
        $conn->query("ALTER TABLE event_results ADD COLUMN placement VARCHAR(20) DEFAULT NULL");
    }
    
    // Insert or update result
    $stmt = $conn->prepare("INSERT INTO event_results (event_id, student_id, marks, remarks, placement, result_status) 
                            VALUES (?, ?, ?, ?, ?, 'pending') 
                            ON DUPLICATE KEY UPDATE marks = VALUES(marks), remarks = VALUES(remarks), placement = VALUES(placement)");
    $stmt->bind_param("iisss", $event_id, $student_id, $marks, $remarks, $placement);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Result saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error saving result: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>


