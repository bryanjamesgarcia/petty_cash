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

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    error_log("Printable reports - Access denied");
    header("Location: auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build date range for the selected month
$start_date = $year . '-' . $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// Fetch summary statistics
$stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE date_requested BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_requests = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM petty_cash_requests pr 
    JOIN request_statuses rs ON pr.status_id = rs.id 
    WHERE rs.status_name = 'Approved' AND pr.date_requested BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$approved_requests = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT SUM(pr.amount_requested) 
    FROM petty_cash_requests pr 
    JOIN request_statuses rs ON pr.status_id = rs.id 
    WHERE rs.status_name = 'Approved' AND pr.date_requested BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_approved_amount = $stmt->fetchColumn() ?: 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM liquidations WHERE date_submitted BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_liquidations = $stmt->fetchColumn();

// Fetch expense breakdown by category
$query = "
    SELECT ec.category_name as expense_category, COUNT(*) as request_count, SUM(pr.amount_requested) as total_amount
    FROM petty_cash_requests pr
    JOIN expense_categories ec ON pr.category_id = ec.id
    JOIN request_statuses rs ON pr.status_id = rs.id
    WHERE rs.status_name = 'Approved' AND pr.date_requested BETWEEN ? AND ?
";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND EXISTS (SELECT 1 FROM users u JOIN departments d ON u.dept_id = d.id WHERE u.id = pr.user_id AND d.dept_name = ?)";
    $params[] = $department;
}

if ($category) {
    $query .= " AND ec.category_name = ?";
    $params[] = $category;
}

$query .= " GROUP BY ec.category_name ORDER BY total_amount DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch department breakdown
$query = "
    SELECT d.dept_name as department, COUNT(*) as request_count, SUM(pr.amount_requested) as total_amount
    FROM petty_cash_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN departments d ON u.dept_id = d.id
    JOIN request_statuses rs ON pr.status_id = rs.id
    WHERE rs.status_name = 'Approved' AND pr.date_requested BETWEEN ? AND ?
";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND d.dept_name = ?";
    $params[] = $department;
}

if ($category) {
    $query .= " AND EXISTS (SELECT 1 FROM expense_categories ec WHERE ec.id = pr.category_id AND ec.category_name = ?)";
    $params[] = $category;
}

$query .= " GROUP BY d.dept_name ORDER BY total_amount DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$department_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch detailed requests list
$query = "
    SELECT 
        pr.request_number,
        u.full_name as employee_name,
        d.dept_name as department,
        ec.category_name as expense_category,
        pr.amount_requested,
        rs.status_name as status,
        pr.date_requested
    FROM petty_cash_requests pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN departments d ON u.dept_id = d.id
    JOIN expense_categories ec ON pr.category_id = ec.id
    JOIN request_statuses rs ON pr.status_id = rs.id
    WHERE pr.date_requested BETWEEN ? AND ?
";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND d.dept_name = ?";
    $params[] = $department;
}

if ($category) {
    $query .= " AND ec.category_name = ?";
    $params[] = $category;
}

$query .= " ORDER BY pr.date_requested DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Petty Cash Report - <?= date('F Y', strtotime($start_date)) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            background: white;
            padding: 30px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .no-print form {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .no-print label {
            font-weight: bold;
            margin-right: 5px;
        }

        .no-print select, .no-print input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .no-print button, .no-print a {
            padding: 10px 25px;
            margin: 5px;
            font-size: 12pt;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .no-print button[type="submit"] {
            background: #3498db;
            color: white;
        }

        .no-print button[onclick] {
            background: #27ae60;
            color: white;
        }

        .no-print a {
            background: #95a5a6;
            color: white;
        }

        .report-container {
            max-width: 1100px;
            margin: 0 auto;
            border: 3px solid #2c3e50;
        }

        .report-header {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            padding: 40px;
            text-align: center;
            border-bottom: 5px solid #27ae60;
        }

        .report-header h1 {
            font-size: 32pt;
            margin-bottom: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .report-header h2 {
            font-size: 22pt;
            margin-top: 15px;
            font-weight: normal;
        }

        .report-meta {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 12pt;
        }

        .report-body {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 18pt;
            font-weight: bold;
            color: white;
            background: #2c3e50;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-left: 6px solid #1abc9c;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-box.green {
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .stat-box.orange {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .stat-box.red {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-label {
            font-size: 10pt;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 28pt;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .data-table thead {
            background: #34495e;
            color: white;
        }

        .data-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c3e50;
            font-size: 10pt;
        }

        .data-table td {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            font-size: 10pt;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e9ecef;
        }

        .data-table tfoot {
            background: #ecf0f1;
            font-weight: bold;
        }

        .highlight-row {
            background: #fff3cd !important;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            padding: 20px;
            background: #ecf0f1;
            border-top: 3px solid #2c3e50;
            text-align: center;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .signature-area {
            margin-top: 50px;
            padding: 20px;
            border-top: 2px solid #2c3e50;
            page-break-inside: avoid;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin: 20px 2%;
        }

        .signature-line {
            border-bottom: 2px solid #2c3e50;
            margin: 40px auto 10px;
            width: 80%;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .report-container {
                border: 3px solid #2c3e50;
                page-break-inside: avoid;
            }

            .section {
                page-break-inside: avoid;
            }

            .data-table {
                page-break-inside: auto;
            }

            .data-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @page {
            margin: 1.5cm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <form method="GET">
            <label>Month:</label>
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label>Year:</label>
            <select name="year">
                <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <label>Department:</label>
            <input type="text" name="department" value="<?= htmlspecialchars($department) ?>" placeholder="All">

            <label>Category:</label>
            <input type="text" name="category" value="<?= htmlspecialchars($category) ?>" placeholder="All">

            <button type="submit">🔍 Filter</button>
            <button type="button" onclick="window.print()">🖨️ Print</button>
            <a href="admin/dashboard.php" class="back-link">Back</a>
        </form>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h1>📊 Petty Cash Report</h1>
            <h2><?= date('F Y', strtotime($start_date)) ?></h2>
            <div class="report-meta">
                <?php if ($department): ?>
                    <strong>Filtered by Department:</strong> <?= htmlspecialchars($department) ?><br>
                <?php endif; ?>
                <?php if ($category): ?>
                    <strong>Filtered by Category:</strong> <?= htmlspecialchars($category) ?><br>
                <?php endif; ?>
                <strong>Report Period:</strong> <?= date('F j, Y', strtotime($start_date)) ?> to <?= date('F j, Y', strtotime($end_date)) ?>
            </div>
        </div>

        <div class="report-body">
            <!-- Summary Statistics -->
            <div class="section">
                <div class="section-title">📈 Summary Statistics</div>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Total Requests</div>
                        <div class="stat-value"><?= $total_requests ?></div>
                    </div>
                    <div class="stat-box green">
                        <div class="stat-label">Approved</div>
                        <div class="stat-value"><?= $approved_requests ?></div>
                    </div>
                    <div class="stat-box orange">
                        <div class="stat-label">Total Amount</div>
                        <div class="stat-value" style="font-size: 18pt;">₱<?= number_format($total_approved_amount, 0) ?></div>
                    </div>
                    <div class="stat-box red">
                        <div class="stat-label">Liquidations</div>
                        <div class="stat-value"><?= $total_liquidations ?></div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <?php if ($category_breakdown): ?>
            <div class="section">
                <div class="section-title">📁 Expense by Category</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Category</th>
                            <th style="width: 120px; text-align: center;">Requests</th>
                            <th style="width: 180px; text-align: right;">Total Amount</th>
                            <th style="width: 100px; text-align: center;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 1;
                        foreach ($category_breakdown as $cat): 
                            $percentage = $total_approved_amount > 0 ? ($cat['total_amount'] / $total_approved_amount * 100) : 0;
                        ?>
                        <tr>
                            <td style="text-align: center;"><?= $index++ ?></td>
                            <td><strong><?= htmlspecialchars($cat['expense_category']) ?></strong></td>
                            <td style="text-align: center;"><?= $cat['request_count'] ?></td>
                            <td style="text-align: right; font-family: 'Courier New', monospace;">
                                ₱<?= number_format($cat['total_amount'], 2) ?>
                            </td>
                            <td style="text-align: center;"><?= number_format($percentage, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right;">TOTAL:</td>
                            <td style="text-align: center;"><?= array_sum(array_column($category_breakdown, 'request_count')) ?></td>
                            <td style="text-align: right; font-family: 'Courier New', monospace;">
                                ₱<?= number_format($total_approved_amount, 2) ?>
                            </td>
                            <td style="text-align: center;">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <!-- Department Breakdown -->
            <?php if ($department_breakdown): ?>
            <div class="section">
                <div class="section-title">🏢 Expense by Department</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Department</th>
                            <th style="width: 120px; text-align: center;">Requests</th>
                            <th style="width: 180px; text-align: right;">Total Amount</th>
                            <th style="width: 100px; text-align: center;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 1;
                        foreach ($department_breakdown as $dept): 
                            $percentage = $total_approved_amount > 0 ? ($dept['total_amount'] / $total_approved_amount * 100) : 0;
                        ?>
                        <tr>
                            <td style="text-align: center;"><?= $index++ ?></td>
                            <td><strong><?= htmlspecialchars($dept['department'] ?? 'N/A') ?></strong></td>
                            <td style="text-align: center;"><?= $dept['request_count'] ?></td>
                            <td style="text-align: right; font-family: 'Courier New', monospace;">
                                ₱<?= number_format($dept['total_amount'], 2) ?>
                            </td>
                            <td style="text-align: center;"><?= number_format($percentage, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Detailed List -->
            <div class="section">
                <div class="section-title">📋 Detailed Transaction List</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Request ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th style="width: 120px; text-align: right;">Amount</th>
                            <th style="width: 80px; text-align: center;">Status</th>
                            <th style="width: 90px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['request_number']) ?></td>
                            <td><?= htmlspecialchars($r['employee_name']) ?></td>
                            <td><?= htmlspecialchars($r['department'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($r['expense_category']) ?></td>
                            <td style="text-align: right; font-family: 'Courier New', monospace;">
                                ₱<?= number_format($r['amount_requested'], 2) ?>
                            </td>
                            <td style="text-align: center;">
                                <small><?= htmlspecialchars($r['status']) ?></small>
                            </td>
                            <td><?= date('M j, Y', strtotime($r['date_requested'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="footer-row">
                <span><strong>Report Generated:</strong> <?= date('F j, Y \a\t g:i A') ?></span>
                <span><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
            </div>
            <div style="margin-top: 15px; font-size: 9pt; color: #7f8c8d;">
                This is a computer-generated report from the Petty Cash Management System
            </div>
        </div>

        <div class="signature-area">
            <div class="signature-box">
                <strong>Prepared By:</strong>
                <div class="signature-line"></div>
                <div style="margin-top: 5px; font-size: 9pt; color: #7f8c8d;">Administrator</div>
            </div>
            <div class="signature-box">
                <strong>Reviewed By:</strong>
                <div class="signature-line"></div>
                <div style="margin-top: 5px; font-size: 9pt; color: #7f8c8d;">Finance Head</div>
            </div>
        </div>
    </div>
</body>
</html>
