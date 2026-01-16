<?php
require 'db.php';

$sqls = [
    // 1. Create leave_requests table
    "CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        leave_date DATE NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (reviewed_by) REFERENCES users(id),
        UNIQUE KEY unique_student_date (student_id, leave_date)
    )",

    // 2. Update attendance table status column
    "ALTER TABLE attendance 
     MODIFY COLUMN status ENUM('Present', 'Absent', 'Late', 'Leave Approved', 'Pending Leave') NOT NULL"
];

foreach ($sqls as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully executed: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error executing: " . $conn->error . "\n";
    }
}

$conn->close();
?>
