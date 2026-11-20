<?php
require_once __DIR__ . '/../api/db.php';
session_start();
session_destroy();
header('Location: /admin/login.php');
exit;
?>



