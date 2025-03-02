<?php
// Database configuration with connection pooling and optimization
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'SianRosesMedical');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . 
        ";dbname=" . DB_NAME . 
        ";charset=utf8mb4" .
        ";persistent=true", // Enable connection pooling
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true, // Keep connections alive
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Buffer results
            PDO::ATTR_STRINGIFY_FETCHES => false, // Don't convert numbers to strings
            PDO::MYSQL_ATTR_FOUND_ROWS => true // Return found rows instead of affected rows
        ]
    );
    
    // Set session SQL mode for better performance
    $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}
?> 