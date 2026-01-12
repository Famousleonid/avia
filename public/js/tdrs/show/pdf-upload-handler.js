/**
 * PdfUploadHandler - обработка загрузки PDF файлов
 * Обрабатывает загрузку PDF файлов на сервер
 */

const PdfUploadHandler = {
    /**
     * Инициализирует обработчик загрузки PDF
     */
    init() {
        const form = document.getElementById('pdfUploadForm');
        if (!form) {
            console.warn('PDF upload form not found');
            return;
        }

        form.addEventListener('submit', this.handleUpload.bind(this));
    },

    /**
     * Обрабатывает загрузку PDF файла
     * @param {Event} event - Событие отправки формы
     */
    async handleUpload(event) {
        event.preventDefault();

        const workorderId = window.currentPdfWorkorderId;
        if (!workorderId) {
            alert('Workorder ID missing');
            return;
        }

        const fileInput = document.getElementById('pdfFileInput');
        if (!fileInput || !fileInput.files.length) {
            alert('Please select a PDF file');
            return;
        }

        const documentName = document.getElementById('pdfDocumentName')?.value.trim() || '';

        const formData = new FormData();
        formData.append('pdf', fileInput.files[0]);
        if (documentName) {
            formData.append('document_name', documentName);
        }

        if (typeof showLoadingSpinner === 'function') {
            showLoadingSpinner();
        }

        const uploadBtn = document.getElementById('uploadPdfBtn');
        if (uploadBtn) {
            uploadBtn.disabled = true;
        }

        try {
            const response = await fetch(`/workorders/pdf/${workorderId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Upload failed');
            }

            const data = await response.json();

            // Перезагружаем список PDF
            if (typeof PdfLibraryHandler !== 'undefined') {
                await PdfLibraryHandler.loadPdfLibrary(workorderId);
            }

            // Очищаем форму
            if (fileInput) {
                fileInput.value = '';
            }
            const documentNameInput = document.getElementById('pdfDocumentName');
            if (documentNameInput) {
                documentNameInput.value = '';
            }

            // Показываем уведомление об успехе
            this.showSuccessToast('PDF file uploaded successfully.');
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed: ' + error.message);
        } finally {
            if (typeof hideLoadingSpinner === 'function') {
                hideLoadingSpinner();
            }
            if (uploadBtn) {
                uploadBtn.disabled = false;
            }
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
    }
};




