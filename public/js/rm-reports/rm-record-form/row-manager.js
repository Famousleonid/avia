/**
 * RmRecordRowManager - модуль для управления строками R&M Record таблицы
 * Каждая строка состоит из 7 ячеек (div11-div17)
 */
class RmRecordRowManager {
    /**
     * Добавляет пустую строку с правильной нумерацией
     * @param {number} rowIndex - Индекс строки
     * @returns {HTMLElement[]|null} Массив созданных элементов ячеек или null
     */
    static addEmptyRow(rowIndex) {
        const parent = document.querySelector('.parent');
        if (!parent) {
            console.warn('Контейнер .parent не найден');
            return null;
        }

        // Создаем все 7 ячеек строки
        const div11 = document.createElement('div');
        div11.className = 'div11 border-l-b text-center align-content-center fs-75 data-row';
        div11.style.minHeight = '37px';
        div11.setAttribute('data-row-index', rowIndex);
        // Нумерация для пустых строк продолжается от последнего используемого Item
        const nextItemNumber = this.getMaxItemNumber() + 1;
        div11.textContent = nextItemNumber;

        const div12 = document.createElement('div');
        div12.className = 'div12 border-l-b text-center align-content-center fs-75 data-row';
        div12.setAttribute('data-row-index', rowIndex);

        const div13 = document.createElement('div');
        div13.className = 'div13 border-l-b text-center align-content-center fs-75 data-row';
        div13.setAttribute('data-row-index', rowIndex);

        const div14 = document.createElement('div');
        div14.className = 'div14 border-l-b text-center align-content-center fs-75 data-row';
        div14.setAttribute('data-row-index', rowIndex);

        const div15 = document.createElement('div');
        div15.className = 'div15 border-l-b text-center align-content-center fs-75 data-row';
        div15.style.color = 'lightgray';
        div15.setAttribute('data-row-index', rowIndex);
        div15.textContent = 'tech stamp';

        const div16 = document.createElement('div');
        div16.className = 'div16 border-l-b text-center align-content-center fs-75 data-row';
        div16.style.color = 'lightgray';
        div16.setAttribute('data-row-index', rowIndex);
        div16.textContent = 'tech stamp';

        const div17 = document.createElement('div');
        div17.className = 'div17 border-l-b-r text-center align-content-center fs-75 data-row';
        div17.setAttribute('data-row-index', rowIndex);

        // Добавляем все ячейки в контейнер
        parent.appendChild(div11);
        parent.appendChild(div12);
        parent.appendChild(div13);
        parent.appendChild(div14);
        parent.appendChild(div15);
        parent.appendChild(div16);
        parent.appendChild(div17);

        return [div11, div12, div13, div14, div15, div16, div17];
    }

    /**
     * Удаляет строку по индексу
     * @param {number} rowIndex - Индекс строки
     * @returns {boolean} true если строка была удалена
     */
    static removeRow(rowIndex) {
        const rows = document.querySelectorAll(`.parent .data-row[data-row-index="${rowIndex}"]`);
        if (rows.length === 0) {
            return false;
        }
        rows.forEach(row => row.remove());
        return true;
    }

    /**
     * Получает индекс строки из элемента
     * @param {HTMLElement} rowElement - Элемент строки (ячейка)
     * @returns {number} Индекс строки или 0
     */
    static getRowIndex(rowElement) {
        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
    }

    /**
     * Получает максимальный номер в колонке "Item"
     * @returns {number} Максимальный номер Item
     */
    static getMaxItemNumber() {
        let max = 0;
        const itemCells = document.querySelectorAll('.parent .div11.data-row');
        itemCells.forEach(cell => {
            const num = parseInt((cell.textContent || '').trim(), 10);
            if (!isNaN(num) && num > max) {
                max = num;
            }
        });
        return max;
    }

    /**
     * Получает максимальный индекс строки
     * @returns {number} Максимальный индекс строки
     */
    static getMaxRowIndex() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        let maxIndex = 0;
        rows.forEach(row => {
            const index = parseInt(row.getAttribute('data-row-index'));
            if (!isNaN(index) && index > maxIndex) {
                maxIndex = index;
            }
        });
        return maxIndex;
    }

    /**
     * Получает текущее количество строк в таблице
     * @returns {number} Количество строк
     */
    static getCurrentRowCount() {
        const rows = document.querySelectorAll('.parent .data-row[data-row-index]');
        const rowIndices = new Set();
        rows.forEach(row => {
            const index = parseInt(row.getAttribute('data-row-index'));
            if (!isNaN(index) && index > 0) {
                rowIndices.add(index);
            }
        });
        return rowIndices.size;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RmRecordRowManager;
}




