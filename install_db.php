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

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS sunday_school_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    die("Error creating database: " . $conn->error . "\n");
}

$conn->select_db("sunday_school_db");

// Create table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'student',
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table users created successfully\n";
} else {
    die("Error creating table: " . $conn->error . "\n");
}

// Insert User (Jibin)
$my_email = 'jibinthomas1762005@gmail.com';
$check = $conn->query("SELECT id FROM users WHERE email='$my_email'");
if ($check->num_rows == 0) {
    $pass = password_hash('password123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('Jibin Thomas', '$my_email', '$pass', 'admin')";
    if ($conn->query($sql) === TRUE) {
        echo "User Jibin (Admin) added successfully\n";
    } else {
        echo "Error adding user: " . $conn->error . "\n";
    }
} else {
    echo "User Jibin already exists\n";
}

// Insert Dummy Users from setup file logic (simplified)
$dummies = [
    ['Rev. Anderson', 'admin@test.com', 'admin'],
    ['Mrs. Thompson', 'teacher@test.com', 'teacher'],
    ['Sarah Miller', 'student@test.com', 'student'],
    ['Mr. Johnson', 'parent@test.com', 'parent']
];

foreach ($dummies as $user) {
    $email = $user[1];
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows == 0) {
        $pass = password_hash('password123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role) VALUES ('$user[0]', '$email', '$pass', '$user[2]')";
        if ($conn->query($sql) === TRUE) {
            echo "User $user[0] added.\n";
        }
    }
}

$conn->close();
?>
