<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_config_active = in_array($current_page, ['settings.php', 'pixels.php', 'gateway.php', 'protecao.php', 'import_ml.php', 'import_reviews_ml.php']);
?>
<style>
    .admin-sidebar-bg {
        background-color: #0f172a;
    }
    .admin-nav-link {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .admin-nav-link-active {
        background: linear-gradient(90deg, #fe2c55 0%, #e11d48 100%) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 15px rgba(254, 44, 85, 0.4) !important;
    }
</style>

<!-- Sidebar Desktop -->
<aside class="w-64 admin-sidebar-bg text-white border-r border-gray-800 hidden md:flex flex-col shrink-0 relative z-20 shadow-2xl">
    <!-- Header com Logo do TikTok Shop -->
    <div class="h-20 flex items-center px-5 border-b border-white/10 justify-between backdrop-blur-md bg-black/40">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#00f2fe] via-[#fe2c55] to-[#ff0050] p-[2px] shadow-lg shadow-pink-500/20 shrink-0">
                <div class="w-full h-full bg-[#0b0d12] rounded-[10px] flex items-center justify-center">
                    <!-- TikTok SVG Icon -->
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-5.201 1.743 2.895 2.895 0 0 1 3.209-4.51V9.431a6.34 6.34 0 0 0-4.832 2.127 6.345 6.345 0 0 0 .548 8.905 6.34 6.34 0 0 0 8.723-1.074c.913-1.18 1.408-2.64 1.408-4.145V8.528a8.214 8.214 0 0 0 4.757 1.503V6.586a4.834 4.834 0 0 1-1.397.1z"/>
                    </svg>
                </div>
            </div>
            <div>
                <span class="text-base font-extrabold tracking-tight text-white block leading-tight">TikTok Shop</span>
                <span class="text-[11px] font-semibold text-pink-400 tracking-wide uppercase">Painel Admin</span>
            </div>
        </div>
    </div>
    
    <!-- Navegação -->
    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto backdrop-blur-sm bg-black/30">
        <!-- Dashboard -->
        <a href="index.php" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'index.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5 opacity-90 text-cyan-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard</span>
        </a>

        <!-- Clientes -->
        <a href="clientes.php" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'clientes.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5 opacity-90 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Clientes</span>
        </a>

        <!-- Produtos -->
        <a href="products.php" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'products.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5 opacity-90 text-pink-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Produtos (Catálogo)</span>
        </a>

        <!-- Categorias -->
        <a href="categories.php" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'categories.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5 opacity-90 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span>Categorias</span>
        </a>

        <!-- Mensagens / Chat -->
        <a href="chat.php" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'chat.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
            <svg class="w-5 h-5 opacity-90 text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Mensagens (Chat)</span>
        </a>

        <!-- Configurações (Sub-menu) -->
        <div class="space-y-1">
            <button type="button" onclick="toggleConfigSubmenu()" class="admin-nav-link w-full flex items-center justify-between px-4 py-2.5 rounded-xl font-medium text-sm <?= $is_config_active ? 'bg-white/15 text-white font-bold' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 opacity-90 text-purple-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Configurações</span>
                </div>
                <svg id="config-chevron" class="w-4 h-4 transition-transform duration-200 <?= $is_config_active ? 'rotate-180 text-pink-400' : 'text-gray-400' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <!-- Lista Sub-Opções -->
            <div id="config-submenu" class="pl-4 space-y-1 border-l-2 border-pink-500/30 ml-4 <?= $is_config_active ? '' : 'hidden' ?>">
                <a href="settings.php" class="admin-nav-link flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'settings.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <svg class="w-4 h-4 text-pink-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Configurações Gerais</span>
                </a>
                <a href="pixels.php" class="admin-nav-link flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'pixels.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                    <span>Pixels (FB / TikTok)</span>
                </a>
                <a href="gateway.php" class="admin-nav-link flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'gateway.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <span>Gateway / Pagamentos</span>
                </a>
                <a href="protecao.php" class="admin-nav-link flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'protecao.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>"><span>🛡️ Proteção / Redirecionamento</span></a>
            </div>
        </div>



        <!-- Ver Loja -->
        <a href="../loja.html" target="_blank" class="admin-nav-link flex items-center gap-3 px-4 py-2.5 text-cyan-400 hover:bg-cyan-950/40 rounded-xl font-bold mt-4 border border-cyan-500/20">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            <span>👁 Ver Loja Ao Vivo</span>
        </a>
    </nav>

    <!-- Rodapé -->
    <div class="p-4 border-t border-white/10 backdrop-blur-md bg-black/40">
        <a href="logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-red-400 hover:bg-red-950/40 hover:text-red-300 rounded-xl transition-colors border border-red-500/20">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Sair do sistema</span>
        </a>
    </div>
</aside>

<!-- Header Mobile -->
<header class="h-16 admin-sidebar-bg text-white border-b border-gray-800 flex items-center justify-between px-4 md:hidden shrink-0 shadow-lg z-30">
    <div class="flex items-center gap-3">
        <button type="button" onclick="toggleMobileMenu()" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-pink-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-5.201 1.743 2.895 2.895 0 0 1 3.209-4.51V9.431a6.34 6.34 0 0 0-4.832 2.127 6.345 6.345 0 0 0 .548 8.905 6.34 6.34 0 0 0 8.723-1.074c.913-1.18 1.408-2.64 1.408-4.145V8.528a8.214 8.214 0 0 0 4.757 1.503V6.586a4.834 4.834 0 0 1-1.397.1z"/>
                </svg>
            </div>
            <span class="font-bold text-white text-base">TikTok Shop</span>
        </div>
    </div>
    <a href="logout.php" class="text-xs font-semibold bg-red-600/80 text-white px-3 py-1.5 rounded-lg hover:bg-red-600">Sair</a>
</header>

<!-- Mobile Menu Drawer (Fundo Escuro com Logo TikTok) -->
<div id="mobile-menu-drawer" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden md:hidden" onclick="toggleMobileMenu()">
    <div class="w-72 admin-sidebar-bg text-white h-full flex flex-col p-4 space-y-2 shadow-2xl border-r border-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-4 border-b border-white/10">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-pink-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-5.201 1.743 2.895 2.895 0 0 1 3.209-4.51V9.431a6.34 6.34 0 0 0-4.832 2.127 6.345 6.345 0 0 0 .548 8.905 6.34 6.34 0 0 0 8.723-1.074c.913-1.18 1.408-2.64 1.408-4.145V8.528a8.214 8.214 0 0 0 4.757 1.503V6.586a4.834 4.834 0 0 1-1.397.1z"/>
                    </svg>
                </div>
                <span class="text-base font-bold text-white">TikTok Shop</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg text-gray-400 hover:bg-white/10 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto space-y-1.5 pt-2">
            <a href="index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'index.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                <svg class="w-5 h-5 text-cyan-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
            <a href="clientes.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'clientes.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Clientes
            </a>
            <a href="products.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'products.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                <svg class="w-5 h-5 text-pink-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Produtos (Catálogo)
            </a>
            <a href="categories.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium text-sm <?= ($current_page === 'categories.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Categorias
            </a>

            <!-- Configurações Mobile -->
            <div>
                <button type="button" onclick="toggleMobileConfigSubmenu()" class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl font-medium text-sm <?= $is_config_active ? 'bg-white/15 text-white font-bold' : 'text-gray-300 hover:bg-white/10' ?>">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-purple-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Configurações</span>
                    </div>
                    <svg id="mobile-config-chevron" class="w-4 h-4 transition-transform duration-200 <?= $is_config_active ? 'rotate-180 text-pink-400' : 'text-gray-400' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div id="mobile-config-submenu" class="pl-4 space-y-1 mt-1 border-l-2 border-pink-500/30 ml-4 <?= $is_config_active ? '' : 'hidden' ?>">
                    <a href="settings.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'settings.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                        <svg class="w-4 h-4 text-pink-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Configurações Gerais</span>
                    </a>
                    <a href="pixels.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'pixels.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                        <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                        <span>Pixels (FB / TikTok)</span>
                    </a>
                    <a href="gateway.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'gateway.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10' ?>">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <span>Gateway / Pagamentos</span>
                    </a>
                <a href="protecao.php" class="admin-nav-link flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium <?= ($current_page === 'protecao.php') ? 'admin-nav-link-active' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>"><span>🛡️ Proteção / Redirecionamento</span></a>
                </div>
            </div>

            <a href="../loja.html" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-cyan-400 font-bold">👁 VER LOJA</a>
        </nav>

        <div class="pt-4 border-t border-white/10">
            <a href="logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-red-400">Sair do sistema</a>
        </div>
    </div>
</div>

<script>
function toggleConfigSubmenu() {
    var sub = document.getElementById('config-submenu');
    var chev = document.getElementById('config-chevron');
    if (sub) {
        sub.classList.toggle('hidden');
        if (chev) chev.classList.toggle('rotate-180');
    }
}
function toggleMobileConfigSubmenu() {
    var sub = document.getElementById('mobile-config-submenu');
    var chev = document.getElementById('mobile-config-chevron');
    if (sub) {
        sub.classList.toggle('hidden');
        if (chev) chev.classList.toggle('rotate-180');
    }
}
function toggleMobileMenu() {
    var drawer = document.getElementById('mobile-menu-drawer');
    if (drawer) {
        drawer.classList.toggle('hidden');
    }
}
</script>
