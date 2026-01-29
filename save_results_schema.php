<?php
require 'includes/db.php';
$res = $conn->query("SHOW CREATE TABLE results");
$row = $res->fetch_array();
file_put_contents('results_full_schema.txt', $row[1]);
?>
