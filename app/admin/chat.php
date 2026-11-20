<?php
require_once '../api/db.php';
check_auth();

$store_name = get_setting($pdo, 'store_name', 'Minha Loja');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .chat-container { height: calc(100vh - 12rem); }
        .messages-area { height: calc(100% - 4rem); }
    </style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col bg-white">
            <header class="h-16 border-b border-gray-200 flex items-center justify-between px-6">
                <h1 class="text-xl font-bold text-gray-800">Mensagens dos Clientes</h1>
                <div class="flex items-center gap-3">
                    <button id="notifBtn" onclick="requestNotificationPermission()" class="text-xs bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 px-3 py-1.5 rounded-full font-medium flex items-center gap-1.5 cursor-pointer transition-colors">
                        🔔 Ativar Notificações
                    </button>
                    <button onclick="testNotificationSound()" title="Testar Som de Notificação" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 px-3 py-1.5 rounded-full font-medium flex items-center gap-1 cursor-pointer transition-colors">
                        🔊 Testar Som
                    </button>
                </div>
            </header>

            <div class="flex-1 flex overflow-hidden">
                <!-- Lista de Contatos -->
                <div class="w-80 border-r border-gray-200 flex flex-col bg-gray-50">
                    <div class="p-4 border-b border-gray-200 bg-white flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Conversas Ativas</h3>
                        <button onclick="deleteAllSessions()" title="Limpar Todas as Conversas" class="text-xs text-red-500 hover:text-red-700 font-medium hover:bg-red-50 px-2 py-1 rounded transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Limpar Todas
                        </button>
                    </div>
                    <div id="sessionList" class="flex-1 overflow-y-auto">
                        <!-- Renderizado via JS -->
                        <div class="p-4 text-center text-sm text-gray-500">Carregando conversas...</div>
                    </div>
                </div>

                <!-- Área do Chat -->
                <div class="flex-1 flex flex-col bg-white" id="chatArea" style="display:none;">
                    <!-- Cabecalho do Chat -->
                    <div class="h-16 border-b border-gray-200 flex items-center justify-between px-6 bg-white shadow-sm z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                C
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800" id="currentSessionName">Cliente</h3>
                                <p class="text-xs text-green-500">Online</p>
                            </div>
                        </div>
                        <button onclick="deleteCurrentSession()" title="Apagar esta conversa" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 border border-red-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Apagar Conversa
                        </button>
                    </div>

                    <!-- Mensagens -->
                    <div id="messagesList" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                        <!-- Renderizado via JS -->
                    </div>

                    <!-- Input -->
                    <div class="p-4 bg-white border-t border-gray-200">
                        <form id="replyForm" class="flex gap-2">
                            <input type="text" id="replyInput" class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#fe2c55]" placeholder="Digite uma resposta para o cliente..." autocomplete="off">
                            <button type="submit" class="bg-[#fe2c55] text-white rounded-full w-10 h-10 flex items-center justify-center hover:bg-[#e0264b] transition-colors shrink-0 shadow-sm">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tela Vazia -->
                <div class="flex-1 flex items-center justify-center bg-gray-50" id="emptyChat">
                    <div class="text-center text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <p class="text-lg">Selecione uma conversa para começar</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentSession = null;
        let prevUnreadTotal = 0;
        let isFirstLoad = true;
        let knownLastMessages = {};
        let audioCtx = null;
        let titleInterval = null;
        const originalTitle = document.title;

        function playChime() {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                const now = audioCtx.currentTime;
                
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.25, now);
                gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.start(now);
                osc1.stop(now + 0.35);
                
                const osc2 = audioCtx.createOscillator();
                const gain2 = audioCtx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.12);
                gain2.gain.setValueAtTime(0.35, now + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.6);
                osc2.connect(gain2);
                gain2.connect(audioCtx.destination);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.6);
            } catch(e) {
                console.log("Áudio não pôde tocar", e);
            }
        }

        function testNotificationSound() {
            playChime();
            if ("Notification" in window && Notification.permission === "granted") {
                new Notification("Teste de Notificação", {
                    body: "As notificações do chat estão funcionando perfeitamente!",
                    icon: 'tiktok_bg.jpg'
                });
            }
        }

        function requestNotificationPermission() {
            if ("Notification" in window) {
                Notification.requestPermission().then(permission => {
                    updateNotificationButton();
                    if (permission === 'granted') {
                        testNotificationSound();
                    }
                });
            } else {
                alert("Seu navegador não suporta notificações de trabalho.");
            }
        }

        function updateNotificationButton() {
            const btn = document.getElementById('notifBtn');
            if (!btn) return;
            if (!("Notification" in window)) {
                btn.innerHTML = '🔕 Notificações Não Suportadas';
                btn.className = 'text-xs bg-gray-100 text-gray-500 border border-gray-200 px-3 py-1.5 rounded-full font-medium';
            } else if (Notification.permission === "granted") {
                btn.innerHTML = '🔔 Notificações Ativas';
                btn.className = 'text-xs bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded-full font-medium flex items-center gap-1.5';
            } else {
                btn.innerHTML = '🔔 Ativar Notificações';
                btn.className = 'text-xs bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 px-3 py-1.5 rounded-full font-medium flex items-center gap-1.5 cursor-pointer transition-colors';
            }
        }

        function flashDocumentTitle() {
            if (document.hidden) {
                if (titleInterval) clearInterval(titleInterval);
                let flag = false;
                titleInterval = setInterval(() => {
                    document.title = flag ? `(1) 💬 NOVA MENSAGEM!` : originalTitle;
                    flag = !flag;
                }, 1000);
            }
        }

        window.addEventListener('focus', () => {
            if (titleInterval) {
                clearInterval(titleInterval);
                titleInterval = null;
            }
            document.title = originalTitle;
        });

        document.addEventListener('click', () => {
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }, { once: true });
        
        function formatTime(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function stripHtmlAndClean(html) {
            if (!html) return 'Nova conversa';
            let tmp = document.createElement('DIV');
            tmp.innerHTML = html;
            let text = tmp.textContent || tmp.innerText || '';
            text = text.replace(/[\r\n\t]+/g, ' ').replace(/\s+/g, ' ').trim();
            return text.length > 35 ? text.substring(0, 35) + '...' : text;
        }

        async function loadSessions() {
            try {
                const res = await fetch('../api/chat.php?action=get_sessions');
                const data = await res.json();
                
                const list = document.getElementById('sessionList');
                if (data.sessions && data.sessions.length > 0) {
                    let currentUnreadTotal = 0;
                    let hasNewCustomerMsg = false;
                    let latestMsgText = '';
                    let latestMsgSession = '';

                    data.sessions.forEach(s => {
                        const unread = parseInt(s.unread_count || 0);
                        currentUnreadTotal += unread;

                        const key = s.session_id;
                        const lastMsgId = s.last_message_date + '_' + s.last_message;
                        if (knownLastMessages[key] && knownLastMessages[key] !== lastMsgId && s.last_sender === 'customer') {
                            hasNewCustomerMsg = true;
                            latestMsgText = s.last_message;
                            latestMsgSession = s.session_id;
                        }
                        knownLastMessages[key] = lastMsgId;
                    });

                    if (isFirstLoad) {
                        prevUnreadTotal = currentUnreadTotal;
                        isFirstLoad = false;
                    } else {
                        if (currentUnreadTotal > prevUnreadTotal || hasNewCustomerMsg) {
                            playChime();
                            
                            if ("Notification" in window && Notification.permission === "granted") {
                                const title = latestMsgSession ? `Nova mensagem - Cliente #${latestMsgSession.substring(0,6)}` : 'Nova mensagem recebida!';
                                const body = stripHtmlAndClean(latestMsgText) || 'Você recebeu uma nova mensagem no chat.';
                                new Notification(title, { body: body });
                            }

                            flashDocumentTitle();
                        }
                        prevUnreadTotal = currentUnreadTotal;
                    }

                    list.innerHTML = data.sessions.map(s => {
                        const cleanPreview = stripHtmlAndClean(s.last_message);
                        const isBotLast = s.last_sender === 'bot';
                        const isAdminLast = s.last_sender === 'admin';
                        const senderPrefix = isBotLast ? '<span class="text-pink-500 font-semibold">(Luciana)</span> ' : (isAdminLast ? '<span class="text-blue-500 font-semibold">(Você)</span> ' : '');
                        
                        return `
                            <div onclick="openSession('${s.session_id}')" class="p-3 border-b border-gray-200/60 cursor-pointer hover:bg-white transition-all ${currentSession === s.session_id ? 'bg-pink-50/80 border-l-4 border-l-[#fe2c55]' : 'bg-gray-50/60'} group relative">
                                <div class="flex justify-between items-center mb-1">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center shrink-0">
                                            👤
                                        </div>
                                        <h4 class="font-bold text-gray-800 text-xs truncate">Cliente #${s.session_id.substring(0,6)}</h4>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="text-[10px] text-gray-400 font-medium">${formatTime(s.last_message_date)}</span>
                                        <button onclick="deleteSession('${s.session_id}', event)" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 p-0.5 rounded hover:bg-red-50 transition-all ml-0.5" title="Apagar conversa">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mt-1 min-w-0">
                                    <p class="text-[11px] text-gray-500 font-normal leading-tight truncate flex-1 min-w-0 pr-2" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${senderPrefix}${cleanPreview}</p>
                                    ${s.unread_count > 0 ? `<span class="bg-[#fe2c55] text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-full shrink-0 shadow-xs">${s.unread_count}</span>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Nenhuma mensagem ainda.</div>';
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function openSession(sessionId) {
            currentSession = sessionId;
            document.getElementById('emptyChat').style.display = 'none';
            document.getElementById('chatArea').style.display = 'flex';
            document.getElementById('currentSessionName').innerText = 'Cliente #' + sessionId.substring(0,6);
            
            await fetch('../api/chat.php?action=mark_read&session_id=' + sessionId);
            
            loadMessages();
            loadSessions();
        }

        async function deleteSession(sessionId, event) {
            if (event) event.stopPropagation();
            if (!confirm(`Deseja realmente apagar a conversa com o Cliente #${sessionId.substring(0,6)}?`)) return;

            try {
                const res = await fetch('../api/chat.php?action=delete_session&session_id=' + sessionId);
                const data = await res.json();
                if (data.success) {
                    if (currentSession === sessionId) {
                        currentSession = null;
                        document.getElementById('chatArea').style.display = 'none';
                        document.getElementById('emptyChat').style.display = 'flex';
                    }
                    loadSessions();
                } else {
                    alert('Erro ao apagar conversa: ' + (data.error || 'Erro desconhecido'));
                }
            } catch(e) {
                console.error(e);
                alert('Erro de conexão ao apagar conversa.');
            }
        }

        async function deleteCurrentSession() {
            if (!currentSession) return;
            deleteSession(currentSession);
        }

        async function deleteAllSessions() {
            if (!confirm('ATENÇÃO: Deseja realmente apagar TODAS as conversas do sistema? Esta ação não pode ser desfeita.')) return;
            try {
                const res = await fetch('../api/chat.php?action=delete_session&session_id=all');
                const data = await res.json();
                if (data.success) {
                    currentSession = null;
                    document.getElementById('chatArea').style.display = 'none';
                    document.getElementById('emptyChat').style.display = 'flex';
                    loadSessions();
                } else {
                    alert('Erro ao apagar conversas: ' + (data.error || 'Erro desconhecido'));
                }
            } catch(e) {
                console.error(e);
                alert('Erro de conexão ao apagar conversas.');
            }
        }

        async function loadMessages() {
            if (!currentSession) return;
            try {
                const res = await fetch('../api/chat.php?action=get_messages&session_id=' + currentSession);
                const data = await res.json();
                
                const list = document.getElementById('messagesList');
                
                if (data.messages) {
                    list.innerHTML = data.messages.map(m => {
                        const isCustomer = m.sender === 'customer';
                        const hasHtml = /<[a-z][\s\S]*>/i.test(m.message);
                        const isBot = m.sender === 'bot' || m.sender === 'bot_assistant' || (hasHtml && m.sender !== 'admin');
                        const isAdmin = m.sender === 'admin';

                        if (isAdmin) {
                            return `
                                <div class="flex justify-end my-2">
                                    <div class="max-w-[75%] bg-[#fe2c55] text-white rounded-2xl rounded-tr-xs px-4 py-2.5 shadow-sm">
                                        <p class="text-[10px] font-extrabold text-white/90 uppercase tracking-wider mb-1">Atendente (Você)</p>
                                        <div class="text-[13px] leading-relaxed">${m.message}</div>
                                        <span class="text-[10px] text-white/70 block mt-1 text-right font-medium">${formatTime(m.created_at)}</span>
                                    </div>
                                </div>
                            `;
                        }

                        if (isBot) {
                            return `
                                <div class="flex gap-2.5 items-start justify-start my-2">
                                    <div class="w-8 h-8 rounded-full bg-pink-100 border border-pink-200 text-[#fe2c55] flex items-center justify-center shrink-0 mt-0.5 text-xs shadow-xs font-bold">
                                        👩‍💼
                                    </div>
                                    <div class="max-w-[82%] bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-tl-xs px-4 py-2.5 shadow-xs">
                                        <p class="text-[10px] font-extrabold text-[#fe2c55] uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <span>Luciana (Atendente TikTok Shop)</span>
                                        </p>
                                        <div class="text-[13px] leading-relaxed text-gray-800">${m.message}</div>
                                        <span class="text-[10px] text-gray-400 block mt-1 text-right font-medium">${formatTime(m.created_at)}</span>
                                    </div>
                                </div>
                            `;
                        }

                        // Customer message (plain text)
                        return `
                            <div class="flex gap-2.5 items-start justify-start my-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center shrink-0 mt-0.5 text-xs shadow-xs">
                                    👤
                                </div>
                                <div class="max-w-[78%] bg-blue-50/80 border border-blue-100 text-gray-900 rounded-2xl rounded-tl-xs px-4 py-2.5 shadow-xs">
                                    <p class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider mb-1">Cliente</p>
                                    <div class="text-[13px] font-medium leading-relaxed">${m.message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</div>
                                    <span class="text-[10px] text-blue-400 block mt-1 text-right font-medium">${formatTime(m.created_at)}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    list.scrollTop = list.scrollHeight;
                }
            } catch(e) {
                console.error(e);
            }
        }

        document.getElementById('replyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('replyInput');
            const msg = input.value.trim();
            if (!msg || !currentSession) return;
            
            input.value = '';
            
            const list = document.getElementById('messagesList');
            list.innerHTML += `
                <div class="flex justify-end">
                    <div class="max-w-[70%] bg-blue-600 text-white rounded-tl-xl rounded-tr-xl rounded-bl-xl px-4 py-2 shadow-sm opacity-50">
                        <p class="text-[13px]">${msg}</p>
                    </div>
                </div>
            `;
            list.scrollTop = list.scrollHeight;
            
            await fetch('../api/chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    session_id: currentSession,
                    message: msg,
                    sender: 'admin'
                })
            });
            
            loadMessages();
            loadSessions();
        });

        setInterval(() => {
            loadSessions();
            if (currentSession) {
                loadMessages();
            }
        }, 4000);

        updateNotificationButton();
        loadSessions();
    </script>
</body>
</html>
