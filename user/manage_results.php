<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

$message = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = intval($_POST['student_id']);
    $marks = intval($_POST['marks']);
    $updated_by = $_SESSION['user_id'];

    // Insert or Update
    $stmt = $conn->prepare("INSERT INTO results (student_id, marks, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE marks = VALUES(marks), updated_by = VALUES(updated_by)");
    $stmt->bind_param("iii", $student_id, $marks, $updated_by);
    
    if ($stmt->execute()) {
        $message = "Results updated successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch Students with Results
$sql = "SELECT u.id, u.username, r.marks, r.updated_at 
        FROM users u 
        LEFT JOIN results r ON u.id = r.student_id 
        WHERE u.role = 'student' AND u.status = 'active'
        ORDER BY u.username ASC";
$students = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Results | Teacher</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .edit-btn { background: #3498db; color: white; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_class.php"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="attendance_history.php"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
            <li><a href="manage_results.php" class="active"><i class="fa-solid fa-chart-line"></i> Results</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Student Results</h2>
            <div class="user-profile"><span><?= htmlspecialchars($_SESSION['username']) ?></span></div>
        </div>

        <?php if ($message): ?>
            <div class="success-msg"><?= $message ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Marks</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $row['marks'] !== null ? $row['marks'] : 'Not Set' ?></td>
                            <td><?= $row['updated_at'] ? date('d M Y', strtotime($row['updated_at'])) : '-' ?></td>
                            <td>
                                <button class="edit-btn" onclick="openEditModal(<?= $row['id'] ?>, '<?= addslashes($row['username']) ?>', <?= $row['marks'] ?? 0 ?>)">
                                    <i class="fa-solid fa-pen"></i> Update
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Marks Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px;">
            <h3>Update Marks: <span id="studentName"></span></h3>
            <form method="POST">
                <input type="hidden" name="student_id" id="studentId">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Marks</label>
                    <input type="number" name="marks" id="marksInput" min="0" max="100" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div style="text-align:right;">
                    <button type="button" onclick="closeEditModal()" style="padding:10px 20px; background:#ccc; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:4px; cursor:pointer;">Save Marks</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, marks) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('studentId').value = id;
            document.getElementById('studentName').innerText = name;
            document.getElementById('marksInput').value = marks;
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
