<?php
/**
 * Email Mailer Class
 * Handles sending emails via PHPMailer with SMTP
 */

namespace Utils;

// Load PHPMailer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static $host = 'smtp.titan.email';
    private static $port = 465;
    private static $username = 'thesteeperclimb@bedfordwebservices.com';
    private static $password = 'X#ZK~3QbuW|W]3=';
    private static $fromEmail = 'thesteeperclimb@bedfordwebservices.com';
    private static $fromName = 'The Steeper Climb';

    /**
     * Send an email using PHPMailer
     */
    public static function send($to, $subject, $htmlBody, $textBody = '')
    {
        try {
            // Create PHPMailer instance
            $mail = new PHPMailer(true);

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = self::$host;
            $mail->SMTPAuth = true;
            $mail->Username = self::$username;
            $mail->Password = self::$password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL on port 465
            $mail->Port = self::$port;

            // Set timeout
            $mail->Timeout = 10;

            // Sender
            $mail->setFrom(self::$fromEmail, self::$fromName);
            
            // Recipient
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = !empty($textBody) ? $textBody : strip_tags($htmlBody);

            // Send
            $mail->send();
            
            error_log("✓ Email sent successfully to: $to. Subject: $subject");
            return true;

        } catch (Exception $e) {
            $errorMsg = "PHPMailer Error: " . $e->getMessage();
            error_log("✗ " . $errorMsg);
            error_log("Error Code: " . $e->getCode());
            error_log("SMTP Host: " . self::$host . ":" . self::$port);
            error_log("To: " . $to);
            error_log("Subject: " . $subject);
            return false;
        } catch (\Exception $e) {
            $errorMsg = "General Email Error: " . $e->getMessage();
            error_log("✗ " . $errorMsg);
            return false;
        }
    }

    /**
     * Send organization welcome email
     */
    public static function sendOrganizationWelcome($organizationEmail, $organizationName, $contactPerson, $setupLink)
    {
        $subject = "Welcome to The Steeper Climb - Set Up Your Account";
        
        $htmlBody = self::getOrganizationWelcomeTemplate($organizationEmail, $organizationName, $contactPerson, $setupLink);
        $textBody = "Welcome to The Steeper Climb!\n\nYour organization account has been created. Please click the link below to set up your password:\n\n" . $setupLink;

        return self::send($organizationEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Get organization welcome email template
     */
    private static function getOrganizationWelcomeTemplate($organizationEmail, $organizationName, $contactPerson, $setupLink)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .greeting strong {
            color: #667eea;
        }
        
        .message {
            font-size: 15px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .message p {
            margin-bottom: 15px;
        }
        
        .cta-section {
            margin: 40px 0;
            text-align: center;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
        }
        
        .setup-info {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 30px 0;
            border-radius: 4px;
        }
        
        .setup-info h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .setup-info p {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .link-text {
            font-size: 13px;
            color: #999;
            word-break: break-all;
            background: white;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
            border: 1px solid #ddd;
        }
        
        .features {
            background: white;
            padding: 30px;
            margin: 30px 0;
        }
        
        .features h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
        }
        
        .feature-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .footer {
            background: #f5f5f5;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        
        .footer p {
            font-size: 13px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin-top: 15px;
        }
        
        .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            margin: 0 5px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            text-decoration: none;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .cta-button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🚀 Welcome to The Steeper Climb</h1>
            <p>Your Organization's Learning Platform</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello <strong>{$contactPerson}</strong>,
            </div>

            <div class="message">
                <p>Welcome to <strong>The Steeper Climb</strong>! We're thrilled to have your organization join our platform.</p>
                
                <p>Your organization account has been successfully created. To get started and begin managing your courses and students, please set up your account password by clicking the button below.</p>
            </div>

            <!-- CTA Button -->
            <div class="cta-section">
                <a href="{$setupLink}" class="cta-button">Set Up Your Password</a>
            </div>

            <!-- Setup Information -->
            <div class="setup-info">
                <h3>Getting Started</h3>
                <p><strong>Organization Email:</strong> {$organizationEmail}</p>
                <p><strong>Organization Name:</strong> {$organizationName}</p>
                <p style="margin-top: 15px; font-size: 13px; color: #999;">If you didn't click the button above, copy and paste this link in your browser:</p>
                <div class="link-text">{$setupLink}</div>
            </div>

            <!-- Features -->
            <div class="features">
                <h3>What You Can Do:</h3>
                <ul class="feature-list">
                    <li>Manage student accounts and enrollments</li>
                    <li>Access course materials and content</li>
                    <li>Track student progress in real-time</li>
                    <li>View detailed progress reports</li>
                    <li>Issue and manage certificates</li>
                </ul>
            </div>

            <div class="message">
                <p>If you have any questions or need assistance, our support team is here to help. Don't hesitate to reach out!</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2026 The Steeper Climb. All rights reserved.</p>
            <p>
                <a href="https://thesteeperclimb.com">Visit Our Website</a> | 
                <a href="https://thesteeperclimb.com/help">Help Center</a> | 
                <a href="https://thesteeperclimb.com/contact">Contact Us</a>
            </p>
            <p style="margin-top: 20px; font-size: 12px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Send student welcome email
     */
    public static function sendStudentWelcome($studentEmail, $studentName, $organizationName, $setupLink)
    {
        $subject = "Welcome to The Steeper Climb - Set Up Your Account";
        
        $htmlBody = self::getStudentWelcomeTemplate($studentEmail, $studentName, $organizationName, $setupLink);
        $textBody = "Welcome to The Steeper Climb!\n\nYour account has been created by $organizationName. Please click the link below to set up your password:\n\n" . $setupLink;

        return self::send($studentEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Get student welcome email template
     */
    private static function getStudentWelcomeTemplate($studentEmail, $studentName, $organizationName, $setupLink)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            background: white;
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .greeting strong {
            color: #667eea;
        }
        
        .message {
            font-size: 15px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .message p {
            margin-bottom: 15px;
        }
        
        .cta-section {
            margin: 40px 0;
            text-align: center;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
        }
        
        .setup-info {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 30px 0;
            border-radius: 4px;
        }
        
        .setup-info h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .setup-info p {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }
        
        .setup-info p:last-child {
            margin-bottom: 0;
        }
        
        .link-area {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 12px;
            color: #666;
        }
        
        .divider {
            border: none;
            border-top: 1px solid #eee;
            margin: 30px 0;
        }
        
        .footer {
            background: #f5f5f5;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        
        .footer p {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .cta-button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🚀 Welcome to The Steeper Climb</h1>
            <p>Start Your Learning Journey</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hi <strong>$studentName</strong>,
            </div>
            
            <div class="message">
                <p>Your learning account has been created by <strong>$organizationName</strong>. We're excited to have you on board!</p>
                
                <p>To get started, you'll need to set up your password and access your personalized learning experience. Click the button below to complete your account setup.</p>
            </div>
            
            <!-- CTA Button -->
            <div class="cta-section">
                <a href="$setupLink" class="cta-button">Set Up Your Password</a>
            </div>
            
            <!-- Setup Info Box -->
            <div class="setup-info">
                <h3>📝 Account Setup Instructions</h3>
                <p>1. Click the button above or paste this link in your browser:</p>
                <div class="link-area">$setupLink</div>
                <p>2. Create a strong password for your account</p>
                <p>3. Log in and start learning!</p>
            </div>
            
            <hr class="divider">
            
            <div class="message">
                <p>If you have any questions or need assistance getting started, please don't hesitate to reach out to your organization's administrator or our support team.</p>
                
                <p>Happy learning!</p>
                <p style="margin-top: 30px;"><strong>The Steeper Climb Team</strong></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2026 The Steeper Climb. All rights reserved.</p>
            <p>
                <a href="https://thesteeperclimb.com">Visit Our Website</a> | 
                <a href="https://thesteeperclimb.com/help">Help Center</a> | 
                <a href="https://thesteeperclimb.com/contact">Contact Us</a>
            </p>
            <p style="margin-top: 20px; font-size: 12px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>
