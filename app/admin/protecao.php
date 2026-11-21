<?php
require_once '../api/db.php';
check_auth();

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['home_redirect_url'] ?? '');
    if ($url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) {
        $err = 'URL inválida. Use o formato https://exemplo.com/pagina (ou deixe vazio para desativar).';
    } else {
        set_setting($pdo, 'home_redirect_url', $url);
        $msg = $url === '' ? 'Redirecionamento desativado.' : 'Redirecionamento salvo com sucesso!';
    }
}

$current = get_setting($pdo, 'home_redirect_url', '');
$host = $_SERVER['HTTP_HOST'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proteção do Domínio - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-3xl mx-auto space-y-6">
                <div class="pb-4 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-900">🛡️ Proteção do Domínio</h1>
                    <p class="text-xs text-gray-500 mt-1">Quem abrir o domínio puro é enviado para outra página. As páginas de produto continuam funcionando pelos links diretos.</p>
                </div>

                <?php if ($msg): ?><div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl font-medium border border-emerald-200">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <?php if ($err): ?><div class="bg-red-50 text-red-800 p-4 rounded-xl font-medium border border-red-200">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

                <form method="POST" class="bg-white p-6 rounded-2xl border border-gray-200 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Redirecionar <?= htmlspecialchars($host) ?>/ para</label>
                        <input type="text" name="home_redirect_url" value="<?= htmlspecialchars($current) ?>" placeholder="https://exemplo.com/pagina" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-mono outline-none">
                        <p class="text-[11px] text-gray-500 mt-1">Deixe vazio para desativar (a home volta a mostrar a loja).</p>
                    </div>
                    <button type="submit" class="bg-[#fe2c55] hover:bg-[#e11d48] text-white px-6 py-2.5 rounded-xl font-bold text-sm">💾 Salvar</button>
                </form>

                <div class="bg-white p-6 rounded-2xl border border-gray-200 space-y-2">
                    <h2 class="text-sm font-bold text-gray-900">🔒 Endereço secreto do painel</h2>
                    <p class="text-sm font-mono text-gray-800 break-all">https://<?= htmlspecialchars($host) ?>/<?= htmlspecialchars(ADMIN_PATH) ?>/</p>
                    <p class="text-[11px] text-gray-500">O endereço <code>/admin</code> retorna 404. Para trocar o código, defina a variável <code>ADMIN_PATH</code> na Vercel (Settings → Environment Variables) e faça um redeploy.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>