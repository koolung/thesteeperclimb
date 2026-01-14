# SMTP Authentication Fix - Hostinger Email Configuration

## Issue Found
The email address `thesteeperclimb@bedfordwebservices.com` is returning **Authentication Failed** on the Hostinger SMTP server (smtp.hostinger.com:465).

This means either:
1. The email address doesn't exist in your Hostinger account
2. The password is incorrect
3. SMTP access is not enabled for this email address

## How to Fix

### Step 1: Verify Email Account in Hostinger
1. Log into your Hostinger control panel (hpanel.hostinger.com)
2. Go to **Emails** section
3. Check if `thesteeperclimb@bedfordwebservices.com` exists
4. If it doesn't exist, you need to create it first

### Step 2: Reset the Email Password
1. In the Emails section, find `thesteeperclimb@bedfordwebservices.com`
2. Click on it to open settings
3. Look for "Change Password" or "Reset Password"
4. Set a new password (write it down!)
5. Save the changes

### Step 3: Enable SMTP/POP3/IMAP
1. In the email account settings, look for protocol options
2. Make sure SMTP is **enabled**
3. Some hosting providers have security options - check if SMTP is allowed
4. Save the changes

### Step 4: Update the Mailer Configuration
Once you have the correct credentials, update the file:
**C:\xampp\htdocs\thesteeperclimb\src\Utils\Mailer.php**

Around line 17-20, update these lines with your correct credentials:
```php
private static $host = 'smtp.hostinger.com';
private static $port = 465;
private static $username = 'YOUR_EMAIL@bedfordwebservices.com';
private static $password = 'YOUR_NEW_PASSWORD';
private static $fromEmail = 'YOUR_EMAIL@bedfordwebservices.com';
```

### Step 5: Test the New Credentials
Run this command to test:
```bash
cd C:\xampp\htdocs\thesteeperclimb
php setup/test-both-ports.php
```

If you see `✓ Port 465 WORKS!` or `✓ Port 587 WORKS!`, the SMTP is now configured correctly.

## Alternative: Use Gmail SMTP (Temporary Testing)

If you don't have Hostinger email set up yet, you can use Gmail for testing:

### Using Gmail SMTP:
1. **Gmail Account**: Your Gmail address
2. **Password**: You need an **App Password**, not your regular Gmail password
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows"
   - Google will generate a 16-character password
3. **SMTP Settings**:
   ```
   Host: smtp.gmail.com
   Port: 587
   Security: STARTTLS (not SMTPS)
   Username: your.email@gmail.com
   Password: [16-character app password from Google]
   ```

Update Mailer.php with Gmail credentials to test, then switch back to Hostinger once that's configured.

## Important Notes

- **Password Special Characters**: If your password contains special characters like `|`, `@`, `!`, etc., make sure they are entered exactly as provided
- **Port 465 vs 587**: 
  - Port 465: SMTPS (Implicit SSL) - usually more secure
  - Port 587: STARTTLS (Explicit TLS) - also secure, sometimes more compatible
  - If port 465 fails, try port 587 with ENCRYPTION_STARTTLS

## Debug Information Available

To run detailed diagnostics again, use:
```bash
php setup/diagnostic.php
```

This will show you the exact error message from the SMTP server, helping you identify the issue.

## If Still Not Working

1. Contact Hostinger support:
   - Email account not accepting SMTP connections?
   - Is SMTP access blocked by firewall?
   - Is there an IP whitelist?

2. Check PHP error log:
   ```
   C:\xampp\apache\logs\error.log
   ```

3. Review SMTP debug output:
   - Run `php setup/diagnostic.php` to see all SMTP handshake details
   - Share the output with Hostinger support if needed

## Once Fixed

After updating credentials, test by creating an organization in the admin dashboard:
- Go to: http://localhost/thesteeperclimb/public/admin/organizations.php?action=create
- Fill in organization details and submit
- Check if the welcome email is sent successfully
