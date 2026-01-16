<?php
require 'includes/db.php';
$sql = "SELECT 
            TABLE_NAME, 
            COLUMN_NAME, 
            CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'users' 
        AND TABLE_SCHEMA = 'sunday_school_db'";
$res = $conn->query($sql);
if (!$res) die($conn->error);

while($r = $res->fetch_assoc()) {
    $tableName = $r['TABLE_NAME'];
    $constraintName = $r['CONSTRAINT_NAME'];
    
    // Get referential side to see DELETE_RULE
    $sqlRule = "SELECT DELETE_RULE 
                FROM information_schema.REFERENTIAL_CONSTRAINTS 
                WHERE CONSTRAINT_NAME = '$constraintName' 
                AND CONSTRAINT_SCHEMA = 'sunday_school_db'";
    $resRule = $conn->query($sqlRule);
    $rule = $resRule ? $resRule->fetch_assoc()['DELETE_RULE'] : 'UNKNOWN';
    
    echo "{$r['TABLE_NAME']}.{$r['COLUMN_NAME']} -> {$r['REFERENCED_TABLE_NAME']}.{$r['REFERENCED_COLUMN_NAME']} | Constraint: {$r['CONSTRAINT_NAME']} | On Delete: $rule\n";
}
?>
