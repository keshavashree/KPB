<?php
// Database Test Script for FoodShare
// Run this script to test if the database is working properly

echo "<h1>FoodShare Database Test</h1>";

try {
    require 'db.php';

    echo "<p>✅ Database connection successful!</p>";

    // Test 1: Check if tables exist
    $tables = ['users', 'food_posts', 'pickups', 'notifications'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists.</p>";
        } else {
            echo "<p>❌ Table '$table' does not exist.</p>";
        }
    }

    // Test 2: Check if we can insert a test user
    $test_username = 'test_user_' . time();
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$test_username, $test_username . '@example.com', password_hash('testpass', PASSWORD_DEFAULT), 'donor']);

    if ($result) {
        echo "<p>✅ Test user inserted successfully.</p>";

        // Clean up test user
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$test_username]);
        echo "<p>✅ Test user cleaned up.</p>";
    } else {
        echo "<p>❌ Failed to insert test user.</p>";
    }

    // Test 3: Check uploads directory
    if (is_writable('uploads')) {
        echo "<p>✅ Uploads directory is writable.</p>";
    } else {
        echo "<p>❌ Uploads directory is not writable.</p>";
    }

    echo "<h2>All Tests Complete!</h2>";
    echo "<p><a href='setup_database.php'>Run Setup Again</a> | <a href='index.php'>Go to Homepage</a></p>";

} catch(PDOException $e) {
    echo "<h2>Test Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
} catch(Exception $e) {
    echo "<h2>Test Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup_database.php'>Run Database Setup</a></p>";
}
?>
