<?php
/**
 * Fix Assignments Table - Add missing assigned_by column
 */

require 'db.php';

echo "Fixing Assignments table...\n\n";

// Add assigned_by column if it doesn't exist
$sql = "ALTER TABLE assignments 
        ADD COLUMN IF NOT EXISTS assigned_by INT NOT NULL AFTER class_id";

if ($conn->query($sql) === TRUE) {
    echo "✓ Added assigned_by column successfully\n";
} else {
    // Column might already exist
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "✓ Column assigned_by already exists\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// Verify table structure
echo "\nVerifying table structure:\n";
$result = $conn->query("DESCRIBE assignments");
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
