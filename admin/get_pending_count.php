<?php
session_start();
require_once '../classes/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->connect();

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE status = 'Pending'");
    $stmt->execute();
    $pending_requests = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM petty_cash_requests WHERE liquidation_status = 'Pending'");
    $stmt->execute();
    $pending_liquidations = $stmt->fetchColumn();

    $total_pending = $pending_requests + $pending_liquidations;

    header('Content-Type: application/json');
    echo json_encode(['pending_count' => $total_pending]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
