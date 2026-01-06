/**
 * CADRowManager - модуль для управления строками CAD таблицы
 * Удаляет только пустые строки (защита данных)
 */
class CADRowManager {
    /**
     * Пустая функция - строки не добавляются (генерируются на бэкенде)
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {null}
     */
    static addEmptyRow(rowIndex, container) {
        // Не добавляем строки - они уже на бэкенде
        return null;
    }

    /**
     * Удаляет только пустые строки (защита данных)
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {boolean} true если строка была удалена
     */
    static removeRow(rowIndex, container) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return false;
        }

        const row = containerElement.querySelector(`[data-row-index="${rowIndex}"]`);
        
        // Удаляем только пустые строки, не строки с данными
        if (row && row.classList.contains('empty-row')) {
            row.remove();
            return true;
        } else if (row) {
            // Если это строка с данными, не удаляем её
            console.warn(`CAD: Попытка удалить строку с данными (rowIndex: ${rowIndex}), пропускаем`);
            return false;
        }
        
        return false;
    }

    /**
     * Получает индекс строки из элемента
     * @param {HTMLElement} rowElement - Элемент строки
     * @returns {number} Индекс строки или 0
     */
    static getRowIndex(rowElement) {
        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CADRowManager;
}


