<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
<<<<<<< HEAD
    $role = $_POST['role']; // The role selected in the frontend
=======
<<<<<<< HEAD
    $role = $_POST['role']; // The role selected in the frontend
=======
    $password = $_POST['password'];
>>>>>>> 85623df (Initial commit - Sunday School Management System)
>>>>>>> 7e1952f (09/01/2026)

    // Prepare statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify Password
        if (password_verify($password, $user['password'])) {
            
            // Optional: Check if the user actually has the role they selected?
            // For now, let's trust the database role, or redirect based on DB role regardless of selection.
            // It's safer to use the DB role.
            
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
                    // Fallback
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
