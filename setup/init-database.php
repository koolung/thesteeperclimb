<?php
/**
 * Database Initialization Script
 * Runs the database.sql file to create all tables
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbName = 'thesteeperclimb';

try {
    // Connect to MySQL
    $conn = new mysqli($host, $user, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "[INFO] Connected to MySQL\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        die("[ERROR] database.sql file not found at: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("[ERROR] Could not read database.sql file\n");
    }
    
    echo "[INFO] Read database.sql file (" . strlen($sql) . " bytes)\n";
    
    // Execute SQL statements
    $statements = array_filter(array_map('trim', explode(';', $sql)), function($s) {
        return !empty($s) && !str_starts_with($s, '--');
    });
    
    $count = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        if (!$conn->query($statement . ';')) {
            echo "[ERROR] SQL Error: " . $conn->error . "\n";
            echo "[ERROR] Statement: " . substr($statement, 0, 100) . "...\n";
        } else {
            $count++;
        }
    }
    
    echo "[SUCCESS] Database initialized! Executed $count SQL statements\n";
    
    // Verify tables exist
    $result = $conn->query("SHOW TABLES FROM $dbName");
    echo "[INFO] Tables in database:\n";
    while ($row = $result->fetch_row()) {
        echo "  - " . $row[0] . "\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die("[FATAL] " . $e->getMessage() . "\n");
}
?>
