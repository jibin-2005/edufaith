<?php
require 'includes/db.php';

$fixes = [
    // [Table, Column, TargetRule]
    ['attendance', 'marked_by', 'SET NULL'],
    ['attendance', 'student_id', 'CASCADE'],
    ['announcements', 'created_by', 'SET NULL'],
    ['events', 'created_by', 'SET NULL'],
    ['assignments', 'assigned_by', 'SET NULL'],
    ['results', 'updated_by', 'SET NULL'],
    ['results', 'student_id', 'CASCADE'],
    ['classes', 'teacher_id', 'SET NULL']
];

foreach ($fixes as $fix) {
    list($table, $column, $rule) = $fix;
    
    // Find constraint name
    $sql = "SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = '$table' 
            AND COLUMN_NAME = '$column' 
            AND REFERENCED_TABLE_NAME = 'users' 
            AND TABLE_SCHEMA = 'sunday_school_db'";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $cname = $r['CONSTRAINT_NAME'];
            echo "Fixing $table.$column (Constraint: $cname) -> ON DELETE $rule...\n";
            
            // 1. Drop old constraint
            $conn->query("ALTER TABLE $table DROP FOREIGN KEY $cname");
            
            // 2. Add new constraint
            // First ensure the column allows NULL if target rule is SET NULL
            if ($rule === 'SET NULL') {
                $conn->query("ALTER TABLE $table MODIFY COLUMN $column INT NULL");
            }
            
            $newSql = "ALTER TABLE $table ADD CONSTRAINT {$table}_{$column}_fk 
                       FOREIGN KEY ($column) REFERENCES users(id) ON DELETE $rule";
            
            if ($conn->query($newSql)) {
                echo "Successfully updated $table.$column\n";
            } else {
                echo "Error updating $table.$column: " . $conn->error . "\n";
            }
        }
    } else {
        echo "No constraint found for $table.$column referencing users. Adding one...\n";
        if ($rule === 'SET NULL') {
            $conn->query("ALTER TABLE $table MODIFY COLUMN $column INT NULL");
        }
        $newSql = "ALTER TABLE $table ADD CONSTRAINT {$table}_{$column}_fk 
                   FOREIGN KEY ($column) REFERENCES users(id) ON DELETE $rule";
        $conn->query($newSql);
    }
}

$conn->close();
?>
