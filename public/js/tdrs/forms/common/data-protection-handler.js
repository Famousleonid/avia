/**
 * DataProtectionHandler - модуль для защиты строк с данными от удаления
 * Используется формами, которые должны удалять только пустые строки
 */
class DataProtectionHandler {
    /**
     * Создает функцию удаления строки с защитой данных
     * @param {string} rowSelector - Селектор строк
     * @param {string} emptyRowClass - Класс пустых строк (по умолчанию 'empty-row')
     * @returns {Function} Функция для удаления строки
     */
    static createProtectedRemoveFunction(rowSelector, emptyRowClass = 'empty-row') {
        return function(rowIndex, tableElement) {
            const container = typeof tableElement === 'string'
                ? document.querySelector(tableElement)
                : tableElement;
            
            if (!container) {
                return false;
            }

            const row = container.querySelector(`${rowSelector}[data-row-index="${rowIndex}"]`);
            
            // Удаляем только пустые строки
            if (row && row.classList.contains(emptyRowClass)) {
                row.remove();
                return true;
            } else if (row) {
                // Если это строка с данными, не удаляем её
                console.warn(`Попытка удалить строку с данными (rowIndex: ${rowIndex}), пропускаем`);
                return false;
            }
            
            return false;
        };
    }

    /**
     * Проверяет, является ли строка пустой
     * @param {HTMLElement} row - Элемент строки
     * @param {string} emptyRowClass - Класс пустых строк
     * @returns {boolean} true если строка пустая
     */
    static isEmptyRow(row, emptyRowClass = 'empty-row') {
        return row && row.classList.contains(emptyRowClass);
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataProtectionHandler;
}

