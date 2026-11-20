<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/compiler.php';

try {
    // 1. Limpar todos os produtos do Banco de Dados
    $pdo->exec("DELETE FROM products");
    
    // 2. Limpar todas as fotos de produtos na pasta uploads (mantendo logos da loja)
    $upload_dir = dirname(__DIR__) . '/uploads/';
    if (is_dir($upload_dir)) {
        $files = glob($upload_dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && !preg_match('/\/store_/i', $file)) {
                @unlink($file);
            }
        }
    }

    // 3. Limpar páginas estáticas e caches
    $baseDir = dirname(__DIR__);
    foreach (glob($baseDir . '/p_*.html') as $f) { @unlink($f); }
    foreach (glob($baseDir . '/produto-*.html') as $f) { @unlink($f); }
    foreach ([$baseDir . '/_index_tmp.html', $baseDir . '/index.html.tmp', $baseDir . '/scratch_compiler.txt'] as $f) {
        if (file_exists($f)) { @unlink($f); }
    }

    // 4. Recompilar o site limpo sem produtos
    compile_static_site($pdo);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Todos os produtos, fotos e caches foram removidos com sucesso!'
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao limpar produtos: ' . $e->getMessage()
    ]);
}
?>
