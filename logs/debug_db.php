<?php
require 'db.php';

echo "--- Database Structure ---\n";
$result = $conn->query("DESCRIBE users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error describing users table: " . $conn->error . "\n";
}

echo "\n--- User Data (JSON) ---\n";
$result = $conn->query("SELECT * FROM users LIMIT 20");
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    file_put_contents('db_dump.txt', json_encode($users, JSON_PRETTY_PRINT));
    echo "Database dump written to db_dump.txt\n";
} else {
    echo "Error selecting from users table: " . $conn->error . "\n";
}

$conn->close();
?>
