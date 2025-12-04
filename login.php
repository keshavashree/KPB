<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // donor or receiver
            $_SESSION['message'] = "Welcome, " . htmlspecialchars($user['username']) . "!";
            $_SESSION['message_type'] = "success";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['message_type'] = "danger";
            header("Location: login.html"); // Redirect back to login with error
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Login error: " . htmlspecialchars($e->getMessage());
        $_SESSION['message_type'] = "danger";
        header("Location: login.html");
        exit();
    }
} else {
    header("Location: login.html");
    exit();
}
?>
