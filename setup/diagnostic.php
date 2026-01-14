<?php
/**
 * SMTP Detailed Diagnostic
 * Shows exact error messages and connection details
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "\n=== Detailed SMTP Diagnostic ===\n\n";

// Enable debug output
$mail = new PHPMailer(true);
$mail->SMTPDebug = 4; // Show all debug output
$mail->Debugoutput = function($str, $level) {
    echo "[DEBUG] $str\n";
};

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->Username = 'thesteeperclimb@bedfordwebservices.com';
    $mail->Password = 'X#ZK~3QbuW|W]3=';
    $mail->Timeout = 20;
    $mail->ConnectTimeout = 20;
    
    echo "Attempting connection to smtp.hostinger.com:465...\n\n";
    
    $mail->setFrom('thesteeperclimb@bedfordwebservices.com', 'Test');
    $mail->addAddress('test@example.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test';
    $mail->Body = 'Test';
    
    // Attempt to send (this will trigger connection and auth)
    if ($mail->send()) {
        echo "\n✓ Success!\n";
    } else {
        echo "\n✗ Send failed: " . $mail->ErrorInfo . "\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Exception: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getCode() . "\n";
} catch (\Throwable $e) {
    echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "\nPossible causes of authentication failure:\n";
echo "1. Email address (thesteeperclimb@bedfordwebservices.com) not configured for SMTP\n";
echo "2. Password is incorrect or expired\n";
echo "3. SMTP access not enabled in Hostinger account\n";
echo "4. Account security settings blocking SMTP\n";
echo "\nNext Steps:\n";
echo "1. Log into Hostinger control panel\n";
echo "2. Check email account settings for SMTP access\n";
echo "3. Reset the password if needed\n";
echo "4. Enable SMTP/POP3 if disabled\n";
?>
