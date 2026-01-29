<?php
require 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'submissions' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
