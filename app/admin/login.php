<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once '../api/db.php';

ensure_admin_user($pdo); // cria o admin na 1a execucao (senha = ADMIN_PASSWORD)

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; position: relative; }
body::before { content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-image: url('tiktok_bg.jpg?v=3'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.25; z-index: -1; pointer-events: none; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm border border-gray-100">
        
        <div class="text-center mb-6 flex flex-col items-center">
            <img src="tiktok_bg.jpg?v=3" alt="Logo" class="h-16 object-contain mb-2">
            <p class="text-gray-500 text-sm font-medium">Faça login para gerenciar sua loja</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 text-center border border-red-100">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuário</label>
                <input type="text" name="username" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-black text-white font-bold rounded-lg px-4 py-3 hover:bg-gray-800 transition shadow-md">
                Entrar no Sistema
            </button>
        </form>
    </div>
</body>
</html>
