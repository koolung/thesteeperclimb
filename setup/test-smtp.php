<?php
/**
 * SMTP Connection Test Script
 * Diagnoses SMTP authentication issues
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "\n=== SMTP Connection Test ===\n\n";

// Test 1: Basic connection
echo "[1/4] Testing basic SMTP connection...\n";
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    
    // Try to connect without auth first
    if (@fsockopen('smtp.hostinger.com', 465, $errno, $errstr, 5)) {
        echo "✓ Port 465 is reachable\n";
    } else {
        echo "✗ Cannot reach port 465: $errstr\n";
        echo "  Try port 587 with TLS instead\n";
    }
} catch (Exception $e) {
    echo "✗ Connection test failed: " . $e->getMessage() . "\n";
}

// Test 2: With authentication
echo "\n[2/4] Testing SMTP authentication...\n";
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->Username = 'thesteeperclimb@bedfordwebservices.com';
    $mail->Password = 'X#ZK~3QbuW|W]3=';
    $mail->Timeout = 15;
    $mail->SMTPDebug = 2;
    
    echo "Username: " . $mail->Username . "\n";
    echo "Attempting authentication...\n";
    
    // This will trigger SMTP connection and auth
    $mail->setFrom('thesteeperclimb@bedfordwebservices.com', 'Test');
    $mail->addAddress('test@example.com');
    $mail->Subject = 'Test';
    $mail->Body = 'Test';
    
    echo "✓ SMTP Configuration set successfully\n";
    
} catch (Exception $e) {
    echo "✗ SMTP Error: " . $e->getMessage() . "\n";
    echo "  Error Code: " . $e->getCode() . "\n";
}

// Test 3: Alternative port
echo "\n[3/4] Testing alternative port 587 (TLS)...\n";
$mail2 = new PHPMailer(true);
try {
    $mail2->isSMTP();
    $mail2->Host = 'smtp.hostinger.com';
    $mail2->Port = 587;
    $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail2->SMTPAuth = true;
    $mail2->Username = 'thesteeperclimb@bedfordwebservices.com';
    $mail2->Password = 'X#ZK~3QbuW|W]3=';
    $mail2->Timeout = 15;
    
    echo "Port 587 configuration set successfully\n";
    echo "Note: Port 587 with STARTTLS might work better\n";
    
} catch (Exception $e) {
    echo "✗ Port 587 Test Error: " . $e->getMessage() . "\n";
}

// Test 4: Send test email (comment out if you don't want to actually send)
echo "\n[4/4] Attempting to send test email...\n";
echo "Note: This will attempt to send a real email\n\n";

$testEmail = 'thesteeperclimb@bedfordwebservices.com';
echo "Do you want to send a test email to $testEmail? (y/n): ";
$handle = fopen("php://stdin", "r");
$response = trim(fgets($handle));
fclose($handle);

if (strtolower($response) === 'y') {
    $mail3 = new PHPMailer(true);
    try {
        $mail3->isSMTP();
        $mail3->Host = 'smtp.hostinger.com';
        $mail3->Port = 465;
        $mail3->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail3->SMTPAuth = true;
        $mail3->Username = 'thesteeperclimb@bedfordwebservices.com';
        $mail3->Password = 'X#ZK~3QbuW|W]3=';
        $mail3->Timeout = 15;
        
        $mail3->setFrom('thesteeperclimb@bedfordwebservices.com', 'The Steeper Climb');
        $mail3->addAddress($testEmail);
        $mail3->Subject = 'SMTP Test Email';
        $mail3->Body = 'If you received this, SMTP is working!';
        $mail3->isHTML(true);
        
        if ($mail3->send()) {
            echo "✓ Test email sent successfully!\n";
        } else {
            echo "✗ Email send failed\n";
        }
    } catch (Exception $e) {
        echo "✗ Error sending test email: " . $e->getMessage() . "\n";
    }
} else {
    echo "Skipped test email send\n";
}

echo "\n=== Test Complete ===\n\n";
?>
