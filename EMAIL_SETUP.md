# Email Configuration Setup Guide

## Overview

The Steeper Climb uses SMTP to send emails to organizations when their accounts are created. This allows organizations to securely set their passwords via email link.

## SMTP Configuration

### Current Settings
- **Host:** smtp.hostinger.com
- **Port:** 465 (SSL) or 587 (TLS)
- **Username:** thesteeperclimb@bedfordwebservices.com
- **Password:** X#ZK~3QbuW|W]3=
- **From Email:** thesteeperclimb@bedfordwebservices.com
- **From Name:** The Steeper Climb

### Location
Email configuration is handled in:
- `src/Utils/Mailer.php` - Main mailer class with SMTP support
- `src/Utils/config.php` - Can be updated with custom SMTP settings if needed

## Features

### Organization Welcome Email
When an organization account is created:

1. **Automatic Email Sending**: Welcome email is automatically sent to the organization email address
2. **Password Setup Link**: Email includes a unique link for the organization to set their password
3. **Beautiful Template**: Modern, responsive HTML email template
4. **Branding**: Customized with The Steeper Climb branding

### Email Contents
- Welcome message
- Organization details confirmation
- Password setup link with CTA button
- List of platform features
- Footer with contact information

## Required PHP Extensions

The Mailer class requires:
- `fopen` wrappers enabled (for mail function fallback)
- OpenSSL extension (for SMTP SSL/TLS connections)

To verify, create a test file with:
```php
<?php
phpinfo();
?>
```

And check for OpenSSL and "Allow URL fopen" under Core settings.

## Installation with PHPMailer

For enhanced SMTP support with better reliability, install PHPMailer:

```bash
composer require phpmailer/phpmailer
```

### Composer Installation (If Not Installed)

1. Download composer from https://getcomposer.org/
2. In the project root directory, run:
```bash
composer install
```

The Mailer class will automatically detect and use PHPMailer if available.

## Testing Email Sending

### Test Script
Create a file `test-email.php` in the root directory:

```php
<?php
require_once 'src/Utils/Mailer.php';

// Test sending an email
$result = \Utils\Mailer::send(
    'test@example.com',
    'Test Email from The Steeper Climb',
    '<h1>Hello!</h1><p>This is a test email.</p>'
);

if ($result) {
    echo "✓ Email sent successfully!";
} else {
    echo "✗ Email failed to send. Check error logs.";
}
?>
```

Run from command line:
```bash
php test-email.php
```

## Troubleshooting

### "Connection refused" Error
- **Cause**: Firewall blocking port 465 or 587
- **Solution**: Contact your hosting provider to enable outbound SMTP connections

### "Authentication failed" Error
- **Cause**: Incorrect username or password
- **Solution**: Verify credentials in `src/Utils/Mailer.php`

### Emails Not Sending
1. Check PHP error logs in `xampp/apache/logs/`
2. Verify OpenSSL extension is enabled
3. Test with `test-email.php` script
4. Check that the organization email address is valid

### Emails Going to Spam
- This is normal for new email addresses
- Organizations should add the sender to their contacts
- Email template follows best practices to minimize spam scoring
- Consider setting up SPF, DKIM, and DMARC records for the domain

## Customization

### Change Sender Information
Edit `src/Utils/Mailer.php`:

```php
$this->fromEmail = 'your-email@example.com';
$this->fromName = 'Your Organization Name';
```

### Change SMTP Settings
Edit the `send()` method in `src/Utils/Mailer.php`:

```php
$mail->Host = 'your.smtp.server.com';
$mail->Port = 587;
$mail->Username = 'your-username';
$mail->Password = 'your-password';
```

### Modify Email Template
Edit the `getOrganizationWelcomeTemplate()` method in `src/Utils/Mailer.php` to customize the HTML template.

## Email Schedule

Emails are sent:
- **Organization Creation**: Immediately when admin creates organization account
- **Organization Password Reset**: When organization account needs password reset (future feature)

## GDPR & Privacy

- Email addresses are stored securely in the database
- Passwords are hashed and never sent via email
- Password setup links expire after use
- Organization has control over their communication preferences

## Security Notes

1. **Credentials**: SMTP credentials are stored in the Mailer class. Consider moving to environment variables for production:

```php
$mail->Username = getenv('SMTP_USERNAME');
$mail->Password = getenv('SMTP_PASSWORD');
```

2. **Email Verification**: Organization email is verified by their ability to access it and set a password

3. **Link Security**: Password setup links include organization ID and email, making them unique and hard to guess

## Support

For email sending issues:
1. Check PHP error logs
2. Verify SMTP server is accessible
3. Test with a simple test-email.php script
4. Contact Hostinger support if SMTP port is blocked

## Future Enhancements

Potential features to add:
- Email templates for password resets
- Student enrollment notifications
- Course completion certificates via email
- Student progress notifications
- Admin digest emails
- Customizable email branding per organization
