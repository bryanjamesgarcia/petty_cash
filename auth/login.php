<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/EmailSender.php';

$db = new Database();
$db->createTables(); // Ensure tables exist
$conn = $db->connect();

$message = '';
$show_registration = isset($_GET['register']);
$show_resend_form = false; // Flag to show resend form

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Query user from database
        $stmt = $conn->prepare("SELECT id, username, password, role, name, department, email, email_verified FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['email_verified'] == 0) {
                $message = "Please verify your email before logging in.";
                $show_resend_form = true;
                $resend_username = $username; // To prefill the resend form
            } else {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'department' => $user['department']
                ];

                if ($user['role'] == 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../employee/dashboard.php");
                }
                exit();
            }
        } else {
            $message = "Invalid credentials!";
        }
    } elseif (isset($_POST['register'])) {
        $username = trim($_POST['reg_username']);
        $password = $_POST['reg_password'];
        $name = trim($_POST['reg_name']);
        $department = trim($_POST['reg_department']);
        $email = trim($_POST['reg_email']);

        if (empty($username) || empty($password) || empty($name) || empty($department) || empty($email)) {
            $message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Username already exists.";
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "Email already registered.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $verification_token = bin2hex(random_bytes(32));

                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department, email, verification_token) VALUES (?, ?, 'employee', ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $name, $department, $email, $verification_token]);

                    $emailSender = new EmailSender();
                    if ($emailSender->sendEmailVerification($email, $username, $verification_token)) {
                        $message = "Registration successful! Please check your email to verify your account.";
                        $show_registration = false;
                    } else {
                        $message = "Registration successful, but failed to send verification email. Please contact admin.";
                    }
                }
            }
        }
    } elseif (isset($_POST['resend_verification'])) {
        // Handle resend verification email request
        $resend_username = trim($_POST['resend_username']);
        if (empty($resend_username)) {
            $message = "Please enter your username to resend verification email.";
            $show_resend_form = true;
        } else {
            // Lookup user by username
            $stmt = $conn->prepare("SELECT id, email, username, email_verified FROM users WHERE username = ?");
            $stmt->execute([$resend_username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $message = "Username not found.";
                $show_resend_form = true;
            } elseif ($user['email_verified'] == 1) {
                $message = "Your email is already verified. You can log in.";
            } else {
                // Generate new verification token
                $new_token = bin2hex(random_bytes(32));
                $stmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
                $stmt->execute([$new_token, $user['id']]);

                // Send verification email
                $emailSender = new EmailSender();
                if ($emailSender->sendEmailVerification($user['email'], $user['username'], $new_token)) {
                    $message = "Verification email resent. Please check your email.";
                } else {
                    $message = "Failed to resend verification email. Please contact admin.";
                }
                $show_resend_form = false;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Petty Cash System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../temp_login_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Petty Cash System Login</h1>

        <?php if (!empty($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

        <?php if ($show_registration): ?>
            <h2>Register New Account</h2>
            <form class="login-form" method="POST" action="login.php?register=1">
                <label for="reg_username">Username:</label>
                <input type="text" id="reg_username" name="reg_username" required>

                <label for="reg_password">Password:</label>
                <input type="password" id="reg_password" name="reg_password" required>

                <label for="reg_name">Full Name:</label>
                <input type="text" id="reg_name" name="reg_name" required>

                <label for="reg_department">Department:</label>
                <input type="text" id="reg_department" name="reg_department" required>

                <label for="reg_email">Email:</label>
                <input type="email" id="reg_email" name="reg_email" required>

                <input type="submit" name="register" value="Register">
            </form>
            <p><a href="login.php">Back to Login</a></p>
        <?php elseif ($show_resend_form): ?>
            <h2>Resend Verification Email</h2>
            <p>Please enter your username to resend your email verification link.</p>
            <form class="login-form" method="POST" action="login.php">
                <label for="resend_username">Username:</label>
                <input type="text" id="resend_username" name="resend_username" required value="<?php echo htmlspecialchars($resend_username ?? ''); ?>">
                <input type="submit" name="resend_verification" value="Resend Verification Email">
            </form>
            <p><a href="login.php">Back to Login</a></p>
        <?php else: ?>
            <form class="login-form" method="POST" action="login.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>

                <input type="submit" name="login" value="Login">
            </form>
            <p>Don't have an account? <a href="login.php?register=1">Register here</a></p>
        <?php endif; ?>
    </div>

    <script>
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");

    togglePassword.addEventListener("click", function () {
        const type = password.getAttribute("type") === "password" ? "text" : "password";
        password.setAttribute("type", type);
        this.classList.toggle("fa-eye");
        this.classList.toggle("fa-eye-slash");
    });
    </script>
</body>
</html>
