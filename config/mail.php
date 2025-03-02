<?php
// First check if composer autoload exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    error_log('Composer autoload file not found. Please run "composer install"');
    die('Internal server error. Please run "composer install" first.');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Verify Dotenv class exists
if (!class_exists('Dotenv\Dotenv')) {
    error_log('Dotenv class not found. Please run "composer install"');
    die('Internal server error. Dependencies not installed correctly.');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Initialize PHPMailer with exceptions enabled
PHPMailer::$validator = 'php';

// Check for .env file
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    error_log('.env file not found at: ' . $envFile);
    die('Configuration error: .env file not found');
}

try {
    // Load environment variables with explicit path
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
    
    // Verify required environment variables
    $required_vars = ['MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_NAME'];
    foreach ($required_vars as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Missing required environment variable: $var");
        }
    }
} catch (Exception $e) {
    error_log("Environment configuration error: " . $e->getMessage());
    die("Configuration error: " . $e->getMessage());
}

// Add this right after the require_once line
$required_extensions = ['openssl', 'pdo', 'pdo_mysql'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        error_log("Required PHP extension not loaded: $ext");
        throw new Exception("Required PHP extension not loaded: $ext");
    }
}

function sendVerificationEmail($to, $username, $code) {
    try {
        error_log("Initializing mail send process");
        error_log("Target email: " . $to);
        
        $mail = new PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        
        // Get and verify credentials
        $smtp_username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
        $smtp_password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');
        $from_name = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME');
        
        error_log("SMTP Username length: " . strlen($smtp_username));
        error_log("SMTP Password length: " . strlen($smtp_password));
        
        if (empty($smtp_username) || empty($smtp_password)) {
            throw new Exception("SMTP credentials missing");
        }

        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Longer timeout for slow connections
        $mail->Timeout = 60;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom($smtp_username, $from_name);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Login Verification Code - Sian Medical';
        
        // Enhanced email body with better styling
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c614f; margin-bottom: 20px;'>Login Verification</h2>
                <p>Hello $username,</p>
                <p>Your verification code for Sian Medical login is:</p>
                <div style='background-color: #f5f5f5; padding: 15px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #2c614f; letter-spacing: 5px; margin: 0;'>$code</h1>
                </div>
                <p>This code will expire in 5 minutes.</p>
                <p style='color: #666; font-size: 0.9em;'>If you didn't request this code, please ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your verification code is: $code";

        error_log("Attempting to send email...");
        $result = $mail->send();
        error_log("Email sent successfully");
        return true;

    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        if (isset($mail)) {
            error_log("SMTP Error: " . $mail->ErrorInfo);
        }
        return false;
    }
}

// Test the email configuration immediately
try {
    error_log("Testing email configuration...");
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    // Verify environment variables are loaded
    $vars = ['MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_NAME'];
    foreach ($vars as $var) {
        $value = getenv($var);
        error_log("$var is " . (empty($value) ? "empty" : "set"));
    }
    
} catch (Exception $e) {
    error_log("Environment Error: " . $e->getMessage());
}

return [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => getenv('MAIL_USERNAME'),
    'password' => getenv('MAIL_PASSWORD'),
    'from' => [
        'address' => getenv('MAIL_USERNAME'),
        'name' => getenv('MAIL_FROM_NAME')
    ],
    'sendmail' => '/usr/sbin/sendmail -bs',
    'pretend' => false,
]; 