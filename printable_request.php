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
    error_log("Printable request - No session found");
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Request ID not specified.");
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT 
        pr.*,
        pr.request_number,
        u.full_name as employee_name,
        d.dept_name as department,
        ec.category_name as expense_category,
        rs.status_name as status
    FROM petty_cash_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN departments d ON u.dept_id = d.id
    JOIN expense_categories ec ON pr.category_id = ec.id
    JOIN request_statuses rs ON pr.status_id = rs.id
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Request Form - <?= htmlspecialchars($request['request_number']) ?></title>
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
            background: #1abc9c;
            color: white;
        }

        .no-print button:hover {
            background: #16a085;
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
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 5px solid #1abc9c;
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
            background: #1abc9c;
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

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 5px solid #1abc9c;
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

        .full-width {
            grid-column: 1 / -1;
        }

        .text-block {
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            min-height: 80px;
            white-space: pre-wrap;
            word-wrap: break-word;
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

        .status-liquidated {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #17a2b8;
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
        }

        @page {
            margin: 2cm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print This Form</button>
        <a href="employee/dashboard.php" class="back-link">Back to Dashboard</a>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1>Petty Cash Request Form</h1>
            <p style="font-size: 14pt; margin: 10px 0;">Official Document</p>
            <h2>Request ID: <?= htmlspecialchars($request['request_number']) ?></h2>
        </div>

        <div class="form-body">
            <!-- Requester Information -->
            <div class="section">
                <div class="section-title">📋 Requester Information</div>
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
                        <span class="info-label">Date Requested:</span>
                        <span class="info-value"><?= date('F j, Y', strtotime($request['date_requested'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Request Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?= strtolower($request['status']) ?>">
                                <?= htmlspecialchars($request['status']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="section">
                <div class="section-title">💰 Request Details</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Amount Requested:</span>
                        <span class="info-value" style="font-size: 18pt; font-weight: bold; color: #e74c3c;">
                            ₱<?= number_format($request['amount_requested'], 2) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Expense Category:</span>
                        <span class="info-value"><?= htmlspecialchars($request['expense_category']) ?></span>
                    </div>
                </div>
                
                <div class="info-row full-width" style="display: block; padding: 0; border: none;">
                    <div class="info-label" style="margin-bottom: 10px;">Purpose / Description:</div>
                    <div class="text-block"><?= htmlspecialchars($request['purpose']) ?></div>
                </div>

                <?php if (!empty($request['justification'])): ?>
                <div class="info-row full-width" style="display: block; padding: 0; border: none; margin-top: 15px;">
                    <div class="info-label" style="margin-bottom: 10px;">Justification:</div>
                    <div class="text-block"><?= htmlspecialchars($request['justification']) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($request['breakdown'])): ?>
                <div class="info-row full-width" style="display: block; padding: 0; border: none; margin-top: 15px;">
                    <div class="info-label" style="margin-bottom: 10px;">Detailed Breakdown:</div>
                    <div class="text-block"><?= htmlspecialchars($request['breakdown']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Approval Information -->
            <?php if ($request['status'] == 'Rejected' && !empty($request['rejection_reason'])): ?>
            <div class="section">
                <div class="section-title">⚠️ Rejection Information</div>
                <div class="info-row full-width" style="display: block; padding: 0; border: none;">
                    <div class="info-label" style="margin-bottom: 10px;">Reason for Rejection:</div>
                    <div class="text-block" style="background: #f8d7da; border-color: #dc3545;">
                        <?= htmlspecialchars($request['rejection_reason']) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="section-title">✍️ Authorization & Approval</div>
                
                <div class="signature-box">
                    <div class="signature-label">Requested By (Employee):</div>
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
                    <div class="signature-label">Reviewed & Approved By (Administrator):</div>
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

                <?php if ($request['status'] == 'Approved'): ?>
                <div class="signature-box">
                    <div class="signature-label">Amount Received By (Employee):</div>
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
                Printed on: <?= date('F j, Y \a\t g:i A') ?>
            </div>
        </div>
    </div>
</body>
</html>
