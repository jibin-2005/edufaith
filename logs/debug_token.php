<?php
require 'db.php';

$email = 'jibinthomas1762005@gmail.com'; // user's email
echo "Checking DB status for: $email\n";

$result = $conn->query("SELECT reset_token_hash, reset_token_expires_at, NOW() as web_server_time FROM users WHERE email = '$email'");

if ($row = $result->fetch_assoc()) {
    echo "Stored Hash: " . $row['reset_token_hash'] . "\n";
    echo "Expires At:  " . $row['reset_token_expires_at'] . "\n";
    echo "MySQL NOW(): " . $row['web_server_time'] . "\n";
    
    if ($row['reset_token_expires_at'] > $row['web_server_time']) {
        echo "STATUS: VALID (Time is in future)\n";
    } else {
        echo "STATUS: EXPIRED (Time is in passed)\n";
    }
} else {
    echo "User not found.\n";
}
?>
