<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="dashboard.css">
    <!-- FontAwesome for Icons -->
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
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Parents</a></li>
            <li><a href="#"><i class="fa-solid fa-calendar-days"></i> Events</a></li>
            <li><a href="#"><i class="fa-solid fa-bullhorn"></i> Bulletins</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Real-time User Analytics & Overview.</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Admin">
                </div>
            </div>
        </div>

        <!-- PHP: Fetch real data -->
        <?php
        require_once 'db.php';

        // Helper function to count users by role
        function countUsers($conn, $role) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE role = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'];
        }

        // Get real counts
        $student_count = countUsers($conn, 'student');
        $teacher_count = countUsers($conn, 'teacher');
        $parent_count = countUsers($conn, 'parent');
        
        // Total users (sum of all roles including admin)
        $sql_total = "SELECT COUNT(*) as count FROM users";
        $result_total = $conn->query($sql_total);
        $total_users = $result_total->fetch_assoc()['count'];
        ?>

        <!-- WIDGETS -->
        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $student_count; ?></h3>
                    <p>Total Students</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $teacher_count; ?></h3>
                    <p>Total Teachers</p>
                </div>
                <div class="card-icon bg-purple">
                    <i class="fa-solid fa-person-chalkboard"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $parent_count; ?></h3>
                    <p>Total Parents</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Accounts</p>
                </div>
                <div class="card-icon bg-orange">
                    <i class="fa-solid fa-database"></i>
                </div>
            </div>
        </div>

        <!-- CONTENT GRID -->
        <div class="section-grid">
            <!-- LEFT COLUMN -->
            <div class="panel">
                <div class="panel-header">
                    <h3>User Distribution Analysis</h3>
                </div>
                <!-- Chart Container -->
                <div style="height: 300px; position: relative;">
                    <canvas id="userChart"></canvas>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="panel">
                <div class="panel-header">
                    <h3>Quick Actions</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="add_user.php?role=student" style="text-decoration:none;">
                        <button style="width:100%; padding: 12px; background: var(--light); border: none; border-radius: 8px; text-align: left; cursor: pointer; color: var(--dark); font-weight: 500;">
                            <i class="fa-solid fa-plus-circle" style="margin-right:8px; color: var(--primary);"></i> Add New Student
                        </button>
                    </a>
                    <a href="add_user.php?role=teacher" style="text-decoration:none;">
                        <button style="width:100%; padding: 12px; background: var(--light); border: none; border-radius: 8px; text-align: left; cursor: pointer; color: var(--dark); font-weight: 500;">
                            <i class="fa-solid fa-chalkboard-user" style="margin-right:8px; color: #9b59b6;"></i> Add New Teacher
                        </button>
                    </a>
                    <button style="padding: 12px; background: var(--light); border: none; border-radius: 8px; text-align: left; cursor: pointer; color: var(--dark); font-weight: 500;">
                        <i class="fa-solid fa-bell" style="margin-right:8px; color: #e67e22;"></i> Create Announcement
                    </button>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('userChart').getContext('2d');
        const userChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Teachers', 'Parents', 'Others'],
                datasets: [{
                    label: 'User Distribution',
                    data: [
                        <?php echo $student_count; ?>, 
                        <?php echo $teacher_count; ?>, 
                        <?php echo $parent_count; ?>, 
                        <?php echo $total_users - ($student_count + $teacher_count + $parent_count); ?>
                    ],
                    backgroundColor: [
                        '#3498db', // Blue
                        '#9b59b6', // Purple
                        '#2ecc71', // Green
                        '#95a5a6'  // Gray
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>

</body>
</html>
