/**
 * CADFormMain - главный файл инициализации для CAD формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initCADForm() {
    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('CAD страницы не найдены');
        return;
    }

    // Настройка высоты таблиц после загрузки (только визуальная настройка)
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        const config = {
            formName: 'CAD',
            min_height_tab: 550,
            max_height_tab: 620,
            row_height: 32,
            row_selector: '.data-row[data-row-index]',
            addRowCallback: CADRowManager.addEmptyRow, // Не добавляем строки
            removeRowCallback: CADRowManager.removeRow, // Удаляем только пустые
            getRowIndexCallback: CADRowManager.getRowIndex,
            max_iterations: 50
        };

        MultiPageHandler.initAllPages(config);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCADForm);
} else {
    initCADForm();
}

