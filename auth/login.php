<?php
// Set error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session path for InfinityFree BEFORE starting session
$session_path = dirname(__DIR__) . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

// Start session
session_start();

require_once '../classes/database.php';

$db = new Database();
$conn = $db->connect();

$message = '';
$show_registration = isset($_GET['register']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        try {
            // Updated query to match new database schema
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.password, r.role_name as role, u.full_name as name, 
                       d.dept_name as department, u.email, u.email_verified, u.is_active 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.dept_id = d.id
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'department' => $user['department']
                ];
                
                // Force session write
                session_write_close();
                
                // Start session again for any further processing
                session_start();

                // Update last login
                try {
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch (PDOException $e) {
                    error_log("Last login update failed: " . $e->getMessage());
                }
                
                // Redirect based on role - use absolute path
                if ($user['role'] == 'admin') {
                    header("Location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/../admin/dashboard.php");
                    exit();
                } else {
                    header("Location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/../employee/dashboard.php");
                    exit();
                }
            } else {
                $message = "Invalid username or password!";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
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
            try {
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

                        // Find or create department
                        $stmt = $conn->prepare("SELECT id FROM departments WHERE dept_name = ?");
                        $stmt->execute([$department]);
                        $dept = $stmt->fetch();
                        
                        if (!$dept) {
                            $dept_code = strtoupper(substr($department, 0, 3));
                            $stmt = $conn->prepare("INSERT INTO departments (dept_name, dept_code) VALUES (?, ?)");
                            $stmt->execute([$department, $dept_code]);
                            $dept_id = $conn->lastInsertId();
                        } else {
                            $dept_id = $dept['id'];
                        }

                        // Get employee role_id (should be 2)
                        $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'employee'");
                        $stmt->execute();
                        $role_id = $stmt->fetchColumn();

                        // Insert new user
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role_id, dept_id, full_name, email, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                        $stmt->execute([$username, $hashed_password, $role_id, $dept_id, $name, $email, $verification_token]);

                        $message = "Registration successful! You can now log in.";
                        $show_registration = false;
                    }
                }
            } catch (PDOException $e) {
                $message = "Registration error: " . $e->getMessage();
                error_log("Registration error: " . $e->getMessage());
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

        <?php if (!empty($message)): ?>
            <div class='alert alert-info'><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

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

    if (togglePassword && password) {
        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    }
    </script>
</body>
</html>
