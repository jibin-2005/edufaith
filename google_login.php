<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Get the raw POST data (Google sends it as JSON sometimes, but usually as form data if using simple fetch. 
// We will assume the JS sends a JSON body or Form data. Let's handle JSON input from fetch.)
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['credential'])) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit;
}

$id_token = $input['credential'];

// DEBUG LOGGING
file_put_contents('google_debug.log', date('Y-m-d H:i:s') . " - Received Token: " . substr($id_token, 0, 10) . "...\n", FILE_APPEND);

// Verify the token using Google's public endpoint
// This avoids needing the heavy Google Client Library for PHP
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
$response = @file_get_contents($url); // Suppress warnings, handle below

if ($response === FALSE) {
    file_put_contents('google_debug.log', date('Y-m-d H:i:s') . " - Failed to contact Google API\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Failed to connect to Google']);
    exit;
}

$payload = json_decode($response, true);

if (isset($payload['error_description'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Token: ' . $payload['error_description']]);
    exit;
}

// Token is valid. Now check if the user exists.
$email = $payload['email'];
$google_sub = $payload['sub']; // Unique Google ID

// Optional: Ensure email is verifying
// if (!$payload['email_verified']) { ... }

// Check DB
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // User exists! Log them in.
    $user = $result->fetch_assoc();
    
    $role = strtolower($user['role']);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $role;
    $_SESSION['login_type'] = 'google';

    // Return success and where to redirect
    $redirect_url = 'index.html'; // Default
    switch ($role) {
        case 'admin':
            $redirect_url = 'dashboard_admin.php';
            break;
        case 'teacher':
             $redirect_url = 'dashboard_teacher.php';
            break;
        case 'student':
             $redirect_url = 'dashboard_student.php';
            break;
        case 'parent':
             $redirect_url = 'dashboard_parent.php';
            break;
    }

    echo json_encode(['success' => true, 'redirect' => $redirect_url]);

} else {
    // User not found
    file_put_contents('google_debug.log', date('Y-m-d H:i:s') . " - User not found: " . $email . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'No account found with this email. Please register first.']);
}

$stmt->close();
$conn->close();
?>
