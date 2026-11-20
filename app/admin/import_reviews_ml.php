<?php
require_once '../api/db.php';
require_once '../api/compiler.php';

$ml_id_or_url = trim($_GET['id'] ?? $_POST['id'] ?? '');
$target_prod_id = (int)($_POST['target_product_id'] ?? 0);
$do_save_reviews = !empty($_POST['save_reviews_to_product']);

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
    || isset($_GET['json']) 
    || isset($_POST['json']);

// Extract ML ID
$ml_id = '';
if ($ml_id_or_url) {
    if (preg_match('/item_id(?:%3A|=)(MLB-?\d+)/i', $ml_id_or_url, $matches)) {
        $ml_id = str_replace('-', '', strtoupper($matches[1]));
    } elseif (preg_match('/(MLB-?\d+)/i', $ml_id_or_url, $matches)) {
        $ml_id = str_replace('-', '', strtoupper($matches[1]));
    }
}

$reviews = [];

if ($ml_id_or_url) {
    // Try cURL scraping from ML Review endpoint
    $review_endpoint = "https://www.mercadolivre.com.br/noindex/catalog/reviews/{$ml_id}?page=1&limit=15";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $review_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml',
        'Accept-Language: pt-BR,pt;q=0.9',
        'Referer: https://produto.mercadolivre.com.br/'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    curl_close($ch);

    $randomNames = [
        "Ana Lúcia Silva", "Juliana Costa", "Fernanda Sousa", "Mariana Torres", "Camila Rodrigues",
        "Beatriz Vieira", "Amanda Gomes", "Bruna Alves", "Larissa Martins", "Letícia Ferreira",
        "Isabela Nogueira", "Gabriela Santos", "Vitória Rocha", "Carolina Mendes", "Patrícia Lima",
        "Thais Oliveira", "Vanessa Barbosa", "Renata Carvalho", "Aline Pereira", "Jéssica Ribeiro",
        "Marcos Silva", "Carlos Eduardo", "Rafael Machado", "Roberto K.", "Lucas Almeida",
        "Thiago Pereira", "Felipe Costa", "Bruno Souza", "Gustavo Henrique", "Diego Augusto",
        "João Pedro", "Matheus Lima", "Gabriel Ferreira", "Leonardo Santos", "Vitor Hugo"
    ];
    shuffle($randomNames);

    if ($html) {
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($doc);

        $commentNodes = $xpath->query('//article[contains(@class, "ui-review-capability-comments__review-comment") or contains(@class, "ui-pdp-reviews__comments__box")]');

        foreach ($commentNodes as $i => $node) {
            $textNode = $xpath->query('.//p[contains(@class, "ui-review-capability-comments__comment__content") or contains(@class, "ui-pdp-reviews__comments__content")]', $node)->item(0);
            $text = $textNode ? trim($textNode->textContent) : '';
            if (strlen($text) < 5) continue;
            
            $dateNode = $xpath->query('.//p[contains(@class, "ui-review-capability-comments__comment__date") or contains(@class, "ui-pdp-reviews__comments__box__date")]', $node)->item(0);
            $date = $dateNode ? trim($dateNode->textContent) : date('d/m/Y');
            
            $photos = [];
            $imgNodes = $xpath->query('.//img', $node);
            foreach ($imgNodes as $img) {
                $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
                if ($src && strpos($src, 'mlstatic.com') !== false && strpos($src, 'avatar') === false) {
                    $photos[] = preg_replace('/-[A-Z]\.(webp|jpg)$/i', '-O.jpg', $src);
                }
            }
            
            $name = $randomNames[$i % count($randomNames)];
            $is_female = rand(0, 1) === 1;
            $avId = rand(1, 95);
            $genderStr = $is_female ? 'women' : 'men';
            $avatar = "https://randomuser.me/api/portraits/{$genderStr}/{$avId}.jpg";
            
            $reviews[] = [
                'name' => $name,
                'rating' => 5,
                'text' => $text,
                'date' => $date,
                'avatar' => $avatar,
                'photos' => $photos
            ];
        }
    }

    // Fallback Generator if page did not have public review HTML
    if (empty($reviews)) {
        $preset_reviews = [
            "Excelente produto, superou minhas expectativas! Chegou antes do prazo e muito bem embalado.",
            "Muito bom, cumpre exatamente o que promete no anúncio. Podem comprar sem medo!",
            "Qualidade sensacional. Atendimento da loja nota 10, recomendo a todos.",
            "Produto lindo e de excelente qualidade. Entrega super rápida!",
            "Adorei a compra! Veio tudo certinho e funcionou perfeitamente.",
            "Ótimo custo benefício, o material é forte e resistente. Nota 5 estrelas!",
            "Chegou tudo perfeitamente! Fiquei muito satisfeito com a rapidez da entrega."
        ];
        foreach ($preset_reviews as $idx => $txt) {
            $is_fem = ($idx % 2 == 0);
            $avId = rand(1, 95);
            $reviews[] = [
                'name' => $randomNames[$idx % count($randomNames)],
                'rating' => 5,
                'text' => $txt,
                'date' => date('d/m/Y', strtotime("-{$idx} days")),
                'avatar' => "https://randomuser.me/api/portraits/" . ($is_fem ? 'women' : 'men') . "/{$avId}.jpg",
                'photos' => []
            ];
        }
    }
}

// Return JSON if AJAX API call
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($reviews);
    exit;
}

// Redirect direct browser navigation to products importer
header('Location: /admin/import_ml.php');
exit;
if ($do_save_reviews && $target_prod_id > 0 && !empty($_POST['reviews_json_payload'])) {
    check_auth();
    $revs_payload = $_POST['reviews_json_payload'];
    $stmt = $pdo->prepare("UPDATE products SET reviews_json = ? WHERE id = ?");
    $stmt->execute([$revs_payload, $target_prod_id]);
    compile_static_site($pdo);
    header("Location: /admin/products.php?reviews_saved=1");
    exit;
}

check_auth();
$all_products = $pdo->query("SELECT id, title FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Avaliações Mercado Livre - Admin</title>
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
                            <span>⭐ Importador de Avaliações Mercado Livre</span>
                        </h1>
                        <p class="text-xs text-gray-500 mt-1">Extraia avaliações com fotos e vincule diretamente a qualquer produto da sua loja</p>
                    </div>
                    <a href="products.php" class="bg-black text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-800 transition">
                        📦 Ir para Produtos
                    </a>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 space-y-4">
                    <form method="GET" class="space-y-4">
                        <label class="block text-sm font-bold text-gray-800">Cole a URL do Produto no Mercado Livre (ou Código MLB)</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" name="id" value="<?= htmlspecialchars($ml_id_or_url) ?>" placeholder="Ex: https://produto.mercadolivre.com.br/MLB-3567891234 ou MLB3567891234" class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none">
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-8 py-3 rounded-xl text-sm transition shadow-md shadow-amber-500/20 shrink-0 flex items-center justify-center gap-2">
                                <span>⭐ Extrair Avaliações</span>
                            </button>
                        </div>
                    </form>
                </div>

                <?php if ($ml_id_or_url && !empty($reviews)): ?>
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-md border border-amber-200 space-y-6">
                        <input type="hidden" name="save_reviews_to_product" value="1">
                        <input type="hidden" name="reviews_json_payload" value="<?= htmlspecialchars(json_encode($reviews)) ?>">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                            <div>
                                <span class="px-3 py-1 bg-amber-100 text-amber-900 text-xs font-bold rounded-lg">
                                    ⭐ <?= count($reviews) ?> Avaliações Extraídas
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <select name="target_product_id" required class="border border-amber-300 rounded-xl px-3 py-2 text-xs font-bold bg-amber-50 text-gray-900 focus:ring-2 focus:ring-amber-500 outline-none">
                                    <option value="">Selecione o Produto para Aplicar</option>
                                    <?php foreach($all_products as $prod): ?>
                                        <option value="<?= $prod['id'] ?>">ID #<?= $prod['id'] ?> - <?= htmlspecialchars($prod['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-xl text-xs transition shadow-md shrink-0">
                                    💾 Vincular ao Produto
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 flex gap-4">
                                    <img src="<?= htmlspecialchars($rev['avatar']) ?>" class="w-10 h-10 rounded-full object-cover shrink-0">
                                    <div class="space-y-1 flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars($rev['name']) ?></span>
                                            <span class="text-xs text-amber-500 font-bold">★★★★★</span>
                                        </div>
                                        <p class="text-xs text-gray-700 leading-relaxed"><?= htmlspecialchars($rev['text']) ?></p>
                                        <?php if (!empty($rev['photos'])): ?>
                                            <div class="flex gap-2 pt-2">
                                                <?php foreach ($rev['photos'] as $p): ?>
                                                    <img src="<?= htmlspecialchars($p) ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </main>
</body>
</html>
