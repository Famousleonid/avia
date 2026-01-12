/**
 * PaintFormMain - главный файл инициализации для Paint формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initPaintForm() {
    // Проверяем наличие контейнера для строк
    const allRowsContainer = document.querySelector('.all-rows-container');
    if (!allRowsContainer) {
        console.warn('Paint контейнер .all-rows-container не найден');
        return;
    }

    console.log('Инициализация Paint формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');
    
    // table-height-adjuster.js отключен
    // Управление количеством строк осуществляется через Print Settings
    // Лимиты строк применяются автоматически при загрузке страницы и перед печатью
    // через функции в основном скрипте paintFormStd.blade.php
    // Страницы создаются динамически через JavaScript функцию applyTableRowLimits
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPaintForm);
} else {
    initPaintForm();
}




