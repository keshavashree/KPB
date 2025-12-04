<?php
// Database Setup Script for FoodShare
// Run this script to set up the database and required tables

echo "<h1>FoodShare Database Setup</h1>";

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

    // Read and execute the schema file
    $schema = file_get_contents('database_schema.sql');

    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
            $conn->exec($statement);
        }
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
    echo "</ul>";

} catch(PDOException $e) {
    echo "<h2>Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>MySQL server is running</li>";
    echo "<li>You have the correct database credentials</li>";
    echo "<li>The database_schema.sql file exists</li>";
    echo "</ul>";
} catch(Exception $e) {
    echo "<h2>Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$conn = null;
?>
