<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<<<<<<< HEAD
    <title>Student Portal | Sunday School</title>
=======
<<<<<<< HEAD
    <title>Student Portal | Sunday School</title>
=======
    <title>Student Portal | St. Thomas Church Kanamala</title>
>>>>>>> 85623df (Initial commit - Sunday School Management System)
>>>>>>> 7e1952f (09/01/2026)
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
<<<<<<< HEAD
            <span>Grace Valley</span>
=======
<<<<<<< HEAD
            <span>Grace Valley</span>
=======
            <span>St. Thomas Church</span>
>>>>>>> 85623df (Initial commit - Sunday School Management System)
>>>>>>> 7e1952f (09/01/2026)
        </div>
        <ul class="menu">
            <li><a href="#" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa-solid fa-book-bible"></i> My Lessons</a></li>
            <li><a href="#"><i class="fa-solid fa-star"></i> Achievements</a></li>
            <li><a href="#"><i class="fa-solid fa-calendar-check"></i> Calendar</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> 7e1952f (09/01/2026)
                <h2>Welcome back, Sarah</h2>
                <p>“Thy word is a lamp unto my feet.” — Psalm 119:105</p>
            </div>
            <div class="user-profile">
                <span>Sarah Miller</span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=Sarah+Miller&background=random" alt="Student">
<<<<<<< HEAD
=======
=======
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>“Thy word is a lamp unto my feet.” — Psalm 119:105</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Student">
>>>>>>> 85623df (Initial commit - Sunday School Management System)
>>>>>>> 7e1952f (09/01/2026)
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
                    <h3>Upcoming Classes</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Old Testament History</td>
                            <td>Mrs. Thompson</td>
                            <td>Sunday, 9:00 AM</td>
                            <td><span class="status pending">Upcoming</span></td>
                        </tr>
                        <tr>
                            <td>Choir Practice</td>
                            <td>Mr. David</td>
                            <td>Sunday, 10:30 AM</td>
                            <td><span class="status pending">Upcoming</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>My Badges</h3>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <i class="fa-solid fa-medal" style="font-size: 32px; color: gold;" title="Perfect Attendance"></i>
                    <i class="fa-solid fa-star" style="font-size: 32px; color: orange;" title="Verse Master"></i>
                    <i class="fa-solid fa-hands-praying" style="font-size: 32px; color: #3498db;" title="Helper"></i>
                </div>
            </div>
        </div>
        
    </div>

</body>
</html>
