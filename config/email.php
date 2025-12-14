<?php
// Email configuration for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP host
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'libreliberty222@gmail.com'); // Your personal email
define('SMTP_PASSWORD', 'tqjm bjcn vzhv joes'); // Replace with your actual Gmail app password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('FROM_EMAIL', 'libreliberty222@gmail.com');
define('FROM_NAME', 'Petty Cash System');

// Base URL for the application (adjust if deployed differently)
define('BASE_URL', 'http://localhost:8080/petty%20cash%20system');

// Note: For Gmail, you need to enable 2FA and generate an app password
// Instructions: https://support.google.com/accounts/answer/185833
?>
