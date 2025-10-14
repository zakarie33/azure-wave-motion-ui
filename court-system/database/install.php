<?php
/**
 * Database Installation Script
 * Run this file to create the database and tables
 */

require_once '../config/config.php';

// Database connection without selecting a database
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h2>Court Management System - Database Installation</h2>\n";
    
    // Create database if it doesn't exist
    echo "<p>Creating database '" . DB_NAME . "'...</p>\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "<p style='color: green;'>✓ Database created successfully!</p>\n";
    
    // Read and execute schema
    $schema = file_get_contents('schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql file");
    }
    
    echo "<p>Executing database schema...</p>\n";
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Some statements might fail (like DROP TABLE IF EXISTS for non-existent tables)
            // We'll continue but log the error
            if (strpos($statement, 'DROP TABLE') === false) {
                echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>\n";
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Database schema created successfully!</p>\n";
    
    // Verify installation by checking tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Created tables: " . implode(', ', $tables) . "</p>\n";
    
    // Check if admin user exists
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminExists > 0) {
        echo "<p style='color: green;'>✓ Default admin user created!</p>\n";
        echo "<p><strong>Login credentials:</strong><br>";
        echo "Email: admin@court.example.com<br>";
        echo "Password: admin123</p>\n";
        echo "<p style='color: red;'><strong>IMPORTANT:</strong> Please change the default admin password after first login!</p>\n";
    }
    
    echo "<h3 style='color: green;'>Installation completed successfully!</h3>\n";
    echo "<p><a href='../index.php'>Go to Court Management System</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database configuration in config/config.php</p>\n";
}
?>