<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'parent'])) {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
require '../includes/validation_helper.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user's section
$user_section = null;
if ($role === 'student') {
    $user_section = Validator::getStudentSection($conn, $user_id);
} elseif ($role === 'teacher') {
    $user_section = Validator::getTeacherSection($conn, $user_id);
}

// Fetch events based on role and section
if ($role === 'student' && $user_section) {
    // Students see only their section events
    $stmt = $conn->prepare("SELECT e.*, s.section_name, s.class_range,
                           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                           (SELECT id FROM event_registrations WHERE event_id = e.id AND student_id = ?) as is_registered
                           FROM events e
                           LEFT JOIN sections s ON e.section_id = s.id
                           WHERE e.section_id = ?
                           ORDER BY e.event_date DESC");
    $stmt->bind_param("ii", $user_id, $user_section);
    $stmt->execute();
    $events = $stmt->get_result();
} elseif ($role === 'teacher' && $user_section) {
    // Teachers see their section events
    $stmt = $conn->prepare("SELECT e.*, s.section_name, s.class_range,
                           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                           FROM events e
                           LEFT JOIN sections s ON e.section_id = s.id
                           WHERE e.section_id = ?
                           ORDER BY e.event_date DESC");
    $stmt->bind_param("i", $user_section);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    // Parents or users without section see all events
    $events = $conn->query("SELECT e.*, s.section_name, s.class_range,
                           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                           FROM events e
                           LEFT JOIN sections s ON e.section_id = s.id
                           ORDER BY e.event_date DESC");
}

// Get section info for display
$section_info = null;
if ($user_section) {
    $stmt_section = $conn->prepare("SELECT * FROM sections WHERE id = ?");
    $stmt_section->bind_param("i", $user_section);
    $stmt_section->execute();
    $section_info = $stmt_section->get_result()->fetch_assoc();
    $stmt_section->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events | Sunday School</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .section-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
        }
        .section-little-flower { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #2d3436; }
        .section-dominic-savio { background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; }
        .section-alphonsa { background: linear-gradient(135deg, #a29bfe, #6c5ce7); color: white; }
        .section-st-thomas { background: linear-gradient(135deg, #fd79a8, #e84393); color: white; }
        
        .event-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .registered-badge {
            background: #00b894;
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .section-info-banner {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h2>Events</h2>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <?php if ($section_info && $role === 'student'): ?>
            <div class="section-info-banner">
                <h3 style="margin: 0 0 10px 0;">
                    <i class="fa-solid fa-users"></i> Your Section: <?php echo htmlspecialchars($section_info['section_name']); ?>
                </h3>
                <p style="margin: 0; opacity: 0.9;">
                    <?php echo htmlspecialchars($section_info['class_range']); ?> | 
                    Showing events for your section only
                </p>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h3>Available Events</h3>
            </div>

            <?php if ($events && $events->num_rows > 0): ?>
                <?php while($event = $events->fetch_assoc()): 
                    $section_class = 'section-' . strtolower(str_replace(' ', '-', $event['section_name']));
                    $is_past = strtotime($event['event_date']) < time();
                ?>
                    <div class="event-card">
                        <div class="event-header">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 10px 0;">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                    <span class="section-badge <?php echo $section_class; ?>">
                                        <?php echo htmlspecialchars($event['section_name']); ?>
                                    </span>
                                    <?php if ($role === 'student' && isset($event['is_registered']) && $event['is_registered']): ?>
                                        <span class="registered-badge">
                                            <i class="fa-solid fa-check-circle"></i> Registered
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <p style="margin: 0 0 10px 0; color: #666;">
                                    <i class="fa-solid fa-calendar"></i> <?php echo date('l, F j, Y', strtotime($event['event_date'])); ?>
                                    <br>
                                    <i class="fa-solid fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                                </p>
                                <p style="margin: 0; color: #555;">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                <p style="margin: 10px 0 0 0; color: #999; font-size: 13px;">
                                    <i class="fa-solid fa-users"></i> <?php echo $event['registration_count']; ?> students registered
                                </p>
                            </div>
                            <div>
                                <?php if ($role === 'student'): ?>
                                    <?php if ($is_past): ?>
                                        <button class="btn-secondary" disabled style="opacity: 0.5;">
                                            <i class="fa-solid fa-clock"></i> Event Ended
                                        </button>
                                    <?php elseif (isset($event['is_registered']) && $event['is_registered']): ?>
                                        <button class="btn-primary" disabled style="opacity: 0.7;">
                                            <i class="fa-solid fa-check"></i> Already Registered
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-primary" onclick="registerForEvent(<?php echo $event['id']; ?>, '<?php echo addslashes($event['title']); ?>')">
                                            <i class="fa-solid fa-user-plus"></i> Register Now
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 60px; color: #999;">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    No events available for your section at the moment.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function registerForEvent(eventId, eventTitle) {
            if (!confirm(`Register for "${eventTitle}"?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('event_id', eventId);

                const response = await fetch('../includes/event_registration_process_new.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Registration failed. Please try again.');
            }
        }
    </script>
</body>
</html>
