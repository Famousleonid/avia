/**
 * ProcessesFormMain - главный файл инициализации для страницы формы процесса
 * Координирует работу всех модулей для настройки высоты таблиц
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initProcessesForm() {
    // Проверяем наличие необходимых элементов
    const hasNDTTable = document.querySelectorAll('.data-row-ndt').length > 0;
    const hasRegularTable = document.querySelector('.data-page') !== null;

    if (!hasNDTTable && !hasRegularTable) {
        console.warn('Таблицы процессов не найдены на странице');
        return;
    }

    // Настройка высоты таблиц после загрузки
    // Используем задержку для обеспечения полного рендеринга
    setTimeout(function() {
        // Проверяем, что функция adjustTableHeightToRange загружена
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена. Проверьте подключение скрипта table-height-adjuster.js');
            return;
        }

        // Используем HeightCalculator для вычисления высот
        const heightCalculator = HeightCalculator.calculateAverageRowHeight;

        // Инициализируем настройку высоты для всех таблиц
        TableHeightManager.initAll(heightCalculator);

        // Дополнительная проверка и корректировка на основе реальных высот строк
        // После того как adjustTableHeightToRange завершил работу
        setTimeout(function() {
            // Проверка обычной таблицы
            const regularTableContainer = document.querySelector('.data-page');
            if (regularTableContainer) {
                const comparison = HeightCalculator.compareContainerAndRowsHeight(
                    regularTableContainer,
                    '[data-row-index]'
                );
                console.log(
                    `Проверка высоты обычной таблицы: контейнер ${comparison.containerHeight}px, сумма высот строк ${comparison.rowsHeight}px`
                );
            }

            // Проверка NDT таблицы
            const ndtContainer = document.querySelector('.ndt-data-container');
            if (ndtContainer) {
                const ndtComparison = HeightCalculator.compareContainerAndRowsHeight(
                    ndtContainer,
                    '[data-row-index]'
                );
                console.log(
                    `Проверка высоты NDT таблицы: контейнер ${ndtComparison.containerHeight}px, сумма высот строк ${ndtComparison.rowsHeight}px`
                );
            }
        }, 300);
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProcessesForm);
} else {
    // DOM уже загружен
    initProcessesForm();
}




