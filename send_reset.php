<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // 1. Check if email exists in DB
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        
        // 2. Generate Token
        $token = bin2hex(random_bytes(16)); // 32 hex characters
        $token_hash = hash("sha256", $token);
        // Expiry 1 hour from now
        $expiry = date("Y-m-d H:i:s", time() + 60 * 60);

        // 3. Update DB with token hash
        // 3. Update DB with token hash - Using MySQL time ensures timezone consistency
        $update_stmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $update_stmt->bind_param("ss", $token_hash, $email);
        $update_stmt->execute();

        // 4. Send Email (Link contains the CLEAN token, not the hash)
        // konstrukt the link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        // Assuming the file is in the same directory
        $path = dirname($_SERVER['PHP_SELF']); 
        if ($path == '/' || $path == '\\') $path = '';
        
        $reset_link = "$protocol://$_SERVER[HTTP_HOST]$path/reset_password.php?token=$token";

        // --- SMTP EMAIL SENDING ---
        require 'SimpleSMTP.php';
        
        $my_email = 'jibinthomas1762005@gmail.com'; 
        $my_app_password = 'YOUR_APP_PASSWORD_HERE'; // 16-char App Password

        if ($my_app_password === 'YOUR_APP_PASSWORD_HERE') {
            echo "<script>
                    alert('CRITICAL: SMTP App Password is not set in send_reset.php line 46. Email cannot be sent.\\n\\nHowever, for testing, here is your reset link:\\n' + '$reset_link');
                    window.location.href = '$reset_link';
                  </script>";
            exit;
        }

        $smtp = new SimpleSMTP($my_email, $my_app_password);

        $subject = "Password Reset - Sunday School Management";
        $message = "<h2>Password Reset</h2>
                    <p>Click the link below to reset your password:</p>
                    <p><a href='$reset_link'>$reset_link</a></p>
                    <p>Link expires in 1 hour.</p>";
        
        $mailSent = $smtp->send($email, $subject, $message);

        if ($mailSent) {
             echo "<script>
                    alert('Reset link sent to $email via GMAIL! Check your inbox.');
                    window.location.href = 'login.html';
                  </script>";
        } else {
             // Fallback for debugging if credentials fail
             echo "<script>
                    alert('SMTP Failed. This usually means the App Password is incorrect or Google blocked the connection.\\n\\nTesting Link:\\n' + '$reset_link');
                    window.location.href = '$reset_link';
                  </script>";
        }

    } else {
        // Email not found - For security, do not reveal this.
        // But for UX, we might say "If that email exists, we sent a link."
        echo "<script>
                alert('If an account exists with that email, a reset link has been sent.');
                window.location.href = 'login.html';
              </script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>
