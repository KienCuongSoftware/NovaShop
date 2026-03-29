@extends('layouts.user')

@section('title', 'Trợ lý AI — NovaShop')

@section('content')
<style>
    #ai-chat-input {
        border-radius: 0.875rem;
        resize: vertical;
    }
    #ai-chat-input:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2);
    }
    #ai-chat-send {
        border-radius: 50rem;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
    }
    .ai-chat-page-assistant-block { margin-right: 1rem; }
    .ai-chat-page-products { margin-top: 0.35rem; display: flex; flex-direction: column; gap: 0.35rem; }
    .ai-chat-page-product-card {
        display: block; padding: 0.5rem 0.65rem; background: #fff; border: 1px solid #e9ecef; border-radius: 0.5rem;
        font-size: 0.8125rem; color: #212529; text-decoration: none;
    }
    .ai-chat-page-product-card:hover {
        border-color: #dc3545; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.12); color: #212529; text-decoration: none;
    }
    .ai-chat-page-product-name { display: block; font-weight: 600; }
    .ai-chat-page-product-meta { display: block; color: #6c757d; font-size: 0.75rem; margin-top: 0.2rem; }
</style>
<div class="container py-4" style="max-width: 720px;">
    <div class="page-header">
        <h2>Trợ lý AI</h2>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="ai-chat-clear">Cuộc hội thoại mới</button>
    </div>
    <p class="text-muted small mb-3">Hỏi về sản phẩm, giao hàng hoặc mua hàng tại NovaShop. Phản hồi do AI tạo — vui lòng kiểm tra thông tin quan trọng trên website. Trên các trang khác, bạn có thể mở nhanh bằng <strong>nút chat đỏ</strong> ở góc phải dưới màn hình.@auth <strong>Đã đăng nhập:</strong> lịch sử chat được lưu theo tài khoản (không mất khi đăng xuất).@endauth</p>

    <div class="card">
        <div class="card-body p-0 d-flex flex-column" style="min-height: 420px; max-height: 65vh;">
            <div id="ai-chat-messages" class="flex-grow-1 overflow-auto p-3" style="background: #f8f9fa;">
                <div class="ai-msg ai-msg-assistant text-dark small mb-2 p-2 rounded" style="background: #fff; border: 1px solid #e9ecef;">
                    Xin chào! Mình có thể giúp gì cho bạn hôm nay?
                </div>
            </div>
            <div class="border-top p-3 bg-white">
                <div id="ai-chat-error" class="alert alert-danger py-2 small mb-2" style="display: none;"></div>
                <form id="ai-chat-form" class="d-flex flex-column gap-2">
                    <textarea id="ai-chat-input" class="form-control" rows="2" maxlength="2000" placeholder="Nhập câu hỏi..." required></textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small" id="ai-chat-status"></span>
                        <button type="submit" class="btn btn-primary" id="ai-chat-send">Gửi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('ai-chat-messages');
    var form = document.getElementById('ai-chat-form');
    var input = document.getElementById('ai-chat-input');
    var sendBtn = document.getElementById('ai-chat-send');
    var statusEl = document.getElementById('ai-chat-status');
    var errEl = document.getElementById('ai-chat-error');
    var clearBtn = document.getElementById('ai-chat-clear');
    var sendUrl = @json(route('ai-chat.send', [], false));
    var clearUrl = @json(route('ai-chat.clear', [], false));
    var historyUrl = @json(auth()->check() ? route('ai-chat.history', [], false) : '');
    var csrf = @json(csrf_token());

    function appendBubble(text, role) {
        var div = document.createElement('div');
        div.className = 'ai-msg small mb-2 p-2 rounded ' + (role === 'user' ? 'text-white ml-4' : 'text-dark mr-4');
        div.style.background = role === 'user' ? '#dc3545' : '#fff';
        div.style.border = role === 'user' ? 'none' : '1px solid #e9ecef';
        div.style.whiteSpace = 'pre-wrap';
        div.textContent = text;
        root.appendChild(div);
        root.scrollTop = root.scrollHeight;
    }
    function appendAssistantWithProducts(text, products) {
        var wrap = document.createElement('div');
        wrap.className = 'ai-chat-page-assistant-block mb-2';
        var bubble = document.createElement('div');
        bubble.className = 'ai-msg small p-2 rounded text-dark mr-4';
        bubble.style.background = '#fff';
        bubble.style.border = '1px solid #e9ecef';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;
        wrap.appendChild(bubble);
        if (products && products.length) {
            var list = document.createElement('div');
            list.className = 'ai-chat-page-products';
            for (var i = 0; i < products.length; i++) {
                var p = products[i];
                var a = document.createElement('a');
                a.className = 'ai-chat-page-product-card';
                a.href = p.search_url || p.url || '#';
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                var nameEl = document.createElement('span');
                nameEl.className = 'ai-chat-page-product-name';
                nameEl.textContent = p.name || '';
                var metaEl = document.createElement('span');
                metaEl.className = 'ai-chat-page-product-meta';
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
        root.appendChild(wrap);
        root.scrollTop = root.scrollHeight;
    }

    function renderChatHistory(messages) {
        root.innerHTML = '';
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
        root.scrollTop = root.scrollHeight;
    }

    function setLoading(on) {
        sendBtn.disabled = on;
        input.disabled = on;
        statusEl.textContent = on ? 'Đang trả lời…' : '';
    }

    function showError(msg) {
        errEl.textContent = msg;
        errEl.style.display = msg ? 'block' : 'none';
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
                input.focus();
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

    if (historyUrl) {
        fetch(historyUrl, {
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
            })
            .catch(function () {});
    }
})();
</script>
@endpush
@endsection
