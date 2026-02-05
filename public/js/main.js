// main.js

// =====================================================
// SPINNER with DELAY 200ms + safe wrappers
// =====================================================

// элемент с data-spinner или старым классом .press-spinner
// <form data-no-spinner>...</form> - если нужно не включать спинер
// fetch(url, { method:'POST', body: fd, spinner: false }); - если нужно не включать спинер

(function () {
    const SHOW_DELAY_MS = 200;

    let pendingSpinner = 0;
    let showTimer = null;

    function getSpinnerEl() {
        return document.querySelector('#spinner-load');
    }

    function showNow() {
        const el = getSpinnerEl();
        if (!el) return;
        el.classList.remove('d-none');
    }

    function hideNow() {
        const el = getSpinnerEl();
        if (!el) return;
        el.classList.add('d-none');
    }

    // публичные (совместимость со старым кодом)
    window.showLoadingSpinner = function () {
        pendingSpinner++;

        if (pendingSpinner < 1) pendingSpinner = 1;

        // уже ждём показ — второй таймер не ставим
        if (showTimer) return;

        showTimer = setTimeout(() => {
            showTimer = null;
            // показываем только если всё ещё есть ожидание
            if (pendingSpinner > 0) showNow();
        }, SHOW_DELAY_MS);

    };

    window.hideLoadingSpinner = function () {
        pendingSpinner = Math.max(0, pendingSpinner - 1);
        if (pendingSpinner === 0) {
            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }
            hideNow();
        }
    };

    // ====== SAFE SPINNER (global) ======
    window.safeShowSpinner = function () {
        try { window.showLoadingSpinner(); } catch (e) { /* ignore */ }
    };

    // делаем "жёсткое" скрытие (сброс счётчиков)
    window.safeHideSpinner = function () {
        pendingSpinner = 0;
        if (showTimer) {
            clearTimeout(showTimer);
            showTimer = null;
        }
        try { hideNow(); } catch (e) { /* ignore */ }
    };
})();


// =====================================================
// GLOBAL SPINNER CLICK (delegation)
// =====================================================
(function () {
    if (window.__spinnerDelegationBound) return;
    window.__spinnerDelegationBound = true;

    document.addEventListener('click', function (e) {
        // ищем ближайший элемент с data-spinner или старым классом .press-spinner
        const target = e.target.closest('[data-spinner], .press-spinner');
        if (!target) return;

        if (typeof window.safeShowSpinner === 'function') {
            window.safeShowSpinner();
        } else if (typeof window.showLoadingSpinner === 'function') {
            try { window.showLoadingSpinner(); } catch (_) {}
        }
    }, true);
})();


// =====================================================
// GLOBAL FORM SUBMIT SPINNER (all forms)
// =====================================================
(function () {
    if (window.__globalSubmitSpinnerBound) return;
    window.__globalSubmitSpinnerBound = true;

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // отключение спиннера для конкретной формы
        if (form.hasAttribute('data-no-spinner')) return;

        if (form.classList.contains('js-ajax')) return;

        if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
    }, true);
})();


// =====================================================
// GLOBAL FETCH SPINNER (with pending counter)
// =====================================================
(function () {
    if (window.__fetchSpinnerWrapped) return;
    window.__fetchSpinnerWrapped = true;

    // если fetch недоступен — просто выходим
    if (typeof window.fetch !== 'function') return;

    const originalFetch = window.fetch.bind(window);
    let pendingFetch = 0;

    function shouldSkipSpinner(input, init) {
        // 1) кастомный флаг
        if (init && init.spinner === false) return true;

        // 2) заголовок-выключатель
        const h = init && init.headers;
        if (h) {
            const getHeader = (name) => {
                if (h instanceof Headers) return h.get(name);
                if (Array.isArray(h)) {
                    const found = h.find(([k]) => String(k).toLowerCase() === name.toLowerCase());
                    return found ? found[1] : null;
                }
                return h[name] || h[name.toLowerCase()] || null;
            };
            if (getHeader('X-No-Spinner')) return true;
        }

        // 3) если URL содержит маркер
        const url = (typeof input === 'string') ? input : (input && input.url) ? input.url : '';
        if (url.includes('no_spinner=1')) return true;

        return false;
    }

    window.fetch = function (input, init = {}) {
        const skip = shouldSkipSpinner(input, init);

        if (!skip) {
            pendingFetch++;
            if (pendingFetch === 1 && typeof window.safeShowSpinner === 'function') {
                window.safeShowSpinner();
            }
        }

        return originalFetch(input, init)
            .finally(() => {
                if (!skip) {
                    pendingFetch = Math.max(0, pendingFetch - 1);
                    if (pendingFetch === 0 && typeof window.hideLoadingSpinner === 'function') {
                        // именно hideLoadingSpinner (а не safeHide), чтобы pendingSpinner корректно уменьшался
                        window.hideLoadingSpinner();
                    }
                }
            });
    };
})();


// =====================================================
// NOTIFICATIONS (твоя текущая система — оставил как есть)
// =====================================================

/**
 * Глобальная система уведомлений для JSON ответов и других сообщений
 * Использование:
 *   showNotification('Success message', 'success')
 *   showNotification('Error message', 'error')
 *   showNotification('Info message', 'info')
 *   showNotification('Warning message', 'warning')
 */
function showNotification(message, type = 'info', duration = 4000) {
    // Удаляем предыдущие уведомления, если есть
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notif => {
        if (notif.parentNode) {
            notif.remove();
        }
    });

    // Создаем контейнер для уведомлений, если его нет
    let notificationContainer = document.getElementById('notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            pointer-events: none;
        `;
        document.body.appendChild(notificationContainer);
    }

    // Определяем цвета и иконки в зависимости от типа
    const types = {
        success: {
            bg: '#28a745',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>',
            border: '#1e7e34'
        },
        error: {
            bg: '#dc3545',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>',
            border: '#c82333'
        },
        warning: {
            bg: '#ffc107',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
            border: '#e0a800'
        },
        info: {
            bg: '#17a2b8',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
            border: '#138496'
        }
    };

    const config = types[type] || types.info;

    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.cssText = `
        background: ${config.bg};
        color: white;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 400px;
        animation: slideInRight 0.3s ease-out;
        pointer-events: auto;
        border-left: 4px solid ${config.border};
        font-size: 14px;
        line-height: 1.5;
    `;

    notification.innerHTML = `
        <div style="flex-shrink: 0; display: flex; align-items: center;">
            ${config.icon}
        </div>
        <div style="flex: 1; word-wrap: break-word;">
            ${message}
        </div>
        <button type="button" class="btn-close btn-close-white" style="flex-shrink: 0; opacity: 0.8;" aria-label="Close"></button>
    `;

    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .custom-notification { transition: all 0.3s ease-out; }
        `;
        document.head.appendChild(style);
    }

    const closeBtn = notification.querySelector('.btn-close');
    const closeNotification = () => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    };

    closeBtn.addEventListener('click', closeNotification);

    notificationContainer.appendChild(notification);

    const timeout = setTimeout(closeNotification, duration);

    notification.addEventListener('mouseenter', () => {
        clearTimeout(timeout);
    });

    notification.addEventListener('mouseleave', () => {
        setTimeout(closeNotification, duration);
    });
}

// aliases
function showSuccessMessage(message, duration) { showNotification(message, 'success', duration); }
function showErrorMessage(message, duration)   { showNotification(message, 'error', duration); }
function showInfoMessage(message, duration)    { showNotification(message, 'info', duration); }
function showWarningMessage(message, duration) { showNotification(message, 'warning', duration); }



window.notify = function (message, type = 'info', durationMs = 6000) {
    // убираем предыдущие (как у тебя)
    document.querySelectorAll('.notify-status').forEach(el => el.remove());

    const map = {
        success: 'alert-success',
        error: 'alert-danger',
        danger: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info'
    };

    const cls = map[type] || map.info;

    const sec = Math.max(2, Math.round(durationMs / 1000));

    const el = document.createElement('div');
    el.className = `notify-status alert ${cls}`;
    el.setAttribute('role', 'alert');

    el.innerHTML = `
    <div class="notify-text" style="flex:1; min-width: 0;">${String(message)}</div>
    <button type="button" class="btn-close" aria-label="Close"></button>
    <span class="notify-countdown">${sec}</span>
  `;

    document.body.appendChild(el);

    const countdownEl = el.querySelector('.notify-countdown');
    const closeBtn = el.querySelector('.btn-close');

    let value = sec;
    let closed = false;

    const cleanup = () => {
        if (closed) return;
        closed = true;
        el.classList.add('hide');
        el.classList.remove('show');

        clearInterval(tick);

        // убрать после анимации
        setTimeout(() => el.remove(), 1100);
    };

    // показать
    setTimeout(() => el.classList.add('show'), 80);

    // countdown
    const tick = setInterval(() => {
        value--;
        if (value <= 0) {
            countdownEl.textContent = '0';
            cleanup();
        } else {
            countdownEl.textContent = String(value);
        }
    }, 1000);

    // авто-скрытие
    const autoTimer = setTimeout(cleanup, durationMs);

    // крестик
    closeBtn.addEventListener('click', () => {
        clearTimeout(autoTimer);
        cleanup();
    });
};

window.notifySuccess = (msg, ms) => window.notify(msg, 'success', ms ?? 6000);
window.notifyError   = (msg, ms) => window.notify(msg, 'error',   ms ?? 6000);
window.notifyInfo    = (msg, ms) => window.notify(msg, 'info',    ms ?? 6000);
window.notifyWarn    = (msg, ms) => window.notify(msg, 'warning', ms ?? 6000);


window.confirmDialog = function ({
                                     title = 'Confirm',
                                     message = 'Are you sure?',
                                     okText = 'OK',
                                     cancelText = 'Cancel',
                                     danger = false
                                 } = {}) {
    return new Promise((resolve) => {
        // создаём один раз
        let modalEl = document.getElementById('globalConfirmModal');
        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.id = 'globalConfirmModal';
            modalEl.className = 'modal fade';
            modalEl.tabIndex = -1;
            modalEl.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content bg-dark text-light border border-secondary">
            <div class="modal-header">
              <h5 class="modal-title" id="globalConfirmTitle"></h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="globalConfirmBody"></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-cancel></button>
              <button type="button" class="btn" data-ok></button>
            </div>
          </div>
        </div>
      `;
            document.body.appendChild(modalEl);
        }

        const titleEl  = modalEl.querySelector('#globalConfirmTitle');
        const bodyEl   = modalEl.querySelector('#globalConfirmBody');
        const okBtn    = modalEl.querySelector('[data-ok]');
        const cancelBtn= modalEl.querySelector('[data-cancel]');

        titleEl.textContent = title;
        bodyEl.textContent = message;
        cancelBtn.textContent = cancelText;
        okBtn.textContent = okText;

        okBtn.className = danger ? 'btn btn-danger' : 'btn btn-info';

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static' });

        let resolved = false;

        const cleanup = () => {
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
        };

        const finish = (val) => {
            if (resolved) return;
            resolved = true;
            cleanup();
            resolve(val);
        };

        const onOk = () => {
            if (typeof window.hapticTap === 'function') window.hapticTap(20);
            bsModal.hide();
            finish(true);
        };

        const onCancel = () => {
            if (typeof window.hapticTap === 'function') window.hapticTap(10);
            bsModal.hide();
            finish(false);
        };

        const onHidden = () => {
            // если закрыли крестиком/тапом
            finish(false);
        };

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        modalEl.addEventListener('hidden.bs.modal', onHidden);

        bsModal.show();
    });
};




// ====== HAPTIC FEEDBACK (mobile) ======
window.hapticTap = function (pattern = 10) {
    if (!('vibrate' in navigator)) return;    try {
        navigator.vibrate(pattern);
    } catch (e) {
        // silently ignore
    }
};

// =====================================================
// GLOBAL AJAX FORM SUBMIT (for inline save without reload)
// usage: window.ajaxSubmit(form) or just ajaxSubmit(form)
// =====================================================
(function () {
    if (window.__ajaxSubmitBound) return;
    window.__ajaxSubmitBound = true;

    function getToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    function showFieldErrors(form, errors) {
        clearFieldErrors(form);

        if (!errors) return;

        Object.keys(data.errors || {}).forEach((field) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input) return;

            // если это flatpickr с altInput — подсветим ВИДИМЫЙ input
            const visible = input._flatpickr?.altInput || input;

            visible.classList.add('is-invalid');
            setTimeout(() => visible.classList.remove('is-invalid'), 2000);
        });
    }
// ===== helper =====
    function applySavedState(form, data) {
        const tr = form.closest('tr');
        const userCell = tr?.querySelector('.js-last-user');
        if (userCell && data?.user) userCell.textContent = data.user;

        const icon = form.querySelector('.save-indicator');
        if (icon) icon.classList.add('d-none');

        form.querySelectorAll('.finish-input').forEach(inp => {
            const hasValue = inp.value && inp.value.trim() !== '';
            inp.classList.toggle('has-finish', hasValue);

            inp.classList.add('is-saved-field');
            setTimeout(() => inp.classList.remove('is-saved-field'), 800);

            inp.dataset.original = inp.value ?? '';
        });
    }

    function applyNotesSavedState(form, data) {
        const ta = form.querySelector('textarea[name="notes"]');
        if (!ta) return;

        // обновить оригинал = текущее значение
        ta.dataset.original = ta.value ?? '';

        // спрятать 💾 в шапке
        const box = form.closest('.wo-notes-box');
        const icon = box ? box.querySelector('.js-notes-save-indicator') : null;
        if (icon) icon.classList.add('d-none');
    }

    function setNotesSaving(form, on) {
        const box = form.closest('.wo-notes-box');
        if (!box) return;

        const saving = box.querySelector('.js-notes-saving');
        if (saving) saving.classList.toggle('d-none', !on);
    }

    async function _ajaxSubmit(form) {
        if (form.classList.contains('is-saving')) return;
        if (form.querySelector('textarea[name="notes"]')) setNotesSaving(form, true);
        const url = form?.getAttribute?.('action');
        if (!url) return;

        const fd = new FormData(form);
        form.classList.add('is-saving');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: fd,
                spinner: false,
            });

            const data = await res.json().catch(() => ({}));

            // ✅ ошибки
            if (res.status === 422 || data?.success === false) {

                // 1) toast (у тебя уже есть готовый notifyError)
                const firstMsg = data?.errors ? Object.values(data.errors)?.[0]?.[0] : 'Validation error';
                if (firstMsg && typeof window.notifyError === 'function') {
                    window.notifyError(firstMsg, 2500);
                }

                // 2) подсветка видимого инпута (flatpickr altInput)
                Object.keys(data.errors || {}).forEach((field) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (!input) return;

                    const fp = input._flatpickr || null;
                    const visible = fp?.altInput || input;

                    visible.classList.add('is-invalid');

                    setTimeout(() => {
                        visible.classList.remove('is-invalid');

                        // 🔥 очистка значения
                        if (fp) {
                            fp.clear();               // flatpickr правильно очистится
                        } else {
                            input.value = '';
                        }

                        // обновить data-original, чтобы не считалось "изменённым"
                        input.dataset.original = '';

                    }, 2000);
                });

                return;
            }

            // ✅ на всякий случай: реальные ошибки сервера
            if (!res.ok) throw new Error('Request failed');

            clearFieldErrors(form);

            if (form.querySelector('textarea[name="notes"]')) {
                applyNotesSavedState(form, data);
            } else {
                applySavedState(form, data);
            }

        } catch (e) {
            console.error(e);
            if (typeof window.notifyError === 'function') window.notifyError('Request failed', 2500);
        } finally {
            if (form.querySelector('textarea[name="notes"]')) setNotesSaving(form, false);
            form.classList.remove('is-saving');
            if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
        }
    }

// ===============================
// NOTES autosave (blur + Ctrl+Enter)
// ===============================
    (function () {
        if (window.__notesAutosaveBound) return;
        window.__notesAutosaveBound = true;

        document.addEventListener('blur', (e) => {
            const ta = e.target;
            if (ta?.name !== 'notes') return;

            const form = ta.closest('form.js-ajax');
            if (!form) return;

            const original = ta.dataset.original ?? '';
            if ((ta.value ?? '') === original) return;

            console.log('NOTES autosave blur → ajaxSubmit');
            window.ajaxSubmit(form);
        }, true);

        document.addEventListener('keydown', (e) => {
            const ta = e.target;
            if (ta?.name !== 'notes') return;

            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();

                const form = ta.closest('form.js-ajax');
                if (!form) return;

                const original = ta.dataset.original ?? '';
                if ((ta.value ?? '') === original) return;

                console.log('NOTES Ctrl+Enter → ajaxSubmit');
                window.ajaxSubmit(form);
            }
        }, true);
    })();




    // делаем глобально (и как window.ajaxSubmit, и как ajaxSubmit)
    window.ajaxSubmit = _ajaxSubmit;
    // чтобы можно было вызывать просто ajaxSubmit(form)
    if (typeof window.ajaxSubmit === 'function') {
        window.ajaxSubmitForm = _ajaxSubmit;
    }

})();
