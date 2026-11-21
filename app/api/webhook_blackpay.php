<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');

// Validate HMAC-SHA256 signature with the webhook secret (whsec_...)
$secret = trim(get_setting($pdo, 'blackpay_webhook_secret', ''));
if ($secret === '') {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Webhook secret not configured']);
    exit;
}
$expected = hash_hmac('sha256', $rawInput, $secret);
$got = $_SERVER['HTTP_X_BLACKPAY_SIGNATURE'] ?? '';
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
$data = $evt['data'] ?? [];

if ($event === 'payment.paid') {
    $transaction_id = $data['id'] ?? '';
    $externalRef = (int)($data['externalRef'] ?? 0);
    $status = strtolower($data['status'] ?? '');

    if ($transaction_id === '' || ($status !== '' && $status !== 'paid')) {
        echo json_encode(['status' => 'ignored']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'PAID' WHERE transaction_id = ? OR (? > 0 AND id = ?)");
        $stmt->execute([$transaction_id, $externalRef, $externalRef]);

        $stmtCust = $pdo->prepare("UPDATE customers SET status = 'pago' WHERE id IN (SELECT id FROM orders WHERE transaction_id = ? OR (? > 0 AND id = ?))");
        $stmtCust->execute([$transaction_id, $externalRef, $externalRef]);

        echo json_encode(['status' => 'success', 'paid' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'received', 'event' => $event]);
