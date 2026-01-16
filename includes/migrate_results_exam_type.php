<?php
/**
 * Results Table Migration - Add Exam Type Support
 * This script updates the results table to support two separate exams (exam_1, exam_2)
 */

require 'db.php';

echo "Starting Results Table Migration...\n\n";

$sqls = [
    // 1. Add exam_type column if it doesn't exist
    "ALTER TABLE results 
     ADD COLUMN IF NOT EXISTS exam_type ENUM('exam_1', 'exam_2') NOT NULL DEFAULT 'exam_1' 
     AFTER student_id",

    // 2. Drop old unique constraint on student_id (if exists)
    "ALTER TABLE results DROP INDEX IF EXISTS student_id",
    
    // 3. Add new unique constraint on (student_id, exam_type)
    "ALTER TABLE results 
     ADD UNIQUE KEY unique_student_exam (student_id, exam_type)",

    // 4. Ensure updated_by column exists
    "ALTER TABLE results 
     ADD COLUMN IF NOT EXISTS updated_by INT DEFAULT NULL 
     AFTER marks",
    
    // 5. Ensure updated_at column exists with proper default
    "ALTER TABLE results 
     MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($sqls as $index => $sql) {
    echo "Executing step " . ($index + 1) . "...\n";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Success\n\n";
    } else {
        // Some errors are expected (like dropping non-existent indexes)
        if (strpos($conn->error, "check that it exists") === false && 
            strpos($conn->error, "Duplicate column") === false) {
            echo "⚠ Warning: " . $conn->error . "\n\n";
        } else {
            echo "✓ Already applied or not needed\n\n";
        }
    }
}

echo "Migration completed!\n";
echo "Results table now supports exam_type (exam_1, exam_2)\n";

$conn->close();
?>
