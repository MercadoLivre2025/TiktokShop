<?php
require_once '../api/db.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_order') {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    exit;
}

$stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-gray-50">

        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold">Cliente</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Contato</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Endereço Completo</th>
                                    <th scope="col" class="px-6 py-4 font-semibold">Data</th>
                                    <th scope="col" class="px-6 py-4 font-semibold text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if(empty($customers)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            Nenhum cliente iniciou checkout ainda.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($customers as $c): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 align-top">
                                                <div class="font-bold text-gray-900"><?= htmlspecialchars($c['name']) ?></div>
                                                <div class="text-gray-500 mt-1">CPF: <?= htmlspecialchars($c['cpf']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 align-top">
                                                <div class="text-gray-900 font-medium">
                                                    <?php
                                                    $phone = preg_replace('/\D/', '', $c['phone']);
                                                    if ($phone) {
                                                        echo "<a href='https://wa.me/55{$phone}' target='_blank' class='text-green-600 hover:underline flex items-center gap-1'>";
                                                        echo "<svg class='w-4 h-4' fill='currentColor' viewBox='0 0 24 24'><path d='M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z'/></svg>";
                                                        echo htmlspecialchars($c['phone']) . "</a>";
                                                    } else {
                                                        echo "-";
                                                    }
                                                    ?>
                                                </div>
                                                <div class="text-gray-500 mt-1"><?= htmlspecialchars($c['email'] ?: '-') ?></div>
                                            </td>
                                            <td class="px-6 py-4 align-top text-gray-700">
                                                <?= htmlspecialchars($c['address']) ?>, <?= htmlspecialchars($c['number']) ?><br>
                                                <?= htmlspecialchars($c['neighborhood']) ?><br>
                                                <?= htmlspecialchars($c['city']) ?> - <?= htmlspecialchars($c['state']) ?><br>
                                                CEP: <?= htmlspecialchars($c['cep']) ?>
                                            </td>
                                            <td class="px-6 py-4 align-top text-gray-500 whitespace-nowrap">
                                                <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 align-top text-center">
                                                <button onclick="excluirCliente(<?= $c['id'] ?>)" class="inline-flex items-center px-2.5 py-1.5 rounded text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition">
                                                    Excluir
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    function excluirCliente(id) {
        if(confirm('Tem certeza que deseja EXCLUIR este registro? Essa ação não pode ser desfeita.')) {
            fetch('clientes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete_order&id=' + id
            }).then(() => window.location.reload());
        }
    }
    </script>
</body>
</html>

