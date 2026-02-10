<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
require '../includes/db.php';
require '../includes/validation_helper.php';

$role = $_SESSION['role'];

// Handle New Event (Teacher Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event']) && $role === 'teacher') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];

    // Validation
    $errors = [];
    $valTitle = Validator::validateTitle($title, 'Event Title');
    if ($valTitle !== true) $errors[] = $valTitle;

    $valDesc = Validator::validateDescription($description, 'Description');
    if ($valDesc !== true) $errors[] = $valDesc;

    $valDate = Validator::validateDate($event_date, 'Event Date', 'future_only');
    if ($valDate !== true) $errors[] = $valDate;

    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $event_date, $description, $created_by);
        if ($stmt->execute()) {
            header("Location: events.php?msg=created");
            exit;
        } else {
             header("Location: events.php?error=db_error");
             exit;
        }
        $stmt->close();
    } else {
        $errorStr = implode(", ", $errors);
        header("Location: events.php?error=" . urlencode($errorStr));
        exit;
    }
}

// Fetch upcoming events
$sql = "SELECT title, event_date, description FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .event-date-box {
            background: linear-gradient(135deg, var(--primary), #5a4fcf);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            min-width: 70px;
        }
        .event-date-box .day {
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }
        .event-date-box .month {
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .event-details h3 {
            margin: 0 0 8px 0;
            color: #333;
        }
        .event-details .time {
            font-size: 13px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .event-details .description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
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
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <?php if ($role === 'student'): ?>
                <li><a href="dashboard_student.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_student.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                <li><a href="leave_student.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
                <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
                <li><a href="view_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'teacher'): ?>
                <li><a href="dashboard_teacher.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_teacher.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                <li><a href="manage_leaves.php"><i class="fa-solid fa-envelope-open-text"></i> Leave Requests</a></li>
                <li><a href="manage_assignments.php"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
                <li><a href="manage_results.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php elseif ($role === 'parent'): ?>
                <li><a href="dashboard_parent.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                <li><a href="attendance_parent.php"><i class="fa-solid fa-calendar-check"></i> Child Attendance</a></li>
                <li><a href="my_children.php"><i class="fa-solid fa-users"></i> My Children</a></li>
            <li><a href="results_parent.php"><i class="fa-solid fa-chart-line"></i> Results</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
                <li><a href="bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
                <li><a href="events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <?php endif; ?>
        </ul>
        <div class="logout">
            <a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Upcoming Events</h2>
                <p>Church events and activities calendar</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="User">
                </div>
            </div>
        </div>

        <!-- Teacher: Add Event Form -->
        <?php if ($role === 'teacher'): ?>
            <div class="event-card" style="display:block; border-left: 4px solid var(--primary);">
                <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-calendar-plus"></i> Create New Event</h3>
                
                <?php if (isset($_GET['msg'])): ?>
                    <p style='color:green; background:#e8f5e9; padding:10px; border-radius:4px;'>Event created successfully!</p>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <p style='color:red; background:#fdf2f2; padding:10px; border-radius:4px;'><?php echo htmlspecialchars($_GET['error']); ?></p>
                <?php endif; ?>

                <form method="POST" id="eventForm">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:600; margin-bottom:5px;">Event Title</label>
                        <input type="text" name="title" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:600; margin-bottom:5px;">Date & Time</label>
                        <input type="datetime-local" name="event_date" required min="<?php echo date('Y-m-d\TH:i'); ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight:600; margin-bottom:5px;">Description</label>
                        <textarea name="description" rows="3" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; font-family:inherit;"></textarea>
                    </div>
                    <button type="submit" name="add_event" style="background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-weight:600;">Create Event</button>
                </form>
            </div>
            
            <script src="../js/validator.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const rules = {
                        'title': (val) => FormValidator.validateTitle(val, 'Event Title'),
                        'description': (val) => FormValidator.validateDescription(val, 'Description'),
                        'event_date': (val) => FormValidator.validateDate(val, 'Event Date', 'future_only')
                    };
                    FormValidator.init('#eventForm', rules, true);
                });
            </script>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-date-box">
                            <div class="day"><?php echo date("j", strtotime($row['event_date'])); ?></div>
                            <div class="month"><?php echo date("M", strtotime($row['event_date'])); ?></div>
                        </div>
                        <div class="event-details">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="time"><i class="fa-regular fa-clock"></i> <?php echo date("l, g:i A", strtotime($row['event_date'])); ?></div>
                            <?php if (!empty($row['description'])): ?>
                                <div class="description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>No upcoming events scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>



