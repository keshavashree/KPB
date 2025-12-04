<?php
// Test script for my_claimed_foods.php functionality
session_start();
require 'db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test My Claimed Foods - FoodShare</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🧪 Testing My Claimed Foods Page</h1>
";

try {
    // Test database connection
    echo "<p class='success'>✅ Database connection successful</p>";

    // Test if we can select from food_posts with food_type column
    $stmt = $conn->query("SELECT id, food_name, food_type FROM food_posts LIMIT 1");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ food_type column accessible in food_posts table</p>";

    // Test the actual query from my_claimed_foods.php
    $test_query = "
        SELECT p.*, fp.food_name, fp.quantity, fp.food_type, fp.pickup_location,
               fp.expiration_datetime, u.username as donor_name
        FROM pickups p
        JOIN food_posts fp ON p.post_id = fp.id
        JOIN users u ON fp.user_id = u.id
        WHERE p.receiver_id = 999 AND p.status = 'scheduled'
        LIMIT 1
    ";

    $stmt = $conn->query($test_query);
    $result = $stmt->fetch();
    echo "<p class='success'>✅ My claimed foods query works without errors</p>";

    // Test users table email column
    $stmt = $conn->query("SELECT id, username, email FROM users LIMIT 1");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ Email column accessible in users table</p>";

    echo "<h2>✅ All database tests passed!</h2>";
    echo "<p>The 'Column not found: food_type' error should now be resolved.</p>";
    echo "<p><a href='my_claimed_foods.php'>Test the actual page</a></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ General Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body>
</html>";
?>
