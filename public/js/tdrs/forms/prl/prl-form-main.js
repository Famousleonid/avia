/**
 * PRLFormMain - главный файл инициализации для PRL формы
 * 
 * PRL/KIT/Bush PRL pages are split by the actual rendered row height.
 * The user controls the font size; page row counts are calculated automatically.
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

    console.log('PRL form initialized with automatic height pagination');
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPRLForm);
} else {
    initPRLForm();
}

