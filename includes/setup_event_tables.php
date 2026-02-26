<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Event Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: red;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #004085;
            padding: 10px;
            background: #cce5ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Event Tables Setup</h2>
        
<?php
require_once 'db.php';

echo "<div class='info'>Creating event registration and results tables...</div>";

$sql_file = '../sql/create_event_tables.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>❌ Error: SQL file not found at $sql_file</div>";
    echo "</div></body></html>";
    exit;
}

$sql = file_get_contents($sql_file);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;
$errors = [];

foreach ($statements as $statement) {
    if (!empty($statement) && strlen(trim($statement)) > 5) {
        if ($conn->query($statement)) {
            $success_count++;
        } else {
            $error_count++;
            $error_msg = $conn->error;
            if (strpos($error_msg, 'already exists') === false) {
                $errors[] = $error_msg;
            }
        }
    }
}

echo "<h3>Installation Results:</h3>";
echo "<div class='info'>✓ Successful operations: $success_count</div>";

if (count($errors) > 0) {
    echo "<div class='error'>✗ Errors encountered: " . count($errors) . "</div>";
    foreach ($errors as $error) {
        echo "<div class='error'>Error: " . htmlspecialchars($error) . "</div>";
    }
}

// Verify tables were created
$check1 = $conn->query("SHOW TABLES LIKE 'event_registrations'");
$check2 = $conn->query("SHOW TABLES LIKE 'event_results'");

if ($check1 && $check1->num_rows > 0 && $check2 && $check2->num_rows > 0) {
    echo "<div class='success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The following tables have been created:</p>";
    echo "<ul>";
    echo "<li><strong>event_registrations</strong> - Stores student event registrations</li>";
    echo "<li><strong>event_results</strong> - Stores event marks and evaluation</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><strong>Next Step:</strong> Run the sections setup to enable section-based events</p>";
    echo "<a href='setup_sections.php' class='btn'>Setup Sections →</a>";
    echo " ";
    echo "<a href='../admin/manage_events.php' class='btn' style='background:#28a745;'>Go to Events →</a>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Setup encountered errors</h3>";
    echo "<p>Please check the errors above and try again.</p>";
    echo "</div>";
}

$conn->close();
?>
    </div>
</body>
</html>
