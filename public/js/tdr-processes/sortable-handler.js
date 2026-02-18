/**
 * SortableHandler - модуль для обработки drag & drop функциональности
 * Использует библиотеку SortableJS для изменения порядка процессов
 */
class SortableHandler {
    /**
     * Инициализирует drag & drop для таблицы процессов
     * @param {string} updateOrderUrl - URL для обновления порядка
     * @param {Function} onOrderUpdated - Callback при успешном обновлении
     */
    static init(updateOrderUrl, onOrderUpdated) {
        const tbody = document.getElementById('sortable-tbody');
        if (!tbody) {
            console.warn('Element #sortable-tbody not found');
            return;
        }

        const sortable = Sortable.create(tbody, {
            animation: 150,
            ghostClass: 'dragging',
            dragClass: 'dragging',
            filter: '.disabled', // Исключаем неактивные строки из drag & drop
            onEnd: function(evt) {
                // Получаем новый порядок элементов (исключаем неактивные строки)
                const newOrder = Array.from(sortable.el.children)
                    .filter(row => !row.querySelector('.disabled') || !row.querySelector('[aria-disabled="true"]'))
                    .map((row, index) => {
                        return {
                            id: row.getAttribute('data-id'),
                            sort_order: index + 1
                        };
                    });

                // Отправляем AJAX запрос для обновления порядка
                SortableHandler.updateProcessOrder(newOrder, updateOrderUrl, onOrderUpdated);
            }
        });

        return sortable;
    }

    /**
     * Отправляет запрос на обновление порядка процессов
     * @param {Array} newOrder - Новый порядок процессов
     * @param {string} updateOrderUrl - URL для обновления
     * @param {Function} onOrderUpdated - Callback при успехе
     */
    static updateProcessOrder(newOrder, updateOrderUrl, onOrderUpdated) {
        // Проверяем, что URL установлен
        if (!updateOrderUrl) {
            console.error('Update order URL is not defined');
            if (window.NotificationHandler) {
                window.NotificationHandler.error('Ошибка: URL для обновления порядка не найден. Проверьте конфигурацию роутов.');
            }
            location.reload();
            return;
        }

        const processIds = newOrder.map(item => item.id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            if (window.NotificationHandler) {
                window.NotificationHandler.error('Ошибка: CSRF токен не найден');
            }
            return;
        }

        fetch(updateOrderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                process_ids: processIds
            })
        })
        .then(response => {
            // Проверяем статус ответа
            if (!response.ok) {
                // Если роут не найден (404) или другая ошибка сервера
                if (response.status === 404) {
                    throw new Error('Роут не найден. Проверьте, что роут tdr-processes.update-order зарегистрирован в routes/web.php');
                } else if (response.status === 419) {
                    throw new Error('Сессия истекла. Пожалуйста, обновите страницу.');
                } else {
                    throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`);
                }
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Order updated successfully');
                if (onOrderUpdated) {
                    onOrderUpdated(data);
                }
            } else {
                console.error('Error updating order:', data.message);
                if (window.NotificationHandler) {
                    window.NotificationHandler.error('Ошибка обновления порядка: ' + (data.message || 'Неизвестная ошибка'));
                }
                // Восстанавливаем предыдущий порядок
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.NotificationHandler) {
                window.NotificationHandler.error(error.message || 'Ошибка сети при обновлении порядка');
            } else {
                showNotification('Ошибка при обновлении порядка: ' + (error.message || 'Неизвестная ошибка'), 'error');
            }
            location.reload();
        });
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SortableHandler;
}

