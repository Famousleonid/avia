(() => {
    if (window.bushingAttachmentsInitialized) return;
    window.bushingAttachmentsInitialized = true;
    const busy = new Set();
    let previewModal;
    let previewParent;
    function openPreview(button) {
        if (!previewModal) {
            previewModal = document.createElement('div');
            previewModal.className = 'modal fade';
            previewModal.style.zIndex = '1100';
            previewModal.tabIndex = -1;
            previewModal.setAttribute('aria-label', 'Bushing document preview');
            previewModal.innerHTML = `<div class="modal-dialog modal-xl modal-dialog-centered" style="width:90vw;max-width:1200px"><div class="modal-content">
                <div class="modal-header gap-2"><h5 class="modal-title text-truncate flex-grow-1"></h5>
                <button type="button" class="btn btn-outline-info bushing-document-print"><i class="bi bi-printer"></i> Print</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body p-0"><iframe title="Bushing document" style="display:block;width:100%;height:72vh;border:0;background:white"></iframe></div>
                </div></div>`;
            document.body.append(previewModal);
            previewModal.querySelector('.bushing-document-print').addEventListener('click', () => {
                try {
                    const frame = previewModal.querySelector('iframe');
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (_) {
                    window.showNotification('Unable to print this document. Use the PDF viewer print button.', 'error');
                }
            });
            previewModal.addEventListener('hidden.bs.modal', () => {
                const frame = previewModal.querySelector('iframe');
                frame.removeAttribute('srcdoc'); frame.src = 'about:blank';
                if (previewParent?.classList.contains('show')) {
                    document.body.classList.add('modal-open');
                    bootstrap.Modal.getInstance(previewParent)?._focustrap?.activate();
                }
                previewParent = null;
            });
        }
        previewModal.querySelector('.modal-title').textContent = button.dataset.name;
        const frame = previewModal.querySelector('iframe');
        const print = previewModal.querySelector('.bushing-document-print');
        print.disabled = true;
        frame.onload = () => { print.disabled = false; };
        if ((button.dataset.mime || '').startsWith('image/')) {
            const img = document.createElement('img');
            img.src = button.dataset.url;
            img.alt = button.dataset.name;
            frame.removeAttribute('src');
            frame.srcdoc = `<!doctype html><html><head><style>html,body{margin:0;height:100%;}body{display:flex;align-items:center;justify-content:center;}img{max-width:100%;max-height:100%;object-fit:contain;}@media print{@page{size:portrait;margin:10mm}html,body{height:auto;display:block;}img{display:block;margin:auto;max-height:250mm;max-width:100%;}}</style></head><body>${img.outerHTML}</body></html>`;
        } else {
            frame.removeAttribute('srcdoc'); frame.src = button.dataset.url;
        }
        previewParent = button.closest('.modal.show');
        if (previewParent) bootstrap.Modal.getInstance(previewParent)?._focustrap?.deactivate();
        bootstrap.Modal.getOrCreateInstance(previewModal).show();
    }
    async function upload(cell, files) {
        const id = cell.dataset.componentId;
        if (busy.has(id) || !files.length) return;
        if (files.length > 1) {
            window.showNotification('Choose one image or PDF. It replaces the current document.', 'error');
            cell.querySelector('input').value = '';
            return;
        }
        busy.add(id);
        const button = cell.querySelector('.bushing-document-drop');
        button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
        button.setAttribute('aria-label', 'Uploading…');
        try {
            for (const file of files) {
                const body = new FormData(); body.append('file', file);
                const response = await fetch(cell.dataset.uploadUrl, {method: 'POST', body,
                    headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Upload failed.');
                document.querySelectorAll('.bushing-documents').forEach(target => {
                    if (target.dataset.componentId !== id) return;
                    const link = document.createElement('button');
                    link.type = 'button';
                    link.className = 'btn btn-sm btn-outline-secondary p-1 bushing-document-preview';
                    link.dataset.url = data.url; link.dataset.mime = data.mime || file.type; link.dataset.name = data.name;
                    link.title = data.name; link.setAttribute('aria-label', `Preview ${data.name}`);
                    if (link.dataset.mime.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.src = data.url; image.alt = ''; image.width = image.height = 36;
                        image.style.objectFit = 'contain'; link.append(image);
                    } else {
                        link.innerHTML = '<i class="bi bi-file-earmark-pdf text-danger" style="font-size:28px" aria-hidden="true"></i>';
                    }
                    target.querySelector('.bushing-document-links').replaceChildren(link);
                });
            }
            window.showNotification('Bushing document saved.', 'success');
        } catch (error) {
            window.showNotification(error.message || 'Upload failed.', 'error');
        } finally {
            busy.delete(id); button.disabled = false; button.innerHTML = '<i class="bi bi-upload" aria-hidden="true"></i>';
            button.setAttribute('aria-label', 'Upload image or PDF');
            cell.querySelector('input').value = '';
        }
    }
    document.addEventListener('click', event => {
        const preview = event.target.closest('.bushing-document-preview');
        if (preview) { openPreview(preview); return; }
        const button = event.target.closest('.bushing-document-drop');
        if (button) button.closest('.bushing-documents').querySelector('input').click();
    });
    document.addEventListener('change', event => {
        if (event.target.matches('.bushing-document-input')) upload(event.target.closest('.bushing-documents'), [...event.target.files]);
    });
    const clearDrop = () => document.querySelectorAll('.bushing-documents.is-drag-over').forEach(cell => cell.classList.remove('is-drag-over'));
    for (const name of ['dragenter', 'dragover', 'dragleave', 'drop']) document.addEventListener(name, event => {
        const cell = event.target.closest('.bushing-documents');
        if (!cell || !Array.from(event.dataTransfer?.types || []).includes('Files')) return;
        event.preventDefault(); event.stopPropagation();
        if (name === 'dragleave') {
            if (!cell.contains(event.relatedTarget)) cell.classList.remove('is-drag-over');
        } else if (name === 'drop') {
            clearDrop(); upload(cell, [...event.dataTransfer.files]);
        } else if (!busy.has(cell.dataset.componentId)) {
            clearDrop(); cell.classList.add('is-drag-over'); event.dataTransfer.dropEffect = 'copy';
        }
    });
    document.addEventListener('dragend', clearDrop);
    document.addEventListener('drop', clearDrop);
})();
