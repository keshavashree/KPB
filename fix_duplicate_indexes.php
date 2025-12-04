<?php
// Fix duplicate indexes in FoodShare database
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Duplicate Indexes - FoodShare</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🔧 Fixing Duplicate Indexes</h1>
";

try {
    $conn = new PDO('mysql:host=localhost;dbname=foodshare', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p class='success'>✅ Database connection successful</p>";

    // Drop existing indexes that might be duplicated
    $indexes_to_drop = [
        'DROP INDEX IF EXISTS idx_food_posts_status ON food_posts',
        'DROP INDEX IF EXISTS idx_food_posts_user_id ON food_posts',
        'DROP INDEX IF EXISTS idx_food_posts_food_type ON food_posts',
        'DROP INDEX IF EXISTS idx_pickups_post_id ON pickups',
        'DROP INDEX IF EXISTS idx_pickups_receiver_id ON pickups',
        'DROP INDEX IF EXISTS idx_notifications_receiver_id ON notifications',
        'DROP INDEX IF EXISTS idx_notifications_is_read ON notifications',
        'DROP INDEX IF EXISTS idx_users_email ON users'
    ];

    foreach ($indexes_to_drop as $drop_sql) {
        try {
            $conn->exec($drop_sql);
            echo "<p class='info'>✅ Dropped index: " . str_replace('DROP INDEX IF EXISTS ', '', $drop_sql) . "</p>";
        } catch (Exception $e) {
            echo "<p class='info'>ℹ️ Index didn't exist: " . str_replace('DROP INDEX IF EXISTS ', '', $drop_sql) . "</p>";
        }
    }

    // Create fresh indexes
    $indexes_to_create = [
        'CREATE INDEX idx_food_posts_status ON food_posts(status)',
        'CREATE INDEX idx_food_posts_user_id ON food_posts(user_id)',
        'CREATE INDEX idx_food_posts_food_type ON food_posts(food_type)',
        'CREATE INDEX idx_pickups_post_id ON pickups(post_id)',
        'CREATE INDEX idx_pickups_receiver_id ON pickups(receiver_id)',
        'CREATE INDEX idx_notifications_receiver_id ON notifications(receiver_id)',
        'CREATE INDEX idx_notifications_is_read ON notifications(is_read)',
        'CREATE INDEX idx_users_email ON users(email)'
    ];

    foreach ($indexes_to_create as $create_sql) {
        try {
            $conn->exec($create_sql);
            echo "<p class='success'>✅ Created index: " . str_replace('CREATE INDEX ', '', $create_sql) . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error creating index: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>✅ Index fix completed!</h2>";
    echo "<p>The 'Duplicate key name' error should now be resolved.</p>";
    echo "<p><a href='setup_database_fixed.php'>Run full setup</a> | <a href='test_my_claimed_foods.php'>Test my claimed foods</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body>
</html>";
?>
