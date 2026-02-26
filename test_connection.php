<?php
// Test different connection methods

echo "<h2>Testing Database Connections</h2>";

// Test 1: 127.0.0.1
echo "<h3>Test 1: Connecting to 127.0.0.1</h3>";
$conn1 = @new mysqli("127.0.0.1", "root", "", "sunday_school_db");
if ($conn1->connect_error) {
    echo "❌ Failed: " . $conn1->connect_error . "<br>";
} else {
    echo "✅ Success!<br>";
    $conn1->close();
}

// Test 2: localhost
echo "<h3>Test 2: Connecting to localhost</h3>";
$conn2 = @new mysqli("localhost", "root", "", "sunday_school_db");
if ($conn2->connect_error) {
    echo "❌ Failed: " . $conn2->connect_error . "<br>";
} else {
    echo "✅ Success!<br>";
    $conn2->close();
}

// Test 3: localhost with port
echo "<h3>Test 3: Connecting to localhost:3306</h3>";
$conn3 = @new mysqli("localhost", "root", "", "sunday_school_db", 3306);
if ($conn3->connect_error) {
    echo "❌ Failed: " . $conn3->connect_error . "<br>";
} else {
    echo "✅ Success!<br>";
    $conn3->close();
}

// Test 4: 127.0.0.1 with port
echo "<h3>Test 4: Connecting to 127.0.0.1:3306</h3>";
$conn4 = @new mysqli("127.0.0.1", "root", "", "sunday_school_db", 3306);
if ($conn4->connect_error) {
    echo "❌ Failed: " . $conn4->connect_error . "<br>";
} else {
    echo "✅ Success!<br>";
    $conn4->close();
}

echo "<hr>";
echo "<h3>Recommended Fix:</h3>";
echo "<p>If all tests failed, you need to configure MariaDB to allow connections.</p>";
echo "<p>Open phpMyAdmin and run this SQL:</p>";
echo "<pre>";
echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;\n";
echo "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY '' WITH GRANT OPTION;\n";
echo "FLUSH PRIVILEGES;";
echo "</pre>";
?>
