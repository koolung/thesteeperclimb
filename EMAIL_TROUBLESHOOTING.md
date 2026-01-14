# Email Sending Troubleshooting Guide

## Quick Diagnosis

Visit: `http://localhost/thesteeperclimb/mail-test.php`

This will show you:
- Whether PHP's mail() function is available
- SMTP configuration on your server
- Whether a test email can be sent

## Common Issues & Solutions

### Issue 1: Mail Function Not Available
**Error**: `✗ mail() function is NOT available`

**Solutions**:
1. **XAMPP Users**: Uncomment sendmail in php.ini
   - Open: `xampp/php/php.ini`
   - Find: `;sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t -i"`
   - Remove the semicolon: `sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t -i"`
   - Restart Apache

2. **Check php.ini location**:
   ```
   Create a test.php file with: <?php phpinfo(); ?>
   Look for "Loaded Configuration File"
   ```

### Issue 2: Email Not Being Sent

**Check these in order**:

1. **Verify mail() works**:
   - Run `mail-test.php` - it should show `✓ Mail function executed successfully`

2. **Check error logs**:
   - XAMPP: `xampp/apache/logs/error.log`
   - Look for mail-related errors

3. **Check sendmail configuration**:
   - Open: `xampp/sendmail/sendmail.ini`
   - Ensure it has proper SMTP settings
   - Default for testing: Use `localhost` or your ISP's mail server

4. **Check firewall**:
   - Windows Defender or antivirus may block mail
   - Allow outbound connections on port 25, 587, or 465

### Issue 3: XAMPP Mail Setup

**Complete XAMPP Email Setup**:

1. **Enable sendmail**:
   - Edit: `xampp/php/php.ini`
   - Find and uncomment/modify:
     ```ini
     sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t -i"
     ```

2. **Configure sendmail**:
   - Edit: `xampp/sendmail/sendmail.ini`
   - Replace the SMTP settings:
     ```ini
     smtp=smtp.gmail.com
     smtp_port=587
     ; For Gmail, use:
     auth_username=your-email@gmail.com
     auth_password=your-app-password
     ; Or use your hosting provider's SMTP
     ```

3. **Use Mailtrap (Recommended for Testing)**:
   - Go to: https://mailtrap.io (free)
   - Create account and get SMTP credentials
   - Update `xampp/sendmail/sendmail.ini`:
     ```ini
     smtp=smtp.mailtrap.io
     smtp_port=2525
     auth_username=your-mailtrap-username
     auth_password=your-mailtrap-password
     ```

4. **Restart Apache**:
   - Stop and start Apache from XAMPP Control Panel

### Issue 4: Check Logs

**View PHP Error Log**:

```php
<?php
$log_file = ini_get('error_log');
if (file_exists($log_file)) {
    echo "Last 50 lines of error log:\n\n";
    $lines = array_slice(file($log_file), -50);
    echo implode('', $lines);
} else {
    echo "Error log file not found: " . $log_file;
}
?>
```

Save this as `check-log.php` and visit it.

## Email Testing Workflow

1. **Run mail-test.php** to check server configuration
2. **Create test organization** from admin panel
3. **Check XAMPP mail logs**: `xampp/sendmail/sendmail.log`
4. **Check PHP error log**: From `check-log.php`
5. **Verify Mailtrap/email service** shows the sent email

## Alternative: Use Mailtrap for Development

Mailtrap is perfect for development/testing:

1. **Sign up**: https://mailtrap.io (free)
2. **Get SMTP credentials** from their dashboard
3. **Configure sendmail.ini** with those credentials
4. **All emails go to Mailtrap** instead of real inboxes
5. **View them in web interface** without actually sending

### Mailtrap Setup (XAMPP):

File: `xampp/sendmail/sendmail.ini`

```ini
[sendmail]
smtp=smtp.mailtrap.io
smtp_port=2525
default_domain=localhost
auth_username=YOUR_MAILTRAP_USER
auth_password=YOUR_MAILTRAP_PASS
sendmail_path="\"C:\xampp\sendmail\sendmail.exe\" -t -i"
```

## Verify Email Was Sent

Once configured:

1. **Create test organization** from admin panel
2. **Should see message**: "Organization created successfully. Welcome email sent to..."
3. **Check email inbox** (or Mailtrap dashboard)
4. **Look for email from**: thesteeperclimb@bedfordwebservices.com
5. **Subject**: "Welcome to The Steeper Climb - Set Up Your Account"

## Email Flow

```
Admin creates organization
    ↓
Organization data saved to database
    ↓
Mailer::sendOrganizationWelcome() called
    ↓
HTML email template generated
    ↓
mail() function sends via SMTP/Sendmail
    ↓
Email appears in recipient's inbox (or spam folder)
    ↓
Organization clicks "Set Password" link
    ↓
Organization sets password via password-reset.php
    ↓
Organization can now login
```

## Production Notes

For production:

1. **Use a real email service**:
   - SendGrid
   - Mailgun
   - Amazon SES
   - Your hosting provider's SMTP

2. **Set proper headers** (already done in Mailer.php):
   - From address
   - Reply-To
   - MIME type
   - Content-Type

3. **Setup SPF/DKIM records** with your domain registrar

4. **Monitor delivery** with email service dashboard

5. **Store credentials securely**:
   ```php
   // Instead of hardcoding:
   $username = getenv('SMTP_USERNAME');
   $password = getenv('SMTP_PASSWORD');
   ```

## File Locations

- Mailer class: `src/Utils/Mailer.php`
- Email template: Inside `Mailer.php` method `getOrganizationWelcomeTemplate()`
- Password reset page: `public/setup/password-reset.php`
- XAMPP sendmail: `xampp/sendmail/sendmail.exe`
- XAMPP sendmail config: `xampp/sendmail/sendmail.ini`
- XAMPP sendmail log: `xampp/sendmail/sendmail.log`
- PHP error log: Check with `mail-test.php`

## Need Help?

1. Run `mail-test.php` - provides detailed diagnostics
2. Check error logs
3. Test with Mailtrap (safer for development)
4. Verify server can connect outbound (firewall/antivirus)
