(function () {
    'use strict';

    if (window.DeleteConfirmHandlerInitialized) {
        return;
    }

    window.DeleteConfirmHandlerInitialized = true;

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('useConfirmDelete');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        if (!modal || !confirmDeleteBtn) {
            return;
        }

        let deleteForm = null;
        let submitConfirmed = false;

        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            deleteForm = button ? button.closest('form') : null;
            submitConfirmed = false;

            const title = button ? button.getAttribute('data-title') : null;
            const modalTitle = modal.querySelector('#confirmDeleteLabel');
            if (modalTitle) {
                modalTitle.textContent = title || 'Delete Confirmation';
            }
        });

        modal.addEventListener('hidden.bs.modal', function () {
            deleteForm = null;
            if (!submitConfirmed && typeof window.safeHideSpinner === 'function') {
                window.safeHideSpinner();
            }
        });

        confirmDeleteBtn.addEventListener('click', function () {
            if (!deleteForm) {
                if (typeof showNotification === 'function') {
                    showNotification('Delete form not found.', 'error');
                } else if (window.NotificationHandler && typeof window.NotificationHandler.error === 'function') {
                    window.NotificationHandler.error('Delete form not found.');
                }
                return;
            }

            submitConfirmed = true;
            if (typeof showGlobalSpinner === 'function') {
                showGlobalSpinner();
            } else if (typeof showLoadingSpinner === 'function') {
                showLoadingSpinner();
            }

            deleteForm.submit();
        });
    });
})();
