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

// Handle Event Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $validator = new Validator();
    
    $title = $validator->sanitize($_POST['title'] ?? '');
    $description = $validator->sanitize($_POST['description'] ?? '');
    $event_date = $_POST['event_date']; // formatting date not needed for sanitization usually, but good to check validity
    $created_by = $_SESSION['user_id'];

    // Validate Title
    if (empty($title)) {
        $validator->addError('Title', 'Title is required');
    } elseif (!preg_match('/[a-zA-Z]/', $title)) {
        $validator->addError('Title', 'Title must contain at least one letter (cannot be only numbers)');
    } elseif (strlen($title) < 3) {
        $validator->addError('Title', 'Title must be at least 3 characters');
    }

    // Validate Description
    if (empty($description)) {
        $validator->addError('Description', 'Description is required');
    } elseif (strlen($description) < 5) {
        $validator->addError('Description', 'Description must be at least 5 characters');
    }

    // Validate Date
    if (empty($event_date)) {
        $validator->addError('Date', 'Event date is required');
    } else {
        $event_timestamp = strtotime($event_date);
        if ($event_timestamp < time()) {
            $validator->addError('Date', 'Event date cannot be in the past');
        }
    }

    if ($validator->isValid()) {
        $sql = "INSERT INTO events (title, event_date, description, created_by) VALUES ('$title', '$event_date', '$description', $created_by)";
        if ($conn->query($sql)) {
            $msg = "Event created successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    } else {
        $error = $validator->getFirstError();
    }
}

// Handle Event Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM events WHERE id = $id");
    header("Location: manage_events.php");
    exit;
}

$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events | Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3498db; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { opacity: 0.9; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-delete { color: red; cursor: pointer; border: none; background: none; }
        .alert-success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .required-star { color: red; }
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
            <li><a href="#" class="active"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="manage_bulletins.php"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
        </ul>
        <div class="logout"><a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Events</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (!empty($msg)): ?>
            <p class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <p class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></p>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Event</h3>
            <form method="POST" id="eventForm">
                <div class="form-group">
                    <label>Event Title <span class="required-star">*</span></label>
                    <input type="text" name="title" id="title" required placeholder="Enter event title (must contain letters)">
                </div>
                <div class="form-group">
                    <label>Date & Time <span class="required-star">*</span></label>
                    <input type="datetime-local" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Description <span class="required-star">*</span></label>
                    <textarea name="description" id="description" rows="3" required placeholder="Event details..."></textarea>
                </div>
                <button type="submit" name="add_event" class="btn-primary">Create Event</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Upcoming Events</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo date("M j, Y h:i A", strtotime($row['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this event?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #999;">No upcoming events found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            // Validate Title: must contain at least one letter
            if (!/[a-zA-Z]/.test(title)) {
                alert('Event title must contain at least one letter (cannot be only numbers).');
                e.preventDefault();
                return false;
            }
            
            if (title.length < 3) {
                alert('Event title must be at least 3 characters long.');
                e.preventDefault();
                return false;
            }

            // Validate Description
            if (description.length === 0) {
                alert('Description is required.');
                e.preventDefault();
                return false;
            }

            // Validate Date (Client-side)
            const eventDate = new Date(document.querySelector('input[name="event_date"]').value);
            const now = new Date();
            if (eventDate < now) {
                alert('Event date cannot be in the past.');
                e.preventDefault();
                return false;
            }
        });

        // Set minimum date to current date-time
        const dateInput = document.querySelector('input[name="event_date"]');
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateInput.min = now.toISOString().slice(0,16);
    </script>
</body>
</html>
