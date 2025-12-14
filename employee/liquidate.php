<?php
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
$stmt = $conn->prepare("SELECT * FROM petty_cash_requests WHERE id = ? AND user_id = ? AND status = 'Approved'");
$stmt->execute([$request_id, $user['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descriptions = $_POST['description'];
    $amounts = $_POST['amount'];
    $date_liquidated = date("Y-m-d");

    $total_spent = 0;
    foreach ($amounts as $amount) {
        $total_spent += floatval($amount);
    }

    $refund_needed = $request['amount_requested'] - $total_spent;
    $reimbursement = $total_spent - $request['amount_requested'];

    // Handle file uploads per expense
    $upload_dir = '../uploads/receipts/';
    $expense_receipts = [];

    if (isset($_FILES['receipts'])) {
        foreach ($_FILES['receipts']['tmp_name'] as $expense_index => $files) {
            $receipt_paths = [];
            if (is_array($files)) {
                foreach ($files as $key => $tmp_name) {
                    $file_name = $_FILES['receipts']['name'][$expense_index][$key];
                    $file_size = $_FILES['receipts']['size'][$expense_index][$key];
                    $file_error = $_FILES['receipts']['error'][$expense_index][$key];
                    $file_type = $_FILES['receipts']['type'][$expense_index][$key];

                    // Validate file
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if ($file_error === UPLOAD_ERR_OK && in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) { // 5MB limit
                        $unique_name = uniqid() . '_' . basename($file_name);
                        $file_path = $upload_dir . $unique_name;
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $receipt_paths[] = $file_path;
                        }
                    }
                }
            }
            $expense_receipts[$expense_index] = $receipt_paths;
        }
    }

    // Insert expense items into liquidation_expenses table with receipts
    foreach ($descriptions as $key => $description) {
        if (!empty($description) && !empty($amounts[$key])) {
            $receipts_json = isset($expense_receipts[$key]) ? json_encode($expense_receipts[$key]) : null;
            $stmt = $conn->prepare("INSERT INTO liquidation_expenses (request_id, description, amount, receipts) VALUES (?, ?, ?, ?)");
            $stmt->execute([$request['request_id'], $description, $amounts[$key], $receipts_json]);
        }
    }

    $stmt = $conn->prepare("UPDATE petty_cash_requests SET
                            actual_expenses = ?,
                            total_spent = ?,
                            refund_needed = ?,
                            reimbursement = ?,
                            liquidation_status = 'Pending',
                            date_liquidated = ?
                            WHERE id = ?");
    $stmt->execute([$total_spent, $total_spent, $refund_needed > 0 ? $refund_needed : 0, $reimbursement > 0 ? $reimbursement : 0, $date_liquidated, $request_id]);

    echo "<script>alert('Liquidation submitted successfully!'); window.location.href='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidate Petty Cash Request</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Liquidate Petty Cash Request</h2>

        <p><strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?></p>
        <p><strong>Amount Requested:</strong> ₱<?php echo number_format($request['amount_requested'], 2); ?></p>
        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>

        <form method="POST" id="liquidationForm" enctype="multipart/form-data">
            <h3>Expense Items</h3>
            <div id="expenseItems">
                <div class="expense-row">
                    <input type="text" name="description[]" placeholder="Description" required>
                    <input type="number" step="0.01" name="amount[]" placeholder="Amount (₱)" required>
                    <label>Receipts:</label>
                    <input type="file" name="receipts[0][]" accept="image/*" multiple>
                    <button type="button" class="remove-expense" onclick="removeExpense(this)">Remove</button>
                </div>
            </div>
            <button type="button" onclick="addExpense()">Add Another Expense</button>

            <button type="submit">Submit Liquidation</button>
        </form>

        <a href="dashboard.php">Back to Dashboard</a>
    </div>

    <script>
        let expenseIndex = 1;

        function addExpense() {
            const expenseItems = document.getElementById('expenseItems');
            const newRow = document.createElement('div');
            newRow.className = 'expense-row';
            newRow.innerHTML = `
                <input type="text" name="description[]" placeholder="Description" required>
                <input type="number" step="0.01" name="amount[]" placeholder="Amount (₱)" required>
                <label>Receipts:</label>
                <input type="file" name="receipts[${expenseIndex}][]" accept="image/*" multiple>
                <button type="button" class="remove-expense" onclick="removeExpense(this)">Remove</button>
            `;
            expenseItems.appendChild(newRow);
            expenseIndex++;
        }

        function removeExpense(button) {
            const expenseItems = document.getElementById('expenseItems');
            if (expenseItems.children.length > 1) {
                button.parentElement.remove();
            }
        }
    </script>
</body>
</html>
