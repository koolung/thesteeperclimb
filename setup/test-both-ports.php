<?php
/**
 * Dual Port SMTP Test
 * Tests both 465 (SSL) and 587 (TLS) to find which works
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$testEmail = 'taeyun.koolung@gmail.com';
$credentials = [
    'host' => 'smtp.hostinger.com',
    'username' => 'thesteeperclimb@bedfordwebservices.com',
    'password' => 'X#ZK~3QbuW|W]3=',
];

echo "\n=== Testing SMTP Ports ===\n";
echo "Host: {$credentials['host']}\n";
echo "Username: {$credentials['username']}\n\n";

// Test Port 465 with SSL
echo "[Test 1] Port 465 with SSL (SMTPS)...\n";
$mail1 = new PHPMailer(true);
try {
    $mail1->isSMTP();
    $mail1->Host = $credentials['host'];
    $mail1->Port = 465;
    $mail1->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail1->SMTPAuth = true;
    $mail1->Username = $credentials['username'];
    $mail1->Password = $credentials['password'];
    $mail1->Timeout = 15;
    
    $mail1->setFrom($credentials['username'], 'Test');
    $mail1->addAddress($testEmail);
    $mail1->isHTML(true);
    $mail1->Subject = 'Port 465 SSL Test';
    $mail1->Body = '<h1>Port 465 Test</h1><p>If you see this, Port 465 SSL works!</p>';
    
    if ($mail1->send()) {
        echo "✓ Port 465 WORKS!\n";
        echo "  Email sent successfully to: $testEmail\n";
    } else {
        echo "✗ Port 465 failed to send\n";
    }
} catch (Exception $e) {
    echo "✗ Port 465 Error: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "✗ Port 465 Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n[Test 2] Port 587 with TLS (STARTTLS)...\n";
$mail2 = new PHPMailer(true);
try {
    $mail2->isSMTP();
    $mail2->Host = $credentials['host'];
    $mail2->Port = 587;
    $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail2->SMTPAuth = true;
    $mail2->Username = $credentials['username'];
    $mail2->Password = $credentials['password'];
    $mail2->Timeout = 15;
    
    $mail2->setFrom($credentials['username'], 'Test');
    $mail2->addAddress($testEmail);
    $mail2->isHTML(true);
    $mail2->Subject = 'Port 587 TLS Test';
    $mail2->Body = '<h1>Port 587 Test</h1><p>If you see this, Port 587 TLS works!</p>';
    
    if ($mail2->send()) {
        echo "✓ Port 587 WORKS!\n";
        echo "  Email sent successfully to: $testEmail\n";
    } else {
        echo "✗ Port 587 failed to send\n";
    }
} catch (Exception $e) {
    echo "✗ Port 587 Error: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "✗ Port 587 Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nRecommendation: Update Mailer.php to use the working port/encryption\n";
?>
