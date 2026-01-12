/**
 * PRLFormMain - главный файл инициализации для PRL формы
 * 
 * Примечание: table-height-adjuster.js отключен для prlForm.
 * Управление количеством строк осуществляется через Print Settings.
 */

/**
 * Инициализирует все модули при загрузке страницы
 */
function initPRLForm() {
    // Проверяем наличие необходимых элементов
    const dataPages = document.querySelectorAll('.data-page, .page');
    if (dataPages.length === 0) {
        console.warn('PRL страницы не найдены');
        return;
    }

    console.log('Инициализация PRL формы (table-height-adjuster.js отключен)');
    console.log('Управление количеством строк осуществляется через Print Settings');

    // Управление строками теперь осуществляется через Print Settings
    // Лимиты строк применяются автоматически при загрузке страницы и перед печатью
    // через функции в основном скрипте prlForm.blade.php
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPRLForm);
} else {
    initPRLForm();
}

