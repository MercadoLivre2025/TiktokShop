<?php
require_once __DIR__ . '/api/db.php';

if (file_exists(__DIR__ . '/loja.html')) {
    include __DIR__ . '/loja.html';
} else {
    echo "Loja sem produtos cadastrados.";
}
