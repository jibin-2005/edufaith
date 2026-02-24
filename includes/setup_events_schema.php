<?php
/**
 * Events Module Enhancement - Database Schema Setup
 * Adds support for:
 * - Student event registrations
 * - Teacher assignments to events
 * - Event results/outcomes
 */

require 'db.php';

$tables = [
    "event_registrations" => "CREATE TABLE IF NOT EXISTS event_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        student_id INT NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attendance_status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_event_student (event_id, student_id)
    )",
    
    "event_teachers" => "CREATE TABLE IF NOT EXISTS event_teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        teacher_id INT NOT NULL,
        role VARCHAR(50) DEFAULT 'coordinator',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_event_teacher (event_id, teacher_id)
    )",
    
    "event_results" => "CREATE TABLE IF NOT EXISTS event_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        student_id INT NOT NULL,
        marks INT,
        remarks TEXT,
        result_status ENUM('pending', 'published') DEFAULT 'pending',
        published_at TIMESTAMP NULL,
        published_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (published_by) REFERENCES users(id),
        UNIQUE KEY unique_event_student_result (event_id, student_id)
    )"
];

// Create tables
foreach ($tables as $name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ Table '$name' created/verified successfully.\n";
    } else {
        echo "✗ Error with table '$name': " . $conn->error . "\n";
    }
}

// Add status column to events table if it doesn't exist
$check_status = $conn->query("SHOW COLUMNS FROM events LIKE 'status'");
if ($check_status->num_rows === 0) {
    if ($conn->query("ALTER TABLE events ADD COLUMN status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming' AFTER created_at")) {
        echo "✓ Added 'status' column to events table.\n";
    } else {
        echo "✗ Error adding 'status' column: " . $conn->error . "\n";
    }
}

// Add is_published column to events table if it doesn't exist
$check_published = $conn->query("SHOW COLUMNS FROM events LIKE 'is_results_published'");
if ($check_published->num_rows === 0) {
    if ($conn->query("ALTER TABLE events ADD COLUMN is_results_published BOOLEAN DEFAULT FALSE AFTER status")) {
        echo "✓ Added 'is_results_published' column to events table.\n";
    } else {
        echo "✗ Error adding 'is_results_published' column: " . $conn->error . "\n";
    }
}

echo "\n✓ Database schema setup completed successfully!\n";
?>
