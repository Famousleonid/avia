/**
 * HeightCalculator - модуль для вычисления высот строк и таблиц
 * Используется для точной настройки высоты таблиц при печати
 */
class HeightCalculator {
    /**
     * Вычисляет реальную среднюю высоту строк
     * @param {NodeList|Array} rows - Коллекция элементов строк
     * @param {number} defaultHeight - Высота по умолчанию, если строки не найдены
     * @returns {number} Средняя высота строк в пикселях
     */
    static calculateAverageRowHeight(rows, defaultHeight = 32) {
        if (!rows || rows.length === 0) {
            console.log(`Нет строк для расчета, используется высота по умолчанию: ${defaultHeight}px`);
            return defaultHeight;
        }

        let totalHeight = 0;
        let count = 0;

        rows.forEach(function(row) {
            const rowHeight = row.offsetHeight;
            if (rowHeight > 0) {
                totalHeight += rowHeight;
                count++;
            }
        });

        const averageHeight = count > 0 ? Math.round(totalHeight / count) : defaultHeight;
        console.log(`Средняя высота строк: ${averageHeight}px (из ${count} строк)`);
        return averageHeight;
    }

    /**
     * Вычисляет общую высоту всех строк в контейнере
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @param {string} rowSelector - Селектор строк
     * @returns {Object} Объект с общей высотой и количеством строк
     */
    static calculateTotalRowsHeight(container, rowSelector) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return { totalHeight: 0, rowCount: 0 };
        }

        const rows = containerElement.querySelectorAll(rowSelector);
        let totalHeight = 0;

        rows.forEach(function(row) {
            totalHeight += row.offsetHeight;
        });

        return {
            totalHeight: totalHeight,
            rowCount: rows.length
        };
    }

    /**
     * Сравнивает высоту контейнера с суммой высот строк
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @param {string} rowSelector - Селектор строк
     * @returns {Object} Результат сравнения с предупреждениями
     */
    static compareContainerAndRowsHeight(container, rowSelector) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return {
                containerHeight: 0,
                rowsHeight: 0,
                difference: 0,
                hasWarning: false
            };
        }

        const containerHeight = containerElement.offsetHeight;
        const rowsData = this.calculateTotalRowsHeight(containerElement, rowSelector);
        const difference = Math.abs(containerHeight - rowsData.totalHeight);
        const hasWarning = difference > 50; // Предупреждение, если разница больше 50px

        if (hasWarning) {
            console.warn(
                `Внимание: разница между высотой контейнера и суммой высот строк составляет ${difference}px`
            );
        }

        return {
            containerHeight: containerHeight,
            rowsHeight: rowsData.totalHeight,
            difference: difference,
            hasWarning: hasWarning,
            rowCount: rowsData.rowCount
        };
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeightCalculator;
}


