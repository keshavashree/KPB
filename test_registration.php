<?php
// Test Registration Functionality
echo "<h1>Registration Test</h1>";

try {
    require 'db.php';

    echo "<p>✅ Database connection successful!</p>";

    // Test 1: Check if users table has email column
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Users Table Structure:</h3>";
    foreach ($columns as $column) {
        echo "<p><strong>" . htmlspecialchars($column['Field']) . "</strong>: " . htmlspecialchars($column['Type']);
        if ($column['Null'] === 'NO') echo " (Required)";
        echo "</p>";
    }

    // Test 2: Check if we can insert a test user
    $test_data = [
        'username' => 'testuser_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => password_hash('testpass', PASSWORD_DEFAULT),
        'role' => 'donor'
    ];

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$test_data['username'], $test_data['email'], $test_data['password'], $test_data['role']]);

    if ($result) {
        $user_id = $conn->lastInsertId();
        echo "<p class='success'>✅ Test user created successfully! (ID: $user_id)</p>";

        // Clean up test user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        echo "<p class='info'>✅ Test user cleaned up.</p>";
    } else {
        echo "<p class='error'>❌ Failed to create test user.</p>";
    }

    // Test 3: Check existing users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p class='info'>📊 Users in database: $user_count</p>";

    echo "<h2 class='success'>🎉 Registration Test Complete!</h2>";
    echo "<p><a href='register.html'>Try Registration Form</a> | <a href='index.php'>Go to Homepage</a></p>";

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
