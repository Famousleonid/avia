/**
 * NotificationHandler - модуль для отображения уведомлений
 * Используется для показа сообщений об успехе или ошибке
 */
class NotificationHandler {
    /**
     * Показывает уведомление пользователю
     * @param {string} message - Текст сообщения
     * @param {string} type - Тип уведомления: 'success' или 'error'
     */
    static show(message, type = 'success') {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Автоматически убираем уведомление через 3 секунды
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    /**
     * Показывает уведомление об успехе
     * @param {string} message - Текст сообщения
     */
    static success(message) {
        this.show(message, 'success');
    }

    /**
     * Показывает уведомление об ошибке
     * @param {string} message - Текст сообщения
     */
    static error(message) {
        this.show(message, 'error');
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationHandler;
}

