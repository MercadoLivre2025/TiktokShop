<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../api/db.php';
check_auth();

require_once '../api/storage.php';

$msg = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_product'])) {
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $price = $_POST['price'] ?? '';
        $discount_price = $_POST['discount_price'] ?? '';
        $flash_sale = $_POST['flash_sale'] ?? '';
        $reviews_json = $_POST['reviews_json'] ?? '[]';
        $description = $_POST['description'] ?? '';
        $description = preg_replace('/<img[^>]*>/i', '', $description);
        foreach (['Especificações Técnicas:', 'Especificações técnicas:', 'ESPECIFICAÇÕES TÉCNICAS:', 'Especificações:', 'ESPECIFICAÇÕES:', 'CARACTERÍSTICAS:'] as $marker) {
            if (($pos = mb_strpos($description, $marker)) !== false && $pos > 20) {
                $description = trim(mb_substr($description, 0, $pos));
                break;
            }
        }
        $features = $_POST['features'] ?? '';
        $packing_list = $_POST['packing_list'] ?? '';
        $rating = $_POST['rating'] ?? '4.9';
        $sold = $_POST['sold'] ?? '12.4K';
        $total_reviews = $_POST['total_reviews'] ?? '';
        $flash_sale_timer = $_POST['flash_sale_timer'] ?? '';
        $bottom_bar_text = $_POST['bottom_bar_text'] ?? '';
        
        // Novos campos ML
        $ml_id = $_POST['ml_id'] ?? '';
        $category = $_POST['category'] ?? '';
        $brand = $_POST['brand'] ?? '';
        $sku = $_POST['sku'] ?? '';
        $specifications = $_POST['specifications'] ?? '';
        $attributes = $_POST['attributes'] ?? '';
        $stock = (int)($_POST['stock'] ?? 0);
        $sales_quantity = (int)($_POST['sales_quantity'] ?? 0);
        $seo_title = $_POST['seo_title'] ?? '';
        $seo_description = $_POST['seo_description'] ?? '';
        $videos = $_POST['videos'] ?? '';
        $variations = $_POST['variations'] ?? '';
        $technical_data = $_POST['technical_data'] ?? '';
        $related_products = $_POST['related_products'] ?? '';
        
        $discount_percent = '';
        $orig_price_float = (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $discount_price));
        $promo_price_float = (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $price));
        if ($orig_price_float > 0 && $orig_price_float > $promo_price_float) {
            $discount_pct = round((($orig_price_float - $promo_price_float) / $orig_price_float) * 100);
            $discount_percent = '-' . $discount_pct . '%';
        }
        $flash_sale_text_val = '⚡ Oferta Relâmpago';

        $old_images_json = '[]';
        if ($id) {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id=?");
            $stmt->execute([$id]);
            $old_images_json = $stmt->fetchColumn() ?: '[]';
        }


        $uploaded_paths = [];
        $upload_errors = [];

        if (!empty($_FILES['images']['name'][0])) {
            $count = min(10, count($_FILES['images']['name']));
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $name = basename($_FILES['images']['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_name = uniqid() . '_' . $i . '.' . $ext;
                    $mime = function_exists('mime_content_type') ? (mime_content_type($tmp_name) ?: 'application/octet-stream') : 'application/octet-stream';
                    $public_url = storage_upload($tmp_name, $new_name, $mime);
                    if ($public_url) {
                        $uploaded_paths[] = $public_url;
                    } else {
                        $upload_errors[] = "Falha ao mover arquivo " . $name;
                    }
                }
            }
        }
        $scraped_images = json_decode($_POST['scraped_images'] ?? '[]', true);
        if (!$scraped_images) $scraped_images = [];
        $final_images_array = array_merge($scraped_images, $uploaded_paths);
        
        $final_images_json = !empty($final_images_array) ? json_encode($final_images_array) : $old_images_json;
        if (!json_decode($final_images_json) && $final_images_json !== '[]' && $final_images_json !== '') {
            $final_images_json = json_encode([$final_images_json]);
        }

        // Auto-gerar avaliações 5 estrelas com fotos se total_reviews estiver preenchido e reviews_json estiver vazio ou insuficiente
        $num_revs = !empty($total_reviews) ? (int)preg_replace('/[^0-9]/', '', $total_reviews) : 0;
        $rev_check = json_decode($reviews_json, true);
        if ($num_revs > 0 && (empty($rev_check) || count($rev_check) < min($num_revs, 10))) {
            if ($num_revs > 300) $num_revs = 300;
                $prod_imgs_list = json_decode($final_images_json, true) ?: [];
                $review_gender = $_POST['review_gender'] ?? 'both';

                $menNames = ["Marcos Silva", "Carlos Eduardo", "Rafael Machado", "Roberto K.", "Lucas Almeida", "Thiago Pereira", "Felipe Costa", "Bruno Souza", "Gustavo Henrique", "Diego Augusto", "João Pedro", "Matheus Lima", "Gabriel Ferreira", "Leonardo Santos", "Vitor Hugo", "Pedro Henrique", "Rodrigo Alves", "André Luiz", "Marcelo Oliveira", "Alexandre Ribeiro", "Fabiano Torres", "Danilo Carvalho", "Eduardo Martins", "Guilherme Nogueira", "Caio Castro", "Samuel Barbosa", "Renan Gomes", "Vinicius Freitas", "Igor Fernandes", "Leandro Ramos", "Adriano Melo", "Maurício Dias", "Márcio Araujo", "Luciano Farias", "Ricardo Soares", "Wellington Miranda", "Everton Nascimento", "Douglas Pinto", "Cristiano Correia", "Wesley Moreira", "Ramon Teixeira", "Fabio Duarte", "Hugo Monteiro", "Alan Cavalcanti", "Juliano Guimarães", "Sandro Fonseca", "Erick Prado", "Benoit Silveira", "Murilo Campos", "Wagner Andrade", "Fabrício Aguiar", "Otávio Meireles", "Breno Salgado", "Henrique Borges", "Márcio Teles"];
                $womenNames = ["Ana Lúcia Silva", "Juliana Costa", "Fernanda Sousa", "Mariana Torres", "Camila Rodrigues", "Beatriz Vieira", "Amanda Gomes", "Bruna Alves", "Larissa Martins", "Letícia Ferreira", "Isabela Nogueira", "Gabriela Santos", "Vitória Rocha", "Carolina Mendes", "Patrícia Lima", "Thais Oliveira", "Vanessa Barbosa", "Renata Carvalho", "Aline Pereira", "Jéssica Ribeiro", "Sabrina Melo", "Débora Cardoso", "Luciana Fernandes", "Tatiane Ramos", "Monique Araujo", "Priscila Dias", "Rafaela Farias", "Camilla Soares", "Graziella Castro", "Eliane Miranda", "Milena Freitas", "Carla Nascimento", "Lívia Pinto", "Bianca Correia", "Daiane Moreira", "Evelyn Teixeira", "Suelen Duarte", "Nathalia Monteiro", "Andressa Cavalcanti", "Rebeca Guimarães", "Cíntia Fonseca", "Valéria Prado", "Jaqueline Silveira", "Tainá Campos", "Leticia Andrade", "Paloma Reis", "Clarissa Machado", "Tamires Moura", "Gisele Batista", "Nayara Marques", "Flávia Neves", "Rosana Peixoto", "Adriana Siqueira", "Luana Xavier", "Priscila Viana"];
                
                $positiveTexts = [
                    "Produto excelente, chegou super rápido e muito bem embalado. Recomendo muito!",
                    "Qualidade impecável, exatamente como descrito no anúncio. Muito satisfeito.",
                    "Comprei com receio, mas me surpreendi positivamente. Vale cada centavo!",
                    "Muito bom, cumpre o que promete. Uso todos os dias e não tenho do que reclamar.",
                    "Entrega super rápida, antes do prazo. O produto é de altíssima qualidade.",
                    "Perfeito! Material ótimo, acabamento impecável. Compraria novamente com certeza.",
                    "Melhor investimento que fiz. Facilita muito no dia a dia. Nota 10!",
                    "Recomendo de olhos fechados. Excelente custo-benefício.",
                    "Chegou em perfeito estado. Funciona perfeitamente, muito lindo.",
                    "Simplesmente amei. O tamanho é ideal e a qualidade é muito boa mesmo.",
                    "Superou minhas expectativas! Muito bem acabado e forte.",
                    "Chegou tudo certinho, bem embalado e funcionando 100%. Recomendo o vendedor!",
                    "Ótimo produto, atendeu super bem ao que eu precisava.",
                    "A entrega foi muito rápida, chegou em menos de uma semana. Nota mil!",
                    "Já é a segunda vez que compro nesta loja e nunca me arrependo.",
                    "Produto conforme as fotos e descrição. Muito prático e bonito.",
                    "Valeu muito a pena, preço justo e produto de primeira linha.",
                    "Adorei a compra! Veio bem protegido e funciona direitinho.",
                    "Excelente acabamento, material muito resistente e bonito.",
                    "Chegou antes da data prevista, vendedor atencioso e produto top!",
                    "Sensacional! Podem comprar sem medo, a qualidade é incrível.",
                    "Tudo impecável desde a embalagem até o funcionamento do produto.",
                    "Produto maravilhoso, me atendeu perfeitamente. Nota 10 em tudo!"
                ];
                
                shuffle($womenNames);
                shuffle($menNames);
                shuffle($positiveTexts);
                
                $womenAvatars = range(1, 99); shuffle($womenAvatars);
                $menAvatars = range(1, 99); shuffle($menAvatars);
                
                $w_idx = 0;
                $m_idx = 0;
                
                $auto_revs = [];
                for ($r_idx = 0; $r_idx < $num_revs; $r_idx++) {
                    if ($review_gender === 'women') {
                        $is_woman = true;
                    } else if ($review_gender === 'men') {
                        $is_woman = false;
                    } else {
                        $is_woman = rand(0, 1) === 1;
                    }
                    
                    if ($is_woman) {
                        $name = $womenNames[$w_idx % count($womenNames)];
                        $avatar_id = $womenAvatars[$w_idx % count($womenAvatars)];
                        $gender = 'women';
                        $w_idx++;
                    } else {
                        $name = $menNames[$m_idx % count($menNames)];
                        $avatar_id = $menAvatars[$m_idx % count($menAvatars)];
                        $gender = 'men';
                        $m_idx++;
                    }
                    
                    $avatar = "https://randomuser.me/api/portraits/" . $gender . "/" . $avatar_id . ".jpg";
                    $text = $positiveTexts[$r_idx % count($positiveTexts)];
                    $date = date('d/m/Y', strtotime('-' . rand(1, 45) . ' days'));
                    
                    $auto_revs[] = [
                        'name' => $name,
                        'rating' => 5,
                        'text' => $text,
                        'date' => $date,
                        'avatar' => $avatar,
                        'photos' => []
                    ];
                }
                $reviews_json = json_encode($auto_revs);
            }

        $msg_suffix = empty($upload_errors) ? "" : " (Atenção: " . implode(", ", $upload_errors) . ")";

        if ($id) {
            $stmt = $pdo->prepare("UPDATE products SET title=?, price=?, discount_price=?, image=?, flash_sale=?, reviews_json=?, description=?, features=?, packing_list=?, rating=?, sold=?, discount_percent=?, flash_sale_timer=?, flash_sale_text=?, bottom_bar_text=?, ml_id=?, category=?, brand=?, sku=?, specifications=?, attributes=?, stock=?, sales_quantity=?, seo_title=?, seo_description=?, videos=?, variations=?, technical_data=?, related_products=?, total_reviews=? WHERE id=?");
            $stmt->execute([$title, $price, $discount_price, $final_images_json, $flash_sale, $reviews_json, $description, $features, $packing_list, $rating, $sold, $discount_percent, $flash_sale_timer, $flash_sale_text_val, $bottom_bar_text, $ml_id, $category, $brand, $sku, $specifications, $attributes, $stock, $sales_quantity, $seo_title, $seo_description, $videos, $variations, $technical_data, $related_products, $total_reviews, $id]);
            $msg = "Produto atualizado!" . $msg_suffix;
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (title, price, discount_price, image, flash_sale, reviews_json, description, features, packing_list, rating, sold, discount_percent, flash_sale_timer, flash_sale_text, bottom_bar_text, ml_id, category, brand, sku, specifications, attributes, stock, sales_quantity, seo_title, seo_description, videos, variations, technical_data, related_products, total_reviews) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $price, $discount_price, $final_images_json, $flash_sale, $reviews_json, $description, $features, $packing_list, $rating, $sold, $discount_percent, $flash_sale_timer, $flash_sale_text_val, $bottom_bar_text, $ml_id, $category, $brand, $sku, $specifications, $attributes, $stock, $sales_quantity, $seo_title, $seo_description, $videos, $variations, $technical_data, $related_products, $total_reviews]);
            $msg = "Produto criado!" . $msg_suffix;
        }

        require_once '../api/compiler.php';
        compile_static_site($pdo);
    }
    
    if (isset($_POST['delete_product'])) {
        $del_id = $_POST['delete_id'] ?? 0;
        // Puxar imagens do produto para apagar os arquivos
        $stmt_img = $pdo->prepare("SELECT image FROM products WHERE id=?");
        $stmt_img->execute([$del_id]);
        $imgs_raw = $stmt_img->fetchColumn();
        if ($imgs_raw) {
            $imgs_arr = json_decode($imgs_raw, true) ?: [$imgs_raw];
            foreach ((array)$imgs_arr as $img_path) {
                if ($img_path && strpos($img_path, '/storage/v1/object/public/') !== false) {
                    storage_delete_by_url($img_path);
                }
            }
        }
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$del_id]);
        $msg = "Produto removido!";
        require_once '../api/compiler.php';
        compile_static_site($pdo);
    }

    if (isset($_POST['clear_all_products'])) {
        require_once '../api/clear_products.php';
        $msg = "Todos os produtos, fotos e caches foram removidos!";
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$products_map = [];
foreach ($products as $p) {
    $products_map[$p['id']] = $p;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
.tab-active { border-bottom: 2px solid black; color: black; font-weight: bold; }
.tab-inactive { border-bottom: 2px solid transparent; color: #6b7280; }
</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col">

        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-5xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Catálogo de Produtos</h1>
                    <div class="flex gap-2">
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja apagar TODOS os produtos, fotos e caches? Esta ação é irreversível!');">
                            <input type="hidden" name="clear_all_products" value="1">
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 transition">
                                🗑️ Limpar Todos os Produtos
                            </button>
                        </form>
                        <button onclick="openAddModal()" class="bg-black text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-800 transition">
                            + Novo Produto
                        </button>
                    </div>
                </div>

                <?php if ($msg): ?>
                    <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6 font-medium border border-green-200">
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="p-4 text-sm font-semibold text-gray-600">Imagens</th>
                                <th class="p-4 text-sm font-semibold text-gray-600">Produto</th>
                                <th class="p-4 text-sm font-semibold text-gray-600">Preço</th>
                                <th class="p-4 text-sm font-semibold text-gray-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($products as $p): 
                                $imgs = json_decode($p['image'], true);
                                if (!$imgs && !empty($p['image'])) {
                                    $imgs = [$p['image']];
                                }
                                $first_img = !empty($imgs) ? $imgs[0] : '';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4 w-20">
                                        <?php if($first_img): ?>
                                            <img src="<?= htmlspecialchars($first_img) ?>" class="w-12 h-12 object-cover rounded-md border border-gray-200">
                                            <?php if(count($imgs) > 1): ?>
                                                <div class="text-[10px] text-gray-500 text-center mt-1">+<?= count($imgs)-1 ?> fotos</div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($p['title']) ?></td>
                                    <td class="p-4 text-sm text-gray-600">
                                        <div class="font-bold text-red-600"><?= htmlspecialchars($p['price']) ?></div>
                                        <div class="text-xs line-through text-gray-400"><?= htmlspecialchars($p['discount_price']) ?></div>
                                    </td>
                                    <td class="p-4 flex gap-3 items-center">
                                        <a href="../produto-<?= $p['id'] ?>.html" target="_blank" class="text-purple-600 hover:text-purple-800 text-sm font-semibold">👁 Ver Produto</a>
                                        <button type="button" onclick="navigator.clipboard.writeText(window.location.origin + '/produto-<?= $p['id'] ?>.html'); alert('Link do Produto copiado!\n' + window.location.origin + '/produto-<?= $p['id'] ?>.html');" class="text-green-600 hover:text-green-800 text-sm font-semibold">🔗 Copiar Link</button>
                                        <button type="button" onclick="openEditModal(<?= $p['id'] ?>)" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Editar</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Excluir produto?');">
                                            <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="delete_product" class="text-red-600 hover:text-red-800 text-sm font-semibold">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="4" class="p-8 text-center text-gray-500">Nenhum produto cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Add/Edit -->
                <div id="modal-add" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
                    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl overflow-hidden max-h-[90vh] flex flex-col">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center shrink-0">
                            <h2 class="text-xl font-bold" id="modal-title">Adicionar Produto</h2>
                            <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-black text-xl">×</button>
                        </div>
                        
                        <div class="flex border-b border-gray-200 px-6 pt-2 bg-gray-50">
                            <button type="button" class="tab-btn px-4 py-2 tab-active" onclick="switchTab('tab-import')">Importar ML</button>
                            <button type="button" class="tab-btn px-4 py-2 tab-inactive" onclick="switchTab('tab-geral')">Dados Básicos & Variações</button>
                            <button type="button" class="tab-btn px-4 py-2 tab-inactive" onclick="switchTab('tab-media')">Mídia & Reviews</button>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto flex-1 bg-white">
                            <input type="hidden" name="save_product" value="1">
                            <input type="hidden" name="id" id="prod_id" value="">
                            
                            <!-- Hidden fields for DB compatibility -->
                            <input type="hidden" name="brand" id="prod_brand" value="">
                            <input type="hidden" name="sku" id="prod_sku" value="">
                            <input type="hidden" name="technical_data" id="prod_technical_data" value="">
                            <input type="hidden" name="features" id="prod_features" value="">
                            <input type="hidden" name="packing_list" id="prod_packing_list" value="">
                            <input type="hidden" name="specifications" id="prod_specifications" value="">
                            <input type="hidden" name="attributes" id="prod_attributes" value="">
                            <input type="hidden" name="seo_title" id="prod_seo_title" value="">
                            <input type="hidden" name="seo_description" id="prod_seo_description" value="">
                            <input type="hidden" name="videos" id="prod_videos" value="">
                            <input type="hidden" name="related_products" id="prod_related_products" value="">
                            
                            <!-- TAB: IMPORT ML -->
                            <div id="tab-import" class="tab-content block">
                                <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200 text-center">
                                    <h3 class="font-bold text-yellow-800 mb-2 text-lg">Integração Mercado Livre (Código Fonte)</h3>
                                    <p class="text-sm text-yellow-700 mb-4">Para driblar todos os bloqueios, importe usando o Código-Fonte (Ctrl+U).</p>
                                    
                                    <div class="flex flex-col gap-2 max-w-2xl mx-auto">
                                        <p class="text-sm text-left text-gray-700 font-bold mb-1">Passo 1: Acesse a página do produto no Mercado Livre.</p>
                                        <p class="text-sm text-left text-gray-700 font-bold mb-1">Passo 2: Pressione Ctrl+U (ou Cmd+Option+U) para ver o código fonte. Copie tudo (Ctrl+A, Ctrl+C) e cole abaixo:</p>
                                        <textarea id="ml_source_input" rows="6" class="w-full border border-yellow-300 rounded-lg px-4 py-3 text-xs font-mono" placeholder="Cole o código-fonte HTML (Ctrl+U) do Mercado Livre ou a URL aqui..."></textarea>
                                        <button type="button" onclick="importMLData()" class="bg-yellow-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-yellow-600 transition shadow-sm w-full mt-2">🔍 Extrair Dados do Código Fonte</button>
                                    </div>
                                    <div id="ml_loading" class="hidden mt-4 text-yellow-700 font-medium">⏳ Lendo código HTML e extraindo dados...</div>
                                </div>
                            </div>
                            
                            <!-- TAB: GERAL -->
                            <div id="tab-geral" class="tab-content hidden space-y-4">
                                <input type="hidden" name="ml_id" id="prod_ml_id">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                                        <input type="text" name="title" id="prod_title" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria do Produto</label>
                                        <input type="text" name="category" id="prod_category" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ex: Roupas, Eletrônicos, Calçados">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço (Venda Atual)</label>
                                        <input type="text" name="price" id="prod_price" oninput="updateCalculatedDiscountBadge()" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="Ex: 147,00">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço Antigo (Riscado)</label>
                                        <input type="text" name="discount_price" id="prod_discount_price" oninput="updateCalculatedDiscountBadge()" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ex: 468,90">
                                    </div>
                                </div>
                                <div id="calc_discount_badge" class="mt-1"></div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Avaliação (Ex: 4.9)</label>
                                        <input type="text" name="rating" id="prod_rating" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="4.9">
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-sm font-medium text-gray-700">Total Avaliações</label>
                                            <button type="button" onclick="autoGenerateFromTotalInput()" class="text-[11px] text-purple-600 font-bold hover:underline">✨ Gerar com Fotos</button>
                                        </div>
                                        <input type="text" name="total_reviews" id="prod_total_reviews" onchange="autoGenerateFromTotalInput()" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ex: 153">
                                        <p class="text-[10px] text-purple-700 mt-0.5 font-medium">Gera comentários 5★ com fotos automaticamente ao preencher!</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendidos</label>
                                        <input type="text" name="sold" id="prod_sold" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Ex: 12.4K">
                                    </div>
                                </div>
                                
                                <!-- Gênero dos Comentários -->
                                <div class="mt-3 bg-purple-50 border border-purple-200 p-3 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-purple-900">👥 Gênero dos Comentários:</span>
                                        <span class="text-[10px] text-purple-700">(Filtro para fotos e nomes gerados)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="inline-flex items-center text-xs font-semibold text-purple-900 cursor-pointer bg-white px-2.5 py-1 rounded border border-purple-200 shadow-sm hover:bg-purple-100 transition-colors">
                                            <input type="radio" name="review_gender" value="women" id="gender_women" onchange="autoGenerateFromTotalInput()" class="text-purple-600 focus:ring-purple-500">
                                            <span class="ml-1.5">👩 Mulher</span>
                                        </label>
                                        <label class="inline-flex items-center text-xs font-semibold text-purple-900 cursor-pointer bg-white px-2.5 py-1 rounded border border-purple-200 shadow-sm hover:bg-purple-100 transition-colors">
                                            <input type="radio" name="review_gender" value="men" id="gender_men" onchange="autoGenerateFromTotalInput()" class="text-purple-600 focus:ring-purple-500">
                                            <span class="ml-1.5">👨 Homem</span>
                                        </label>
                                        <label class="inline-flex items-center text-xs font-semibold text-purple-900 cursor-pointer bg-white px-2.5 py-1 rounded border border-purple-200 shadow-sm hover:bg-purple-100 transition-colors">
                                            <input type="radio" name="review_gender" value="both" id="gender_both" checked onchange="autoGenerateFromTotalInput()" class="text-purple-600 focus:ring-purple-500">
                                            <span class="ml-1.5">👫 Ambos</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                    <textarea name="description" id="prod_description" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                                </div>
                                <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg space-y-4">
                                     <h3 class="font-bold text-orange-800 text-sm">Gatilhos de Escassez</h3>
                                     <div class="grid grid-cols-2 gap-4">
                                         <div>
                                             <label class="block text-sm font-medium text-gray-700 mb-1">Tempo Oferta Relâmpago (Cronômetro)</label>
                                             <select name="flash_sale_timer" id="prod_flash_sale_timer" class="w-full border border-orange-300 rounded-lg px-3 py-2 text-sm font-bold bg-white text-gray-800">
                                                 <option value="">Usar padrão global (10 min)</option>
                                                 <option value="5">05 Minutos</option>
                                                 <option value="10">10 Minutos</option>
                                                 <option value="15">15 Minutos</option>
                                                 <option value="20">20 Minutos</option>
                                                 <option value="30">30 Minutos</option>
                                                 <option value="45">45 Minutos</option>
                                                 <option value="60">1 Hora (60 Minutos)</option>
                                                 <option value="120">2 Horas (120 Minutos)</option>
                                                 <option value="300">5 Horas (300 Minutos)</option>
                                                 <option value="720">12 Horas (720 Minutos)</option>
                                                 <option value="1440">24 Horas (1 Dia)</option>
                                             </select>
                                         </div>
                                         <div>
                                             <label class="block text-sm font-medium text-gray-700 mb-1">Texto Barra Fixa Inferior</label>
                                             <input type="text" name="bottom_bar_text" id="prod_bottom_bar_text" class="w-full border border-orange-300 rounded-lg px-3 py-2 text-sm font-semibold" placeholder="Padrão: Oferta Relâmpago">
                                             <p class="text-xs text-gray-500 mt-1">Texto do título na faixa laranja. Se deixar em branco, usará "Oferta Relâmpago".</p>
                                         </div>
                                    </div>
                                </div>
                                <div class="hidden bg-purple-50 border border-purple-200 p-4 rounded-lg space-y-3">
                                     <div class="flex items-center justify-between">
                                         <label class="font-bold text-purple-900 text-sm flex items-center gap-2 cursor-pointer">
                                             <input type="checkbox" id="prod_enable_variations" onchange="toggleVariationsBox()" class="w-4 h-4 text-purple-600 rounded">
                                             <span>Ativar Barra de Variações neste produto</span>
                                         </label>
                                     </div>
                                     <div id="variations_input_container" class="hidden space-y-3 pt-2 border-t border-purple-200">
                                         <div class="bg-white p-3 rounded-lg border border-purple-200 space-y-2">
                                             <label class="font-bold text-purple-900 text-xs flex items-center gap-2 cursor-pointer">
                                                 <input type="checkbox" id="prod_enable_options" onchange="toggleOptionsInput()" class="w-4 h-4 text-purple-600 rounded">
                                                 <span>Ativar Variação de Cor / Modelo / Opção</span>
                                             </label>
                                             <div id="options_input_box" class="hidden pt-1 space-y-3">
                                                  <div>
                                                      <label class="block text-xs font-bold text-gray-700 mb-1">Cores ou Opções / Modelos (separadas por vírgula)</label>
                                                      <input type="text" id="prod_options_input" oninput="renderOptionImageBinders(); updateVariationsJson();" class="w-full border border-purple-300 rounded-lg px-3 py-2 text-xs bg-white" placeholder="Ex: Modelo A, Modelo B ou Preto, Rosa">
                                                      <p class="text-[11px] text-gray-500 mt-1">Digite os modelos acima. O cliente verá apenas essas variações, e ao clicar em cada uma as fotos daquele modelo serão exibidas.</p>
                                                  </div>
                                                  <div id="option_image_binders_container" class="space-y-3 pt-2 border-t border-purple-100"></div>
                                              </div>
                                         </div>

                                         <div class="bg-white p-3 rounded-lg border border-purple-200 space-y-2">
                                             <label class="font-bold text-purple-900 text-xs flex items-center gap-2 cursor-pointer">
                                                 <input type="checkbox" id="prod_enable_sizes" onchange="toggleSizesInput()" class="w-4 h-4 text-purple-600 rounded">
                                                 <span>Ativar Variação de Tamanho</span>
                                             </label>
                                             <div id="sizes_input_box" class="hidden pt-1">
                                                 <label class="block text-xs text-gray-600 mb-1">Tamanhos Disponíveis (separados por vírgula)</label>
                                                 <input type="text" id="prod_sizes_input" oninput="updateVariationsJson()" class="w-full border border-purple-300 rounded-lg px-3 py-2 text-xs bg-white" placeholder="Ex: P, M, G, GG ou 36, 38, 40, 42">
                                             </div>
                                         </div>

                                         <div class="bg-white p-3 rounded-lg border border-purple-200 space-y-2">
                                             <label class="font-bold text-purple-900 text-xs flex items-center gap-2 cursor-pointer">
                                                 <input type="checkbox" id="prod_enable_voltages" onchange="toggleVoltagesInput()" class="w-4 h-4 text-purple-600 rounded">
                                                 <span>Ativar Variação de Voltagem</span>
                                             </label>
                                             <div id="voltages_input_box" class="hidden pt-1">
                                                 <label class="block text-xs text-gray-600 mb-1">Voltagens Disponíveis (separadas por vírgula)</label>
                                                 <input type="text" id="prod_voltages_input" oninput="updateVariationsJson()" class="w-full border border-purple-300 rounded-lg px-3 py-2 text-xs bg-white" placeholder="Ex: 110V, 220V, Bivolt">
                                             </div>
                                         </div>
                                     </div>
                                     <input type="hidden" name="variations" id="prod_variations">
                                 </div>
                            </div>
                            
                            <!-- TAB: MÍDIA E REVIEWS -->
                            <div id="tab-media" class="tab-content hidden space-y-4">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <label class="block text-sm font-bold text-blue-900 mb-2">Fotos do Produto (Upload manual)</label>
                                    <input type="file" name="images[]" multiple accept="image/*" class="w-full bg-white border border-blue-200 rounded-lg px-3 py-2 text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagens Importadas da API</label>
                                    <input type="hidden" name="scraped_images" id="prod_scraped_images" value="[]">
                                    <div id="scraped_images_preview" class="flex gap-2 overflow-x-auto mt-1 pb-2"></div>
                                </div>
                                
                                <div class="mt-4 border-t pt-4">
                                    <div class="mb-2">
                                        <label class="block text-sm font-bold text-gray-700">Avaliações Importadas (Geradas Automaticamente)</label>
                                    </div>

                                    <div id="reviews_preview" class="space-y-3 max-h-60 overflow-y-auto bg-gray-50 p-3 rounded border"></div>
                                    <input type="hidden" name="reviews_json" id="prod_reviews_json" value="[]">
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end gap-3 border-t">
                                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg">Cancelar</button>
                                <button type="submit" class="bg-black text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-800 shadow">Salvar Produto Completo</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        window.productsMap = <?= json_encode($products_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let currentReviews = [];

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = "tab-btn px-4 py-2 tab-inactive";
                if (btn.getAttribute('onclick').includes(tabId)) {
                    btn.className = "tab-btn px-4 py-2 tab-active";
                }
            });
        }

        function autoGenerateFromTotalInput() {
            const val = document.getElementById('prod_total_reviews').value;
            if (!val) return;
            const num = parseInt(val.replace(/[^0-9]/g, ''));
            if (!isNaN(num) && num > 0) {
                generateAutoReviews(num);
            }
        }

        function addManualReview() {
            const name = document.getElementById('manual_rev_name').value.trim();
            const text = document.getElementById('manual_rev_text').value.trim();
            const photo = document.getElementById('manual_rev_photo').value.trim();
            let date = document.getElementById('manual_rev_date').value.trim();
            
            if(!name || !text) { alert('Preencha pelo menos o nome e o texto da avaliação!'); return; }
            if(!date) date = new Date().toLocaleDateString('pt-BR');
            
            const gender = name.toLowerCase().match(/[aã]\s/) ? 'women' : 'men';
            const avatar = "https://randomuser.me/api/portraits/" + gender + "/" + (Math.floor(Math.random() * 90) + 1) + ".jpg";
            
            let photos = [];
            if(photo) photos.push(photo);
            
            currentReviews.unshift({
                name: name,
                rating: 5,
                text: text,
                date: date,
                avatar: avatar,
                photos: photos
            });
            
            document.getElementById('manual_rev_name').value = '';
            document.getElementById('manual_rev_text').value = '';
            document.getElementById('manual_rev_photo').value = '';
            document.getElementById('manual_review_form').classList.add('hidden');
            
            renderReviews();
        }

        function shuffleArrayJS(array) {
            let arr = [...array];
            for (let i = arr.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [arr[i], arr[j]] = [arr[j], arr[i]];
            }
            return arr;
        }

        function generateAutoReviews(targetCount) {
            let qtd = targetCount ? parseInt(targetCount) : parseInt(document.getElementById('auto_rev_qtd') ? document.getElementById('auto_rev_qtd').value : 0);
            if(isNaN(qtd) || qtd <= 0) {
                const inputVal = document.getElementById('prod_total_reviews') ? document.getElementById('prod_total_reviews').value : '';
                qtd = parseInt(inputVal.replace(/[^0-9]/g, '')) || 10;
            }
            if(qtd > 300) qtd = 300;
            
            let selectedGender = 'both';
            const checkedRadio = document.querySelector('input[name="review_gender"]:checked');
            if (checkedRadio) selectedGender = checkedRadio.value;
            
            const menNames = [
                "Marcos Silva", "Carlos Eduardo", "Rafael Machado", "Roberto K.", "Lucas Almeida",
                "Thiago Pereira", "Felipe Costa", "Bruno Souza", "Gustavo Henrique", "Diego Augusto",
                "João Pedro", "Matheus Lima", "Gabriel Ferreira", "Leonardo Santos", "Vitor Hugo",
                "Pedro Henrique", "Rodrigo Alves", "André Luiz", "Marcelo Oliveira", "Alexandre Ribeiro",
                "Fabiano Torres", "Danilo Carvalho", "Eduardo Martins", "Guilherme Nogueira", "Caio Castro",
                "Samuel Barbosa", "Renan Gomes", "Vinicius Freitas", "Igor Fernandes", "Leandro Ramos",
                "Adriano Melo", "Maurício Dias", "Márcio Araujo", "Luciano Farias", "Ricardo Soares",
                "Wellington Miranda", "Everton Nascimento", "Douglas Pinto", "Cristiano Correia", "Wesley Moreira",
                "Ramon Teixeira", "Fabio Duarte", "Hugo Monteiro", "Alan Cavalcanti", "Juliano Guimarães",
                "Sandro Fonseca", "Erick Prado", "Benoit Silveira", "Murilo Campos", "Wagner Andrade",
                "Fabrício Aguiar", "Otávio Meireles", "Breno Salgado", "Henrique Borges", "Márcio Teles"
            ];
            
            const womenNames = [
                "Ana Lúcia Silva", "Juliana Costa", "Fernanda Sousa", "Mariana Torres", "Camila Rodrigues",
                "Beatriz Vieira", "Amanda Gomes", "Bruna Alves", "Larissa Martins", "Letícia Ferreira",
                "Isabela Nogueira", "Gabriela Santos", "Vitória Rocha", "Carolina Mendes", "Patrícia Lima",
                "Thais Oliveira", "Vanessa Barbosa", "Renata Carvalho", "Aline Pereira", "Jéssica Ribeiro",
                "Sabrina Melo", "Débora Cardoso", "Luciana Fernandes", "Tatiane Ramos", "Monique Araujo",
                "Priscila Dias", "Rafaela Farias", "Camilla Soares", "Graziella Castro", "Eliane Miranda",
                "Milena Freitas", "Carla Nascimento", "Lívia Pinto", "Bianca Correia", "Daiane Moreira",
                "Evelyn Teixeira", "Suelen Duarte", "Nathalia Monteiro", "Andressa Cavalcanti", "Rebeca Guimarães",
                "Cíntia Fonseca", "Valéria Prado", "Jaqueline Silveira", "Tainá Campos", "Leticia Andrade",
                "Paloma Reis", "Clarissa Machado", "Tamires Moura", "Gisele Batista", "Nayara Marques",
                "Flávia Neves", "Rosana Peixoto", "Adriana Siqueira", "Luana Xavier", "Priscila Viana"
            ];
            
            const positiveTexts = [
                "Produto excelente, chegou super rápido e muito bem embalado. Recomendo muito!",
                "Qualidade impecável, exatamente como descrito no anúncio. Muito satisfeito.",
                "Comprei com receio, mas me surpreendi positivamente. Vale cada centavo!",
                "Muito bom, cumpre o que promete. Uso todos os dias e não tenho do que reclamar.",
                "Entrega super rápida, antes do prazo. O produto é de altíssima qualidade.",
                "Perfeito! Material ótimo, acabamento impecável. Compraria novamente com certeza.",
                "Melhor investimento que fiz. Facilita muito no dia a dia. Nota 10!",
                "Recomendo de olhos fechados. Excelente custo-benefício.",
                "Chegou em perfeito estado. Funciona perfeitamente, muito lindo.",
                "Simplesmente amei. O tamanho é ideal e a qualidade é muito boa mesmo.",
                "Superou minhas expectativas! Muito bem acabado e forte.",
                "Chegou tudo certinho, bem embalado e funcionando 100%. Recomendo o vendedor!",
                "Ótimo produto, atendeu super bem ao que eu precisava.",
                "A entrega foi muito rápida, chegou em menos de uma semana. Nota mil!",
                "Já é a segunda vez que compro nesta loja e nunca me arrependo.",
                "Produto conforme as fotos e descrição. Muito prático e bonito.",
                "Valeu muito a pena, preço justo e produto de primeira linha.",
                "Adorei a compra! Veio bem protegido e funciona direitinho.",
                "Excelente acabamento, material muito resistente e bonito.",
                "Chegou antes da data prevista, vendedor atencioso e produto top!",
                "Sensacional! Podem comprar sem medo, a qualidade é incrível.",
                "Tudo impecável desde a embalagem até o funcionamento do produto.",
                "Produto maravilhoso, me atendeu perfeitamente. Nota 10 em tudo!"
            ];
            
            const shuffledWomen = shuffleArrayJS(womenNames);
            const shuffledMen = shuffleArrayJS(menNames);
            const shuffledTexts = shuffleArrayJS(positiveTexts);
            
            const womenAvatars = shuffleArrayJS(Array.from({length: 99}, (_, i) => i + 1));
            const menAvatars = shuffleArrayJS(Array.from({length: 99}, (_, i) => i + 1));
            
            let womanCount = 0;
            let manCount = 0;
            
            currentReviews = [];
            for(let i = 0; i < qtd; i++) {
                let isWoman = false;
                if (selectedGender === 'women') {
                    isWoman = true;
                } else if (selectedGender === 'men') {
                    isWoman = false;
                } else {
                    isWoman = Math.random() > 0.5;
                }
                
                let name, avatar;
                if (isWoman) {
                    name = shuffledWomen[womanCount % shuffledWomen.length];
                    const avId = womenAvatars[womanCount % womenAvatars.length];
                    avatar = "https://randomuser.me/api/portraits/women/" + avId + ".jpg";
                    womanCount++;
                } else {
                    name = shuffledMen[manCount % shuffledMen.length];
                    const avId = menAvatars[manCount % menAvatars.length];
                    avatar = "https://randomuser.me/api/portraits/men/" + avId + ".jpg";
                    manCount++;
                }
                
                const text = shuffledTexts[i % shuffledTexts.length];
                
                const pastDate = new Date();
                pastDate.setDate(pastDate.getDate() - Math.floor(Math.random() * 45));
                const dateStr = pastDate.toLocaleDateString('pt-BR');
                
                currentReviews.push({
                    name: name,
                    rating: 5,
                    text: text,
                    date: dateStr,
                    avatar: avatar,
                    photos: []
                });
            }
            
            if (document.getElementById('auto_review_form')) {
                document.getElementById('auto_review_form').classList.add('hidden');
            }
            renderReviews();
        }

        function renderReviews() {
            const container = document.getElementById('reviews_preview');
            if (!container) return;
            document.getElementById('prod_reviews_json').value = JSON.stringify(currentReviews);

            if (!currentReviews || currentReviews.length === 0) {
                container.innerHTML = '<div class="text-xs text-gray-500 italic text-center p-4">Nenhuma avaliação cadastrada.</div>';
                return;
            }

            let html = '';
            const displayLimit = Math.min(currentReviews.length, 50);

            for (let idx = 0; idx < displayLimit; idx++) {
                const r = currentReviews[idx];
                const name = r.name || r.n || 'Cliente';
                const text = r.text || r.t || '';
                const date = r.date || r.d || 'Recente';
                
                let photosHtml = '';
                if (r.photos && r.photos.length > 0) {
                    photosHtml = '<div class="flex gap-2 mt-2">';
                    r.photos.forEach(img => {
                        photosHtml += '<img src="' + img + '" class="w-12 h-12 rounded object-cover border border-gray-200 hover:opacity-75 cursor-pointer">';
                    });
                    photosHtml += '</div>';
                }

                let avatarHtml = '';
                if (r.avatar) {
                    avatarHtml = '<img src="' + r.avatar + '" class="w-8 h-8 rounded-full object-cover border border-gray-200">';
                } else {
                    avatarHtml = '<div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-600 text-xs">' + name.charAt(0).toUpperCase() + '</div>';
                }

                html += '<div class="bg-white p-3 rounded shadow-sm border border-gray-200 flex flex-col gap-2 relative">' +
                    '<button type="button" onclick="removeReview(' + idx + ')" class="absolute top-2 right-2 text-red-500 hover:text-red-700 font-bold text-xs">X Remover</button>' +
                    '<div class="flex items-center gap-3">' + avatarHtml + '<div><div class="text-sm font-bold flex items-center gap-1">⭐ ' + (r.rating || 5) + ' - ' + name + '</div><div class="text-xs text-gray-400">' + date + '</div></div></div>' +
                    '<div class="text-xs text-gray-700 ml-11">' + text + '</div><div class="ml-11">' + photosHtml + '</div></div>';
            }

            if (currentReviews.length > 50) {
                html += '<div class="text-xs text-gray-500 text-center py-2 font-medium bg-gray-100 rounded">+ ' + (currentReviews.length - 50) + ' avaliações ocultas para manter o painel super rápido</div>';
            }

            container.innerHTML = html;
        }

        function removeReview(idx) {
            currentReviews.splice(idx, 1);
            renderReviews();
        }

        function updateScrapedImages() {
            const imgs = Array.from(document.querySelectorAll('#scraped_images_preview img')).map(img => img.getAttribute('data-src'));
            document.getElementById('prod_scraped_images').value = JSON.stringify(imgs);
        }

        async function importMLData() {
            const rawInput = document.getElementById('ml_source_input').value.trim();
            if(!rawInput) { alert("Cole o código fonte HTML (Ctrl+U) do produto do Mercado Livre!"); return; }
            
            document.getElementById('ml_loading').classList.remove('hidden');
            
            // Check if user pasted a URL or MLB ID directly
            if (rawInput.startsWith('http://') || rawInput.startsWith('https://') || (rawInput.includes('MLB') && !rawInput.includes('<html') && !rawInput.includes('<body') && !rawInput.includes('<meta'))) {
                try {
                    const res = await fetch('import_ml.php?json=1&ml_url=' + encodeURIComponent(rawInput));
                    const data = await res.json();
                    
                    document.getElementById('ml_loading').classList.add('hidden');
                    
                    if (data && data.success) {
                        document.getElementById('prod_title').value = data.title || '';
                        if (data.price) {
                            document.getElementById('prod_price').value = typeof data.price === 'string' ? (data.price.includes('R$') ? data.price : 'R$ ' + data.price) : 'R$ ' + data.price.toFixed(2).replace('.', ',');
                        }
                        if (data.original_price) {
                            document.getElementById('prod_discount_price').value = typeof data.original_price === 'string' ? (data.original_price.includes('R$') ? data.original_price : 'R$ ' + data.original_price) : 'R$ ' + data.original_price.toFixed(2).replace('.', ',');
                        }
                        if (data.description) {
                            document.getElementById('prod_description').value = data.description.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '');
                        }
                        if (data.category) document.getElementById('prod_category').value = data.category;
                        if (data.brand) document.getElementById('prod_brand').value = data.brand;
                        if (data.ml_id) document.getElementById('prod_ml_id').value = data.ml_id;
                        
                        if (data.images && data.images.length > 0) {
                            document.getElementById('prod_scraped_images').value = JSON.stringify(data.images);
                            const preview = document.getElementById('scraped_images_preview');
                            preview.innerHTML = '';
                            data.images.forEach(img => {
                                preview.innerHTML += '<img src="' + img + '" class="w-16 h-16 rounded object-cover border cursor-pointer hover:opacity-50" onclick="this.remove(); updateScrapedImages()" data-src="' + img + '" title="Clique para Remover">';
                            });
                        }

                        alert('✨ Produto extraído com sucesso!\n\nVerifique os dados na aba "Dados Básicos & Variações" e clique em "Salvar Produto Completo".');
                        switchTab('tab-geral');
                        return;
                    }
                } catch(e) {
                    console.error(e);
                }
            }

            // HTML Source Code Parser (Ctrl+U)
            try {
                const htmlSource = rawInput;
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlSource, "text/html");
                
                let ml_id = '';
                const matchId = htmlSource.match(/MLB-?\d+/i);
                if (matchId) ml_id = matchId[0].replace('-', '').toUpperCase();
                
                // 1. Extract Title
                let title = '';
                const titleEl = doc.querySelector('.ui-pdp-title') || doc.querySelector('.ui-vpp-title') || doc.querySelector('h1');
                if (titleEl) title = titleEl.innerText.trim();
                
                if (!title) {
                    const ogTitleMatch = htmlSource.match(/<meta[^>]*property=["\']og:title["\'][^>]*content=["\'](.*?)["\']/i);
                    if (ogTitleMatch) title = ogTitleMatch[1].replace(/&quot;/g, '"').replace(/&amp;/g, '&').trim();
                }
                if (!title) {
                    const titleTag = doc.querySelector('title');
                    if (titleTag) title = titleTag.innerText.replace(/\| MercadoLivre.*/i, '').replace(/\| Mercado Livre.*/i, '').trim();
                }
                title = title.replace(/ - Mercado Livre.*/i, '').replace(/ - MercadoLivre.*/i, '').trim();

                // 2. Extract Price
                let price = 0;
                const priceMeta = doc.querySelector('meta[itemprop="price"]') || doc.querySelector('meta[property="og:price:amount"]');
                if (priceMeta) price = parseFloat(priceMeta.getAttribute('content'));

                if (!price || isNaN(price) || price === 0) {
                    const priceFraction = doc.querySelector('.andes-money-amount__fraction') || doc.querySelector('.ui-pdp-price__second-line .andes-money-amount__fraction');
                    if (priceFraction) {
                        price = parseFloat(priceFraction.innerText.replace(/\./g, '').replace(',', '.'));
                        const centsEl = doc.querySelector('.andes-money-amount__cents');
                        if (centsEl && !isNaN(parseInt(centsEl.innerText))) {
                            price += parseInt(centsEl.innerText) / 100;
                        }
                    }
                }

                if (!price || isNaN(price) || price === 0) {
                    const priceMatch = htmlSource.match(/"price"\s*:\s*(\d+(?:\.\d+)?)/i);
                    if (priceMatch) price = parseFloat(priceMatch[1]);
                }

                // 3. Extract Description
                let desc = '';
                const descEl = doc.querySelector('.ui-pdp-description__content') || doc.querySelector('.ui-vpp-description__content');
                if (descEl) {
                    desc = descEl.innerHTML.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '').trim();
                }
                if (!desc) {
                    const ogDescMatch = htmlSource.match(/<meta[^>]*property=["\']og:description["\'][^>]*content=["\'](.*?)["\']/i);
                    if (ogDescMatch) desc = ogDescMatch[1].replace(/&quot;/g, '"').replace(/&amp;/g, '&').trim();
                }

                // 4. Extract Brand & Specs
                let brand = '', sku = '', tech = '';
                let specs = [];
                const specRows = doc.querySelectorAll('.ui-pdp-specs__table tr, .andes-table__row');
                specRows.forEach(row => {
                    const keyEl = row.querySelector('th');
                    const valEl = row.querySelector('td');
                    if (keyEl && valEl) {
                        const k = keyEl.innerText.trim();
                        const v = valEl.innerText.trim();
                        tech += '<b>' + k + '</b>: ' + v + '<br>';
                        specs.push(k + ': ' + v);
                        if (k.toLowerCase().includes('marca')) brand = v;
                        if (k.toLowerCase().includes('sku') || k.toLowerCase().includes('modelo')) sku = v;
                    }
                });

                // 5. Extract Images
                let uniqueImgs = [];
                const ogImageMatch = htmlSource.match(/<meta\s+property="og:image"\s+content="([^"]+)"/i);
                if (ogImageMatch) {
                    uniqueImgs.push(ogImageMatch[1]);
                }

                const allMlImgs = htmlSource.match(/https:\/\/http2\.mlstatic\.com\/D_NQ_NP_[^"'\s\)]+/gi) || [];
                allMlImgs.forEach(rawUrl => {
                    let clean = rawUrl.replace('D_NQ_NP_2X_', 'D_NQ_NP_');
                    clean = clean.replace(/-[a-zA-Z]\.(webp|jpg|jpeg|png)$/i, '-O.jpg');
                    if (!uniqueImgs.includes(clean) && !clean.includes('avatar')) {
                        uniqueImgs.push(clean);
                    }
                });

                uniqueImgs = [...new Set(uniqueImgs)].slice(0, 15);

                const catEl = doc.querySelector('.andes-breadcrumb__item:last-child');
                const category = catEl ? catEl.innerText.trim() : '';
                
                const soldEl = doc.querySelector('.ui-pdp-header__subtitle');
                let sold = 0;
                if (soldEl && soldEl.innerText.includes('vendido')) {
                    const sMatch = soldEl.innerText.match(/(\d+)/);
                    if (sMatch) sold = parseInt(sMatch[1]);
                }

                document.getElementById('ml_loading').classList.add('hidden');
                
                if (title) document.getElementById('prod_title').value = title;
                if (price > 0) {
                    document.getElementById('prod_price').value = 'R$ ' + price.toFixed(2).replace('.', ',');
                    document.getElementById('prod_discount_price').value = 'R$ ' + (price * 1.35).toFixed(2).replace('.', ',');
                }
                if (desc) document.getElementById('prod_description').value = desc;
                if (ml_id) document.getElementById('prod_ml_id').value = ml_id;
                if (category) document.getElementById('prod_category').value = category;
                if (brand) document.getElementById('prod_brand').value = brand;
                if (sku) document.getElementById('prod_sku').value = sku;
                
                if (uniqueImgs.length > 0) {
                    document.getElementById('prod_scraped_images').value = JSON.stringify(uniqueImgs);
                    const preview = document.getElementById('scraped_images_preview');
                    preview.innerHTML = '';
                    uniqueImgs.forEach(img => {
                        preview.innerHTML += '<img src="' + img + '" class="w-16 h-16 rounded object-cover border cursor-pointer hover:opacity-50" onclick="this.remove(); updateScrapedImages()" data-src="' + img + '" title="Clique para Remover">';
                    });
                }
                
                document.getElementById('prod_variations').value = '';
                if (typeof loadVariationsToUi === 'function') loadVariationsToUi();

                alert("✅ Dados extraídos do código fonte com sucesso!\n\nVerifique os dados preenchidos na aba 'Dados Básicos & Variações'.");
                switchTab('tab-geral');
                
            } catch(e) {
                document.getElementById('ml_loading').classList.add('hidden');
                alert("Erro ao extrair código fonte: " + e.message);
            }
        }

        function openAddModal() {
            document.getElementById('modal-title').innerText = 'Adicionar Produto';
            document.getElementById('prod_id').value = '';
            currentReviews = [];
            renderReviews();
            document.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(el => el.value = '');
            if (document.getElementById('prod_flash_sale_timer')) document.getElementById('prod_flash_sale_timer').value = '';
            
            document.getElementById('prod_scraped_images').value = '[]';
            document.getElementById('scraped_images_preview').innerHTML = '';
            document.getElementById('prod_variations').value = '';
            updateCalculatedDiscountBadge();
            loadVariationsToUi();
            document.getElementById('modal-add').classList.remove('hidden');
        }

        function openEditModal(prodId) {
            const prod = (window.productsMap && window.productsMap[prodId]) ? window.productsMap[prodId] : (typeof prodId === 'object' ? prodId : null);
            if (!prod) return;

            document.getElementById('modal-title').innerText = 'Editar Produto';
            document.getElementById('prod_id').value = prod.id;
            
            document.getElementById('prod_title').value = prod.title || '';
            document.getElementById('prod_price').value = prod.price || '';
            document.getElementById('prod_discount_price').value = prod.discount_price || '';
            document.getElementById('prod_description').value = prod.description || '';
            document.getElementById('prod_features').value = prod.features || '';
            document.getElementById('prod_packing_list').value = prod.packing_list || '';
            document.getElementById('prod_rating').value = prod.rating || '';
            document.getElementById('prod_sold').value = prod.sold || '';
            document.getElementById('prod_total_reviews').value = prod.total_reviews || '';
            document.getElementById('prod_bottom_bar_text').value = prod.bottom_bar_text || '';
            if (document.getElementById('prod_flash_sale_timer')) document.getElementById('prod_flash_sale_timer').value = prod.flash_sale_timer || '';
            
            document.getElementById('prod_ml_id').value = prod.ml_id || '';
            document.getElementById('prod_category').value = prod.category || '';
            document.getElementById('prod_brand').value = prod.brand || '';
            document.getElementById('prod_sku').value = prod.sku || '';

            document.getElementById('prod_variations').value = prod.variations || '';
            document.getElementById('prod_scraped_images').value = prod.image || '[]';
            document.getElementById('scraped_images_preview').innerHTML = '';
            
            let imgs = [];
            try {
                imgs = typeof prod.image === 'string' ? JSON.parse(prod.image) : prod.image;
            } catch(e) {}
            if (Array.isArray(imgs) && imgs.length > 0) {
                const preview = document.getElementById('scraped_images_preview');
                imgs.forEach(img => {
                    preview.innerHTML += '<img src="' + img + '" class="w-16 h-16 rounded object-cover border cursor-pointer hover:opacity-50" onclick="this.remove(); updateScrapedImages()" data-src="' + img + '" title="Remover">';
                });
            }

            currentReviews = prod.reviews_json ? (typeof prod.reviews_json === 'string' ? JSON.parse(prod.reviews_json) : prod.reviews_json) : [];
            
            if ((!currentReviews || currentReviews.length === 0) && prod.total_reviews) {
                autoGenerateFromTotalInput();
            } else {
                renderReviews();
            }
            updateCalculatedDiscountBadge();
            loadVariationsToUi();
            document.getElementById('modal-add').classList.remove('hidden');
        }

        let optionImagesState = {};

        function getAvailableProductImages() {
            let prodImages = [];
            try {
                const scraped = JSON.parse(document.getElementById('prod_scraped_images').value || '[]');
                if (Array.isArray(scraped)) prodImages = prodImages.concat(scraped);
            } catch(e) {}
            return prodImages;
        }

        function renderOptionImageBinders() {
            const container = document.getElementById('option_image_binders_container');
            if (!container) return;

            const rawOpts = document.getElementById('prod_options_input').value.trim();
            const namesArr = rawOpts ? rawOpts.split(',').map(s => s.trim()).filter(s => s.length > 0) : [];
            const prodImages = getAvailableProductImages();

            if (namesArr.length === 0) {
                container.innerHTML = '';
                optionImagesState = {};
                return;
            }

            const photosPerOption = Math.max(1, Math.floor(prodImages.length / namesArr.length));
            namesArr.forEach((name, idx) => {
                if (!optionImagesState[name] || !Array.isArray(optionImagesState[name])) {
                    if (prodImages.length > 0) {
                        const start = idx * photosPerOption;
                        const end = (idx === namesArr.length - 1) ? prodImages.length : start + photosPerOption;
                        const assigned = prodImages.slice(start, end);
                        optionImagesState[name] = assigned.length > 0 ? assigned : [prodImages[0]];
                    } else {
                        optionImagesState[name] = [];
                    }
                }
            });

            Object.keys(optionImagesState).forEach(key => {
                if (!namesArr.includes(key)) {
                    delete optionImagesState[key];
                }
            });

            if (prodImages.length === 0) {
                container.innerHTML = '<p class="text-xs text-gray-500 italic">Envie ou importe fotos do produto acima para associá-las às variações.</p>';
                return;
            }

            let html = '<div class="text-xs font-bold text-purple-900 mb-1">Vincular fotos por variação (Clique nas fotos para incluir/remover):</div>';

            namesArr.forEach((name) => {
                const selectedList = optionImagesState[name] || [];
                html += '<div class="bg-gray-50 p-2.5 rounded-lg border border-purple-100 space-y-1.5">';
                html += '<div class="flex items-center justify-between text-xs font-bold text-gray-800">';
                html += '<span>🎨 ' + name + '</span>';
                html += '<span class="text-[11px] font-normal text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">' + selectedList.length + ' foto(s) selecionada(s)</span>';
                html += '</div>';

                html += '<div class="flex gap-2 overflow-x-auto pt-1 pb-1">';
                prodImages.forEach((imgUrl) => {
                    const isSelected = selectedList.includes(imgUrl);
                    const borderCls = isSelected ? 'border-2 border-purple-600 ring-2 ring-purple-300 scale-95' : 'border border-gray-200 opacity-50 grayscale hover:opacity-100 hover:grayscale-0';
                    const checkBadge = isSelected ? '<div class="absolute top-0 right-0 bg-purple-600 text-white w-4 h-4 rounded-bl flex items-center justify-center text-[10px] font-bold">✓</div>' : '';
                    
                    html += '<div onclick="toggleOptionImage(\'' + name.replace(/'/g, "\\'") + '\', \'' + imgUrl.replace(/'/g, "\\'") + '\')" class="relative shrink-0 cursor-pointer transition-all ' + borderCls + ' rounded-md overflow-hidden" title="Clique para vincular/desvincular">';
                    html += '<img src="' + imgUrl + '" class="w-14 h-14 object-cover">';
                    html += checkBadge;
                    html += '</div>';
                });
                html += '</div>';
                html += '</div>';
            });

            container.innerHTML = html;
        }

        function toggleOptionImage(optName, imgUrl) {
            if (!optionImagesState[optName]) optionImagesState[optName] = [];
            const idx = optionImagesState[optName].indexOf(imgUrl);
            if (idx >= 0) {
                optionImagesState[optName].splice(idx, 1);
            } else {
                optionImagesState[optName].push(imgUrl);
            }
            renderOptionImageBinders();
            updateVariationsJson();
        }

        function toggleVariationsBox() {
            const chk = document.getElementById('prod_enable_variations').checked;
            const container = document.getElementById('variations_input_container');
            if (chk) {
                container.classList.remove('hidden');
                const chkOpts = document.getElementById('prod_enable_options');
                const chkSizes = document.getElementById('prod_enable_sizes');
                const chkVoltages = document.getElementById('prod_enable_voltages');
                if (!chkOpts.checked && !chkSizes.checked && !chkVoltages.checked) {
                    chkOpts.checked = true;
                    toggleOptionsInput();
                } else {
                    updateVariationsJson();
                }
            } else {
                container.classList.add('hidden');
                document.getElementById('prod_variations').value = '';
            }
        }

        function toggleOptionsInput() {
            const chk = document.getElementById('prod_enable_options').checked;
            const box = document.getElementById('options_input_box');
            const input = document.getElementById('prod_options_input');
            if (chk) {
                box.classList.remove('hidden');
                renderOptionImageBinders();
            } else {
                box.classList.add('hidden');
            }
            updateVariationsJson();
        }

        function toggleSizesInput() {
            const chk = document.getElementById('prod_enable_sizes').checked;
            const box = document.getElementById('sizes_input_box');
            const input = document.getElementById('prod_sizes_input');
            if (chk) {
                box.classList.remove('hidden');
                if (!input.value.trim()) input.value = 'P, M, G, GG';
            } else {
                box.classList.add('hidden');
            }
            updateVariationsJson();
        }

        function toggleVoltagesInput() {
            const chk = document.getElementById('prod_enable_voltages').checked;
            const box = document.getElementById('voltages_input_box');
            const input = document.getElementById('prod_voltages_input');
            if (chk) {
                box.classList.remove('hidden');
                if (!input.value.trim()) input.value = '110V, 220V, Bivolt';
            } else {
                box.classList.add('hidden');
            }
            updateVariationsJson();
        }

        function updateVariationsJson() {
            const chkMain = document.getElementById('prod_enable_variations').checked;
            if (!chkMain) {
                document.getElementById('prod_variations').value = '';
                return;
            }

            const chkOpts = document.getElementById('prod_enable_options').checked;
            let finalOptsArr = [];
            if (chkOpts) {
                const rawOpts = document.getElementById('prod_options_input').value.trim();
                const namesArr = rawOpts ? rawOpts.split(',').map(s => s.trim()).filter(s => s.length > 0) : [];
                const prodImages = getAvailableProductImages();

                finalOptsArr = namesArr.map((name) => {
                    const selectedImgs = optionImagesState[name] && optionImagesState[name].length > 0
                        ? optionImagesState[name]
                        : (prodImages.length > 0 ? [prodImages[0]] : []);
                    
                    const coverImg = selectedImgs[0] || (prodImages[0] || '');
                    return {
                        name: name,
                        image: coverImg,
                        images: selectedImgs
                    };
                });
            }
            
            const chkSizes = document.getElementById('prod_enable_sizes').checked;
            let sizesArr = [];
            if (chkSizes) {
                const rawSizes = document.getElementById('prod_sizes_input').value.trim();
                sizesArr = rawSizes ? rawSizes.split(',').map(s => s.trim()).filter(s => s.length > 0) : [];
            }

            const chkVoltages = document.getElementById('prod_enable_voltages').checked;
            let voltagesArr = [];
            if (chkVoltages) {
                const rawVoltages = document.getElementById('prod_voltages_input').value.trim();
                voltagesArr = rawVoltages ? rawVoltages.split(',').map(s => s.trim()).filter(s => s.length > 0) : [];
            }

            const obj = {
                options: finalOptsArr,
                sizes: sizesArr,
                voltages: voltagesArr
            };
            document.getElementById('prod_variations').value = JSON.stringify(obj);
        }

        function loadVariationsToUi() {
            const raw = document.getElementById('prod_variations').value || '';
            const chkMain = document.getElementById('prod_enable_variations');
            const container = document.getElementById('variations_input_container');
            
            const chkOpts = document.getElementById('prod_enable_options');
            const boxOpts = document.getElementById('options_input_box');
            const optsInput = document.getElementById('prod_options_input');

            const chkSizes = document.getElementById('prod_enable_sizes');
            const boxSizes = document.getElementById('sizes_input_box');
            const sizesInput = document.getElementById('prod_sizes_input');

            const chkVoltages = document.getElementById('prod_enable_voltages');
            const boxVoltages = document.getElementById('voltages_input_box');
            const voltagesInput = document.getElementById('prod_voltages_input');
            
            if (!raw || raw.trim() === '') {
                chkMain.checked = false;
                container.classList.add('hidden');
                
                chkOpts.checked = false;
                boxOpts.classList.add('hidden');
                optsInput.value = '';
                optionImagesState = {};
                renderOptionImageBinders();

                chkSizes.checked = false;
                boxSizes.classList.add('hidden');
                sizesInput.value = 'P, M, G, GG';

                chkVoltages.checked = false;
                boxVoltages.classList.add('hidden');
                voltagesInput.value = '110V, 220V, Bivolt';
                return;
            }
            
            chkMain.checked = true;
            container.classList.remove('hidden');
            
            try {
                const parsed = JSON.parse(raw);
                
                // Cores / Opções
                if (parsed.options && Array.isArray(parsed.options) && parsed.options.length > 0) {
                    chkOpts.checked = true;
                    boxOpts.classList.remove('hidden');
                    optsInput.value = parsed.options.map(o => o.name || o).join(', ');
                    
                    optionImagesState = {};
                    parsed.options.forEach(o => {
                        if (o.name) {
                            if (Array.isArray(o.images) && o.images.length > 0) {
                                optionImagesState[o.name] = o.images;
                            } else if (o.image) {
                                optionImagesState[o.name] = [o.image];
                            }
                        }
                    });
                    renderOptionImageBinders();
                } else {
                    chkOpts.checked = false;
                    boxOpts.classList.add('hidden');
                    optsInput.value = '';
                    optionImagesState = {};
                    renderOptionImageBinders();
                }

                // Tamanhos
                if (parsed.sizes && Array.isArray(parsed.sizes) && parsed.sizes.length > 0) {
                    chkSizes.checked = true;
                    boxSizes.classList.remove('hidden');
                    sizesInput.value = parsed.sizes.join(', ');
                } else if (typeof parsed === 'string' && parsed.trim() !== '') {
                    chkSizes.checked = true;
                    boxSizes.classList.remove('hidden');
                    sizesInput.value = parsed;
                } else {
                    chkSizes.checked = false;
                    boxSizes.classList.add('hidden');
                    sizesInput.value = 'P, M, G, GG';
                }

                // Voltagens
                if (parsed.voltages && Array.isArray(parsed.voltages) && parsed.voltages.length > 0) {
                    chkVoltages.checked = true;
                    boxVoltages.classList.remove('hidden');
                    voltagesInput.value = parsed.voltages.join(', ');
                } else {
                    chkVoltages.checked = false;
                    boxVoltages.classList.add('hidden');
                    voltagesInput.value = '110V, 220V, Bivolt';
                }
            } catch(e) {
                chkOpts.checked = false;
                boxOpts.classList.add('hidden');
                optsInput.value = '';
                optionImagesState = {};
                renderOptionImageBinders();

                chkSizes.checked = true;
                boxSizes.classList.remove('hidden');
                sizesInput.value = raw;

                chkVoltages.checked = false;
                boxVoltages.classList.add('hidden');
            }
        }

        function updateCalculatedDiscountBadge() {
            const priceStr = document.getElementById('prod_price').value || '';
            const origStr = document.getElementById('prod_discount_price').value || '';
            const priceFloat = parseFloat(priceStr.replace('R$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
            const origFloat = parseFloat(origStr.replace('R$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
            
            const badgeEl = document.getElementById('calc_discount_badge');
            if (badgeEl) {
                if (origFloat > 0 && origFloat > priceFloat) {
                    const pct = Math.round(((origFloat - priceFloat) / origFloat) * 100);
                    const savings = (origFloat - priceFloat).toFixed(2).replace('.', ',');
                    badgeEl.innerHTML = `<div class="p-2 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between text-xs text-green-800"><span class="font-bold">⚡ Desconto Calculado: -${pct}% OFF</span><span>Economia de R$ ${savings} para o cliente</span></div>`;
                } else {
                    badgeEl.innerHTML = `<div class="p-1.5 bg-gray-50 border border-gray-200 rounded text-xs text-gray-500">💡 O desconto % é calculado automaticamente quando o Preço Antigo for maior que o Preço de Venda.</div>`;
                }
            }
        }
    </script>
</body>
</html>
