<?php
require 'includes/db.php';

$token_valid = false;
$error_msg = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash("sha256", $token);

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valid = true;
    } else {
        $error_msg = "Invalid or expired token.";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match");
    }

    // Hash the token to find the user
    $token_hash = hash("sha256", $token);

    // Verify token again before updating
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update Password
        // Using PHP default password hashing (BCRYPT)
        $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Clear token fields
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE reset_token_hash = ?");
        $update_stmt->bind_param("ss", $new_password_hash, $token_hash);
        
        if ($update_stmt->execute()) {
             echo "<script>
                    alert('Password has been reset successfully!');
                    window.location.href = 'login.html';
                  </script>";
        } else {
             echo "Error updating password.";
        }
    } else {
        echo "Invalid or expired token.";
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | St. Thomas Church Kanamala</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .right-panel { justify-content: center; }
        .error { color: red; text-align: center; margin-bottom: 1em; }
    </style>
</head>
<body>

    <video autoplay muted loop class="video-bg">
        <source src="assets/videos/aradyane+kingdom1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="container">
        
        <div class="left-panel">
            <div class="overlay">
                <h1>Faith & Learning</h1>
                <p>“Create in me a clean heart, O God, and renew a right spirit within me.”<br>— Psalm 51:10</p>
            </div>
        </div>

        <div class="right-panel">
            <h2>Reset Password</h2>
            
            <?php if ($token_valid): ?>
                <p class="subtitle">Enter your new password below</p>
                
                <form method="post" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="New Password" required minlength="6">
                    
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">

                    <button type="submit">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="error">
                    <p><?php echo $error_msg ? $error_msg : "No token provided."; ?></p>
                </div>
                <a href="forgot_password.html" style="text-align:center; display:block; margin-top:20px;">Request a new link</a>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
