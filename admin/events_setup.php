<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$setup_status = [];
$all_good = true;

// Check if event_registrations table exists
$check_reg = $conn->query("SHOW TABLES LIKE 'event_registrations'");
if ($check_reg && $check_reg->num_rows > 0) {
    $setup_status['event_registrations'] = ['status' => 'exists', 'message' => 'Table exists'];
    // Check columns
    $columns = $conn->query("SHOW COLUMNS FROM event_registrations");
    $col_names = [];
    while($col = $columns->fetch_assoc()) {
        $col_names[] = $col['Field'];
    }
    if (in_array('id', $col_names) && in_array('student_id', $col_names)) {
        $setup_status['event_registrations']['columns'] = 'OK';
    } else {
        $setup_status['event_registrations']['columns'] = 'Missing columns';
        $all_good = false;
    }
} else {
    $setup_status['event_registrations'] = ['status' => 'missing', 'message' => 'Table does not exist'];
    $all_good = false;
}

// Check if event_teachers table exists
$check_teach = $conn->query("SHOW TABLES LIKE 'event_teachers'");
if ($check_teach && $check_teach->num_rows > 0) {
    $setup_status['event_teachers'] = ['status' => 'exists', 'message' => 'Table exists'];
} else {
    $setup_status['event_teachers'] = ['status' => 'missing', 'message' => 'Table does not exist'];
    $all_good = false;
}

// Check if event_results table exists
$check_res = $conn->query("SHOW TABLES LIKE 'event_results'");
if ($check_res && $check_res->num_rows > 0) {
    $setup_status['event_results'] = ['status' => 'exists', 'message' => 'Table exists'];
} else {
    $setup_status['event_results'] = ['status' => 'missing', 'message' => 'Table does not exist'];
    $all_good = false;
}

// Check if events table has required columns
$check_events = $conn->query("SHOW COLUMNS FROM events LIKE 'status'");
if ($check_events && $check_events->num_rows > 0) {
    $setup_status['events_columns'] = ['status' => 'exists', 'message' => 'Status column exists'];
} else {
    $setup_status['events_columns'] = ['status' => 'missing', 'message' => 'Status column missing'];
    $all_good = false;
}

// Handle setup execution
if (isset($_POST['run_setup'])) {
    // Include and execute the setup script
    require '../includes/setup_events_schema.php';
    header("Location: events_setup.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Module Setup - Sunday School Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
        }
        .status-badge.ready {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #664d03;
        }
        .checks {
            margin: 30px 0;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .check-item h4 {
            margin-bottom: 5px;
            color: #333;
        }
        .check-item p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }
        .check-icon {
            float: right;
            font-size: 18px;
        }
        .check-item.success .check-icon {
            color: #28a745;
        }
        .check-item.error .check-icon {
            color: #dc3545;
        }
        .action-section {
            margin-top: 30px;
            padding: 20px;
            background: #f0f7ff;
            border-radius: 8px;
            border-left: 4px solid #0066cc;
        }
        .action-section h3 {
            color: #0066cc;
            margin-bottom: 15px;
        }
        .action-section p {
            color: #555;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .instructions {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .instructions h4 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
            color: #333;
            font-size: 14px;
            line-height: 1.8;
        }
        .instructions li {
            margin-bottom: 8px;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 12px;
            margin-top: 15px;
            font-size: 13px;
            color: #664d03;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fa-solid fa-cog"></i> Events Module Setup</h1>
            <p>Initialize and verify the events management system</p>
            <span class="status-badge <?php echo $all_good ? 'ready' : 'pending'; ?>">
                <i class="fa-solid fa-<?php echo $all_good ? 'check' : 'exclamation'; ?>"></i>
                <?php echo $all_good ? 'System Ready' : 'Setup Required'; ?>
            </span>
        </div>

        <div class="checks">
            <h3 style="margin-bottom: 15px; color: #333;">Database Tables Status</h3>
            
            <!-- event_registrations check -->
            <div class="check-item <?php echo $setup_status['event_registrations']['status'] === 'exists' ? 'success' : 'error'; ?>">
                <span class="check-icon"><i class="fa-solid fa-<?php echo $setup_status['event_registrations']['status'] === 'exists' ? 'check-circle' : 'times-circle'; ?>"></i></span>
                <h4>Event Registrations Table</h4>
                <p><?php echo $setup_status['event_registrations']['message']; ?>
                   <?php if (isset($setup_status['event_registrations']['columns'])): ?>
                       - Columns: <?php echo $setup_status['event_registrations']['columns']; ?>
                   <?php endif; ?>
                </p>
            </div>

            <!-- event_teachers check -->
            <div class="check-item <?php echo $setup_status['event_teachers']['status'] === 'exists' ? 'success' : 'error'; ?>">
                <span class="check-icon"><i class="fa-solid fa-<?php echo $setup_status['event_teachers']['status'] === 'exists' ? 'check-circle' : 'times-circle'; ?>"></i></span>
                <h4>Event Teachers Table</h4>
                <p><?php echo $setup_status['event_teachers']['message']; ?></p>
            </div>

            <!-- event_results check -->
            <div class="check-item <?php echo $setup_status['event_results']['status'] === 'exists' ? 'success' : 'error'; ?>">
                <span class="check-icon"><i class="fa-solid fa-<?php echo $setup_status['event_results']['status'] === 'exists' ? 'check-circle' : 'times-circle'; ?>"></i></span>
                <h4>Event Results Table</h4>
                <p><?php echo $setup_status['event_results']['message']; ?></p>
            </div>

            <!-- events columns check -->
            <div class="check-item <?php echo $setup_status['events_columns']['status'] === 'exists' ? 'success' : 'error'; ?>">
                <span class="check-icon"><i class="fa-solid fa-<?php echo $setup_status['events_columns']['status'] === 'exists' ? 'check-circle' : 'times-circle'; ?>"></i></span>
                <h4>Events Table Columns</h4>
                <p><?php echo $setup_status['events_columns']['message']; ?></p>
            </div>
        </div>

        <?php if (!$all_good): ?>
            <div class="action-section">
                <h3><i class="fa-solid fa-warning"></i> Action Required</h3>
                <p>The Events module requires the following database tables to function properly. Click the button below to create them automatically.</p>
                
                <form method="POST">
                    <button type="submit" name="run_setup" class="btn btn-success" onclick="return confirm('This will create the necessary tables for the Events module. Continue?');">
                        <i class="fa-solid fa-play"></i> Initialize Database
                    </button>
                </form>

                <div class="note">
                    <strong><i class="fa-solid fa-info-circle"></i> Note:</strong> This will create three new tables and add columns to the events table. No existing data will be deleted.
                </div>
            </div>
        <?php else: ?>
            <div class="action-section">
                <h3><i class="fa-solid fa-check-circle"></i> System Ready</h3>
                <p>The Events module is properly configured and ready to use. You can now:</p>
                <ul style="margin-left: 20px; color: #555; font-size: 14px; line-height: 1.8;">
                    <li>Create and manage events</li>
                    <li>Assign teachers to events</li>
                    <li>Enter and publish event results</li>
                    <li>Allow students to register for events</li>
                </ul>
                <div style="margin-top: 20px;">
                    <a href="manage_events.php" class="btn btn-primary">
                        <i class="fa-solid fa-calendar"></i> Go to Events Management
                    </a>
                    <a href="dashboard_admin.php" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="instructions">
            <h4><i class="fa-solid fa-book"></i> Events Module Features</h4>
            <ol>
                <li><strong>Admins</strong> can create events, assign teachers, and publish results</li>
                <li><strong>Teachers</strong> can create events and enter results for assigned events</li>
                <li><strong>Students</strong> can register for events and view published results</li>
                <li><strong>Results</strong> are shown to students after being published by admins</li>
                <li><strong>Event Status</strong> can be: Upcoming, Ongoing, Completed, or Cancelled</li>
            </ol>
        </div>
    </div>
</body>
</html>
