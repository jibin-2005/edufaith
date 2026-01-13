<?php
require 'db.php';
$result = $conn->query("SELECT id, username, email, role FROM users");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | " . $row['username'] . " | " . $row['email'] . " | " . $row['role'] . "\n";
}
?>
