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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Request Form - <?= htmlspecialchars($request['request_id']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            body { font-size: 12px; }
            .print-header { text-align: center; margin-bottom: 30px; }
            .form-section { margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 10px; }
            .signature-line { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin-left: 10px; }
            .no-print { display: none; }
            .container { max-width: none; margin: 0; padding: 20px; }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()">Print Form</button>
            <a href="javascript:history.back()">Back</a>
        </div>

        <div class="print-header">
            <h1>Petty Cash Request Form</h1>
            <h2>Request ID: <?= htmlspecialchars($request['request_id']) ?></h2>
        </div>

        <div class="form-section">
            <h3>Requester Information</h3>
            <div class="form-row">
                <span class="form-label">Employee Name:</span>
                <span class="form-value"><?= htmlspecialchars($request['employee_name']) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Department:</span>
                <span class="form-value"><?= htmlspecialchars($request['department']) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Date Requested:</span>
                <span class="form-value"><?= htmlspecialchars($request['date_requested']) ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3>Request Details</h3>
            <div class="form-row">
                <span class="form-label">Amount Requested:</span>
                <span class="form-value">₱<?= number_format($request['amount_requested'], 2) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Expense Category:</span>
                <span class="form-value"><?= htmlspecialchars($request['expense_category']) ?></span>
            </div>
            <div class="form-row">
                <span class="form-label">Purpose:</span>
                <span class="form-value"><?= htmlspecialchars($request['purpose']) ?></span>
            </div>
            <?php if ($request['justification']): ?>
            <div class="form-row">
                <span class="form-label">Justification:</span>
                <span class="form-value"><?= htmlspecialchars($request['justification']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($request['breakdown']): ?>
            <div class="form-row">
                <span class="form-label">Breakdown:</span>
                <span class="form-value"><?= htmlspecialchars($request['breakdown']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <h3>Approval Status</h3>
            <div class="form-row">
                <span class="form-label">Status:</span>
                <span class="form-value"><?= htmlspecialchars($request['status']) ?></span>
            </div>
            <?php if ($request['status'] == 'Rejected' && $request['rejection_reason']): ?>
            <div class="form-row">
                <span class="form-label">Rejection Reason:</span>
                <span class="form-value"><?= htmlspecialchars($request['rejection_reason']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="signature-section">
            <div style="margin-bottom: 30px;">
                <strong>Requested By:</strong>
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

            <?php if ($request['status'] == 'Approved'): ?>
            <div style="margin-bottom: 30px;">
                <strong>Amount Received:</strong>
                <span class="signature-line"></span>
                <span style="margin-left: 20px;">Date:</span>
                <span class="signature-line"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
