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

// Create users table with indexes and commonly-used profile fields
$sql = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'student',
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    firebase_uid VARCHAR(128) NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    class_id INT NULL,
    profile_picture VARCHAR(255) NULL,
    dob DATE NULL,
    phone VARCHAR(20) NULL,
    baptism_date DATE NULL,
    holy_communion_date DATE NULL,
    address TEXT NULL,
    INDEX idx_role (role),
    INDEX idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created with optimized schema.\n";
} else {
    die("Error creating table: " . $conn->error . "\n");
}

// Create classes table
$sql = "CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    teacher_id INT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX fk_classes_teacher_id_set_null (teacher_id),
    CONSTRAINT fk_classes_teacher_id_set_null FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'classes' created successfully.\n";
} else {
    die("Error creating classes table: " . $conn->error . "\n");
}

// Create attendance table
$sql = "CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent','Late','Leave Approved','Pending Leave') NOT NULL,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_attendance (student_id, date),
    INDEX student_id (student_id),
    INDEX class_id (class_id),
    INDEX teacher_id (teacher_id),
    CONSTRAINT fk_attendance_student_id_cascade FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance' created successfully.\n";
} else {
    die("Error creating attendance table: " . $conn->error . "\n");
}

// Create leave_requests table
$sql = "CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    leave_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_date (student_id, leave_date),
    INDEX class_id (class_id),
    INDEX fk_leave_requests_reviewed_by_set_null (reviewed_by),
    CONSTRAINT fk_leave_requests_reviewed_by_set_null FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_leave_requests_student_id_cascade FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_leave_requests_class_id FOREIGN KEY (class_id) REFERENCES classes(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'leave_requests' created successfully.\n";
} else {
    die("Error creating leave_requests table: " . $conn->error . "\n");
}

// Create messages table
$sql = "CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'messages' created successfully.\n";
} else {
    die("Error creating messages table: " . $conn->error . "\n");
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
