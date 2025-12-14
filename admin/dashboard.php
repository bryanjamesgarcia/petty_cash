<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/EmailSender.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$message = '';
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Handle status update for requests
if (isset($_POST['action']) && isset($_POST['id'])) {
    if ($_POST['action'] == 'reject') {
        $status = 'Rejected';
        $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;
        $stmt = $conn->prepare("UPDATE petty_cash_requests SET status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->execute([$status, $rejection_reason, $_POST['id']]);
        $message = "Request rejected successfully.";
    } elseif ($_POST['action'] == 'reject_liquidation') {
        $liquidation_status = 'Rejected';
        $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;
        $stmt = $conn->prepare("UPDATE petty_cash_requests SET liquidation_status = ?, status = 'Liquidated', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$liquidation_status, $rejection_reason, $_POST['id']]);
        $message = "Liquidation rejected successfully.";
    }
    header("Location: dashboard.php?section=view_requests");
    exit();
} elseif (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] == 'approve') {
        $status = 'Approved';
        $stmt = $conn->prepare("UPDATE petty_cash_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $_GET['id']]);
        $message = "Request approved successfully.";
    } elseif ($_GET['action'] == 'approve_liquidation') {
        $liquidation_status = 'Approved';
        $stmt = $conn->prepare("UPDATE petty_cash_requests SET liquidation_status = ?, status = 'Liquidated' WHERE id = ?");
        $stmt->execute([$liquidation_status, $_GET['id']]);
        $message = "Liquidation approved successfully.";
    }
    header("Location: dashboard.php?section=view_requests");
    exit();
}

// Handle adding new employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);

    if (empty($username) || empty($password) || empty($name) || empty($department)) {
        $message = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $message = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department) VALUES (?, ?, 'employee', ?, ?)");
            $stmt->execute([$username, $hashed_password, $name, $department]);
            $message = "Employee added successfully.";
        }
    }
    $section = 'employee_list';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];

    if (empty($new_password)) {
        $message = "New password is required.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        $message = "Password reset successfully.";
    }
    $section = 'employee_list';
}

// Handle email update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email'])) {
    $user_id = $_POST['user_id'];
    $new_email = trim($_POST['new_email']);

    if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Check if email is already used by another user
        if (!empty($new_email)) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Email already in use by another user.";
            } else {
                // Update email and set verified to 0, generate new token
                $verification_token = bin2hex(random_bytes(32));
                $stmt = $conn->prepare("UPDATE users SET email = ?, email_verified = 0, verification_token = ? WHERE id = ?");
                $stmt->execute([$new_email, $verification_token, $user_id]);

                // Send verification email
                $emailSender = new EmailSender();
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $username = $stmt->fetchColumn();
                if ($emailSender->sendEmailVerification($new_email, $username, $verification_token)) {
                    $message = "Email updated successfully. Verification email sent.";
                } else {
                    $message = "Email updated, but failed to send verification email.";
                }
            }
        } else {
            // Remove email
            $stmt = $conn->prepare("UPDATE users SET email = NULL, email_verified = 0, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Email removed successfully.";
        }
    }
    $section = 'employee_list';
}

// Fetch data based on section
if ($section == 'dashboard') {
    // Summary statistics
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'employee'");
    $stmt->execute();
    $total_employees = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests");
    $stmt->execute();
    $total_requests = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE status = 'Pending'");
    $stmt->execute();
    $pending_requests = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE liquidation_status = 'Pending'");
    $stmt->execute();
    $pending_liquidations = $stmt->fetchColumn();
} elseif ($section == 'employee_list') {
    $stmt = $conn->prepare("SELECT id, username, name, department, role, email, email_verified FROM users WHERE role = 'employee' ORDER BY name");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($section == 'view_requests') {
    $stmt = $conn->prepare("SELECT * FROM petty_cash_requests ORDER BY date_requested DESC");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>Admin Panel</h4>
                <p>Petty Cash Management System</p>
            </div>
            <nav class="sidebar-nav">
                <a href="?section=dashboard" class="nav-link <?php if($section=='dashboard') echo 'active'; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?section=employee_list" class="nav-link <?php if($section=='employee_list') echo 'active'; ?>">
                    <i class="fas fa-users"></i> Employee List
                </a>
                <a href="?section=view_requests" class="nav-link <?php if($section=='view_requests') echo 'active'; ?>">
                    <i class="fas fa-list"></i> View Requests
                </a>
                <a href="../printable_reports.php" target="_blank" class="nav-link">
                    <i class="fas fa-print"></i> Print Reports
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/login.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-header">
                <h1>Admin Dashboard</h1>
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span id="pending-count" class="notification-count">0</span>
                </div>
            </div>
            <div class="content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($section == 'dashboard'): ?>
                    <h2>KPI (Key Performance Indicator)</h2>
                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <h5>Total Petty Cash Fund</h5>
                            <p class="kpi-value">₱500,000.00</p>
                        </div>
                        <div class="kpi-card">
                            <h5>Total Requests</h5>
                            <p class="kpi-value"><?= $total_requests ?></p>
                        </div>
                        <div class="kpi-card">
                            <h5>Approved Requests</h5>
                            <p class="kpi-value"><?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE status = 'Approved'");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                            ?></p>
                        </div>
                        <div class="kpi-card">
                            <h5>Pending Requests</h5>
                            <p class="kpi-value"><?= $pending_requests ?></p>
                        </div>
                        <div class="kpi-card">
                            <h5>Rejected Requests</h5>
                            <p class="kpi-value"><?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE status = 'Rejected'");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                            ?></p>
                        </div>
                        <div class="kpi-card">
                            <h5>Liquidations This Month</h5>
                            <p class="kpi-value"><?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE liquidation_status IN ('Approved', 'Rejected') AND MONTH(date_liquidated) = MONTH(CURRENT_DATE()) AND YEAR(date_liquidated) = YEAR(CURRENT_DATE())");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                            ?></p>
                        </div>
                    </div>

                    <h3>Expense Breakdown by Category</h3>
                    <div class="expense-breakdown">
                        <?php
                        $stmt = $conn->prepare("SELECT expense_category, SUM(amount_requested) as total FROM petty_cash_requests WHERE status = 'Approved' GROUP BY expense_category ORDER BY total DESC");
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($categories): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['expense_category']) ?></td>
                                        <td>₱<?= number_format($cat['total'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No approved requests yet.</p>
                        <?php endif; ?>
                    </div>

                <?php elseif ($section == 'employee_list'): ?>
                    <h2>Employee List</h2>

                    <!-- Add New Employee Form -->
                    <h3>Add New Employee</h3>
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Username:</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password:</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Name:</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department:</label>
                                <input type="text" name="department" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="add_employee" class="btn btn-primary mt-3">Add Employee</button>
                    </form>

                    <h3>All Employees</h3>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Email Verified</th>
                                    <th>Password</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['name']) ?></td>
                                    <td><?= htmlspecialchars($emp['username']) ?></td>
                                    <td><?= htmlspecialchars($emp['department']) ?></td>
                                    <td><?= htmlspecialchars($emp['role']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                            <input type="email" name="new_email" value="<?= htmlspecialchars($emp['email'] ?? '') ?>" placeholder="Enter email" class="form-control d-inline-block" style="width: 150px;">
                                            <button type="submit" name="update_email" class="btn btn-sm btn-outline-primary">Update Email</button>
                                        </form>
                                    </td>
                                    <td><?= $emp['email_verified'] ? 'Yes' : 'No' ?></td>
                                    <td>Hashed (Reset Available)</td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                            <input type="password" name="new_password" placeholder="New Password" class="form-control d-inline-block" style="width: 120px;" required>
                                            <button type="submit" name="reset_password" class="btn btn-sm btn-outline-warning">Reset Password</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($section == 'view_requests'): ?>
                    <h2>All Requests</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Purpose</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Liquidation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['request_id']) ?></td>
                                    <td><?= htmlspecialchars($r['employee_name']) ?></td>
                                    <td><?= htmlspecialchars($r['department']) ?></td>
                                    <td>₱<?= number_format($r['amount_requested'], 2) ?></td>
                                    <td><?= htmlspecialchars($r['expense_category']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($r['purpose']) ?>
                                        <?php if ($r['justification']): ?>
                                            <br><small><strong>Justification:</strong> <?= htmlspecialchars(substr($r['justification'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                        <?php if ($r['breakdown']): ?>
                                            <br><small><strong>Breakdown:</strong> <?= htmlspecialchars(substr($r['breakdown'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['date_requested']) ?></td>
                                    <td><?= htmlspecialchars($r['status']) ?></td>
                                    <td>
                                        <?php if ($r['liquidation_status'] != 'Not Submitted'): ?>
                                            <?= htmlspecialchars($r['liquidation_status']) ?>
                                            <?php if ($r['total_spent']): ?>
                                                <br>Total Spent: ₱<?= number_format($r['total_spent'], 2) ?>
                                                <?php
                                                // Fetch expense items
                                                $stmt_expenses = $conn->prepare("SELECT * FROM liquidation_expenses WHERE request_id = ?");
                                                $stmt_expenses->execute([$r['request_id']]);
                                                $expenses = $stmt_expenses->fetchAll(PDO::FETCH_ASSOC);
                                                if ($expenses): ?>
                                                    <br><small>Expenses:</small>
                                                    <?php foreach ($expenses as $exp): ?>
                                                        <br><small>- <?= htmlspecialchars($exp['description']) ?>: ₱<?= number_format($exp['amount'], 2) ?></small>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <?php if ($r['refund_needed'] > 0): ?>
                                                    <br>Refund Needed: ₱<?= number_format($r['refund_needed'], 2) ?>
                                                <?php elseif ($r['reimbursement'] > 0): ?>
                                                    <br>Reimbursement: ₱<?= number_format($r['reimbursement'], 2) ?>
                                                <?php endif; ?>
                                                <?php if ($r['receipts']): ?>
                                                    <br><small>Receipts: <?= htmlspecialchars(substr($r['receipts'], 0, 50)) ?>...</small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] == 'Pending'): ?>
                                            <a href="?section=view_requests&action=approve&id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this request?')">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <textarea name="rejection_reason" placeholder="Rejection reason" class="form-control" required style="width: 150px; height: 40px;"></textarea>
                                                <button type="submit" class="btn btn-sm btn-danger mt-1">Reject</button>
                                            </form>
                                        <?php elseif ($r['liquidation_status'] == 'Pending'): ?>
                                            <a href="?section=view_requests&action=approve_liquidation&id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Approve Liquidation</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this liquidation?')">
                                                <input type="hidden" name="action" value="reject_liquidation">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <textarea name="rejection_reason" placeholder="Rejection reason" class="form-control" required style="width: 150px; height: 40px;"></textarea>
                                                <button type="submit" class="btn btn-sm btn-danger mt-1">Reject Liquidation</button>
                                            </form>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                        <?php if ($r['status'] == 'Rejected' && isset($r['rejection_reason']) && $r['rejection_reason']): ?>
                                            <br><small><strong>Rejection Reason:</strong> <?= htmlspecialchars($r['rejection_reason']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($r['liquidation_status'] == 'Rejected' && isset($r['rejection_reason']) && $r['rejection_reason']): ?>
                                            <br><small><strong>Liquidation Rejection Reason:</strong> <?= htmlspecialchars($r['rejection_reason']) ?></small>
                                        <?php endif; ?>
                                        <br><a href="printable_request.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">Print Request</a>
                                        <?php if ($r['liquidation_status'] != 'Not Submitted'): ?>
                                            <a href="printable_liquidation.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">Print Liquidation</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Function to update pending request count
    function updatePendingCount() {
        fetch('get_pending_count.php')
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('pending-count');
                const count = data.pending_count;
                countElement.textContent = count;
                countElement.style.display = count > 0 ? 'inline' : 'none';
            })
            .catch(error => console.error('Error fetching pending count:', error));
    }

    // Update count on page load
    updatePendingCount();

    // Update count every 30 seconds
    setInterval(updatePendingCount, 30000);
    </script>
</body>
</html>
