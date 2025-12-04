<?php
// Test Food Posting Functionality
echo "<h1>Food Posting Test</h1>";

try {
    require 'db.php';

    echo "<p>✅ Database connection successful!</p>";

    // Test 1: Check if we can insert a test food post
    $test_data = [
        'user_id' => 1, // Assuming test user exists
        'food_name' => 'Test Pizza Slices',
        'description' => 'Fresh pizza slices from local restaurant',
        'quantity' => 5,
        'unit' => 'pieces',
        'expiration_datetime' => date('Y-m-d H:i:s', strtotime('+2 hours')),
        'pickup_location' => 'Restaurant back entrance',
        'nutritional_info' => 'Contains gluten, dairy, vegetarian option available',
        'status' => 'available'
    ];

    $stmt = $conn->prepare("
        INSERT INTO food_posts (
            user_id, food_name, description, quantity, unit,
            expiration_datetime, pickup_location, nutritional_info,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $result = $stmt->execute([
        $test_data['user_id'],
        $test_data['food_name'],
        $test_data['description'],
        $test_data['quantity'],
        $test_data['unit'],
        $test_data['expiration_datetime'],
        $test_data['pickup_location'],
        $test_data['nutritional_info'],
        $test_data['status']
    ]);

    if ($result) {
        $post_id = $conn->lastInsertId();
        echo "<p class='success'>✅ Test food post created successfully! (ID: $post_id)</p>";

        // Clean up test post
        $stmt = $conn->prepare("DELETE FROM food_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        echo "<p class='info'>✅ Test food post cleaned up.</p>";
    } else {
        echo "<p class='error'>❌ Failed to create test food post.</p>";
    }

    // Test 2: Check if uploads directory is ready
    if (is_writable('uploads')) {
        echo "<p class='success'>✅ Uploads directory is ready for file uploads.</p>";
    } else {
        echo "<p class='error'>❌ Uploads directory is not writable.</p>";
    }

    // Test 3: Check if we can query food posts
    $stmt = $conn->query("SELECT COUNT(*) as count FROM food_posts");
    $post_count = $stmt->fetch()['count'];
    echo "<p class='info'>📊 Current food posts in database: $post_count</p>";

    echo "<h2 class='success'>🎉 Food Posting Test Complete!</h2>";
    echo "<p><a href='donor_post_food.php'>Try Posting Food</a> | <a href='index.php'>Go to Homepage</a></p>";

} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Database Error</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please run the database setup first: <a href='setup_database_fixed.php'>Setup Database</a></p>";
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Test Failed</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
</style>
