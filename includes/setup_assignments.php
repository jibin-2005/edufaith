<?php
/**
 * Assignments Table Setup
 * Creates the assignments table if it doesn't exist
 */

require 'db.php';

echo "Setting up Assignments table...\n\n";

$sql = "CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    class_id INT DEFAULT NULL,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Assignments table created/verified successfully\n";
    
    // Check if table has data
    $check = $conn->query("SELECT COUNT(*) as count FROM assignments");
    $row = $check->fetch_assoc();
    echo "✓ Current assignments count: " . $row['count'] . "\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

$conn->close();
?>
