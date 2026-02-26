<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

// Handle Event Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $section_id = intval($_POST['section_id'] ?? 0);
    
    $errors = [];
    
    // Validation
    $valTitle = Validator::validateTitle($title, 'Event Title');
    if ($valTitle !== true) $errors[] = $valTitle;

    $valDesc = Validator::validateDescription($description, 'Description');
    if ($valDesc !== true) $errors[] = $valDesc;

    $valDate = Validator::validateDate($event_date, 'Event Date', 'future_only');
    if ($valDate !== true) $errors[] = $valDate;

    // Section validation
    if ($section_id <= 0) {
        $errors[] = "Please select a section for this event.";
    }

    // Check event name uniqueness within section
    if (empty($errors) && !Validator::isEventNameUniqueInSection($conn, $title, $section_id)) {
        $errors[] = "An event with this title already exists in the selected section.";
    }

    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, section_id, created_by, status, published) VALUES (?, ?, ?, ?, ?, 'upcoming', FALSE)");
        $stmt->bind_param("sssii", $title, $event_date, $description, $section_id, $created_by);
        if ($stmt->execute()) {
            $msg = "Event created successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle Publish Results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_results'])) {
    $event_id = intval($_POST['event_id']);
    
    // Check if all registered students have marks
    if (!Validator::allStudentsHaveMarks($conn, $event_id)) {
        $error = "Cannot publish results. Not all registered students have been evaluated.";
    } else {
        $stmt = $conn->prepare("UPDATE events SET published = TRUE WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        if ($stmt->execute()) {
            $msg = "Event results published successfully! Students can now view their results.";
        } else {
            $error = "Error publishing results: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Event Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if event has registrations
    $check = $conn->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['count'];
    $check->close();
    
    if ($count > 0) {
        $error = "Cannot delete event with registered students.";
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: manage_events_new.php?msg=deleted");
            exit;
        }
        $stmt->close();
    }
}

// Handle View Event Details
$view_event_id = intval($_GET['view'] ?? 0);
$event_detail = null;
$event_registrations = [];

if ($view_event_id > 0) {
    // Get event details
    $stmt = $conn->prepare("SELECT e.*, s.section_name, s.class_range FROM events e 
                           LEFT JOIN sections s ON e.section_id = s.id 
                           WHERE e.id = ?");
    $stmt->bind_param("i", $view_event_id);
    $stmt->execute();
    $event_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($event_detail) {
        // Get registrations with results
        $reg_stmt = $conn->prepare("
            SELECT er.id, er.student_id, u.username, u.email, er.registered_at,
                   evr.marks, evr.remarks, evr.result_status
            FROM event_registrations er
            JOIN users u ON er.student_id = u.id
            LEFT JOIN event_results evr ON er.event_id = evr.event_id AND er.student_id = evr.student_id
            WHERE er.event_id = ?
            ORDER BY u.username ASC
        ");
        $reg_stmt->bind_param("i", $view_event_id);
        $reg_stmt->execute();
        $event_registrations = $reg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $reg_stmt->close();
    }
}

// Fetch sections for filter and form
$sections = $conn->query("SELECT * FROM sections ORDER BY id");

// Filter by section
$filter_section = intval($_GET['section'] ?? 0);

// Fetch events with section info (only if not viewing details)
if ($view_event_id == 0) {
    if ($filter_section > 0) {
        $stmt = $conn->prepare("SELECT e.*, s.section_name, s.class_range,
                               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                               FROM events e
                               LEFT JOIN sections s ON e.section_id = s.id
                               WHERE e.section_id = ?
                               ORDER BY e.event_date DESC");
        $stmt->bind_param("i", $filter_section);
        $stmt->execute();
        $events = $stmt->get_result();
    } else {
        $events = $conn->query("SELECT e.*, s.section_name, s.class_range,
                               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                               FROM events e
                               LEFT JOIN sections s ON e.section_id = s.id
                               ORDER BY e.event_date DESC");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e64d6;
            --success-green: #00b894;
            --warning-yellow: #fdcb6e;
            --danger-red: #e74c3c;
        }
        
        .main-content {
            background: linear-gradient(180deg, #f5f8ff 0%, #ffffff 220px);
        }
        
        /* Section Badges */
        .section-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-little-flower { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #2d3436; }
        .section-dominic-savio { background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; }
        .section-alphonsa { background: linear-gradient(135deg, #a29bfe, #6c5ce7); color: white; }
        .section-st-thomas { background: linear-gradient(135deg, #fd79a8, #e84393); color: white; }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-bar label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filter-bar select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: white;
            font-size: 14px;
            min-width: 250px;
        }
        
        /* Form Container */
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .form-container h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group small {
            color: #7f8c8d;
            font-weight: normal;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 100, 214, 0.1);
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #1552b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 100, 214, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: var(--danger-red);
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        /* Event Cards */
        .events-grid {
            display: grid;
            gap: 20px;
        }
        
        .event-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid var(--primary-blue);
        }
        
        .event-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .event-title {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 18px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .event-meta {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .event-meta i {
            color: var(--primary-blue);
        }
        
        .event-description {
            margin: 15px 0 0 0;
            color: #555;
            line-height: 1.6;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-published {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-unpublished {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-upcoming {
            background: #cfe2ff;
            color: #084298;
        }
        
        .badge-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        /* Messages */
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 16px;
            margin: 0;
        }
        
        /* Panel */
        .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .panel-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .panel-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .event-header {
                flex-direction: column;
            }
            
            .event-actions {
                width: 100%;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-bar select {
                width: 100%;
            }
        }
        
        /* Event Detail View */
        .detail-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .detail-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .detail-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-info-item label {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .detail-info-item value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .results-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .results-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: #2196f3;
            margin-right: 8px;
        }
        
        .info-box p {
            margin: 0;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2><i class="fa-solid fa-calendar-days"></i> Manage Events</h2>
                <p>Create and manage events for different sections</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($msg)): ?>
            <div class="success-msg"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($view_event_id > 0 && $event_detail): ?>
            <!-- Event Detail View -->
            <a href="manage_events_new.php" class="btn-secondary" style="margin-bottom: 20px; display: inline-block;">
                <i class="fa-solid fa-arrow-left"></i> Back to Events List
            </a>
            
            <div class="detail-container">
                <div class="detail-header">
                    <div>
                        <h3 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
                            <?php echo htmlspecialchars($event_detail['title']); ?>
                            <?php 
                            $section_class = 'section-' . strtolower(str_replace(' ', '-', $event_detail['section_name']));
                            ?>
                            <span class="section-badge <?php echo $section_class; ?>">
                                <?php echo htmlspecialchars($event_detail['section_name']); ?>
                            </span>
                        </h3>
                        <span class="status-badge <?php echo $event_detail['published'] ? 'badge-published' : 'badge-unpublished'; ?>">
                            <i class="fa-solid fa-<?php echo $event_detail['published'] ? 'check' : 'clock'; ?>"></i>
                            <?php echo $event_detail['published'] ? 'Results Published' : 'Results Not Published'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-info">
                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <label>Event Date</label>
                            <value><?php echo date('M j, Y', strtotime($event_detail['event_date'])); ?></value>
                        </div>
                        <div class="detail-info-item">
                            <label>Event Time</label>
                            <value><?php echo date('g:i A', strtotime($event_detail['event_date'])); ?></value>
                        </div>
                        <div class="detail-info-item">
                            <label>Section</label>
                            <value><?php echo htmlspecialchars($event_detail['section_name']); ?> (<?php echo htmlspecialchars($event_detail['class_range']); ?>)</value>
                        </div>
                        <div class="detail-info-item">
                            <label>Total Registrations</label>
                            <value><?php echo count($event_registrations); ?> students</value>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <label style="font-size: 12px; color: #7f8c8d; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; display: block;">Description</label>
                        <p style="margin: 0; color: #2c3e50; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($event_detail['description'])); ?></p>
                    </div>
                </div>
                
                <div class="info-box">
                    <p><i class="fa-solid fa-info-circle"></i> <strong>Admin View Only:</strong> You can view participant marks but cannot edit them. Teachers assigned to this event can enter and manage results.</p>
                </div>
                
                <h4 style="margin-bottom: 15px; color: #2c3e50;">
                    <i class="fa-solid fa-chart-bar"></i> Participant Results
                </h4>
                
                <?php if (!empty($event_registrations)): ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Registered On</th>
                                <th>Marks</th>
                                <th>Remarks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach ($event_registrations as $reg): 
                            ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($reg['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reg['registered_at'])); ?></td>
                                    <td>
                                        <?php if ($reg['marks'] !== null): ?>
                                            <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 4px; font-weight: 600;">
                                                <?php echo $reg['marks']; ?>/100
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999; font-style: italic;">Not evaluated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($reg['remarks'])): ?>
                                            <?php echo htmlspecialchars(substr($reg['remarks'], 0, 50)); ?>
                                            <?php if (strlen($reg['remarks']) > 50): ?>
                                                <span style="color: #999;">...</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reg['result_status'] === 'published'): ?>
                                            <span class="status-badge badge-published">Published</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-unpublished">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php 
                    // Calculate statistics
                    $evaluated_count = 0;
                    $total_marks = 0;
                    foreach ($event_registrations as $reg) {
                        if ($reg['marks'] !== null) {
                            $evaluated_count++;
                            $total_marks += $reg['marks'];
                        }
                    }
                    $average = $evaluated_count > 0 ? round($total_marks / $evaluated_count, 2) : 0;
                    ?>
                    
                    <div style="margin-top: 25px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 12px; color: #1565c0; font-weight: 600; margin-bottom: 5px;">TOTAL PARTICIPANTS</div>
                            <div style="font-size: 28px; color: #0d47a1; font-weight: 700;"><?php echo count($event_registrations); ?></div>
                        </div>
                        <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 12px; color: #2e7d32; font-weight: 600; margin-bottom: 5px;">EVALUATED</div>
                            <div style="font-size: 28px; color: #1b5e20; font-weight: 700;"><?php echo $evaluated_count; ?></div>
                        </div>
                        <div style="background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 12px; color: #e65100; font-weight: 600; margin-bottom: 5px;">PENDING</div>
                            <div style="font-size: 28px; color: #bf360c; font-weight: 700;"><?php echo count($event_registrations) - $evaluated_count; ?></div>
                        </div>
                        <div style="background: #f3e5f5; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 12px; color: #6a1b9a; font-weight: 600; margin-bottom: 5px;">AVERAGE MARKS</div>
                            <div style="font-size: 28px; color: #4a148c; font-weight: 700;"><?php echo $average; ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-users-slash"></i>
                        <p>No participants registered for this event yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Events List View -->

        <!-- Section Filter -->
        <div class="filter-bar">
            <label><i class="fa-solid fa-filter"></i> Filter by Section:</label>
            <select onchange="window.location.href='?section=' + this.value">
                <option value="0">All Sections</option>
                <?php 
                mysqli_data_seek($sections, 0);
                while($sec = $sections->fetch_assoc()): 
                ?>
                    <option value="<?php echo $sec['id']; ?>" <?php echo $filter_section == $sec['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sec['section_name']); ?> (<?php echo htmlspecialchars($sec['class_range']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if ($filter_section > 0): ?>
                <a href="?" class="btn-secondary" style="padding: 10px 15px;">
                    <i class="fa-solid fa-times"></i> Clear Filter
                </a>
            <?php endif; ?>
        </div>

        <!-- Add Event Form -->
        <div class="form-container">
            <h3><i class="fa-solid fa-plus-circle"></i> Create New Event</h3>
            <form method="POST">
                <input type="hidden" name="add_event" value="1">
                <div class="form-group">
                    <label><i class="fa-solid fa-heading"></i> Event Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Bible Quiz Competition">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-layer-group"></i> Section * <small>(Event will be visible only to students in this section)</small></label>
                    <select name="section_id" required>
                        <option value="">-- Select Section --</option>
                        <?php 
                        mysqli_data_seek($sections, 0);
                        while($sec = $sections->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $sec['id']; ?>">
                                <?php echo htmlspecialchars($sec['section_name']); ?> (<?php echo htmlspecialchars($sec['class_range']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar"></i> Event Date & Time *</label>
                    <input type="datetime-local" name="event_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-align-left"></i> Description *</label>
                    <textarea name="description" rows="4" required placeholder="Provide event details, rules, and any important information..."></textarea>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Event
                </button>
            </form>
        </div>

        <!-- Events List -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-list"></i> Events List</h3>
            </div>
            
            <?php if ($events && $events->num_rows > 0): ?>
                <div class="events-grid">
                    <?php while($event = $events->fetch_assoc()): 
                        $section_class = 'section-' . strtolower(str_replace(' ', '-', $event['section_name']));
                    ?>
                        <div class="event-card">
                            <div class="event-header">
                                <div style="flex: 1;">
                                    <h4 class="event-title">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                        <span class="section-badge <?php echo $section_class; ?>">
                                            <?php echo htmlspecialchars($event['section_name']); ?>
                                        </span>
                                    </h4>
                                    <p class="event-meta">
                                        <span><i class="fa-solid fa-calendar"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                        <span><i class="fa-solid fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_date'])); ?></span>
                                        <span><i class="fa-solid fa-users"></i> <?php echo $event['registration_count']; ?> registered</span>
                                        <span class="status-badge <?php echo $event['published'] ? 'badge-published' : 'badge-unpublished'; ?>">
                                            <i class="fa-solid fa-<?php echo $event['published'] ? 'check' : 'clock'; ?>"></i>
                                            <?php echo $event['published'] ? 'Published' : 'Unpublished'; ?>
                                        </span>
                                    </p>
                                    <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                </div>
                                <div class="event-actions">
                                    <a href="?view=<?php echo $event['id']; ?>" class="btn-secondary" style="padding: 10px 16px; font-size: 13px; background: var(--primary-blue);">
                                        <i class="fa-solid fa-eye"></i> View Results
                                    </a>
                                    <?php if (!$event['published']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Publish results? Students will be able to view their marks.');">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="publish_results" class="btn-primary" style="padding: 10px 16px; font-size: 13px;">
                                                <i class="fa-solid fa-upload"></i> Publish Results
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $event['id']; ?>" class="btn-secondary btn-danger" style="padding: 10px 16px; font-size: 13px;" onclick="return confirm('Delete this event? This action cannot be undone.');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>No events found. Create your first event above!</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
