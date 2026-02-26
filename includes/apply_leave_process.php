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

    // 2. Future Date Validation
    $valDate = Validator::validateDate($leave_date, 'Leave Date', 'future_only');
    if ($valDate !== true) {
         header("Location: ../user/attendance_student.php?leave_error=past_date"); 
         exit;
    }

    // 3. Maximum advance days (30 days)
    $leave_timestamp = strtotime($leave_date);
    $max_advance_date = strtotime('+30 days');
    if ($leave_timestamp > $max_advance_date) {
        header("Location: ../user/leave_student.php?error=too_far_advance");
        exit;
    }

    // 4. Minimum notice period (at least 1 day before)
    $tomorrow = strtotime('+1 day');
    if ($leave_timestamp < $tomorrow) {
        header("Location: ../user/leave_student.php?error=insufficient_notice");
        exit;
    }

    // 5. Sunday validation
    $weekday = date('w', strtotime($leave_date));
    if ($weekday != 0) {
        header("Location: ../user/attendance_student.php?leave_error=not_sunday");
        exit;
    }

    // 6. Maximum pending leaves (3)
    $pending_count = Validator::countPendingLeaves($conn, $student_id);
    if ($pending_count >= 3) {
        header("Location: ../user/leave_student.php?error=max_pending_leaves");
        exit;
    }

    // 7. Maximum approved leaves in term (4 in last 3 months)
    $approved_count = Validator::countApprovedLeavesInTerm($conn, $student_id);
    if ($approved_count >= 4) {
        header("Location: ../user/leave_student.php?error=max_term_leaves");
        exit;
    }

    // 8. Check for consecutive leaves (max 2 consecutive Sundays)
    $prev_sunday = date('Y-m-d', strtotime($leave_date . ' -7 days'));
    $next_sunday = date('Y-m-d', strtotime($leave_date . ' +7 days'));
    
    $stmt_consecutive = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests 
                                        WHERE student_id = ? 
                                        AND status IN ('pending', 'approved')
                                        AND (leave_date = ? OR leave_date = ?)");
    $stmt_consecutive->bind_param("iss", $student_id, $prev_sunday, $next_sunday);
    $stmt_consecutive->execute();
    $consecutive_result = $stmt_consecutive->get_result()->fetch_assoc();
    $stmt_consecutive->close();
    
    if (($consecutive_result['count'] ?? 0) >= 1) {
        // Check if there's already a leave on both sides
        $stmt_both = $conn->prepare("SELECT leave_date FROM leave_requests 
                                     WHERE student_id = ? 
                                     AND status IN ('pending', 'approved')
                                     AND leave_date IN (?, ?)");
        $stmt_both->bind_param("iss", $student_id, $prev_sunday, $next_sunday);
        $stmt_both->execute();
        $both_result = $stmt_both->get_result();
        
        if ($both_result->num_rows >= 1) {
            header("Location: ../user/leave_student.php?error=consecutive_leaves");
            exit;
        }
        $stmt_both->close();
    }

    // 9. Prevent duplicate requests
    $stmt_check = $conn->prepare("SELECT id FROM leave_requests WHERE student_id = ? AND leave_date = ?");
    $stmt_check->bind_param("is", $student_id, $leave_date);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        header("Location: ../user/attendance_student.php?leave_error=duplicate");
        exit;
    }
    $stmt_check->close();

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
