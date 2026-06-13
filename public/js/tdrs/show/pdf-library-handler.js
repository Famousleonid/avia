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

            // keep the full list; the toggle decides whether generated docs show
            this._allPdfs = Array.isArray(data.pdfs) ? data.pdfs : [];

            const filter = document.getElementById('pdfCategoryFilter');
            if (filter && !filter._bound) {
                filter._bound = true;
                filter.addEventListener('change', () => this.renderPdfList());
            }

            this.renderPdfList();
        } catch (error) {
            console.error('Load PDF error:', error);
            container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load PDF files</div></div>';
        } finally {
            if (typeof hideLoadingSpinner === 'function') {
                hideLoadingSpinner();
            }
        }
    },

    renderPdfList() {
        const container = document.getElementById('pdfListContainer');
        if (!container) return;
        const cat = document.getElementById('pdfCategoryFilter')?.value || 'uploaded';
        const all = this._allPdfs || [];
        const pdfs = all.filter(p => {
            if (cat === 'all') return true;
            if (cat === 'uploaded') return !p.is_generated;   // General + EC Approved
            return p.kind === cat;                            // exact category
        });

        // badge counts the user-visible (uploaded) files only
        if (typeof PdfBadgeHandler !== 'undefined') {
            const pdfBadge = document.getElementById('pdfCountBadge');
            if (pdfBadge) {
                const n = all.filter(p => !p.is_generated).length;
                if (n > 0) { pdfBadge.textContent = n; pdfBadge.classList.remove('d-none'); }
                else { pdfBadge.textContent = ''; pdfBadge.classList.add('d-none'); }
            }
        }

        if (pdfs.length === 0) {
            container.innerHTML = '<div class="text-muted text-center py-4">No PDF files in this category.</div>';
            return;
        }

        let html = '';
        pdfs.forEach(pdf => {
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
                    <div class="pdf-list-row pdf-card" data-pdf-id="${pdf.id}">
                        <div class="pdf-list-icon" aria-hidden="true">PDF</div>
                        <div class="min-w-0">
                            <div class="pdf-list-title" title="${displayTitle}">${displayName} <span class="badge ${pdf.is_generated ? 'bg-secondary' : (pdf.kind === 'ec_approved' ? 'bg-success' : 'bg-info')}" style="font-size:9px;vertical-align:middle">${pdf.kind_label || 'General'}</span></div>
                            <div class="pdf-list-meta">${fileSize} · ${uploadDate}</div>
                        </div>
                        <div class="pdf-list-actions">
                            <button class="btn btn-sm btn-outline-info view-pdf-btn"
                                    type="button"
                                    data-url="${pdf.url}"
                                    data-download="${pdf.download_url}"
                                    data-name="${displayName}"
                                    title="View PDF"
                                    aria-label="View PDF">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="${pdf.download_url}"
                               class="btn btn-sm btn-outline-success"
                               download
                               title="Download"
                               aria-label="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary rename-pdf-btn"
                                    type="button"
                                    data-id="${pdf.id}"
                                    data-name="${displayName}"
                                    title="Rename"
                                    aria-label="Rename">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-pdf-btn"
                                    type="button"
                                    data-id="${pdf.id}"
                                    title="Delete"
                                    aria-label="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
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
        this.bindRenameButtons();
    },

    bindRenameButtons() {
        const self = this;
        document.querySelectorAll('.rename-pdf-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id = btn.dataset.id;
                const current = btn.dataset.name || '';
                const name = prompt('Rename PDF:', current);
                if (name === null) return;
                const trimmed = name.trim();
                if (!trimmed || trimmed === current) return;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const r = await fetch('/workorders/pdf/' + id + '/rename', {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ name: trimmed }),
                    });
                    const j = await r.json();
                    if (!r.ok) throw new Error(j.message || j.error || 'Rename failed');
                    const item = (self._allPdfs || []).find(p => String(p.id) === String(id));
                    if (item) item.name = trimmed;
                    self.renderPdfList();
                } catch (e) { alert(e.message); }
            });
        });
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




