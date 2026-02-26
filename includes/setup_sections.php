<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Sections</title>
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
        <h2>📚 Sunday School Sections Setup</h2>
        
<?php
require_once 'db.php';

echo "<div class='info'>Starting sections setup...</div>";

$sql_file = '../sql/setup_sections.sql';
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
            // Ignore "already exists" errors
            if (strpos($error_msg, 'already exists') === false && 
                strpos($error_msg, 'Duplicate') === false &&
                strpos($error_msg, 'duplicate') === false) {
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

// Verify sections were created
$check = $conn->query("SELECT * FROM sections ORDER BY id");
if ($check && $check->num_rows > 0) {
    echo "<div class='success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>The following sections have been created:</p>";
    echo "<ul>";
    while ($row = $check->fetch_assoc()) {
        echo "<li><strong>" . htmlspecialchars($row['section_name']) . "</strong> - " . htmlspecialchars($row['class_range']) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<a href='../admin/manage_events.php' class='btn'>Go to Event Management →</a>";
    echo " ";
    echo "<a href='../admin/dashboard_admin.php' class='btn' style='background:#28a745;'>Go to Dashboard →</a>";
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
