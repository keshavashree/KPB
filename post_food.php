<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'donor') {
    $user_id = $_SESSION['user_id'];
    $food_name = $_POST['food_name'];
    $quantity = intval($_POST['quantity']);
    $food_type = $_POST['food_type'] ?? '';
    $pickup_location = $_POST['pickup_location'] ?? '';
    $expiration_datetime = $_POST['expiration_datetime'] ?? null;
    $contact_number = $_POST['contact_number'] ?? '';
    $dietary_info = isset($_POST['dietary_info']) ? implode(', ', $_POST['dietary_info']) : ''; // Store as comma-separated string
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $formatted_address = $_POST['formatted_address'] ?? '';

    try {
        // Insert food post with location data
        $stmt = $conn->prepare("INSERT INTO food_posts (user_id, food_name, quantity, food_type, pickup_location, expiration_datetime, contact_number, dietary_info, latitude, longitude, formatted_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $food_name, $quantity, $food_type, $pickup_location, $expiration_datetime, $contact_number, $dietary_info, $latitude, $longitude, $formatted_address]);

        if ($result) {
            $food_post_id = $conn->lastInsertId();

            // Notify all receivers
            $stmt_receivers = $conn->prepare("SELECT id FROM users WHERE role = 'receiver'");
            $stmt_receivers->execute();
            $receivers = $stmt_receivers->fetchAll(PDO::FETCH_ASSOC);

            $notification_message = "New food posted: " . htmlspecialchars($food_name) . " (" . htmlspecialchars($quantity) . " servings) by " . htmlspecialchars($_SESSION['username']);

            foreach ($receivers as $receiver) {
                $stmt_notify = $conn->prepare("INSERT INTO notifications (food_post_id, receiver_id, message) VALUES (?, ?, ?)");
                $stmt_notify->execute([$food_post_id, $receiver['id'], $notification_message]);
            }

            $_SESSION['message'] = "Food posted successfully and receivers notified!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error posting food. Please try again.";
            $_SESSION['message_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error posting food: " . htmlspecialchars($e->getMessage());
        $_SESSION['message_type'] = "danger";
    }

    header("Location: index.php");
    exit();
} else {
    $_SESSION['message'] = "Unauthorized access or invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}
?>
