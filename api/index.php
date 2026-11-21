<?php
// Front controller: todas as rotas dinamicas passam por aqui (limite de funcoes do plano Hobby da Vercel).
// vercel.json reescreve  /qualquer/caminho  ->  /api/index.php?__p=qualquer/caminho
// Substitui o .htaccess do Apache.

header_remove('X-Powered-By');
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

require_once $app . '/api/config.php';

// Protecao: scanners, rate limit e bloqueio de bots (webhooks de gateways ficam isentos)
require_once $app . '/guard/guard.php';
if (!preg_match('#^api/webhook_[A-Za-z0-9_]+(\.php)?$#', $p)) {
    // Caminhos de scanners/arquivos sensiveis: banem o IP e respondem 404
    if (preg_match('#(^|/)(\.env|\.git|\.htaccess|wp-login\.php|wp-admin|xmlrpc\.php|phpmyadmin|vendor|composer\.(json|lock)|supabase|_legacy_hostinger)(/|$)#i', $p)) {
        Guard::ban();
        not_found();
    }
    Guard::protect();
}

// URLs amigaveis de produto: /produto/12  ou  /produto/12-nome-do-produto
if (preg_match('#^produto/(\d+)(?:-[A-Za-z0-9\-]*)?/?$#', $p, $m)) {
    $_GET['id'] = $_REQUEST['id'] = $m[1];
    ob_start(function ($html) { // links relativos da pagina continuam apontando para a raiz
        return preg_replace('#<head([^>]*)>#i', '<head$1><base href="/">', $html, 1);
    });
    require route_prepare($app, 'produto.php', '/produto.php'); exit;
}

// 0) Painel admin protegido: so abre em /{ADMIN_PATH}/ ; /admin e /admin/* ficam 404
$adminPrefix = ADMIN_PATH;
if ($p === $adminPrefix || strpos($p, $adminPrefix . '/') === 0) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if ($p === $adminPrefix && substr($uriPath, -1) !== '/') {
        header('Location: /' . $adminPrefix . '/', true, 302); exit; // links relativos do painel exigem a barra final
    }
    $rest = trim(substr($p, strlen($adminPrefix)), '/');
    $p = 'admin' . ($rest !== '' ? '/' . $rest : '');
    $isAdminAccess = true;
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store');
} elseif ($p === 'admin' || strpos($p, 'admin/') === 0) {
    not_found();
}

// 1) Rotas "bonitas" (equivalentes ao .htaccess)
if ($p === '' || $p === 'index' || $p === 'index.html' || $p === 'index.php') {
    // Dominio seco: redireciona para a pagina definida no admin (Protecao)
    require_once $app . '/api/db.php';
    $homeRedirect = trim(get_setting($pdo, 'home_redirect_url', ''));
    if ($homeRedirect !== '' && preg_match('#^https?://#i', $homeRedirect)) {
        header('Location: ' . $homeRedirect, true, 302);
        header('Cache-Control: no-store');
        exit;
    }
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
if ($rel === 'admin') $rel = 'admin/index.php';
if (substr($rel, -4) !== '.php' && substr($rel, -1) !== '/') $rel .= '.php';
if (substr($rel, -1) === '/') $rel .= 'index.php';

if (preg_match('#^(admin|api)/[A-Za-z0-9_\-]+\.php$#', $rel)
    && !in_array($rel, $blocked, true)
    && is_file($app . '/' . $rel)) {
    require route_prepare($app, $rel, '/' . $rel); exit;
}

not_found();
