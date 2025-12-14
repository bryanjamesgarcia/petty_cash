# 📋 DEPLOYMENT CHECKLIST - Print This Out!

Use this checklist as you deploy your Petty Cash System to infinityfree.com.

---

## PHASE 1: PREPARATION & READING (Do This First)

### Read Documentation
- [ ] Read `START_HERE.md` (Overview - 5 min)
- [ ] Read `READY_FOR_DEPLOYMENT.md` (Quick start - 5 min)
- [ ] Read `CONFIGURATION_TEMPLATES.md` (Config help - 10 min)
- [ ] Read `DEPLOYMENT_GUIDE.md` (Full guide - 20 min)

### Gather Required Information
- [ ] Have infinityfree.com account ready
- [ ] Have Gmail account ready
- [ ] Have FTP credentials from InfinityFree
- [ ] Have access to email account
- [ ] Have text editor (VS Code)
- [ ] Have FTP client (FileZilla/WinSCP)

---

## PHASE 2: LOCAL CONFIGURATION (Update on Your Computer)

### Create Gmail App Password
- [ ] Go to: https://myaccount.google.com/apppasswords
- [ ] Select "Mail" and "Windows Computer"
- [ ] Copy the 16-character password
- [ ] Save it: `xxxx xxxx xxxx xxxx`

### Update config/email.php
- [ ] Open file: `config/email.php`
- [ ] Update: `SMTP_USERNAME` = your Gmail email
- [ ] Update: `SMTP_PASSWORD` = 16-char app password
- [ ] Update: `FROM_EMAIL` = your Gmail email
- [ ] Update: `BASE_URL` = https://yourdomain.infinityfree.com
- [ ] Save file
- [ ] Verify no errors (check syntax)

### Create InfinityFree Database
- [ ] Log into infinityfree.com
- [ ] Go to Control Panel
- [ ] Navigate to "Databases" section
- [ ] Create new MySQL database
- [ ] Note database host: _______________
- [ ] Note database name: _______________
- [ ] Note database user: _______________
- [ ] Note database pass: _______________
- [ ] Copy all credentials

### Update classes/database.php
- [ ] Open file: `classes/database.php`
- [ ] Find: `private $host = ...`
- [ ] Update: with database host from InfinityFree
- [ ] Find: `private $db_name = ...`
- [ ] Update: with database name from InfinityFree
- [ ] Find: `private $username = ...`
- [ ] Update: with database user from InfinityFree
- [ ] Find: `private $password = ...`
- [ ] Update: with database password from InfinityFree
- [ ] Save file
- [ ] Verify no errors (check syntax)

### Verify Local Configuration
- [ ] Can access index.php locally
- [ ] No PHP errors in config files
- [ ] Both config files saved and updated
- [ ] All credentials match InfinityFree

---

## PHASE 3: FTP SETUP (Prepare for Upload)

### Get FTP Credentials from InfinityFree
- [ ] Log into infinityfree.com control panel
- [ ] Go to "FTP Accounts" or similar
- [ ] Note FTP Host: ftp.yourdomain.infinityfree.com
- [ ] Note FTP Username: _______________
- [ ] Note FTP Password: _______________
- [ ] Note FTP Port: 21 (standard)

### Install FTP Client
- [ ] Download FileZilla from: https://filezilla-project.org
  OR
- [ ] Download WinSCP from: https://winscp.net
- [ ] Install FTP client
- [ ] Launch FTP client

### Test FTP Connection
- [ ] Open FTP client
- [ ] Enter FTP Host: ftp.yourdomain.infinityfree.com
- [ ] Enter Username: (from above)
- [ ] Enter Password: (from above)
- [ ] Click Connect
- [ ] Verify connection successful
- [ ] Navigate to: `/public_html/`
- [ ] Verify directory is empty or ready for upload

---

## PHASE 4: FILE UPLOAD (Upload to Server)

### Prepare Files for Upload
- [ ] Verify `index.php` is updated (enhanced version)
- [ ] Verify all `.htaccess` files exist:
  - [ ] `/.htaccess` (root)
  - [ ] `/classes/.htaccess`
  - [ ] `/config/.htaccess`
  - [ ] `/uploads/.htaccess`
- [ ] Verify `setup.php` is present
- [ ] Verify `/vendor/` directory exists with all files
- [ ] Verify `/config/email.php` is updated
- [ ] Verify `/classes/database.php` is updated

### Upload Root Level Files
Via FTP, upload to `/public_html/`:
- [ ] index.php
- [ ] setup.php
- [ ] composer.json
- [ ] .htaccess
- [ ] All printable_*.php files

### Upload Directories
Via FTP, upload entire directories to `/public_html/`:
- [ ] /auth/
- [ ] /admin/
- [ ] /employee/
- [ ] /classes/
- [ ] /config/
- [ ] /css/
- [ ] /uploads/
- [ ] /vendor/

### Verify Upload
- [ ] All files appear in FTP
- [ ] Directory structure matches local
- [ ] .htaccess files are present (may be hidden)
- [ ] /uploads/ directory exists
- [ ] /vendor/ directory exists with many files

---

## PHASE 5: INITIALIZATION (First Run)

### Run Setup Script
- [ ] Open browser
- [ ] Visit: `https://yourdomain.infinityfree.com/setup.php`
- [ ] Wait for page to load
- [ ] Look for: "Database setup complete" message
- [ ] Note: This creates database tables

### Delete Setup.php (CRITICAL FOR SECURITY)
- [ ] Via FTP, navigate to `/public_html/`
- [ ] Find: `setup.php`
- [ ] **DELETE** setup.php
- [ ] Confirm deletion
- [ ] Verify file is gone from FTP

### Create Uploads Directory (if needed)
- [ ] Via FTP, go to `/public_html/uploads/`
- [ ] Verify `receipts/` folder exists
- [ ] If not, create `receipts/` folder
- [ ] Right-click and set permissions to 755

---

## PHASE 6: ADMIN ACCOUNT CREATION

### Access Login Page
- [ ] Open browser
- [ ] Visit: `https://yourdomain.infinityfree.com/auth/login.php`
- [ ] Verify page loads

### Register Admin Account
- [ ] Click: "Don't have an account? Register here"
- [ ] Fill Username: _________________
- [ ] Fill Password: _________________
- [ ] Fill Name: _________________
- [ ] Fill Department: _________________
- [ ] Fill Email: _________________
- [ ] Select Role: **Admin**
- [ ] Click: Register
- [ ] Note: You'll receive verification email

### Verify Email
- [ ] Check your email inbox
- [ ] Look for: Petty Cash System email
- [ ] Click: Verification link in email
- [ ] Note: May take 1-5 minutes to arrive

### Login as Admin
- [ ] Go to: `https://yourdomain.infinityfree.com/auth/login.php`
- [ ] Enter admin username: _________________
- [ ] Enter admin password: _________________
- [ ] Click: Login
- [ ] Verify: Admin dashboard loads
- [ ] See: Pending requests count

---

## PHASE 7: TESTING (Verify Everything Works)

### Test Authentication
- [ ] Can login as admin ✓
- [ ] Can logout
- [ ] Login page shows "Register here" link ✓
- [ ] Email verification works ✓

### Test Admin Features
- [ ] Admin dashboard loads ✓
- [ ] Can see "Pending Requests" ✓
- [ ] Can see pending count widget ✓
- [ ] Can generate reports ✓

### Test Employee Registration
- [ ] Go to login page
- [ ] Register as employee
- [ ] Receive verification email ✓
- [ ] Verify email successfully
- [ ] Login as employee ✓
- [ ] Employee dashboard loads ✓

### Test Employee Features
- [ ] Can click "Add Request" ✓
- [ ] Can fill request form ✓
- [ ] Can upload receipt image ✓
- [ ] Can submit request ✓
- [ ] Request appears in admin pending ✓

### Test Admin Features (Part 2)
- [ ] Can approve request ✓
- [ ] Can reject request ✓
- [ ] Employee gets notification ✓

### Test Liquidation
- [ ] As employee, see approved request ✓
- [ ] Can click "Liquidate" ✓
- [ ] Request status changes ✓
- [ ] Admin sees completion ✓

### Test Reports
- [ ] As admin, go to reports
- [ ] Can generate report ✓
- [ ] Report shows correct data ✓
- [ ] Can print/export if available ✓

### Test File Uploads
- [ ] Upload test image as receipt ✓
- [ ] File appears in /uploads/receipts/ ✓
- [ ] Can download receipt ✓

### Test Email Notifications
- [ ] Submit request as employee
- [ ] Check email for notification ✓
- [ ] Approve request as admin
- [ ] Employee gets notification ✓

---

## PHASE 8: SECURITY & CONFIGURATION

### Verify Security
- [ ] HTTPS works (green padlock in browser) ✓
- [ ] Can't access `/config/` directly ✓
- [ ] Can't access `/classes/` directly ✓
- [ ] setup.php is deleted ✓
- [ ] Directory listing disabled ✓

### Check Error Logs
- [ ] Log into InfinityFree control panel
- [ ] Go to Error Logs
- [ ] Check for any PHP errors
- [ ] Note any issues found: _______________

### Enable HTTPS (if not automatic)
- [ ] In InfinityFree control panel
- [ ] Look for SSL/HTTPS section
- [ ] Verify certificate is active
- [ ] Test auto-redirect works

### Test Security Headers
- [ ] In browser, press F12 (Developer Tools)
- [ ] Go to Network tab
- [ ] Refresh page
- [ ] Check response headers
- [ ] Look for security headers

---

## PHASE 9: DOCUMENTATION & BACKUPS

### Save Important Information
- [ ] Save this checklist
- [ ] Save credentials in secure location:
  - [ ] Domain name: _______________
  - [ ] Admin username: _______________
  - [ ] Database name: _______________
  - [ ] Database host: _______________
  - [ ] FTP host: _______________

### Create Backups
- [ ] Backup local database dump
- [ ] Backup all source code files
- [ ] Export database from InfinityFree (if available)
- [ ] Save configuration files locally

### Keep Documentation
- [ ] Save DEPLOYMENT_GUIDE.md locally
- [ ] Save QUICK_START_GUIDE.md locally
- [ ] Save PRE_DEPLOYMENT_CHECKLIST.md locally
- [ ] Create README for yourself

---

## PHASE 10: FINAL VERIFICATION

### Checklist Summary
- [ ] All files uploaded ✓
- [ ] Database initialized ✓
- [ ] setup.php deleted ✓
- [ ] Admin account created ✓
- [ ] All features tested ✓
- [ ] Security verified ✓
- [ ] Error logs checked ✓
- [ ] Backups created ✓
- [ ] Documentation saved ✓

### Ready for Production?
- [ ] All above items checked
- [ ] No error messages ✓
- [ ] All users can login ✓
- [ ] All features work ✓
- [ ] Emails send ✓
- [ ] File uploads work ✓

---

## 🎉 DEPLOYMENT COMPLETE!

```
╔════════════════════════════════════════╗
║  PETTY CASH SYSTEM DEPLOYED TO        ║
║        infinityfree.com                ║
║                                        ║
║     Your domain is live and ready!    ║
║     All systems functioning properly  ║
║                                        ║
║      Ready to invite users now!       ║
╚════════════════════════════════════════╝
```

### Next Steps:
1. ✅ Invite employees to register
2. ✅ Monitor system regularly
3. ✅ Keep backups updated
4. ✅ Monitor error logs

### Support References:
- QUICK_START_GUIDE.md - For user instructions
- DEPLOYMENT_GUIDE.md - For troubleshooting
- InfinityFree Forum - https://forum.infinityfree.com/

---

## NOTES & ISSUES ENCOUNTERED

Use this space to note any issues during deployment:

```
Issue 1: 
Resolution:

Issue 2:
Resolution:

Issue 3:
Resolution:
```

---

## DEPLOYMENT INFORMATION

Fill this in for your records:

```
Deployment Date: _______________
Domain: _______________
Admin Username: _______________
Database Name: _______________
Deployment Time Taken: _______________
Deployed By: _______________
Notes: _______________
```

---

**Print this checklist and mark off each item as you complete it!**

Good luck with your deployment! 🚀

**System:** Petty Cash System v1.0  
**Created:** December 14, 2025
