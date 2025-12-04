<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'receiver') {
    $food_id = intval($_POST['food_id']);
    $receiver_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Check if food is available and not expired
        $stmt = $conn->prepare("SELECT status, expiration_datetime FROM food_posts WHERE id = ?");
        $stmt->execute([$food_id]);
        $food = $stmt->fetch();

        if (!$food) {
            throw new Exception("Food post not found.");
        }

        if ($food['status'] !== 'available') {
            throw new Exception("Food is no longer available.");
        }

        // Check if food has expired
        if (!empty($food['expiration_datetime']) && strtotime($food['expiration_datetime']) < time()) {
            throw new Exception("This food item has expired and is no longer available.");
        }

        // Check if receiver has already claimed this post
        $stmt = $conn->prepare("SELECT id FROM pickups WHERE post_id = ? AND receiver_id = ?");
        $stmt->execute([$food_id, $receiver_id]);
        $existing_claim = $stmt->fetch();

        if ($existing_claim) {
            throw new Exception("You have already claimed this food item.");
        }

        // Insert record into pickups table
        $stmt = $conn->prepare("INSERT INTO pickups (post_id, receiver_id, scheduled_at, status) VALUES (?, ?, NOW(), 'scheduled')");
        $stmt->execute([$food_id, $receiver_id]);

        // Update food_posts status to 'claimed'
        $stmt = $conn->prepare("UPDATE food_posts SET status = 'claimed' WHERE id = ?");
        $stmt->execute([$food_id]);

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Food claimed successfully!";
        $_SESSION['message_type'] = "success";

        // Redirect to My Claimed Foods page
        header("Location: my_claimed_foods.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();

        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "warning";
        header("Location: index.php");
        exit();
    }
} else {
    // Redirect if not POST, not logged in, or not a receiver
    $_SESSION['message'] = "Unauthorized access or invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}
?>
