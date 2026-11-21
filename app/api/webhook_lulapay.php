<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');

// Signature: "sha256=" + HMAC-SHA256(raw body, account token gwk_...)
$token = trim(get_setting($pdo, 'lulapay_token', ''));
if ($token === '') {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Token not configured']);
    exit;
}
$expected = 'sha256=' . hash_hmac('sha256', $rawInput, $token);
$got = $_SERVER['HTTP_X_LULAPAY_SIGNATURE'] ?? '';
if (!hash_equals($expected, $got)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

$evt = json_decode($rawInput, true);
if (!is_array($evt)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$event = $evt['event'] ?? '';
$charge = $evt['charge'] ?? [];

if ($event === 'pix.paid' && ($charge['status'] ?? '') === 'paid') {
    $transaction_id = $charge['id'] ?? '';
    $orderId = 0;
    if (preg_match('/^pedido-(\d+)-\d+$/', $charge['external_id'] ?? '', $m)) $orderId = (int)$m[1];

    if ($transaction_id === '' && !$orderId) {
        echo json_encode(['status' => 'ignored']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'PAID' WHERE transaction_id = ? OR (? > 0 AND id = ?)");
        $stmt->execute([$transaction_id, $orderId, $orderId]);

        $stmtCust = $pdo->prepare("UPDATE customers SET status = 'pago' WHERE id IN (SELECT id FROM orders WHERE transaction_id = ? OR (? > 0 AND id = ?))");
        $stmtCust->execute([$transaction_id, $orderId, $orderId]);

        echo json_encode(['status' => 'success', 'paid' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'received', 'event' => $event]);