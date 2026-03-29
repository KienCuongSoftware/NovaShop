{{-- Trợ lý AI: nút nổi + cửa sổ chat (OpenAI qua route ai-chat.*) --}}
<style>
    #ai-chat-widget-fab {
        position: fixed;
        z-index: 1080;
        right: 1.25rem;
        bottom: 1.25rem;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        border: none;
        background: #dc3545;
        color: #fff;
        box-shadow: 0 4px 14px rgba(220, 53, 69, 0.45);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    #ai-chat-widget-fab:hover {
        transform: scale(1.06);
        box-shadow: 0 6px 18px rgba(220, 53, 69, 0.5);
    }
    #ai-chat-widget-fab:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35);
    }
    #ai-chat-widget-fab svg { width: 1.5rem; height: 1.5rem; }
    #ai-chat-widget-fab .ai-chat-fab-icon-close { display: none; }
    #ai-chat-widget-root.is-open #ai-chat-widget-fab .ai-chat-fab-icon-chat { display: none; }
    #ai-chat-widget-root.is-open #ai-chat-widget-fab .ai-chat-fab-icon-close { display: block; }

    #ai-chat-widget-panel {
        position: fixed;
        z-index: 1079;
        right: 1.25rem;
        bottom: 5.5rem;
        width: min(22rem, calc(100vw - 2rem));
        max-height: min(32rem, calc(100vh - 7rem));
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        border: 1px solid #e9ecef;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(0.5rem) scale(0.98);
        transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
        pointer-events: none;
    }
    #ai-chat-widget-root.is-open #ai-chat-widget-panel {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .ai-chat-widget-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        background: #dc3545;
        color: #fff;
    }
    .ai-chat-widget-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }
    .ai-chat-widget-header-actions {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .ai-chat-widget-header-actions button {
        background: transparent;
        border: none;
        color: #fff;
        padding: 0.25rem 0.4rem;
        border-radius: 0.25rem;
        cursor: pointer;
        line-height: 1;
        opacity: 0.9;
        -webkit-tap-highlight-color: transparent;
    }
    .ai-chat-widget-header-actions button::-moz-focus-inner {
        border: 0;
    }
    .ai-chat-widget-header-actions button:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.15);
    }
    .ai-chat-widget-header-actions button:focus,
    .ai-chat-widget-header-actions button:active {
        outline: none;
        box-shadow: none;
    }
    .ai-chat-widget-header-actions button:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
    }

    #ai-chat-widget-messages {
        flex: 1;
        min-height: 12rem;
        max-height: 18rem;
        overflow-y: auto;
        padding: 0.75rem;
        background: #f8f9fa;
    }
    .ai-chat-widget-msg {
        font-size: 0.8125rem;
        margin-bottom: 0.5rem;
        padding: 0.5rem 0.65rem;
        border-radius: 0.5rem;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .ai-chat-widget-msg.user {
        margin-left: 1.5rem;
        background: #dc3545;
        color: #fff;
    }
    .ai-chat-widget-msg.assistant {
        margin-right: 1rem;
        background: #fff;
        color: #212529;
        border: 1px solid #e9ecef;
    }

    .ai-chat-widget-footer {
        padding: 0.65rem 0.75rem;
        border-top: 1px solid #e9ecef;
        background: #fff;
    }
    #ai-chat-widget-error {
        display: none;
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
    }
    #ai-chat-widget-error.is-visible { display: block; }

    #ai-chat-widget-input {
        border-radius: 0.875rem;
        resize: none;
    }
    #ai-chat-widget-input:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2);
    }
    #ai-chat-widget-send {
        border-radius: 50rem;
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }

    .ai-chat-widget-assistant-block {
        margin-right: 0.5rem;
    }
    .ai-chat-widget-products {
        margin-top: 0.35rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .ai-chat-widget-product-card {
        display: block;
        padding: 0.45rem 0.55rem;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        color: #212529;
        text-decoration: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .ai-chat-widget-product-card:hover {
        border-color: #dc3545;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.12);
        color: #212529;
        text-decoration: none;
    }
    .ai-chat-widget-product-name {
        display: block;
        font-weight: 600;
        line-height: 1.3;
    }
    .ai-chat-widget-product-meta {
        display: block;
        color: #6c757d;
        font-size: 0.7rem;
        margin-top: 0.15rem;
    }
</style>

<div id="ai-chat-widget-root"
    {{-- Đường dẫn tương đối: tránh APP_URL (localhost) khác host trình duyệt (127.0.0.1) → fetch không gửi cookie session --}}
    data-send-url="{{ route('ai-chat.send', [], false) }}"
    data-clear-url="{{ route('ai-chat.clear', [], false) }}"
    data-history-url="{{ auth()->check() ? route('ai-chat.history', [], false) : '' }}"
    data-auth="{{ auth()->check() ? '1' : '0' }}"
    data-csrf="{{ csrf_token() }}">
    <div id="ai-chat-widget-panel" role="dialog" aria-labelledby="ai-chat-widget-title" aria-hidden="true">
        <div class="ai-chat-widget-header">
            <h3 id="ai-chat-widget-title">Trợ lý AI</h3>
            <div class="ai-chat-widget-header-actions">
                <button type="button" id="ai-chat-widget-clear" title="Cuộc hội thoại mới" aria-label="Cuộc hội thoại mới">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/><path d="M1 4v6h6"/></svg>
                </button>
                <button type="button" id="ai-chat-widget-close-header" title="Đóng" aria-label="Đóng cửa sổ chat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <p class="small text-muted px-3 pt-2 mb-0" style="font-size: 0.7rem;">AI hỗ trợ — vui lòng kiểm tra thông tin quan trọng trên website.</p>
        <div id="ai-chat-widget-messages">
            <div class="ai-chat-widget-msg assistant">Xin chào! Mình có thể giúp gì cho bạn hôm nay?</div>
        </div>
        <div class="ai-chat-widget-footer">
            <div id="ai-chat-widget-error" class="alert alert-danger py-1 mb-2"></div>
            <form id="ai-chat-widget-form">
                <textarea id="ai-chat-widget-input" class="form-control form-control-sm mb-2" rows="2" maxlength="2000" placeholder="Nhập câu hỏi..." required></textarea>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="ai-chat-widget-status" style="font-size: 0.75rem;"></span>
                    <button type="submit" class="btn btn-primary btn-sm" id="ai-chat-widget-send">Gửi</button>
                </div>
            </form>
        </div>
    </div>

    <button type="button" id="ai-chat-widget-fab" aria-label="Mở trợ lý AI" aria-expanded="false" aria-controls="ai-chat-widget-panel">
        <svg class="ai-chat-fab-icon-chat" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <svg class="ai-chat-fab-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
</div>

<script>
(function () {
    var root = document.getElementById('ai-chat-widget-root');
    if (!root) return;
    var panel = document.getElementById('ai-chat-widget-panel');
    var fab = document.getElementById('ai-chat-widget-fab');
    var messagesEl = document.getElementById('ai-chat-widget-messages');
    var form = document.getElementById('ai-chat-widget-form');
    var input = document.getElementById('ai-chat-widget-input');
    var sendBtn = document.getElementById('ai-chat-widget-send');
    var statusEl = document.getElementById('ai-chat-widget-status');
    var errEl = document.getElementById('ai-chat-widget-error');
    var clearBtn = document.getElementById('ai-chat-widget-clear');
    var closeHeaderBtn = document.getElementById('ai-chat-widget-close-header');
    var sendUrl = root.getAttribute('data-send-url');
    var clearUrl = root.getAttribute('data-clear-url');
    var historyUrl = root.getAttribute('data-history-url') || '';
    var isAuthed = root.getAttribute('data-auth') === '1';
    var csrf = root.getAttribute('data-csrf');

    function isOpen() {
        return root.classList.contains('is-open');
    }
    function renderChatHistory(messages) {
        messagesEl.innerHTML = '';
        if (!messages || !messages.length) {
            appendBubble('Xin chào! Mình có thể giúp gì cho bạn hôm nay?', 'assistant');
            return;
        }
        for (var i = 0; i < messages.length; i++) {
            var m = messages[i];
            if (m.role === 'user') {
                appendBubble(m.content, 'user');
            } else if (m.role === 'assistant') {
                appendAssistantWithProducts(m.content, m.products || []);
            }
        }
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    function loadHistory() {
        if (!isAuthed || !historyUrl) {
            return Promise.resolve();
        }
        return fetch(historyUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        })
            .then(function (r) {
                if (!r.ok) {
                    return Promise.reject(new Error('history HTTP ' + r.status));
                }
                return r.json();
            })
            .then(function (data) {
                renderChatHistory(data.messages || []);
            });
    }
    function setOpen(open) {
        root.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        fab.setAttribute('aria-label', open ? 'Đóng trợ lý AI' : 'Mở trợ lý AI');
        if (open) {
            if (isAuthed && historyUrl) {
                loadHistory().catch(function () {}).finally(function () {
                    setTimeout(function () { input && input.focus(); }, 200);
                });
            } else {
                setTimeout(function () { input && input.focus(); }, 200);
            }
        }
    }
    fab.addEventListener('click', function () {
        setOpen(!isOpen());
    });
    closeHeaderBtn.addEventListener('click', function () {
        setOpen(false);
    });

    function appendBubble(text, role) {
        var div = document.createElement('div');
        div.className = 'ai-chat-widget-msg ' + (role === 'user' ? 'user' : 'assistant');
        div.style.whiteSpace = 'pre-wrap';
        div.textContent = text;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    function appendAssistantWithProducts(text, products) {
        var wrap = document.createElement('div');
        wrap.className = 'ai-chat-widget-assistant-block';
        var bubble = document.createElement('div');
        bubble.className = 'ai-chat-widget-msg assistant';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;
        wrap.appendChild(bubble);
        if (products && products.length) {
            var list = document.createElement('div');
            list.className = 'ai-chat-widget-products';
            for (var i = 0; i < products.length; i++) {
                var p = products[i];
                var a = document.createElement('a');
                a.className = 'ai-chat-widget-product-card';
                a.href = p.search_url || p.url || '#';
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                var nameEl = document.createElement('span');
                nameEl.className = 'ai-chat-widget-product-name';
                nameEl.textContent = p.name || '';
                var metaEl = document.createElement('span');
                metaEl.className = 'ai-chat-widget-product-meta';
                var meta = p.price_formatted || '';
                if (p.category) meta += (meta ? ' · ' : '') + p.category;
                if (p.in_stock === false) meta += (meta ? ' · ' : '') + 'Hết hàng';
                metaEl.textContent = meta;
                a.appendChild(nameEl);
                a.appendChild(metaEl);
                list.appendChild(a);
            }
            wrap.appendChild(list);
        }
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    function setLoading(on) {
        sendBtn.disabled = on;
        input.disabled = on;
        statusEl.textContent = on ? 'Đang trả lời…' : '';
    }
    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.classList.toggle('is-visible', !!msg);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (input.value || '').trim();
        if (!text) return;
        showError('');
        appendBubble(text, 'user');
        input.value = '';
        setLoading(true);

        fetch(sendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: JSON.stringify({ message: text })
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; }); })
            .then(function (res) {
                if (!res.ok) {
                    showError(res.body.error || ('Lỗi ' + res.status));
                    return;
                }
                if (res.body.reply) {
                    appendAssistantWithProducts(res.body.reply, res.body.products || []);
                }
            })
            .catch(function () {
                showError('Lỗi mạng. Kiểm tra kết nối và thử lại.');
            })
            .finally(function () {
                setLoading(false);
                if (isOpen()) input.focus();
            });
    });

    clearBtn.addEventListener('click', function () {
        fetch(clearUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        }).then(function () {
            renderChatHistory([]);
            showError('');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            setOpen(false);
        }
    });

    if (isAuthed && historyUrl) {
        loadHistory().catch(function () {});
    }
})();
</script>
