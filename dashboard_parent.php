<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Portal | St. Thomas Church Kanamala</title>
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
            <li><a href="#"><i class="fa-solid fa-child-reaching"></i> My Children</a></li>
            <li><a href="#"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</a></li>
            <li><a href="#"><i class="fa-solid fa-envelope"></i> Messages</a></li>
        </ul>
        <div class="logout">
            <a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p>Tracking your children's spiritual growth.</p>
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div class="user-img">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="Parent">
                </div>
            </div>
        </div>

        <!-- CHILD SELECTOR TAB -->
        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <button style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 20px; font-weight: 500;">Joshua (Grade 4)</button>
            <button style="padding: 10px 20px; background: white; color: #aaa; border: 1px solid #eee; border-radius: 20px; font-weight: 500;">Esther (Grade 1)</button>
        </div>

        <div class="grid-container">
            <div class="card">
                <div class="card-info">
                    <h3>92%</h3>
                    <p>Joshua's Attendance</p>
                </div>
                <div class="card-icon bg-green">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>$0</h3>
                    <p>Fees Pending</p>
                </div>
                <div class="card-icon bg-blue">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3>Teacher's Remarks</h3>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                    <p style="font-size: 14px; color: #555;">"Joshua was very attentive today during the lesson on The Good Samaritan. He volunteered for the role-play!"</p>
                    <p style="font-size: 12px; color: #aaa; margin-top: 5px;">— Mrs. Thompson • 2 days ago</p>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Permission Slips</h3>
                </div>
                <div style="border: 1px solid #ffd700; background: #fffdf0; padding: 15px; border-radius: 8px;">
                    <h4 style="margin-bottom: 5px; color: #d4ac0d;">Christmas Pageant</h4>
                    <p style="font-size: 13px; color: #7f8c8d; margin-bottom: 10px;">Please sign the permission slip for the upcoming rehearsal.</p>
                    <button style="background: #d4ac0d; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">Sign Now</button>
                </div>
            </div>
        </div>
        
    </div>

</body>
</html>
