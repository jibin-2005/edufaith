<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$admin_id = $_SESSION['user_id'];
$send_error = '';
$send_success = '';

// All parents
$parents = [];
$parent_res = $conn->query("SELECT id, username FROM users WHERE role = 'parent' ORDER BY username ASC");
if ($parent_res) {
    while ($row = $parent_res->fetch_assoc()) {
        $parents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = (int)($_POST['parent_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    $valSubject = Validator::validateTitle($subject, 'Subject');
    if ($valSubject !== true) { $send_error = $valSubject; }

    $valBody = Validator::validateDescription($body, 'Message', 5);
    if ($valBody !== true) { $send_error = $valBody; }

    if (!$send_error) {
        $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'parent'");
        $check->bind_param("i", $recipient_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $send_error = 'Invalid parent selection.';
        }
        $check->close();
    }

    if (!$send_error && $recipient_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $admin_id, $recipient_id, $subject, $body);
        if ($stmt->execute()) {
            $send_success = 'Message sent successfully.';
        } else {
            $send_error = 'Failed to send message.';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $recipient_id = (int)($_POST['recipient_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    $valSubject = Validator::validateTitle($subject, 'Subject');
    if ($valSubject !== true) { $send_error = $valSubject; }

    $valBody = Validator::validateDescription($body, 'Message', 5);
    if ($valBody !== true) { $send_error = $valBody; }

    if (!$send_error && $recipient_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $admin_id, $recipient_id, $subject, $body);
        if ($stmt->execute()) {
            $send_success = 'Reply sent successfully.';
        } else {
            $send_error = 'Failed to send reply.';
        }
        $stmt->close();
    }
}

$stmt_in = $conn->prepare("SELECT m.*, u.username AS sender_name, u.role AS sender_role 
                           FROM messages m JOIN users u ON m.sender_id = u.id 
                           WHERE m.recipient_id = ? ORDER BY m.created_at DESC LIMIT 100");
$stmt_in->bind_param("i", $admin_id);
$stmt_in->execute();
$inbox = $stmt_in->get_result();

$mark = $conn->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ?");
$mark->bind_param("i", $admin_id);
$mark->execute();
$mark->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid var(--primary); }
        .reply-box { margin-top: 12px; background: #f9fafb; padding: 12px; border-radius: 8px; }
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
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="manage_bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
            <li><a href="attendance_admin.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
            <li><a href="messages_admin.php" class="active"><i class="fa-solid fa-envelope"></i> Messages</a></li>
        </ul>
        <div class="logout"><a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Parent Messages</h2>
            <div class="user-profile"><span>Administrator</span></div>
        </div>

        <?php if ($send_success): ?>
            <p style="color:green;"><?php echo htmlspecialchars($send_success); ?></p>
        <?php endif; ?>
        <?php if ($send_error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($send_error); ?></p>
        <?php endif; ?>

        <div class="message-card">
            <h3>Send Message to Parent</h3>
            <form method="POST">
                <div style="margin-bottom:8px;">
                    <label>Parent</label>
                    <select name="parent_id" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                        <option value="">Select Parent</option>
                        <?php foreach ($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label>Subject</label>
                    <input type="text" name="subject" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div style="margin-bottom:8px;">
                    <label>Message</label>
                    <textarea name="body" rows="3" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"></textarea>
                </div>
                <button type="submit" name="send_message" style="background:var(--primary); color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600;">Send Message</button>
            </form>
        </div>

        <?php if ($inbox->num_rows > 0): ?>
            <?php while($row = $inbox->fetch_assoc()): ?>
                <div class="message-card">
                    <h3><?php echo htmlspecialchars($row['subject']); ?></h3>
                    <p style="color:#777; font-size:12px; margin-bottom:10px;">
                        From <?php echo htmlspecialchars($row['sender_name']); ?> (<?php echo htmlspecialchars($row['sender_role']); ?>) • 
                        <?php echo date("M j, Y h:i A", strtotime($row['created_at'])); ?>
                    </p>
                    <p><?php echo nl2br(htmlspecialchars($row['body'])); ?></p>

                    <div class="reply-box">
                        <form method="POST">
                            <input type="hidden" name="recipient_id" value="<?php echo (int)$row['sender_id']; ?>">
                            <div style="margin-bottom:8px;">
                                <label>Subject</label>
                                <input type="text" name="subject" value="Re: <?php echo htmlspecialchars($row['subject']); ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>Reply</label>
                                <textarea name="body" rows="3" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"></textarea>
                            </div>
                            <button type="submit" name="reply_message" style="background:var(--primary); color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600;">Send Reply</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
