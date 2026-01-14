<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

// --- DELETE TEACHER ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if the user is indeed a teacher to avoid accidental deletion of admins
    $checkRole = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $checkRole->bind_param("i", $delete_id);
    $checkRole->execute();
    $roleResult = $checkRole->get_result();
    
    if ($roleResult->num_rows > 0) {
        $userObj = $roleResult->fetch_assoc();
        if ($userObj['role'] === 'teacher') {
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->bind_param("i", $delete_id);
            if ($deleteStmt->execute()) {
                header("Location: manage_teachers.php?msg=deleted");
                exit;
            }
        }
    }
}

// Fetch teachers
// Fetch teachers with their assigned class
$sql = "SELECT u.id, u.username, u.email, c.class_name 
        FROM users u 
        LEFT JOIN classes c ON c.teacher_id = u.id 
        WHERE u.role = 'teacher' 
        ORDER BY u.username ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .action-btn:hover {
            background: #2980b9;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php" class="active"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Parents</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Manage Teachers</h2>
                <p>View and manage all teachers in the Sunday School system.</p>
            </div>
            <div>
                <a href="add_user.php?role=teacher" class="action-btn"><i class="fa-solid fa-plus"></i> Add New Teacher</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'success'): ?>
                <div class="success-msg">Teacher added successfully!</div>
            <?php elseif ($_GET['msg'] == 'deleted'): ?>
                <div class="success-msg" style="background:#f8d7da; color:#721c24;">Teacher deleted successfully!</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Teacher ID</th>
                        <th>Name</th>
                        <th>Assigned Class</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <?php if($row['class_name']): ?>
                                        <span class="badge" style="background:#e8f4fc; color:#2c3e50; padding:4px 8px; border-radius:4px; font-size:12px;">
                                            <?php echo htmlspecialchars($row['class_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-style:italic;">None</span>
                                    <?php endif; ?>
                                    <a href="#" onclick="openAssignModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>')" style="font-size:12px; margin-left:5px; text-decoration:underline;">Change</a>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>"><i class="fa-solid fa-user-pen" style="color: #3498db; cursor: pointer; margin-right: 15px;"></i></a>
                                    <a href="manage_teachers.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this teacher?');">
                                        <i class="fa-solid fa-trash" style="color: #e74c3c; cursor: pointer;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #888;">No teachers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Assign Class Modal -->
        <div id="assignModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px;">
                <h3 style="margin-top:0;">Assign Class to <span id="assignTeacherName"></span></h3>
                <form action="assign_class.php" method="POST">
                    <input type="hidden" name="teacher_id" id="assignTeacherId">
                    <div style="margin-bottom:20px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Select Class:</label>
                        <select name="class_id" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #ddd;">
                            <option value="">-- Select Class --</option>
                            <?php 
                            // Fetch all classes again to populate dropdown
                            $class_res = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
                            while($c = $class_res->fetch_assoc()) {
                                echo "<option value='".$c['id']."'>".htmlspecialchars($c['class_name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div style="text-align:right;">
                        <button type="button" onclick="document.getElementById('assignModal').style.display='none'" style="padding:10px 20px; background:#ccc; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Cancel</button>
                        <button type="submit" style="padding:10px 20px; background:#2ecc71; color:white; border:none; border-radius:4px; cursor:pointer;">Assign</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAssignModal(id, name) {
                document.getElementById('assignTeacherId').value = id;
                document.getElementById('assignTeacherName').innerText = name;
                document.getElementById('assignModal').style.display = 'block';
            }
        </script>
    </div>
</body>
</html>
<?php $conn->close(); ?>
