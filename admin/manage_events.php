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
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    
    $errors = [];
    
    $valTitle = Validator::validateTitle($title, 'Event Title');
    if ($valTitle !== true) $errors[] = $valTitle;

    $valDesc = Validator::validateDescription($description, 'Description');
    if ($valDesc !== true) $errors[] = $valDesc;

    $valDate = Validator::validateDate($event_date, 'Event Date', 'future_only');
    if ($valDate !== true) $errors[] = $valDate;

    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, created_by, status, is_results_published) VALUES (?, ?, ?, ?, 'upcoming', FALSE)");
        $stmt->bind_param("sssi", $title, $event_date, $description, $created_by);
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

// Handle Event Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $event_id = intval($_POST['event_id']);
    $new_status = $_POST['new_status'];
    
    $valid_statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $event_id);
        if ($stmt->execute()) {
            $msg = "Event status updated successfully!";
        } else {
            $error = "Error updating status: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Teacher Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $event_id = intval($_POST['event_id']);
    $teacher_id = intval($_POST['teacher_id']);
    $role = $_POST['role'] ?? 'coordinator';
    
    if ($event_id > 0 && $teacher_id > 0) {
        $stmt = $conn->prepare("INSERT INTO event_teachers (event_id, teacher_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = ?");
        $stmt->bind_param("iiss", $event_id, $teacher_id, $role, $role);
        if ($stmt->execute()) {
            $msg = "Teacher assigned successfully!";
        } else {
            $error = "Error assigning teacher: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Remove Teacher
if (isset($_GET['remove_teacher'])) {
    $teacher_assign_id = intval($_GET['remove_teacher']);
    $event_id = intval($_GET['event_id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM event_teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_assign_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: manage_events.php?view=$event_id#event-detail");
    exit;
}

// Handle Publish Results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_results'])) {
    $event_id = intval($_POST['event_id']);
    $admin_id = $_SESSION['user_id'];
    
    // Update all pending results to published
    $stmt = $conn->prepare("UPDATE event_results SET result_status = 'published', published_at = NOW(), published_by = ? WHERE event_id = ?");
    $stmt->bind_param("ii", $admin_id, $event_id);
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("UPDATE events SET is_results_published = TRUE WHERE id = ?");
        $stmt2->bind_param("i", $event_id);
        $stmt2->execute();
        $stmt2->close();
        $msg = "Event results published successfully!";
    } else {
        $error = "Error publishing results: " . $conn->error;
    }
    $stmt->close();
}

// Handle Event Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if event has any student registrations
    $check_registrations = $conn->prepare("SELECT COUNT(*) as count FROM event_results WHERE event_id = ?");
    $check_registrations->bind_param("i", $id);
    $check_registrations->execute();
    $registration_count = $check_registrations->get_result()->fetch_assoc()['count'];
    $check_registrations->close();
    
    if ($registration_count > 0) {
        $_GET['error'] = "Cannot delete event with registered students. Please unregister all students first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: manage_events.php?msg=deleted");
            exit;
        } else {
            $_GET['error'] = "Error deleting event: " . $conn->error;
        }
        $stmt->close();
    }
}

// Check if viewing event details
$view_event_id = intval($_GET['view'] ?? 0);
$event_detail = null;
$event_registrations = [];
$event_teachers_assigned = [];
$event_results = [];

if ($view_event_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $view_event_id);
    $stmt->execute();
    $event_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($event_detail) {
        // Get registrations - with error handling
        $reg_result = $conn->query("
            SELECT er.id, u.id as student_id, u.username, u.email, er.registered_at, er.attendance_status 
            FROM event_registrations er
            JOIN users u ON er.student_id = u.id
            WHERE er.event_id = $view_event_id
            ORDER BY er.registered_at DESC
        ");
        if ($reg_result) {
            while ($row = $reg_result->fetch_assoc()) {
                $event_registrations[] = $row;
            }
        }
        
        // Get assigned teachers - with error handling
        $teachers_result = $conn->query("
            SELECT et.id, u.id as teacher_id, u.username, u.email, et.role, et.assigned_at
            FROM event_teachers et
            JOIN users u ON et.teacher_id = u.id
            WHERE et.event_id = $view_event_id
        ");
        if ($teachers_result) {
            while ($row = $teachers_result->fetch_assoc()) {
                $event_teachers_assigned[] = $row;
            }
        }
        
        // Get event results - with error handling
        $results_result = $conn->query("
            SELECT er.id, u.id as student_id, u.username, er.marks, er.remarks, er.result_status, er.published_at
            FROM event_results er
            JOIN users u ON er.student_id = u.id
            WHERE er.event_id = $view_event_id
            ORDER BY u.username
        ");
        if ($results_result) {
            while ($row = $results_result->fetch_assoc()) {
                $event_results[] = $row;
            }
        }
    }
}

// Check if database schema is set up
$tables_exist = true;
$schema_check = $conn->query("SHOW TABLES LIKE 'event_registrations'");
if (!$schema_check || $schema_check->num_rows === 0) {
    $tables_exist = false;
}


// Fetch all upcoming events for main list
$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
$all_teachers = $conn->query("SELECT id, username, email FROM users WHERE role = 'teacher' ORDER BY username");
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
            --events-surface: #ffffff;
            --events-bg-soft: #f4f8ff;
            --events-border: #d9e4f5;
            --events-heading: #1f2f4a;
            --events-text-soft: #5b6b85;
            --events-primary: #1e64d6;
            --events-primary-soft: #eaf2ff;
        }
        .main-content {
            background: linear-gradient(180deg, #f5f8ff 0%, #ffffff 220px);
        }
        .form-container {
            background: var(--events-surface);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(11, 40, 84, 0.08);
            border: 1px solid var(--events-border);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cfdcf0;
            border-radius: 8px;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--events-primary);
            box-shadow: 0 0 0 3px rgba(30, 100, 214, 0.15);
        }
        .btn-primary { background: var(--events-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-right: 5px; font-weight: 600; }
        .btn-primary:hover { background: #1552b3; }
        .btn-secondary { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .btn-success { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .table-container {
            background: var(--events-surface);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(11, 40, 84, 0.08);
            margin-bottom: 20px;
            border: 1px solid var(--events-border);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f0f5ff; font-weight: bold; color: var(--events-heading); }
        tr:hover { background: #f9f9f9; }
        .btn-action { padding: 6px 12px; margin: 2px; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .badge-upcoming { background: #cfe2ff; color: #084298; }
        .badge-ongoing { background: #fff3cd; color: #664d03; }
        .badge-completed { background: #d1e7dd; color: #0f5132; }
        .badge-cancelled { background: #f8d7da; color: #842029; }
        .error-msg { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .success-msg { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .event-detail { background: var(--events-surface); padding: 22px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(11, 40, 84, 0.08); border: 1px solid var(--events-border); }
        .detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .detail-section { margin-bottom: 22px; }
        .detail-section h4 { margin-bottom: 10px; color: var(--events-heading); }
        .tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 20px;
            padding: 5px;
            border: 1px solid var(--events-border);
            border-radius: 10px;
            background: var(--events-bg-soft);
            width: fit-content;
            flex-wrap: wrap;
        }
        .tab { padding: 10px 14px; cursor: pointer; border: none; background: transparent; font-size: 14px; border-radius: 8px; color: var(--events-text-soft); font-weight: 600; }
        .tab.active { background: linear-gradient(135deg, var(--events-primary), #4985e5); color: #fff; box-shadow: 0 6px 12px rgba(30, 100, 214, 0.25); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .info-item { padding: 12px; background: var(--events-bg-soft); border: 1px solid #dce7fa; border-radius: 8px; }
        .info-item label { font-weight: bold; color: #666; font-size: 12px; }
        .info-item value { display: block; margin-top: 5px; color: #333; }
        .empty-state { text-align: center; padding: 30px; color: #7b8798; background: #fff; border: 1px dashed #ccd8ee; border-radius: 10px; }
        .btn-back { margin-bottom: 20px; }
        .attendance-select { width: 120px; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); z-index: 1001; min-width: 400px; }
        .modal.active { display: block; }
        .modal-overlay.active { display: block; }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .modal-body { margin-bottom: 20px; }
        .modal-footer { text-align: right; }
        .form-group.inline { display: inline-block; width: 48%; margin-right: 2%; }
        .inline-card {
            background: var(--events-bg-soft);
            border: 1px solid #cfe0f8;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .inline-card.info {
            background: #eaf2ff;
            border-color: #c8dcff;
        }
        .teacher-assign-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr auto;
            gap: 10px;
            align-items: end;
        }
        .teacher-assign-grid > div label {
            margin-bottom: 6px;
        }
        .teacher-can-grid {
            display: grid;
            gap: 10px;
        }
        .teacher-can-item {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--events-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2ebfa;
            border-right: 1px solid #e2ebfa;
            border-bottom: 1px solid #e2ebfa;
        }
        .metrics-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .metrics-students { background: #e7f3ff; color: #0066cc; }
        .metrics-teachers { background: #eef0ff; color: #4e3fbf; }
        @media (max-width: 980px) {
            .info-grid { grid-template-columns: 1fr; }
            .teacher-assign-grid { grid-template-columns: 1fr; }
            .detail-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2><?php echo $view_event_id ? 'Event Details' : 'Manage Events'; ?></h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($msg)): ?>
            <div class="success-msg"><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-msg"><i class="fa-solid fa-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$tables_exist): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; color: #664d03; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <i class="fa-solid fa-exclamation-triangle"></i> <strong>Database Setup Required</strong><br>
                The enhanced events module requires database tables to be created. 
                <a href="../includes/setup_events_schema.php" style="color: #664d03; text-decoration: underline; font-weight: bold;">Click here to run the database migration</a> or visit <code>/includes/setup_events_schema.php</code>
            </div>
        <?php endif; ?>

        <!-- Back Button for Detail View -->
        <?php if ($view_event_id): ?>
            <a href="manage_events.php" class="btn-secondary btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>

            <!-- Event Detail View -->
            <div class="event-detail" id="event-detail">
                <div class="detail-header">
                    <div>
                        <h3><?php echo htmlspecialchars($event_detail['title']); ?></h3>
                        <span class="badge badge-<?php echo strtolower($event_detail['status']); ?>">
                            <?php echo ucfirst($event_detail['status']); ?>
                        </span>
                    </div>
                    <div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="event_id" value="<?php echo $event_detail['id']; ?>">
                            <select name="new_status" onchange="if(confirm('Change event status?')) this.form.submit(); else this.value='<?php echo $event_detail['status']; ?>';" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="upcoming" <?php echo $event_detail['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="ongoing" <?php echo $event_detail['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $event_detail['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $event_detail['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" style="padding: 8px 12px; border: none; background: var(--primary); color: white; border-radius: 4px; cursor: pointer;">Update</button>
                        </form>
                    </div>
                </div>

                <!-- Event Information -->
                <div class="detail-section">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Event Date & Time</label>
                            <value><?php echo date("M j, Y h:i A", strtotime($event_detail['event_date'])); ?></value>
                        </div>
                        <div class="info-item">
                            <label>Created By</label>
                            <value><?php echo htmlspecialchars($event_detail['created_by']); ?></value>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Description</label>
                        <value><?php echo nl2br(htmlspecialchars($event_detail['description'])); ?></value>
                    </div>
                </div>

                <!-- Tabs for different sections -->
                <div class="tabs">
                    <button class="tab active" onclick="switchTab(event, 'registrations')"><i class="fa-solid fa-users"></i> Registrations (<?php echo count($event_registrations); ?>)</button>
                    <button class="tab" onclick="switchTab(event, 'teachers')"><i class="fa-solid fa-chalkboard-user"></i> Teachers</button>
                    <button class="tab" onclick="switchTab(event, 'results')"><i class="fa-solid fa-chart-line"></i> Results</button>
                </div>

                <!-- Registrations Tab -->
                <div id="registrations" class="tab-content active">
                    <div class="detail-section">
                        <h4><i class="fa-solid fa-users"></i> Student Registrations</h4>
                        <?php if (!empty($event_registrations)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Attendance Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_registrations as $reg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reg['username']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                            <td><?php echo date("M j, Y", strtotime($reg['registered_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                    <select name="attendance_status" class="attendance-select" onchange="this.form.submit();">
                                                        <option value="registered" <?php echo $reg['attendance_status'] === 'registered' ? 'selected' : ''; ?>>Registered</option>
                                                        <option value="attended" <?php echo $reg['attendance_status'] === 'attended' ? 'selected' : ''; ?>>Attended</option>
                                                        <option value="absent" <?php echo $reg['attendance_status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                        <option value="cancelled" <?php echo $reg['attendance_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <a href="?view=<?php echo $view_event_id; ?>&remove_reg=<?php echo $reg['id']; ?>" onclick="return confirm('Remove registration?');" class="btn-danger btn-action"><i class="fa-solid fa-trash"></i> Remove</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty-state"><i class="fa-solid fa-inbox"></i> No student registrations yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teachers Tab -->
                <div id="teachers" class="tab-content">
                    <div class="detail-section">
                        <h4><i class="fa-solid fa-chalkboard-user"></i> Assigned Teachers</h4>
                        
                        <!-- Assign Teacher Form -->
                        <div class="form-container">
                            <h5>Assign New Teacher</h5>
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $view_event_id; ?>">
                                <div class="teacher-assign-grid">
                                    <div>
                                        <label>Teacher</label>
                                        <select name="teacher_id" required>
                                            <option value="">-- Select Teacher --</option>
                                            <?php 
                                            $all_teachers->data_seek(0);
                                            while ($teacher = $all_teachers->fetch_assoc()): ?>
                                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['username']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Role</label>
                                        <select name="role">
                                            <option value="coordinator">Coordinator</option>
                                            <option value="co-coordinator">Co-Coordinator</option>
                                            <option value="participant">Participant</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="assign_teacher" class="btn-primary">Assign</button>
                                </div>
                            </form>
                        </div>

                        <!-- Teachers List -->
                        <?php if (!empty($event_teachers_assigned)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Teacher Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Assigned</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_teachers_assigned as $teacher_assign): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher_assign['username']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher_assign['email']); ?></td>
                                            <td><span class="badge" style="background: #e7f3ff; color: #0066cc;"><?php echo ucfirst($teacher_assign['role']); ?></span></td>
                                            <td><?php echo date("M j, Y", strtotime($teacher_assign['assigned_at'])); ?></td>
                                            <td>
                                                <a href="?view=<?php echo $view_event_id; ?>&remove_teacher=<?php echo $teacher_assign['id']; ?>" onclick="return confirm('Remove teacher from event?');" class="btn-danger btn-action"><i class="fa-solid fa-trash"></i> Remove</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty-state"><i class="fa-solid fa-inbox"></i> No teachers assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Results Tab -->
                <div id="results" class="tab-content">
                    <div class="detail-section">
                        <div class="detail-header" style="margin-bottom: 20px;">
                            <h4><i class="fa-solid fa-chart-line"></i> Event Results</h4>
                            <?php if (!$event_detail['is_results_published']): ?>
                                <span class="badge" style="background: #fff3cd; color: #664d03;">
                                    <i class="fa-solid fa-hourglass-end"></i> Pending Publication
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background: #d1e7dd; color: #0f5132;">
                                    <i class="fa-solid fa-check"></i> Published
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Teacher Assignment Notice -->
                        <div class="inline-card info">
                            <p style="margin: 0; color: #0066cc; font-weight: 600;">
                                <i class="fa-solid fa-info-circle"></i> Teacher Result Management
                            </p>
                            <p style="margin: 8px 0 0 0; color: #333; font-size: 14px;">
                                Assigned teachers (Coordinators & Co-Coordinators) can enter and publish results from their personal Event Results panel. 
                                Admin can overview results here but direct entry is handled by assigned teachers.
                            </p>
                        </div>

                        <!-- Assigned Teachers Managing Results -->
                        <?php if (!empty($event_teachers_assigned)): ?>
                            <div class="inline-card">
                                <h5 style="margin: 0 0 12px 0; color: #333;">
                                    <i class="fa-solid fa-chalkboard-user"></i> Teachers Who Can Manage Results
                                </h5>
                                <div class="teacher-can-grid">
                                    <?php foreach ($event_teachers_assigned as $teacher): ?>
                                        <?php if (in_array(strtolower($teacher['role']), ['coordinator', 'co-coordinator'])): ?>
                                            <div class="teacher-can-item">
                                                <div>
                                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($teacher['username']); ?></span>
                                                    <span style="color: #666; font-size: 13px; margin-left: 8px;">(<?php echo ucfirst($teacher['role']); ?>)</span>
                                                </div>
                                                <span style="font-size: 12px; color: #999;">Can enter & manage results</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Results Overview -->
                        <?php if (!empty($event_registrations)): ?>
                            <h5 style="margin-bottom: 15px; color: #333;">
                                <i class="fa-solid fa-list"></i> Student Results Overview
                            </h5>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Marks</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_registrations as $reg): 
                                        // Get existing result if any
                                        $result_row = null;
                                        if ($event_detail) {
                                            $res_stmt = $conn->prepare("SELECT marks, remarks, result_status FROM event_results WHERE event_id = ? AND student_id = ?");
                                            $res_stmt->bind_param("ii", $view_event_id, $reg['student_id']);
                                            $res_stmt->execute();
                                            $res_result = $res_stmt->get_result();
                                            $result_row = $res_result->fetch_assoc();
                                            $res_stmt->close();
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reg['username']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                            <td><?php echo $result_row && $result_row['marks'] !== null ? $result_row['marks'] : '-'; ?></td>
                                            <td><?php echo $result_row && !empty($result_row['remarks']) ? htmlspecialchars(substr($result_row['remarks'], 0, 40)) . (strlen($result_row['remarks']) > 40 ? '...' : '') : '-'; ?></td>
                                            <td>
                                                <span class="badge" style="background: <?php echo ($result_row && $result_row['result_status'] === 'published') ? '#d1e7dd; color: #0f5132;' : '#fff3cd; color: #664d03;'; ?>">
                                                    <?php echo ($result_row && $result_row['result_status'] === 'published') ? 'Published' : 'Pending'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Publication Status Info -->
                            <?php 
                            // Count results entered
                            $completed_count = 0;
                            foreach ($event_registrations as $reg) {
                                $res_stmt = $conn->prepare("SELECT marks FROM event_results WHERE event_id = ? AND student_id = ? AND marks IS NOT NULL");
                                $res_stmt->bind_param("ii", $view_event_id, $reg['student_id']);
                                $res_stmt->execute();
                                if ($res_stmt->get_result()->num_rows > 0) {
                                    $completed_count++;
                                }
                                $res_stmt->close();
                            }
                            ?>

                            <?php if (!$event_detail['is_results_published']): ?>
                                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
                                    <p style="margin: 0 0 8px 0; color: #664d03; font-weight: 600;">
                                        <i class="fa-solid fa-hourglass-end"></i> Awaiting Publication
                                    </p>
                                    <p style="margin: 0; color: #664d03; font-size: 13px;">
                                        Results are prepared by assigned teachers and published through their Event Results panel. 
                                        Currently: <?php echo $completed_count; ?>/<?php echo count($event_registrations); ?> results entered.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 20px; padding: 15px; background: #d1e7dd; border-radius: 6px; border-left: 4px solid #28a745;">
                                    <p style="margin: 0; color: #0f5132; font-weight: 600;">
                                        <i class="fa-solid fa-check-circle"></i> Results Published to Students
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>No students registered for this event yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Add Event Form -->
            <div class="form-container">
                <h3><i class="fa-solid fa-plus"></i> Add New Event</h3>
                <form method="POST" id="eventForm">
                    <div class="form-group">
                        <label>Event Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="event_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="add_event" class="btn-primary"><i class="fa-solid fa-plus"></i> Create Event</button>
                </form>
            </div>

            <!-- Events List -->
            <div class="table-container">
                <h3><i class="fa-solid fa-list"></i> All Events</h3>
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Registrations</th>
                                <th>Teachers</th>
                                <th>Results Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                // Count registrations - with error handling
                                $reg_result = $conn->query("SELECT COUNT(*) as cnt FROM event_registrations WHERE event_id = " . intval($row['id']));
                                $reg_count = ($reg_result && $reg_result->num_rows > 0) ? $reg_result->fetch_assoc()['cnt'] : 0;
                                
                                // Count teachers - with error handling
                                $teacher_result = $conn->query("SELECT COUNT(*) as cnt FROM event_teachers WHERE event_id = " . intval($row['id']));
                                $teacher_count = ($teacher_result && $teacher_result->num_rows > 0) ? $teacher_result->fetch_assoc()['cnt'] : 0;
                                
                                // Handle missing status column (backwards compatibility)
                                $status = isset($row['status']) ? strtolower($row['status']) : 'upcoming';
                                $is_published = isset($row['is_results_published']) ? $row['is_results_published'] : false;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo date("M j, Y h:i A", strtotime($row['event_date'])); ?></td>
                                <td><span class="badge badge-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                                <td>
                                    <span class="metrics-badge metrics-students">
                                        <?php echo $reg_count; ?> students
                                    </span>
                                </td>
                                <td>
                                    <span class="metrics-badge metrics-teachers">
                                        <?php echo $teacher_count; ?> assigned
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: <?php echo $is_published ? '#d1e7dd; color: #0f5132;' : '#f8d7da; color: #842029;'; ?>">
                                        <?php echo $is_published ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?view=<?php echo $row['id']; ?>" class="btn-secondary btn-action"><i class="fa-solid fa-eye"></i> Details</a>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-danger btn-action" onclick="return confirm('Delete this event?');"><i class="fa-solid fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state"><i class="fa-solid fa-inbox"></i> No events created yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
                        
                    <div class="form-group">
                        <label for="marks">Marks (0-100) *</label>
                        <input type="number" id="marks" name="marks" min="0" max="100" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666;">Leave blank if result not available yet</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks/Comments</label>
                        <textarea id="remarks" name="remarks" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    </div>
                    
                    <div id="resultMessage" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeResultModal()">Cancel</button>
                <button type="button" class="btn-success" onclick="saveResult()">
                    <i class="fa-solid fa-save"></i> Save Result
                </button>
            </div>
        </div>
    </div>

    
    <script src="../js/validator.js"></script>
    <script>
        // Tab switching functionality
        function openTab(evt, tabName) {
            const contents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < contents.length; i++) {
                contents[i].style.display = "none";
            }
            
            const tabs = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            document.getElementById(tabName).style.display = "block";
            if (evt) evt.currentTarget.classList.add("active");
        }

        // Initialize first tab
        document.addEventListener('DOMContentLoaded', function() {
            const defaultBtn = document.querySelector('.tab-btn');
            if (defaultBtn) {
                defaultBtn.click();
            }
        });

        function switchTab(event, tabName) {
            // Hide all tab content
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content and mark tab as active
            document.getElementById(tabName).classList.add('active');
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.getElementById('eventForm');
            if (forms) {
                const rules = {
                    'title': (val) => FormValidator.validateTitle(val, 'Event Title'),
                    'description': (val) => FormValidator.validateDescription(val, 'Description'),
                    'event_date': (val) => FormValidator.validateDate(val, 'Date', 'future_only')
                };
                FormValidator.init('#eventForm', rules, true);
            }
        });
    </script>
</body>
</html>

