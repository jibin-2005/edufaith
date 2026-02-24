<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle New Event (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event']) && $role === 'admin') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date_input = trim($_POST['event_date'] ?? '');
    $event_date = $event_date_input;

    // Validation
    $errors = [];
    $valTitle = Validator::validateTitle($title, 'Event Title');
    if ($valTitle !== true) $errors[] = $valTitle;

    $valDesc = Validator::validateDescription($description, 'Description');
    if ($valDesc !== true) $errors[] = $valDesc;

    $valDate = Validator::validateDate($event_date_input, 'Event Date', 'future_only');
    if ($valDate !== true) $errors[] = $valDate;

    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];
        // Convert HTML5 datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME
        $event_date = str_replace('T', ' ', $event_date_input);
        if ($event_date !== '' && strlen($event_date) <= 16) {
            $event_date .= ':00';
        }

        $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, created_by, status, is_results_published) VALUES (?, ?, ?, ?, 'upcoming', FALSE)");
        $stmt->bind_param("sssi", $title, $event_date, $description, $created_by);
        if ($stmt->execute()) {
            header("Location: events.php?msg=created");
            exit;
        } else {
             header("Location: events.php?error=db_error");
             exit;
        }
        $stmt->close();
    } else {
        $errorStr = implode(", ", $errors);
        header("Location: events.php?error=" . urlencode($errorStr));
        exit;
    }
}

// Handle Student Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event']) && $role === 'student') {
    $event_id = intval($_POST['event_id']);
    $student_id = $user_id;
    
    // Check if tables exist first
    $table_check = $conn->query("SHOW TABLES LIKE 'event_registrations'");
    if (!$table_check || $table_check->num_rows === 0) {
        $_GET['error'] = 'System not initialized. Please contact administrator.';
    } else {
        // Check if already registered
        $check_stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $event_id, $student_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if (!$existing) {
            $insert_stmt = $conn->prepare("INSERT INTO event_registrations (event_id, student_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $event_id, $student_id);
            if ($insert_stmt->execute()) {
                // Also create a result record for this event
                $result_stmt = $conn->prepare("INSERT INTO event_results (event_id, student_id, result_status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE result_status = 'pending'");
                $result_stmt->bind_param("ii", $event_id, $student_id);
                $result_stmt->execute();
                $result_stmt->close();
                
                $_GET['msg'] = 'registered';
            } else {
                $_GET['error'] = 'Failed to register for event';
            }
            $insert_stmt->close();
        } else {
            $_GET['error'] = 'already_registered';
        }
    }
}

// Handle Cancel Registration
if (isset($_GET['cancel_registration'])) {
    $event_id = intval($_GET['cancel_registration']);
    $student_id = $user_id;
    
    $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $event_id, $student_id);
    $stmt->execute();
    $stmt->close();
    
    $_GET['msg'] = 'cancelled';
}

// Fetch upcoming events
$sql = "SELECT id, title, event_date, description, status, is_results_published FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC";
$result = $conn->query($sql);
if (!$result) {
    $result = new mysqli_result(null); // Create empty result on error
}

// Get student's registered events (if student)
$registered_events = [];
if ($role === 'student') {
    $reg_result = $conn->query("SELECT event_id FROM event_registrations WHERE student_id = $user_id");
    if ($reg_result) {
        while ($row = $reg_result->fetch_assoc()) {
            $registered_events[] = $row['event_id'];
        }
    }
}

$teacher_assigned_events = [];
if ($role === 'teacher') {
    $assigned_stmt = $conn->prepare("SELECT event_id FROM event_teachers WHERE teacher_id = ?");
    if ($assigned_stmt) {
        $assigned_stmt->bind_param("i", $user_id);
        $assigned_stmt->execute();
        $assigned_result = $assigned_stmt->get_result();
        while ($row = $assigned_result->fetch_assoc()) {
            $teacher_assigned_events[] = (int)$row['event_id'];
        }
        $assigned_stmt->close();
    }
}

// Check if viewing event details
$view_event_id = intval($_GET['view'] ?? 0);
$event_detail = null;
$event_teachers = [];
$student_result = null;

if ($view_event_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $view_event_id);
    $stmt->execute();
    $event_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($event_detail) {
        // Get event teachers - with error handling
        $teachers_result = $conn->query("
            SELECT u.username, u.email, et.role 
            FROM event_teachers et
            JOIN users u ON et.teacher_id = u.id
            WHERE et.event_id = $view_event_id
        ");
        if ($teachers_result) {
            while ($row = $teachers_result->fetch_assoc()) {
                $event_teachers[] = $row;
            }
        }
        
        // Get student result if event results are published - with error handling
        if ($role === 'student' && isset($event_detail['is_results_published']) && $event_detail['is_results_published']) {
            $result_stmt = $conn->prepare("SELECT marks, remarks FROM event_results WHERE event_id = ? AND student_id = ?");
            if ($result_stmt) {
                $result_stmt->bind_param("ii", $view_event_id, $user_id);
                $result_stmt->execute();
                $student_result = $result_stmt->get_result()->fetch_assoc();
                $result_stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --event-bg-soft: #f5f8ff;
            --event-border: #dce6f7;
            --event-text-soft: #5f6b80;
            --event-heading: #1f2c44;
        }
        .main-content {
            background: linear-gradient(180deg, #f6f8fc 0%, #f8fbff 220px, transparent 220px);
        }
        .event-card {
            background: white;
            padding: 22px;
            border-radius: 14px;
            margin-bottom: 16px;
            border: 1px solid var(--event-border);
            box-shadow: 0 8px 24px rgba(15, 33, 64, 0.06);
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }
        .event-create-card {
            display: block;
            border-left: 4px solid var(--primary);
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
        }
        .event-create-card h3 {
            margin-bottom: 14px;
            color: var(--event-heading);
        }
        .event-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .event-form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .event-form-field.full {
            grid-column: 1 / -1;
        }
        .event-form-field label {
            font-weight: 600;
            color: var(--event-heading);
            font-size: 13px;
        }
        .event-form-field input,
        .event-form-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
        }
        .event-form-field input:focus,
        .event-form-field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 110, 245, 0.12);
        }
        .event-date-box {
            background: linear-gradient(135deg, var(--primary), #5a4fcf);
            color: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            min-width: 78px;
            box-shadow: 0 6px 16px rgba(45, 81, 191, 0.28);
        }
        .event-date-box .day {
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }
        .event-date-box .month {
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .event-details {
            flex: 1;
        }
        .event-details h3 {
            margin: 0 0 8px 0;
            color: var(--event-heading);
            font-size: 18px;
        }
        .event-details .time {
            font-size: 13px;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }
        .event-details .description {
            color: var(--event-text-soft);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .event-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .btn-register,
        .btn-registered,
        .btn-view,
        .btn-cancel,
        .btn-back {
            border: none;
            padding: 9px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-register { background: var(--primary); color: white; }
        .btn-register:hover { background: var(--primary-dark); }
        .btn-registered { background: #6c757d; color: white; }
        .btn-view { background: #0f6bd8; color: white; }
        .btn-view:hover { background: #0d5ab5; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-cancel:hover { background: #bf2d3b; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 8px;
        }
        .badge-status {
            background: #e7f3ff;
            color: #0066cc;
        }
        .badge-registered {
            background: #d1e7dd;
            color: #0f5132;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            background: #fff;
            border: 1px dashed #c9d6ee;
            border-radius: 12px;
        }
        .no-data i {
            font-size: 42px;
            margin-bottom: 12px;
            color: #8392ad;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffc107;
        }
        
        /* Event Detail Styles */
        .event-detail-page {
            background: white;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 33, 64, 0.06);
            border: 1px solid var(--event-border);
            margin-top: 20px;
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .detail-header h2 {
            margin: 0 0 10px 0;
        }
        .detail-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .detail-meta-item {
            flex: 1;
            min-width: 200px;
        }
        .detail-meta-item label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .detail-meta-item value {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            color: #333;
        }
        .coordinator-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
        .coordinator-card {
            background: var(--event-bg-soft);
            border: 1px solid #dde7fb;
            padding: 12px;
            border-radius: 10px;
        }
        .coordinator-name {
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--event-heading);
        }
        .coordinator-email {
            font-size: 12px;
            color: var(--event-text-soft);
            margin-bottom: 4px;
        }
        .coordinator-role {
            font-size: 12px;
            color: var(--primary);
            font-weight: 700;
        }
        .detail-section {
            margin-bottom: 30px;
        }
        .detail-section h4 {
            margin-bottom: 15px;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .results-display {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .result-item {
            margin-bottom: 15px;
        }
        .result-item label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .result-value {
            font-size: 16px;
            color: #333;
        }
        .result-score {
            font-size: 24px;
            color: var(--primary);
            font-weight: 700;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            margin-bottom: 20px;
        }
        .detail-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tab-nav {
            display: flex;
            gap: 6px;
            margin: 18px 0;
            padding: 5px;
            background: #eef3ff;
            border-radius: 10px;
            width: fit-content;
            border: 1px solid #dde7fa;
        }
        .tab-btn {
            background: transparent;
            border: none;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #4b5972;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), #4f87f8);
            box-shadow: 0 6px 12px rgba(62, 119, 245, 0.25);
        }
        .tab-content {
            display: none;
            margin-top: 20px;
        }
        .tab-content.active {
            display: block;
        }
        @media (max-width: 820px) {
            .event-card {
                padding: 16px;
                gap: 12px;
            }
            .event-create-card {
                padding: 18px;
            }
            .event-form-grid {
                grid-template-columns: 1fr;
            }
            .event-date-box {
                min-width: 70px;
                padding: 12px;
            }
            .event-date-box .day {
                font-size: 24px;
            }
            .detail-header,
            .detail-meta {
                flex-direction: column;
                gap: 10px;
            }
            .tab-nav {
                width: 100%;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2><?php echo $view_event_id ? 'Event Details' : 'Upcoming Events'; ?></h2>
                <p>Church events and activities calendar</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'registered'): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check"></i> Successfully registered for event!</div>
            <?php elseif ($_GET['msg'] === 'cancelled'): ?>
                <div class="alert alert-info"><i class="fa-solid fa-info"></i> Registration cancelled.</div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'already_registered'): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation"></i> You are already registered for this event.</div>
            <?php else: ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php 
        // Check if database schema is set up for students
        $tables_setup = true;
        $schema_check = $conn->query("SHOW TABLES LIKE 'event_registrations'");
        if (!$schema_check || $schema_check->num_rows === 0) {
            $tables_setup = false;
        }
        ?>
        <?php if (!$tables_setup && $role === 'student'): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-exclamation-triangle"></i> <strong>Event Registration System Not Ready</strong><br>
                The event registration system is currently being set up. Please check back soon or contact your administrator.
            </div>
        <?php endif; ?>

        <!-- Admin: Add Event Form -->
        <?php if ($role === 'admin'): ?>
            <div class="event-card event-create-card">
                <h3><i class="fa-solid fa-calendar-plus"></i> Create New Event</h3>

                <form method="POST" id="eventForm">
                    <div class="event-form-grid">
                    <div class="event-form-field full">
                        <label>Event Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="event-form-field">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="event_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="event-form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" required></textarea>
                    </div>
                    </div>
                    <button type="submit" name="add_event" class="btn-register">Create Event</button>
                </form>
            </div>
            
            <script src="../js/validator.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const rules = {
                        'title': (val) => FormValidator.validateTitle(val, 'Event Title'),
                        'description': (val) => FormValidator.validateDescription(val, 'Description'),
                        'event_date': (val) => FormValidator.validateDate(val, 'Event Date', 'future_only')
                    };
                    FormValidator.init('#eventForm', rules, true);
                });
            </script>
        <?php endif; ?>

        <!-- Event Detail View -->
        <?php if ($view_event_id && $event_detail): ?>
            <a href="events.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>

            <div class="event-detail-page">
                <div class="detail-header">
                    <div>
                        <h2><?php echo htmlspecialchars($event_detail['title']); ?></h2>
                        <?php $detail_status = isset($event_detail['status']) ? strtolower($event_detail['status']) : 'upcoming'; ?>
                        <span class="badge badge-status"><i class="fa-solid fa-calendar"></i> <?php echo ucfirst($detail_status); ?></span>
                    </div>
                </div>

                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <label>Date & Time</label>
                        <value><?php echo date("l, F j, Y \\a\\t g:i A", strtotime($event_detail['event_date'])); ?></value>
                    </div>
                    <div class="detail-meta-item">
                        <label>Created</label>
                        <value><?php echo isset($event_detail['created_at']) ? date("F j, Y", strtotime($event_detail['created_at'])) : 'N/A'; ?></value>
                    </div>
                </div>

                <div class="detail-section">
                    <h4><i class="fa-solid fa-align-left"></i> Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($event_detail['description'])); ?></p>
                </div>

                <?php if (!empty($event_teachers)): ?>
                    <div class="detail-section">
                        <h4><i class="fa-solid fa-chalkboard-user"></i> Event Coordinators</h4>
                        <div class="coordinator-grid">
                            <?php foreach ($event_teachers as $teacher): ?>
                                <div class="coordinator-card">
                                    <div class="coordinator-name"><?php echo htmlspecialchars($teacher['username']); ?></div>
                                    <div class="coordinator-email"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                    <div class="coordinator-role"><?php echo ucfirst($teacher['role']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Student-specific sections -->
                <?php if ($role === 'student'): ?>
                    <div class="detail-section">
                        <h4><i class="fa-solid fa-ticket"></i> Registration Status</h4>
                        <?php 
                        $is_registered = in_array($view_event_id, $registered_events);
                        ?>
                        <?php if ($is_registered): ?>
                            <div class="alert alert-success">
                                <i class="fa-solid fa-check-circle"></i> You are registered for this event.
                            </div>
                            <form method="POST" style="display:inline;">
                                <a href="?cancel_registration=<?php echo $view_event_id; ?>" class="btn-cancel" onclick="return confirm('Cancel registration?');"><i class="fa-solid fa-times"></i> Cancel Registration</a>
                            </form>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $view_event_id; ?>">
                                <button type="submit" name="register_event" class="btn-register"><i class="fa-solid fa-pen-to-square"></i> Register for Event</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Event Results section -->
                    <?php if ($event_detail['is_results_published'] && $student_result): ?>
                        <div class="detail-section">
                            <h4><i class="fa-solid fa-chart-line"></i> Event Results</h4>
                            <div class="results-display">
                                <div class="result-item">
                                    <label>Your Score</label>
                                    <value class="result-value result-score">
                                        <?php echo $student_result['marks'] !== null ? $student_result['marks'] : 'Not graded'; ?>
                                    </value>
                                </div>
                                <?php if (!empty($student_result['remarks'])): ?>
                                    <div class="result-item">
                                        <label>Remarks</label>
                                        <value class="result-value"><?php echo nl2br(htmlspecialchars($student_result['remarks'])); ?></value>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($event_detail['is_results_published']): ?>
                        <div class="detail-section">
                            <h4><i class="fa-solid fa-chart-line"></i> Event Results</h4>
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle"></i> Results have been published. You will see your scores once they are available.
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Action button for back -->
                <div class="detail-actions">
                    <a href="events.php" class="btn-view"><i class="fa-solid fa-arrow-left"></i> Back to All Events</a>
                    <?php if ($role === 'teacher' && in_array($view_event_id, $teacher_assigned_events)): ?>
                        <a href="manage_event_results.php" class="btn-register">
                            <i class="fa-solid fa-chart-line"></i> Manage Results
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (!$view_event_id): ?>
            <!-- Tab Navigation for Students -->
            <?php if ($role === 'student'): ?>
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="showTab(event, 'all-events', this)">
                        <i class="fa-solid fa-calendar"></i> All Upcoming Events
                    </button>
                    <button class="tab-btn" onclick="showTab(event, 'my-events', this)">
                        <i class="fa-solid fa-bookmark"></i> My Registered Events (<?php echo count($registered_events); ?>)
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- All Events Tab -->
            <div id="all-events" class="tab-content active">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $is_registered = in_array($row['id'], $registered_events);
                    ?>
                        <div class="event-card">
                            <div class="event-date-box">
                                <div class="day"><?php echo date("j", strtotime($row['event_date'])); ?></div>
                                <div class="month"><?php echo date("M", strtotime($row['event_date'])); ?></div>
                            </div>
                            <div class="event-details">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="time"><i class="fa-regular fa-clock"></i> <?php echo date("l, g:i A", strtotime($row['event_date'])); ?></div>
                                <?php $status = isset($row['status']) ? strtolower($row['status']) : 'upcoming'; ?>
                                <span class="badge badge-status"><?php echo ucfirst($status); ?></span>
                                <?php if ($is_registered && $role === 'student'): ?>
                                    <span class="badge badge-registered"><i class="fa-solid fa-check"></i> Registered</span>
                                <?php endif; ?>
                                <?php if (!empty($row['description'])): ?>
                                    <div class="description"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?></div>
                                <?php endif; ?>
                                <div class="event-actions">
                                    <a href="?view=<?php echo $row['id']; ?>" class="btn-view"><i class="fa-solid fa-eye"></i> View Details</a>
                                    <?php if ($role === 'student'): ?>
                                        <?php if ($is_registered): ?>
                                            <button class="btn-registered" disabled><i class="fa-solid fa-check"></i> Already Registered</button>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="register_event" class="btn-register"><i class="fa-solid fa-pen-to-square"></i> Register</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php elseif ($role === 'teacher' && in_array((int)$row['id'], $teacher_assigned_events)): ?>
                                        <a href="manage_event_results.php" class="btn-register"><i class="fa-solid fa-chart-line"></i> Manage Results</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <p>No upcoming events scheduled.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Registered Events Tab (Students Only) -->
            <?php if ($role === 'student'): ?>
                <div id="my-events" class="tab-content">
                    <?php if (!empty($registered_events)): ?>
                        <?php 
                        $registered_result = $conn->query("SELECT e.*, u.username as created_by_name FROM events e 
                                                          LEFT JOIN users u ON e.created_by = u.id 
                                                          WHERE e.id IN (" . implode(',', $registered_events) . ") 
                                                          ORDER BY e.event_date ASC");
                        ?>
                        <?php if ($registered_result && $registered_result->num_rows > 0): ?>
                            <?php while($row = $registered_result->fetch_assoc()): ?>
                                <div class="event-card">
                                    <div class="event-date-box">
                                        <div class="day"><?php echo date("j", strtotime($row['event_date'])); ?></div>
                                        <div class="month"><?php echo date("M", strtotime($row['event_date'])); ?></div>
                                    </div>
                                    <div class="event-details">
                                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                        <div class="time"><i class="fa-regular fa-clock"></i> <?php echo date("l, g:i A", strtotime($row['event_date'])); ?></div>
                                        <?php $status = isset($row['status']) ? strtolower($row['status']) : 'upcoming'; ?>
                                        <span class="badge badge-status"><?php echo ucfirst($status); ?></span>
                                        <span class="badge badge-registered"><i class="fa-solid fa-check"></i> Registered</span>
                                        
                                        <?php 
                                        // Check if results are published
                                        $my_result = null;
                                        if ($row['is_results_published']) {
                                            $res_check = $conn->prepare("SELECT marks, remarks, result_status FROM event_results WHERE event_id = ? AND student_id = ?");
                                            $res_check->bind_param("ii", $row['id'], $user_id);
                                            $res_check->execute();
                                            $res_data = $res_check->get_result();
                                            $my_result = $res_data->fetch_assoc();
                                            $res_check->close();
                                        }
                                        
                                        if ($my_result && $my_result['marks'] !== null) {
                                            echo '<span class="badge" style="background: #d1e7dd; color: #0f5132;"><i class="fa-solid fa-chart-line"></i> Result: ' . $my_result['marks'] . '/100</span>';
                                        } elseif ($row['is_results_published']) {
                                            echo '<span class="badge" style="background: #fff3cd; color: #664d03;"><i class="fa-solid fa-hourglass-end"></i> Results Published - Awaiting Mark</span>';
                                        }
                                        ?>
                                        
                                        <?php if (!empty($row['description'])): ?>
                                            <div class="description"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?></div>
                                        <?php endif; ?>
                                        <div class="event-actions">
                                            <a href="?view=<?php echo $row['id']; ?>" class="btn-view"><i class="fa-solid fa-eye"></i> View Details</a>
                                            <a href="?cancel_registration=<?php echo $row['id']; ?>" class="btn-cancel" onclick="return confirm('Cancel registration?');"><i class="fa-solid fa-times"></i> Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fa-solid fa-calendar-check"></i>
                            <p>You haven't registered for any events yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        
        <?php endif; ?>
        
    </div>

    <script>
        function showTab(e, tabName, buttonEl) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Mark button as active
            if (buttonEl) {
                buttonEl.classList.add('active');
            } else if (e && e.currentTarget) {
                e.currentTarget.classList.add('active');
            }
        }
    </script>

</body>
</html>

