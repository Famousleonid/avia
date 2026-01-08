/**
 * WoBushingsProcessesFormMain - главный файл инициализации для страницы формы Wo Bushing процесса
 * Координирует работу всех модулей для настройки высоты таблиц
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initWoBushingsProcessesForm() {
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

        // Конфигурация для NDT таблицы
        const ndtConfig = {
            min_height_tab: 500,
            max_height_tab: 600,
            tab_name: '.ndt-data-container',
            row_selector: '.data-row-ndt[data-row-index]',
            row_height: 32, // Фиксированная высота строки
            max_iterations: 50
        };

        // Конфигурация для обычной таблицы
        const regularConfig = {
            min_height_tab: 700,
            max_height_tab: 750,
            tab_name: '.data-page',
            row_selector: '.data-page [data-row-index]',
            row_height: 34, // Фиксированная высота строки
            max_iterations: 50
        };

        // Инициализируем настройку высоты для NDT таблицы
        const ndtRows = document.querySelectorAll('.data-row-ndt');
        if (ndtRows.length > 0) {
            const ndtDataContainer = document.querySelector(ndtConfig.tab_name);
            if (ndtDataContainer) {
                adjustTableHeightToRange({
                    min_height_tab: ndtConfig.min_height_tab,
                    max_height_tab: ndtConfig.max_height_tab,
                    tab_name: ndtConfig.tab_name,
                    row_height: ndtConfig.row_height,
                    row_selector: ndtConfig.row_selector,
                    addRowCallback: RowManager.addEmptyRowNDT,
                    removeRowCallback: RowManager.removeRowNDT,
                    getRowIndexCallback: RowManager.getRowIndex,
                    max_iterations: ndtConfig.max_iterations,
                    onComplete: function(currentHeight, rowCount) {
                        console.log(`NDT таблица настроена: высота ${currentHeight}px, строк ${rowCount}`);
                    }
                });
            }
        }

        // Инициализируем настройку высоты для обычной таблицы
        const regularTableContainer = document.querySelector(regularConfig.tab_name);
        const regularRows = document.querySelectorAll('.data-page .data-row:not(.data-row-ndt)');
        if (regularTableContainer && regularRows.length > 0) {
            adjustTableHeightToRange({
                min_height_tab: regularConfig.min_height_tab,
                max_height_tab: regularConfig.max_height_tab,
                tab_name: regularConfig.tab_name,
                row_height: regularConfig.row_height,
                row_selector: regularConfig.row_selector,
                addRowCallback: RowManager.addEmptyRowRegular,
                removeRowCallback: RowManager.removeRowRegular,
                getRowIndexCallback: RowManager.getRowIndex,
                max_iterations: regularConfig.max_iterations,
                onComplete: function(currentHeight, rowCount) {
                    console.log(`Обычная таблица настроена: высота ${currentHeight}px, строк ${rowCount}`);
                }
            });
        }

        // Обработка пустых строк на основе высоты ячеек процесса
        // Переиспользуем EmptyRowProcessor из extra-processes
        if (typeof EmptyRowProcessor !== 'undefined') {
            const processResult = EmptyRowProcessor.processEmptyRows();
            if (processResult && processResult.totalExtraLines > 0) {
                console.log('Обработка пустых строк завершена:', processResult);
            }
        }
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWoBushingsProcessesForm);
} else {
    // DOM уже загружен
    initWoBushingsProcessesForm();
}



