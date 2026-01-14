# PHPMailer Quick Start

## Installation (Quick Version)

### Windows (XAMPP):

1. **Open PowerShell** in your project folder (`C:\xampp\htdocs\thesteeperclimb`)

2. **Run**:
   ```bash
   composer install
   ```

3. **Done!** PHPMailer is installed

### Alternative (Automated):

Double-click: `install-phpmailer.bat`

## Verification

After installation, check for:
- ✓ `vendor/` folder exists
- ✓ `vendor/phpmailer/phpmailer/` folder exists  
- ✓ `vendor/autoload.php` exists

## Test It

1. Go to Admin Dashboard
2. Create a test organization
3. Watch for success message: "Welcome email sent to..."
4. Check the organization's email inbox for welcome email

## SMTP Settings

**Already configured for Hostinger:**

```
Host: smtp.hostinger.com
Port: 465 (SSL)
Username: thesteeperclimb@bedfordwebservices.com
Password: X#ZK~3QbuW|W]3=
```

Located in: `src/Utils/Mailer.php` (lines 13-17)

## What This Does

When you create an organization:
1. ✓ Organization saved to database
2. ✓ Welcome email sent via Hostinger SMTP
3. ✓ Email includes password setup link
4. ✓ Organization can set password and login

## Troubleshooting

### "vendor/autoload.php not found"
→ Run `composer install`

### "SMTP connect failed"
→ Check internet connection, firewall settings

### "Authentication failed"
→ Verify SMTP credentials are correct

### Want to send more emails?

Add methods to `src/Utils/Mailer.php`:

```php
public static function sendCertificate($email, $name, $courseName)
{
    $subject = "Certificate: " . $courseName;
    $html = "<h2>Congratulations, " . $name . "!</h2>";
    return self::send($email, $subject, $html);
}
```

## Full Documentation

- **Setup Guide**: `PHPMAILER_SETUP.md`
- **Troubleshooting**: `EMAIL_TROUBLESHOOTING.md`
- **Mailer Code**: `src/Utils/Mailer.php`

## Key Files

```
thesteeperclimb/
├── composer.json              ← Defines PHPMailer dependency
├── composer.lock              ← Locks versions
├── vendor/                    ← PHPMailer installed here
├── install-phpmailer.bat      ← Easy installation script
├── PHPMAILER_SETUP.md         ← Detailed setup guide
├── src/Utils/Mailer.php       ← Email sending class
└── public/admin/organizations.php  ← Where emails are sent
```

## That's It!

PHPMailer is now handling all email sending for The Steeper Climb platform.
