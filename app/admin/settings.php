<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../api/db.php';
check_auth();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process text fields
        $fields = [
            'store_name', 'store_phone', 'store_whatsapp', 'store_email',
            'store_instagram', 'store_facebook', 'store_tiktok',
            'store_response', 'store_shipping', 'store_sales',
            'scarcity_timer_minutes', 'scarcity_timer_title'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                set_setting($pdo, $field, $_POST[$field]);
            }
        }

        // Process file uploads
        require_once __DIR__ . '/../api/storage.php';

        $upload_failed = '';
        $image_fields = ['store_logo', 'store_profile', 'store_cover', 'store_banner'];
        
        foreach ($image_fields as $img_field) {
            if (isset($_FILES[$img_field]) && $_FILES[$img_field]['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES[$img_field]['tmp_name'];
                $name = basename($_FILES[$img_field]['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (empty($ext)) $ext = 'jpg';
                $new_name = uniqid($img_field . '_') . '.' . $ext;
                
                $mime = function_exists('mime_content_type') ? (mime_content_type($tmp_name) ?: 'application/octet-stream') : 'application/octet-stream';
                $file_url = storage_upload($tmp_name, $new_name, $mime);
                if (!$file_url) { $upload_failed = $GLOBALS['STORAGE_LAST_ERROR'] ?? 'falha no upload'; }
                if ($file_url) {
                    set_setting($pdo, $img_field, $file_url);
                    if ($img_field === 'store_logo' || $img_field === 'store_profile') {
                        set_setting($pdo, 'store_logo', $file_url);
                        set_setting($pdo, 'store_profile', $file_url);
                    }
                }
            }
        }


        $msg = "Configurações e foto de perfil salvas com sucesso!";
        if ($upload_failed) { $msg = "ATENÇÃO: a imagem NÃO foi enviada (" . $upload_failed . "). Os outros dados foram salvos."; }
        
        // Compile site to apply changes
        if (file_exists('../api/compiler.php')) {
            require_once '../api/compiler.php';
            @compile_static_site($pdo);
        }
    } catch (Exception $e) {
        $msg = "Configurações salvas!";
    }
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
    return htmlspecialchars($settings[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Gerais - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto space-y-6">
                <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Configurações Gerais</h1>
                        <p class="text-xs text-gray-500 mt-1">Gerencie a identidade e métricas exibidas na loja</p>
                    </div>
                </div>

                <?php if ($msg): ?>
                    <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl font-semibold border border-emerald-200 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span><?= htmlspecialchars($msg) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- Informações Básicas (Apenas Nome da Loja) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <h2 class="text-base font-bold text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Informações Básicas
                        </h2>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nome da Loja</label>
                            <input type="text" name="store_name" value="<?= val('store_name', 'Minha Loja') ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none">
                        </div>
                    </div>

                    <!-- Identidade Visual (Logotipo e Perfil) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <h2 class="text-base font-bold text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Identidade Visual (Uploads)
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Logo Principal -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <label class="block text-sm font-bold text-gray-800 mb-1">Logotipo Principal</label>
                                    <p class="text-xs text-gray-500 mb-3">Exibido no topo da loja e cabeçalho</p>
                                    <?php if(val('store_logo')): ?>
                                        <div class="mb-3 bg-white p-2 rounded-lg border border-gray-200 flex items-center justify-center">
                                            <img src="<?= val('store_logo') ?>" class="h-10 object-contain">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="store_logo" accept="image/*" class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                            </div>

                            <!-- Foto de Perfil (Avatar) -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <label class="block text-sm font-bold text-gray-800 mb-1">Foto de Perfil (Avatar)</label>
                                    <p class="text-xs text-gray-500 mb-3">Exibido na página do produto e perfil da loja</p>
                                    <?php if(val('store_profile')): ?>
                                        <div class="mb-3 flex items-center justify-center">
                                            <img src="<?= val('store_profile') ?>" class="w-12 h-12 object-cover rounded-full border-2 border-pink-500 shadow-sm">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="store_profile" accept="image/*" class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                            </div>

                        </div>
                    </div>

                    <!-- Métricas da Loja -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <h2 class="text-base font-bold text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            Métricas da Loja (Perfil)
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Total de Vendas (Ex: 12.4K, 618)</label>
                                <input type="text" name="store_sales" value="<?= val('store_sales', '128.4K') ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Taxa de Resposta (Ex: 98%)</label>
                                <input type="text" name="store_response" value="<?= val('store_response', '98%') ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Envios Pontuais (Ex: 95%)</label>
                                <input type="text" name="store_shipping" value="<?= val('store_shipping', '95%') ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 pb-12">
                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-8 py-3.5 rounded-xl font-bold transition text-sm shadow-lg shadow-pink-500/25 active:scale-95 flex items-center gap-2">
                            <span>💾 Salvar Configurações</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</body>
</html>
