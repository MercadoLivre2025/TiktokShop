<?php
require_once '../api/db.php';
require_once '../api/compiler.php';

$url_or_id = trim($_POST['ml_url'] ?? $_GET['ml_url'] ?? '');
$do_save = !empty($_POST['save_to_catalog']);

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
    || isset($_GET['json']) 
    || isset($_POST['json']);

$scraped = null;
$error_msg = '';
$success_msg = '';

if (!empty($url_or_id)) {
    $target_url = '';
    $ml_id = '';

    // Extract MLB ID if present
    if (preg_match('/MLB-?\d+/i', $url_or_id, $m)) {
        $ml_id = str_replace('-', '', strtoupper($m[0]));
    }

    if (strpos($url_or_id, 'http://') === 0 || strpos($url_or_id, 'https://') === 0) {
        $target_url = $url_or_id;
    } else {
        if ($ml_id) {
            $target_url = "https://produto.mercadolivre.com.br/MLB-{$ml_id}";
        }
    }

    $title = '';
    $price = 0.00;
    $original_price = 0.00;
    $description = '';
    $brand = '';
    $category = '';
    $images = [];

    // Strategy 1: cURL the full HTML page (preserves user URL exactly)
    if ($target_url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache'
        ]);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($html && strlen($html) > 500) {
            // A. Extract JSON-LD
            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $ld_matches)) {
                foreach ($ld_matches[1] as $json_raw) {
                    $json_data = json_decode($json_raw, true);
                    if ($json_data) {
                        if (isset($json_data['name']) && !$title) {
                            $title = trim($json_data['name']);
                        }
                        if (isset($json_data['description']) && !$description) {
                            $description = trim($json_data['description']);
                        }
                        if (isset($json_data['brand']['name']) && !$brand) {
                            $brand = trim($json_data['brand']['name']);
                        }
                        if (isset($json_data['image'])) {
                            $img_list = is_array($json_data['image']) ? $json_data['image'] : [$json_data['image']];
                            foreach ($img_list as $img_item) {
                                if (is_string($img_item)) $images[] = $img_item;
                                elseif (isset($img_item['url'])) $images[] = $img_item['url'];
                            }
                        }
                        if (isset($json_data['offers'])) {
                            $offer = is_array($json_data['offers']) && isset($json_data['offers'][0]) ? $json_data['offers'][0] : $json_data['offers'];
                            if (isset($offer['price'])) {
                                $price = (float)$offer['price'];
                            }
                            if (isset($offer['lowPrice'])) {
                                $price = (float)$offer['lowPrice'];
                            }
                        }
                    }
                }
            }

            // B. Extract OpenGraph Meta Tags
            if (!$title && preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\'](.*?)["\']/is', $html, $m)) {
                $title = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
            }
            if (!$title && preg_match('/<h1[^>]*class=["\'][^"\']*(?:ui-pdp-title|ui-vpp-title)[^"\']*["\'][^>]*>(.*?)<\/h1>/is', $html, $m)) {
                $title = trim(strip_tags($m[1]));
            }
            if (!$title && preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
                $title = trim(str_replace(['| MercadoLivre', '| Mercado Livre'], '', $m[1]));
            }

            // C. Extract Price
            if (!$price && preg_match('/<meta[^>]*property=["\']og:price:amount["\'][^>]*content=["\'](.*?)["\']/is', $html, $m)) {
                $price = (float)$m[1];
            }
            if (!$price && preg_match('/class=["\'][^"\']*andes-money-amount__fraction[^"\']*["\'][^>]*>(\d+[\d\.]*)/i', $html, $m)) {
                $price = (float)str_replace('.', '', $m[1]);
                if (preg_match('/class=["\'][^"\']*andes-money-amount__cents[^"\']*["\'][^>]*>(\d+)/i', $html, $cm)) {
                    $price += (float)($cm[1] / 100);
                }
            }

            // D. Extract Images
            preg_match_all('/https:\/\/http2\.mlstatic\.com\/D_NQ_NP_[^"\'\s\)]+/i', $html, $img_matches);
            if (!empty($img_matches[0])) {
                foreach ($img_matches[0] as $raw_img) {
                    $high_res = preg_replace('/-[A-Z]\.(webp|jpg|png|jpeg)$/i', '-O.jpg', $raw_img);
                    $high_res = preg_replace('/-[A-Z]\.webp$/i', '-O.jpg', $high_res);
                    if (!in_array($high_res, $images) && strpos($high_res, 'D_NQ_NP_') !== false) {
                        $images[] = $high_res;
                    }
                }
            }

            // E. Extract Description
            if (!$description && preg_match('/<div[^>]*class=["\'][^"\']*(?:ui-pdp-description__content|ui-vpp-description__content)[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
                $description = trim(strip_tags($m[1], '<br><p><ul><li><b><strong>'));
            }
        }
    }

    // Strategy 2: Mercado Livre API Fallbacks (Products Catalog API & Items API)
    if ((!$title || !$price) && $ml_id) {
        $endpoints = [
            "https://api.mercadolibre.com/products/{$ml_id}",
            "https://api.mercadolibre.com/items/{$ml_id}"
        ];
        foreach ($endpoints as $ep) {
            $api_json = @file_get_contents($ep);
            if ($api_json) {
                $api_data = json_decode($api_json, true);
                if ($api_data) {
                    if (!$title && !empty($api_data['name'])) $title = $api_data['name'];
                    if (!$title && !empty($api_data['title'])) $title = $api_data['title'];
                    
                    if (!$price && isset($api_data['buy_box_winner']['price'])) {
                        $price = (float)$api_data['buy_box_winner']['price'];
                    }
                    if (!$price && isset($api_data['price'])) {
                        $price = (float)$api_data['price'];
                    }

                    if (empty($images) && !empty($api_data['pictures'])) {
                        foreach ($api_data['pictures'] as $p) {
                            $images[] = str_replace('http://', 'https://', $p['secure_url'] ?? $p['url']);
                        }
                    }

                    if (!$description && isset($api_data['short_description']['content'])) {
                        $description = $api_data['short_description']['content'];
                    }
                }
            }
        }
    }

    // Format & Return Output
    $title = trim(str_replace([' - Mercado Livre', ' - MercadoLivre'], '', $title));
    $title = preg_replace('/\s+/', ' ', $title);
    $images = array_values(array_unique(array_slice($images, 0, 10)));

    if ($price > 0 && !$original_price) {
        $original_price = round($price * 1.35, 2);
    }

    if ($title && $price > 0) {
        $scraped = [
            'success' => true,
            'ml_id' => $ml_id ?: 'MLB' . rand(10000000, 99999999),
            'title' => $title,
            'price' => number_format($price, 2, ',', '.'),
            'original_price' => number_format($original_price, 2, ',', '.'),
            'price_float' => $price,
            'original_price_float' => $original_price,
            'images' => !empty($images) ? $images : ['https://http2.mlstatic.com/D_NQ_NP_2X_613942-MLA47854619717_102021-F.jpg'],
            'brand' => $brand ?: 'Principia',
            'category' => $category ?: 'Cuidados com a Pele',
            'description' => $description ?: "Kit oficial de cuidados com a pele com fórmula avançada. Acompanha garantia e entrega rápida.",
            'stock' => 999,
            'sales_quantity' => rand(300, 3500)
        ];
    } else {
        $error_msg = 'Não foi possível extrair os dados deste produto automaticamente por link direto. Experimente abrir o produto no Mercado Livre, pressionar Ctrl+U (código fonte), copiar e colar na caixa.';
    }
}

// Return JSON if AJAX API call
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    if ($scraped) {
        echo json_encode($scraped);
    } else {
        echo json_encode(['error' => $error_msg ?: 'Erro ao importar produto.']);
    }
    exit;
}

// Redirect direct browser navigation to products page
header('Location: products.php');
exit;
if ($do_save && !empty($_POST['title']) && !empty($_POST['price'])) {
    check_auth();
    $title = trim($_POST['title']);
    $price = trim($_POST['price']);
    $original_price = trim($_POST['original_price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'Geral');
    $brand = trim($_POST['brand'] ?? 'Importado');
    $stock = (int)($_POST['stock'] ?? 999);
    $sales_quantity = (int)($_POST['sales_quantity'] ?? 1500);
    $images_json = $_POST['images_json'] ?? '[]';

    // Calculate discount percent badge
    $discount_percent = '';
    $p_float = (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $price));
    $op_float = (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $original_price));
    if ($op_float > $p_float && $op_float > 0) {
        $pct = round((($op_float - $p_float) / $op_float) * 100);
        $discount_percent = '-' . $pct . '%';
    }

    $stmt = $pdo->prepare("INSERT INTO products (title, price, discount_price, discount_percent, description, category, brand, stock, sales_quantity, image, variations, technical_data, flash_sale_title, flash_sale_timer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', '', '⚡ Oferta Relâmpago', '10')");
    $stmt->execute([
        $title,
        $price,
        $original_price,
        $discount_percent,
        $description,
        $category,
        $brand,
        $stock,
        $sales_quantity,
        $images_json
    ]);

    compile_static_site($pdo);
    header('Location: products.php?imported=1');
    exit;
}

check_auth();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador Mercado Livre - Admin</title>
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
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <span>🛍️ Importador Direto do Mercado Livre</span>
                        </h1>
                        <p class="text-xs text-gray-500 mt-1">Cole qualquer link de produto do Mercado Livre para importar dados, fotos e preços automaticamente para o seu catálogo!</p>
                    </div>
                    <a href="products.php" class="bg-black text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-800 transition">
                        📦 Ir para Produtos
                    </a>
                </div>

                <?php if ($error_msg): ?>
                    <div class="bg-red-50 text-red-800 p-4 rounded-xl font-semibold border border-red-200 text-sm flex items-center gap-2">
                        <span>⚠️</span>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 space-y-4">
                    <form method="POST" class="space-y-4">
                        <label class="block text-sm font-bold text-gray-800">Cole a URL do Produto no Mercado Livre</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" name="ml_url" value="<?= htmlspecialchars($url_or_id) ?>" placeholder="Ex: https://www.mercadolivre.com.br/principia-kit... ou https://produto.mercadolivre.com.br/MLB-..." class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-yellow-500 outline-none">
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-8 py-3 rounded-xl text-sm transition shadow-md shadow-yellow-500/20 shrink-0 flex items-center justify-center gap-2">
                                <span>⚡ Importar Anúncio</span>
                            </button>
                        </div>
                    </form>
                </div>

                <?php if ($scraped): ?>
                    <!-- Form para Salvar Direto no Catálogo -->
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-md border border-emerald-200 space-y-6">
                        <input type="hidden" name="save_to_catalog" value="1">
                        <input type="hidden" name="images_json" value="<?= htmlspecialchars(json_encode($scraped['images'])) ?>">

                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg flex items-center gap-1">
                                <span>✅ Dados Extraídos com Sucesso</span>
                            </span>
                            <span class="text-xs text-gray-400 font-mono">MLB ID: <?= htmlspecialchars($scraped['ml_id']) ?></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Preview Fotos -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-2">Galeria de Fotos Extraída</label>
                                <?php if(!empty($scraped['images'])): ?>
                                    <img src="<?= htmlspecialchars($scraped['images'][0]) ?>" class="w-full h-48 object-contain rounded-xl border border-gray-200 bg-gray-50 mb-3">
                                    <div class="flex gap-2 overflow-x-auto pb-1">
                                        <?php foreach(array_slice($scraped['images'], 1, 5) as $img): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-200 shrink-0">
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="text-[11px] text-gray-400 text-center mt-2"><?= count($scraped['images']) ?> fotos em alta resolução</p>
                                <?php endif; ?>
                            </div>

                            <!-- Campos do Produto -->
                            <div class="md:col-span-2 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Título do Produto</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($scraped['title']) ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-emerald-500 outline-none">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Preço Promocional (R$)</label>
                                        <input type="text" name="price" value="<?= htmlspecialchars($scraped['price']) ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-bold text-emerald-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Preço Original / Riscado (R$)</label>
                                        <input type="text" name="original_price" value="<?= htmlspecialchars($scraped['original_price']) ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-sm font-semibold text-gray-500 focus:ring-2 focus:ring-emerald-500 outline-none">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Categoria</label>
                                        <input type="text" name="category" value="<?= htmlspecialchars($scraped['category']) ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Marca</label>
                                        <input type="text" name="brand" value="<?= htmlspecialchars($scraped['brand']) ?>" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs font-medium">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Descrição do Produto</label>
                                    <textarea name="description" rows="4" class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs leading-relaxed font-sans focus:ring-2 focus:ring-emerald-500 outline-none"><?= htmlspecialchars(strip_tags($scraped['description'])) ?></textarea>
                                </div>

                                <div class="pt-2 flex justify-end">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-8 py-3.5 rounded-xl text-sm transition shadow-lg shadow-emerald-600/30 flex items-center gap-2">
                                        <span>💾 Salvar Produto no Catálogo Agora</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </main>
</body>
</html>
