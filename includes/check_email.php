<?php
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email)) {
        echo json_encode(['available' => false, 'message' => 'Email is required.']);
        exit;
    }

    // Check basic format before DB query
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['available' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['available' => false, 'message' => 'Email already exists.']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Email is available.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
}
?>
