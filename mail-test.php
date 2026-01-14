<?php
/**
 * Test Email Configuration
 * Run this to diagnose email sending issues
 */

// Check PHP configuration
echo "=== PHP Mail Configuration ===\n\n";

// Check if mail() function is available
if (function_exists('mail')) {
    echo "✓ mail() function is available\n";
} else {
    echo "✗ mail() function is NOT available\n";
}

// Check sendmail path
$sendmail = ini_get('sendmail_path');
echo "Sendmail Path: " . ($sendmail ? $sendmail : "Not configured (using Windows SMTP)\n");

// Check SMTP setting (Windows)
$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
echo "SMTP Host: " . ($smtp ? $smtp : "localhost") . "\n";
echo "SMTP Port: " . ($smtp_port ? $smtp_port : "25") . "\n";

// Check mail.from
$mail_from = ini_get('sendmail_from');
echo "Mail From: " . ($mail_from ? $mail_from : "Not set\n");

echo "\n=== Test Email Send ===\n\n";

// Attempt to send test email
$to = 'test@example.com';
$subject = 'Test Email - The Steeper Climb';
$message = 'This is a test email from The Steeper Climb platform.';
$headers = "From: thesteeperclimb@bedfordwebservices.com\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✓ Mail function executed successfully\n";
    echo "Note: This doesn't guarantee the email was actually delivered.\n";
    echo "Check error logs or spam folder.\n";
} else {
    echo "✗ Mail function failed\n";
    echo "Check that your server is configured for email sending.\n";
}

// Check error log location
echo "\n=== Troubleshooting ===\n\n";
echo "Error Log: " . ini_get('error_log') . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Additional info
echo "\n=== Email Sending Tips ===\n";
echo "1. On Windows, mail() uses SMTP relay\n";
echo "2. On Linux, mail() uses sendmail\n";
echo "3. Check XAMPP mail settings in php.ini\n";
echo "4. For XAMPP, check: xampp/sendmail/sendmail.ini\n";
echo "5. Verify firewall isn't blocking outbound connections\n";
?>
