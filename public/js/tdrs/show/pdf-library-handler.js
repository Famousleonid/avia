/**
 * PdfLibraryHandler - управление PDF библиотекой
 * Загружает и отображает список PDF файлов для рабочего заказа
 */

const PdfLibraryHandler = {
    /**
     * Загружает список PDF файлов для рабочего заказа
     * @param {number|string} workorderId - ID рабочего заказа
     */
    async loadPdfLibrary(workorderId) {
        const container = document.getElementById('pdfListContainer');
        if (!container) {
            console.error('PDF list container not found');
            return;
        }

        if (typeof showLoadingSpinner === 'function') {
            showLoadingSpinner();
        }

        try {
            const response = await fetch(`/workorders/${workorderId}/pdfs`);
            if (!response.ok) {
                throw new Error('Response not ok');
            }

            const data = await response.json();

            // Обновляем бейдж с количеством PDF
            if (typeof PdfBadgeHandler !== 'undefined') {
                const pdfBadge = document.getElementById('pdfCountBadge');
                if (pdfBadge) {
                    const pdfCount = Array.isArray(data.pdfs) ? data.pdfs.length : 0;
                    if (pdfCount > 0) {
                        pdfBadge.textContent = pdfCount;
                        pdfBadge.classList.remove('d-none');
                    } else {
                        pdfBadge.textContent = '';
                        pdfBadge.classList.add('d-none');
                    }
                }
            }

            if (!data.pdfs || data.pdfs.length === 0) {
                container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No PDF files uploaded yet.</p></div>';
                if (typeof hideLoadingSpinner === 'function') {
                    hideLoadingSpinner();
                }
                return;
            }

            let html = '';
            data.pdfs.forEach(pdf => {
                const fileSize = this.formatFileSize(pdf.size);
                const uploadDate = new Date(pdf.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const displayName = pdf.name && pdf.name !== pdf.file_name ? pdf.name : pdf.file_name;
                const displayTitle = pdf.name && pdf.name !== pdf.file_name ? `${pdf.name} (${pdf.file_name})` : pdf.file_name;

                html += `
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card bg-secondary border-primary pdf-card" data-pdf-id="${pdf.id}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title text-truncate mb-0" style="max-width: 150px;"
                                    title="${displayTitle}">
                                        <i class="bi bi-file-earmark-pdf text-danger"></i> ${displayName}
                                    </h6>
                                    <button class="btn btn-sm btn-danger delete-pdf-btn" data-id="${pdf.id}" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-file-text"></i> ${fileSize}<br>
                                    <i class="bi bi-calendar"></i> ${uploadDate}
                                </p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-primary view-pdf-btn"
                                            data-url="${pdf.url}"
                                            data-download="${pdf.download_url}"
                                            data-name="${displayName}">
                                        <i class="bi bi-eye"></i> View PDF
                                    </button>
                                    <a href="${pdf.download_url}" class="btn btn-sm btn-outline-success" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Привязываем обработчики кнопок
            if (typeof PdfViewerHandler !== 'undefined') {
                PdfViewerHandler.bindViewButtons();
            }
            if (typeof PdfDeleteHandler !== 'undefined') {
                PdfDeleteHandler.bindDeleteButtons();
            }
        } catch (error) {
            console.error('Load PDF error:', error);
            container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load PDF files</div></div>';
        } finally {
            if (typeof hideLoadingSpinner === 'function') {
                hideLoadingSpinner();
            }
        }
    },

    /**
     * Форматирует размер файла в читаемый формат
     * @param {number} bytes - Размер файла в байтах
     * @returns {string} - Отформатированный размер файла
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
};

