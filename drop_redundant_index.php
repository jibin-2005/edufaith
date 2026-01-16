<?php
require 'includes/db.php';

echo "Attempting to drop redundant unique constraint 'unique_student' from results table...\n";

$sql = "ALTER TABLE results DROP INDEX unique_student";

if ($conn->query($sql) === TRUE) {
    echo "✓ Redundant constraint 'unique_student' dropped successfully.\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
    echo "Note: If the error says 'check that column/key exists', it might have already been dropped.\n";
}

$conn->close();
?>
