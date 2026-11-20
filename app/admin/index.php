<?php
require_once '../api/db.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_paid') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE customers SET status = 'pago' WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($_POST['action'] === 'delete_order') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($_POST['action'] === 'clear_visitors') {
        $pdo->exec("TRUNCATE TABLE live_visitors");
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Telemetria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        /* Smooth number transition */
        .live-num { transition: color 0.3s ease; }
        .live-num.updated { color: #16a34a; }
        /* Table row animation for new rows */
        @keyframes fadeInRow {
            from { background-color: #f0fdf4; }
            to   { background-color: transparent; }
        }
        .row-new { animation: fadeInRow 1.5s ease forwards; }
        /* Pulse dot */
        @keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }
        .animate-ping { animation: ping 1s cubic-bezier(0,0,0.2,1) infinite; }
    </style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">

        <div class="flex-1 p-6 md:p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard de Desempenho</h1>
                    <span id="last-update" class="text-xs text-gray-400">Atualizando...</span>
                </div>

                <!-- Live KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <!-- Visitantes Online (índice) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-500 text-sm">Na Loja (Ao Vivo)</h3>
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                        </div>
                        <p id="kpi-online-index" class="text-4xl font-extrabold text-gray-900 live-num">—</p>
                        <p class="text-sm text-gray-500 mt-2">Navegando no índice</p>
                    </div>

                    <!-- Em Checkout Online -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-500 text-sm">No Checkout (Ao Vivo)</h3>
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                            </span>
                        </div>
                        <p id="kpi-online-checkout" class="text-4xl font-extrabold text-gray-900 live-num">—</p>
                        <p class="text-sm text-gray-500 mt-2">Preenchendo dados</p>
                    </div>

                    <!-- Visitantes Hoje -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-500 text-sm">Visitantes (Hoje)</h3>
                            <button onclick="limparVisitantesHoje()" title="Limpar contador de visitantes" class="p-2 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-100 transition-all flex items-center justify-center border border-transparent cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="kpi-visitors-today" class="text-4xl font-extrabold text-gray-900">—</p>
                        <p class="text-sm text-gray-500 mt-2">Limpa às 00:00 (BRT)</p>
                    </div>

                    <!-- Checkouts Hoje -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-500 text-sm">Checkouts (Hoje)</h3>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <p id="kpi-checkouts-today" class="text-4xl font-extrabold text-gray-900">—</p>
                        <p class="text-sm text-gray-500 mt-2">Limpa às 00:00 (BRT)</p>
                    </div>

                </div>

                <!-- Tabela de Pedidos Recentes -->
                <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-900">Pedidos Recentes</h2>
                        <span id="orders-badge" class="text-xs text-gray-400"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3">Cliente</th>
                                    <th class="px-6 py-3">Produto</th>
                                    <th class="px-6 py-3">Endereço</th>
                                    <th class="px-6 py-3">Data/Hora (BRT)</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody" class="divide-y divide-gray-100">
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Carregando pedidos...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
    // ─── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setNum(id, val) {
        const el = document.getElementById(id);
        if (!el) return;
        const prev = el.textContent;
        el.textContent = val;
        if (prev !== String(val) && prev !== '—') {
            el.classList.add('updated');
            setTimeout(() => el.classList.remove('updated'), 800);
        }
    }

    // ─── Order actions ───────────────────────────────────────────────────────────
    window.marcarComoPago = function(id) {
        if (!confirm('Marcar este pedido como pago?')) return;
        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_paid&id=' + id
        })
        .then(r => r.json())
        .then(() => fetchDashboard(true))
        .catch(() => fetchDashboard(true));
    };

    window.limparVisitantesHoje = function() {
        if (!confirm('Deseja realmente zerar a contagem de visitantes de hoje?')) return;
        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear_visitors'
        })
        .then(r => r.json())
        .then(() => fetchDashboard(true))
        .catch(() => fetchDashboard(true));
    };

    window.excluirPedido = function(id) {
        if (!confirm('Excluir este pedido? Essa ação não pode ser desfeita.')) return;
        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete_order&id=' + id
        })
        .then(r => r.json())
        .then(() => fetchDashboard(true))
        .catch(() => fetchDashboard(true));
    };

    // ─── Render orders table ─────────────────────────────────────────────────────
    let lastOrderIds = [];

    function renderOrders(orders) {
        const tbody = document.getElementById('orders-tbody');
        const badge = document.getElementById('orders-badge');

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum pedido recente.</td></tr>';
            if (badge) badge.textContent = '';
            lastOrderIds = [];
            return;
        }

        const currentIds = orders.map(o => o.id);
        const newIds = currentIds.filter(id => !lastOrderIds.includes(id));

        let html = '';
        orders.forEach(c => {
            const isNew = newIds.includes(c.id);
            const isPago = c.status === 'pago';

            html += `<tr class="hover:bg-gray-50 ${isNew ? 'row-new' : ''}">
                <td class="px-6 py-4 align-top">
                    <div class="font-bold text-gray-900">${escHtml(c.name)}</div>
                    <div class="text-xs text-gray-500">${escHtml(c.cpf)}</div>
                    <div class="text-xs text-gray-400">${escHtml(c.phone)}</div>
                </td>
                <td class="px-6 py-4 align-top text-gray-700">${escHtml(c.product_name)}</td>
                <td class="px-6 py-4 align-top text-gray-600 text-xs">${escHtml(c.address)}</td>
                <td class="px-6 py-4 align-top text-gray-600 whitespace-nowrap">${escHtml(c.datetime)}</td>
                <td class="px-6 py-4 align-top text-center">
                    <div class="flex flex-col gap-2 items-center">
                        ${isPago
                            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Pago</span>`
                            : `<button onclick="marcarComoPago(${c.id})" class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition">Marcar Pago</button>`
                        }
                        <button onclick="excluirPedido(${c.id})" class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition">Excluir</button>
                    </div>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
        lastOrderIds = currentIds;

        if (badge) {
            const now = new Date();
            badge.textContent = 'Atualizado ' + now.toLocaleTimeString('pt-BR');
        }
    }

    // ─── Main polling function ───────────────────────────────────────────────────
    let isFirstLoad = true;

    function fetchDashboard(force) {
        fetch('../api/dashboard.php?t=' + Date.now(), {
            credentials: 'same-origin'
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            // Update live KPIs
            setNum('kpi-online-index',    data.online_index);
            setNum('kpi-online-checkout', data.online_checkout);
            setNum('kpi-visitors-today',  data.visitors_today);
            setNum('kpi-checkouts-today', data.checkouts_today);

            // Update orders table
            renderOrders(data.orders);

            // Update timestamp indicator
            const el = document.getElementById('last-update');
            if (el) {
                const now = new Date();
                el.textContent = '⟳ ' + now.toLocaleTimeString('pt-BR') + ' (ao vivo · 1s)';
            }

            isFirstLoad = false;
        })
        .catch(err => {
            console.warn('Dashboard fetch error:', err);
            const el = document.getElementById('last-update');
            if (el) el.textContent = '⚠ Erro ao atualizar — tentando novamente...';
        });
    }

    // ─── Start polling ────────────────────────────────────────────────────────
    // Immediate first load
    fetchDashboard(true);
    // Then every 1 second for real-time live visitor updates
    setInterval(fetchDashboard, 1000);
    </script>
</body>
</html>
