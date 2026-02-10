<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent','Late','Leave Approved','Pending Leave') NOT NULL,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_attendance (student_id, date),
    INDEX student_id (student_id),
    INDEX class_id (class_id),
    INDEX teacher_id (teacher_id),
    CONSTRAINT fk_attendance_student_id_cascade FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance' created successfully (or already exists).";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
