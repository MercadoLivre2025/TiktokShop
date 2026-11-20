<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Extract Payberry status and transaction ID
$transaction_id = $data['Id'] ?? $data['id'] ?? $data['ExternalId'] ?? $data['external_id'] ?? '';
$status = strtoupper($data['Status'] ?? $data['status'] ?? '');

if (empty($transaction_id)) {
    echo json_encode(['status' => 'ignored', 'message' => 'Missing transaction_id']);
    exit;
}

if ($status === 'PAID' || $status === 'CONFIRMED' || $status === 'APPROVED') {
    try {
        // Update Order
        $stmt = $pdo->prepare("UPDATE orders SET status = 'PAID' WHERE transaction_id = ? OR id = ?");
        $stmt->execute([$transaction_id, (int)$transaction_id]);

        // Update Customer Status
        $stmtCust = $pdo->prepare("UPDATE customers SET status = 'pago' WHERE id IN (SELECT id FROM orders WHERE transaction_id = ? OR id = ?)");
        $stmtCust->execute([$transaction_id, (int)$transaction_id]);

        echo json_encode(['status' => 'success', 'paid' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'received', 'payberry_status' => $status]);
?>
