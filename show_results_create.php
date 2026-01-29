<?php
require 'includes/db.php';
$res = $conn->query("SHOW CREATE TABLE results");
$row = $res->fetch_array();
echo $row[1];
?>
