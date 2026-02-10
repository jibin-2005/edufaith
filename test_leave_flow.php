<?php
require 'includes/db.php';

function logMsg($msg) { echo "[LOG] $msg <br>"; }

// 1. Find a Teacher and their Class
logMsg("Finding Teacher and Class...");
$sql_teacher = "SELECT u.id as teacher_id, c.id as class_id, c.class_name 
                FROM users u 
                JOIN classes c ON c.teacher_id = u.id 
                WHERE u.role = 'teacher' LIMIT 1";
$res_t = $conn->query($sql_teacher);

if ($res_t->num_rows == 0) {
    die("[ERROR] No teacher with a class found. Please assign a teacher to a class first.");
}
$teacher_data = $res_t->fetch_assoc();
$teacher_id = $teacher_data['teacher_id'];
$class_id = $teacher_data['class_id'];
logMsg("Found Teacher ID: $teacher_id (Class: {$teacher_data['class_name']})");

// 2. Find a Student in that Class
logMsg("Finding Student in Class $class_id...");
$sql_student = "SELECT id FROM users WHERE role='student' AND class_id = $class_id LIMIT 1";
$res_s = $conn->query($sql_student);

if ($res_s->num_rows == 0) {
    // Try to find ANY student and assign them temp
    logMsg("No student found in class. Finding ANY student...");
    $res_s = $conn->query("SELECT id FROM users WHERE role='student' LIMIT 1");
    if ($res_s->num_rows == 0) die("[ERROR] No students exist in DB.");
}
$student_id = $res_s->fetch_assoc()['id'];
logMsg("Using Student ID: $student_id");

// 3. Create OR Get Leave Request
logMsg("Checking for existing Leave Request...");
$leave_date = date('Y-m-d', strtotime('next sunday'));
$reason = "Test Leave from Debug Script";

$check_exist = $conn->query("SELECT id FROM leave_requests WHERE student_id = $student_id AND leave_date = '$leave_date'");
if ($check_exist->num_rows > 0) {
    $request_id = $check_exist->fetch_assoc()['id'];
    logMsg("Existing Leave Request Found! ID: $request_id");
} else {
    logMsg("Inserting Test Leave Request...");
    $stmt = $conn->prepare("INSERT INTO leave_requests (student_id, class_id, leave_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $student_id, $class_id, $leave_date, $reason);

    if (!$stmt->execute()) {
        die("[ERROR] Insert Failed: " . $stmt->error);
    }
    $request_id = $stmt->insert_id;
    logMsg("Leave Request Created! ID: $request_id");
}

// 4. Simulate Approval (Logic from approve_leave_process.php)
logMsg("Simulating Approval by Teacher $teacher_id...");

$status = 'approved';
$update_stmt = $conn->prepare("UPDATE leave_requests SET status = ?, reviewed_by = ? WHERE id = ?");
$update_stmt->bind_param("sii", $status, $teacher_id, $request_id);

if ($update_stmt->execute()) {
    logMsg("Update Query Executed Successfully. Rows affected: " . $update_stmt->affected_rows);
    
    // Check if it actually updated
    $check = $conn->query("SELECT status, reviewed_by FROM leave_requests WHERE id = $request_id");
    $row = $check->fetch_assoc();
    logMsg("Verification: Status = {$row['status']}, Reviewed By = {$row['reviewed_by']}");
    
    if ($row['status'] == 'approved' && $row['reviewed_by'] == $teacher_id) {
        echo "<h3 style='color:green'>SUCCESS: Leave Approval Flow Works!</h3>";
    } else {
        echo "<h3 style='color:red'>FAILURE: Update query ran but data didn't change?</h3>";
    }
} else {
    echo "<h3 style='color:red'>FAILURE: Update Query Failed: " . $update_stmt->error . "</h3>";
}
?>
