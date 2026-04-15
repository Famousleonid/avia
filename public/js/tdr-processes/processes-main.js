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
        if (!ProcessesConfig.updateOrderUrl) {
            console.error('ProcessesConfig.updateOrderUrl is not set. Please check that the route is defined in the Blade template.');
            if (window.NotificationHandler) {
            window.NotificationHandler.error('Configuration error: order update URL is not set');
            }
        } else {
            SortableHandler.init(
                ProcessesConfig.updateOrderUrl,
                function(data) {
                    NotificationHandler.success('Process order updated');
                }
            );
        }
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

}

// Инициализация при загрузке DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTdrProcesses);
} else {
    // DOM уже загружен
    initTdrProcesses();
}

