<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/mail.php';

// Add styling for better readability
echo '<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; }
    .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; }
    .info { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>';

try {
    echo "<h2>Email Test</h2>";
    
    // Show current settings
    echo "<div class='info'>";
    echo "<h3>Current Settings:</h3>";
    echo "MAIL_USERNAME: " . getenv('MAIL_USERNAME') . "<br>";
    echo "MAIL_PASSWORD: " . (getenv('MAIL_PASSWORD') ? str_repeat('*', 4) . substr(getenv('MAIL_PASSWORD'), -4) : 'Not set') . "<br>";
    echo "MAIL_FROM_NAME: " . getenv('MAIL_FROM_NAME') . "<br>";
    echo "</div>";

    // Test email to yourself
    echo "<div class='info'>";
    echo "Attempting to send test email to: " . getenv('MAIL_USERNAME') . "<br>";
    
    $result = sendVerificationEmail(
        getenv('MAIL_USERNAME'), // Send to your own email
        'Test User',
        '123456'
    );
    
    if ($result) {
        echo "<div class='success'>Email sent successfully!</div>";
    } else {
        echo "<div class='error'>Failed to send email.</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "</div>";
}

// Check PHP Mail Configuration
echo "<h3>PHP Mail Configuration:</h3>";
echo "<div class='info'>";
echo "OpenSSL Enabled: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "<br>";
echo "SMTP Port Open: " . (fsockopen('smtp.gmail.com', 587, $errno, $errstr, 5) ? 'Yes' : 'No') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "</div>";

// Check if .env file is readable
echo "<h3>Environment File Check:</h3>";
echo "<div class='info'>";
$env_file = __DIR__ . '/.env';
echo ".env file exists: " . (file_exists($env_file) ? 'Yes' : 'No') . "<br>";
echo ".env file readable: " . (is_readable($env_file) ? 'Yes' : 'No') . "<br>";
echo "</div>"; 