/**
 * RowManager - модуль для управления строками таблиц
 * Отвечает за добавление и удаление пустых строк для настройки высоты таблиц
 */
class RowManager {
    /**
     * Добавляет пустую строку для NDT таблицы
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {HTMLElement|null} Созданный элемент строки или null
     */
    static addEmptyRowNDT(rowIndex, container) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            console.warn('Контейнер для NDT строки не найден');
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
    static removeRowNDT(rowIndex, container) {
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
     * Добавляет пустую строку для обычной таблицы
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {HTMLElement|null} Созданный элемент строки или null
     */
    static addEmptyRowRegular(rowIndex, container) {
        const containerElement = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerElement) {
            console.warn('Контейнер для обычной строки не найден');
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
     * Удаляет строку обычной таблицы по индексу
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {boolean} true если строка была удалена
     */
    static removeRowRegular(rowIndex, container) {
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
        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RowManager;
}



