<?php
// Set session path BEFORE starting session
$session_path = dirname(__DIR__) . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

session_start();
require_once '../classes/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$db = new Database();
$conn = $db->connect();

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    header("Location: dashboard.php");
    exit();
}

// Get request details
$stmt = $conn->prepare("SELECT pr.*, rs.status_name as status FROM petty_cash_requests pr
                        JOIN request_statuses rs ON pr.status_id = rs.id
                        WHERE pr.id = ? AND pr.user_id = ? AND rs.status_name = 'Approved'");
$stmt->execute([$request_id, $user['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descriptions = $_POST['description'];
    $amounts = $_POST['amount'];
    $date_submitted = date("Y-m-d");

    $total_spent = 0;
    foreach ($amounts as $amount) {
        $total_spent += floatval($amount);
    }

    $refund_amount = max(0, $request['amount_requested'] - $total_spent);
    $reimbursement_amount = max(0, $total_spent - $request['amount_requested']);

    try {
        // Get pending liquidation status
        $stmt = $conn->prepare("SELECT id FROM liquidation_statuses WHERE status_name = 'Pending'");
        $stmt->execute();
        $liquidation_status_id = $stmt->fetchColumn();

        // Create liquidation record
        $stmt = $conn->prepare("INSERT INTO liquidations (request_id, status_id, total_spent, refund_amount, reimbursement_amount, date_submitted, approved_by) 
                                VALUES (?, ?, ?, ?, ?, ?, NULL)");
        $stmt->execute([$request['id'], $liquidation_status_id, $total_spent, $refund_amount, $reimbursement_amount, $date_submitted]);
        
        $liquidation_id = $conn->lastInsertId();

        // Insert expense items
        foreach ($descriptions as $key => $description) {
            if (!empty($description) && !empty($amounts[$key])) {
                $stmt = $conn->prepare("INSERT INTO liquidation_expenses (liquidation_id, description, amount) VALUES (?, ?, ?)");
                $stmt->execute([$liquidation_id, $description, $amounts[$key]]);
            }
        }

        echo "<script>alert('Liquidation submitted successfully!'); window.location.href='dashboard.php';</script>";
    } catch (PDOException $e) {
        error_log("Liquidation error: " . $e->getMessage());
        $error_message = "Error submitting liquidation: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidate Petty Cash Request</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/employee_styles.css">
</head>
<body>
    <div class="add-request-container">
        <h2>Liquidate Petty Cash Request</h2>

        <p><strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_number']); ?></p>
        <p><strong>Amount Requested:</strong> ₱<?php echo number_format($request['amount_requested'], 2); ?></p>
        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>

        <?php if (isset($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="liquidationForm" class="add-request-form">
            <h3>Expense Items</h3>
            <div id="expenseItems">
                <div class="expense-row" style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <div style="margin-bottom: 10px;">
                        <label>Description:</label>
                        <input type="text" name="description[]" placeholder="Description" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Amount (₱):</label>
                        <input type="number" step="0.01" name="amount[]" placeholder="Amount" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <button type="button" class="remove-expense" onclick="removeExpense(this)" style="background: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer;">Remove</button>
                </div>
            </div>
            <button type="button" onclick="addExpense()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 0;">Add Another Expense</button>

            <button type="submit">Submit Liquidation</button>
        </form>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>

    <script>
        let expenseIndex = 1;

        function addExpense() {
            const expenseItems = document.getElementById('expenseItems');
            const newRow = document.createElement('div');
            newRow.className = 'expense-row';
            newRow.style.marginBottom = '15px';
            newRow.style.padding = '15px';
            newRow.style.background = '#f8f9fa';
            newRow.style.borderRadius = '5px';
            newRow.innerHTML = `
                <div style="margin-bottom: 10px;">
                    <label>Description:</label>
                    <input type="text" name="description[]" placeholder="Description" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label>Amount (₱):</label>
                    <input type="number" step="0.01" name="amount[]" placeholder="Amount" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button type="button" class="remove-expense" onclick="removeExpense(this)" style="background: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer;">Remove</button>
            `;
            expenseItems.appendChild(newRow);
            expenseIndex++;
        }

        function removeExpense(button) {
            const expenseItems = document.getElementById('expenseItems');
            if (expenseItems.children.length > 1) {
                button.parentElement.remove();
            } else {
                alert('You must have at least one expense item.');
            }
        }
    </script>
</body>
</html>
