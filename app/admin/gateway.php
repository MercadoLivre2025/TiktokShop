<?php
require_once '../api/db.php';
check_auth();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'gateway_mode',
        'payberry_public_key',
        'payberry_secret_key'
    ];

    $saved = 0; $err = '';
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            try {
                set_setting($pdo, $field, trim($_POST[$field]));
                $saved++;
            } catch (Throwable $e) {
                $err = $e->getMessage();
                error_log('gateway save error: ' . $err);
            }
        }
    }

    if ($err !== '') {
        $msg = "ERRO ao salvar no banco: " . $err;
    } elseif ($saved === 0) {
        $msg = "ERRO: nenhum dado chegou ao servidor (POST vazio). Tente novamente.";
    } else {
        $chk = get_setting($pdo, 'payberry_secret_key', '');
        $msg = $chk !== '' || trim($_POST['payberry_secret_key'] ?? '') === ''
            ? "Configurações do Gateway de Pagamento salvas com sucesso!"
            : "ERRO: a chave secreta não foi gravada no banco.";
    }
    
    // Clear cache if needed
}

// Load current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function val($key, $default = '') {
    global $settings;
    if ($key === 'payberry_public_key' && empty($settings[$key])) return PAYBERRY_PUBLIC_KEY;
    if ($key === 'payberry_secret_key' && empty($settings[$key])) return PAYBERRY_SECRET_KEY;
    if ($key === 'gateway_mode' && empty($settings[$key])) return 'payberry';
    return htmlspecialchars($settings[$key] ?? $default);
}

$host = $_SERVER['HTTP_HOST'] ?? 'ultimateshop.online';
$webhookUrl = 'https://' . $host . '/api/webhook_payberry.php';
$gatewayMode = val('gateway_mode', 'payberry');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração do Gateway - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50">
        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <!-- Header -->
                <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <span>💳 Gateway de Pagamento (Payberry)</span>
                        </h1>
                        <p class="text-xs text-gray-500 mt-1">Configure as chaves da API Payberry (https://www.payberry.com.br/documentacao.html) para gerar os PIX de forma automatizada.</p>
                    </div>
                    <a href="https://www.payberry.com.br/documentacao.html" target="_blank" class="text-xs bg-pink-50 hover:bg-pink-100 text-[#d10f72] font-bold px-3 py-2 rounded-lg border border-pink-200 flex items-center gap-1.5 transition-all">
                        📖 Ver Documentação Payberry ↗
                    </a>
                </div>

                <?php if ($msg): ?>
                    <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl font-medium border border-emerald-200 flex items-center gap-2">
                        <span>✅</span>
                        <span><?= htmlspecialchars($msg) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    
                    <!-- Gateway Selection Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <h2 class="text-base font-bold text-gray-900 border-b pb-3 flex items-center gap-2">
                            <span>⚙️ Modo de Processamento do PIX</span>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="border-2 rounded-xl p-4 cursor-pointer transition-all flex items-start gap-3 <?= $gatewayMode === 'payberry' ? 'border-[#d10f72] bg-pink-50/40' : 'border-gray-200 hover:border-gray-300' ?>">
                                <input type="radio" name="gateway_mode" value="payberry" <?= $gatewayMode === 'payberry' ? 'checked' : '' ?> class="mt-1 text-[#d10f72] focus:ring-[#d10f72]">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm">Payberry Gateway (API Oficial)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Conecta à API Payberry (v1), envia cobranças e recebe confirmações automáticas por Webhook.</p>
                                </div>
                            </label>

                            <label class="border-2 rounded-xl p-4 cursor-pointer transition-all flex items-start gap-3 <?= $gatewayMode === 'native' ? 'border-blue-600 bg-blue-50/40' : 'border-gray-200 hover:border-gray-300' ?>">
                                <input type="radio" name="gateway_mode" value="native" <?= $gatewayMode === 'native' ? 'checked' : '' ?> class="mt-1 text-blue-600 focus:ring-blue-600">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm">Modo Direto Nativo (BACEN EMVCo)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Gera o payload PIX nativo instantâneo sem depender de API externa.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Payberry API Credentials Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <div class="flex items-center justify-between border-b pb-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[#d10f72] text-lg">🍓</span>
                                <h2 class="text-base font-bold text-gray-900">Credenciais API Payberry (https://www.payberry.com.br/documentacao.html)</h2>
                            </div>
                            <span class="text-[11px] font-bold bg-pink-50 text-[#d10f72] border border-pink-200 px-2.5 py-1 rounded-full">Basic Authentication</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Chave Pública (Public Key / Username)</label>
                                <input type="text" name="payberry_public_key" placeholder="pk_live_..." value="<?= val('payberry_public_key') ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-[#d10f72] focus:border-[#d10f72] outline-none transition-all">
                                <p class="text-[11px] text-gray-500 mt-1">Obtenha no painel Payberry (Dashboard -> Credenciais de API).</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Chave Secreta (Secret Key / Password)</label>
                                <input type="password" name="payberry_secret_key" placeholder="sk_live_..." value="<?= val('payberry_secret_key') ?>" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-[#d10f72] focus:border-[#d10f72] outline-none transition-all">
                                <p class="text-[11px] text-gray-500 mt-1">Sua secret key da Payberry para autenticação nos endpoints de pagamentos.</p>
                            </div>
                        </div>

                        <!-- Webhook URL Display -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">URL do Webhook (Postback URL)</label>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly id="webhookUrlInput" value="<?= htmlspecialchars($webhookUrl) ?>" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-700 outline-none">
                                <button type="button" onclick="copyWebhookUrl()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs px-4 py-2.5 rounded-xl border border-gray-300 transition-all">
                                    📋 Copiar URL
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-1">Cadastre essa URL no painel da Payberry para receber notificações de pagamentos em tempo real.</p>
                        </div>
                    </div>

                    <!-- Integration Specs / Documentation Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-200 space-y-3">
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>📌 Especificações Técnicas da Integração Payberry API</span>
                        </h3>
                        <div class="bg-slate-900 text-slate-200 p-4 rounded-xl text-xs font-mono overflow-x-auto space-y-2">
                            <div><span class="text-blue-400 font-bold">POST</span> <span class="text-emerald-400">https://api.payberry.com.br/v1/payment-transaction/create</span></div>
                            <div class="text-slate-400">// Headers: Authorization: Basic Base64(pk_live:sk_live) | Content-Type: application/json</div>
                            <div class="text-slate-300">
{
  "amount": 19900,
  "payment_method": "pix",
  "postback_url": "<?= htmlspecialchars($webhookUrl) ?>",
  "customer": { "name": "...", "email": "...", "document": { "number": "...", "type": "cpf" }, "phone": "..." },
  "items": [{ "title": "Produto", "unit_price": 19900, "quantity": 1, "tangible": true }],
  "pix": { "expires_in_days": 1 }
}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 pb-12">
                        <button type="submit" class="bg-[#d10f72] hover:bg-[#a10c58] text-white px-8 py-3 rounded-xl font-extrabold transition-all text-base shadow-md cursor-pointer flex items-center gap-2">
                            <span>💾</span>
                            <span>Salvar Configurações do Gateway</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>

    <script>
    function copyWebhookUrl() {
        const input = document.getElementById('webhookUrlInput');
        input.select();
        navigator.clipboard.writeText(input.value).then(() => {
            alert('URL do Webhook copiada com sucesso!');
        });
    }
    </script>
</body>
</html>
