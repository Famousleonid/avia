/**
 * ProcessesMain - главный файл инициализации для страницы процессов TDR
 * Координирует работу всех модулей
 */

// Конфигурация с URL для AJAX запросов
const ProcessesConfig = {
    updateOrderUrl: null,  // Будет установлено из Blade шаблона
    storeVendorUrl: null   // Будет установлено из Blade шаблона
};

/**
 * Инициализирует все модули при загрузке страницы
 */
function initTdrProcesses() {
    // Проверяем наличие необходимых элементов
    if (!document.getElementById('sortable-tbody')) {
        console.warn('TDR Processes page elements not found');
        return;
    }

    // Инициализируем модуль уведомлений (делаем доступным глобально)
    window.NotificationHandler = NotificationHandler;

    // Инициализируем обработчик модального окна Group Process Forms
    GroupFormsModalHandler.init();

    // Инициализируем drag & drop
    if (typeof Sortable !== 'undefined') {
        SortableHandler.init(
            ProcessesConfig.updateOrderUrl,
            function(data) {
                NotificationHandler.success('Порядок процессов обновлен');
            }
        );
    } else {
        console.warn('SortableJS library not loaded');
    }

    // Инициализируем обработчик удаления
    DeleteModalHandler.init();

    // Инициализируем обработчик vendors
    VendorHandler.init(ProcessesConfig.storeVendorUrl);

    // Инициализируем обработчик ссылок на формы
    FormLinkHandler.init();

    // Инициализируем обработчик групповых форм
    GroupProcessFormsHandler.init();

    console.log('TDR Processes page initialized successfully');
}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTdrProcesses);
} else {
    // DOM уже загружен
    initTdrProcesses();
}

