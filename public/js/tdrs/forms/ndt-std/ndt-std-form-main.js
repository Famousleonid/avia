/**
 * NDTStdFormMain - главный файл инициализации для NDT Standard формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initNDTStdForm() {
    // Применяем патч для Chart.js (если используется)
    if (typeof ChartJSPatcher !== 'undefined') {
        ChartJSPatcher.applyPatch();
    }

    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('NDT Standard страницы не найдены');
        return;
    }

    // Настройка высоты таблиц после загрузки
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        const config = {
            formName: 'NDT Standard',
            min_height_tab: 500,
            max_height_tab: 600,
            row_height: 32,
            row_selector: '.data-row-ndt[data-row-index]',
            addRowCallback: function() {}, // Не добавляем строки - они уже на бэкенде
            removeRowCallback: function() {}, // Не удаляем строки - только визуальная настройка
            getRowIndexCallback: NDTStdRowManager.getRowIndex,
            max_iterations: 50
        };

        MultiPageHandler.initAllPages(config);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNDTStdForm);
} else {
    initNDTStdForm();
}

