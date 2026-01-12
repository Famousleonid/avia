/**
 * CADFormMain - главный файл инициализации для CAD формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initCADForm() {
    // Проверяем наличие контейнера для строк
    const allRowsContainer = document.querySelector('.all-rows-container');
    if (!allRowsContainer) {
        console.warn('CAD контейнер .all-rows-container не найден');
        return;
    }

    console.log('Инициализация CAD формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');
    
    // table-height-adjuster.js отключен
    // Управление количеством строк осуществляется через Print Settings
    // Лимиты строк применяются автоматически при загрузке страницы и перед печатью
    // через функции в основном скрипте cadFormStd.blade.php
    // Страницы создаются динамически через JavaScript функцию applyTableRowLimits
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCADForm);
} else {
    initCADForm();
}




