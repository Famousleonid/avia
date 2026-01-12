/**
 * EmptyRowProcessor - модуль для обработки пустых строк на основе высоты ячеек процесса
 * Специфично для extra_processes - удаляет лишние пустые строки для компенсации длинного текста
 */
class EmptyRowProcessor {
    /**
     * Обрабатывает пустые строки на основе высоты ячеек процесса
     * Удаляет лишние пустые строки для компенсации дополнительной высоты от длинного текста
     */
    static processEmptyRows() {
        const processCells = document.querySelectorAll('.data-row .process-cell');
        if (processCells.length === 0) {
            console.log('Ячейки процесса не найдены (это нормально для NDT таблиц)');
            return {
                totalExtraLines: 0,
                rowsToRemove: 0,
                removedCount: 0
            };
        }

        let totalExtraLines = 0;

        processCells.forEach(function(cell) {
            const extraLines = this.calculateExtraLines(cell);
            totalExtraLines += extraLines;
        }, this);

        const emptyRowsToRemove = Math.floor(totalExtraLines / 2);
        const emptyRows = document.querySelectorAll('.empty-row');

        let removedCount = 0;
        for (let i = 0; i < emptyRowsToRemove && i < emptyRows.length; i++) {
            // Удаляем только если строка не была добавлена функцией adjustTableHeightToRange
            // (не имеет атрибута data-keep)
            if (emptyRows[i] && !emptyRows[i].hasAttribute('data-keep')) {
                emptyRows[i].remove();
                removedCount++;
            }
        }

        console.log("Всего дополнительных строк:", totalExtraLines);
        console.log("Пустых строк для удаления:", emptyRowsToRemove);
        console.log("Удалено пустых строк:", removedCount);

        return {
            totalExtraLines: totalExtraLines,
            rowsToRemove: emptyRowsToRemove,
            removedCount: removedCount
        };
    }

    /**
     * Вычисляет количество дополнительных строк для ячейки процесса
     * @param {HTMLElement} cell - Ячейка процесса
     * @returns {number} Количество дополнительных строк
     */
    static calculateExtraLines(cell) {
        const cellHeight = cell.offsetHeight;
        const baseHeight = 32; // Базовая высота строки
        const lineHeight = 16; // Высота одной строки текста

        if (cellHeight > baseHeight) {
            const extraLines = Math.floor((cellHeight - baseHeight) / lineHeight);
            return extraLines;
        }

        return 0;
    }

    /**
     * Удаляет указанное количество пустых строк
     * @param {number} rowsToRemove - Количество строк для удаления
     * @returns {number} Количество фактически удаленных строк
     */
    static removeExtraEmptyRows(rowsToRemove) {
        const emptyRows = document.querySelectorAll('.empty-row');
        let removedCount = 0;

        for (let i = 0; i < rowsToRemove && i < emptyRows.length; i++) {
            // Удаляем только если строка не была добавлена функцией adjustTableHeightToRange
            if (emptyRows[i] && !emptyRows[i].hasAttribute('data-keep')) {
                emptyRows[i].remove();
                removedCount++;
            }
        }

        return removedCount;
    }

    /**
     * Получает статистику по ячейкам процесса
     * @returns {Object} Статистика с информацией о ячейках
     */
    static getProcessCellsStats() {
        const processCells = document.querySelectorAll('.data-row .process-cell');
        const stats = {
            totalCells: processCells.length,
            cellsWithExtraHeight: 0,
            totalExtraHeight: 0,
            totalExtraLines: 0
        };

        processCells.forEach(function(cell) {
            const cellHeight = cell.offsetHeight;
            if (cellHeight > 32) {
                stats.cellsWithExtraHeight++;
                stats.totalExtraHeight += (cellHeight - 32);
                stats.totalExtraLines += this.calculateExtraLines(cell);
            }
        }, this);

        return stats;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmptyRowProcessor;
}




