const PdfUploadHandler = {
    currentRequest: null,
    dropZoneBound: false,

    init() {
        const form = document.getElementById('pdfUploadForm');
        if (!form || form.dataset.bound === '1') {
            return;
        }

        form.dataset.bound = '1';
        form.addEventListener('submit', (event) => event.preventDefault());
        this.bindDropZone();
    },

    bindDropZone() {
        if (this.dropZoneBound) {
            return;
        }

        const dropZone = document.getElementById('pdfDropZone');
        const fileInput = document.getElementById('pdfFileInput');
        const form = document.getElementById('pdfUploadForm');
        const modal = document.getElementById('pdfModal');
        const cancelBtn = document.getElementById('clearPdfFileBtn');

        if (!dropZone || !fileInput) {
            return;
        }

        this.dropZoneBound = true;

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0] || null;
            if (file) {
                this.startUpload(file);
            }
        });

        const isFileDrag = (event) => Array.from(event.dataTransfer?.types || []).includes('Files');
        const stopBrowserFileOpen = (event) => {
            if (!isFileDrag(event)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'copy';
            }
        };

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            form?.addEventListener(eventName, stopBrowserFileOpen);
            modal?.addEventListener(eventName, stopBrowserFileOpen);
        });

        document.addEventListener('dragover', (event) => {
            if (modal?.classList.contains('show')) {
                stopBrowserFileOpen(event);
            }
        });

        document.addEventListener('drop', (event) => {
            if (modal?.classList.contains('show')) {
                stopBrowserFileOpen(event);
            }
        });

        dropZone.addEventListener('dragenter', (event) => {
            stopBrowserFileOpen(event);
            dropZone.classList.add('is-dragover');
        });

        dropZone.addEventListener('dragover', (event) => {
            stopBrowserFileOpen(event);
            dropZone.classList.add('is-dragover');
        });

        dropZone.addEventListener('dragleave', (event) => {
            stopBrowserFileOpen(event);
            if (!dropZone.contains(event.relatedTarget)) {
                dropZone.classList.remove('is-dragover');
            }
        });

        dropZone.addEventListener('drop', (event) => {
            stopBrowserFileOpen(event);
            dropZone.classList.remove('is-dragover');

            const file = event.dataTransfer?.files?.[0] || null;
            if (file) {
                this.startUpload(file);
            }
        });

        cancelBtn?.addEventListener('click', () => {
            this.abortUpload();
        });
    },

    startUpload(file) {
        if (this.currentRequest) {
            showNotification('Upload already in progress', 'warning');
            return;
        }

        if (!this.isPdfFile(file)) {
            showNotification('Please select a PDF file', 'warning');
            this.clearFormFile();
            return;
        }

        const workorderId = window.currentPdfWorkorderId;
        if (!workorderId) {
            showNotification('Workorder ID missing', 'error');
            this.clearFormFile();
            return;
        }

        this.updateSelectedFile(file);
        this.setProgress(0, 'Starting upload...');
        this.setUploading(true);

        const formData = new FormData();
        formData.append('pdf', file);

        const request = new XMLHttpRequest();
        this.currentRequest = request;

        request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                this.setProgress(12, 'Uploading...');
                return;
            }

            const percent = Math.max(1, Math.min(99, Math.round((event.loaded / event.total) * 100)));
            this.setProgress(percent, `Uploading ${percent}%`);
        });

        request.addEventListener('load', async () => {
            try {
                const data = JSON.parse(request.responseText || '{}');
                if (request.status < 200 || request.status >= 300) {
                    throw new Error(data.message || 'Upload failed');
                }

                this.setProgress(100, 'Uploaded');

                if (typeof PdfLibraryHandler !== 'undefined') {
                    await PdfLibraryHandler.loadPdfLibrary(workorderId);
                }

                this.showSuccessToast('PDF file uploaded successfully.');
                window.setTimeout(() => this.resetUploadUi(), 650);
            } catch (error) {
                console.error('Upload error:', error);
                showNotification('Upload failed: ' + error.message, 'error');
                this.resetUploadUi();
            } finally {
                this.currentRequest = null;
                this.setUploading(false);
            }
        });

        request.addEventListener('error', () => {
            showNotification('Upload failed: network error', 'error');
            this.currentRequest = null;
            this.setUploading(false);
            this.resetUploadUi();
        });

        request.addEventListener('abort', () => {
            showNotification('Upload cancelled', 'warning');
            this.currentRequest = null;
            this.setUploading(false);
            this.resetUploadUi();
        });

        request.open('POST', `/workorders/pdf/${workorderId}`);
        request.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
        request.send(formData);
    },

    abortUpload() {
        if (this.currentRequest) {
            this.currentRequest.abort();
            return;
        }

        this.resetUploadUi();
    },

    isPdfFile(file) {
        const name = (file?.name || '').toLowerCase();
        return file && (file.type === 'application/pdf' || name.endsWith('.pdf'));
    },

    updateSelectedFile(file) {
        const selectedFile = document.getElementById('pdfSelectedFile');
        if (!selectedFile) {
            return;
        }

        selectedFile.textContent = file ? `${file.name} (${this.formatFileSize(file.size)})` : 'No file selected';
    },

    setProgress(percent, label) {
        const progress = document.getElementById('pdfUploadProgress');
        const bar = document.getElementById('pdfUploadProgressBar');
        const text = document.getElementById('pdfUploadProgressLabel');

        progress?.classList.add('is-visible');
        if (bar) {
            bar.style.width = `${percent}%`;
        }
        if (text) {
            text.textContent = label;
        }
    },

    setUploading(isUploading) {
        const cancelBtn = document.getElementById('clearPdfFileBtn');
        const dropZone = document.getElementById('pdfDropZone');
        const fileInput = document.getElementById('pdfFileInput');

        cancelBtn?.classList.toggle('d-none', !isUploading);
        dropZone?.classList.toggle('is-uploading', isUploading);
        if (fileInput) {
            fileInput.disabled = isUploading;
        }
    },

    clearFormFile() {
        const fileInput = document.getElementById('pdfFileInput');
        if (fileInput) {
            fileInput.value = '';
        }
        this.updateSelectedFile(null);
    },

    resetUploadUi() {
        const progress = document.getElementById('pdfUploadProgress');
        const bar = document.getElementById('pdfUploadProgressBar');
        const text = document.getElementById('pdfUploadProgressLabel');
        const dropZone = document.getElementById('pdfDropZone');

        this.clearFormFile();
        this.setUploading(false);
        dropZone?.classList.remove('is-dragover', 'is-uploading');
        progress?.classList.remove('is-visible');
        if (bar) {
            bar.style.width = '0';
        }
        if (text) {
            text.textContent = 'Waiting for file';
        }
    },

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

    formatFileSize(bytes) {
        if (!bytes) {
            return '0 Bytes';
        }

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), sizes.length - 1);

        return `${Math.round((bytes / Math.pow(k, index)) * 100) / 100} ${sizes[index]}`;
    }
};
