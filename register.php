<?php
session_start(); // Start session to use messages
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role = $_POST['role']; // donor or receiver

    try {
        // Check if username already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->execute([$username]);
        $existing_user = $stmt_check->fetch();

        if ($existing_user) {
            $_SESSION['message'] = "Username already exists. Please choose another.";
            $_SESSION['message_type'] = "danger";
            header("Location: register.html");
            exit();
        }

        // Check if email already exists
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check_email->execute([$email]);
        $existing_email = $stmt_check_email->fetch();

        if ($existing_email) {
            $_SESSION['message'] = "Email already exists. Please use another email.";
            $_SESSION['message_type'] = "danger";
            header("Location: register.html");
            exit();
        }

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $password, $role]);

        if ($result) {
            $_SESSION['message'] = "Registration successful! You can now log in.";
            $_SESSION['message_type'] = "success";
            header("Location: login.html");
            exit();
        } else {
            $_SESSION['message'] = "Error during registration. Please try again.";
            $_SESSION['message_type'] = "danger";
            header("Location: register.html");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Registration error: " . htmlspecialchars($e->getMessage());
        $_SESSION['message_type'] = "danger";
        header("Location: register.html");
        exit();
    }
} else {
    header("Location: register.html");
    exit();
}
?>
