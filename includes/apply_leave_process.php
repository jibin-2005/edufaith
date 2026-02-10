<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}
require 'db.php';
require 'validation_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $leave_date = $_POST['leave_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    // 1. Basic validation
    if (empty($leave_date) || empty($reason)) {
        header("Location: ../user/attendance_student.php?leave_error=empty_fields");
        exit;
    }

    // 2. Future Date Validation (New Requirement)
    $valDate = Validator::validateDate($leave_date, 'Leave Date', 'future_only');
    if ($valDate !== true) {
         // Pass error message back? For now simple redirect with error code
         // Ideally modify attendance_student.php to show specific errors
         header("Location: ../user/attendance_student.php?leave_error=past_date"); 
         exit;
    }

    // 3. Sunday validation
    $weekday = date('w', strtotime($leave_date));
    if ($weekday != 0) {
        header("Location: ../user/attendance_student.php?leave_error=not_sunday");
        exit;
    }

    // 3. Prevent duplicate requests
    $stmt_check = $conn->prepare("SELECT id FROM leave_requests WHERE student_id = ? AND leave_date = ?");
    $stmt_check->bind_param("is", $student_id, $leave_date);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: ../user/attendance_student.php?leave_error=duplicate");
        exit;
    }

    // 4. Fetch class_id for the student
    $stmt_class = $conn->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt_class->bind_param("i", $student_id);
    $stmt_class->execute();
    $class_id = $stmt_class->get_result()->fetch_assoc()['class_id'] ?? 0;

    // 5. Insert leave request
    $stmt_insert = $conn->prepare("INSERT INTO leave_requests (student_id, class_id, leave_date, reason) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $student_id, $class_id, $leave_date, $reason);

    if ($stmt_insert->execute()) {
        // 6. Mark attendance as Pending Leave for the date (upsert)
        $teacher_id = 0;
        $stmt_teacher = $conn->prepare("SELECT teacher_id FROM classes WHERE id = ?");
        $stmt_teacher->bind_param("i", $class_id);
        $stmt_teacher->execute();
        $teacher_row = $stmt_teacher->get_result()->fetch_assoc();
        if ($teacher_row && !empty($teacher_row['teacher_id'])) {
            $teacher_id = (int)$teacher_row['teacher_id'];
        }
        $stmt_teacher->close();

        $stmt_att = $conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, date, status)
                                    VALUES (?, ?, ?, ?, 'Pending Leave')
                                    ON DUPLICATE KEY UPDATE status = 'Pending Leave', class_id = VALUES(class_id), teacher_id = VALUES(teacher_id)");
        $stmt_att->bind_param("iiis", $student_id, $class_id, $teacher_id, $leave_date);
        $stmt_att->execute();
        $stmt_att->close();

        header("Location: ../user/attendance_student.php?leave_msg=success");
    } else {
        header("Location: ../user/attendance_student.php?leave_error=db_error");
    }
    
    $stmt_insert->close();
    $conn->close();
    exit;
}
?>
