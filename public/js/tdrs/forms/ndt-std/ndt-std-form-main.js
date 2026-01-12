/**
 * NDTStdFormMain - главный файл инициализации для NDT Standard формы
 * 
 * Примечание: table-height-adjuster.js отключен для ndtFormStd.
 * Управление количеством строк осуществляется через Print Settings.
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initNDTStdForm() {
    // Применяем патч для Chart.js (если используется)
    if (typeof ChartJSPatcher !== 'undefined') {
        ChartJSPatcher.applyPatch();
    }

    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page');
    if (dataPages.length === 0) {
        console.warn('NDT Standard страницы не найдены');
        return;
    }

    console.log('Инициализация NDT Standard формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');

    // Управление строками теперь осуществляется через Print Settings
    // Лимиты строк применяются автоматически при загрузке страницы и перед печатью
    // через функции в основном скрипте ndtFormStd.blade.php
}

