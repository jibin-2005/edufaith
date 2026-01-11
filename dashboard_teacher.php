<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="dashboard.css">
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
            <li><a href="#" class="active"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa-solid fa-user-group"></i> My Class</a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> Attendance</a></li>
            <li><a href="#"><i class="fa-solid fa-book"></i> Lesson Plans</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Grade 4 • Room 303</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Teacher">
                </div>
            </div>
        </div>

        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3>18</h3>
                    <p>Students Present</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>2</h3>
                    <p>Absent</p>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>Sunday</h3>
                    <p>Next Class</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-calendar"></i>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Today's Attendance</h3>
                <button style="padding: 8px 16px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">Save Attendance</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Caleb Johnson</td>
                        <td>Grade 4</td>
                        <td><span class="status present">Present</span></td>
                        <td><i class="fa-solid fa-pen-to-square" style="color: #aaa; cursor: pointer;"></i></td>
                    </tr>
                    <tr>
                        <td>Mia Wong</td>
                        <td>Grade 4</td>
                        <td><span class="status absent">Absent</span></td>
                        <td><i class="fa-solid fa-pen-to-square" style="color: #aaa; cursor: pointer;"></i></td>
                    </tr>
                    <tr>
                        <td>Liam Smith</td>
                        <td>Grade 4</td>
                        <td><span class="status present">Present</span></td>
                        <td><i class="fa-solid fa-pen-to-square" style="color: #aaa; cursor: pointer;"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>
