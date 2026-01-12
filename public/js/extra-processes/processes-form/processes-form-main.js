/**
 * ExtraProcessesFormMain - главный файл инициализации для страницы формы extra процесса
 * Координирует работу модулей для обработки пустых строк
 * 
 * Примечание: table-height-adjuster.js отключен для extra_processes.
 * Управление количеством строк осуществляется через Print Settings.
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initExtraProcessesForm() {
    // Проверяем наличие необходимых элементов
    const hasNDTTable = document.querySelectorAll('.data-row-ndt').length > 0;
    const hasRegularTable = document.querySelector('.data-page') !== null;

    if (!hasNDTTable && !hasRegularTable) {
        console.log('Таблицы процессов не найдены на странице');
        return;
    }

    console.log('Инициализация extra_processes формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');

    // Обработка пустых строк на основе высоты ячеек процесса
    // Используем задержку для обеспечения полного рендеринга
    setTimeout(function() {
        // Проверяем наличие EmptyRowProcessor
        if (typeof EmptyRowProcessor === 'undefined') {
            console.warn('EmptyRowProcessor не загружен. Проверьте подключение скрипта empty-row-processor.js');
            return;
        }

        // Обработка пустых строк на основе высоты ячеек процесса
        // Это специфично для extra_processes
        const processResult = EmptyRowProcessor.processEmptyRows();
        if (processResult && processResult.totalExtraLines > 0) {
            console.log('Обработка пустых строк завершена:', processResult);
        } else if (processResult) {
            console.log('Обработка пустых строк: дополнительных строк не обнаружено');
        } else {
            console.log('Обработка пустых строк: ячейки процесса не найдены (это нормально для NDT таблиц)');
        }
    }, 200);
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initExtraProcessesForm);
} else {
    // DOM уже загружен
    initExtraProcessesForm();
}
