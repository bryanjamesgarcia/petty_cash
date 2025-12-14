# Pre-Deployment Checklist for InfinityFree

Complete this checklist before deploying your Petty Cash System to production.

## 1. Configuration Updates

- [ ] Update `config/email.php`:
  - [ ] Set correct SMTP_HOST
  - [ ] Set correct SMTP_PORT
  - [ ] Set correct SMTP_USERNAME (your email)
  - [ ] Generate and set SMTP_PASSWORD (Gmail app password)
  - [ ] Update FROM_EMAIL
  - [ ] Update BASE_URL to your infinityfree domain
  - [ ] Update FROM_NAME if needed

- [ ] Update `classes/database.php`:
  - [ ] Set correct database host (from InfinityFree)
  - [ ] Set correct database name
  - [ ] Set correct database username
  - [ ] Set correct database password

## 2. Database Preparation

- [ ] Create database in InfinityFree control panel
- [ ] Note database credentials
- [ ] Test database connection locally if possible
- [ ] Have backup of `petty_cash_db.sql` available

## 3. Files to Upload

- [ ] All application files
- [ ] `.htaccess` files (for security)
- [ ] `config/` directory
- [ ] `classes/` directory
- [ ] `auth/` directory
- [ ] `admin/` directory
- [ ] `employee/` directory
- [ ] `css/` directory
- [ ] `uploads/` directory (create if missing)
- [ ] `vendor/` directory (Composer dependencies)
- [ ] `index.php` (entry point)
- [ ] `composer.json`

## 4. Files NOT to Upload (Security)

- [ ] `.git/` or `.gitignore`
- [ ] `node_modules/` (if any)
- [ ] `DEPLOYMENT_GUIDE.md` (optional - for reference only)
- [ ] `TODO.md` and `TODO_notifications.md`
- [ ] Any `.env` files with sensitive data
- [ ] `setup.php` (remove after first run)

## 5. Deployment Steps

- [ ] Create FTP account credentials
- [ ] Download FTP client (FileZilla, WinSCP, etc.)
- [ ] Connect to FTP server
- [ ] Upload all files to `public_html/`
- [ ] Verify all files uploaded correctly
- [ ] Run `setup.php` to create database tables
- [ ] **DELETE `setup.php` after running**
- [ ] Test login at `/auth/login.php`

## 6. Post-Deployment

- [ ] Create admin account
- [ ] Verify email verification works
- [ ] Test employee registration
- [ ] Test file uploads
- [ ] Test report generation
- [ ] Test email notifications
- [ ] Create `.htaccess` files in sensitive directories
- [ ] Enable HTTPS in InfinityFree control panel
- [ ] Test HTTPS redirect works

## 7. Security Hardening

- [ ] Delete `setup.php`
- [ ] Verify `.htaccess` files are in place
- [ ] Test that `/config/` directory is not directly accessible
- [ ] Test that `/classes/` directory is not directly accessible
- [ ] Verify file permissions are correct (755 for directories, 644 for files)

## 8. Final Testing

- [ ] User registration works
- [ ] Email verification works
- [ ] Admin login works
- [ ] Employee login works
- [ ] Admin can view dashboard
- [ ] Employee can submit requests
- [ ] File uploads work
- [ ] Reports can be generated
- [ ] Liquidation feature works
- [ ] Email notifications are sent correctly

## 9. Backups

- [ ] Create backup of local database before deployment
- [ ] Create backup of all source files
- [ ] Export InfinityFree database after setup
- [ ] Document all credentials in a secure location

## 10. Documentation

- [ ] Document domain name
- [ ] Document admin username
- [ ] Document database name
- [ ] Document FTP credentials (in secure location)
- [ ] Document any modifications made

## Email Verification Setup

**Important:** Before deploying, ensure Gmail app password is set up:

1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer"
3. Google will generate a 16-character password
4. Use this in `config/email.php` for `SMTP_PASSWORD`

## InfinityFree Verification Requirements

InfinityFree may require you to:
- [ ] Verify your email address
- [ ] Verify you are not a bot
- [ ] Add a text record to DNS (if using custom domain)
- [ ] Keep the free ad banner (if on free plan)

## Troubleshooting

If you encounter issues:

1. **Database Connection Error**
   - Verify credentials in `classes/database.php`
   - Check database exists in InfinityFree control panel
   - Check InfinityFree error logs

2. **Emails Not Sending**
   - Verify Gmail app password is correct
   - Check `config/email.php` settings
   - Review error logs in InfinityFree

3. **File Upload Issues**
   - Ensure `uploads/receipts/` directory exists
   - Check directory permissions (755)

4. **Login/Session Issues**
   - Clear browser cookies
   - Check PHP version compatibility
   - Review error logs

## Quick Reference

| Item | Value |
|------|-------|
| Domain | https://yourdomain.infinityfree.com |
| Database Host | sql12345.infinityfree.com |
| Database Name | [Your DB Name] |
| DB Username | [Your DB User] |
| Email Account | [Your Gmail] |
| Admin Username | [Created during setup] |
| Deployment Date | _________________ |

---

**Last Updated:** December 14, 2025
**System:** Petty Cash System
**Hosting:** InfinityFree

**Remember:** Test thoroughly before announcing the system to users!
