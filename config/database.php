<?php
/**
 * Database Configuration
 * Loads credentials from environment variables
 */

// Load environment variables
require_once __DIR__ . '/environment.php';
loadEnvironmentVariables();

// Define database constants from environment variables
define('DB_HOST', getEnv('DB_HOST', 'localhost'));
define('DB_USER', getEnv('DB_USER'));
define('DB_PASS', getEnv('DB_PASS'));
define('DB_NAME', getEnv('DB_NAME'));
define('DB_CHARSET', getEnv('DB_CHARSET', 'utf8mb4'));

// Validate required database environment variables
if (!DB_USER || !DB_PASS || !DB_NAME) {
    die('Error: Missing required database environment variables. Please check your .env file.');
}

// Create database connection
function getDatabaseConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Get main database connection (database must exist)
function getMainDatabaseConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
?>
