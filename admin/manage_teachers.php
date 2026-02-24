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

// Handle Search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = '%' . $search_query . '%';

// Fetch teachers with search filter
if (!empty($search_query)) {
    // Use prepared statement for search
    $sql = "SELECT u.id, u.username, u.email, c.class_name 
            FROM users u 
            LEFT JOIN classes c ON c.teacher_id = u.id 
            WHERE u.role = 'teacher' AND u.username LIKE ? 
            ORDER BY u.username ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch all teachers
    $sql = "SELECT u.id, u.username, u.email, c.class_name 
            FROM users u 
            LEFT JOIN classes c ON c.teacher_id = u.id 
            WHERE u.role = 'teacher' 
            ORDER BY u.username ASC";
    $result = $conn->query($sql);
}
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
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

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
            <!-- Search Bar -->
            <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex: 1; max-width: 300px;">
                        <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="manage_teachers.php" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600;"><i class="fa-solid fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
                <?php if (!empty($search_query)): ?>
                    <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">Searching for: <strong><?php echo htmlspecialchars($search_query); ?></strong></p>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Teacher ID</th>
                        <th>Name</th>
                        <th>Assigned Class</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $status_info = $conn->query("SELECT status FROM users WHERE id = " . $row['id'])->fetch_assoc();
                            $status = $status_info['status'] ? $status_info['status'] : 'active';
                            $status_class = ($status === 'active') ? 'present' : 'absent';
                        ?>
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
                                <td><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>"><i class="fa-solid fa-user-pen" style="color: #3498db; cursor: pointer; margin-right: 15px;"></i></a>
                                    <a href="#" onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>')">
                                        <i class="fa-solid fa-trash" style="color: #e74c3c; cursor: pointer;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #888;">
                                <?php if (!empty($search_query)): ?>
                                    No teachers found matching "<?php echo htmlspecialchars($search_query); ?>"
                                <?php else: ?>
                                    No teachers found.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Delete Teacher Modal -->
        <div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div style="background:white; width:450px; margin:100px auto; padding:25px; border-radius:8px;">
                <h3 style="margin-top:0; color:#e74c3c;">Manage Teacher Account: <span id="deleteTeacherName"></span></h3>
                <p style="color:#666; margin-bottom:20px;">Choose an action for this teacher:</p>
                
                <div style="margin-bottom:20px;">
                    <div style="border:1px solid #ddd; padding:15px; border-radius:6px; margin-bottom:10px; cursor:pointer;" onclick="selectDeleteType('soft')" id="softDeleteOption">
                        <input type="radio" name="delete_type" value="soft" id="softRadio">
                        <label for="softRadio" style="cursor:pointer; margin-left:5px;">
                            <strong>Soft Delete (Deactivate)</strong><br>
                            <small style="color:#666;">Set status to inactive. Teacher cannot login but data is preserved.</small>
                        </label>
                    </div>
                    
                    <div style="border:1px solid #ddd; padding:15px; border-radius:6px; cursor:pointer;" onclick="selectDeleteType('hard')" id="hardDeleteOption">
                        <input type="radio" name="delete_type" value="hard" id="hardRadio">
                        <label for="hardRadio" style="cursor:pointer; margin-left:5px;">
                            <strong>Hard Delete (Permanent)</strong><br>
                            <small style="color:#666;">Permanently remove from database. This cannot be undone!</small>
                        </label>
                    </div>
                </div>
                
                <div style="text-align:right;">
                    <button type="button" onclick="closeDeleteModal()" style="padding:10px 20px; background:#ccc; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Cancel</button>
                    <button type="button" onclick="confirmDelete()" id="confirmDeleteBtn" style="padding:10px 20px; background:#e74c3c; color:white; border:none; border-radius:4px; cursor:pointer;" disabled>Confirm Action</button>
                </div>
            </div>
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
            let currentDeleteId = null;

            function openAssignModal(id, name) {
                document.getElementById('assignTeacherId').value = id;
                document.getElementById('assignTeacherName').innerText = name;
                document.getElementById('assignModal').style.display = 'block';
            }

            function openDeleteModal(id, name) {
                currentDeleteId = id;
                document.getElementById('deleteModal').style.display = 'block';
                document.getElementById('deleteTeacherName').innerText = name;
                document.getElementById('confirmDeleteBtn').disabled = true;
                document.getElementById('softRadio').checked = false;
                document.getElementById('hardRadio').checked = false;
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').style.display = 'none';
                currentDeleteId = null;
            }

            function selectDeleteType(type) {
                if (type === 'soft') {
                    document.getElementById('softRadio').checked = true;
                    document.getElementById('softDeleteOption').style.borderColor = '#3498db';
                    document.getElementById('hardDeleteOption').style.borderColor = '#ddd';
                } else {
                    document.getElementById('hardRadio').checked = true;
                    document.getElementById('hardDeleteOption').style.borderColor = '#e74c3c';
                    document.getElementById('softDeleteOption').style.borderColor = '#ddd';
                }
                document.getElementById('confirmDeleteBtn').disabled = false;
            }

            async function confirmDelete() {
                const deleteType = document.querySelector('input[name="delete_type"]:checked').value;
                
                if (!confirm(`Are you sure you want to ${deleteType} delete this teacher?`)) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('teacher_id', currentDeleteId);
                formData.append('delete_type', deleteType);
                
                try {
                    const response = await fetch('../includes/delete_teacher.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        </script>
    </div>
</body>
</html>
<?php $conn->close(); ?>

