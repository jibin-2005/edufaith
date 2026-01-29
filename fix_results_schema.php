<?php
require 'includes/db.php';

$sql = "ALTER TABLE results ADD COLUMN updated_by INT AFTER marks";
if ($conn->query($sql)) {
    echo "Column 'updated_by' added successfully.\n";
    
    // Also add foreign key if possible
    $fk_sql = "ALTER TABLE results ADD CONSTRAINT fk_results_teacher FOREIGN KEY (updated_by) REFERENCES users(id)";
    if ($conn->query($fk_sql)) {
        echo "Foreign key added successfully.\n";
    }
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
