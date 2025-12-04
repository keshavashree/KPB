<?php
// Complete Database Fix for FoodShare
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Complete Database Fix - FoodShare</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>🔧 Complete Database Fix</h1>
    <p>Fixing all database issues in the correct order...</p>
";

try {
    $conn = new PDO('mysql:host=localhost;dbname=foodshare', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p class='success'>✅ Database connection successful</p>";

    // Step 1: Check and add missing columns
    echo "<h2>Step 1: Adding Missing Columns</h2>";

    // Check if food_type column exists in food_posts
    $stmt = $conn->query('DESCRIBE food_posts');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $has_food_type = false;
    $has_email = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'food_type') {
            $has_food_type = true;
        }
    }

    if (!$has_food_type) {
        echo "<p class='info'>Adding food_type column to food_posts table...</p>";
        try {
            $conn->exec('ALTER TABLE food_posts ADD COLUMN food_type VARCHAR(50) NOT NULL DEFAULT "other" AFTER unit');
            echo "<p class='success'>✅ food_type column added successfully!</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error adding food_type column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>✅ food_type column already exists</p>";
    }

    // Check if email column exists in users
    $stmt = $conn->query('DESCRIBE users');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if ($column['Field'] === 'email') {
            $has_email = true;
        }
    }

    if (!$has_email) {
        echo "<p class='info'>Adding email column to users table...</p>";
        try {
            $conn->exec('ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username');
            echo "<p class='success'>✅ email column added successfully!</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error adding email column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>✅ email column already exists</p>";
    }

    // Step 2: Fix indexes
    echo "<h2>Step 2: Fixing Indexes</h2>";

    // Drop all existing indexes first
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

    // Create fresh indexes (only for columns that exist)
    $indexes_to_create = [
        'CREATE INDEX idx_food_posts_status ON food_posts(status)',
        'CREATE INDEX idx_food_posts_user_id ON food_posts(user_id)',
        'CREATE INDEX idx_pickups_post_id ON pickups(post_id)',
        'CREATE INDEX idx_pickups_receiver_id ON pickups(receiver_id)',
        'CREATE INDEX idx_notifications_receiver_id ON notifications(receiver_id)',
        'CREATE INDEX idx_notifications_is_read ON notifications(is_read)'
    ];

    // Only add indexes for columns that exist
    if ($has_food_type) {
        $indexes_to_create[] = 'CREATE INDEX idx_food_posts_food_type ON food_posts(food_type)';
    }
    if ($has_email) {
        $indexes_to_create[] = 'CREATE INDEX idx_users_email ON users(email)';
    }

    foreach ($indexes_to_create as $create_sql) {
        try {
            $conn->exec($create_sql);
            echo "<p class='success'>✅ Created index: " . str_replace('CREATE INDEX ', '', $create_sql) . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error creating index: " . $e->getMessage() . "</p>";
        }
    }

    // Step 3: Test the fixes
    echo "<h2>Step 3: Testing Fixes</h2>";

    // Test food_posts table with food_type
    try {
        $stmt = $conn->query("SELECT id, food_name, food_type FROM food_posts LIMIT 1");
        $result = $stmt->fetch();
        echo "<p class='success'>✅ food_type column accessible in food_posts table</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error accessing food_type column: " . $e->getMessage() . "</p>";
    }

    // Test users table with email
    try {
        $stmt = $conn->query("SELECT id, username, email FROM users LIMIT 1");
        $result = $stmt->fetch();
        echo "<p class='success'>✅ email column accessible in users table</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error accessing email column: " . $e->getMessage() . "</p>";
    }

    // Test the problematic query from my_claimed_foods.php
    try {
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
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error with my claimed foods query: " . $e->getMessage() . "</p>";
    }

    echo "<h2>✅ Complete database fix finished!</h2>";
    echo "<p>All database issues should now be resolved.</p>";
    echo "<p><a href='test_my_claimed_foods.php'>Test my claimed foods page</a></p>";
    echo "<p><a href='register.html'>Test registration with email</a></p>";
    echo "<p><a href='donor_post_food.php'>Test food posting</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body>
</html>";
?>
