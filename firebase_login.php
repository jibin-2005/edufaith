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
    $redirect_url = 'index.html'; 
    switch ($role) {
        case 'admin':
            $redirect_url = 'dashboard_admin.php'; break;
        case 'teacher':
             $redirect_url = 'dashboard_teacher.php'; break;
        case 'student':
             $redirect_url = 'dashboard_student.php'; break;
        case 'parent':
             $redirect_url = 'dashboard_parent.php'; break;
    }

    echo json_encode(['success' => true, 'redirect' => $redirect_url]);

} else {
    // User authenticated in Firebase but NOT found in MySQL
    // Option A: Auto-Register?
    // Option B: Error?
    // Let's return Error for now, as user said "data to mysql user id", implying match.
    // Actually, maybe we should insert them if they are new (from Google Login)?
    
    // For now, let's keep it strict: You must be in the DB.
    echo json_encode(['success' => false, 'message' => 'Account not found in School Database. Please contact Admin.']);
}

$stmt->close();
$conn->close();
?>
