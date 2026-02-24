<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}
    require '../includes/db.php';
    require '../includes/validation_helper.php';

    // Handle Event Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $event_date = $_POST['event_date'];
        
        // Backend Validation
        $errors = [];
        
        $valTitle = Validator::validateTitle($title, 'Event Title');
        if ($valTitle !== true) $errors[] = $valTitle;

        $valDesc = Validator::validateDescription($description, 'Description');
        if ($valDesc !== true) $errors[] = $valDesc;

        // "No past dates"
        $valDate = Validator::validateDate($event_date, 'Event Date', 'future_only');
        if ($valDate !== true) $errors[] = $valDate;

        if (empty($errors)) {
            $created_by = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $event_date, $description, $created_by);
            if ($stmt->execute()) {
                $msg = "Event created successfully!";
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = implode("<br>", $errors);
        }
    }

    // Handle Event Deletion
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
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
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-delete { color: red; cursor: pointer; border: none; background: none; }
        .error-msg { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
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
        <div class="logout"><a href="../includes/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Manage Events</h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
        </div>

        <?php if (isset($msg)) echo "<p style='color:green; background:#e8f5e9; padding:10px; border-radius:4px;'>$msg</p>"; ?>
        <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>

        <div class="form-container">
            <h3>Add New Event</h3>
            <form method="POST" id="eventForm">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Date & Time</label>
                    <input type="datetime-local" name="event_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" required></textarea>
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
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../js/validator.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rules = {
                'title': (val) => FormValidator.validateTitle(val, 'Event Title'),
                'description': (val) => FormValidator.validateDescription(val, 'Description'),
                'event_date': (val) => FormValidator.validateDate(val, 'Date', 'future_only')
            };
            FormValidator.init('#eventForm', rules, true);
        });
    </script>
</body>
</html>
