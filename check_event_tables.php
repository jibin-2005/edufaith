<?php
require 'includes/db.php';

echo "<h2>Checking Event Tables</h2>";

// Check event_registrations table
$check1 = $conn->query("SHOW TABLES LIKE 'event_registrations'");
if ($check1 && $check1->num_rows > 0) {
    echo "<p style='color:green;'>✓ event_registrations table exists</p>";
    $desc1 = $conn->query("DESCRIBE event_registrations");
    echo "<pre>";
    while ($row = $desc1->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ event_registrations table does NOT exist</p>";
}

// Check event_results table
$check2 = $conn->query("SHOW TABLES LIKE 'event_results'");
if ($check2 && $check2->num_rows > 0) {
    echo "<p style='color:green;'>✓ event_results table exists</p>";
    $desc2 = $conn->query("DESCRIBE event_results");
    echo "<pre>";
    while ($row = $desc2->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ event_results table does NOT exist</p>";
}

// Check sections table
$check3 = $conn->query("SHOW TABLES LIKE 'sections'");
if ($check3 && $check3->num_rows > 0) {
    echo "<p style='color:green;'>✓ sections table exists</p>";
    $desc3 = $conn->query("DESCRIBE sections");
    echo "<pre>";
    while ($row = $desc3->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
    
    // Show sections
    $sections = $conn->query("SELECT * FROM sections");
    echo "<h3>Sections:</h3>";
    while ($sec = $sections->fetch_assoc()) {
        echo "<p>" . $sec['id'] . ". " . $sec['section_name'] . " - " . $sec['class_range'] . "</p>";
    }
} else {
    echo "<p style='color:red;'>✗ sections table does NOT exist - RUN SETUP!</p>";
    echo "<p><a href='includes/setup_sections.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Run Sections Setup</a></p>";
}

// Check events table for section_id column
$check4 = $conn->query("SHOW COLUMNS FROM events LIKE 'section_id'");
if ($check4 && $check4->num_rows > 0) {
    echo "<p style='color:green;'>✓ events.section_id column exists</p>";
} else {
    echo "<p style='color:red;'>✗ events.section_id column does NOT exist - RUN SETUP!</p>";
}

// Check events table for published column
$check5 = $conn->query("SHOW COLUMNS FROM events LIKE 'published'");
if ($check5 && $check5->num_rows > 0) {
    echo "<p style='color:green;'>✓ events.published column exists</p>";
} else {
    echo "<p style='color:red;'>✗ events.published column does NOT exist - RUN SETUP!</p>";
}

// Check classes table for section_id column
$check6 = $conn->query("SHOW COLUMNS FROM classes LIKE 'section_id'");
if ($check6 && $check6->num_rows > 0) {
    echo "<p style='color:green;'>✓ classes.section_id column exists</p>";
} else {
    echo "<p style='color:red;'>✗ classes.section_id column does NOT exist - RUN SETUP!</p>";
}

$conn->close();
?>
