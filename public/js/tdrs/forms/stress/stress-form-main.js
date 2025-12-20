/**
 * StressFormMain - главный файл инициализации для Stress Relief формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initStressForm() {
    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('Stress Relief страницы не найдены');
        return;
    }

    // Настройка высоты таблиц после загрузки
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        const config = {
            formName: 'Stress Relief',
            min_height_tab: 600,
            max_height_tab: 650,
            row_height: 34,
            row_selector: '.data-row[data-row-index]',
            addRowCallback: function() {}, // Не добавляем строки - они уже на бэкенде
            removeRowCallback: function() {}, // Не удаляем строки - только визуальная настройка
            getRowIndexCallback: StressRowManager.getRowIndex,
            max_iterations: 50
        };

        MultiPageHandler.initAllPages(config);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStressForm);
} else {
    initStressForm();
}

