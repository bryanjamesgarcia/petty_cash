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

// Check if the request is pending and belongs to the user
$stmt = $conn->prepare("SELECT * FROM petty_cash_requests WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->execute([$request_id, $user['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header("Location: dashboard.php");
    exit();
}

// Delete the request
$stmt = $conn->prepare("DELETE FROM petty_cash_requests WHERE id = ?");
$stmt->execute([$request_id]);

echo "<script>alert('Request deleted successfully!'); window.location.href='dashboard.php';</script>";
?>
