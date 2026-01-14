<?php
require 'db.php';

$sqlFile = '../sql/update_schema.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found.");
}

$sql = file_get_contents($sqlFile);

// Execute multi_query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check if there are more results
        if ($conn->more_results()) {
           // printf("-------------\n");
        }
    } while ($conn->next_result());
    echo "Database updated successfully.";
} else {
    echo "Error updating database: " . $conn->error;
}

$conn->close();
?>
