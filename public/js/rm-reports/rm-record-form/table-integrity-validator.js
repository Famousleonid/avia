/**
 * RmRecordTableIntegrityValidator - модуль для проверки целостности строк таблицы
 * Проверяет, что каждая строка имеет ровно 7 ячеек
 */
class RmRecordTableIntegrityValidator {
    /**
     * Проверяет целостность строк (все строки должны иметь 7 ячеек)
     * @returns {Object} Объект с результатами проверки: {isValid, issues, rowCount, totalCells}
     */
    static validateRowIntegrity() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        const rowGroups = {};

        // Группируем ячейки по индексу строки
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!isNaN(index) && index > 0) {
                if (!rowGroups[index]) {
                    rowGroups[index] = [];
                }
                rowGroups[index].push(cell);
            }
        });

        // Проверяем, что каждая строка имеет 7 ячеек
        const issues = [];
        Object.keys(rowGroups).forEach(index => {
            const cellCount = rowGroups[index].length;
            if (cellCount !== 7) {
                issues.push(`Строка ${index} имеет ${cellCount} ячеек вместо 7`);
            }
        });

        return {
            isValid: issues.length === 0,
            issues: issues,
            rowCount: Object.keys(rowGroups).length,
            totalCells: rows.length
        };
    }

    /**
     * Проверяет целостность конкретной строки по индексу
     * @param {number} rowIndex - Индекс строки
     * @returns {Object} Объект с результатами проверки: {isValid, cellCount, expectedCells}
     */
    static validateRowIntegrityByIndex(rowIndex) {
        const rows = document.querySelectorAll(`.parent .data-row[data-row-index="${rowIndex}"]`);
        const cellCount = rows.length;
        const expectedCells = 7;

        return {
            isValid: cellCount === expectedCells,
            cellCount: cellCount,
            expectedCells: expectedCells,
            rowIndex: rowIndex
        };
    }

    /**
     * Получает список всех строк с проблемами целостности
     * @returns {Array} Массив объектов с информацией о проблемных строках
     */
    static getProblematicRows() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        const rowGroups = {};
        const problematicRows = [];

        // Группируем ячейки по индексу строки
        rows.forEach(cell => {
            const index = parseInt(cell.getAttribute('data-row-index'));
            if (!isNaN(index) && index > 0) {
                if (!rowGroups[index]) {
                    rowGroups[index] = [];
                }
                rowGroups[index].push(cell);
            }
        });

        // Находим строки с проблемами
        Object.keys(rowGroups).forEach(index => {
            const cellCount = rowGroups[index].length;
            if (cellCount !== 7) {
                problematicRows.push({
                    rowIndex: parseInt(index),
                    cellCount: cellCount,
                    expectedCells: 7,
                    cells: rowGroups[index]
                });
            }
        });

        return problematicRows;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RmRecordTableIntegrityValidator;
}




