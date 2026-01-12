/**
 * MultiPageHandler - общий модуль для обработки множественных страниц
 * Используется формами, которые имеют несколько страниц (.data-page)
 */
class MultiPageHandler {
    /**
     * Инициализирует настройку высоты для всех страниц
     * @param {Object} config - Конфигурация для настройки
     * @param {Function} onPageComplete - Callback при завершении настройки каждой страницы
     */
    static initAllPages(config, onPageComplete) {
        const dataPages = document.querySelectorAll('.data-page');
        
        if (dataPages.length === 0) {
            console.log('Страницы не найдены');
            return;
        }

        dataPages.forEach(function(pageContainer, pageIndex) {
            const rows = pageContainer.querySelectorAll(config.row_selector);
            
            if (rows.length > 0) {
                if (typeof adjustTableHeightToRange === 'undefined') {
                    console.error('adjustTableHeightToRange не загружена');
                    return;
                }

                adjustTableHeightToRange({
                    min_height_tab: config.min_height_tab,
                    max_height_tab: config.max_height_tab,
                    tab_name: pageContainer,
                    row_height: config.row_height,
                    row_selector: config.row_selector,
                    addRowCallback: config.addRowCallback || function() {},
                    removeRowCallback: config.removeRowCallback || function() {},
                    getRowIndexCallback: config.getRowIndexCallback || function(rowElement) {
                        return parseInt(rowElement.getAttribute('data-row-index')) || 0;
                    },
                    max_iterations: config.max_iterations || 50,
                    onComplete: function(currentHeight, rowCount) {
                        console.log(`${config.formName} страница ${pageIndex + 1}: высота настроена - ${currentHeight}px, строк ${rowCount}`);
                        if (onPageComplete) {
                            onPageComplete(pageIndex + 1, currentHeight, rowCount);
                        }
                    }
                });
            }
        });
    }

    /**
     * Получает количество страниц
     * @returns {number} Количество страниц
     */
    static getPageCount() {
        return document.querySelectorAll('.data-page').length;
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MultiPageHandler;
}




