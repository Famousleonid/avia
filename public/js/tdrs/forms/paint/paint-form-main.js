/**
 * PaintFormMain - главный файл инициализации для Paint формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initPaintForm() {
    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('Paint страницы не найдены');
        return;
    }

    // Настройка высоты таблиц после загрузки (только визуальная настройка)
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        const config = {
            formName: 'Paint',
            min_height_tab: 700,
            max_height_tab: 750,
            row_height: 34,
            row_selector: '.data-row[data-row-index]',
            addRowCallback: PaintRowManager.addEmptyRow, // Не добавляем строки
            removeRowCallback: PaintRowManager.removeRow, // Не удаляем строки
            getRowIndexCallback: PaintRowManager.getRowIndex,
            max_iterations: 50
        };

        MultiPageHandler.initAllPages(config);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPaintForm);
} else {
    initPaintForm();
}



