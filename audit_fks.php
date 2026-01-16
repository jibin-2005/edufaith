<?php
require 'includes/db.php';
$sql = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'users' 
        AND TABLE_SCHEMA = 'sunday_school_db'";
$res = $conn->query($sql);
if (!$res) {
    die($conn->error);
}
while($r = $res->fetch_assoc()) {
    echo "{$r['TABLE_NAME']}.{$r['COLUMN_NAME']} -> {$r['REFERENCED_TABLE_NAME']}.{$r['REFERENCED_COLUMN_NAME']} ({$r['CONSTRAINT_NAME']})\n";
}
?>
