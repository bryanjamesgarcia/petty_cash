<?php
session_start();
require_once "classes/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
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

$stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE status = 'Approved' AND date_requested BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$approved_requests = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT SUM(amount_requested) FROM petty_cash_requests WHERE status = 'Approved' AND date_requested BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_approved_amount = $stmt->fetchColumn() ?: 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE liquidation_status IN ('Approved', 'Rejected') AND date_liquidated BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_liquidations = $stmt->fetchColumn();

// Fetch expense breakdown by category
$query = "SELECT expense_category, COUNT(*) as request_count, SUM(amount_requested) as total_amount
          FROM petty_cash_requests
          WHERE status = 'Approved' AND date_requested BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
}

if ($category) {
    $query .= " AND expense_category = ?";
    $params[] = $category;
}

$query .= " GROUP BY expense_category ORDER BY total_amount DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch department breakdown
$query = "SELECT department, COUNT(*) as request_count, SUM(amount_requested) as total_amount
          FROM petty_cash_requests
          WHERE status = 'Approved' AND date_requested BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
}

if ($category) {
    $query .= " AND expense_category = ?";
    $params[] = $category;
}

$query .= " GROUP BY department ORDER BY total_amount DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$department_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch detailed requests list
$query = "SELECT * FROM petty_cash_requests WHERE date_requested BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
}

if ($category) {
    $query .= " AND expense_category = ?";
    $params[] = $category;
}

$query .= " ORDER BY date_requested DESC";

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
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            body { font-size: 11px; }
            .print-header { text-align: center; margin-bottom: 20px; }
            .report-section { margin-bottom: 20px; page-break-inside: avoid; }
            .summary-table { font-size: 10px; }
            .no-print { display: none; }
            .container { max-width: none; margin: 0; padding: 15px; }
            table { font-size: 9px; page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .report-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .summary-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .filters {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print filters">
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
                <input type="text" name="department" value="<?= htmlspecialchars($department) ?>" placeholder="All departments">

                <label>Category:</label>
                <input type="text" name="category" value="<?= htmlspecialchars($category) ?>" placeholder="All categories">

                <button type="submit">Filter</button>
                <button onclick="window.print()">Print Report</button>
                <a href="admin/dashboard.php">Back to Dashboard</a>
            </form>
        </div>

        <div class="print-header">
            <h1>Petty Cash Monthly Report</h1>
            <h2><?= date('F Y', strtotime($start_date)) ?></h2>
            <?php if ($department): ?>
                <p><strong>Department:</strong> <?= htmlspecialchars($department) ?></p>
            <?php endif; ?>
            <?php if ($category): ?>
                <p><strong>Category:</strong> <?= htmlspecialchars($category) ?></p>
            <?php endif; ?>
        </div>

        <div class="report-section">
            <h3>Summary Statistics</h3>
            <table class="summary-table">
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Total Requests</td>
                    <td><?= $total_requests ?></td>
                </tr>
                <tr>
                    <td>Approved Requests</td>
                    <td><?= $approved_requests ?></td>
                </tr>
                <tr>
                    <td>Total Approved Amount</td>
                    <td>₱<?= number_format($total_approved_amount, 2) ?></td>
                </tr>
                <tr>
                    <td>Liquidations Processed</td>
                    <td><?= $total_liquidations ?></td>
                </tr>
            </table>
        </div>

        <?php if ($category_breakdown): ?>
        <div class="report-section">
            <h3>Expense Breakdown by Category</h3>
            <table class="summary-table">
                <tr>
                    <th>Category</th>
                    <th>Number of Requests</th>
                    <th>Total Amount</th>
                </tr>
                <?php foreach ($category_breakdown as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['expense_category']) ?></td>
                    <td><?= $cat['request_count'] ?></td>
                    <td>₱<?= number_format($cat['total_amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($department_breakdown): ?>
        <div class="report-section">
            <h3>Expense Breakdown by Department</h3>
            <table class="summary-table">
                <tr>
                    <th>Department</th>
                    <th>Number of Requests</th>
                    <th>Total Amount</th>
                </tr>
                <?php foreach ($department_breakdown as $dept): ?>
                <tr>
                    <td><?= htmlspecialchars($dept['department']) ?></td>
                    <td><?= $dept['request_count'] ?></td>
                    <td>₱<?= number_format($dept['total_amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <div class="report-section">
            <h3>Detailed Requests List</h3>
            <table class="summary-table">
                <tr>
                    <th>Request ID</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['request_id']) ?></td>
                    <td><?= htmlspecialchars($r['employee_name']) ?></td>
                    <td><?= htmlspecialchars($r['department']) ?></td>
                    <td><?= htmlspecialchars($r['expense_category']) ?></td>
                    <td>₱<?= number_format($r['amount_requested'], 2) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><?= htmlspecialchars($r['date_requested']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="report-section">
            <p><strong>Report Generated:</strong> <?= date('F j, Y \a\t g:i A') ?></p>
            <p><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['user']['name']) ?> (Administrator)</p>
        </div>
    </div>
</body>
</html>
