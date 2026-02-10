<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$teacher_id = $_SESSION['user_id'];
$active_exam = $_GET['exam'] ?? 'exam_1'; // Default to exam_1
if (!in_array($active_exam, ['exam_1', 'exam_2'], true)) {
    $active_exam = 'exam_1';
}

// Fetch students from teacher's class with their marks for the selected exam
$sql = "SELECT u.id, u.username, r.marks, r.updated_at 
        FROM users u 
        JOIN classes c ON u.class_id = c.id 
        LEFT JOIN results r ON u.id = r.student_id AND r.exam_type = ?
        WHERE u.role = 'student' AND u.status = 'active' AND c.teacher_id = ?
        ORDER BY u.username ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $active_exam, $teacher_id);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Results | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module">
        import RealTimeSync from '../js/realtime_sync.js';
        RealTimeSync.checkAndTriggerFromURL();
    </script>
    <style>
        .tabs-container { background: white; border-radius: 8px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabs { display: flex; border-bottom: 2px solid #f0f0f0; }
        .tab { flex: 1; padding: 15px 20px; text-align: center; cursor: pointer; background: #fafafa; border: none; font-weight: 600; transition: all 0.3s; color: #666; }
        .tab:hover { background: #f0f0f0; }
        .tab.active { background: white; color: var(--primary); border-bottom: 3px solid var(--primary); }
        .tab.exam1.active { border-bottom-color: #667eea; color: #667eea; }
        .tab.exam2.active { border-bottom-color: #f5576c; color: #f5576c; }
        .table-container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .edit-btn { background: #3498db; color: white; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .exam-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-left: 10px; }
        .exam1-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .exam2-badge { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php" class="active"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
        </ul>
        <div class="logout"><a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Student Results</h2>
            <div class="user-profile"><span><?= htmlspecialchars($_SESSION['username']) ?></span></div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="success-msg"><i class="fa-solid fa-circle-check"></i> Marks updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Error: <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs-container">
            <div class="tabs">
                <a href="?exam=exam_1" class="tab exam1 <?= $active_exam === 'exam_1' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap"></i> Main Exam 1
                </a>
                <a href="?exam=exam_2" class="tab exam2 <?= $active_exam === 'exam_2' ? 'active' : '' ?>">
                    <i class="fa-solid fa-graduation-cap"></i> Main Exam 2
                </a>
            </div>

            <div class="table-container">
                <h3 style="margin: 0 0 20px 0;">
                    <?= $active_exam === 'exam_1' ? 'Main Exam 1' : 'Main Exam 2' ?> Results
                    <span class="exam-badge <?= $active_exam === 'exam_1' ? 'exam1-badge' : 'exam2-badge' ?>">
                        <?= $active_exam === 'exam_1' ? 'Exam 1' : 'Exam 2' ?>
                    </span>
                </h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Marks (out of 100)</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                    <td><?= $row['marks'] !== null ? $row['marks'] . '/100' : '<span style="color:#999;">Not Set</span>' ?></td>
                                    <td><?= $row['updated_at'] ? date('d M Y, h:i A', strtotime($row['updated_at'])) : '-' ?></td>
                                    <td>
                                        <button class="edit-btn" onclick="openEditModal(<?= $row['id'] ?>, '<?= addslashes($row['username']) ?>', <?= $row['marks'] ?? 0 ?>, '<?= $active_exam ?>')">
                                            <i class="fa-solid fa-pen"></i> Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color:#999;">No students found in your class.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Marks Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px;">
            <h3>Update Marks: <span id="studentName"></span></h3>
            <p style="font-size: 13px; color: #666; margin: 5px 0 15px;">Exam: <span id="examLabel"></span></p>
            <form method="POST" action="../includes/save_result_process.php">
                <input type="hidden" name="student_id" id="studentId">
                <input type="hidden" name="exam_type" id="examType">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Marks (0-100)</label>
                    <input type="number" name="marks" id="marksInput" min="0" max="100" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div style="text-align:right;">
                    <button type="button" onclick="closeEditModal()" style="padding:10px 20px; background:#ccc; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:var(--primary); color:white; border:none; border-radius:4px; cursor:pointer;">Save Marks</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, marks, examType) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('studentId').value = id;
            document.getElementById('studentName').innerText = name;
            document.getElementById('marksInput').value = marks;
            document.getElementById('examType').value = examType;
            document.getElementById('examLabel').innerText = examType === 'exam_1' ? 'Main Exam 1' : 'Main Exam 2';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
