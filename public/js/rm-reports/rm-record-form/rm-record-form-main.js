/**
 * RmRecordFormMain - главный файл инициализации для R&M Record формы
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initRmRecordForm() {
    // Проверяем, что функция adjustTableHeightToRange загружена
    if (typeof adjustTableHeightToRange === 'undefined') {
        console.error('❌ КРИТИЧЕСКАЯ ОШИБКА: Функция adjustTableHeightToRange не загружена!');
        console.error('Проверьте, что файл js/table-height-adjuster.js существует и доступен.');
        return;
    }

    // Сначала настраиваем высоту таблицы
    setTimeout(function() {
        RmRecordTableHeightManager.adjustTableHeight();

        // Затем выводим информацию о высотах
        RmRecordTableDiagnostics.logTablesHeight();
    }, 100); // Небольшая задержка для полной отрисовки
}

// Вызываем функции после полной загрузки страницы
window.addEventListener('load', function() {
    initRmRecordForm();
});

// Также можно вызвать функцию вручную через консоль: getTablesHeight() или adjustTableHeight()


