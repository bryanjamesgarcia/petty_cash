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

// Debug: Log session check
error_log("Employee dashboard accessed. Session user: " . print_r($_SESSION['user'] ?? 'NOT SET', true));

require_once '../classes/database.php';

// Check if user is logged in and is employee
if (!isset($_SESSION['user'])) {
    error_log("No session user found, redirecting to login");
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['user']['role'] != 'employee') {
    error_log("User role is not employee: " . $_SESSION['user']['role']);
    header("Location: ../auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$db = new Database();
$conn = $db->connect();

// Fetch employee requests
$stmt = $conn->prepare("SELECT * FROM petty_cash_requests WHERE user_id = ? ORDER BY date_requested DESC");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/employee_styles.css">
</head>
<body>
    <div class="employee-dashboard">
        <div class="logout">
            <a href="add_request.php">Add New Request</a>
            <a href="../auth/login.php">Logout</a>
        </div>

        <h1>Employee Dashboard</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($user['name']); ?></strong> | Department: <strong><?php echo htmlspecialchars($user['department']); ?></strong></p>

        <h3>My Petty Cash Requests</h3>
        
        <?php if (empty($requests)): ?>
            <p>You have not submitted any requests yet. <a href="add_request.php">Create your first request</a></p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Purpose</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Liquidation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['request_id']) ?></td>
                            <td>₱<?= number_format($r['amount_requested'], 2) ?></td>
                            <td><?= htmlspecialchars($r['expense_category']) ?></td>
                            <td><?= htmlspecialchars(substr($r['purpose'], 0, 50)) ?>...</td>
                            <td><?= htmlspecialchars($r['date_requested']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($r['status']) ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                                <?php if ($r['status'] == 'Rejected' && $r['rejection_reason']): ?>
                                    <br><small><strong>Reason:</strong> <?= htmlspecialchars($r['rejection_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['liquidation_status'] != 'Not Submitted'): ?>
                                    <span class="status-badge status-<?= strtolower($r['liquidation_status']) ?>">
                                        <?= htmlspecialchars($r['liquidation_status']) ?>
                                    </span>
                                    <?php if ($r['total_spent']): ?>
                                        <br><small>Spent: ₱<?= number_format($r['total_spent'], 2) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['status'] == 'Pending'): ?>
                                    <a href="edit_request.php?id=<?= $r['id'] ?>" class="action-btn">Edit</a>
                                    <a href="delete_request.php?id=<?= $r['id'] ?>" class="action-btn danger" onclick="return confirm('Are you sure you want to delete this request?')">Delete</a>
                                <?php elseif ($r['status'] == 'Approved' && $r['liquidation_status'] == 'Not Submitted'): ?>
                                    <a href="liquidate.php?id=<?= $r['id'] ?>" class="action-btn">Liquidate</a>
                                <?php endif; ?>
                                <br>
                                <a href="../printable_request.php?id=<?= $r['id'] ?>" target="_blank" class="action-btn">Print Request</a>
                                <?php if ($r['liquidation_status'] != 'Not Submitted'): ?>
                                    <a href="../printable_liquidation.php?id=<?= $r['id'] ?>" target="_blank" class="action-btn">Print Liquidation</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
