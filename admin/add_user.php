<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';
$role_override = isset($_GET['role']) ? $_GET['role'] : 'student';

// Fetch classes for dropdown
$classes_result = $conn->query("SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name ASC");
$classes = [];
if ($classes_result) {
    while($c = $classes_result->fetch_assoc()) {
        $classes[] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { overflow-y: scroll; }
        .main-content { min-height: 100vh; padding-bottom: 100px; }
        .form-container { 
            max-width: 600px; 
            margin: 40px auto 100px auto; 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .form-group { 
            margin-bottom: 25px; 
            clear: both;
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #333;
        }
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #ddd; 
            border-radius: 6px; 
            font-size: 15px; 
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-submit { 
            width: 100%; 
            padding: 15px; 
            background: #2ecc71; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 18px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn-submit:hover { 
            background: #27ae60; 
        }
        .btn-submit:disabled { 
            background: #95a5a6; 
            cursor: not-allowed; 
        }
        #statusMsg { 
            padding: 12px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
            display: none;
            font-weight: 500;
        }
        #statusMsg.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
            display: block; 
        }
        #statusMsg.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
            display: block; 
        }
        .note-text {
            text-align: center;
            padding-top: 15px;
            font-size: 13px;
            color: #666;
        }
        .required-note {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-church"></i> 
            <span>St. Thomas Church Kanamala</span>
        </div>
        <ul class="menu">
            <li><a href="dashboard_admin.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="manage_teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
            <li><a href="manage_students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
            <li><a href="manage_parents.php"><i class="fa-solid fa-users"></i> Parents</a></li>
        </ul>
        <div class="logout">
            <a href="../index.html"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Register New User</h2>
                <p>This will create an account in both <b>Firebase</b> and <b>MySQL</b>.</p>
            </div>
        </div>

        <div class="form-container">
            <div id="statusMsg"></div>

            <form id="addForm">
                <!-- Full Name -->
                <div class="form-group">
                    <label for="fullname">Full Name <span style="color:red;">*</span></label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter full name" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address <span style="color:red;">*</span></label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Initial Password <span style="color:red;">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
                </div>

                <!-- Role -->
                <div class="form-group">
                    <label for="role">Role <span style="color:red;">*</span></label>
                    <select id="role" name="role" required>
                        <option value="student" <?php echo ($role_override == 'student' ? 'selected' : ''); ?>>Student</option>
                        <option value="teacher" <?php echo ($role_override == 'teacher' ? 'selected' : ''); ?>>Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <!-- Class Selection (Shows for Student and Teacher) -->
                <div class="form-group" id="class-group">
                    <label for="class_id">Assign Class <span style="color:red;">*</span></label>
                    <select id="class_id" name="class_id">
                        <option value="">-- Select Class --</option>
                        <?php foreach($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="required-note">Required for students only</div>
                </div>

                <!-- SUBMIT BUTTON - ALWAYS VISIBLE -->
                <button type="submit" id="submitBtn" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Register Member
                </button>

                <p class="note-text">
                    <i class="fa-solid fa-info-circle"></i> 
                    Firebase requires unique emails and minimum 6 character passwords.
                </p>
            </form>
        </div>
    </div>

    <script>
        // Show/hide class dropdown based on role
        const roleSelect = document.getElementById('role');
        const classGroup = document.getElementById('class-group');
        const classSelect = document.getElementById('class_id');
        
        function toggleClassField() {
            const role = roleSelect.value;
            console.log('Role changed to:', role); // Debug log
            
            // Only show class dropdown for STUDENTS
            if(role === 'student') {
                classGroup.style.display = 'block';
                classSelect.required = true;
            } else {
                classGroup.style.display = 'none';
                classSelect.required = false;
                classSelect.value = '';
            }
        }
        
        // Listen for role changes
        roleSelect.addEventListener('change', toggleClassField);
        
        // Run on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing form...'); // Debug log
            toggleClassField();
        });
        
        // Also run immediately
        toggleClassField();
    </script>

    <script type="module" src="../js/add_user_sync.js"></script>
</body>
</html>
