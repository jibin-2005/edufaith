<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation.php';

$error = "";
$msg = "";

// Handle Announcement Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $validator = new Validator();
    
    $title = $validator->sanitize($_POST['title'] ?? '');
    $content = $validator->sanitize($_POST['content'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    // Validate title - must not be empty and must contain at least one letter
    if (empty($title)) {
        $validator->addError('Title', 'Title is required');
    } elseif (!preg_match('/[a-zA-Z]/', $title)) {
        $validator->addError('Title', 'Title must contain at least one letter (cannot be only numbers)');
    } elseif (strlen($title) < 3) {
        $validator->addError('Title', 'Title must be at least 3 characters');
    } elseif (strlen($title) > 200) {
        $validator->addError('Title', 'Title must be less than 200 characters');
    }
    
    // Validate content - must not be empty
    if (empty($content)) {
        $validator->addError('Content', 'Content/Description is required');
    } elseif (strlen($content) < 10) {
        $validator->addError('Content', 'Content must be at least 10 characters');
    }
    
    if ($validator->isValid()) {
        // Always set target_role to 'all' (available for everyone)
        $target_role = 'all';
        
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, target_role, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $content, $target_role, $created_by);
        
        if ($stmt->execute()) {
            $msg = "Announcement posted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = $validator->getFirstError();
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    header("Location: manage_bulletins.php");
    exit;
}

$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bulletins | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3498db; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { opacity: 0.9; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-delete { color: red; cursor: pointer; border: none; background: none; }
        .alert-success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .char-count { font-size: 12px; color: #666; text-align: right; margin-top: 5px; }
        .required-star { color: red; }
        .bulletin-content { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-church"></i> <span>St. Thomas Church</span></div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Bulletins & Announcements</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (!empty($msg)): ?>
            <p class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <p class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></p>
        <?php endif; ?>

        <div class="form-container">
            <h3><i class="fa-solid fa-plus-circle"></i> Post New Announcement</h3>
            <form method="POST" id="bulletinForm">
                <div class="form-group">
                    <label>Title <span class="required-star">*</span></label>
                    <input type="text" name="title" id="title" required minlength="3" maxlength="200" 
                           placeholder="Enter announcement title (must contain letters)">
                    <div class="char-count"><span id="titleCount">0</span>/200 characters</div>
                </div>
                <div class="form-group">
                    <label>Content <span class="required-star">*</span></label>
                    <textarea name="content" id="content" rows="4" required minlength="10" 
                              placeholder="Enter announcement details (minimum 10 characters)"></textarea>
                    <div class="char-count"><span id="contentCount">0</span> characters (min: 10)</div>
                </div>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                    <i class="fa-solid fa-info-circle"></i> This announcement will be visible to everyone (students, parents, and teachers).
                </p>
                <button type="submit" name="add_announcement" class="btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Post Bulletin
                </button>
            </form>
        </div>

        <div class="table-container">
            <h3><i class="fa-solid fa-list"></i> Recent Bulletins</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td class="bulletin-content" title="<?php echo htmlspecialchars($row['content']); ?>">
                                <?php echo htmlspecialchars($row['content']); ?>
                            </td>
                            <td><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this announcement?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">No bulletins yet. Create your first announcement above!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Character counters
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const titleCount = document.getElementById('titleCount');
        const contentCount = document.getElementById('contentCount');

        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
        });

        contentInput.addEventListener('input', function() {
            contentCount.textContent = this.value.length;
        });

        // Form validation
        document.getElementById('bulletinForm').addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            // Check if title contains at least one letter
            if (!/[a-zA-Z]/.test(title)) {
                e.preventDefault();
                alert('Title must contain at least one letter. It cannot be only numbers.');
                titleInput.focus();
                return false;
            }
            
            // Check title length
            if (title.length < 3) {
                e.preventDefault();
                alert('Title must be at least 3 characters long.');
                titleInput.focus();
                return false;
            }
            
            // Check content
            if (content.length < 10) {
                e.preventDefault();
                alert('Content must be at least 10 characters long.');
                contentInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>
