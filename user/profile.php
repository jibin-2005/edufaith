<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}

require '../includes/db.php';

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Helper function to calculate age
function calculateAge($dob) {
    if (empty($dob)) return null;
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

// Helper function to validate phone number
function validatePhone($phone) {
    if (empty($phone)) return true; // Optional field
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
}

// Handle Profile Picture Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_type = $_FILES['profile_picture']['type'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_extensions)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
        elseif ($file_size > $max_size) {
            $error = "File size exceeds 2MB limit.";
        }
        elseif (!getimagesize($file_tmp)) {
            $error = "Invalid image file.";
        }
        else {
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = '../uploads/profile_pictures/' . $new_filename;
            $db_path = 'uploads/profile_pictures/' . $new_filename;
            
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $old_data = $result->fetch_assoc();
            $stmt->close();
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $db_path, $user_id);
                
                if ($stmt->execute()) {
                    if (!empty($old_data['profile_picture']) && file_exists('../' . $old_data['profile_picture'])) {
                        unlink('../' . $old_data['profile_picture']);
                    }
                    $_SESSION['profile_image'] = $db_path;
                    $_SESSION['profile_picture'] = $db_path;
                    $message = "Profile picture updated successfully!";
                } else {
                    $error = "Error updating database: " . $conn->error;
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
                $stmt->close();
            } else {
                $error = "Error uploading file. Please try again.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle Profile Picture Removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_picture'])) {
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!empty($old_data['profile_picture'])) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            if (file_exists('../' . $old_data['profile_picture'])) {
                unlink('../' . $old_data['profile_picture']);
            }
            $_SESSION['profile_image'] = null;
            $_SESSION['profile_picture'] = null;
            $message = "Profile picture removed successfully!";
        } else {
            $error = "Error removing profile picture: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "No profile picture to remove.";
    }
}

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : NULL;
    $baptism_date = !empty($_POST['baptism_date']) ? $_POST['baptism_date'] : NULL;
    $holy_communion_date = !empty($_POST['holy_communion_date']) ? $_POST['holy_communion_date'] : NULL;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : NULL;

    // Validation
    if (empty($fullname) || empty($email)) {
        $error = "Full name and email are required.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    elseif (!validatePhone($phone)) {
        $error = "Invalid phone number. Must be 10-15 digits.";
    }
    elseif ($dob && strtotime($dob) >= time()) {
        $error = "Date of birth must be in the past.";
    }
    elseif ($dob && calculateAge($dob) < 5) {
        $error = "Age must be at least 5 years.";
    }
    elseif ($dob && calculateAge($dob) > 100) {
        $error = "Age must be less than 100 years.";
    }
    elseif ($baptism_date && strtotime($baptism_date) >= time()) {
        $error = "Baptism date must be in the past.";
    }
    elseif ($dob && $baptism_date && strtotime($baptism_date) < strtotime($dob)) {
        $error = "Baptism date must be after date of birth.";
    }
    elseif ($holy_communion_date && strtotime($holy_communion_date) >= time()) {
        $error = "Holy Communion date must be in the past.";
    }
    elseif ($baptism_date && $holy_communion_date && strtotime($holy_communion_date) < strtotime($baptism_date)) {
        $error = "Holy Communion date must be after baptism date.";
    }
    elseif ($address && strlen($address) > 500) {
        $error = "Address must be less than 500 characters.";
    }
    else {
        // Clean phone number (remove formatting)
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
        }
        
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, dob = ?, phone = ?, baptism_date = ?, holy_communion_date = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $fullname, $email, $dob, $phone, $baptism_date, $holy_communion_date, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $fullname;
            $_SESSION['user_name'] = $fullname;
            $message = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch current user data with class information
$role = $_SESSION['role'];
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT u.username, u.email, u.role, u.firebase_uid, u.status, u.profile_picture, u.dob, u.phone, u.baptism_date, u.holy_communion_date, u.address, u.class_id, c.class_name 
                            FROM users u 
                            LEFT JOIN classes c ON u.class_id = c.id 
                            WHERE u.id = ?");
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT u.username, u.email, u.role, u.firebase_uid, u.status, u.profile_picture, u.dob, u.phone, u.baptism_date, u.holy_communion_date, u.address, u.class_id
                            FROM users u 
                            WHERE u.id = ?");
} else {
    $stmt = $conn->prepare("SELECT username, email, role, firebase_uid, status, profile_picture, dob, phone, baptism_date, holy_communion_date, address 
                            FROM users 
                            WHERE id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// For teachers, get teaching classes
$teaching_classes = [];
if ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT c.class_name FROM classes c WHERE c.id = ?");
    if ($user_data['class_id']) {
        $stmt->bind_param("i", $user_data['class_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $teaching_classes[] = $row['class_name'];
        }
        $stmt->close();
    }
}

// Calculate age
$age = calculateAge($user_data['dob']);

$dashboard_link = "dashboard_student.php";
if ($role === 'admin') $dashboard_link = "../admin/dashboard_admin.php";
elseif ($role === 'teacher') $dashboard_link = "dashboard_teacher.php";
elseif ($role === 'parent') $dashboard_link = "dashboard_parent.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 4px solid #eef2f7;
            object-fit: cover;
        }
        .upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }
        .upload-section h4 {
            margin-bottom: 15px;
            color: var(--dark);
            font-size: 1.1rem;
        }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin: 10px 0;
        }
        .file-input-wrapper input[type="file"] {
            display: none;
        }
        .btn-choose-file {
            padding: 10px 20px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-block;
        }
        .btn-choose-file:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        .btn-upload {
            padding: 10px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin: 0 5px;
        }
        .btn-upload:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-remove {
            padding: 10px 25px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin: 0 5px;
        }
        .btn-remove:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        #preview-container {
            margin: 15px 0;
        }
        #image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
            border: 2px solid #ddd;
        }
        .file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        .profile-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .profile-section h4 {
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-section h4 i {
            color: var(--primary);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
        .form-group input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .info-note {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #eef2f7;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark);
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <?php include_once '../includes/sidebar.php'; render_sidebar($_SESSION['role'] ?? '', basename($_SERVER['PHP_SELF']), '..'); ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h2>Account Settings</h2>
                <p>Manage your personal information and preferences.</p>
            </div>
            <?php include_once '../includes/header.php'; render_user_header_profile('..'); ?>
        </div>

        <div class="profile-container">
            <div class="profile-header">
                <?php if (!empty($user_data['profile_picture']) && file_exists('../' . $user_data['profile_picture'])): ?>
                    <img src="../<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Image" class="profile-img">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['username']); ?>&background=random&size=128" alt="Profile Image" class="profile-img">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($user_data['username']); ?></h3>
                <span class="role-badge"><?php echo htmlspecialchars($user_data['role']); ?></span>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Profile Picture Upload Section -->
            <div class="upload-section">
                <h4><i class="fa-solid fa-camera"></i> Profile Picture</h4>
                <form action="profile.php" method="POST" enctype="multipart/form-data" id="upload-form">
                    <div class="file-input-wrapper">
                        <label for="profile_picture" class="btn-choose-file">
                            <i class="fa-solid fa-upload"></i> Choose Image
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif">
                    </div>
                    <div id="preview-container">
                        <img id="image-preview" alt="Image preview">
                        <div class="file-name" id="file-name"></div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" name="upload_picture" class="btn-upload" id="upload-btn" style="display:none;">
                            <i class="fa-solid fa-check"></i> Upload Picture
                        </button>
                        <?php if (!empty($user_data['profile_picture'])): ?>
                        <button type="submit" name="remove_picture" class="btn-remove" onclick="return confirm('Are you sure you want to remove your profile picture?');">
                            <i class="fa-solid fa-trash"></i> Remove Picture
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Profile Information Form -->
            <form action="profile.php" method="POST">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <h4><i class="fa-solid fa-user"></i> Personal Information</h4>
                    
                    <div class="form-group">
                        <label>Full Name <span style="color:red;">*</span></label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($user_data['dob'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Age</label>
                            <input type="text" id="age" value="<?php echo $age ? $age . ' years' : 'Not set'; ?>" readonly>
                            <div class="info-note">Auto-calculated from date of birth</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address <span style="color:red;">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="1234567890">
                            <div class="info-note">10-15 digits</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" maxlength="500" placeholder="Enter your full address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                        <div class="info-note">Maximum 500 characters</div>
                    </div>
                </div>

                <!-- Religious Milestones Section -->
                <div class="profile-section">
                    <h4><i class="fa-solid fa-cross"></i> Religious Milestones</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Baptism Date</label>
                            <input type="date" name="baptism_date" id="baptism_date" value="<?php echo htmlspecialchars($user_data['baptism_date'] ?? ''); ?>">
                            <div class="info-note">Must be after date of birth</div>
                        </div>
                        <div class="form-group">
                            <label>Holy Communion Date</label>
                            <input type="date" name="holy_communion_date" id="holy_communion_date" value="<?php echo htmlspecialchars($user_data['holy_communion_date'] ?? ''); ?>">
                            <div class="info-note">Must be after baptism date</div>
                        </div>
                    </div>
                </div>

                <!-- Role-Specific Information -->
                <?php if ($role === 'student' && !empty($user_data['class_name'])): ?>
                <div class="profile-section">
                    <h4><i class="fa-solid fa-graduation-cap"></i> Class Information</h4>
                    <div class="form-group">
                        <label>Assigned Class</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_data['class_name']); ?>" readonly>
                        <div class="info-note">Contact admin to change your class assignment</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($role === 'teacher' && !empty($teaching_classes)): ?>
                <div class="profile-section">
                    <h4><i class="fa-solid fa-chalkboard-user"></i> Teaching Information</h4>
                    <div class="form-group">
                        <label>Teaching Class(es)</label>
                        <input type="text" value="<?php echo htmlspecialchars(implode(', ', $teaching_classes)); ?>" readonly>
                        <div class="info-note">Contact admin to update teaching assignments</div>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </form>
            
            <p style="text-align:center; margin-top:20px; font-size:0.9rem; color:#777;">
                <i class="fa-solid fa-circle-info"></i> Password management is handled via the login portal's forgot password flow.
            </p>
        </div>
    </div>

    <script>
        // Image preview functionality
        const fileInput = document.getElementById('profile_picture');
        const imagePreview = document.getElementById('image-preview');
        const fileName = document.getElementById('file-name');
        const uploadBtn = document.getElementById('upload-btn');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB limit.');
                    fileInput.value = '';
                    imagePreview.style.display = 'none';
                    fileName.textContent = '';
                    uploadBtn.style.display = 'none';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
                    fileInput.value = '';
                    imagePreview.style.display = 'none';
                    fileName.textContent = '';
                    uploadBtn.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    fileName.textContent = file.name;
                    uploadBtn.style.display = 'inline-block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
                fileName.textContent = '';
                uploadBtn.style.display = 'none';
            }
        });

        // Age calculation on DOB change
        const dobInput = document.getElementById('dob');
        const ageInput = document.getElementById('age');

        dobInput.addEventListener('change', function() {
            if (this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                ageInput.value = age + ' years';
            } else {
                ageInput.value = 'Not set';
            }
        });

        // Date validation
        const baptismInput = document.getElementById('baptism_date');
        const communionInput = document.getElementById('holy_communion_date');

        baptismInput.addEventListener('change', function() {
            if (dobInput.value && this.value) {
                if (new Date(this.value) < new Date(dobInput.value)) {
                    alert('Baptism date must be after date of birth.');
                    this.value = '';
                }
            }
        });

        communionInput.addEventListener('change', function() {
            if (baptismInput.value && this.value) {
                if (new Date(this.value) < new Date(baptismInput.value)) {
                    alert('Holy Communion date must be after baptism date.');
                    this.value = '';
                }
            }
        });

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function() {
            // Remove non-digits
            let cleaned = this.value.replace(/\D/g, '');
            this.value = cleaned;
        });
    </script>
</body>
</html>

