/**
 * FormLinkHandler - модуль для обработки ссылок на формы процессов
 * Добавляет vendor_id в URL при клике на кнопку "Form"
 */
class FormLinkHandler {
    /**
     * Инициализирует обработчики для ссылок на формы
     * @param {ParentNode} [root] - искать только внутри узла (например после AJAX); по умолчанию document
     */
    static init(root) {
        const scope = root && root.querySelectorAll ? root : document;
        const formLinks = scope.querySelectorAll('.form-link:not(.disabled)');
        formLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Пропускаем неактивные ссылки
                if (this.classList.contains('disabled') || this.hasAttribute('aria-disabled')) {
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                FormLinkHandler.updateFormLinkUrl(this);
                const target = this.getAttribute('target');
                if (target === '_blank') {
                    window.open(this.href, '_blank');
                } else {
                    window.location.href = this.href;
                }
            });
        });
    }

    /**
     * Обновляет URL ссылки на форму с учетом выбранного vendor
     * @param {HTMLElement} link - Элемент ссылки
     */
    static updateFormLinkUrl(link) {
        const tdrProcessId = link.getAttribute('data-tdr-process-id');
        const process = link.getAttribute('data-process');
        const processNameId = link.getAttribute('data-process-name-id');

        // Сначала ищем вендор в той же строке таблицы / блоке процессов (TDR show, модалки), иначе — по всему document
        const row = link.closest('tr');
        const localRoot = row || link.closest('.processes-modal-body') || link.closest('.extra-processes-modal-body') || document;

        let vendorSelect = null;

        // Ищем селект vendor по tdrProcessId и process (для обычных процессов - несколько строк на tdr_process)
        if (tdrProcessId && process) {
            vendorSelect = localRoot.querySelector(
                `select.vendor-select[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
            )
                || document.querySelector(
                    `select.vendor-select[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
                );
        }

        // Если не нашли, ищем по tdrProcessId только (NDT с plus — одна строка без data-process на ссылке)
        if (!vendorSelect && tdrProcessId) {
            const pool = localRoot.querySelectorAll(`select.vendor-select[data-tdr-process-id="${tdrProcessId}"]`);
            vendorSelect = pool.length ? pool[0] : null;
            if (!vendorSelect) {
                const globalPool = document.querySelectorAll(`select.vendor-select[data-tdr-process-id="${tdrProcessId}"]`);
                vendorSelect = globalPool.length ? globalPool[0] : null;
            }
        }

        // Если не нашли, ищем по processNameId (групповые формы в модалке на полной странице процессов)
        if (!vendorSelect && processNameId) {
            vendorSelect = document.querySelector(
                `select.vendor-select[data-process-name-id="${processNameId}"]`
            );
        }

        const currentUrl = new URL(link.getAttribute('href') || link.href, window.location.origin);
        if (vendorSelect && vendorSelect.value) {
            currentUrl.searchParams.set('vendor_id', vendorSelect.value);
        } else {
            currentUrl.searchParams.delete('vendor_id');
        }
        link.setAttribute('href', currentUrl.pathname + currentUrl.search + currentUrl.hash);
    }
}

/**
 * GenDocHandler (2c.1) — генерация конкретного PDF документа процесса для WO.
 * Делегированный обработчик на document (привязывается один раз), работает после AJAX-перерисовок.
 */
(function () {
    if (window.__genDocHandlerBound) return;
    window.__genDocHandlerBound = true;

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.gen-doc-btn');
        if (!btn) return;
        e.preventDefault();

        const docId = btn.getAttribute('data-doc-id');
        const woId = btn.getAttribute('data-wo-id');
        const parameterId = btn.getAttribute('data-parameter-id'); // EC: render one place only
        if (!docId || !woId) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`/workorders/${woId}/process-documents/${docId}/generate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(parameterId ? { parameter_id: parameterId } : {}),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.show_url) {
                window.open(data.show_url, '_blank');
            } else {
                alert((data && data.message) ? data.message : 'Failed to generate document');
            }
        } catch (err) {
            alert('Failed to generate document: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormLinkHandler;
}




