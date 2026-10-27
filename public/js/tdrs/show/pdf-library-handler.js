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
            this._uploadCategories = data.upload_categories || [];

            const updateOptions = (id, categories, prefix = [], suffix = []) => {
                const select = document.getElementById(id);
                if (!select || !Array.isArray(categories)) return;
                const value = select.value;
                select.replaceChildren(...[...prefix, ...categories, ...suffix].map(item => new Option(item.name, item.key)));
                select.value = Array.from(select.options).some(option => option.value === value) ? value : select.options[0]?.value;
            };
            updateOptions('pdfUploadCategory', data.upload_categories);
            if (typeof PdfUploadHandler !== 'undefined') PdfUploadHandler.updateFileTypes();
            updateOptions('pdfCategoryFilter', data.document_categories,
                [{key:'uploaded',name:'Uploaded only'},{key:'all',name:'All documents'}],
                [{key:'ec_draft',name:'EC Draft (generated)'},{key:'fc',name:'F&C (generated)'},{key:'machining',name:'Machining (generated)'}]);
            const filter = document.getElementById('pdfCategoryFilter');
            if (filter && !filter._bound) {
                filter._bound = true;
                filter.addEventListener('change', () => this.renderPdfList());
            }

            this.renderPdfList();
        } catch (error) {
            console.error('Load PDF error:', error);
            container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load documents</div></div>';
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
            container.innerHTML = '<div class="text-muted text-center py-4">No documents in this category.</div>';
            return;
        }

        let html = '';
        pdfs.forEach(pdf => {
                const fileSize = this.formatFileSize(pdf.size);
                const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                const uploadDate = escape(pdf.display_date || '');
                const displayName = escape(pdf.name && pdf.name !== pdf.file_name ? pdf.name : pdf.file_name);
                const displayTitle = escape(pdf.name && pdf.name !== pdf.file_name ? `${pdf.name} (${pdf.file_name})` : pdf.file_name);

                html += `
                    <div class="pdf-list-row pdf-card" data-pdf-id="${pdf.id}">
                        <div class="pdf-list-icon" aria-hidden="true">${pdf.mime_type?.startsWith('video/') ? 'VID' : (pdf.mime_type?.startsWith('image/') ? 'IMG' : 'PDF')}</div>
                        <div class="min-w-0">
                            <div class="pdf-list-title" title="${displayTitle}">${displayName} <span class="badge ${pdf.is_generated ? 'bg-secondary' : (pdf.kind === 'ec_approved' ? 'bg-success' : 'bg-info')}" style="font-size:9px;vertical-align:middle">${escape(pdf.kind_label || 'General')}</span></div>
                            <div class="pdf-list-meta">${fileSize} · ${uploadDate}</div>
                        </div>
                        <div class="pdf-list-actions">
                            <button class="btn btn-sm btn-outline-info view-pdf-btn"
                                    type="button"
                                    data-url="${pdf.url}"
                                    data-mime="${escape(pdf.mime_type)}"
                                    data-download="${pdf.download_url}"
                                    data-name="${displayName}"
                                    title="View document"
                                    aria-label="View document">
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
                                    title="Edit document"
                                    aria-label="Edit document">
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
        const form = document.getElementById('pdfEditForm');
        const pane = document.getElementById('pdfDocumentPane');
        const modalEl = document.getElementById('pdfModal');
        if (!form || !pane || !modalEl) return;
        const name = document.getElementById('pdfEditName');
        const category = document.getElementById('pdfEditCategory');
        const save = document.getElementById('pdfEditSave');
        const closeEditor = () => { form.hidden = true; pane.hidden = false; };
        if (!form._bound) {
            form._bound = true;
            document.getElementById('pdfEditCancel').addEventListener('click', closeEditor);
            modalEl.addEventListener('hidden.bs.modal', () => {
                if (!form._confirming) closeEditor();
            });
            form.addEventListener('submit', async event => {
                event.preventDefault();
                if (save.disabled || !form.reportValidity()) return;
                if (typeof window.confirmDialog !== 'function') {
                    window.showNotification('Confirmation dialog is unavailable.', 'error');
                    return;
                }
                const id = form.dataset.mediaId;
                const values = {name: name.value.trim(), doc_kind: category.value};
                if (!values.name) { name.focus(); return; }
                save.disabled = true;
                form._confirming = true;
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                try {
                    await new Promise(resolve => {
                        modalEl.addEventListener('hidden.bs.modal', resolve, {once: true});
                        modal.hide();
                    });
                    const confirmed = await window.confirmDialog({title: 'Save document', message: 'Save the document name and category?', okText: 'Save'});
                    if (!confirmed) return;
                    const response = await fetch('/workorders/pdf/' + id + '/rename', {
                        method: 'PATCH',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content},
                        body: JSON.stringify(values),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || data.error || 'Could not save document');
                    closeEditor();
                    await this.loadPdfLibrary(window.currentPdfWorkorderId);
                    window.showNotification('Document updated', 'success');
                } catch (error) {
                    window.showNotification(error.message, 'error');
                } finally {
                    form._confirming = false;
                    save.disabled = false;
                    modal.show();
                }
            });
        }
        document.querySelectorAll('.rename-pdf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = (this._allPdfs || []).find(p => String(p.id) === btn.dataset.id);
                if (!item) return;
                const video = (item.mime_type || '').startsWith('video/');
                const options = (this._uploadCategories || []).filter(c => video ? c.key === 'video' : c.key !== 'video').slice();
                if (item.source_kind && !options.some(c => c.key === item.source_kind)) options.push({key: item.source_kind, name: item.gen_type});
                if (!options.some(c => c.key === item.kind)) options.push({key: item.kind, name: item.kind_label});
                category.replaceChildren(...options.map(c => new Option(c.name, c.key)));
                category.value = item.kind;
                name.value = item.name || '';
                form.dataset.mediaId = item.id;
                pane.hidden = true;
                form.hidden = false;
                name.focus();
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





// Manage categories inside the existing documents dialog, keeping the WO in place.
document.addEventListener('DOMContentLoaded', () => {
    const open = document.getElementById('manageDocumentCategories');
    const documents = document.getElementById('pdfDocumentPane');
    const categories = document.getElementById('pdfCategoriesPane');
    const frame = document.getElementById('documentCategoriesFrame');
    if (!open || !documents || !categories || !frame) return;
    const restore = () => {
        categories.hidden = true;
        documents.hidden = false;
        frame.removeAttribute('src');
    };
    open.addEventListener('click', () => {
        documents.hidden = true;
        categories.hidden = false;
        frame.src = open.dataset.url;
        document.getElementById('backToDocuments').focus();
    });
    document.getElementById('backToDocuments').addEventListener('click', () => {
        restore();
        if (window.currentPdfWorkorderId) PdfLibraryHandler.loadPdfLibrary(window.currentPdfWorkorderId);
        open.focus();
    });
    document.getElementById('pdfModal').addEventListener('hidden.bs.modal', () => { if (!categories.hidden) restore(); });
});
