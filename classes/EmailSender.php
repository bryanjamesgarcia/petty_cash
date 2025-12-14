<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = SMTP_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = SMTP_USERNAME;
        $this->mail->Password = SMTP_PASSWORD;
        $this->mail->SMTPSecure = SMTP_ENCRYPTION;
        $this->mail->Port = SMTP_PORT;

        // Default sender
        $this->mail->setFrom(FROM_EMAIL, FROM_NAME);
    }

    public function sendEmailVerification($toEmail, $username, $verificationToken) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Email - Petty Cash System';

            $verificationLink = BASE_URL . "/auth/email_verification.php?token=" . $verificationToken;

            $this->mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background-color: #f8f9fa; }
                        .button { background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Petty Cash System</h1>
                        </div>
                        <div class='content'>
                            <h2>Welcome, $username!</h2>
                            <p>Thank you for registering with the Petty Cash System. Please verify your email address by clicking the button below:</p>
                            <p style='text-align: center;'>
                                <a href='$verificationLink' class='button'>Verify Email</a>
                            </p>
                            <p>If the button doesn't work, copy and paste this link into your browser:</p>
                            <p>$verificationLink</p>
                            <p>This link will expire in 24 hours.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email verification failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendNewRequestNotification($adminEmail, $employeeName, $requestId, $amount, $purpose) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($adminEmail);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'New Petty Cash Request Submitted';

            $this->mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background-color: #f8f9fa; }
                        .details { background-color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>New Petty Cash Request</h1>
                        </div>
                        <div class='content'>
                            <p>A new petty cash request has been submitted and requires your approval.</p>
                            <div class='details'>
                                <strong>Request ID:</strong> $requestId<br>
                                <strong>Employee:</strong> $employeeName<br>
                                <strong>Amount:</strong> ₱" . number_format($amount, 2) . "<br>
                                <strong>Purpose:</strong> $purpose<br>
                            </div>
                            <p>Please log in to the admin dashboard to review and approve/reject this request.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("New request notification failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
?>
