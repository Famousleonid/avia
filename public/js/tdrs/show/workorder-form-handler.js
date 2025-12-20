/**
 * WorkOrderFormHandler - обработка формы обновления Work Order
 * Обрабатывает отправку формы инспекции рабочего заказа через AJAX
 */

const WorkOrderFormHandler = {
    /**
     * Инициализирует обработчик формы обновления Work Order
     */
    init() {
        const form = document.getElementById('updateWorkOrderForm');
        if (!form) {
            console.warn('Work Order form not found');
            return;
        }

        form.addEventListener('submit', this.handleSubmit.bind(this));
    },

    /**
     * Обрабатывает отправку формы
     * @param {Event} event - Событие отправки формы
     */
    handleSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        
        // Получаем маршрут из data-атрибута формы
        const inspectionRoute = form.dataset.inspectionRoute;
        
        if (!inspectionRoute) {
            console.error('Inspection route not found');
            alert('Inspection route missing');
            return;
        }

        // Показываем индикатор загрузки, если функция доступна
        if (typeof showLoadingSpinner === 'function') {
            showLoadingSpinner();
        }
        
        fetch(inspectionRoute, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Work Order updated successfully!');
                location.reload();
            } else {
                alert('Failed to update Work Order.');
            }
        })
        .catch(error => {
            console.error('Error updating Work Order:', error);
            alert('An error occurred while updating Work Order.');
        })
        .finally(() => {
            if (typeof hideLoadingSpinner === 'function') {
                hideLoadingSpinner();
            }
        });
    }
};

