/**
 * PRLFormMain - главный файл инициализации для PRL формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initPRLForm() {
    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('PRL страницы не найдены');
        return;
    }

    // Настройка высоты таблиц после загрузки
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        const config = {
            formName: 'PRL',
            min_height_tab: 800,
            max_height_tab: 850,
            row_height: 36,
            row_selector: '.data-row-prl[data-row-index]',
            addRowCallback: PRLRowManager.addEmptyRow,
            removeRowCallback: PRLRowManager.removeRow,
            getRowIndexCallback: PRLRowManager.getRowIndex,
            max_iterations: 50
        };

        MultiPageHandler.initAllPages(config);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPRLForm);
} else {
    initPRLForm();
}

