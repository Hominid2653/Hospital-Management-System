<?php
// Add these lines at the very top of login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log'); // This will create error.log in your project directory

session_start();
require_once 'config/database.php';
require_once 'config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$verification_sent = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // This is an AJAX request
    header('Content-Type: text/html; charset=utf-8');
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $verification_code = sprintf("%06d", mt_rand(100000, 999999));
            
            $_SESSION['verification'] = [
                'code' => $verification_code,
                'user_id' => $user['id'],
                'username' => $user['username'],
                'expires' => time() + (5 * 60),
                'password' => $password
            ];
            
            // Add debug logging
            error_log("Attempting to send verification email to: " . $user['email'] . " with username: " . $user['username']);
            
            if (sendVerificationEmail($user['email'], $user['username'], $verification_code)) {
                echo '<div class="message"><i class="fas fa-check-circle"></i>Verification code has been sent to your email</div>';
                $verification_sent = true;
            } else {
                echo '<div class="error-message"><i class="fas fa-exclamation-circle"></i>Failed to send verification code. Please try again.</div>';
            }
        } else {
            echo '<div class="error-message"><i class="fas fa-exclamation-circle"></i>Invalid username or password</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error-message"><i class="fas fa-exclamation-circle"></i>An error occurred. Please try again.</div>';
        error_log("Database error: " . $e->getMessage());
    }
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug information
    error_log("POST request received: " . json_encode($_POST));
    
    if (isset($_POST['verify'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Debug log
        error_log("Verifying credentials for username: $username");
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Debug log
            error_log("User found: " . ($user ? 'yes' : 'no'));
            
            if ($user && password_verify($password, $user['password'])) {
                $verification_code = sprintf("%06d", mt_rand(100000, 999999));
                
                $_SESSION['verification'] = [
                    'code' => $verification_code,
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'expires' => time() + (5 * 60),
                    'password' => $password  // Store password temporarily
                ];
                
                // Debug log
                error_log("Attempting to send email to: " . $user['email']);
                
                if (sendVerificationEmail($user['email'], $user['username'], $verification_code)) {
                    $verification_sent = true;
                    $message = "Verification code has been sent to your email";
                    // Debug log
                    error_log("Email sent successfully");
                } else {
                    $error = "Failed to send verification code. Please try again.";
                    error_log("Failed to send email");
                }
            } else {
                $error = "Invalid username or password";
                error_log("Invalid credentials");
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again.";
            error_log("Database error: " . $e->getMessage());
        }
    } else if (isset($_POST['login'])) {
        if (!isset($_SESSION['verification'])) {
            $error = "Please verify your credentials first";
        } else if (time() > $_SESSION['verification']['expires']) {
            $error = "Verification code has expired. Please try again";
            unset($_SESSION['verification']);
        } else if ($_POST['verification_code'] !== $_SESSION['verification']['code']) {
            $error = "Invalid verification code";
        } else {
            $_SESSION['user_id'] = $_SESSION['verification']['user_id'];
            $_SESSION['username'] = $_SESSION['verification']['username'];
            unset($_SESSION['verification']);
            header("Location: index.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sian Medical Records</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('assets/images/bg.jpg') no-repeat center center fixed;
            background-size: cover;
            padding: 1rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .card-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(231, 84, 128, 0.1);
        }

        .message {
            background: rgba(46, 125, 50, 0.1);
            color: #2e7d32;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .verification-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .verification-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verification-section p {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .verification-code {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            padding: 1rem !important;
        }

        .btn-verify, .btn-login {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-verify {
            width: 100%;
            background: var(--secondary);
            color: white;
            margin: 1rem 0;
        }

        .btn-login {
            width: 100%;
            background: var(--primary);
            color: white;
            margin-top: 1rem;
        }

        .btn-verify:hover, .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Loading indicator for verify button */
        .btn-verify.loading {
            position: relative;
            color: transparent;
        }

        .btn-verify.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h1>Sian Medical</h1>
                <p>Please login to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           value="<?php echo isset($_SESSION['verification']['password']) ? 
                                        htmlspecialchars($_SESSION['verification']['password']) : ''; ?>"
                           required>
                </div>

                <button type="submit" name="verify" class="btn-verify" id="verifyBtn">
                    <i class="fas fa-shield-alt"></i>
                    Verify Credentials
                </button>

                <div class="verification-section <?php echo $verification_sent ? 'active' : ''; ?>">
                    <div class="form-group">
                        <label for="verification_code">Enter Verification Code</label>
                        <input type="text" 
                               id="verification_code" 
                               name="verification_code" 
                               class="verification-code" 
                               maxlength="6" 
                               placeholder="000000" 
                               <?php echo $verification_sent ? 'required' : ''; ?>>
                    </div>

                    <button type="submit" name="login" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add this after your existing script tags
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            if (e.submitter && e.submitter.name === 'verify') {
                e.preventDefault(); // Prevent form submission
                
                const verifyBtn = e.submitter;
                verifyBtn.classList.add('loading');
                
                try {
                    const formData = new FormData(this);
                    formData.append('verify', '1');
                    
                    const response = await fetch('login.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.text();
                    
                    // Parse the response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(result, 'text/html');
                    
                    // Check for error message
                    const errorDiv = doc.querySelector('.error-message');
                    const messageDiv = doc.querySelector('.message');
                    const verificationSection = doc.querySelector('.verification-section');
                    
                    // Clear existing messages
                    document.querySelectorAll('.error-message, .message').forEach(el => el.remove());
                    
                    if (errorDiv) {
                        // Show error if present
                        document.querySelector('.card-header').insertAdjacentElement('afterend', errorDiv);
                    } else if (messageDiv) {
                        // Show success message and verification section
                        document.querySelector('.card-header').insertAdjacentElement('afterend', messageDiv);
                        document.querySelector('.verification-section').classList.add('active');
                    }
                    
                } catch (error) {
                    console.error('Error:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i>
                        An error occurred. Please try again.
                    `;
                    document.querySelector('.card-header').insertAdjacentElement('afterend', errorDiv);
                } finally {
                    verifyBtn.classList.remove('loading');
                }
            }
        });

        // Auto-format verification code input
        document.getElementById('verification_code')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html> 