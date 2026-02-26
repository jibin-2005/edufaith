<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require '../includes/validation_helper.php';

$teacher_id = $_SESSION['user_id'];

// Get teacher's section
$teacher_section = Validator::getTeacherSection($conn, $teacher_id);

if (!$teacher_section) {
    die("Error: You are not assigned to any class/section. Please contact admin.");
}

// Handle marks entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $event_id = intval($_POST['event_id']);
    $student_id = intval($_POST['student_id']);
    $marks = intval($_POST['marks']);
    $remarks = trim($_POST['remarks'] ?? '');

    // Validate marks range
    if ($marks < 0 || $marks > 100) {
        $error = "Marks must be between 0 and 100";
    } else {
        // Verify event belongs to teacher's section
        $stmt_verify = $conn->prepare("SELECT section_id FROM events WHERE id = ?");
        $stmt_verify->bind_param("i", $event_id);
        $stmt_verify->execute();
        $event_section = $stmt_verify->get_result()->fetch_assoc()['section_id'] ?? null;
        $stmt_verify->close();

        if ($event_section != $teacher_section) {
            $error = "You can only evaluate students from your section.";
        } else {
            // Verify student is registered for this event
            $stmt_check = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
            $stmt_check->bind_param("ii", $event_id, $student_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows === 0) {
                $error = "Student is not registered for this event.";
            } else {
                // Save marks
                $stmt_save = $conn->prepare("INSERT INTO event_results (event_id, student_id, marks, remarks, evaluated_by, evaluated_at) 
                                            VALUES (?, ?, ?, ?, ?, NOW())
                                            ON DUPLICATE KEY UPDATE marks = ?, remarks = ?, evaluated_by = ?, evaluated_at = NOW()");
                $stmt_save->bind_param("iiisiii", $event_id, $student_id, $marks, $remarks, $teacher_id, $marks, $remarks, $teacher_id);
                
                if ($stmt_save->execute()) {
                    $msg = "Marks saved successfully!";
                } else {
                    $error = "Error saving marks: " . $conn->error;
                }
                $stmt_save->close();
            }
            $stmt_check->close();
        }
    }
}

// Fetch events for teacher's section
$stmt_events = $conn->prepare("SELECT e.*, s.section_name,
                               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as total_registered,
                               (SELECT COUNT(*) FROM event_results WHERE event_id = e.id AND marks IS NOT NULL) as total_evaluated
                               FROM events e
                               LEFT JOIN sections s ON e.section_id = s.id
                               WHERE e.section_id = ?
                               ORDER BY e.event_date DESC");
$stmt_events->bind_param("i", $teacher_section);
$stmt_events->execute();
$events = $stmt_events->get_result();

// Get selected event details
$selected_event_id = intval($_GET['event_id'] ?? 0);
$selected_event = null;
$registered_students = [];

if ($selected_event_id > 0) {
    $stmt_event = $conn->prepare("SELECT e.*, s.section_name FROM events e
                                  LEFT JOIN sections s ON e.section_id = s.id
                                  WHERE e.id = ? AND e.section_id = ?");
    $stmt_event->bind_param("ii", $selected_event_id, $teacher_section);
    $stmt_event->execute();
    $selected_event = $stmt_event->get_result()->fetch_assoc();
    $stmt_event->close();

    if ($selected_event) {
        // Fetch registered students with their marks
        $stmt_students = $conn->prepare("SELECT u.id, u.username, u.email, c.class_name,
                                         er.marks, er.remarks, er.evaluated_at
                                         FROM event_registrations reg
                                         JOIN users u ON reg.student_id = u.id
                                         LEFT JOIN classes c ON u.class_id = c.id
                                         LEFT JOIN event_results er ON er.event_id = reg.event_id AND er.student_id = u.id
                                         WHERE reg.event_id = ?
                                         ORDER BY u.username ASC");
        $stmt_students->bind_param("i", $selected_event_id);
        $stmt_students->execute();
        $registered_students = $stmt_students->get_result();
        $stmt_students->close();
    }
}

// Get section info
$stmt_section = $conn->prepare("SELECT * FROM sections WHERE id = ?");
$stmt_section->bind_param("i", $teacher_section);
$stmt_section->execute();
$section_info = $stmt_section->get_result()->fetch_assoc();
$stmt_section->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Event Results | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .section-banner {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .event-selector {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            background: #00b894;
            height: 100%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Event Results</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if (isset($msg)): ?>
            <div class="success-msg"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="section-banner">
            <h3 style="margin: 0 0 10px 0;">
                <i class="fa-solid fa-chalkboard-user"></i> Your Section: <?php echo htmlspecialchars($section_info['section_name']); ?>
            </h3>
            <p style="margin: 0; opacity: 0.9;">
                <?php echo htmlspecialchars($section_info['class_range']); ?> | 
                You can evaluate only students from your section
            </p>
        </div>

        <div class="event-selector">
            <label style="font-weight: 600; display: block; margin-bottom: 10px;">
                <i class="fa-solid fa-calendar-check"></i> Select Event to Evaluate:
            </label>
            <select onchange="window.location.href='?event_id=' + this.value" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ddd;">
                <option value="0">-- Choose Event --</option>
                <?php while($evt = $events->fetch_assoc()): ?>
                    <option value="<?php echo $evt['id']; ?>" <?php echo $selected_event_id == $evt['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($evt['title']); ?> 
                        (<?php echo date('M j, Y', strtotime($evt['event_date'])); ?>) - 
                        <?php echo $evt['total_evaluated']; ?>/<?php echo $evt['total_registered']; ?> evaluated
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <?php if ($selected_event): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3><?php echo htmlspecialchars($selected_event['title']); ?> - Evaluation</h3>
                </div>

                <?php if ($registered_students->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Marks (0-100)</th>
                                <th>Remarks</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $registered_students->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($student['marks'] !== null): ?>
                                            <span style="color: #00b894; font-weight: 600;"><?php echo $student['marks']; ?>/100</span>
                                        <?php else: ?>
                                            <span style="color: #999;">Not evaluated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['remarks'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($student['marks'] !== null): ?>
                                            <span style="color: #00b894;"><i class="fa-solid fa-check-circle"></i> Evaluated</span>
                                        <?php else: ?>
                                            <span style="color: #fdcb6e;"><i class="fa-solid fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-primary" style="padding: 6px 12px; font-size: 13px;" 
                                                onclick="openMarksModal(<?php echo $student['id']; ?>, '<?php echo addslashes($student['username']); ?>', <?php echo $student['marks'] ?? 0; ?>, '<?php echo addslashes($student['remarks'] ?? ''); ?>')">
                                            <i class="fa-solid fa-pen"></i> <?php echo $student['marks'] !== null ? 'Edit' : 'Enter'; ?> Marks
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #999;">
                        No students registered for this event yet.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Marks Entry Modal -->
    <div id="marksModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:500px; margin:100px auto; padding:30px; border-radius:12px;">
            <h3 style="margin-top:0;">Enter Marks: <span id="studentName"></span></h3>
            <form method="POST">
                <input type="hidden" name="save_marks" value="1">
                <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                <input type="hidden" name="student_id" id="studentId">
                
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">Marks (0-100) *</label>
                    <input type="number" name="marks" id="marksInput" min="0" max="100" required 
                           style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px;">
                </div>
                
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:600;">Remarks (Optional)</label>
                    <textarea name="remarks" id="remarksInput" rows="3" 
                              style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px;" 
                              placeholder="Performance feedback..."></textarea>
                </div>
                
                <div style="text-align:right;">
                    <button type="button" onclick="closeMarksModal()" 
                            style="padding:10px 20px; background:#ccc; border:none; border-radius:6px; cursor:pointer; margin-right:10px;">
                        Cancel
                    </button>
                    <button type="submit" 
                            style="padding:10px 20px; background:var(--primary); color:white; border:none; border-radius:6px; cursor:pointer;">
                        <i class="fa-solid fa-save"></i> Save Marks
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMarksModal(studentId, studentName, marks, remarks) {
            document.getElementById('marksModal').style.display = 'block';
            document.getElementById('studentId').value = studentId;
            document.getElementById('studentName').innerText = studentName;
            document.getElementById('marksInput').value = marks || '';
            document.getElementById('remarksInput').value = remarks || '';
        }

        function closeMarksModal() {
            document.getElementById('marksModal').style.display = 'none';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMarksModal();
        });
    </script>
</body>
</html>
