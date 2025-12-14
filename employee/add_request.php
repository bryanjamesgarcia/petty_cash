<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session path BEFORE starting session
$session_path = dirname(__DIR__) . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

session_start();

require_once '../classes/database.php';

// Debug logging
error_log("Add request page - Session: " . print_r($_SESSION['user'] ?? 'NOT SET', true));

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'employee') {
    error_log("Access denied to add_request. Role: " . ($_SESSION['user']['role'] ?? 'NO SESSION'));
    header("Location: ../auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_name = $user['name'];
    $department = $user['department'];
    $amount_requested = $_POST['amount_requested'];
    $purpose = $_POST['purpose'];
    $expense_category = $_POST['expense_category'];
    $justification = $_POST['justification'];
    $breakdown = $_POST['breakdown'];
    $date_requested = date("Y-m-d");

    try {
        // Get the category_id and status_id
        $stmt = $conn->prepare("SELECT id FROM expense_categories WHERE category_name = ?");
        $stmt->execute([$expense_category]);
        $category_id = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT id FROM request_statuses WHERE status_name = 'Pending'");
        $stmt->execute();
        $status_id = $stmt->fetchColumn();

        // Insert the request
        $stmt = $conn->prepare("INSERT INTO petty_cash_requests (user_id, category_id, status_id, amount_requested, purpose, justification, breakdown, date_requested)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $category_id, $status_id, $amount_requested, $purpose, $justification, $breakdown, $date_requested]);

        // Auto-generate request_number
        $last_id = $conn->lastInsertId();
        $request_number = 'PCR-' . str_pad($last_id, 5, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("UPDATE petty_cash_requests SET request_number = ? WHERE id = ?");
        $stmt->execute([$request_number, $last_id]);

        // Send email notification to admin (optional)
        try {
            $stmt_admin = $conn->prepare("SELECT u.email FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'admin' AND u.email IS NOT NULL LIMIT 1");
            $stmt_admin->execute();
            $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

            if ($admin && $admin['email']) {
                require_once '../classes/EmailSender.php';
                $emailSender = new EmailSender();
                $emailSender->sendNewRequestNotification($admin['email'], $employee_name, $request_number, $amount_requested, $purpose);
            }
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }

        echo "<script>alert('Request submitted successfully! Request ID: $request_number'); window.location.href='dashboard.php';</script>";
    } catch (PDOException $e) {
        error_log("Request creation error: " . $e->getMessage());
        $error_message = "Error submitting request: " . $e->getMessage();
    }
}

// Fetch available categories
$stmt = $conn->prepare("SELECT category_name FROM expense_categories WHERE is_active = 1 ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Petty Cash Request</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/employee_styles.css">
</head>
<body>
    <div class="add-request-container">
        <h2>Add Petty Cash Request</h2>
        <p><strong>Employee:</strong> <?php echo htmlspecialchars($user['name']); ?> | <strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        
        <?php if (isset($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="add-request-form">
            <div>
                <label>Amount Requested (₱):</label>
                <input type="number" step="0.01" name="amount_requested" required min="0">
            </div>

            <div>
                <label>Purpose/Description:</label>
                <textarea name="purpose" required></textarea>
            </div>

            <div>
                <label>Expense Category:</label>
                <select name="expense_category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Justification:</label>
                <textarea name="justification" placeholder="Explain why this expense is necessary..." required></textarea>
            </div>

            <div>
                <label>Detailed Breakdown:</label>
                <textarea name="breakdown" placeholder="Provide a detailed breakdown of the expenses..." required></textarea>
            </div>

            <button type="submit">Submit Request</button>
        </form>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
