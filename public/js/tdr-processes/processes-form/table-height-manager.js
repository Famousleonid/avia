/**
 * TableHeightManager - модуль для настройки высоты таблиц
 * Использует библиотеку table-height-adjuster.js для автоматической настройки
 */
class TableHeightManager {
    /**
     * Конфигурация для NDT таблицы
     */
    static NDT_CONFIG = {
        min_height_tab: 570,
        max_height_tab: 620,
        tab_name: '.ndt-data-container',
        row_selector: '.data-row-ndt[data-row-index]',
        max_iterations: 50
    };

    /**
     * Конфигурация для обычной таблицы
     */
    static REGULAR_CONFIG = {
        min_height_tab: 720,
        max_height_tab: 770,
        tab_name: '.data-page',
        row_selector: '.data-page [data-row-index]',
        max_iterations: 50
    };

    /**
     * Инициализирует настройку высоты для NDT таблицы
     * @param {Function} heightCalculator - Функция для вычисления высоты строк
     * @param {Function} onComplete - Callback при завершении
     */
    static initNDTTable(heightCalculator, onComplete) {
        const ndtRows = document.querySelectorAll('.data-row-ndt');
        if (ndtRows.length === 0) {
            console.log('NDT таблица не найдена');
            return;
        }

        const ndtDataContainer = document.querySelector(this.NDT_CONFIG.tab_name);
        if (!ndtDataContainer) {
            console.warn('Контейнер NDT таблицы не найден');
            return;
        }

        // Вычисляем реальную среднюю высоту строк NDT
        const ndtRowHeight = heightCalculator(ndtRows);

        // Проверяем наличие библиотеки
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена. Проверьте подключение скрипта table-height-adjuster.js');
            return;
        }

        adjustTableHeightToRange({
            min_height_tab: this.NDT_CONFIG.min_height_tab,
            max_height_tab: this.NDT_CONFIG.max_height_tab,
            tab_name: this.NDT_CONFIG.tab_name,
            row_height: ndtRowHeight,
            row_selector: this.NDT_CONFIG.row_selector,
            addRowCallback: RowManager.addEmptyRowNDT,
            removeRowCallback: RowManager.removeRowNDT,
            getRowIndexCallback: RowManager.getRowIndex,
            max_iterations: this.NDT_CONFIG.max_iterations,
            onComplete: function(currentHeight, rowCount) {
                console.log(
                    `NDT таблица настроена: высота ${currentHeight}px, строк ${rowCount}, средняя высота строки ${ndtRowHeight}px`
                );
                if (onComplete) {
                    onComplete('NDT', currentHeight, rowCount, ndtRowHeight);
                }
            }
        });
    }

    /**
     * Инициализирует настройку высоты для обычной таблицы
     * @param {Function} heightCalculator - Функция для вычисления высоты строк
     * @param {Function} onComplete - Callback при завершении
     */
    static initRegularTable(heightCalculator, onComplete) {
        const regularTableContainer = document.querySelector(this.REGULAR_CONFIG.tab_name);
        const regularRows = document.querySelectorAll('.data-page .data-row:not(.data-row-ndt)');
        
        if (!regularTableContainer || regularRows.length === 0) {
            console.log('Обычная таблица не найдена');
            return;
        }

        // Вычисляем реальную среднюю высоту строк с данными
        const dataRowHeight = heightCalculator(regularRows);

        // Также учитываем пустые строки для более точного расчета
        const emptyRows = document.querySelectorAll('.data-page .empty-row');
        const emptyRowHeight = emptyRows.length > 0 ? heightCalculator(emptyRows) : 32;

        // Используем среднее значение между высотой строк с данными и пустых строк
        // или высоту строк с данными, если она больше
        const avgRowHeight = Math.max(dataRowHeight, emptyRowHeight);

        // Проверяем текущую высоту таблицы перед настройкой
        const initialHeight = regularTableContainer.offsetHeight;
        const initialRowCount = regularTableContainer.querySelectorAll('[data-row-index]').length;

        console.log(
            `Обычная таблица: высота строк с данными ${dataRowHeight}px, пустых строк ${emptyRowHeight}px, используется ${avgRowHeight}px`
        );
        console.log(
            `Текущая высота таблицы: ${initialHeight}px, текущее количество строк: ${initialRowCount}`
        );
        console.log(
            `Целевой диапазон: ${this.REGULAR_CONFIG.min_height_tab}-${this.REGULAR_CONFIG.max_height_tab}px`
        );

        // Проверяем наличие библиотеки
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена. Проверьте подключение скрипта table-height-adjuster.js');
            return;
        }

        adjustTableHeightToRange({
            min_height_tab: this.REGULAR_CONFIG.min_height_tab,
            max_height_tab: this.REGULAR_CONFIG.max_height_tab,
            tab_name: this.REGULAR_CONFIG.tab_name,
            row_height: avgRowHeight,
            row_selector: this.REGULAR_CONFIG.row_selector,
            addRowCallback: RowManager.addEmptyRowRegular,
            removeRowCallback: RowManager.removeRowRegular,
            getRowIndexCallback: RowManager.getRowIndex,
            max_iterations: this.REGULAR_CONFIG.max_iterations,
            onComplete: function(currentHeight, rowCount) {
                console.log(
                    `Обычная таблица настроена: высота ${currentHeight}px, строк ${rowCount}, средняя высота строки ${avgRowHeight}px`
                );
                console.log(
                    `Изменение высоты: ${currentHeight - initialHeight}px (было ${initialHeight}px, стало ${currentHeight}px)`
                );

                // Если высота не изменилась, выводим предупреждение
                if (Math.abs(currentHeight - initialHeight) < 5) {
                    console.warn('ВНИМАНИЕ: Высота таблицы практически не изменилась!');
                    console.warn('Возможные причины:');
                    console.warn(`1. Таблица уже находится в целевом диапазоне (${currentHeight}px между ${this.REGULAR_CONFIG.min_height_tab}-${this.REGULAR_CONFIG.max_height_tab}px)`);
                    console.warn('2. Недостаточно строк для достижения целевой высоты');
                    console.warn('3. CSS ограничения (max-height, overflow)');
                }

                if (onComplete) {
                    onComplete('Regular', currentHeight, rowCount, avgRowHeight, initialHeight);
                }
            }
        });
    }

    /**
     * Инициализирует настройку высоты для всех таблиц на странице
     * @param {Function} heightCalculator - Функция для вычисления высоты строк
     */
    static initAll(heightCalculator) {
        // Инициализируем NDT таблицу
        this.initNDTTable(heightCalculator);

        // Инициализируем обычную таблицу
        this.initRegularTable(heightCalculator);
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TableHeightManager;
}

