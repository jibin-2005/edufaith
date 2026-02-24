<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$parent_id = $_SESSION['user_id'];
$send_error = '';
$send_success = '';

// Fetch linked children for selector
$sql_children = "SELECT u.id, u.username, c.teacher_id 
                 FROM users u 
                 JOIN parent_student ps ON u.id = ps.student_id 
                 LEFT JOIN classes c ON u.class_id = c.id
                 WHERE ps.parent_id = ?";
$stmt_kids = $conn->prepare($sql_children);
$stmt_kids->bind_param("i", $parent_id);
$stmt_kids->execute();
$children_res = $stmt_kids->get_result();
$children = [];
while ($row = $children_res->fetch_assoc()) {
    $children[] = $row;
}
$stmt_kids->close();

// Fetch admins for dropdown
$admins = [];
$admin_res = $conn->query("SELECT id, username FROM users WHERE role = 'admin' ORDER BY username ASC");
if ($admin_res) {
    while ($row = $admin_res->fetch_assoc()) {
        $admins[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $recipient_type = $_POST['recipient_type'] ?? '';
    $child_id = (int)($_POST['child_id'] ?? 0);
    $admin_id = (int)($_POST['admin_id'] ?? 0);

    $valSubject = Validator::validateTitle($subject, 'Subject');
    if ($valSubject !== true) { $send_error = $valSubject; }

    $valBody = Validator::validateDescription($body, 'Message', 5);
    if ($valBody !== true) { $send_error = $valBody; }

    $recipient_id = 0;

    if (!$send_error) {
        if ($recipient_type === 'teacher') {
            $check = $conn->prepare("SELECT c.teacher_id 
                                     FROM parent_student ps 
                                     JOIN users u ON ps.student_id = u.id 
                                     LEFT JOIN classes c ON u.class_id = c.id
                                     WHERE ps.parent_id = ? AND ps.student_id = ?");
            $check->bind_param("ii", $parent_id, $child_id);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();
            if ($row && !empty($row['teacher_id'])) {
                $recipient_id = (int)$row['teacher_id'];
            } else {
                $send_error = 'Selected child does not have an assigned teacher.';
            }
        } elseif ($recipient_type === 'admin') {
            if ($admin_id > 0) {
                $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
                $check->bind_param("i", $admin_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $recipient_id = $admin_id;
                } else {
                    $send_error = 'Selected admin is invalid.';
                }
                $check->close();
            } else {
                $send_error = 'Please select an admin recipient.';
            }
        } else {
            $send_error = 'Please select a recipient type.';
        }
    }

    if (!$send_error && $recipient_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $parent_id, $recipient_id, $subject, $body);
        if ($stmt->execute()) {
            $send_success = 'Message sent successfully.';
        } else {
            $send_error = 'Failed to send message.';
        }
        $stmt->close();
    }
}

// Inbox
$stmt_in = $conn->prepare("SELECT m.*, u.username AS sender_name, u.role AS sender_role 
                           FROM messages m JOIN users u ON m.sender_id = u.id 
                           WHERE m.recipient_id = ? ORDER BY m.created_at DESC LIMIT 50");
$stmt_in->bind_param("i", $parent_id);
$stmt_in->execute();
$inbox = $stmt_in->get_result();

// Sent
$stmt_out = $conn->prepare("SELECT m.*, u.username AS recipient_name, u.role AS recipient_role 
                            FROM messages m JOIN users u ON m.recipient_id = u.id 
                            WHERE m.sender_id = ? ORDER BY m.created_at DESC LIMIT 50");
$stmt_out->bind_param("i", $parent_id);
$stmt_out->execute();
$sent = $stmt_out->get_result();

// Mark all inbox as read
$conn->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ?");
$mark = $conn->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ?");
$mark->bind_param("i", $parent_id);
$mark->execute();
$mark->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | Parent</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid var(--primary); }
        .tabs { display: flex; gap: 10px; margin-bottom: 15px; }
        .tab-btn { padding: 8px 14px; border: none; border-radius: 20px; background: #f0f4f8; cursor: pointer; font-weight: 600; }
        .tab-btn.active { background: var(--primary); color: #fff; }
        .form-panel { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Messages</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <div class="form-panel">
            <h3 style="margin-top:0;">Send a Message</h3>
            <?php if ($send_success): ?>
                <p style="color:green;"><?php echo htmlspecialchars($send_success); ?></p>
            <?php endif; ?>
            <?php if ($send_error): ?>
                <p style="color:red;"><?php echo htmlspecialchars($send_error); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Recipient Type</label>
                        <select name="recipient_type" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                            <option value="">Select</option>
                            <option value="teacher">Class Teacher</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label>Child (for Teacher)</label>
                        <select name="child_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                            <option value="">Select Child</option>
                            <?php foreach ($children as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label>Administrator (if selected)</label>
                    <select name="admin_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                        <option value="">Select Admin</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-top:12px;">
                    <label>Subject</label>
                    <input type="text" name="subject" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div style="margin-top:12px;">
                    <label>Message</label>
                    <textarea name="body" rows="4" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;"></textarea>
                </div>
                <button type="submit" name="send_message" style="margin-top:12px; background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600;">Send</button>
            </form>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('inbox')">Inbox</button>
            <button class="tab-btn" onclick="showTab('sent')">Sent</button>
        </div>

        <div id="inbox">
            <?php if ($inbox->num_rows > 0): ?>
                <?php while($row = $inbox->fetch_assoc()): ?>
                    <div class="message-card">
                        <h3><?php echo htmlspecialchars($row['subject']); ?></h3>
                        <p style="color:#777; font-size:12px; margin-bottom:10px;">
                            From <?php echo htmlspecialchars($row['sender_name']); ?> (<?php echo htmlspecialchars($row['sender_role']); ?>) • 
                            <?php echo date("M j, Y h:i A", strtotime($row['created_at'])); ?>
                        </p>
                        <p><?php echo nl2br(htmlspecialchars($row['body'])); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No inbox messages.</p>
            <?php endif; ?>
        </div>

        <div id="sent" style="display:none;">
            <?php if ($sent->num_rows > 0): ?>
                <?php while($row = $sent->fetch_assoc()): ?>
                    <div class="message-card">
                        <h3><?php echo htmlspecialchars($row['subject']); ?></h3>
                        <p style="color:#777; font-size:12px; margin-bottom:10px;">
                            To <?php echo htmlspecialchars($row['recipient_name']); ?> (<?php echo htmlspecialchars($row['recipient_role']); ?>) • 
                            <?php echo date("M j, Y h:i A", strtotime($row['created_at'])); ?>
                        </p>
                        <p><?php echo nl2br(htmlspecialchars($row['body'])); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No sent messages.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.getElementById('inbox').style.display = tab === 'inbox' ? 'block' : 'none';
            document.getElementById('sent').style.display = tab === 'sent' ? 'block' : 'none';
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            buttons[tab === 'inbox' ? 0 : 1].classList.add('active');
        }
    </script>
</body>
</html>


