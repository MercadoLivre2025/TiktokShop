<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once 'db.php';

$session_id = trim($_GET['session_id'] ?? '');
$page_raw   = strtolower(trim($_GET['page'] ?? 'index'));
$action     = strtolower(trim($_GET['action'] ?? 'ping'));

if (empty($session_id)) {
    echo json_encode(['error' => 'Missing session_id']);
    exit;
}

// Normalize page parameter: 'checkout' vs 'index'
if (strpos($page_raw, 'check') !== false || strpos($page_raw, 'pag') !== false) {
    $page = 'checkout';
} else {
    $page = 'index';
}

try {
    if ($action === 'leave') {
        // Set last_active to 1 hour ago so live online count drops immediately, while preserving daily visitor history
        $stmt = $pdo->prepare("UPDATE live_visitors SET last_active = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE session_id = ?");
        $stmt->execute([$session_id]);
        echo json_encode(['success' => true, 'action' => 'left']);
    } else {
        // Insert or update live visitor ping — keep last_active fresh & refresh created_at if returning on a new day
        $stmt = $pdo->prepare("
            INSERT INTO live_visitors (session_id, page, last_active, created_at)
            VALUES (?, ?, NOW(), NOW())
            ON CONFLICT (session_id) DO UPDATE SET
                page = EXCLUDED.page,
                last_active = NOW(),
                created_at = CASE WHEN DATE(live_visitors.created_at) < CURDATE() THEN NOW() ELSE live_visitors.created_at END
        ");
        $stmt->execute([$session_id, $page]);
        echo json_encode(['success' => true, 'action' => 'pinged', 'page' => $page]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

