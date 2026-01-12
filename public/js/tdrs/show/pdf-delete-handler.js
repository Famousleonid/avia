/**
 * PdfDeleteHandler - обработка удаления PDF файлов
 * Обрабатывает удаление PDF файлов с подтверждением
 */

const PdfDeleteHandler = {
    /**
     * Привязывает обработчики кнопок удаления PDF
     */
    bindDeleteButtons() {
        document.querySelectorAll('.delete-pdf-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const pdfId = btn.dataset.id;
                const pdfCard = btn.closest('.pdf-card');

                if (pdfId && pdfCard) {
                    this.confirmPdfDelete(pdfId, pdfCard);
                }
            });
        });
    },

    /**
     * Подтверждает удаление PDF файла
     * @param {number|string} pdfId - ID PDF файла
     * @param {HTMLElement} pdfCard - Элемент карточки PDF
     */
    confirmPdfDelete(pdfId, pdfCard) {
        window.pendingPdfDelete = { pdfId, pdfCard };

        const confirmModal = document.getElementById('confirmDeletePdfModal');
        if (confirmModal) {
            const modal = new bootstrap.Modal(confirmModal);
            modal.show();
        }
    },

    /**
     * Удаляет PDF файл
     * @param {number|string} pdfId - ID PDF файла
     * @returns {Promise<void>}
     */
    async deletePdf(pdfId) {
        if (typeof showLoadingSpinner === 'function') {
            showLoadingSpinner();
        }

        try {
            const response = await fetch(`/workorders/pdf/delete/${pdfId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error('Failed to delete PDF');
            }

            // Анимация удаления карточки
            const { pdfCard } = window.pendingPdfDelete || {};
            if (pdfCard) {
                pdfCard.style.transition = 'opacity 0.3s ease';
                pdfCard.style.opacity = '0';
                setTimeout(() => {
                    pdfCard.remove();

                    // Перезагружаем список, если он пуст
                    const container = document.getElementById('pdfListContainer');
                    if (container && container.querySelectorAll('.pdf-card').length === 0) {
                        if (typeof PdfLibraryHandler !== 'undefined' && window.currentPdfWorkorderId) {
                            PdfLibraryHandler.loadPdfLibrary(window.currentPdfWorkorderId);
                        }
                    }
                }, 300);
            }

            // Обновляем бейдж количества PDF после удаления
            if (window.currentPdfWorkorderId && typeof PdfBadgeHandler !== 'undefined') {
                PdfBadgeHandler.updatePdfCountBadge(window.currentPdfWorkorderId);
            }

            // Показываем уведомление об успехе
            this.showSuccessToast('PDF deleted successfully.');
        } catch (error) {
            console.error('Delete error:', error);
            alert('Failed to delete PDF');
        } finally {
            if (typeof hideLoadingSpinner === 'function') {
                hideLoadingSpinner();
            }

            // Закрываем модальное окно подтверждения
            const confirmModal = document.getElementById('confirmDeletePdfModal');
            if (confirmModal) {
                const modalInstance = bootstrap.Modal.getInstance(confirmModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            window.pendingPdfDelete = null;
        }
    },

    /**
     * Показывает уведомление об успехе
     * @param {string} message - Сообщение для отображения
     */
    showSuccessToast(message) {
        const toastElement = document.getElementById('pdfDeletedToast');
        if (!toastElement) {
            return;
        }

        const toastBody = toastElement.querySelector('.toast-body');
        if (toastBody) {
            toastBody.textContent = message;
        }

        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    },

    /**
     * Инициализирует обработчик подтверждения удаления
     */
    init() {
        const confirmBtn = document.getElementById('confirmPdfDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                const { pdfId } = window.pendingPdfDelete || {};
                if (pdfId) {
                    this.deletePdf(pdfId);
                }
            });
        }
    }
};




