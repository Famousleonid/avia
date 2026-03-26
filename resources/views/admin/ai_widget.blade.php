{{-- admin/ai_widget.blade --}}
{{-- AI Assistant Floating Widget --}}
@auth
@php
    $aiAgentName = trim((string) config('services.openai.agent_name', 'Assistant')) ?: 'Assistant';
@endphp
<style>
    .ai-widget {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 5000;
        font-family: inherit;
    }

    .ai-widget-toggle {
        width: 64px;
        height: 64px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #3d8bfd);
        color: #fff;
        box-shadow: 0 10px 30px rgba(13, 110, 253, .35);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .ai-widget-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 34px rgba(13, 110, 253, .45);
    }

    .ai-widget-toggle-icon {
        font-weight: 700;
        font-size: 18px;
        letter-spacing: .5px;
    }

    .ai-widget-box {
        position: absolute;
        right: 0;
        bottom: 78px;
        width: min(560px, 92vw);
        height: min(760px, 80vh);
        min-width: 360px;
        min-height: 420px;
        max-width: 92vw;
        max-height: 85vh;
        background: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,.22);
        border: 1px solid rgba(0,0,0,.08);
        display: flex;
        flex-direction: column;
        resize: none;
    }

    .ai-widget-resizer {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 18px;
        height: 18px;
        cursor: nesw-resize;
        z-index: 3;
        background: linear-gradient(135deg, transparent 45%, rgba(13, 110, 253, .6) 46%, rgba(13, 110, 253, .6) 54%, transparent 55%);
        opacity: .85;
    }

    .ai-widget-header {
        background: linear-gradient(135deg, #0d6efd, #3d8bfd);
        color: #fff;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex: 0 0 auto;
    }

    .ai-widget-title {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.2;
    }

    .ai-widget-subtitle {
        font-size: 12px;
        opacity: .9;
    }

    .ai-widget-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ai-widget-messages {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 14px;
        background: var(--bs-tertiary-bg, #f8f9fa);
    }

    .ai-msg {
        display: flex;
        margin-bottom: 12px;
    }

    .ai-msg.user {
        justify-content: flex-end;
    }

    .ai-msg.assistant {
        justify-content: flex-start;
    }

    .ai-msg-bubble {
        max-width: 85%;
        padding: 10px 12px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.4;
        white-space: pre-wrap;
        word-break: break-word;
        box-shadow: 0 4px 14px rgba(0,0,0,.07);
    }

    .ai-msg.user .ai-msg-bubble {
        background: #0d6efd;
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .ai-msg.assistant .ai-msg-bubble {
        background: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        border-bottom-left-radius: 4px;
    }

    .ai-msg.assistant .ai-msg-bubble a {
        color: var(--bs-link-color, #0d6efd);
        text-decoration: underline;
        font-weight: 600;
    }

    .ai-msg.assistant .ai-msg-bubble a:hover {
        opacity: .9;
    }

    .ai-widget-typing {
        padding: 0 14px 8px;
        font-size: 12px;
        opacity: .75;
        background: var(--bs-body-bg, #fff);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ai-typing-dots {
        display: inline-flex;
        gap: 4px;
        align-items: center;
    }

    .ai-typing-dots span {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
        opacity: .35;
        animation: aiDots 1.1s infinite ease-in-out;
    }

    .ai-typing-dots span:nth-child(2) { animation-delay: .15s; }
    .ai-typing-dots span:nth-child(3) { animation-delay: .3s; }

    @keyframes aiDots {
        0%, 80%, 100% { transform: translateY(0); opacity: .35; }
        40% { transform: translateY(-3px); opacity: .95; }
    }

    .ai-widget-footer {
        flex: 0 0 auto;
        background: var(--bs-body-bg, #fff);
        border-top: 1px solid rgba(0,0,0,.08);
        padding: 12px;
    }

    .ai-widget-form {
        display: flex;
        gap: 8px;
    }

    .ai-widget-form input {
        min-width: 0;
    }

    @media (max-width: 768px) {
        .ai-widget {
            right: 12px;
            bottom: 12px;
            left: 12px;
        }

        .ai-widget-toggle {
            margin-left: auto;
        }

        .ai-widget-box {
            right: 0;
            left: 0;
            width: auto;
            height: min(70vh, 520px);
            bottom: 74px;
            min-width: 0;
            min-height: 0;
        }

        .ai-widget-resizer {
            display: none;
        }
    }
</style>
{{-- AI Assistant Floating Widget --}}
<div id="aiAssistantWidget" class="ai-widget">
    <button id="aiAssistantToggle" class="ai-widget-toggle" type="button" aria-label="Open AI Assistant">
        <span class="ai-widget-toggle-icon">AI</span>
    </button>

    <div id="aiAssistantBox" class="ai-widget-box d-none">
        <div id="aiWidgetResizer" class="ai-widget-resizer" title="Resize"></div>
        <div class="ai-widget-header">
            <div class="ai-widget-title-wrap">
                <div class="ai-widget-title">AI Assistant</div>
                <div class="ai-widget-subtitle">Chat + workorders + helper</div>
            </div>

            <div class="ai-widget-actions">
                <button type="button" class="btn btn-sm btn-outline-light" id="aiWidgetResetBtn">
                    New
                </button>
                <button type="button" class="btn btn-sm btn-light" id="aiWidgetCloseBtn">
                    ×
                </button>
            </div>
        </div>

        <div id="aiWidgetMessages" class="ai-widget-messages">
            <div class="ai-msg assistant">
                <div class="ai-msg-bubble">
                    Привет! Я — {{ $aiAgentName }}. Спроси про воркордеры, задачи, мануалы, фото и замечания — или что угодно по работе.
                </div>
            </div>
        </div>

        <div id="aiWidgetTyping" class="ai-widget-typing d-none">
            {{ $aiAgentName }} думает…
            <span class="ai-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
        </div>

        <div class="ai-widget-footer">
            <form id="aiWidgetForm" class="ai-widget-form" data-no-spinner>
                @csrf
                <input
                    id="aiWidgetInput"
                    type="text"
                    class="form-control"
                    placeholder="Write a message..."
                    autocomplete="off"
                >
                <button id="aiWidgetSendBtn" type="submit" class="btn btn-primary">
                    Send
                </button>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        if (window.__aiWidgetInitialized) {
            return;
        }
        window.__aiWidgetInitialized = true;

        const widget = document.getElementById('aiAssistantWidget');
        const toggleBtn = document.getElementById('aiAssistantToggle');
        const box = document.getElementById('aiAssistantBox');
        const closeBtn = document.getElementById('aiWidgetCloseBtn');
        const resetBtn = document.getElementById('aiWidgetResetBtn');
        const resizer = document.getElementById('aiWidgetResizer');
        const form = document.getElementById('aiWidgetForm');
        const input = document.getElementById('aiWidgetInput');
        const sendBtn = document.getElementById('aiWidgetSendBtn');
        const messages = document.getElementById('aiWidgetMessages');
        const typing = document.getElementById('aiWidgetTyping');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!widget || !toggleBtn || !box || !form || !input || !sendBtn || !messages) {
            return;
        }

        const STORAGE_OPEN_KEY = 'ai_widget_open';
        const STORAGE_SIZE_KEY = 'ai_widget_size_v1';
        const AI_AGENT_NAME = @json($aiAgentName);
        let historyLoaded = false;
        let loadingHistory = false;
        let pendingAction = null;

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.innerText = str ?? '';
            return div.innerHTML;
        }

        function escapeAttr(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        /** Разрешённые markdown-ссылки [текст](url) → <a>; только тот же origin или относительный путь. */
        function isSafeAssistantHref(href) {
            const t = String(href || '').trim();
            if (!t || /^(javascript:|data:|vbscript:)/i.test(t)) return false;
            if (t.startsWith('/')) return true;
            try {
                const u = new URL(t, window.location.origin);
                return u.origin === window.location.origin;
            } catch (e) {
                return false;
            }
        }

        function assistantMarkdownLinksToHtml(raw) {
            const s = String(raw ?? '');
            const re = /\[([^\]]*)\]\(([^)]+)\)/g;
            let out = '';
            let last = 0;
            let m;
            while ((m = re.exec(s)) !== null) {
                out += escapeHtml(s.slice(last, m.index));
                const label = m[1];
                const href = m[2].trim();
                if (isSafeAssistantHref(href)) {
                    out += '<a href="' + escapeAttr(href) + '">' + escapeHtml(label) + '</a>';
                } else {
                    out += escapeHtml(m[0]);
                }
                last = m.index + m[0].length;
            }
            out += escapeHtml(s.slice(last));
            return out;
        }

        function appendMessage(role, text) {
            const row = document.createElement('div');
            row.className = 'ai-msg ' + role;

            const bubble = document.createElement('div');
            bubble.className = 'ai-msg-bubble';
            bubble.innerHTML = role === 'assistant'
                ? assistantMarkdownLinksToHtml(text)
                : escapeHtml(text);

            row.appendChild(bubble);
            messages.appendChild(row);
            scrollToBottom();
        }

        function getCurrentContext() {
            const wo = window.aiCurrentWorkorder || null;
            const page = window.aiPageContext && typeof window.aiPageContext === 'object'
                ? { route: window.aiPageContext.route || null }
                : null;
            return {
                current_workorder: wo && wo.id ? {
                    id: Number(wo.id) || null,
                    number: Number(wo.number) || null,
                    manual_id: Number(wo.manual_id) || null
                } : null,
                page: page && page.route ? page : null,
                origin: window.location.origin || null
            };
        }

        function appendConfirmBox(action) {
            pendingAction = action || null;
            if (!pendingAction) return;

            const row = document.createElement('div');
            row.className = 'ai-msg assistant';
            row.innerHTML = `
                <div class="ai-msg-bubble">
                    <div class="mb-2">Confirm action?</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" data-ai-confirm="yes">Yes, create</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-ai-confirm="no">Cancel</button>
                    </div>
                </div>
            `;
            messages.appendChild(row);
            scrollToBottom();
        }

        function scrollToBottom() {
            messages.scrollTop = messages.scrollHeight;
        }

        function setBusy(state) {
            input.disabled = state;
            sendBtn.disabled = state;
            typing.classList.toggle('d-none', !state);
        }

        function clamp(n, min, max) {
            return Math.max(min, Math.min(max, n));
        }

        function normalizeSize(width, height) {
            const maxW = Math.floor(window.innerWidth * 0.92);
            const maxH = Math.floor(window.innerHeight * 0.85);
            return {
                width: clamp(Math.round(width || 0), 360, maxW),
                height: clamp(Math.round(height || 0), 420, maxH),
            };
        }

        function saveWidgetSize(width, height) {
            try {
                const s = normalizeSize(width, height);
                localStorage.setItem(STORAGE_SIZE_KEY, JSON.stringify(s));
            } catch (_) {}
        }

        function applySavedWidgetSize() {
            if (window.matchMedia('(max-width: 768px)').matches) return;
            try {
                const raw = localStorage.getItem(STORAGE_SIZE_KEY);
                if (!raw) return;
                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object') return;
                const s = normalizeSize(parsed.width, parsed.height);
                box.style.width = `${s.width}px`;
                box.style.height = `${s.height}px`;
            } catch (_) {}
        }

        function initResizer() {
            if (!resizer) return;
            if (window.matchMedia('(max-width: 768px)').matches) return;

            const MIN_W = 360;
            const MIN_H = 420;

            let dragging = false;
            let startX = 0;
            let startY = 0;
            let startW = 0;
            let startH = 0;

            resizer.addEventListener('mousedown', function (e) {
                e.preventDefault();
                dragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startW = box.offsetWidth;
                startH = box.offsetHeight;
                document.body.style.userSelect = 'none';
            });

            window.addEventListener('mousemove', function (e) {
                if (!dragging) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                // left-bottom handle: drag left => wider, drag up => taller
                const newW = Math.min(window.innerWidth * 0.92, Math.max(MIN_W, startW - dx));
                const newH = Math.min(window.innerHeight * 0.85, Math.max(MIN_H, startH - dy));

                box.style.width = `${Math.round(newW)}px`;
                box.style.height = `${Math.round(newH)}px`;
            });

            window.addEventListener('mouseup', function () {
                if (!dragging) return;
                dragging = false;
                document.body.style.userSelect = '';
                saveWidgetSize(box.offsetWidth, box.offsetHeight);
            });
        }

        async function loadHistory(force = false) {
            if ((historyLoaded && !force) || loadingHistory) {
                return;
            }

            loadingHistory = true;

            try {
                const response = await fetch(@json(route('admin.ai.history')), {
                    method: 'GET',
                    spinner: false,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                });

                let data = null;

                try {
                    data = await response.json();
                } catch (e) {
                    return;
                }

                if (!response.ok || !data?.ok) {
                    return;
                }

                messages.innerHTML = '';

                if (Array.isArray(data.messages) && data.messages.length > 0) {
                    data.messages.forEach(function (msg) {
                        appendMessage(msg.role, msg.content || '');
                    });
                } else {
                    appendMessage('assistant', 'Привет! Я — ' + AI_AGENT_NAME + '. Спроси про воркордеры, задачи, мануалы, фото и замечания — или что угодно по работе.');
                }

                historyLoaded = true;
            } catch (e) {
            } finally {
                loadingHistory = false;
                scrollToBottom();
                if (typeof safeHideSpinner === 'function') {
                    safeHideSpinner(); //
                }
            }
        }

        async function openWidget() {
            box.classList.remove('d-none');
            localStorage.setItem(STORAGE_OPEN_KEY, '1');
            await loadHistory();
            setTimeout(() => input.focus(), 50);
            scrollToBottom();
        }

        function closeWidget() {
            box.classList.add('d-none');
            localStorage.setItem(STORAGE_OPEN_KEY, '0');
        }

        function toggleWidget() {
            if (box.classList.contains('d-none')) {
                openWidget();
            } else {
                closeWidget();
            }
        }

        toggleBtn.addEventListener('click', function () {
            toggleWidget();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                closeWidget();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !box.classList.contains('d-none')) {
                closeWidget();
            }
        });

        document.addEventListener('click', function (e) {
            if (box.classList.contains('d-none')) return;
            if (widget.contains(e.target)) return;
            closeWidget();
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const text = input.value.trim();
            if (!text) {
                if (typeof safeHideSpinner === 'function') safeHideSpinner();
                return;
            }

            if (!historyLoaded) {
                await loadHistory();
            }

            appendMessage('user', text);
            input.value = '';
            setBusy(true);

            try {
                const response = await fetch(@json(route('admin.ai.chat')), {
                    method: 'POST',
                    spinner: false,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        message: text,
                        current_context: getCurrentContext()
                    })
                });

                let data = null;

                try {
                    data = await response.json();
                } catch (e) {
                    appendMessage('assistant', 'Server returned invalid response.');
                    return;
                }

                if (!response.ok || !data?.ok) {
                    appendMessage('assistant', data?.reply || data?.message || 'Error.');
                    return;
                }

                appendMessage('assistant', data.reply || 'No response.');
                if (data?.requires_confirmation && data?.action) {
                    appendConfirmBox(data.action);
                }
            } catch (error) {
                appendMessage('assistant', 'Connection error.');
            } finally {
                setBusy(false);
                input.focus();
                if (typeof safeHideSpinner === 'function') {
                    safeHideSpinner();
                }
            }
        });

        messages.addEventListener('click', async function (e) {
            const yesBtn = e.target.closest('[data-ai-confirm="yes"]');
            const noBtn = e.target.closest('[data-ai-confirm="no"]');
            if (!yesBtn && !noBtn) return;

            if (noBtn) {
                pendingAction = null;
                appendMessage('assistant', 'Cancelled.');
                return;
            }

            if (!pendingAction) {
                appendMessage('assistant', 'No pending action.');
                return;
            }

            setBusy(true);
            try {
                const response = await fetch(@json(route('admin.ai.chat')), {
                    method: 'POST',
                    spinner: false,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        message: 'Please execute confirmed action.',
                        current_context: getCurrentContext(),
                        confirm_action: pendingAction
                    })
                });

                const data = await response.json().catch(() => ({}));
                appendMessage('assistant', data?.reply || (response.ok ? 'Done.' : 'Error while executing action.'));
            } catch (_) {
                appendMessage('assistant', 'Connection error.');
            } finally {
                pendingAction = null;
                setBusy(false);
            }
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', async function () {
                try {
                    const response = await fetch(@json(route('admin.ai.reset')), {
                        method: 'POST',
                        spinner: false,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({})
                    });

                    if (!response.ok) {
                        appendMessage('assistant', 'Could not reset chat.');
                        return;
                    }

                    historyLoaded = false;
                    messages.innerHTML = '';
                    setBusy(false);
                    appendMessage('assistant', 'New chat started.');
                } catch (error) {
                    appendMessage('assistant', 'Could not reset chat.');
                }
            });
        }

        if (localStorage.getItem(STORAGE_OPEN_KEY) === '1') {
            openWidget();
        }

        applySavedWidgetSize();
        initResizer();
    })();
</script>
@endauth
