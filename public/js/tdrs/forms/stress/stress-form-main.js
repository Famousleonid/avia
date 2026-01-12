/**
 * StressFormMain - главный файл инициализации для Stress Relief формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initStressForm() {
    // Проверяем наличие контейнера для строк
    const allRowsContainer = document.querySelector('.all-rows-container');
    if (!allRowsContainer) {
        console.warn('Stress Relief контейнер .all-rows-container не найден');
        return;
    }

    console.log('Инициализация Stress Relief формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');
    
    // table-height-adjuster.js отключен
    // Управление количеством строк осуществляется через Print Settings
    // Лимиты строк применяются автоматически при загрузке страницы и перед печатью
    // через функции в основном скрипте stressFormStd.blade.php
    // Страницы создаются динамически через JavaScript функцию applyTableRowLimits
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Небольшая задержка для гарантии, что все элементы загружены
        setTimeout(initStressForm, 100);
    });
} else {
    // Если DOM уже загружен, добавляем небольшую задержку
    setTimeout(initStressForm, 100);
}

