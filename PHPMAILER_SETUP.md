# PHPMailer Installation & Setup

## Quick Setup

### Step 1: Install Composer (One Time)

If you don't have Composer installed:

1. Download from: https://getcomposer.org/download/
2. Run the installer (just click through with defaults)
3. Verify installation - open PowerShell and run:
   ```bash
   composer --version
   ```

### Step 2: Install PHPMailer

Navigate to your project root (`C:\xampp\htdocs\thesteeperclimb`) and run:

```bash
composer install
```

This will:
- Read `composer.json`
- Download PHPMailer into `vendor/` folder
- Create `vendor/autoload.php` for automatic class loading

### Step 3: Verify Installation

Check that these files exist:
- `vendor/` folder
- `vendor/phpmailer/phpmailer/` folder
- `vendor/autoload.php`

If they exist, PHPMailer is installed! ✓

## SMTP Configuration

The Mailer class is configured for Hostinger SMTP:

```php
Host: smtp.hostinger.com
Port: 465 (SSL)
Username: thesteeperclimb@bedfordwebservices.com
Password: X#ZK~3QbuW|W]3=
```

These are set in `src/Utils/Mailer.php` lines 13-17.

## How It Works

1. **Admin creates organization** → Email sent via PHPMailer
2. **PHPMailer connects to smtp.hostinger.com** → Uses your account
3. **Email delivered** → To organization's inbox
4. **Organization receives** → Welcome email with password setup link

## Testing Email Sending

### Method 1: Test via Admin Panel
1. Go to Admin Dashboard
2. Create a test organization
3. Check console output for:
   - ✓ `Email sent successfully to: ...`
   - ✗ `PHPMailer Error: ...`

### Method 2: Check Error Logs
Look at `php_errors.log` in your logs folder to see detailed errors.

## Troubleshooting

### Error: "Class not found: PHPMailer\PHPMailer\PHPMailer"
**Solution**: Run `composer install` again

### Error: "SMTP connect() failed"
**Cause**: Network/firewall issue
**Solutions**:
1. Check internet connection
2. Check firewall isn't blocking port 465
3. Verify SMTP host is correct: smtp.hostinger.com
4. Try with different port (587 with TLS) - edit Mailer.php line 28

### Error: "SMTP authentication failed"
**Cause**: Wrong username or password
**Solution**: Verify credentials in Mailer.php match your Hostinger account

### Error: "Failed to connect to server"
**Solutions**:
1. Test connectivity: `ping smtp.hostinger.com`
2. Check that Hostinger SMTP is enabled on your account
3. Contact Hostinger support

## Changing SMTP Settings

To use different email provider, edit `src/Utils/Mailer.php`:

```php
private static $host = 'smtp.your-provider.com';
private static $port = 587; // or 465
private static $username = 'your-email@example.com';
private static $password = 'your-password';
private static $fromEmail = 'your-email@example.com';
private static $fromName = 'Your Platform Name';
```

Then on line 28, change encryption if needed:
```php
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // For port 587
```

## File Structure

```
thesteeperclimb/
├── composer.json                    # Defines PHPMailer dependency
├── composer.lock                    # Locks versions (auto-generated)
├── vendor/                          # PHPMailer installed here
│   ├── phpmailer/
│   │   └── phpmailer/
│   │       ├── src/
│   │       │   └── PHPMailer.php   # Main class
│   │       └── ...
│   └── autoload.php                # Auto-loads classes
├── src/
│   └── Utils/
│       └── Mailer.php              # Our email wrapper
└── ...
```

## Usage in Your Code

To send an email anywhere in the application:

```php
require_once __DIR__ . '/../src/Utils/Mailer.php';

// Send email
$success = \Utils\Mailer::send(
    'user@example.com',
    'Email Subject',
    '<h1>Hello!</h1><p>Email content here</p>'
);

if ($success) {
    echo "Email sent!";
} else {
    echo "Email failed. Check logs.";
}
```

## What's Included

### Main Method
- `Mailer::send($to, $subject, $htmlBody, $textBody)` - Send any email

### Organization Welcome Email
- `Mailer::sendOrganizationWelcome($email, $name, $contactPerson, $setupLink)` - Send welcome email

### Template
- Professional HTML email template with:
  - Responsive design
  - Gradient header
  - CTA button
  - Feature list
  - Professional footer

## Production Deployment

When deploying to production:

1. **Copy vendor/ folder** to production server
   - Or run `composer install` on production server

2. **Update SMTP credentials** to production values
   - Edit `src/Utils/Mailer.php`
   - Or use environment variables (more secure)

3. **Verify SSL/TLS works** on production server
   - Production servers usually support port 465/587
   - May need firewall adjustments for outbound SMTP

4. **Monitor email delivery**
   - Check Hostinger's email logs
   - Monitor for bounces/failures
   - Watch for emails going to spam

5. **Setup SPF/DKIM** (optional but recommended)
   - Adds security and improves deliverability
   - Contact your domain registrar for setup

## Common Use Cases

### Send Custom Email

```php
\Utils\Mailer::send(
    'student@example.com',
    'Course Completion!',
    '<h2>Congratulations!</h2><p>You completed the course</p>'
);
```

### Add New Email Type

Add a method to `Mailer.php`:

```php
public static function sendStudentCertificate($email, $studentName, $courseName, $certificateLink)
{
    $subject = "Your Certificate for " . $courseName;
    $htmlBody = self::getCertificateTemplate($studentName, $courseName, $certificateLink);
    return self::send($email, $subject, $htmlBody);
}

private static function getCertificateTemplate($name, $course, $link)
{
    // HTML email template here
}
```

## Need Help?

1. Check error logs for detailed messages
2. Verify `vendor/` folder exists (run `composer install` again if missing)
3. Test SMTP credentials on Hostinger account
4. Check firewall/antivirus isn't blocking outbound SMTP
5. Contact Hostinger support if SMTP isn't working
