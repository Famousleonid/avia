/**
 * NDTFormMain - главный файл инициализации для NDT формы (простая)
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initNDTForm() {
    // Проверяем наличие необходимых элементов
    const ndtDataContainer = document.querySelector('.data-page');
    const ndtRows = document.querySelectorAll('.data-row-ndt');
    
    if (!ndtDataContainer || ndtRows.length === 0) {
        console.warn('NDT таблица не найдена');
        return;
    }

    // Настройка высоты таблицы после загрузки
    setTimeout(function() {
        if (typeof adjustTableHeightToRange === 'undefined') {
            console.error('adjustTableHeightToRange не загружена');
            return;
        }

        // Переиспользуем RowManager из tdr-processes
        const addRowCallback = typeof RowManager !== 'undefined' 
            ? RowManager.addEmptyRowNDT 
            : function() { return null; };
        
        const removeRowCallback = typeof RowManager !== 'undefined'
            ? RowManager.removeRowNDT
            : function() { return false; };
        
        const getRowIndexCallback = typeof RowManager !== 'undefined'
            ? RowManager.getRowIndex
            : function(rowElement) {
                return parseInt(rowElement.getAttribute('data-row-index')) || 0;
            };

        adjustTableHeightToRange({
            min_height_tab: 500,
            max_height_tab: 600,
            tab_name: '.data-page',
            row_height: 32,
            row_selector: '.data-row-ndt[data-row-index]',
            addRowCallback: addRowCallback,
            removeRowCallback: removeRowCallback,
            getRowIndexCallback: getRowIndexCallback,
            max_iterations: 50,
            onComplete: function(currentHeight, rowCount) {
                console.log(`NDT таблица настроена: высота ${currentHeight}px, строк ${rowCount}`);
            }
        });
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNDTForm);
} else {
    initNDTForm();
}

