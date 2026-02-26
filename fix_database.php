<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Fix Tool</h1>";
echo "<hr>";

// Try different connection methods
$methods = [
    ['host' => 'localhost', 'socket' => null],
    ['host' => '127.0.0.1', 'socket' => null],
    ['host' => 'localhost', 'socket' => '/tmp/mysql.sock'],
    ['host' => 'localhost', 'socket' => 'C:/xampp/mysql/mysql.sock'],
];

$working_connection = null;

echo "<h2>Testing Connection Methods...</h2>";

foreach ($methods as $index => $method) {
    $host = $method['host'];
    $socket = $method['socket'];
    
    echo "<p><strong>Test " . ($index + 1) . ":</strong> Trying $host" . ($socket ? " with socket $socket" : "") . "... ";
    
    try {
        if ($socket) {
            $conn = @new mysqli($host, 'root', '', '', 0, $socket);
        } else {
            $conn = @new mysqli($host, 'root', '');
        }
        
        if (!$conn->connect_error) {
            echo "<span style='color: green; font-weight: bold;'>✓ SUCCESS!</span></p>";
            $working_connection = $method;
            
            // Try to create/use database
            $conn->query("CREATE DATABASE IF NOT EXISTS sunday_school_db");
            $conn->select_db('sunday_school_db');
            
            echo "<p style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
            echo "<strong>Connection Successful!</strong><br>";
            echo "Host: <code>$host</code><br>";
            if ($socket) echo "Socket: <code>$socket</code><br>";
            echo "</p>";
            
            $conn->close();
            break;
        } else {
            echo "<span style='color: red;'>✗ Failed: " . $conn->connect_error . "</span></p>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>✗ Failed: " . $e->getMessage() . "</span></p>";
    }
}

if ($working_connection) {
    echo "<hr>";
    echo "<h2>✓ Fix Applied!</h2>";
    echo "<p>Updating your db.php file...</p>";
    
    $host = $working_connection['host'];
    $socket = $working_connection['socket'];
    
    $db_content = "<?php\n";
    $db_content .= "// db.php - Central Database connection\n\n";
    $db_content .= "\$servername = \"$host\";\n";
    $db_content .= "\$username = \"root\";\n";
    $db_content .= "\$password = \"\";\n";
    $db_content .= "\$dbname = \"sunday_school_db\";\n\n";
    $db_content .= "// Create connection\n";
    
    if ($socket) {
        $db_content .= "\$conn = new mysqli(\$servername, \$username, \$password, \$dbname, 0, \"$socket\");\n\n";
    } else {
        $db_content .= "\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);\n\n";
    }
    
    $db_content .= "// Check connection\n";
    $db_content .= "if (\$conn->connect_error) {\n";
    $db_content .= "    die(\"Connection failed: \" . \$conn->connect_error);\n";
    $db_content .= "}\n";
    $db_content .= "?>\n";
    
    if (file_put_contents('includes/db.php', $db_content)) {
        echo "<p style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<strong>✓ db.php has been updated successfully!</strong><br>";
        echo "You can now try logging into your application.";
        echo "</p>";
        
        echo "<p><a href='login.html' style='display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Login Page</a></p>";
    } else {
        echo "<p style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
        echo "<strong>✗ Could not write to db.php</strong><br>";
        echo "Please manually update includes/db.php with the following code:";
        echo "</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo htmlspecialchars($db_content);
        echo "</pre>";
    }
} else {
    echo "<hr>";
    echo "<h2 style='color: red;'>✗ No Working Connection Found</h2>";
    echo "<p style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<strong>Your MySQL/MariaDB server is not accessible.</strong><br><br>";
    echo "Please try these steps:<br>";
    echo "1. Open XAMPP Control Panel<br>";
    echo "2. Make sure MySQL is running (should be green)<br>";
    echo "3. If it's not running, click 'Start'<br>";
    echo "4. If it fails to start, click 'Logs' to see the error<br>";
    echo "5. You may need to change the MySQL port in XAMPP config if port 3306 is already in use<br>";
    echo "</p>";
    
    echo "<p><strong>Common Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Restart your computer</li>";
    echo "<li>Reinstall XAMPP</li>";
    echo "<li>Check if another program is using port 3306</li>";
    echo "<li>Run XAMPP as Administrator</li>";
    echo "</ul>";
}
?>
