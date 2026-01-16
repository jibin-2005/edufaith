<?php
require 'includes/db.php';
$output = "Table: results\n";
$res = $conn->query("DESCRIBE results");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $output .= "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Key: " . $row['Key'] . "\n";
    }
} else {
    $output .= "Error: " . $conn->error . "\n";
}

$res = $conn->query("SHOW CREATE TABLE results");
if ($res) {
    $row = $res->fetch_assoc();
    $output .= "\nCreate Table:\n" . $row['Create Table'] . "\n";
}

file_put_contents('results_schema.txt', $output);
echo "Output written to results_schema.txt\n";
?>
