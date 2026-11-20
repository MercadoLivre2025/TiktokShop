<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/compiler.php';

$message = '';
$error = '';

// Handle Category Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $image = trim($_POST['image'] ?? '');
        
        if (empty($name)) {
            $error = "O nome da categoria é obrigatório!";
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
                    $stmt->execute([$name, $image]);
                    $message = "Categoria adicionada com sucesso!";
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, image = ? WHERE id = ?");
                    $stmt->execute([$name, $image, $id]);
                    $message = "Categoria atualizada com sucesso!";
                }
                compile_static_site($pdo);
            } catch (PDOException $e) {
                $error = "Erro ao salvar categoria: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Categoria excluída com sucesso!";
                compile_static_site($pdo);
            } catch (PDOException $e) {
                $error = "Erro ao excluir categoria: " . $e->getMessage();
            }
        }
    }
}

// Fetch all categories with product count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON (p.category = c.name OR p.category LIKE CONCAT('%', c.name, '%'))
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-8">
            <div class="max-w-6xl mx-auto space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciador de Categorias</h1>
                        <p class="text-xs text-gray-500 mt-1">Crie e organize as categorias exibidas na aba de categorias em /loja.html</p>
                    </div>
                    <button onclick="openCategoryModal()" class="bg-pink-600 hover:bg-pink-700 text-white font-bold px-4 py-2.5 rounded-xl text-sm flex items-center justify-center gap-2 shadow-sm transition active:scale-95">
                        ➕ Nova Categoria
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-semibold flex items-center justify-between">
                        <span>✅ <?= htmlspecialchars($message) ?></span>
                        <button onclick="this.parentElement.remove()" class="text-green-600 font-bold">✕</button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-semibold flex items-center justify-between">
                        <span>❌ <?= htmlspecialchars($error) ?></span>
                        <button onclick="this.parentElement.remove()" class="text-red-600 font-bold">✕</button>
                    </div>
                <?php endif; ?>

                <!-- Grid of Categories -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($categories)): ?>
                        <div class="col-span-full bg-white p-8 rounded-2xl border border-gray-200 text-center text-gray-500">
                            Nenhuma categoria cadastrada ainda. Clique em <b>+ Nova Categoria</b> para adicionar a primeira!
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center justify-between gap-3 hover:shadow-md transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-14 h-14 rounded-xl bg-gray-100 border overflow-hidden shrink-0 flex items-center justify-center">
                                        <?php if ($cat['image']): ?>
                                            <img src="<?= htmlspecialchars($cat['image']) ?>" class="w-full h-full object-cover" alt="Categoria">
                                        <?php else: ?>
                                            <span class="text-2xl">📂</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($cat['name']) ?></h3>
                                        <span class="inline-block bg-pink-50 text-pink-700 text-[11px] font-bold px-2 py-0.5 rounded-full mt-1">
                                            <?= $cat['product_count'] ?> produto(s)
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button onclick='editCategory(<?= json_encode($cat) ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar">✏️</button>
                                    <form method="POST" onsubmit="return confirm('Deseja realmente excluir esta categoria?')" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Excluir">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form -->
    <div id="catModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl relative animate-in fade-in zoom-in duration-200">
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-900">Nova Categoria</h3>
                <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600 font-bold text-xl">✕</button>
            </div>
            
            <form method="POST" class="space-y-4 mt-4">
                <input type="hidden" name="action" id="catAction" value="add">
                <input type="hidden" name="id" id="catId" value="0">
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome da Categoria</label>
                    <input type="text" name="name" id="catName" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-pink-500 focus:outline-none" placeholder="Ex: Roupas, Eletrônicos, Calçados">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">URL da Imagem / Ícone (Opcional)</label>
                    <input type="text" name="image" id="catImage" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-pink-500 focus:outline-none" placeholder="Ex: https://.../categoria.jpg">
                    <p class="text-[11px] text-gray-500 mt-1">Cole a URL de uma imagem para representar esta categoria na loja.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t">
                    <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-sm font-bold bg-pink-600 text-white hover:bg-pink-700 shadow-sm active:scale-95">Salvar Categoria</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCategoryModal() {
            document.getElementById('catAction').value = 'add';
            document.getElementById('catId').value = '0';
            document.getElementById('catName').value = '';
            document.getElementById('catImage').value = '';
            document.getElementById('modalTitle').innerText = 'Nova Categoria';
            const modal = document.getElementById('catModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCategoryModal() {
            const modal = document.getElementById('catModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function editCategory(cat) {
            document.getElementById('catAction').value = 'edit';
            document.getElementById('catId').value = cat.id;
            document.getElementById('catName').value = cat.name || '';
            document.getElementById('catImage').value = cat.image || '';
            document.getElementById('modalTitle').innerText = 'Editar Categoria';
            const modal = document.getElementById('catModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    </script>
</body>
</html>
