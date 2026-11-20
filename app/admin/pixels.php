<?php
require_once '../api/db.php';
check_auth();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process text fields
    $fields = [
        'facebook_pixel_id', 'facebook_token',
        'tiktok_pixel_id', 'tiktok_token'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            set_setting($pdo, $field, trim($_POST[$field]));
        }
    }

    $msg = "Pixels de rastreamento atualizados com sucesso!";
    
    // Compile site to apply changes (inject scripts)
    require_once '../api/compiler.php';
    compile_static_site($pdo);
}

// Load current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Helper para pegar valor com default
function val($key, $default = '') {
    global $settings;
    if ($key === 'tiktok_pixel_id' && empty($settings[$key])) return 'D9QVVQ3C77U97D5QE3LG';
    if ($key === 'tiktok_token' && empty($settings[$key])) return 'be35e6cb29d84261ce77c1c5ac40a295004f74f1';
    return htmlspecialchars($settings[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixels e Rastreamento - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Pixels e Rastreamento</h1>
                </div>

                <?php if ($msg): ?>
                    <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6 font-medium border border-green-200">
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    
                    <!-- Facebook Pixel -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-4 border-b pb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.325v21.351C0 23.403.597 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.597 1.323-1.324V1.325C24 .597 23.403 0 22.675 0z"/></svg>
                            <h2 class="text-lg font-bold text-gray-800">Pixel do Facebook</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Facebook Pixel ID</label>
                                <input type="text" name="facebook_pixel_id" placeholder="Ex: 123456789012345" value="<?= val('facebook_pixel_id') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                <p class="text-xs text-gray-500 mt-1">Insira apenas os números do ID do seu Pixel.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Token de Acesso (CAPI)</label>
                                <input type="text" name="facebook_token" placeholder="Ex: EAAB..." value="<?= val('facebook_token') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                <p class="text-xs text-gray-500 mt-1">Opcional. Token para envio de eventos via servidor (Conversions API).</p>
                            </div>
                        </div>
                    </div>

                    <!-- TikTok Pixel -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-4 border-b pb-3">
                            <svg class="w-6 h-6 text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 2.63-1.64 5.22-4.08 6.44-2.22 1.12-4.99 1.09-7.07-.15-2.06-1.22-3.41-3.45-3.53-5.87-.13-2.61 1.25-5.26 3.52-6.52 2.22-1.25 5.08-1.21 7.23.11V14.7c-1.35-.91-3.21-1.07-4.66-.46-1.57.65-2.58 2.37-2.39 4.09.2 1.83 1.89 3.23 3.73 3.27 1.88.04 3.58-1.29 3.91-3.12.12-.66.1-1.34.1-2.01V.02z"/></svg>
                            <h2 class="text-lg font-bold text-gray-800">Pixel do TikTok</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">TikTok Pixel ID</label>
                                <input type="text" name="tiktok_pixel_id" placeholder="Ex: C123ABCD..." value="<?= val('tiktok_pixel_id') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all">
                                <p class="text-xs text-gray-500 mt-1">ID base do TikTok Pixel (alfanumérico).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Token de Acesso (Events API)</label>
                                <input type="text" name="tiktok_token" placeholder="Opcional" value="<?= val('tiktok_token') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all">
                                <p class="text-xs text-gray-500 mt-1">Opcional. Token para envio de eventos via servidor.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 pb-12">
                        <button type="submit" class="bg-black text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-800 transition text-lg shadow-lg">
                            💾 Salvar Pixels e Compilar Site
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</body>
</html>




