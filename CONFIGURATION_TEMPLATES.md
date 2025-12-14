# Configuration Templates for InfinityFree Deployment

This file contains templates for updating your configuration files before deployment.

## 1. Update: `config/email.php`

Replace the contents of `config/email.php` with this template, filling in your details:

```php
<?php
// Email configuration for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');        // YOUR EMAIL HERE
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');         // 16-CHAR APP PASSWORD
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'your-email@gmail.com');           // YOUR EMAIL HERE
define('FROM_NAME', 'Petty Cash System');

// Update this with your infinityfree domain
define('BASE_URL', 'https://yourdomain.infinityfree.com');

// Note: For Gmail, generate app password at:
// https://myaccount.google.com/apppasswords
?>
```

### How to get Gmail App Password:

1. Go to: https://myaccount.google.com/apppasswords
2. You may need to enable 2-Factor Authentication first
3. Select "Mail" in the dropdown
4. Select "Windows Computer" 
5. Google generates a 16-character password
6. Copy and paste it into SMTP_PASSWORD
7. Format: `xxxx xxxx xxxx xxxx` (4 spaces)

---

## 2. Update: `classes/database.php`

Find the database connection section and replace with your InfinityFree credentials:

```php
<?php
class Database {
    private $host = 'sql12345.infinityfree.com';    // GET FROM INFINITYFREE
    private $db_name = 'if0_xxxxx_yourdbname';       // GET FROM INFINITYFREE
    private $username = 'if0_xxxxx_yourdbuser';      // GET FROM INFINITYFREE
    private $password = 'YourDatabasePassword123';   // GET FROM INFINITYFREE
    private $conn;

    public function connect() {
        // Rest of the code remains the same
    }
    // ... rest of class
?>
```

### How to get InfinityFree Database Credentials:

1. Log into your InfinityFree account
2. Go to Control Panel
3. Look for "MySQL Databases" or "Databases" section
4. Create a new database
5. You'll see:
   - **Host:** Copy this to `$host`
   - **Database Name:** Copy this to `$db_name`
   - **Username:** Copy this to `$username`
   - **Password:** Copy this to `$password`

---

## 3. Information to Record

Before deploying, fill in this table with your information:

```
INFINITYFREE ACCOUNT INFO
=========================
Email: _________________________________
Password: ____________________________
Control Panel URL: ____________________

DOMAIN INFO
=========================
Domain Name: https://yourdomain.infinityfree.com
(without https://) yourdomain.infinityfree.com

DATABASE INFO
=========================
Database Host: _________________________
Database Name: _________________________
Database User: _________________________
Database Pass: _________________________

EMAIL CONFIGURATION
=========================
Gmail Email: _________________________
Gmail App Password: _________________________
(Format: xxxx xxxx xxxx xxxx)

ADMIN ACCOUNT
=========================
Admin Username: _________________________
Admin Password: _________________________
Admin Email: _________________________
```

---

## 4. FTP Credentials (InfinityFree)

You'll need FTP to upload files. Get these from InfinityFree:

```
FTP HOST: ftp.yourdomain.infinityfree.com
FTP USERNAME: (from control panel)
FTP PASSWORD: (from control panel)
FTP PORT: 21 (standard)

Upload directory: /public_html/
```

### Recommended FTP Clients:
- FileZilla (Free, cross-platform)
- WinSCP (Free, Windows)
- Cyberduck (Free, Mac)

---

## 5. Verification Checklist

Use this to verify all settings before uploading:

```
CONFIGURATION FILES
===================
[ ] config/email.php
    [ ] SMTP_HOST = smtp.gmail.com
    [ ] SMTP_PORT = 587
    [ ] SMTP_USERNAME = filled with your email
    [ ] SMTP_PASSWORD = filled with 16-char app password
    [ ] FROM_EMAIL = filled with your email
    [ ] BASE_URL = your infinityfree domain with https://

[ ] classes/database.php
    [ ] $host = InfinityFree database host
    [ ] $db_name = InfinityFree database name
    [ ] $username = InfinityFree database user
    [ ] $password = InfinityFree database password

INFINITYFREE SETUP
==================
[ ] Created MySQL database
[ ] Have database credentials
[ ] Have FTP credentials
[ ] FTP client installed

SECURITY FILES
==============
[ ] .htaccess created in root
[ ] .htaccess created in /config/
[ ] .htaccess created in /classes/
[ ] .htaccess created in /uploads/

DOCUMENTATION
==============
[ ] Read DEPLOYMENT_GUIDE.md
[ ] Read PRE_DEPLOYMENT_CHECKLIST.md
[ ] Filled in information above
```

---

## 6. Step-by-Step Configuration Update

### For config/email.php:

1. Open `config/email.php` in VS Code
2. Locate lines with email configuration
3. Replace with template above
4. Fill in:
   - Your Gmail address
   - Your Gmail app password (from step above)
5. Update BASE_URL with your infinityfree domain
6. Save file

### For classes/database.php:

1. Open `classes/database.php` in VS Code
2. Find the Database class constructor
3. Locate the private variables:
   - `$host`
   - `$db_name`
   - `$username`
   - `$password`
4. Replace with InfinityFree credentials
5. Save file

---

## 7. Important Notes

⚠️ **CRITICAL:**
- Never commit credentials to Git/GitHub
- Never share these files with anyone
- Keep passwords in secure location
- Delete setup.php after initialization
- Enable HTTPS in InfinityFree control panel

✓ **REMEMBER:**
- Gmail app password ≠ Gmail password
- Both use 16-character app password
- InfinityFree credentials from control panel
- Database must be created before uploading
- FTP upload to public_html/ folder

---

## 8. Testing Credentials Locally (Optional)

Before uploading, test credentials locally:

1. Update config files with InfinityFree credentials
2. Run setup.php locally to test database connection
3. Verify no connection errors
4. Then proceed with uploading to InfinityFree

---

## 9. Post-Deployment Configuration

After deploying to InfinityFree:

### Enable HTTPS:
1. Go to InfinityFree control panel
2. Look for "SSL Certificate" or "HTTPS"
3. Enable free SSL (usually automatic)
4. Update BASE_URL if needed

### Test Email:
1. Log in as admin
2. Submit a test request
3. Check if you receive email
4. If not, verify Gmail app password

---

**Keep this file for reference during deployment!**

Last Updated: December 14, 2025
