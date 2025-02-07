<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without database selected
    $pdo = new PDO(
        "mysql:host=$host",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Read and execute the SQL schema
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($sql);
    
    echo "Database and tables created successfully!\n";
    
    // Create default admin user
    $pdo->exec("USE SianRosesMedical");
    
    // Check if admin user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute(['admin']);
    $existingUser = $checkStmt->fetch();
    
    if (!$existingUser) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $defaultUsername = 'admin';
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute([$defaultUsername, $defaultPassword]);
        echo "Default admin user created (username: admin, password: admin123)\n";
    } else {
        echo "Admin user already exists\n";
    }
    
    // Verify the user was created
    $verifyStmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
    $verifyStmt->execute(['admin']);
    $user = $verifyStmt->fetch();
    
    if ($user) {
        echo "Verified: Admin user exists with ID: " . $user['id'] . "\n";
    } else {
        echo "WARNING: Admin user creation failed!\n";
    }
    
} catch(PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 