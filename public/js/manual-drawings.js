document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('manualDrawingsModal');
    if (!modal) return;
    const drop = modal.querySelector('.manual-drawings-drop');
    const input = drop.querySelector('input');
    const list = modal.querySelector('.manual-drawings-list');
    const status = modal.querySelector('.manual-drawings-status');
    const frame = modal.querySelector('iframe');
    const print = modal.querySelector('.manual-drawing-print');
    const title = modal.querySelector('.manual-drawing-name');
    const empty = modal.querySelector('.manual-drawing-empty');
    const rename = modal.querySelector('.manual-drawing-rename');
    const remove = modal.querySelector('.manual-drawing-delete');
    const renameForm = modal.querySelector('.manual-drawing-rename-form');
    const nameInput = renameForm.querySelector('input');
    const countBadge = document.getElementById('manualDrawingsCountBadge');
    let items = [], selected = null, uploading = false, loading = false, mutating = false, generation = 0;
    const notify = (text, kind = 'error') => window.showNotification(text, kind);

    function render() {
        if (countBadge) {
            countBadge.textContent = items.length ? String(items.length) : '';
            countBadge.classList.toggle('d-none', items.length === 0);
        }
        list.replaceChildren();
        if (!items.length) {
            const label = document.createElement('span');
            label.className = 'text-muted small'; label.textContent = 'No drawings uploaded yet.';
            list.append(label);
        }
        items.forEach(item => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-outline-info manual-drawing-item';
            button.disabled = mutating;
            button.classList.toggle('active', selected?.id === item.id);
            button.setAttribute('aria-pressed', String(selected?.id === item.id));
            const name = document.createElement('span'); name.textContent = item.name;
            const meta = document.createElement('small');
            meta.textContent = [item.uploaded_by, item.date, `${Math.max(1, Math.round(item.size / 1024))} KB`].filter(Boolean).join(' · ');
            button.append(name, meta); button.addEventListener('click', () => preview(item));
            list.append(button);
        });
    }

    function clearPreview() {
        frame.onload = null; frame.removeAttribute('srcdoc'); frame.src = 'about:blank';
        frame.classList.add('d-none'); empty.classList.remove('d-none'); print.disabled = true;
        selected = null; title.textContent = 'Select a drawing';
        rename.classList.add('d-none'); remove.classList.add('d-none'); renameForm.classList.add('d-none');
    }

    function preview(item) {
        if (mutating) return;
        selected = item; title.textContent = item.name; print.disabled = true;
        rename.classList.toggle('d-none', !item.can_manage); remove.classList.toggle('d-none', !item.can_manage);
        renameForm.classList.add('d-none');
        empty.classList.add('d-none'); frame.classList.remove('d-none');
        frame.onload = () => { if (selected?.id === item.id) print.disabled = false; };
        if (item.mime.startsWith('image/')) {
            const img = document.createElement('img'); img.src = item.url; img.alt = item.name;
            frame.removeAttribute('src');
            frame.srcdoc = `<!doctype html><html><head><style>html,body{margin:0;height:100%;}body{display:flex;align-items:center;justify-content:center;}img{max-width:100%;max-height:100%;object-fit:contain;}@media print{@page{margin:10mm}html,body{height:auto;display:block}img{display:block;margin:auto;max-width:100%;max-height:250mm}}</style></head><body>${img.outerHTML}</body></html>`;
        } else {
            frame.removeAttribute('srcdoc'); frame.src = item.url;
        }
        render();
    }

    async function load() {
        const current = ++generation;
        loading = true; drop.setAttribute('aria-disabled', 'true'); input.disabled = true;
        items = [];
        status.textContent = 'Loading drawings…'; list.replaceChildren(); clearPreview();
        try {
            const response = await fetch(modal.dataset.listUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('Unable to load drawings. Close this window and try again.');
            const data = await response.json();
            if (current !== generation) return;
            items = data.items; status.textContent = ''; render();
        } catch (error) {
            if (current === generation) status.textContent = error.message;
        } finally {
            if (current === generation) {
                loading = false; drop.removeAttribute('aria-disabled'); input.disabled = false;
            }
        }
    }

    async function upload(files) {
        if (uploading || loading || mutating || !files.length) return;
        uploading = true; generation++; drop.setAttribute('aria-disabled', 'true'); input.disabled = true;
        let saved = 0; const failed = [];
        try {
            for (const [index, file] of files.entries()) {
                status.textContent = `Uploading ${index + 1} / ${files.length}: ${file.name}`;
                try {
                    if (file.size > 10 * 1024 * 1024 || !/\.(pdf|jpe?g|png|webp)$/i.test(file.name)) {
                        throw new Error('Use PDF, JPG, PNG or WebP, up to 10 MB.');
                    }
                    const body = new FormData(); body.append('file', file);
                    const response = await fetch(modal.dataset.uploadUrl, {method: 'POST', body,
                        headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
                    const data = await response.json();
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Upload failed.');
                    items = [data, ...items.filter(item => item.id !== data.id)]; saved++; render();
                } catch (error) { failed.push(`${file.name}: ${error.message}`); }
            }
            status.textContent = `${saved} uploaded${failed.length ? `, ${failed.length} failed` : ''}.`;
            if (failed.length) notify(failed.join('\n'));
            else notify('Drawings uploaded.', 'success');
        } finally {
            uploading = false; input.disabled = false; drop.removeAttribute('aria-disabled'); input.value = '';
        }
    }

    modal.addEventListener('show.bs.modal', load);
    modal.addEventListener('hide.bs.modal', event => {
        if (uploading || mutating) { event.preventDefault(); notify('Please wait for the current action to finish.', 'info'); }
    });
    modal.addEventListener('hidden.bs.modal', () => { generation++; clearPreview(); });
    drop.addEventListener('click', event => { if (event.target !== input && !uploading && !loading && !mutating) input.click(); });
    drop.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); if (!uploading && !loading && !mutating) input.click(); }
    });
    input.addEventListener('change', () => upload([...input.files]));
    for (const name of ['dragenter', 'dragover', 'dragleave', 'drop']) {
        drop.addEventListener(name, event => {
            event.preventDefault(); event.stopPropagation();
            if (name === 'drop') { drop.classList.remove('is-drag-over'); upload([...event.dataTransfer.files]); }
            else if (name === 'dragleave') { if (!drop.contains(event.relatedTarget)) drop.classList.remove('is-drag-over'); }
            else if (!uploading) { drop.classList.add('is-drag-over'); event.dataTransfer.dropEffect = 'copy'; }
        });
    }
    // Dropping outside the upload target must not navigate away from the current WO.
    for (const name of ['dragover', 'drop']) modal.addEventListener(name, event => {
        if (Array.from(event.dataTransfer?.types || []).includes('Files')) event.preventDefault();
        if (name === 'drop') drop.classList.remove('is-drag-over');
    });
    print.addEventListener('click', () => {
        try { frame.contentWindow.focus(); frame.contentWindow.print(); }
        catch (_) { notify('Use the PDF viewer print button to print this document.'); }
    });

    function setMutating(value) {
        mutating = value;
        rename.disabled = remove.disabled = value;
        renameForm.querySelectorAll('input, button').forEach(el => { el.disabled = value; });
        input.disabled = value;
        if (value) drop.setAttribute('aria-disabled', 'true');
        else drop.removeAttribute('aria-disabled');
        render();
    }

    async function change(url, method, body) {
        const response = await fetch(url, {method, headers: {
            Accept: 'application/json', 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }, ...(body ? {body: JSON.stringify(body)} : {})});
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to update drawing.');
        return data;
    }

    rename.addEventListener('click', () => {
        if (!selected?.can_manage || uploading || mutating) return;
        nameInput.value = selected.name; renameForm.classList.remove('d-none'); nameInput.focus(); nameInput.select();
    });
    renameForm.querySelector('.manual-drawing-rename-cancel').addEventListener('click', () => {
        renameForm.classList.add('d-none'); rename.focus();
    });
    renameForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!selected?.can_manage || uploading || mutating) return;
        const name = nameInput.value.trim();
        if (!name) { notify('Enter a drawing name.'); nameInput.focus(); return; }
        const item = selected;
        setMutating(true);
        try {
            const updated = await change(item.update_url, 'PATCH', {name});
            items = items.map(row => row.id === item.id ? updated : row);
            selected = updated; title.textContent = updated.name;
            renameForm.classList.add('d-none'); notify('Drawing renamed.', 'success');
        } catch (error) { notify(error.message); }
        finally { setMutating(false); }
    });
    remove.addEventListener('click', async () => {
        if (!selected?.can_manage || uploading || mutating) return;
        if (typeof window.confirmDialog !== 'function') { notify('Confirmation is unavailable. Reload the page.'); return; }
        const item = selected;
        setMutating(true);
        try {
            if (!await window.confirmDialog({title: 'Delete drawing',
                message: `Delete "${item.name}"? It will be removed from all workorders using this manual.`,
                okText: 'Delete', cancelText: 'Cancel', danger: true})) return;
            await change(item.delete_url, 'DELETE');
            items = items.filter(row => row.id !== item.id);
            clearPreview(); status.textContent = ''; notify('Drawing deleted.', 'success');
        } catch (error) { notify(error.message); }
        finally { setMutating(false); }
    });
});
