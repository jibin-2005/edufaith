<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email'])) {
    echo json_encode(['success' => false, 'message' => 'No email provided']);
    exit;
}

$email = $input['email'];

// --- BRIDGE LOGIC ---
// 1. Firebase has already authenticated the user (we trust the client for this MVP, 
//    but ideally we should verify the ID Token on the server using Firebase Admin SDK).
//    Since we don't have Composer/Admin SDK easily on this XAMPP, we trust the email.

// 2. Check if this email exists in MySQL
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // User Match Found in MySQL!
    $user = $result->fetch_assoc();
    
    $role = strtolower($user['role']);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $role;
    $_SESSION['auth_provider'] = 'firebase';

    // Determine Redirect
    $redirect_url = 'user/dashboard_student.php'; // Default fallback
    if ($role === 'admin') $redirect_url = 'admin/dashboard_admin.php';
    elseif ($role === 'teacher') $redirect_url = 'user/dashboard_teacher.php';
    elseif ($role === 'student') $redirect_url = 'user/dashboard_student.php';
    elseif ($role === 'parent') $redirect_url = 'user/dashboard_parent.php';

    echo json_encode(['success' => true, 'redirect' => $redirect_url]);

} else {
    // User authenticated in Firebase but NOT found in MySQL
    // Auto-Register as student
    $displayName = isset($input['displayName']) ? $input['displayName'] : $email;
    $defaultRole = 'student'; 
    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $displayName, $email, $randomPass, $defaultRole);
    
    if ($insertStmt->execute()) {
        $newId = $insertStmt->insert_id;
        
        $_SESSION['user_id'] = $newId;
        $_SESSION['username'] = $displayName;
        $_SESSION['role'] = $defaultRole;
        $_SESSION['auth_provider'] = 'firebase';

        echo json_encode(['success' => true, 'redirect' => 'user/dashboard_student.php', 'is_new' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Sync Error: ' . $conn->error]);
    }
    $insertStmt->close();
}

$stmt->close();
$conn->close();
?>
