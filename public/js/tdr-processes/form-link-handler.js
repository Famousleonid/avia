/**
 * FormLinkHandler - модуль для обработки ссылок на формы процессов
 * Добавляет vendor_id в URL при клике на кнопку "Form"
 */
class FormLinkHandler {
    /**
     * Инициализирует обработчики для ссылок на формы
     */
    static init() {
        const formLinks = document.querySelectorAll('.form-link:not(.disabled)');
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

        let vendorSelect = null;
        
        // Ищем селект vendor по tdrProcessId и process (для обычных процессов - несколько строк на tdr_process)
        if (tdrProcessId && process) {
            vendorSelect = document.querySelector(
                `select[data-tdr-process-id="${tdrProcessId}"][data-process="${process}"]`
            );
        }
        
        // Если не нашли, ищем по tdrProcessId только (для NDT с дополнительными процессами - одна объединённая строка без data-process)
        if (!vendorSelect && tdrProcessId) {
            const selects = document.querySelectorAll(`select.vendor-select[data-tdr-process-id="${tdrProcessId}"]`);
            if (selects.length > 0) {
                vendorSelect = selects[0];
            }
        }
        
        // Если не нашли, ищем по processNameId (для групповых форм)
        if (!vendorSelect && processNameId) {
            vendorSelect = document.querySelector(
                `select.vendor-select[data-process-name-id="${processNameId}"]`
            );
        }

        // Если vendor выбран, добавляем его в URL
        if (vendorSelect && vendorSelect.value) {
            const currentUrl = new URL(link.href, window.location.origin);
            currentUrl.searchParams.set('vendor_id', vendorSelect.value);
            link.href = currentUrl.toString();
        }
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormLinkHandler;
}




