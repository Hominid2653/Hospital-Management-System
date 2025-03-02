<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M'); // Increase memory limit if needed

$base_url = './'; // Adjust based on the file's location relative to root

require_once 'config/database.php';

try {
    // Use transaction for better data integrity
    $pdo->beginTransaction();

    // Check database connection with timeout
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 seconds timeout
    if (!$pdo->query('SELECT 1')) {
        throw new Exception("Database connection lost");
    }

    // Check if users table exists - optimized query
    $tableExists = $pdo->query("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "' 
        AND table_name = 'users'
    ")->fetchColumn();

    if (!$tableExists) {
        // Create users table with proper indexing
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                UNIQUE KEY unique_username (username),
                UNIQUE KEY unique_email (email),
                KEY idx_status (status),
                KEY idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Prepare user data
    $username = 'Elias Cheruiyot';
    $email = 'eliascheruiyot9@gmail.com';
    $password = password_hash('123456', PASSWORD_DEFAULT, ['cost' => 12]);

    // Check existing user with single query
    $stmt = $pdo->prepare("
        SELECT 'username' as type FROM users WHERE username = :username
        UNION ALL
        SELECT 'email' as type FROM users WHERE email = :email
    ");
    
    $stmt->execute([
        ':username' => $username,
        ':email' => $email
    ]);
    
    $exists = $stmt->fetch();

    if ($exists) {
        echo "User already exists with this " . $exists['type'] . "!<br>";
    } else {
        // Create new user with prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password) 
            VALUES (:username, :email, :password)
        ");
        
        $success = $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $password
        ]);
        
        if ($success) {
            $pdo->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => [
                    'username' => $username,
                    'email' => $email
                ]
            ]);
        } else {
            throw new Exception("Failed to create user");
        }
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("User creation error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while creating user'
    ]);
} finally {
    // Clean up resources
    $stmt = null;
    $pdo = null;
}

// Display current database status
try {
    echo "<br>Current users in database:<br>";
    $users = $pdo->query("SELECT id, username, email FROM users")->fetchAll();
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error fetching users: " . $e->getMessage();
}
?> 