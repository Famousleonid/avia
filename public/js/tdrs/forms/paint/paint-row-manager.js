/**
 * PaintRowManager - модуль для управления строками Paint таблицы
 * Только визуальная настройка - строки не добавляются/удаляются
 */
class PaintRowManager {
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
     * Пустая функция - строки не удаляются (только визуальная настройка)
     * @param {number} rowIndex - Индекс строки
     * @param {HTMLElement|string} container - Контейнер или селектор
     * @returns {boolean}
     */
    static removeRow(rowIndex, container) {
        // Не удаляем строки - только визуальная настройка
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
    module.exports = PaintRowManager;
}




