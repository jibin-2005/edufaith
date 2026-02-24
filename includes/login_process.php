<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : null;

    // Prepare statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password, role, status, profile_picture FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify Password
        if (password_verify($password, $user['password'])) {
            if (isset($user['status']) && $user['status'] !== 'active') {
                echo "<script>alert('Account is inactive. Please contact an administrator.'); window.location.href='../login.html';</script>";
                exit;
            }
            
            $db_role = strtolower($user['role']);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $db_role;
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_role'] = $db_role;
            $_SESSION['profile_image'] = $user['profile_picture'] ?? null;
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;

            // Redirect based on role
            switch ($db_role) {
                case 'admin':
                    header("Location: ../admin/dashboard_admin.php");
                    break;
                case 'teacher':
                    header("Location: ../user/dashboard_teacher.php");
                    break;
                case 'student':
                    header("Location: ../user/dashboard_student.php");
                    break;
                case 'parent':
                    header("Location: ../user/dashboard_parent.php");
                    break;
                default:
                    header("Location: ../index.html");
            }
            exit;
        } else {
            echo "<script>alert('Invalid Password'); window.location.href='../login.html';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='../login.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
