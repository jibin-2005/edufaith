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
$firebase_uid = isset($input['uid']) ? $input['uid'] : null;

// --- BRIDGE LOGIC ---

// 1. Check if user exists by Firebase UID
$stmt = $conn->prepare("SELECT id, username, role, status, email FROM users WHERE firebase_uid = ?");
$stmt->bind_param("s", $firebase_uid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Found by UID
    $user = $result->fetch_assoc();
} else {
    // 2. Fallback: Check by Email (Migration or first sync)
    $stmt->close(); // Close previous
    $stmt = $conn->prepare("SELECT id, username, role, status, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Update UID for future logins
        if ($firebase_uid) {
            $updateStmt = $conn->prepare("UPDATE users SET firebase_uid = ? WHERE id = ?");
            $updateStmt->bind_param("si", $firebase_uid, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } else {
        // User NOT found
        echo json_encode(['success' => false, 'message' => 'User not found in system. Please contact Administrator.']);
        exit;
    }
}

// 3. Status Check
if ($user['status'] === 'inactive') {
    echo json_encode(['success' => false, 'message' => 'Account is inactive. Contact Administrator.']);
    exit;
}

// 4. Success - Set Session
$role = strtolower($user['role']);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $role;
$_SESSION['auth_provider'] = 'firebase';

// Determine Redirect
$redirect_url = 'user/dashboard_student.php'; // Default
if ($role === 'admin') $redirect_url = 'admin/dashboard_admin.php';
elseif ($role === 'teacher') $redirect_url = 'user/dashboard_teacher.php';
elseif ($role === 'student') $redirect_url = 'user/dashboard_student.php';
elseif ($role === 'parent') $redirect_url = 'user/dashboard_parent.php';

echo json_encode(['success' => true, 'redirect' => $redirect_url]);

$stmt->close();
$conn->close();
?>
