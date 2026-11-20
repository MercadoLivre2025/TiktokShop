<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/compiler.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

if (!$product) {
    // Se ID não foi informado ou não foi encontrado, busca o produto mais recente
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 1");
    $product = $stmt->fetch();
}

if (!$product) {
    header('Location: /loja.html');
    exit;
}

echo render_dynamic_product_page($pdo, $product);
?>
