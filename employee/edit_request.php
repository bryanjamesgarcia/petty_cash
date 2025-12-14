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

// Fetch the request to edit
$stmt = $conn->prepare("SELECT * FROM petty_cash_requests WHERE id = ? AND user_id = ? AND status = 'Pending'");
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

    $stmt = $conn->prepare("UPDATE petty_cash_requests SET amount_requested = ?, purpose = ?, expense_category = ?, justification = ?, breakdown = ? WHERE id = ?");
    $stmt->execute([$amount_requested, $purpose, $expense_category, $justification, $breakdown, $request_id]);

    echo "<script>alert('Request updated successfully!'); window.location.href='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Petty Cash Request</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Petty Cash Request</h2>
        <p><strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?></p>
        <p><strong>Employee:</strong> <?php echo htmlspecialchars($user['name']); ?> | <strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <form method="POST">
            <label>Amount Requested (₱):</label>
            <input type="number" step="0.01" name="amount_requested" value="<?php echo htmlspecialchars($request['amount_requested']); ?>" required>

            <label>Purpose/Description:</label>
            <textarea name="purpose" required><?php echo htmlspecialchars($request['purpose']); ?></textarea>

            <label>Expense Category:</label>
            <select name="expense_category" required>
                <option value="">Select Category</option>
                <option value="Office Supplies" <?php if ($request['expense_category'] == 'Office Supplies') echo 'selected'; ?>>Office Supplies</option>
                <option value="Travel" <?php if ($request['expense_category'] == 'Travel') echo 'selected'; ?>>Travel</option>
                <option value="Meals" <?php if ($request['expense_category'] == 'Meals') echo 'selected'; ?>>Meals</option>
                <option value="Transportation" <?php if ($request['expense_category'] == 'Transportation') echo 'selected'; ?>>Transportation</option>
                <option value="Miscellaneous" <?php if ($request['expense_category'] == 'Miscellaneous') echo 'selected'; ?>>Miscellaneous</option>
            </select>

            <label>Justification:</label>
            <textarea name="justification" placeholder="Explain why this expense is necessary..." required><?php echo htmlspecialchars($request['justification']); ?></textarea>

            <label>Detailed Breakdown:</label>
            <textarea name="breakdown" placeholder="Provide a detailed breakdown of the expenses..." required><?php echo htmlspecialchars($request['breakdown']); ?></textarea>

            <button type="submit">Update Request</button>
        </form>

        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
