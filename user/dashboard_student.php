<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_student.php" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="my_lessons.php"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="achievements.php"><i class="fa-solid fa-star"></i> Achievements</a></li>
            <li><a href="calendar.php"><i class="fa-solid fa-calendar-check"></i> Calendar</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>“Thy word is a lamp unto my feet.” — Psalm 119:105</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Student">
                </div>
            </div>
        </div>

        <div class="grid-container">
            <!-- MEMORY VERSE HERO -->
            <div class="card" style="grid-column: span 2; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white;">
                <div class="card-info">
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Verse of the Week</span>
                    <h3 style="color: white; font-size: 24px; margin: 10px 0;">"For God so loved the world..."</h3>
                    <p style="color: rgba(255,255,255,0.9);">John 3:16</p>
                    <button style="margin-top: 15px; padding: 8px 16px; border: none; border-radius: 20px; background: white; color: var(--primary); font-weight: 600; cursor: pointer;">Mark Recited</button>
                </div>
                <div class="card-icon" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3>92%</h3>
                    <p>Attendance</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3>Upcoming Events</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $e_sql = "SELECT title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5";
                        $e_res = $conn->query($e_sql);
                        if ($e_res->num_rows > 0) {
                            while($row = $e_res->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . date("M j, h:i A", strtotime($row['event_date'])) . "</td>";
                                echo "<td><span class='status pending'>Upcoming</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No upcoming events.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>My Badges</h3>
                </div>
                <!-- Static Badges (placeholder logic could be expanded) -->
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <i class="fa-solid fa-medal" style="font-size: 32px; color: gold;" title="Perfect Attendance"></i>
                    <i class="fa-solid fa-star" style="font-size: 32px; color: orange;" title="Verse Master"></i>
                    <i class="fa-solid fa-hands-praying" style="font-size: 32px; color: #3498db;" title="Helper"></i>
                </div>
            </div>
        </div>
        
        <!-- ASSIGNMENTS SECTION -->
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h3>Assignments Due</h3>
            </div>
            <table>
                 <thead>
                    <tr>
                        <th>Title</th>
                        <th>Grade</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display assignments
                    $a_sql = "SELECT title, target_grade, due_date FROM assignments WHERE due_date >= CURDATE() ORDER BY due_date ASC LIMIT 5";
                    $a_res = $conn->query($a_sql);
                    if ($a_res->num_rows > 0) {
                        while($row = $a_res->fetch_assoc()) {
                             echo "<tr>";
                             echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                             echo "<td>" . htmlspecialchars($row['target_grade']) . "</td>";
                             echo "<td><span style='color:red;'>" . date("M j", strtotime($row['due_date'])) . "</span></td>";
                             echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center;'>No assignments due.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>
