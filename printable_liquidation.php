<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session path BEFORE starting session
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

session_start();

require_once "classes/database.php";

if (!isset($_SESSION['user'])) {
    error_log("Printable liquidation - No session found");
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Request ID not specified.");
}

$db = new Database();
$conn = $db->connect();

// Get request and liquidation details
$stmt = $conn->prepare("
    SELECT 
        pr.*,
        pr.request_number,
        u.full_name as employee_name,
        d.dept_name as department,
        ec.category_name as expense_category,
        l.id as liquidation_id,
        l.total_spent,
        l.refund_amount,
        l.reimbursement_amount,
        l.date_submitted as date_liquidated,
        l.rejection_reason as liquidation_rejection_reason,
        ls.status_name as liquidation_status
    FROM petty_cash_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN departments d ON u.dept_id = d.id
    JOIN expense_categories ec ON pr.category_id = ec.id
    LEFT JOIN liquidations l ON pr.id = l.request_id
    LEFT JOIN liquidation_statuses ls ON l.status_id = ls.id
    WHERE pr.id = ?
");
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
$expenses = [];
if ($request['liquidation_id']) {
    $stmt_expenses = $conn->prepare("SELECT * FROM liquidation_expenses WHERE liquidation_id = ? ORDER BY id");
    $stmt_expenses->execute([$request['liquidation_id']]);
    $expenses = $stmt_expenses->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidation Report - <?= htmlspecialchars($request['request_number']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: white;
            padding: 40px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .no-print button, .no-print a {
            padding: 12px 30px;
            margin: 0 10px;
            font-size: 14pt;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .no-print button {
            background: #3498db;
            color: white;
        }

        .no-print button:hover {
            background: #2980b9;
        }

        .no-print a {
            background: #95a5a6;
            color: white;
        }

        .no-print a:hover {
            background: #7f8c8d;
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            border: 3px solid #2c3e50;
            padding: 0;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 5px solid #3498db;
        }

        .form-header h1 {
            font-size: 28pt;
            margin-bottom: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-header h2 {
            font-size: 18pt;
            margin-top: 15px;
            font-weight: normal;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
        }

        .form-body {
            padding: 30px;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }

        .section:last-of-type {
            border-bottom: none;
        }

        .section-title {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 5px solid #3498db;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 180px;
            flex-shrink: 0;
        }

        .info-value {
            flex: 1;
            color: #34495e;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .expense-table thead {
            background: #34495e;
            color: white;
        }

        .expense-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }

        .expense-table td {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
        }

        .expense-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .expense-table tbody tr:hover {
            background: #e9ecef;
        }

        .expense-table tfoot {
            background: #ecf0f1;
            font-weight: bold;
        }

        .expense-table tfoot td {
            padding: 15px 12px;
            border: 2px solid #2c3e50;
        }

        .amount-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .amount-box.positive {
            background: #d4edda;
            border-color: #28a745;
        }

        .amount-box.negative {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-box {
            margin-bottom: 40px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .signature-line {
            border-bottom: 2px solid #2c3e50;
            margin-top: 40px;
            position: relative;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .date-line {
            border-bottom: 2px solid #2c3e50;
            margin-top: 40px;
        }

        .footer-note {
            margin-top: 30px;
            padding: 15px;
            background: #ecf0f1;
            text-align: center;
            font-size: 10pt;
            color: #7f8c8d;
            border-radius: 5px;
        }

        @media print {
            body {
                padding: 20px;
            }

            .no-print {
                display: none !important;
            }

            .form-container {
                border: 3px solid #2c3e50;
                page-break-inside: avoid;
            }

            .signature-section {
                page-break-inside: avoid;
            }

            .expense-table {
                page-break-inside: auto;
            }

            .expense-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        @page {
            margin: 2cm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print This Report</button>
        <a href="javascript:history.back()">← Back to Dashboard</a>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1>Liquidation Report</h1>
            <p style="font-size: 14pt; margin: 10px 0;">Expense Accountability Document</p>
            <h2>Request ID: <?= htmlspecialchars($request['request_number']) ?></h2>
        </div>

        <div class="form-body">
            <!-- Employee Information -->
            <div class="section">
                <div class="section-title">👤 Employee Information</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Employee Name:</span>
                        <span class="info-value"><?= htmlspecialchars($request['employee_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?= htmlspecialchars($request['department'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Liquidated:</span>
                        <span class="info-value">
                            <?= $request['date_liquidated'] ? date('F j, Y', strtotime($request['date_liquidated'])) : 'Not yet liquidated' ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Liquidation Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?= strtolower($request['liquidation_status'] ?? 'pending') ?>">
                                <?= htmlspecialchars($request['liquidation_status'] ?? 'Not Submitted') ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Original Request Summary -->
            <div class="section">
                <div class="section-title">📄 Original Request Summary</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Amount Requested:</span>
                        <span class="info-value" style="font-size: 16pt; font-weight: bold; color: #3498db;">
                            ₱<?= number_format($request['amount_requested'], 2) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Expense Category:</span>
                        <span class="info-value"><?= htmlspecialchars($request['expense_category']) ?></span>
                    </div>
                    <div class="info-row" style="grid-column: 1 / -1;">
                        <span class="info-label">Purpose:</span>
                        <span class="info-value"><?= htmlspecialchars($request['purpose']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Expense Breakdown -->
            <div class="section">
                <div class="section-title">💳 Detailed Expense Breakdown</div>
                <?php if ($expenses): ?>
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No.</th>
                                <th>Description of Expense</th>
                                <th style="width: 150px; text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_expenses = 0;
                            foreach ($expenses as $index => $exp):
                                $total_expenses += $exp['amount'];
                            ?>
                            <tr>
                                <td style="text-align: center;"><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($exp['description']) ?></td>
                                <td style="text-align: right; font-family: 'Courier New', monospace;">
                                    ₱<?= number_format($exp['amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align: right; font-size: 14pt;">TOTAL EXPENSES:</td>
                                <td style="text-align: right; font-family: 'Courier New', monospace; font-size: 14pt; color: #e74c3c;">
                                    ₱<?= number_format($total_expenses, 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
                        ⚠️ No expense details have been submitted yet.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Financial Summary -->
            <div class="section">
                <div class="section-title">💰 Financial Summary</div>
                
                <div class="amount-box">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14pt; font-weight: bold;">Amount Requested:</span>
                        <span style="font-size: 18pt; font-weight: bold;">₱<?= number_format($request['amount_requested'], 2) ?></span>
                    </div>
                </div>

                <div class="amount-box">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14pt; font-weight: bold;">Total Amount Spent:</span>
                        <span style="font-size: 18pt; font-weight: bold;">₱<?= number_format($request['total_spent'] ?? 0, 2) ?></span>
                    </div>
                </div>

                <?php if (isset($request['refund_amount']) && $request['refund_amount'] > 0): ?>
                <div class="amount-box positive">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14pt; font-weight: bold;">💵 Refund to Company:</span>
                        <span style="font-size: 20pt; font-weight: bold; color: #155724;">₱<?= number_format($request['refund_amount'], 2) ?></span>
                    </div>
                    <p style="margin-top: 10px; font-size: 10pt; color: #155724;">
                        Employee must return this amount to the company.
                    </p>
                </div>
                <?php elseif (isset($request['reimbursement_amount']) && $request['reimbursement_amount'] > 0): ?>
                <div class="amount-box negative">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14pt; font-weight: bold;">💸 Reimbursement Due:</span>
                        <span style="font-size: 20pt; font-weight: bold; color: #721c24;">₱<?= number_format($request['reimbursement_amount'], 2) ?></span>
                    </div>
                    <p style="margin-top: 10px; font-size: 10pt; color: #721c24;">
                        Company must reimburse this amount to the employee.
                    </p>
                </div>
                <?php else: ?>
                <div class="amount-box positive">
                    <div style="text-align: center;">
                        <span style="font-size: 16pt; font-weight: bold; color: #155724;">✓ Exact Amount - No Refund or Reimbursement Required</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (($request['liquidation_status'] ?? '') == 'Rejected' && !empty($request['liquidation_rejection_reason'])): ?>
                <div class="amount-box negative">
                    <div style="font-weight: bold; margin-bottom: 10px; font-size: 14pt;">⚠️ Rejection Reason:</div>
                    <div><?= htmlspecialchars($request['liquidation_rejection_reason']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="section-title">✍️ Authorization & Verification</div>
                
                <div class="signature-box">
                    <div class="signature-label">Submitted By (Employee):</div>
                    <div class="signature-grid">
                        <div>
                            <div class="signature-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Signature over Printed Name
                            </div>
                        </div>
                        <div>
                            <div class="date-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Date
                            </div>
                        </div>
                    </div>
                </div>

                <div class="signature-box">
                    <div class="signature-label">Verified By (Administrator):</div>
                    <div class="signature-grid">
                        <div>
                            <div class="signature-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Signature over Printed Name
                            </div>
                        </div>
                        <div>
                            <div class="date-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Date
                            </div>
                        </div>
                    </div>
                </div>

                <div class="signature-box">
                    <div class="signature-label">Approved By (Administrator):</div>
                    <div class="signature-grid">
                        <div>
                            <div class="signature-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Signature over Printed Name
                            </div>
                        </div>
                        <div>
                            <div class="date-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Date
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($request['refund_amount']) && $request['refund_amount'] > 0): ?>
                <div class="signature-box">
                    <div class="signature-label">Refund Received By (Finance Officer):</div>
                    <div class="signature-grid">
                        <div>
                            <div class="signature-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Signature over Printed Name
                            </div>
                        </div>
                        <div>
                            <div class="date-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Date
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($request['reimbursement_amount']) && $request['reimbursement_amount'] > 0): ?>
                <div class="signature-box">
                    <div class="signature-label">Reimbursement Received By (Employee):</div>
                    <div class="signature-grid">
                        <div>
                            <div class="signature-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Signature over Printed Name
                            </div>
                        </div>
                        <div>
                            <div class="date-line"></div>
                            <div style="text-align: center; margin-top: 5px; font-size: 10pt; color: #7f8c8d;">
                                Date
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer-note">
                This is an official document of the Petty Cash Management System.<br>
                All signatures must be original. Photocopies are not valid for financial transactions.<br>
                All receipts and supporting documents must be attached to this liquidation report.<br>
                Printed on: <?= date('F j, Y \a\t g:i A') ?>
            </div>
        </div>
    </div>
</body>
</html>
