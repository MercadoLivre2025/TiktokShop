<?php
header('Content-Type: text/html; charset=utf-8');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_data = null;
if (file_exists(__DIR__ . '/api/db.php')) {
    require_once __DIR__ . '/api/db.php';
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {}

    if ($product_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
}
?>
<script>
window.ServerProductData = <?php echo json_encode($product_data); ?>;
</script>
<?php
include __DIR__ . '/pagamento.html';
?>
