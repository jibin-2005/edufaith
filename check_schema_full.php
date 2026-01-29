<?php
require 'includes/db.php';
$tables = $conn->query("SHOW TABLES");
while ($row = $tables->fetch_array()) {
    echo "Table: " . $row[0] . "\n";
    $cols = $conn->query("SHOW COLUMNS FROM " . $row[0]);
    while ($col = $cols->fetch_assoc()) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
