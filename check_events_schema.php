<?php
require 'includes/db.php';

// Check events table structure
$result = $conn->query('DESCRIBE events');
if($result) {
    echo "Events Table Structure:\n";
    while($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
} else {
    echo "Events Table does not exist or error: " . $conn->error . "\n";
}

// Check if registrations table exists
$result = $conn->query('DESCRIBE event_registrations');
if($result) {
    echo "\nEvent Registrations Table exists:\n";
} else {
    echo "\nEvent Registrations Table does not exist\n";
}

// Check if event_results table exists
$result = $conn->query('DESCRIBE event_results');
if($result) {
    echo "\nEvent Results Table exists:\n";
} else {
    echo "\nEvent Results Table does not exist\n";
}

// Check if event_teachers table exists
$result = $conn->query('DESCRIBE event_teachers');
if($result) {
    echo "\nEvent Teachers Table exists:\n";
} else {
    echo "\nEvent Teachers Table does not exist\n";
}
?>
