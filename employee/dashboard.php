<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session path for InfinityFree
$session_path = dirname(__DIR__) . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

session_start();

// Debug: Log session check
error_log("Admin dashboard accessed. Session user: " . print_r($_SESSION['user'] ?? 'NOT SET', true));

require_once '../classes/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    error_log("No session user found, redirecting to login");
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['user']['role'] != 'admin') {
    error_log("User role is not admin: " . $_SESSION['user']['role']);
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$message = '';
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// ... rest of your admin dashboard code
// (I'm only showing the session handling part - keep all your existing code below this)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Your existing sidebar and content -->
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Admin'); ?>!</p>
        
        <!-- Add a debug section temporarily -->
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <strong>Debug Info:</strong><br>
            Username: <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'N/A'); ?><br>
            Role: <?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'N/A'); ?><br>
            Session ID: <?php echo session_id(); ?>
        </div>
        
        <!-- Rest of your dashboard content -->
    </div>
</body>
</html>
