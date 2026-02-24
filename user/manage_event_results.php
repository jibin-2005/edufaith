<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$user_id = $_SESSION['user_id'];
$view_event_id = intval($_GET['view'] ?? 0);

// Handle Publish Results (Teacher Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_results'])) {
    $event_id = intval($_POST['event_id']);
    
    // Verify teacher is assigned to this event
    $verify_stmt = $conn->prepare("SELECT id FROM event_teachers WHERE event_id = ? AND teacher_id = ? AND role IN ('coordinator', 'co-coordinator')");
    $verify_stmt->bind_param("ii", $event_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $error = "Only assigned teachers can publish results.";
    } else {
        // Update all pending results to published
        $stmt = $conn->prepare("UPDATE event_results SET result_status = 'published', published_at = NOW(), published_by = ? WHERE event_id = ?");
        $stmt->bind_param("ii", $user_id, $event_id);
        if ($stmt->execute()) {
            $msg = "Event results published successfully!";
            // Also update events table
            $update_stmt = $conn->prepare("UPDATE events SET is_results_published = TRUE WHERE id = ?");
            $update_stmt->bind_param("i", $event_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $error = "Error publishing results: " . $conn->error;
        }
        $stmt->close();
    }
    $verify_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Event Results - Sunday School Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar .logo {
            padding: 0 20px;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: bold;
        }
        .sidebar .menu {
            list-style: none;
        }
        .sidebar .menu li {
            padding: 0;
            margin-bottom: 8px;
        }
        .sidebar .menu li a {
            display: block;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar .menu li a:hover {
            background: rgba(0, 0, 0, 0.2);
            color: white;
        }
        .sidebar .menu li a.active {
            background: rgba(0, 0, 0, 0.3);
            color: white;
            border-left: 4px solid white;
            padding-left: 16px;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 20px;
        }
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #e6e6e6;
        }
        .user-img img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eef2f7;
        }
        .welcome-text h2 {
            font-size: 24px;
            color: #333;
            margin: 0 0 5px 0;
        }
        .welcome-text p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .events-grid {
            display: grid;
            gap: 20px;
        }
        .event-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
        }
        .event-card h3 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 18px;
        }
        .event-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #666;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-registered {
            background: #d1e7dd;
            color: #0f5132;
        }
        .badge-published {
            background: #d1e7dd;
            color: #0f5132;
        }
        .badge-pending {
            background: #fff3cd;
            color: #664d03;
        }
        .badge-coordinator {
            background: #d1ecf1;
            color: #0c5460;
        }
        .results-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .results-section h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table thead {
            background: #f8f9fa;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        table tbody tr:hover {
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-success {
            background: var(--success);
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
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            color: #ddd;
        }
        .status-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .not-assigned {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            color: #666;
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
                <h2><i class="fa-solid fa-chart-line"></i> Event Results Management</h2>
                <p>Manage results for events where you are assigned as coordinator</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- Messages -->
        <?php if (isset($msg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Check if database is initialized -->
        <?php 
        $tables_ready = true;
        $table_check = $conn->query("SHOW TABLES LIKE 'event_teachers'");
        if (!$table_check || $table_check->num_rows === 0) {
            $tables_ready = false;
        }
        
        if (!$tables_ready): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-info-circle"></i> Event system is not yet initialized. Please contact your administrator.
            </div>
        <?php else: 
            // Fetch events where this teacher is coordinator or co-coordinator
            $events_stmt = $conn->prepare("
                SELECT DISTINCT e.id, e.title, e.event_date, e.description, e.status, e.is_results_published, et.role
                FROM events e
                JOIN event_teachers et ON e.id = et.event_id
                WHERE et.teacher_id = ? AND et.role IN ('coordinator', 'co-coordinator')
                ORDER BY e.event_date DESC
            ");
            $events_stmt->bind_param("i", $user_id);
            $events_stmt->execute();
            $events_result = $events_stmt->get_result();
            $events = [];
            while ($row = $events_result->fetch_assoc()) {
                $events[] = $row;
            }
            $events_stmt->close();
        ?>

        <div class="events-grid">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta">
                                    <div class="meta-item">
                                        <i class="fa-regular fa-calendar"></i>
                                        <?php echo date("F j, Y", strtotime($event['event_date'])); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fa-regular fa-clock"></i>
                                        <?php echo date("g:i A", strtotime($event['event_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge" style="background: <?php echo $event['status'] === 'completed' ? '#d1e7dd' : '#fff3cd'; ?>; color: <?php echo $event['status'] === 'completed' ? '#0f5132' : '#664d03'; ?>;">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                                <?php if ($event['is_results_published']): ?>
                                    <span class="badge badge-published">
                                        <i class="fa-solid fa-check"></i> Published
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-pending">
                                        <i class="fa-solid fa-hourglass-end"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($event['description'])): ?>
                            <p style="color: #666; font-size: 14px; margin: 12px 0 0 0;"><?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?></p>
                        <?php endif; ?>

                        <!-- Results Section -->
                        <div class="results-section">
                            <h4><i class="fa-solid fa-chart-line"></i> Student Results</h4>
                            
                            <?php 
                            // Get registered students
                            $students_stmt = $conn->prepare("
                                    SELECT u.id, u.username, u.email, er.id as reg_id,
                                        evr.marks, evr.remarks, evr.result_status, evr.placement
                                FROM event_registrations er
                                JOIN users u ON er.student_id = u.id
                                    LEFT JOIN event_results evr ON er.event_id = evr.event_id AND er.student_id = evr.student_id
                                WHERE er.event_id = ?
                                ORDER BY u.username ASC
                            ");
                            $students_stmt->bind_param("i", $event['id']);
                            $students_stmt->execute();
                            $students_result = $students_stmt->get_result();
                            $students = [];
                            while ($row = $students_result->fetch_assoc()) {
                                $students[] = $row;
                            }
                            $students_stmt->close();
                            ?>

                            <?php if (!empty($students)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Marks</th>
                                            <th>Remarks</th>
                                            <th>Status</th>
                                            <th>Placement</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max="100"
                                                                id="marks_<?php echo $event['id']; ?>_<?php echo $student['id']; ?>"
                                                                value="<?php echo $student['marks'] !== null ? (int)$student['marks'] : ''; ?>"
                                                                style="width:80px; padding:6px; border-radius:4px; border:1px solid #ddd;"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="text"
                                                                id="remarks_<?php echo $event['id']; ?>_<?php echo $student['id']; ?>"
                                                                value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>"
                                                                style="width:220px; padding:6px; border-radius:4px; border:1px solid #ddd;"
                                                                placeholder="Remarks"
                                                            >
                                                        </td>
                                                        <td>
                                                            <span class="badge" style="background: <?php echo ($student['result_status'] === 'published') ? '#d1e7dd; color: #0f5132;' : '#fff3cd; color: #664d03;'; ?>">
                                                                <?php echo ucfirst($student['result_status'] ?? 'pending'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php $curPlacement = $student['placement'] ?? '';?>
                                                            <select id="placement_<?php echo $event['id']; ?>_<?php echo $student['id']; ?>" style="padding:6px; border-radius:4px;">
                                                                <option value="">-</option>
                                                                <option value="first" <?php echo ($curPlacement === 'first') ? 'selected' : ''; ?>>First</option>
                                                                <option value="second" <?php echo ($curPlacement === 'second') ? 'selected' : ''; ?>>Second</option>
                                                                <option value="third" <?php echo ($curPlacement === 'third') ? 'selected' : ''; ?>>Third</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <button
                                                                type="button"
                                                                class="btn btn-primary"
                                                                onclick="saveResult(<?php echo $event['id']; ?>, <?php echo $student['id']; ?>)"
                                                            >
                                                                Save
                                                            </button>
                                                        </td>
                                                    </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Publish Results Info -->
                                <?php if (!$event['is_results_published']): ?>
                                    <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px; border-left: 4px solid var(--primary);">
                                        <p style="margin: 0 0 12px 0; color: #0066cc; font-weight: 600;">
                                            <i class="fa-solid fa-info-circle"></i> Ready to Publish
                                        </p>
                                        <p style="margin: 0 0 15px 0; color: #333; font-size: 14px;">
                                            Click the button below to publish these results to students. Once published, they will be able to see their marks and remarks.
                                        </p>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="publish_results" class="btn btn-success" 
                                                    onclick="return confirm('Publish all results for this event? This action cannot be undone.');">
                                                <i class="fa-solid fa-paper-plane"></i> Publish Results to Students
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 20px; padding: 15px; background: #d1e7dd; border-radius: 6px; border-left: 4px solid var(--success);">
                                        <p style="margin: 0; color: #0f5132; font-weight: 600;">
                                            <i class="fa-solid fa-check-circle"></i> Results Published
                                        </p>
                                        <p style="margin: 8px 0 0 0; color: #0f5132; font-size: 13px;">
                                            Students can now see their results.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="not-assigned">
                                    <i class="fa-solid fa-users"></i>
                                    <p>No students registered for this event yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <h3>No Events Assigned</h3>
                    <p>You are not assigned as a coordinator or co-coordinator for any events yet.</p>
                    <p style="font-size: 13px; color: #aaa; margin-top: 10px;">
                        When an admin assigns you to manage event results, they will appear here.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </div>

    <script>
        function saveResult(eventId, studentId) {
            const marksInput = document.getElementById('marks_' + eventId + '_' + studentId);
            const remarksInput = document.getElementById('remarks_' + eventId + '_' + studentId);
            const placementInput = document.getElementById('placement_' + eventId + '_' + studentId);

            if (!marksInput || !remarksInput || !placementInput) {
                alert('Unable to find result fields for this student.');
                return;
            }

            const marksRaw = (marksInput.value || '').trim();
            if (marksRaw !== '') {
                const marksNum = Number(marksRaw);
                if (Number.isNaN(marksNum) || marksNum < 0 || marksNum > 100) {
                    alert('Marks must be between 0 and 100.');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('event_id', eventId);
            formData.append('student_id', studentId);
            formData.append('marks', marksRaw);
            formData.append('remarks', remarksInput.value || '');
            formData.append('placement', placementInput.value || '');

            fetch('../includes/save_event_result.php', {
                method: 'POST',
                body: formData
            }).then(resp => resp.json()).then(data => {
                if (data.success) {
                    alert('Result saved');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unable to save'));
                }
            }).catch(err => {
                console.error(err);
                alert('Network error while saving result');
            });
        }
    </script>

</body>
</html>

