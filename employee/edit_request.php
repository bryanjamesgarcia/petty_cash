<?php
// ============================================
// employee/edit_request.php
// ============================================
?>
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

// Fetch the request to edit
$stmt = $conn->prepare("SELECT pr.*, rs.status_name as status FROM petty_cash_requests pr 
                        JOIN request_statuses rs ON pr.status_id = rs.id
                        WHERE pr.id = ? AND pr.user_id = ? AND rs.status_name = 'Pending'");
$stmt->execute([$request_id, $user['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount_requested = $_POST['amount_requested'];
    $purpose = $_POST['purpose'];
    $expense_category = $_POST['expense_category'];
    $justification = $_POST['justification'];
    $breakdown = $_POST['breakdown'];

    // Get category_id
    $stmt = $conn->prepare("SELECT id FROM expense_categories WHERE category_name = ?");
    $stmt->execute([$expense_category]);
    $category_id = $stmt->fetchColumn();

    $stmt = $conn->prepare("UPDATE petty_cash_requests SET amount_requested = ?, purpose = ?, category_id = ?, justification = ?, breakdown = ? WHERE id = ?");
    $stmt->execute([$amount_requested, $purpose, $category_id, $justification, $breakdown, $request_id]);

    echo "<script>alert('Request updated successfully!'); window.location.href='dashboard.php';</script>";
}

// Fetch categories
$stmt = $conn->prepare("SELECT category_name FROM expense_categories WHERE is_active = 1 ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get current category name
$stmt = $conn->prepare("SELECT category_name FROM expense_categories WHERE id = ?");
$stmt->execute([$request['category_id']]);
$current_category = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Petty Cash Request</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/employee_styles.css">
</head>
<body>
    <div class="add-request-container">
        <h2>Edit Petty Cash Request</h2>
        <p><strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_number']); ?></p>
        <p><strong>Employee:</strong> <?php echo htmlspecialchars($user['name']); ?> | <strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <form method="POST" class="add-request-form">
            <div>
                <label>Amount Requested (₱):</label>
                <input type="number" step="0.01" name="amount_requested" value="<?php echo htmlspecialchars($request['amount_requested']); ?>" required>
            </div>

            <div>
                <label>Purpose/Description:</label>
                <textarea name="purpose" required><?php echo htmlspecialchars($request['purpose']); ?></textarea>
            </div>

            <div>
                <label>Expense Category:</label>
                <select name="expense_category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($cat == $current_category) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Justification:</label>
                <textarea name="justification" placeholder="Explain why this expense is necessary..." required><?php echo htmlspecialchars($request['justification']); ?></textarea>
            </div>

            <div>
                <label>Detailed Breakdown:</label>
                <textarea name="breakdown" placeholder="Provide a detailed breakdown of the expenses..." required><?php echo htmlspecialchars($request['breakdown']); ?></textarea>
            </div>

            <button type="submit">Update Request</button>
        </form>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
