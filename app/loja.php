<?php
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/compiler.php';

if (file_exists(__DIR__ . '/loja.html')) {
    include __DIR__ . '/loja.html';
} else {
    echo "Página da loja indisponível.";
}
?>
