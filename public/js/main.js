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


// =====================================================
// Глобальная загрузка table-height-adjuster.js (ОТКЛЮЧЕНО - файл не существует)
// =====================================================
/*
(function() {
    if (typeof adjustTableHeightToRange === 'undefined' && typeof calculateMaxTableRows === 'undefined') {
        var existingScript = document.querySelector('script[src*="table-height-adjuster.js"]');
        if (existingScript) {
            console.log('table-height-adjuster.js уже загружается или загружен');
            return;
        }

        var script = document.createElement('script');
        var baseUrl = window.location.origin;
        var scriptPath = baseUrl + '/js/table-height-adjuster.js';

        script.src = scriptPath;
        script.async = false;
        script.onload = function() {
            window.dispatchEvent(new Event('tableHeightAdjusterLoaded'));
        };
        script.onerror = function() {
            console.error('Ошибка загрузки table-height-adjuster.js по пути: ' + scriptPath);
            console.error('Проверьте, существует ли файл по этому пути');
        };

        var head = document.head || document.getElementsByTagName('head')[0];
        head.appendChild(script);
    } else {
        console.log('table-height-adjuster.js уже загружен');
        window.dispatchEvent(new Event('tableHeightAdjusterLoaded'));
    }
})();
*/


// ====== HAPTIC FEEDBACK (mobile) ======
window.hapticTap = function (pattern = 10) {
    if (!('vibrate' in navigator)) return;    try {
        navigator.vibrate(pattern);
    } catch (e) {
        // silently ignore
    }
};