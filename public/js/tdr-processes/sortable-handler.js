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
            onEnd: function(evt) {
                // Получаем новый порядок элементов
                const newOrder = Array.from(sortable.el.children).map((row, index) => {
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
        const processIds = newOrder.map(item => item.id);

        fetch(updateOrderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                process_ids: processIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Order updated successfully');
                if (onOrderUpdated) {
                    onOrderUpdated(data);
                }
            } else {
                console.error('Error updating order:', data.message);
                if (window.NotificationHandler) {
                    window.NotificationHandler.error('Ошибка обновления порядка: ' + data.message);
                }
                // Восстанавливаем предыдущий порядок
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.NotificationHandler) {
                window.NotificationHandler.error('Ошибка сети при обновлении порядка');
            }
            location.reload();
        });
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SortableHandler;
}

