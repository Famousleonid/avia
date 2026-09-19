/**
 * PdfViewerHandler - управление просмотром PDF файлов
 * Открывает PDF файлы в модальном окне для просмотра
 */

const PdfViewerHandler = {
    /**
     * Привязывает обработчики кнопок просмотра PDF
     */
    bindViewButtons() {
        document.querySelectorAll('.view-pdf-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const pdfUrl = btn.dataset.url;
                const downloadUrl = btn.dataset.download;
                const pdfName = btn.dataset.name;

                this.openPdfViewer(pdfUrl, downloadUrl, pdfName, btn.dataset.mime);
            });
        });
    },

    /**
     * Открывает PDF файл в просмотрщике
     * @param {string} pdfUrl - URL PDF файла для просмотра
     * @param {string} downloadUrl - URL для скачивания PDF
     * @param {string} pdfName - Имя PDF файла
     */
    openPdfViewer(pdfUrl, downloadUrl, pdfName, mimeType = 'application/pdf') {
        const iframe = document.getElementById('pdfViewerFrame');
        const downloadLink = document.getElementById('pdfDownloadLink');
        const modalLabel = document.getElementById('pdfViewerModalLabel');

        if (!iframe || !downloadLink) {
            console.error('PDF viewer elements not found');
            return;
        }

        const image = document.getElementById('documentViewerImage');
        const isImage = mimeType.startsWith('image/');
        iframe.classList.toggle('d-none', isImage);
        iframe.classList.toggle('d-block', !isImage);
        iframe.src = isImage ? 'about:blank' : pdfUrl;
        if (image) {
            image.classList.toggle('d-none', !isImage);
            if (isImage) image.src = pdfUrl;
            else image.removeAttribute('src');
        }
        downloadLink.href = downloadUrl;
        downloadLink.download = pdfName;

        if (modalLabel) {
            modalLabel.textContent = pdfName;
        }

        const pdfViewerModal = document.getElementById('pdfViewerModal');
        if (pdfViewerModal) {
            const modal = new bootstrap.Modal(pdfViewerModal);
            modal.show();
        }
    },

    /**
     * Закрывает просмотрщик и очищает iframe
     */
    closePdfViewer() {
        document.getElementById('documentViewerImage')?.removeAttribute('src');
        const iframe = document.getElementById('pdfViewerFrame');
        const downloadLink = document.getElementById('pdfDownloadLink');

        if (iframe) {
            iframe.src = 'about:blank';
        }

        if (downloadLink) {
            downloadLink.href = '#';
            downloadLink.download = '';
        }
    },

    /**
     * Инициализирует обработчики закрытия модального окна
     */
    init() {
        const pdfViewerModal = document.getElementById('pdfViewerModal');
        if (pdfViewerModal) {
            pdfViewerModal.addEventListener('hidden.bs.modal', () => {
                this.closePdfViewer();
            });
        }
    }
};




