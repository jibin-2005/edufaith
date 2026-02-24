<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

// Handle Search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = '%' . $search_query . '%';

// Fetch students with search filter
if (!empty($search_query)) {
    // Use prepared statement for search
    $sql = "SELECT u.id, u.username, u.email, c.class_name FROM users u 
            LEFT JOIN classes c ON u.class_id = c.id 
            WHERE u.role = 'student' AND u.username LIKE ? 
            ORDER BY c.class_name ASC, u.username ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch all students
    $sql = "SELECT u.id, u.username, u.email, c.class_name FROM users u 
            LEFT JOIN classes c ON u.class_id = c.id 
            WHERE u.role = 'student' 
            ORDER BY c.class_name ASC, u.username ASC";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #2ecc71;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .action-btn:hover {
            background: #27ae60;
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
                <h2>Manage Students</h2>
                <p>View and manage all students in the Sunday School system.</p>
            </div>
            <div>
                <a href="add_user.php?role=student" class="action-btn"><i class="fa-solid fa-plus"></i> Add New Student</a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="success-msg">
                Student added successfully!
            </div>
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
                        <a href="manage_students.php" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600;"><i class="fa-solid fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
                <?php if (!empty($search_query)): ?>
                    <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">Searching for: <strong><?php echo htmlspecialchars($search_query); ?></strong></p>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Class</th>
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
                                        <span style="color:#aaa; font-style:italic;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>" title="Edit Student">
                                        <i class="fa-solid fa-user-pen" style="color: #3498db; cursor: pointer; margin-right: 15px;"></i>
                                    </a>
                                    <a href="#" onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>')" title="Delete Student">
                                        <i class="fa-solid fa-trash" style="color: #e74c3c; cursor: pointer;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #888;">
                                <?php if (!empty($search_query)): ?>
                                    No students found matching "<?php echo htmlspecialchars($search_query); ?>"
                                <?php else: ?>
                                    No students found.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Student Modal -->
    <div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:450px; margin:100px auto; padding:25px; border-radius:8px;">
            <h3 style="margin-top:0; color:#e74c3c;">Manage Student Account: <span id="deleteStudentName"></span></h3>
            <p style="color:#666; margin-bottom:20px;">Choose an action for this student:</p>
            
            <div style="margin-bottom:20px;">
                <div style="border:1px solid #ddd; padding:15px; border-radius:6px; margin-bottom:10px; cursor:pointer;" onclick="selectDeleteType('soft')" id="softDeleteOption">
                    <input type="radio" name="delete_type" value="soft" id="softRadio">
                    <label for="softRadio" style="cursor:pointer; margin-left:5px;">
                        <strong>Soft Delete (Deactivate)</strong><br>
                        <small style="color:#666;">Set status to inactive. Student cannot login but data is preserved.</small>
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

    <script>
        let currentDeleteId = null;
        
        function openDeleteModal(id, name) {
            currentDeleteId = id;
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('deleteStudentName').innerText = name;
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
            
            if (!confirm(`Are you sure you want to ${deleteType} delete this student?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('student_id', currentDeleteId);
            formData.append('delete_type', deleteType);
            
            try {
                const response = await fetch('../includes/delete_student.php', {
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
</body>
</html>
<?php $conn->close(); ?>

