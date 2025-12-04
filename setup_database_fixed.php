<?php
// Database Setup Script for FoodShare (Fixed)
// Run this script to set up the database and required tables

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>FoodShare Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .status-item {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2ecc71;
            background: #f8f9fa;
        }
        .status-item.error {
            border-left-color: #e74c3c;
            background: #fdf2f2;
        }
        .status-item.warning {
            border-left-color: #f39c12;
            background: #fef9f2;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .warning {
            color: #f39c12;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer li {
            display: inline-block;
            margin: 0 15px;
        }
        .footer a {
            color: #6c757d;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .footer a:hover {
            background: #6c757d;
            color: white;
        }
        .progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            height: 100%;
            border-radius: 10px;
            width: 100%;
            animation: progress 2s ease-in-out;
        }
        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🍽️ FoodShare Database Setup</h1>
            <p>Setting up your food sharing platform database</p>
        </div>
        <div class='content'>";

echo "<h1>🍽️ FoodShare Database Setup</h1>";

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodshare";

try {
    // Create connection without database first
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<p>✅ Database '$dbname' created or already exists.</p>";

    // Select the database
    $conn->exec("USE $dbname");

    // Check if tables exist and their structure
    $tables_exist = [];
    $tables_to_check = ['users', 'food_posts', 'pickups', 'notifications'];

    foreach ($tables_to_check as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $tables_exist[$table] = $stmt->rowCount() > 0;
    }

    // If tables exist but might be missing email column, recreate them
    if ($tables_exist['users']) {
        // Check if users table has email column
        $stmt = $conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $has_email = false;

        foreach ($columns as $column) {
            if ($column['Field'] === 'email') {
                $has_email = true;
                break;
            }
        }

        if (!$has_email) {
            echo "<p>⚠️ Users table exists but missing email column. Recreating tables...</p>";

            // Drop existing tables
            $conn->exec("DROP TABLE IF EXISTS notifications");
            $conn->exec("DROP TABLE IF EXISTS pickups");
            $conn->exec("DROP TABLE IF EXISTS food_posts");
            $conn->exec("DROP TABLE IF EXISTS users");

            $tables_exist = [];
        }
    }

    // Read and execute the fixed schema file
    $schema = file_get_contents('database_schema_fixed.sql');

    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE|INSERT)/i', $statement)) {
            try {
                $conn->exec($statement);
                echo "<p>✅ Executed: " . htmlspecialchars(substr($statement, 0, 50)) . "...</p>";
            } catch (PDOException $e) {
                // Skip if table/index already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p class='error'>❌ Error executing: " . htmlspecialchars($statement) . "</p>";
                    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
                }
            }
        }
    }

    // Insert sample data separately to handle any issues
    try {
        // Check if users table has email column
        $stmt = $conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $has_email = false;

        foreach ($columns as $column) {
            if ($column['Field'] === 'email') {
                $has_email = true;
                break;
            }
        }

        if ($has_email) {
            $sample_users = [
                ['donor1', 'donor1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'Local Restaurant', '+1234567890'],
                ['receiver1', 'receiver1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receiver', 'Community Center', '+1234567891'],
                ['admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'FoodShare Admin', '+1234567892']
            ];

            $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, role, organization_name, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
        } else {
            // Fallback for tables without email column
            $sample_users = [
                ['donor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'Local Restaurant', '+1234567890'],
                ['receiver1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receiver', 'Community Center', '+1234567891'],
                ['admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'FoodShare Admin', '+1234567892']
            ];

            $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, role, organization_name, contact_number) VALUES (?, ?, ?, ?, ?)");
        }

        foreach ($sample_users as $user) {
            $stmt->execute($user);
        }

        echo "<p>✅ Sample users created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error creating sample users: " . $e->getMessage() . "</p>";
    }

    echo "<p>✅ Database tables created successfully!</p>";

    // Create uploads directory
    $upload_dir = 'uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p>✅ Uploads directory created.</p>";
    } else {
        echo "<p>✅ Uploads directory already exists.</p>";
    }

    // Set proper permissions for uploads directory
    chmod($upload_dir, 0755);
    echo "<p>✅ Uploads directory permissions set.</p>";

    echo "<h2>Setup Complete!</h2>";
    echo "<p>Your FoodShare application is now ready to use.</p>";
    echo "<ul>";
    echo "<li><a href='register.html'>Register a new account</a></li>";
    echo "<li><a href='login.html'>Login to existing account</a></li>";
    echo "<li><a href='index.php'>Go to homepage</a></li>";
    echo "<li><a href='test_database.php'>Test database connection</a></li>";
    echo "</ul>";

} catch(PDOException $e) {
    echo "<h2>Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>MySQL server is running</li>";
    echo "<li>You have the correct database credentials</li>";
    echo "<li>The database_schema_fixed.sql file exists</li>";
    echo "</ul>";
} catch(Exception $e) {
    echo "<h2>Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup_database_fixed.php'>Run Setup Again</a></p>";
}

$conn = null;

echo "        </div>
        <div class='footer'>
            <ul>
                <li><a href='register.html'>Register</a></li>
                <li><a href='login.html'>Login</a></li>
                <li><a href='index.php'>Homepage</a></li>
                <li><a href='test_database.php'>Test DB</a></li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
