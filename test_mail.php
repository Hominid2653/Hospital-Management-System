<?php
require_once 'config/mail.php';

try {
    // Create test message
    $result = sendVerificationEmail(
        'eliascheruiyot9@gmail.com', // Your actual test email
        'Test User',
        '123456'
    );

    if ($result) {
        echo '<div style="color: green; padding: 20px; background: #e8f5e9; border-radius: 5px; margin: 20px;">
            <h3>✅ Email Test Successful!</h3>
            <p>The test email was sent successfully. Please check your inbox.</p>
        </div>';
    } else {
        echo '<div style="color: red; padding: 20px; background: #ffebee; border-radius: 5px; margin: 20px;">
            <h3>❌ Email Test Failed</h3>
            <p>The email could not be sent. Check the error log for details.</p>
        </div>';
    }
} catch (Exception $e) {
    echo '<div style="color: red; padding: 20px; background: #ffebee; border-radius: 5px; margin: 20px;">
        <h3>❌ Connection Error</h3>
        <p>Error details: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}

// Display current settings (without showing full password)
echo '<div style="padding: 20px; background: #f5f5f5; border-radius: 5px; margin: 20px;">
    <h3>Current Settings:</h3>
    <pre>';
echo "MAIL_USERNAME: " . $_ENV['MAIL_USERNAME'] . "\n";
echo "MAIL_PASSWORD: " . substr($_ENV['MAIL_PASSWORD'], 0, 4) . "..." . "\n";
echo "MAIL_FROM_NAME: " . $_ENV['MAIL_FROM_NAME'] . "\n";
echo '</pre></div>'; 