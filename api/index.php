<?php
// Front controller: todas as rotas dinamicas passam por aqui (limite de funcoes do plano Hobby da Vercel).
// vercel.json reescreve  /qualquer/caminho  ->  /api/index.php?__p=qualquer/caminho
// Substitui o .htaccess do Apache.

$app = dirname(__DIR__) . '/app';
$p = trim((string)($_GET['__p'] ?? ''), '/');
unset($_GET['__p']);
$_REQUEST = $_GET + $_POST;
$_SERVER['QUERY_STRING'] = http_build_query($_GET);

function route_prepare(string $app, string $rel, string $uri) {
    $target = $app . '/' . $rel;
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $uri;
    chdir(dirname($target));
    return $target;
}

function not_found() {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>404</h1>';
    exit;
}

// Seguranca: caminhos invalidos
if (strpos($p, '..') !== false || strpos($p, "\0") !== false) not_found();

// 1) Rotas "bonitas" (equivalentes ao .htaccess)
if ($p === '' || $p === 'index' || $p === 'index.html' || $p === 'index.php') {
    require route_prepare($app, 'index.php', '/index.php'); exit;
}
if ($p === 'data/config.js') {
    require route_prepare($app, 'api/config_js.php', '/api/config_js.php'); exit;
}
if ($p === 'data/products.json') {
    require $app . '/api/db.php';
    require $app . '/api/compiler.php';
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    $cfg = get_shop_config($pdo);
    echo json_encode($cfg['store_products'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if (preg_match('#^(?:produto-|produto_|p_)(\d+)(?:\.html)?$#', $p, $m)) {
    $_GET['id'] = $_REQUEST['id'] = $m[1];
    require route_prepare($app, 'produto.php', '/produto.php'); exit;
}
if (preg_match('#^produto_[A-Za-z0-9_-]+(?:\.html)?$#', $p)) {
    require route_prepare($app, 'produto.php', '/produto.php'); exit;
}
if (preg_match('#^loja(?:\.html|\.php)?$#', $p)) {
    require route_prepare($app, 'loja.php', '/loja.php'); exit;
}
if (preg_match('#^pagamento(?:\.html|\.php)?/?$#', $p)) {
    require route_prepare($app, 'pagamento.php', '/pagamento.php'); exit;
}

// 2) Painel admin e API: apenas arquivos .php existentes (sem bibliotecas internas)
$blocked = [
    'api/db.php', 'api/config.php', 'api/compiler.php', 'api/storage.php',
    'api/clear_products.php', 'admin/sidebar.php',
];
$rel = $p;
if ($rel === 'admin' || substr($rel, -6) === '/admin') $rel = 'admin/index.php';
if (substr($rel, -4) !== '.php') $rel .= '.php';

if (preg_match('#^(admin|api)/[A-Za-z0-9_\-]+\.php$#', $rel)
    && !in_array($rel, $blocked, true)
    && is_file($app . '/' . $rel)) {
    require route_prepare($app, $rel, '/' . $rel); exit;
}

not_found();
