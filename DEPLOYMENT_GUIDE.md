# Petty Cash System - Deployment Guide for InfinityFree

## Overview
This guide will help you deploy the Petty Cash System to infinityfree.com, a free PHP hosting platform.

## Pre-Deployment Checklist

- [ ] Backup your entire project
- [ ] Test locally one final time
- [ ] Create infinityfree.com account
- [ ] Note your FTP credentials
- [ ] Update configuration files for production

## Step 1: Prepare Configuration Files

### 1.1 Update `config/email.php`

Before deploying, update the email configuration:

```php
<?php
// Email configuration for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'your-email@gmail.com');
define('FROM_NAME', 'Petty Cash System');

// Update this to your infinityfree domain
define('BASE_URL', 'https://yourdomain.infinityfree.com');
?>
```

**Important:** For Gmail, generate an app password:
1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer"
3. Copy the generated 16-character password
4. Use it in `SMTP_PASSWORD`

### 1.2 Create `.htaccess` (if needed for URL rewriting)

Create a file named `.htaccess` in your root directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Prevent direct access to sensitive files
    <FilesMatch "\.(env|json|md|sql)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

## Step 2: Prepare Database

### 2.1 Create Database on InfinityFree

1. Log in to your InfinityFree account
2. Go to "Databases" (usually in the control panel)
3. Create a new database (note the name, username, and password)
4. The host will be something like `sql12345.infinityfree.com`

### 2.2 Update Database Connection

Update `classes/database.php` with your InfinityFree database credentials:

```php
private $host = 'sql12345.infinityfree.com';
private $db_name = 'your_database_name';
private $username = 'your_db_user';
private $password = 'your_db_password';
```

## Step 3: Upload Files to InfinityFree

### Method 1: Using FTP (Recommended)

1. Download an FTP client (FileZilla, WinSCP, or Cyberduck)
2. Use your InfinityFree FTP credentials:
   - **Host:** ftp.yourdomain.infinityfree.com
   - **Username:** From InfinityFree control panel
   - **Password:** From InfinityFree control panel
3. Upload all files to the `public_html` directory:
   - Keep all files in the root of `public_html`
   - Ensure the folder structure is preserved

### Method 2: Using File Manager

1. Go to InfinityFree control panel
2. Click "File Manager"
3. Upload files directly (may be slower for large projects)

### Important Files to Upload

```
public_html/
├── index.php (entry point)
├── setup.php (run once after upload)
├── auth/
├── admin/
├── employee/
├── classes/
├── config/
├── css/
├── uploads/
├── vendor/
├── composer.json
└── [other files]
```

## Step 4: Initialize Database

### 4.1 Run Setup Script

After uploading, visit: `https://yourdomain.infinityfree.com/setup.php`

This will:
- Create database tables
- Set up the database schema

### 4.2 Delete Setup Script (Security)

After running setup, **delete** `setup.php` from your server for security:
- Via FTP: Delete the file
- Via File Manager: Delete the file

## Step 5: Create Admin Account

1. Visit `https://yourdomain.infinityfree.com/auth/login.php`
2. Click "Register"
3. Create an admin account with:
   - Role: Admin
   - Department: Administration
4. Verify your email through the verification link

## Step 6: Verify Everything Works

### Test These Features

- [ ] Login page loads
- [ ] Registration works
- [ ] Email verification is sent
- [ ] Admin dashboard accessible
- [ ] Employee dashboard accessible
- [ ] File uploads work
- [ ] Reports generate correctly

## Step 7: Security Hardening

### Create `.htaccess` for Protected Directories

Create `.htaccess` in sensitive directories:

#### `/classes/.htaccess`
```apache
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### `/config/.htaccess`
```apache
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### `/uploads/.htaccess`
```apache
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Create `config/.htaccess` to prevent direct access

```apache
Order allow,deny
Deny from all
```

## Step 8: Post-Deployment Tasks

### 8.1 Enable HTTPS

InfinityFree provides free SSL. Enable it in your control panel:
1. SSL certificate (free)
2. Set auto-redirect to HTTPS

### 8.2 Update Email Configuration

Test email sending:
1. Go to Employee Dashboard
2. Submit a request with a receipt
3. Check if notification emails work

### 8.3 Monitor Logs

Check InfinityFree error logs if issues occur:
- Control Panel → Error Logs

## Troubleshooting

### Common Issues and Solutions

#### Issue: Database Connection Failed
**Solution:**
- Verify database credentials in `classes/database.php`
- Check that database is created in InfinityFree control panel
- Ensure your IP is not blocked by InfinityFree

#### Issue: Emails Not Sending
**Solution:**
- Verify Gmail app password is correct
- Enable "Less secure app access" if needed
- Check email configuration in `config/email.php`
- Check InfinityFree error logs

#### Issue: File Uploads Not Working
**Solution:**
- Ensure `uploads/receipts/` directory exists and is writable
- Create directory if missing via FTP
- Set permissions to 755 via FTP

#### Issue: Session Issues / Login Not Working
**Solution:**
- Clear browser cookies
- Check `php.ini` session settings in control panel
- Verify session save path is writable

#### Issue: Composer Autoload Not Working
**Solution:**
- The `vendor/` directory must be uploaded
- All Composer dependencies are already installed
- No need to run `composer install` on the server

## Additional Resources

- **InfinityFree Help:** https://forum.infinityfree.com/
- **PHP Documentation:** https://www.php.net/
- **PHPMailer Docs:** https://github.com/PHPMailer/PHPMailer

## Quick Checklist for Deployment

1. ✓ Update `config/email.php` with your settings
2. ✓ Update `classes/database.php` with InfinityFree credentials
3. ✓ Upload all files via FTP to `public_html/`
4. ✓ Run `setup.php` to create tables
5. ✓ Delete `setup.php` after setup
6. ✓ Create admin account
7. ✓ Test all features
8. ✓ Create `.htaccess` files for security
9. ✓ Enable HTTPS in control panel
10. ✓ Delete this guide from production (optional)

## Support

If you encounter any issues:
1. Check the troubleshooting section
2. Review InfinityFree error logs
3. Test locally first to isolate issues
4. Check database connectivity
5. Verify file permissions

---

**Deployment Date:** 
**Domain:** 
**Admin Email:** 

Keep this document for reference during deployment!
