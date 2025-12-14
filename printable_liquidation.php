<?php
session_start();
require_once "classes/database.php";

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Request ID not specified.");
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM petty_cash_requests WHERE id = ?");
$stmt->execute([$_GET['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request not found.");
}

// Check if user has permission to view this request
if ($_SESSION['user']['role'] == 'employee' && $request['user_id'] != $_SESSION['user']['id']) {
    die("Access denied.");
}

// Fetch expense items
$stmt_expenses = $conn->prepare("SELECT * FROM liquidation_expenses WHERE request_id = ?");
$stmt_expenses->execute([$request['request_id']]);
$expenses = $stmt_expenses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidation Form - <?= htmlspecialchars($request['request_id']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            body { font-size: 12px; }
            .print-header { text-align: center; margin-bottom: 30px; }
            .form-section { margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 10px; }
            .signature-line { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin-left: 10px; }
            .no-print { display: none; }
            .container { max-width: none; margin: 0; padding: 20px; }
            table { font-size: 11px; }
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .form-row {
            display: flex;
            margin-bottom: 10px;
        }
        .form-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        .form-value {
            flex: 1;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin-left: 10px;
            padding-bottom: 5px;
        }
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .expenses-table th, .expenses-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .expenses-table th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()">Print Form</button>
            <a href="javascript:history.back()">Back</a>
        </div>

        <div class="print-header">
            <h1>Liquidation Form</h1>
            <h2>Request ID: <?= htmlspecialchars($request['request_id']) ?></h2>
        </div>

        <div class="form-section">
            <h3>Employee Information</h3>
            <div class="form-row">
                <span class="form-label">Employee Name:</span>
                <span class="form-value"><?= htmlspecialchars($request['employee_name']) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Department:</span>
                <span class="form-value"><?= htmlspecialchars($request['department']) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Date Liquidated:</span>
                <span class="form-value"><?= htmlspecialchars($request['date_liquidated'] ?? 'Not yet liquidated') ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3>Original Request Summary</h3>
            <div class="form-row">
                <span class="form-label">Amount Requested:</span>
                <span class="form-value">₱<?= number_format($request['amount_requested'], 2) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Purpose:</span>
                <span class="form-value"><?= htmlspecialchars($request['purpose']) ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3>Expense Breakdown</h3>
            <?php if ($expenses): ?>
                <table class="expenses-table">
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Receipts</th>
                    </tr>
                    <?php
                    $total_expenses = 0;
                    foreach ($expenses as $exp):
                        $total_expenses += $exp['amount'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($exp['description']) ?></td>
                        <td>₱<?= number_format($exp['amount'], 2) ?></td>
                        <td>
                            <?php if ($exp['receipts']): ?>
                                Receipt attached
                            <?php else: ?>
                                No receipt
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold;">
                        <td><strong>Total Expenses</strong></td>
                        <td><strong>₱<?= number_format($total_expenses, 2) ?></strong></td>
                        <td></td>
                    </tr>
                </table>
            <?php else: ?>
                <p>No expense details available.</p>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <h3>Liquidation Summary</h3>
            <div class="form-row">
                <span class="form-label">Total Spent:</span>
                <span class="form-value">₱<?= number_format($request['total_spent'] ?? 0, 2) ?></span>
            </div>
            <?php if (isset($request['refund_needed']) && $request['refund_needed'] > 0): ?>
            <div class="form-row">
                <span class="form-label">Refund Needed:</span>
                <span class="form-value">₱<?= number_format($request['refund_needed'], 2) ?></span>
            </div>
            <?php elseif (isset($request['reimbursement']) && $request['reimbursement'] > 0): ?>
            <div class="form-row">
                <span class="form-label">Reimbursement:</span>
                <span class="form-value">₱<?= number_format($request['reimbursement'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="form-row">
                <span class="form-label">Liquidation Status:</span>
                <span class="form-value"><?= htmlspecialchars($request['liquidation_status']) ?></span>
            </div>
            <?php if ($request['liquidation_status'] == 'Rejected' && $request['rejection_reason']): ?>
            <div class="form-row">
                <span class="form-label">Rejection Reason:</span>
                <span class="form-value"><?= htmlspecialchars($request['rejection_reason']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="signature-section">
            <div style="margin-bottom: 30px;">
                <strong>Submitted By (Employee):</strong>
                <span class="signature-line"></span>
                <span style="margin-left: 20px;">Date:</span>
                <span class="signature-line"></span>
            </div>

            <div style="margin-bottom: 30px;">
                <strong>Verified By (Admin):</strong>
                <span class="signature-line"></span>
                <span style="margin-left: 20px;">Date:</span>
                <span class="signature-line"></span>
            </div>

            <div style="margin-bottom: 30px;">
                <strong>Approved By (Admin):</strong>
                <span class="signature-line"></span>
                <span style="margin-left: 20px;">Date:</span>
                <span class="signature-line"></span>
            </div>

            <?php if (isset($request['reimbursement']) && $request['reimbursement'] > 0): ?>
            <div style="margin-bottom: 30px;">
                <strong>Reimbursement Received:</strong>
                <span class="signature-line"></span>
                <span style="margin-left: 20px;">Date:</span>
                <span class="signature-line"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
