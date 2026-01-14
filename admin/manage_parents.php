<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';

// Handle Linking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_child'])) {
    $parent_id = $_POST['parent_id'];
    $student_id = $_POST['student_id'];
    
    // Check if link exists
    $check = $conn->query("SELECT id FROM parent_student WHERE parent_id=$parent_id AND student_id=$student_id");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $parent_id, $student_id);
        if ($stmt->execute()) {
            $msg = "Child linked successfully.";
        } else {
            $error = "Error linking child.";
        }
    } else {
        $error = "This child is already linked to this parent.";
    }
}

// Handle Unlink
if (isset($_GET['unlink'])) {
    $link_id = intval($_GET['unlink']);
    $conn->query("DELETE FROM parent_student WHERE id=$link_id");
    $msg = "Child unlinked successfully.";
}

// Handle Parent Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'parent'");
    $msg = "Parent deleted successfully.";
}

// Fetch Parents and their Children
$sql = "SELECT users.id, users.username, users.email, 
        GROUP_CONCAT(CONCAT(s.username, ' (ID:', s.id, '):', ps.id) SEPARATOR '||') as children
        FROM users 
        LEFT JOIN parent_student ps ON users.id = ps.parent_id
        LEFT JOIN users s ON ps.student_id = s.id
        WHERE users.role = 'parent' 
        GROUP BY users.id 
        ORDER BY users.username ASC";
$result = $conn->query($sql);

// Fetch Students for Dropdown
$students = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC");
$student_options = [];
while($s = $students->fetch_assoc()) {
    $student_options[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Parents | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .action-btn { margin-right: 5px; color: #555; cursor: pointer; }
        .action-btn:hover { color: var(--primary); }
        .add-btn { background: var(--primary); color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; margin-bottom: 15px; display: inline-block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_classes.php"><i class="fa-solid fa-chalkboard"></i> Classes</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php" class="active"><i class="fa-solid fa-users"></i> Parents</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Parents</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>
        
        <?php if (isset($msg)) echo "<p style='color:green; padding:10px; background:#e8f5e9; margin-bottom:15px;'>$msg</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red; padding:10px; background:#f8d7da; margin-bottom:15px;'>$error</p>"; ?>

        <a href="add_user.php?role=parent" class="add-btn"><i class="fa-solid fa-plus"></i> Add New Parent</a>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Linked Children</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php 
                                    if ($row['children']) {
                                        $kids = explode('||', $row['children']);
                                        foreach($kids as $k) {
                                            if(strpos($k, ':') === false) continue;
                                            list($name, $link_id) = explode(':', $k); // Simple parse might fail on name colons, but username is usually safe
                                            // A fast robust way is tricky in MySQL GROUP_CONCAT with colons. 
                                            // Let's assume username doesn't contain ':'. 
                                            echo "<span style='display:inline-block; background:#e3f2fd; color:#1565c0; padding:2px 8px; border-radius:12px; font-size:12px; margin-right:5px; margin-bottom:2px;'>
                                                    $name <a href='?unlink=$link_id' onclick='return confirm(\"Unlink this child?\")' style='color:#c62828; margin-left:5px;'>&times;</a>
                                                  </span>";
                                        }
                                    } else {
                                        echo "<span style='color:#999; font-style:italic;'>No children linked</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button onclick="openLinkModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>')" class="action-btn" style="background:#8e44ad; color:white; padding:5px 10px; font-size:12px; border:none; border-radius:4px;">
                                        <i class="fa-solid fa-link"></i> Link Child
                                    </button>
                                    <a href="edit_parent.php?id=<?php echo $row['id']; ?>" class="action-btn"><i class="fa-solid fa-pen-to-square" style="color:#3498db;"></i></a>
                                    <button onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>')" class="action-btn" style="background:transparent; border:none; cursor:pointer;"><i class="fa-solid fa-trash" style="color:#e74c3c;"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No parents found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Link Child Modal -->
    <div id="linkModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:400px; margin:100px auto; padding:20px; border-radius:8px;">
            <h3>Link Child to <span id="parentName"></span></h3>
            <form method="POST">
                <input type="hidden" name="link_child" value="1">
                <input type="hidden" name="parent_id" id="parentIdField">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Select Student</label>
                    <select name="student_id" required style="width:100%; padding:8px;">
                        <option value="">-- Choose Student --</option>
                        <?php foreach($student_options as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="text-align:right;">
                    <button type="button" onclick="document.getElementById('linkModal').style.display='none'" style="padding:8px 15px; background:#ccc; border:none; border-radius:4px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:8px 15px; background:var(--primary); color:white; border:none; border-radius:4px; cursor:pointer;">Link Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Parent Modal -->
    <div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:450px; margin:100px auto; padding:25px; border-radius:8px;">
            <h3 style="margin-top:0; color:#e74c3c;">Delete Parent: <span id="deleteParentName"></span></h3>
            <p style="color:#666; margin-bottom:20px;">Choose how to delete this parent account:</p>
            
            <div style="margin-bottom:20px;">
                <div style="border:1px solid #ddd; padding:15px; border-radius:6px; margin-bottom:10px; cursor:pointer;" onclick="selectDeleteType('soft')" id="softDeleteOption">
                    <input type="radio" name="delete_type" value="soft" id="softRadio">
                    <label for="softRadio" style="cursor:pointer; margin-left:5px;">
                        <strong>Soft Delete (Deactivate)</strong><br>
                        <small style="color:#666;">Set status to inactive. Parent cannot login but data is preserved.</small>
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
                <button type="button" onclick="confirmDelete()" id="confirmDeleteBtn" style="padding:10px 20px; background:#e74c3c; color:white; border:none; border-radius:4px; cursor:pointer;" disabled>Delete Parent</button>
            </div>
        </div>
    </div>

    <script>
        let currentDeleteId = null;
        
        function openLinkModal(id, name) {
            document.getElementById('linkModal').style.display = 'block';
            document.getElementById('parentIdField').value = id;
            document.getElementById('parentName').innerText = name;
        }
        
        function openDeleteModal(id, name) {
            currentDeleteId = id;
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('deleteParentName').innerText = name;
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
            
            if (!confirm(`Are you sure you want to ${deleteType} delete this parent?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('parent_id', currentDeleteId);
            formData.append('delete_type', deleteType);
            
            try {
                const response = await fetch('../includes/delete_parent.php', {
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
