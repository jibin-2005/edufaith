<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : null;

    // Prepare statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify Password
        if (password_verify($password, $user['password'])) {
            
            $db_role = strtolower($user['role']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $db_role;

            // Redirect based on role
            switch ($db_role) {
                case 'admin':
                    header("Location: dashboard_admin.php");
                    break;
                case 'teacher':
                    header("Location: dashboard_teacher.php");
                    break;
                case 'student':
                    header("Location: dashboard_student.php");
                    break;
                case 'parent':
                    header("Location: dashboard_parent.php");
                    break;
                default:
                    header("Location: index.html");
            }
            exit;
        } else {
            echo "<script>alert('Invalid Password'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='login.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
