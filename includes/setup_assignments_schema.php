<?php
/**
 * Assignment Submissions Schema Setup
 * Creates tables for tracking student assignment submissions (PDF only)
 */

require 'db.php';

// Create assignment_submissions table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_file VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
    grade INT,
    feedback TEXT,
    graded_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    INDEX idx_student (student_id),
    INDEX idx_assignment (assignment_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'assignment_submissions' created/verified successfully.\n";
} else {
    echo "✗ Error with table 'assignment_submissions': " . $conn->error . "\n";
}

// Add submission_required column to assignments if it doesn't exist
$check = $conn->query("SHOW COLUMNS FROM assignments LIKE 'submission_required'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE assignments ADD COLUMN submission_required BOOLEAN DEFAULT FALSE AFTER description")) {
        echo "✓ Added 'submission_required' column to assignments table.\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

// Create uploads directory for submissions if it doesn't exist
$uploads_dir = __DIR__ . '/../uploads/assignments/submissions';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
    echo "✓ Created submissions upload directory.\n";
}

echo "\n✓ Assignment submission schema setup completed successfully!\n";
?>
