/**
 * StressRowManager - модуль для управления строками Stress Relief таблицы
 * Переиспользует базовый RowManager из tdr-processes
 */
class StressRowManager {
    /**
     * Добавляет пустую строку для обычной таблицы
     * Переиспользует RowManager из tdr-processes
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {HTMLElement|null} Созданный элемент строки или null
     */
    static addEmptyRow(rowIndex, container) {
        // Переиспользуем RowManager из tdr-processes
        if (typeof RowManager !== 'undefined') {
            return RowManager.addEmptyRowRegular(rowIndex, container);
        }
        
        // Fallback
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return null;
        }

        const row = document.createElement('div');
        row.className = 'row empty-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-4 border-l-b text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
        `;
        containerElement.appendChild(row);
        return row;
    }

    /**
     * Удаляет строку таблицы по индексу
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {boolean} true если строка была удалена
     */
    static removeRow(rowIndex, container) {
        // Переиспользуем RowManager из tdr-processes
        if (typeof RowManager !== 'undefined') {
            return RowManager.removeRowRegular(rowIndex, container);
        }
        
        // Fallback
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return false;
        }

        const row = containerElement.querySelector(`[data-row-index="${rowIndex}"]`);
        if (row) {
            row.remove();
            return true;
        }
        return false;
    }

    /**
     * Получает индекс строки из элемента
     * @param {HTMLElement} rowElement - Элемент строки
     * @returns {number} Индекс строки или 0
     */
    static getRowIndex(rowElement) {
        if (typeof RowManager !== 'undefined') {
            return RowManager.getRowIndex(rowElement);
        }
        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StressRowManager;
}


