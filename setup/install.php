<?php
/**
 * Database Installation Script
 * Run this once to create the database and tables
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Create database if not exists
    $pdo = getDatabaseConnection();
    
    $sql = "CREATE DATABASE IF NOT EXISTS `thesteeperclimb`;";
    $pdo->exec($sql);
    
    // Use the database
    $pdo = getMainDatabaseConnection();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Database and tables created successfully!<br>";
    echo "✓ You can now proceed to create the initial admin account.<br>";
    
    // Create initial admin (optional - can be done through registration)
    echo "<br><a href='create-admin.php'>Create Initial Admin Account</a>";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
    exit(1);
}
?>
