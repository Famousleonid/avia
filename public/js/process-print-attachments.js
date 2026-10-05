(() => {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Process print images</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"></div></div></div>';
    document.body.append(modal);
    const body = modal.querySelector('.modal-body');
    let url = null, busy = false;
    const error = message => window.notifyError ? window.notifyError(message) : window.showNotification?.(message, 'error');
    async function load() {
        const response = await fetch(url, {headers:{Accept:'text/html'}});
        if (!response.ok) throw new Error('Could not load print images.');
        body.innerHTML = await response.text();
        const count = Number(body.querySelector('[data-print-images-count]')?.dataset.printImagesCount || 0);
        document.querySelectorAll('[data-process-images]').forEach(button => {
            if (button.dataset.processImages !== url) return;
            button.classList.toggle('has-print-images', count > 0);
            button.title = `Print images by part number (${count})`;
        });
    }
    async function mutate(target, options, message) {
        if (busy) return;
        if (!window.confirmDialog) { error('Confirmation dialog is unavailable.'); return; }
        busy = true;
        const dialog = bootstrap.Modal.getOrCreateInstance(modal);
        await new Promise(resolve => { modal.addEventListener('hidden.bs.modal', resolve, {once:true}); dialog.hide(); });
        try {
            if (!await window.confirmDialog({title:'Process print image', message, okText:options.method === 'DELETE' ? 'Delete' : 'Save', danger:options.method === 'DELETE'})) return;
            const response = await fetch(target, {...options, headers:{Accept:'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not save image.');
            await load();
        } catch (e) { error(e.message); }
        finally { busy = false; dialog.show(); }
    }
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-process-images]');
        if (!button || busy) return;
        url = button.dataset.processImages;
        body.textContent = 'Loading…';
        bootstrap.Modal.getOrCreateInstance(modal).show();
        try { await load(); } catch(e) { body.textContent = e.message; }
    });
    body.addEventListener('submit', event => {
        const form = event.target.closest('[data-process-image-form]');
        if (!form) return;
        event.preventDefault();
        mutate(form.action, {method:'POST', body:new FormData(form)}, 'Save this image for the part number? An existing image for this part number will be replaced.');
    });
    body.addEventListener('click', event => {
        const replace = event.target.closest('[data-process-image-replace]');
        if (replace) { body.querySelector('[name="part_number"]').value = replace.dataset.processImageReplace; body.querySelector('[name="image"]').focus(); }
        const remove = event.target.closest('[data-process-image-delete]');
        if (remove) mutate(remove.dataset.processImageDelete, {method:'DELETE'}, 'Delete this print image?');
    });
})();
