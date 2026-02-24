<?php
/**
 * Event Results Management
 * Allows teachers to manage results for events they coordinate
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$teacher_id = $_SESSION['user_id'];

// Handle Save Results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_result'])) {
    $event_id = intval($_POST['event_id']);
    $student_id = intval($_POST['student_id']);
    $marks = intval($_POST['marks']);
    $remarks = $_POST['remarks'] ?? '';
    
    // Validate marks (0-100)
    if ($marks < 0 || $marks > 100) {
        $_GET['error'] = 'Marks must be between 0 and 100';
    } else {
        // Verify teacher is assigned to this event
        $verify = $conn->query("SELECT id FROM event_teachers WHERE event_id = $event_id AND teacher_id = $teacher_id");
        if ($verify->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO event_results (event_id, student_id, marks, remarks) 
                                    VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE marks = ?, remarks = ?");
            $stmt->bind_param("iiisss", $event_id, $student_id, $marks, $remarks, $marks, $remarks);
            if ($stmt->execute()) {
                $_GET['msg'] = 'result_saved';
            } else {
                $_GET['error'] = 'Error saving result: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $_GET['error'] = 'You are not assigned to this event';
        }
    }
}

// Get events where teacher is coordinator
$my_events = $conn->query("
    SELECT DISTINCT e.id, e.title, e.event_date, e.status, e.is_results_published
    FROM events e
    JOIN event_teachers et ON e.id = et.event_id
    WHERE et.teacher_id = $teacher_id
    ORDER BY e.event_date DESC
");

// Check if viewing specific event
$view_event_id = intval($_GET['view'] ?? 0);
$event_detail = null;
$registrations_with_results = [];

if ($view_event_id > 0) {
    // Verify teacher is assigned
    $verify = $conn->query("SELECT * FROM events WHERE id = $view_event_id");
    $event_detail = $verify->fetch_assoc();
    
    if ($event_detail) {
        // Get registrations with results
        $sql = "
            SELECT u.id, u.username, u.email, 
                   er.id as reg_id, er.attendance_status,
                   evr.id as result_id, evr.marks, evr.remarks, evr.result_status
            FROM event_registrations er
            JOIN users u ON er.student_id = u.id
            LEFT JOIN event_results evr ON er.event_id = evr.event_id AND er.student_id = evr.student_id
            WHERE er.event_id = $view_event_id
            ORDER BY u.username
        ";
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $registrations_with_results[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Results | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .btn-success { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-published { background: #d1e7dd; color: #0f5132; }
        .badge-pending { background: #fff3cd; color: #664d03; }
        .badge-attended { background: #cfe2ff; color: #084298; }
        .badge-absent { background: #f8d7da; color: #842029; }
        .error-msg { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .success-msg { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .detail-view { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .detail-header { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #eee; }
        .detail-header h3 { margin: 0; }
        .event-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-item { padding: 12px; background: #f8f9fa; border-radius: 4px; }
        .info-item label { font-weight: bold; color: #666; font-size: 12px; }
        .info-label { display: block; font-weight: bold; color: #666; margin-bottom: 5px; }
        .event-list-item { background: white; padding: 15px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--primary); cursor: pointer; transition: box-shadow 0.2s; }
        .event-list-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .event-list-item h4 { margin: 0 0 8px 0; }
        .event-list-item p { margin: 0; color: #666; font-size: 13px; }
        .btn-back { background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; display: inline-block; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .modal-footer { margin-top: 20px; text-align: right; }
        .input-small { width: 80px; }
        .input-medium { width: 200px; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .result-row-form { display: inline-flex; gap: 8px; align-items: center; }
        .result-row-form input { padding: 6px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2><?php echo $view_event_id ? 'Event Results - ' . htmlspecialchars($event_detail['title']) : 'Manage Event Results'; ?></h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'result_saved'): ?>
            <div class="success-msg"><i class="fa-solid fa-check"></i> Result saved successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg"><i class="fa-solid fa-exclamation"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if ($view_event_id && $event_detail): ?>
            <!-- Back Button -->
            <a href="event_results.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>

            <!-- Event Detail -->
            <div class="detail-view">
                <div class="detail-header">
                    <h3><?php echo htmlspecialchars($event_detail['title']); ?></h3>
                    <span class="badge" style="background: <?php echo $event_detail['is_results_published'] ? '#d1e7dd; color: #0f5132;' : '#fff3cd; color: #664d03;'; ?>">
                        <?php echo $event_detail['is_results_published'] ? 'Results Published' : 'Results Pending'; ?>
                    </span>
                </div>

                <div class="event-info">
                    <div class="info-item">
                        <label>Event Date & Time</label>
                        <div><?php echo date("M j, Y \\a\\t g:i A", strtotime($event_detail['event_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <div><?php echo ucfirst($event_detail['status']); ?></div>
                    </div>
                </div>

                <!-- Results Table -->
                <h4 style="margin-top: 20px; margin-bottom: 15px;"><i class="fa-solid fa-chart-line"></i> Student Results</h4>
                <?php if (!empty($registrations_with_results)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Attendance</th>
                                    <th>Marks</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations_with_results as $reg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reg['username']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($reg['attendance_status']); ?>">
                                                <?php echo ucfirst($reg['attendance_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $reg['marks'] !== null ? $reg['marks'] : 'Not set'; ?></td>
                                        <td><?php echo !empty($reg['remarks']) ? htmlspecialchars(substr($reg['remarks'], 0, 30)) . (strlen($reg['remarks']) > 30 ? '...' : '') : '-'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $reg['result_id'] ? 'published' : 'pending'; ?>">
                                                <?php echo $reg['result_id'] ? 'Entered' : 'Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" onclick="openEditResult(<?php echo $reg['id']; ?>, <?php echo htmlspecialchars(json_encode($reg)); ?>)" class="btn-secondary btn-small">
                                                <i class="fa-solid fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i> No students registered for this event.
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Events List -->
            <div class="table-container">
                <h3><i class="fa-solid fa-list"></i> My Coordinated Events</h3>
                <?php if ($my_events->num_rows > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                        <?php while ($event = $my_events->fetch_assoc()): 
                            $reg_count = $conn->query("SELECT COUNT(*) as cnt FROM event_registrations WHERE event_id = " . $event['id'])->fetch_assoc()['cnt'];
                            $result_count = $conn->query("SELECT COUNT(*) as cnt FROM event_results WHERE event_id = " . $event['id'] . " AND marks IS NOT NULL")->fetch_assoc()['cnt'];
                        ?>
                            <div class="event-list-item" onclick="window.location.href='?view=<?php echo $event['id']; ?>'">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p><strong>Date:</strong> <?php echo date("M j, Y \a\t g:i A", strtotime($event['event_date'])); ?></p>
                                <p><strong>Status:</strong> <span class="badge" style="background: #e7f3ff; color: #0066cc;"><?php echo ucfirst($event['status']); ?></span></p>
                                <p><strong>Registrations:</strong> <?php echo $reg_count; ?> | <strong>Results Entered:</strong> <?php echo $result_count; ?>/<?php echo $reg_count; ?></p>
                                <?php if ($event['is_results_published']): ?>
                                    <p><span class="badge badge-published"><i class="fa-solid fa-check"></i> Published</span></p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>You are not assigned as a coordinator to any events yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Result Modal -->
    <div id="editResultModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Edit Student Result</div>
            <form method="POST">
                <input type="hidden" id="edit_event_id" name="event_id" value="">
                <input type="hidden" id="edit_student_id" name="student_id" value="">

                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" id="edit_student_name" readonly style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>Marks (0-100)</label>
                    <input type="number" id="edit_marks" name="marks" min="0" max="100" required>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea id="edit_remarks" name="remarks" rows="4" placeholder="Add any remarks about the student's performance..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditResult()">Cancel</button>
                    <button type="submit" name="save_result" class="btn-primary">Save Result</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditResult(studentId, regData) {
            const data = JSON.parse(regData);
            document.getElementById('edit_event_id').value = <?php echo $view_event_id; ?>;
            document.getElementById('edit_student_id').value = data.id;
            document.getElementById('edit_student_name').value = data.username;
            document.getElementById('edit_marks').value = data.marks || '';
            document.getElementById('edit_remarks').value = data.remarks || '';
            document.getElementById('editResultModal').classList.add('active');
        }

        function closeEditResult() {
            document.getElementById('editResultModal').classList.remove('active');
        }

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editResultModal');
            if (event.target === modal) {
                closeEditResult();
            }
        });
    </script>
</body>
</html>

