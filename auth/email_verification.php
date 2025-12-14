<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/EmailSender.php';

$db = new Database();
$conn = $db->connect();

$message = '';
$verified = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Find user with this verification token
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify the email
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        $message = "Email verified successfully! You can now log in.";
        $verified = true;
    } else {
        $message = "Invalid or expired verification link.";
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Petty Cash System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Email Verification</h1>

        <div class="alert alert-<?= $verified ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>

        <?php if ($verified): ?>
            <p><a href="login.php">Click here to log in</a></p>
        <?php else: ?>
            <p><a href="login.php">Back to Login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
