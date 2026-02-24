<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$role = $_SESSION['role'];

// Handle New Bulletin (Teacher Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement']) && $role === 'teacher') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $target_role = 'all';

    // Validation
    $errors = [];
    $valTitle = Validator::validateTitle($title, 'Title');
    if ($valTitle !== true) $errors[] = $valTitle;
    
    $valContent = Validator::validateDescription($content, 'Content');
    if ($valContent !== true) $errors[] = $valContent;

    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, target_role, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $content, $target_role, $created_by);
        if ($stmt->execute()) {
            header("Location: bulletins.php?msg=posted");
            exit;
        } else {
             header("Location: bulletins.php?error=db_error");
             exit;
        }
        $stmt->close();
    } else {
        $errorStr = implode(", ", $errors);
        header("Location: bulletins.php?error=" . urlencode($errorStr));
        exit;
    }
}

// Fetch all bulletins
$sql = "SELECT title, content, created_at FROM announcements 
        ORDER BY created_at DESC";
$bulletins = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulletins | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bulletin-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        .bulletin-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .bulletin-card .date {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        .bulletin-card .content {
            color: #555;
            line-height: 1.6;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Bulletins & Announcements</h2>
                <p>Stay updated with the latest news from St. Thomas Church</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <!-- Teacher: Add Bulletin Form -->
        <?php if ($role === 'teacher'): ?>
            <div class="bulletin-card" style="border-left: 4px solid #2ecc71; margin-top: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-plus-circle"></i> Post New Announcement</h3>
                
                <?php if (isset($_GET['msg'])): ?>
                    <p style='color:green; background:#e8f5e9; padding:10px; border-radius:4px;'>Announcement posted successfully!</p>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <p style='color:red; background:#fdf2f2; padding:10px; border-radius:4px;'><?php echo htmlspecialchars($_GET['error']); ?></p>
                <?php endif; ?>

                <form method="POST" id="bulletinForm">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:600; margin-bottom:5px;">Title</label>
                        <input type="text" name="title" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:600; margin-bottom:5px;">Content</label>
                        <textarea name="content" rows="3" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-family:inherit;"></textarea>
                    </div>
                    <button type="submit" name="add_announcement" style="background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-weight:600;">Post Bulletin</button>
                </form>
            </div>
            
            <script src="../js/validator.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const rules = {
                        'title': (val) => FormValidator.validateTitle(val, 'Title'),
                        'content': (val) => FormValidator.validateDescription(val, 'Content')
                    };
                    FormValidator.init('#bulletinForm', rules, true);
                });
            </script>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <?php if ($bulletins->num_rows > 0): ?>
                <?php while($row = $bulletins->fetch_assoc()): ?>
                    <div class="bulletin-card">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="date"><i class="fa-regular fa-calendar"></i> <?php echo date("F j, Y", strtotime($row['created_at'])); ?></div>
                        <div class="content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-bullhorn" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No bulletins available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>




