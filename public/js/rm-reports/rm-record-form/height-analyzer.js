/**
 * RmRecordHeightAnalyzer - модуль для анализа высот строк и таблиц
 * Учитывает разную высоту строк из-за переноса текста
 * Использует базовые функции из HeightCalculator для общих вычислений
 */
class RmRecordHeightAnalyzer {
    /**
     * Измеряет реальную высоту строк с учетом разного содержимого
     * @returns {number} Средняя высота строки в пикселях
     */
    static getActualRowHeight() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        if (rows.length === 0) {
            return 37; // Возвращаем значение по умолчанию, если строк нет
        }

        // Группируем строки по индексу (каждая строка состоит из 7 ячеек)
        const rowGroups = {};
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!isNaN(index) && index > 0) {
                if (!rowGroups[index]) {
                    rowGroups[index] = [];
                }
                rowGroups[index].push(cell);
            }
        });

        // Измеряем высоту каждой строки (берем максимальную высоту среди ячеек строки)
        // Учитываем реальную высоту с данными, включая перенос текста
        const rowHeights = [];
        Object.keys(rowGroups).sort((a, b) => parseInt(a) - parseInt(b)).forEach(index => {
            const cells = rowGroups[index];
            let maxCellHeight = 0;
            cells.forEach(cell => {
                // Используем offsetHeight для получения реальной высоты с учетом содержимого
                const height = cell.offsetHeight || cell.clientHeight || 0;
                if (height > maxCellHeight) {
                    maxCellHeight = height;
                }
            });
            if (maxCellHeight > 0) {
                rowHeights.push(maxCellHeight);
            }
        });

        if (rowHeights.length === 0) {
            return 37; // Значение по умолчанию
        }

        // Возвращаем среднюю высоту строки (можно использовать Math.max для максимальной)
        const avgHeight = rowHeights.reduce((sum, h) => sum + h, 0) / rowHeights.length;
        const maxHeight = Math.max(...rowHeights);

        // Используем среднее значение, но не меньше минимальной высоты
        return Math.max(37, Math.round(avgHeight));
    }

    /**
     * Измеряет высоту заголовка таблицы
     * Использует базовую логику из HeightCalculator для вычисления максимальной высоты
     * @returns {number} Высота заголовка в пикселях
     */
    static getHeaderHeight() {
        const headerCells = document.querySelectorAll('.parent > div:not(.data-row)');
        if (headerCells.length === 0) {
            return 0;
        }

        // Используем HeightCalculator для вычисления средней высоты, если доступен
        // Иначе используем собственную логику
        if (typeof HeightCalculator !== 'undefined') {
            // Преобразуем NodeList в Array для совместимости
            const headerArray = Array.from(headerCells);
            const avgHeight = HeightCalculator.calculateAverageRowHeight(headerArray, 0);
            
            // Для заголовка берем максимальную высоту, а не среднюю
            let maxHeight = 0;
            headerCells.forEach(cell => {
                const height = cell.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });
            return maxHeight;
        }

        // Fallback: собственная логика
        let maxHeight = 0;
        headerCells.forEach(cell => {
            const height = cell.offsetHeight;
            if (height > maxHeight) {
                maxHeight = height;
            }
        });

        return maxHeight;
    }

    /**
     * Получает статистику по высотам строк
     * @returns {Object} Объект со статистикой: {min, max, avg, count, heights}
     */
    static getRowHeightStatistics() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        if (rows.length === 0) {
            return {
                min: 0,
                max: 0,
                avg: 0,
                count: 0,
                heights: []
            };
        }

        // Группируем строки по индексу (каждая строка состоит из 7 ячеек)
        const rowGroups = {};
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!isNaN(index) && index > 0) {
                if (!rowGroups[index]) {
                    rowGroups[index] = [];
                }
                rowGroups[index].push(cell);
            }
        });

        // Измеряем высоту каждой строки (берем максимальную высоту среди ячеек строки)
        // Это важно, так как ячейки в одной строке могут иметь разную высоту из-за переноса текста
        const rowHeights = [];
        Object.keys(rowGroups).sort((a, b) => parseInt(a) - parseInt(b)).forEach(index => {
            const cells = rowGroups[index];
            // Проверяем, что строка имеет все 7 ячеек
            if (cells.length !== 7) {
                console.warn(`getRowHeightStatistics: Строка ${index} имеет ${cells.length} ячеек вместо 7`);
            }

            let maxCellHeight = 0;
            cells.forEach(cell => {
                // Используем offsetHeight для получения реальной высоты с учетом содержимого (включая перенос текста)
                const height = cell.offsetHeight || cell.clientHeight || 0;
                if (height > maxCellHeight) {
                    maxCellHeight = height;
                }
            });
            if (maxCellHeight > 0) {
                rowHeights.push(maxCellHeight);
            }
        });

        if (rowHeights.length === 0) {
            return {
                min: 0,
                max: 0,
                avg: 0,
                count: 0,
                heights: []
            };
        }

        const min = Math.min(...rowHeights);
        const max = Math.max(...rowHeights);
        const avg = Math.round(rowHeights.reduce((sum, h) => sum + h, 0) / rowHeights.length);

        return {
            min: min,
            max: max,
            avg: avg,
            count: rowHeights.length,
            heights: rowHeights // Массив всех высот для детального анализа
        };
    }

    /**
     * Рассчитывает реальную сумму высот всех строк (учитывает разную высоту)
     * @returns {number} Сумма высот всех строк в пикселях
     */
    static getTotalRowsHeight() {
        const rowStats = this.getRowHeightStatistics();
        if (!rowStats || !rowStats.heights || rowStats.heights.length === 0) {
            return 0;
        }
        // Суммируем реальные высоты всех строк, а не используем среднюю
        return rowStats.heights.reduce((sum, height) => sum + height, 0);
    }

    /**
     * Детальный анализ расчетов высоты таблицы
     * @returns {Object|null} Объект с анализом или null если таблица не найдена
     */
    static analyzeTableHeightCalculations() {
        const table = document.querySelector('.parent');
        if (!table) {
            console.error('Таблица .parent не найдена');
            return null;
        }

        const headerHeight = this.getHeaderHeight();
        const rowStats = this.getRowHeightStatistics();
        const actualTableHeight = table.offsetHeight;
        const rowCount = RmRecordRowManager.getCurrentRowCount();

        // Получаем реальную сумму высот всех строк (учитывает разную высоту)
        const totalRowsHeight = this.getTotalRowsHeight();

        // Получаем CSS свойства таблицы для учета отступов и границ
        const computedStyle = window.getComputedStyle(table);
        const paddingTop = parseFloat(computedStyle.paddingTop) || 0;
        const paddingBottom = parseFloat(computedStyle.paddingBottom) || 0;
        const borderTop = parseFloat(computedStyle.borderTopWidth) || 0;
        const borderBottom = parseFloat(computedStyle.borderBottomWidth) || 0;
        const marginTop = parseFloat(computedStyle.marginTop) || 0;
        const marginBottom = parseFloat(computedStyle.marginBottom) || 0;

        const tableExtraHeight = paddingTop + paddingBottom + borderTop + borderBottom;

        // Расчеты на основе текущих данных
        // Используем РЕАЛЬНУЮ сумму высот всех строк, а не среднюю * количество
        const calculatedHeight = headerHeight + totalRowsHeight + tableExtraHeight;
        // Для диапазона используем минимальную и максимальную высоту
        const calculatedHeightMin = headerHeight + (rowCount * rowStats.min) + tableExtraHeight;
        const calculatedHeightMax = headerHeight + (rowCount * rowStats.max) + tableExtraHeight;

        // Целевые параметры
        const targetMinHeight = 583;
        const targetMaxHeight = 629;
        const targetRange = targetMaxHeight - targetMinHeight;

        // Расчет целевого количества строк (учитываем отступы и границы таблицы)
        const availableMinHeight = targetMinHeight - headerHeight - tableExtraHeight;
        const availableMaxHeight = targetMaxHeight - headerHeight - tableExtraHeight;
        const targetMinRows = Math.floor(availableMinHeight / rowStats.avg);
        const targetMaxRows = Math.floor(availableMaxHeight / rowStats.avg);

        // Проверка: если строки разной высоты, показываем разницу
        const rowsHeightDifference = rowStats.max - rowStats.min;
        const hasVariableRowHeights = rowsHeightDifference > 2; // Разница более 2px считается значительной

        const analysis = {
            actualTableHeight: actualTableHeight,
            headerHeight: headerHeight,
            rowCount: rowCount,
            rowStats: rowStats,
            totalRowsHeight: totalRowsHeight, // Реальная сумма высот всех строк
            calculatedHeight: calculatedHeight, // Использует реальную сумму высот
            calculatedHeightMin: calculatedHeightMin,
            calculatedHeightMax: calculatedHeightMax,
            targetMinHeight: targetMinHeight,
            targetMaxHeight: targetMaxHeight,
            targetRange: targetRange,
            availableMinHeight: availableMinHeight,
            availableMaxHeight: availableMaxHeight,
            targetMinRows: targetMinRows,
            targetMaxRows: targetMaxRows,
            isInRange: actualTableHeight >= targetMinHeight && actualTableHeight <= targetMaxHeight,
            difference: actualTableHeight < targetMinHeight
                ? targetMinHeight - actualTableHeight
                : actualTableHeight > targetMaxHeight
                    ? actualTableHeight - targetMaxHeight
                    : 0,
            tableExtraHeight: tableExtraHeight,
            hasVariableRowHeights: hasVariableRowHeights,
            rowsHeightDifference: rowsHeightDifference,
            cssProperties: {
                paddingTop: paddingTop,
                paddingBottom: paddingBottom,
                borderTop: borderTop,
                borderBottom: borderBottom,
                marginTop: marginTop,
                marginBottom: marginBottom
            }
        };

        return analysis;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RmRecordHeightAnalyzer;
}

