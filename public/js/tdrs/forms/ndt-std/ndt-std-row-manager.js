/**
 * NDTStdRowManager - модуль для управления строками NDT Standard таблицы
 * Использует базовый RowManager из tdr-processes
 */
class NDTStdRowManager {
    /**
     * Добавляет пустую строку для NDT таблицы
     * Переиспользует RowManager из tdr-processes
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {HTMLElement|null} Созданный элемент строки или null
     */
    static addEmptyRow(rowIndex, container) {
        // Переиспользуем RowManager из tdr-processes
        if (typeof RowManager !== 'undefined') {
            return RowManager.addEmptyRowNDT(rowIndex, container);
        }
        
        // Fallback если RowManager не загружен
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return null;
        }

        const row = document.createElement('div');
        row.className = 'row fs-85 data-row-ndt empty-row';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
        `;
        containerElement.appendChild(row);
        return row;
    }

    /**
     * Удаляет строку NDT таблицы по индексу
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {boolean} true если строка была удалена
     */
    static removeRow(rowIndex, container) {
        // Переиспользуем RowManager из tdr-processes
        if (typeof RowManager !== 'undefined') {
            return RowManager.removeRowNDT(rowIndex, container);
        }
        
        // Fallback
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            return false;
        }

        const row = containerElement.querySelector(`.data-row-ndt[data-row-index="${rowIndex}"]`);
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
    module.exports = NDTStdRowManager;
}

