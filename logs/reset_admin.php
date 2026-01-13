<?php
require 'db.php';

$email = 'jibinthomas1762005@gmail.com';
$new_pass = 'password123';
$hash = password_hash($new_pass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
$stmt->bind_param("ss", $hash, $email);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Admin password reset successfully for $email\n";
    } else {
        echo "No user found with email $email to reset.\n";
    }
} else {
    echo "Error updating password: " . $conn->error . "\n";
}

$conn->close();
?>
