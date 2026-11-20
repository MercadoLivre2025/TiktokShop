<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
// Cache prevention
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once 'db.php';

// Require admin session (basic check)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ----- LIVE STATS (12-second window for fast, high-precision tracking) -----
$stmt = $pdo->query("SELECT COUNT(*) FROM live_visitors WHERE page='index' AND last_active >= NOW() - INTERVAL 12 SECOND");
$online_index = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM live_visitors WHERE page='checkout' AND last_active >= NOW() - INTERVAL 12 SECOND");
$online_checkout = (int)$stmt->fetchColumn();

// ----- TODAY STATS (Brasília BRT = UTC-3) -----
$stmt = $pdo->query("SELECT COUNT(*) FROM live_visitors WHERE DATE(created_at) = CURDATE()");
$visitors_today = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()");
$checkouts_today = (int)$stmt->fetchColumn();

// ----- RECENT ORDERS -----
$stmt = $pdo->query("SELECT id, name, cpf, phone, email, address, number, neighborhood, city, state, product_name, status, created_at FROM customers ORDER BY created_at DESC LIMIT 15");
$rows = $stmt->fetchAll();

$orders = [];
foreach ($rows as $c) {
    $dt = new DateTime($c['created_at']);
    $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));

    // Build address cleanly — skip empty parts
    $addrParts = [];
    if (!empty(trim($c['address']))) $addrParts[] = trim($c['address']);
    if (!empty(trim($c['number'])))  $addrParts[] = trim($c['number']);
    $street = implode(', ', $addrParts);

    $cityState = '';
    if (!empty(trim($c['city'])) && !empty(trim($c['state']))) {
        $cityState = trim($c['city']) . '/' . trim($c['state']);
    } elseif (!empty(trim($c['city']))) {
        $cityState = trim($c['city']);
    } elseif (!empty(trim($c['state']))) {
        $cityState = trim($c['state']);
    }

    $fullAddress = $street;
    if ($cityState) $fullAddress .= ($fullAddress ? ' - ' : '') . $cityState;

    $orders[] = [
        'id'           => $c['id'],
        'name'         => $c['name'],
        'cpf'          => $c['cpf'],
        'phone'        => $c['phone'],
        'product_name' => $c['product_name'] ?: 'Produto Padrão',
        'address'      => $fullAddress ?: '—',
        'datetime'     => $dt->format('d/m/Y H:i'),
        'status'       => $c['status'] ?: 'pendente',
    ];
}

echo json_encode([
    'online_index'    => $online_index,
    'online_checkout' => $online_checkout,
    'visitors_today'  => $visitors_today,
    'checkouts_today' => $checkouts_today,
    'orders'          => $orders,
    'ts'              => time(),
]);
