<?php
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/compiler.php';

$config = get_shop_config($pdo);
echo "window.ShopConfig = " . json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";";
