/**
 * PRLRowManager - модуль для управления строками PRL таблицы
 * Специфичная структура строк PRL (col-5 и col-7)
 */
class PRLRowManager {
    /**
     * Добавляет пустую строку для PRL таблицы
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {HTMLElement|null} Созданный элемент строки или null
     */
    static addEmptyRow(rowIndex, container) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            console.warn('Контейнер для PRL строки не найден');
            return null;
        }

        const row = document.createElement('div');
        row.className = 'row data-row-prl empty-row';
        row.style.width = '100%';
        row.setAttribute('data-row-index', rowIndex);
        row.innerHTML = `
            <div class="prl-col-fig border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-item border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-desc border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-part border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-qty border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-code border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-po border-l-b align-content-center"><h6></h6></div>
            <div class="prl-col-notes border-l-b-r align-content-center"><h6></h6></div>
        `;
        containerElement.appendChild(row);
        return row;
    }

    /**
     * Удаляет строку PRL таблицы по индексу
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

        const row = containerElement.querySelector(`.data-row-prl[data-row-index="${rowIndex}"]`);
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
        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PRLRowManager;
}




