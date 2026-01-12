/**
 * DeleteModalHandler - модуль для обработки удаления процессов
 * Управляет модальным окном подтверждения удаления
 */
class DeleteModalHandler {
    /**
     * Инициализирует обработчики для модального окна удаления
     */
    static init() {
        const deleteModal = document.getElementById('useConfirmDelete');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');
        
        if (!deleteModal || !confirmDeleteButton) {
            console.warn('Delete modal elements not found');
            return;
        }

        let deleteForm = null;

        // Обработчик открытия модального окна
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Кнопка, которая вызвала модальное окно
            const title = button.getAttribute('data-title'); // Заголовок модального окна
            deleteForm = button.closest('form'); // Находим форму удаления

            // Устанавливаем заголовок модального окна
            const modalTitle = deleteModal.querySelector('.modal-title');
            if (modalTitle && title) {
                modalTitle.textContent = title;
            }
        });

        // Обработчик нажатия на кнопку "Delete" в модальном окне
        confirmDeleteButton.addEventListener('click', function () {
            if (deleteForm) {
                deleteForm.submit(); // Отправляем форму удаления
            }
        });
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeleteModalHandler;
}




