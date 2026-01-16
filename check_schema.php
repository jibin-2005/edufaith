<?php
require 'includes/db.php';
$output = "Table: assignments\n";
$res = $conn->query("DESCRIBE assignments");
while($row = $res->fetch_assoc()) {
    $output .= "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}
$res = $conn->query("SHOW CREATE TABLE assignments");
$row = $res->fetch_assoc();
$output .= "\nCreate Table:\n" . $row['Create Table'] . "\n";
file_put_contents('schema_output.txt', $output);
echo "Output written to schema_output.txt\n";
?>
