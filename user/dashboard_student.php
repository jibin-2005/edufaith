<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require_once '../includes/relationship_helper.php';

$student_id = $_SESSION['user_id'];

// Profile picture
$profile_picture = null;
$stmt_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_pic->bind_param("i", $student_id);
$stmt_pic->execute();
$profile_picture = $stmt_pic->get_result()->fetch_assoc()['profile_picture'] ?? null;
$stmt_pic->close();

// Attendance summary
$stmt_att = $conn->prepare("SELECT COUNT(*) AS total,
                           SUM(CASE WHEN status IN ('Present','Leave Approved') THEN 1 ELSE 0 END) AS present
                           FROM attendance WHERE student_id = ?");
$stmt_att->bind_param("i", $student_id);
$stmt_att->execute();
$att_row = $stmt_att->get_result()->fetch_assoc();
$stmt_att->close();
$total_days = (int)($att_row['total'] ?? 0);
$present_days = (int)($att_row['present'] ?? 0);
$attendance_percent = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;

// Pending leave requests
$stmt_leaves = $conn->prepare("SELECT COUNT(*) AS count FROM leave_requests WHERE student_id = ? AND status = 'pending'");
$stmt_leaves->bind_param("i", $student_id);
$stmt_leaves->execute();
$pending_leaves = (int)($stmt_leaves->get_result()->fetch_assoc()['count'] ?? 0);
$stmt_leaves->close();

// Upcoming events count
$event_res = $conn->query("SELECT COUNT(*) AS count FROM events WHERE event_date >= CURDATE()");
$upcoming_events = $event_res ? (int)$event_res->fetch_assoc()['count'] : 0;

// Student class
$stmt_class = $conn->prepare("SELECT class_id FROM users WHERE id = ?");
$stmt_class->bind_param("i", $student_id);
$stmt_class->execute();
$class_id = $stmt_class->get_result()->fetch_assoc()['class_id'] ?? null;
$stmt_class->close();

// Assignments due count
$assignments_due = 0;
if ($class_id) {
    $stmt_assign = $conn->prepare("SELECT COUNT(*) AS count FROM assignments WHERE class_id = ? AND due_date >= CURDATE()");
    $stmt_assign->bind_param("i", $class_id);
    $stmt_assign->execute();
    $assignments_due = (int)($stmt_assign->get_result()->fetch_assoc()['count'] ?? 0);
    $stmt_assign->close();
} else {
    $assign_res = $conn->query("SELECT COUNT(*) AS count FROM assignments WHERE class_id IS NULL AND due_date >= CURDATE()");
    $assignments_due = $assign_res ? (int)$assign_res->fetch_assoc()['count'] : 0;
}

// Class teacher details for logged-in student's class
$class_teacher = null;
if ($class_id) {
    $qualificationSelect = rel_has_column($conn, 'users', 'qualification') ? "t.qualification," : "NULL AS qualification,";
    $phoneSelect = rel_has_column($conn, 'users', 'phone') ? "t.phone," : "NULL AS phone,";
    $picSelect = rel_has_column($conn, 'users', 'profile_picture') ? "t.profile_picture," : "NULL AS profile_picture,";
    $stmt_teacher = $conn->prepare(
        "SELECT c.class_name, t.username, t.email, $phoneSelect $qualificationSelect $picSelect c.id AS class_id
         FROM classes c
         LEFT JOIN users t ON t.id = c.teacher_id AND t.role = 'teacher'
         WHERE c.id = ? LIMIT 1"
    );
    if ($stmt_teacher) {
        $stmt_teacher->bind_param("i", $class_id);
        $stmt_teacher->execute();
        $class_teacher = $stmt_teacher->get_result()->fetch_assoc();
        $stmt_teacher->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        window.RealTimeSync = RealTimeSync;
        RealTimeSync.checkAndTriggerFromURL();
    </script>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Thy word is a lamp unto my feet. - Psalm 119:105</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <div class="grid-container" style="margin-bottom: 20px;">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $attendance_percent; ?>%</h3>
                    <p>Attendance Rate</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $present_days; ?></h3>
                    <p>Days Present</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $pending_leaves; ?></h3>
                    <p>Pending Leaves</p>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $upcoming_events; ?></h3>
                    <p>Upcoming Events</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $assignments_due; ?></h3>
                    <p>Assignments Due</p>
                </div>
                <div class="card-icon bg-orange">
                    <i class="fa-solid fa-book"></i>
                </div>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 22px;">
            <div class="panel-header">
                <h3>Class Teacher</h3>
            </div>
            <?php if ($class_teacher && !empty($class_teacher['username'])): ?>
                <div class="person-card" style="max-width:520px; cursor:pointer; transition:all 0.3s;" 
                     onclick="openTeacherModal({name: '<?php echo htmlspecialchars(addslashes($class_teacher['username'])); ?>', email: '<?php echo htmlspecialchars(addslashes($class_teacher['email'] ?? '')); ?>', phone: '<?php echo htmlspecialchars(addslashes($class_teacher['phone'] ?? '')); ?>', qualification: '<?php echo htmlspecialchars(addslashes($class_teacher['qualification'] ?? '')); ?>', class: '<?php echo htmlspecialchars(addslashes($class_teacher['class_name'] ?? 'My Class')); ?>', pic: '<?php echo htmlspecialchars(rel_avatar_src($class_teacher['profile_picture'] ?? '', '..')); ?>'})" 
                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" 
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                    <img class="person-avatar" src="<?php echo htmlspecialchars(rel_avatar_src($class_teacher['profile_picture'] ?? '', '..')); ?>" alt="Teacher">
                    <div class="person-name"><?php echo htmlspecialchars($class_teacher['username']); ?></div>
                    <div style="font-size:13px; color:#6b7280;"><?php echo !empty($class_teacher['email']) ? htmlspecialchars($class_teacher['email']) : '-'; ?></div>
                    <div style="font-size:13px; color:#6b7280;"><?php echo !empty($class_teacher['phone']) ? htmlspecialchars($class_teacher['phone']) : '-'; ?></div>
                    <?php if (!empty($class_teacher['qualification'])): ?>
                        <div style="font-size:13px; color:#6b7280;"><?php echo htmlspecialchars($class_teacher['qualification']); ?></div>
                    <?php endif; ?>
                    <div class="class-pill" style="margin-top:8px;"><?php echo htmlspecialchars($class_teacher['class_name'] ?? 'My Class'); ?></div>
                    <div style="font-size:12px; color:var(--primary); margin-top:10px; font-weight:600;"><i class="fa-solid fa-arrow-right"></i> Click for details</div>
                </div>
            <?php else: ?>
                <p style="color:#6b7280;">Class teacher is not assigned yet.</p>
            <?php endif; ?>
        </div>

        <!-- Teacher Detail Modal -->
        <div id="teacherModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:white; border-radius:12px; width:90%; max-width:400px; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,0.2); animation:modalSlideIn 0.3s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2 style="margin:0; color:#333;">Teacher Details</h2>
                    <button onclick="closeTeacherModal()" style="background:none; border:none; font-size:24px; color:#999; cursor:pointer;">&times;</button>
                </div>
                <div id="teacherModalContent" style="text-align:center;">
                    <!-- Content filled by JS -->
                </div>
                <button onclick="closeTeacherModal()" style="margin-top:20px; width:100%; padding:10px; background:var(--primary); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Close</button>
            </div>
        </div>

        <style>
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>

        <div class="grid-container">

        </div>

        <div class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3>Upcoming Events</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $e_sql = "SELECT title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5";
                        $e_res = $conn->query($e_sql);
                        if ($e_res->num_rows > 0) {
                            while($row = $e_res->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . date("M j, h:i A", strtotime($row['event_date'])) . "</td>";
                                echo "<td><span class='status pending'>Upcoming</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No upcoming events.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ASSIGNMENTS SECTION -->
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h3>Assignments Due</h3>
            </div>
            <table>
                 <thead>
                    <tr>
                        <th>Title</th>
                        <th>Class</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($class_id) {
                         $a_sql = "SELECT a.title, a.due_date, c.class_name 
                                   FROM assignments a 
                                   LEFT JOIN classes c ON a.class_id = c.id 
                                   WHERE a.class_id = $class_id AND a.due_date >= CURDATE() 
                                   ORDER BY a.due_date ASC LIMIT 5";
                    } else {
                         $a_sql = "SELECT title, due_date, 'All' as class_name FROM assignments WHERE class_id IS NULL AND due_date >= CURDATE()";
                    }

                    $a_res = $conn->query($a_sql);
                    if ($a_res && $a_res->num_rows > 0) {
                        while($row = $a_res->fetch_assoc()) {
                             echo "<tr>";
                             echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                             echo "<td>" . htmlspecialchars($row['class_name'] ?? 'General') . "</td>";
                             echo "<td><span style='color:red;'>" . date("M j", strtotime($row['due_date'])) . "</span></td>";
                             echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center;'>No assignments due.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- RESULTS SECTION -->
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h3>Exam Results</h3>
            </div>
            <?php
                // Fetch Results for both exams
                $exam1_marks = null;
                $exam1_date = null;
                $exam2_marks = null;
                $exam2_date = null;
                
                $r_sql = "SELECT exam_type, marks, updated_at FROM results WHERE student_id = $student_id";
                $r_res = $conn->query($r_sql);
                if ($r_res && $r_res->num_rows > 0) {
                    while($r_row = $r_res->fetch_assoc()) {
                        if ($r_row['exam_type'] === 'exam_1') {
                            $exam1_marks = $r_row['marks'];
                            $exam1_date = date("M j, Y", strtotime($r_row['updated_at']));
                        } elseif ($r_row['exam_type'] === 'exam_2') {
                            $exam2_marks = $r_row['marks'];
                            $exam2_date = date("M j, Y", strtotime($r_row['updated_at']));
                        }
                    }
                }
            ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px;">
                <!-- Exam 1 -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; opacity: 0.9;">Main Exam 1</h4>
                    <?php if ($exam1_marks !== null): ?>
                        <h1 style="font-size: 48px; margin: 0;"><?= $exam1_marks ?><span style="font-size: 24px; opacity: 0.8;">/100</span></h1>
                        <p style="margin: 10px 0 0; font-size: 12px; opacity: 0.8;">Updated: <?= $exam1_date ?></p>
                    <?php else: ?>
                        <p style="margin: 20px 0; opacity: 0.7;">Not Published</p>
                    <?php endif; ?>
                </div>
                
                <!-- Exam 2 -->
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; opacity: 0.9;">Main Exam 2</h4>
                    <?php if ($exam2_marks !== null): ?>
                        <h1 style="font-size: 48px; margin: 0;"><?= $exam2_marks ?><span style="font-size: 24px; opacity: 0.8;">/100</span></h1>
                        <p style="margin: 10px 0 0; font-size: 12px; opacity: 0.8;">Updated: <?= $exam2_date ?></p>
                    <?php else: ?>
                        <p style="margin: 20px 0; opacity: 0.7;">Not Published</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>

    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        RealTimeSync.listen('attendance_updates', () => window.location.reload());
        RealTimeSync.listen('leave_updates', () => window.location.reload());
        RealTimeSync.listen('result_updates', () => window.location.reload());
    </script>
    <script>
        // Auto-refresh dashboard every 60 seconds for live stats
        setInterval(() => {
            window.location.reload();
        }, 60000);

        // Teacher Modal Functions
        function openTeacherModal(teacher) {
            const modal = document.getElementById('teacherModal');
            const content = document.getElementById('teacherModalContent');
            
            const qualText = teacher.qualification ? `<p style="color:#666; font-size:13px;">${teacher.qualification}</p>` : '';
            const emailText = teacher.email ? `<p style="color:#666; font-size:13px;"><i class="fa-solid fa-envelope"></i> ${teacher.email}</p>` : '';
            const phoneText = teacher.phone ? `<p style="color:#666; font-size:13px;"><i class="fa-solid fa-phone"></i> ${teacher.phone}</p>` : '';
            
            content.innerHTML = `
                <img src="${teacher.pic}" alt="${teacher.name}" style="width:80px; height:80px; border-radius:50%; margin-bottom:15px; border:3px solid var(--primary);">
                <h3 style="margin:15px 0 10px 0; color:#333;">${teacher.name}</h3>
                <p style="color:var(--primary); font-weight:600; margin:0 0 15px 0;">${teacher.class}</p>
                ${qualText}
                ${emailText}
                ${phoneText}
            `;
            
            modal.style.display = 'flex';
        }

        function closeTeacherModal() {
            const modal = document.getElementById('teacherModal');
            modal.style.display = 'none';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTeacherModal();
            }
        });

        // Close modal on background click
        document.getElementById('teacherModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTeacherModal();
            }
        });
    </script>

</body>
</html>

