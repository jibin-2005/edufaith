<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// DROP and RECREATE for a perfectly clean slate
$conn->query("DROP DATABASE IF EXISTS sunday_school_db");

// Create database
$sql = "CREATE DATABASE sunday_school_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    die("Error creating database: " . $conn->error . "\n");
}

$conn->select_db("sunday_school_db");

// Create table with indexes and unique constraints
$sql = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent') DEFAULT 'student',
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created with optimized schema.\n";
} else {
    die("Error creating table: " . $conn->error . "\n");
}

// Insert Standardized Users
$pass = password_hash('password123', PASSWORD_DEFAULT);
$users = [
    ['Jibin Thomas', 'jibinthomas1762005@gmail.com', 'admin'],
    ['Rev. Anderson', 'admin@test.com', 'admin'],
    ['Mrs. Thompson', 'teacher@test.com', 'teacher'],
    ['Sarah Miller', 'student@test.com', 'student'],
    ['Mr. Johnson', 'parent@test.com', 'parent']
];

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
foreach ($users as $u) {
    $stmt->bind_param("ssss", $u[0], $u[1], $pass, $u[2]);
    if ($stmt->execute()) {
        echo "User added: {$u[0]} ({$u[2]})\n";
    } else {
        echo "Error adding {$u[0]}: " . $stmt->error . "\n";
    }
}
$stmt->close();

$conn->close();
?>
