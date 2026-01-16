<?php
require 'db.php';

// Prepare SQL for migration
$sqls = [
    // 1. Create the new attendance table
    "CREATE TABLE IF NOT EXISTS attendance_new (
        attendance_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        teacher_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('Present', 'Absent', 'Late') NOT NULL,
        last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (student_id),
        INDEX (class_id),
        INDEX (teacher_id),
        UNIQUE KEY unique_daily_attendance (student_id, date)
    )",

    // 2. Migrate data if old table exists
    // We'll try to find student_id or user_id. From save_attendance.php it seems to be user_id.
    // Class ID is unknown, setting to 0 for now.
    "INSERT INTO attendance_new (student_id, class_id, teacher_id, date, status, last_updated_at)
     SELECT user_id, 0, marked_by, attendance_date, 
            CASE WHEN LOWER(status) = 'present' THEN 'Present' ELSE 'Absent' END, 
            created_at 
     FROM attendance",

    // 3. Drop old and rename new
    "DROP TABLE attendance",
    "RENAME TABLE attendance_new TO attendance"
];

foreach ($sqls as $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            echo "Success: $sql\n";
        } else {
            echo "Error: " . $conn->error . " for SQL: $sql\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . " for SQL: $sql\n";
    }
}

$conn->close();
?>
