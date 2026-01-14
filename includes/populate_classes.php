<?php
require 'db.php';

echo "Creating classes 1-12...\n\n";

// Create classes table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_table)) {
    echo "✓ Classes table ready\n";
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
    exit;
}

// Insert classes 1-12
$classes = [
    'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6',
    'Class 7', 'Class 8', 'Class 9', 'Class 10', 'Class 11', 'Class 12'
];

$success_count = 0;
foreach ($classes as $class_name) {
    // Check if class already exists
    $check = $conn->prepare("SELECT id FROM classes WHERE class_name = ?");
    $check->bind_param("s", $class_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "✓ Already exists: $class_name\n";
        $success_count++;
    } else {
        $stmt = $conn->prepare("INSERT INTO classes (class_name, status) VALUES (?, 'active')");
        $stmt->bind_param("s", $class_name);
        
        if ($stmt->execute()) {
            echo "✓ Created: $class_name\n";
            $success_count++;
        } else {
            echo "✗ Error creating $class_name: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
    $check->close();
}

echo "\n========================================\n";
echo "Successfully created $success_count classes!\n";
echo "========================================\n";

// Display all classes
echo "\nCurrent classes in database:\n";
$result = $conn->query("SELECT id, class_name, status FROM classes ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "  ID: {$row['id']} - {$row['class_name']} ({$row['status']})\n";
}

$conn->close();
?>
