<?php
require 'includes/db.php';

echo "=== DATABASE VERIFICATION ===\n\n";

// Show all tables
echo "Tables in database:\n";
$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

echo "\n=== USERS TABLE STRUCTURE ===\n";
$cols = $conn->query('DESCRIBE users');
while($col = $cols->fetch_assoc()) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== PARENT_STUDENT TABLE ===\n";
$ps = $conn->query('DESCRIBE parent_student');
if ($ps) {
    while($col = $ps->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "Table not found\n";
}

echo "\n=== ATTENDANCE TABLE ===\n";
$att = $conn->query('DESCRIBE attendance');
if ($att) {
    while($col = $att->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "Table not found\n";
}

$conn->close();
?>
