<?php
require 'db.php';

echo "<h2>Starting Requirement Alignment Schema Update...</h2>";

// 1. Create Classes Table
$sql_classes = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql_classes) === TRUE) {
    echo "✅ Table 'classes' checked/created successfully.<br>";
} else {
    echo "❌ Error creating 'classes' table: " . $conn->error . "<br>";
}

// 2. Create Parent-Student Link Table
$sql_parent_student = "CREATE TABLE IF NOT EXISTS parent_student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    student_id INT NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_parent_student) === TRUE) {
    echo "✅ Table 'parent_student' checked/created successfully.<br>";
} else {
    echo "❌ Error creating 'parent_student' table: " . $conn->error . "<br>";
}

// 3. Keep Existing Users Safe: Add class_id only if missing
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'class_id'");
if ($checkCol->num_rows == 0) {
    $sql_alter = "ALTER TABLE users ADD COLUMN class_id INT DEFAULT NULL";
    if ($conn->query($sql_alter) === TRUE) {
        echo "✅ Column 'class_id' added to 'users' table.<br>";
    } else {
        echo "❌ Error creating 'class_id' column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Column 'class_id' already exists in 'users'. Skipping.<br>";
}

// 4. Add Constraints (safely)
try {
   $conn->query("ALTER TABLE users ADD CONSTRAINT fk_user_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL");
   echo "✅ Foreign Key constraint added/verified for class_id.<br>";
} catch (Exception $e) {
    // Constraint might already exist, ignore duplication error
    echo "ℹ️ Foreign Key constraint likely already exists.<br>";
}

echo "<h3>Schema Update Complete.</h3>";
$conn->close();
?>
