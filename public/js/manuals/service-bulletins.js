(() => {
    const table = document.querySelector('.manual-sb-table');
    if (!table) return;
    const forms = [...document.querySelectorAll('form[id^="update-sb-"]')];
    const baseline = new Map();
    let busy = false, prompting = false, dragged = null, oldOrder = [];
    const notify = (message, type = 'danger') => window.showNotification?.(message, type);
    const values = form => JSON.stringify([...new FormData(form)].filter(([key]) => !['_token', '_method'].includes(key)));
    const dirty = form => values(form) !== baseline.get(form);
    const paint = form => {
        const button = document.querySelector(`button[form="${form.id}"][title="Save"]`);
        button?.classList.toggle('btn-warning', dirty(form));
        button?.classList.toggle('btn-outline-primary', !dirty(form));
    };
    forms.forEach(form => baseline.set(form, values(form)));
    table.addEventListener('input', event => { if (baseline.has(event.target.form)) paint(event.target.form); });
    table.addEventListener('change', event => { if (baseline.has(event.target.form)) paint(event.target.form); });
    async function request(url, options) {
        const response = await fetch(url, { ...options, headers: {
            Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            ...options.headers,
        } });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Save failed.');
        return data;
    }
    async function save(form) {
        if (!form.reportValidity()) throw new Error('Check the highlighted fields.');
        const snapshot = values(form);
        const submitted = new Map(new FormData(form));
        await request(form.action, { method: 'POST', body: new FormData(form) });
        baseline.set(form, snapshot);
        [...form.elements].forEach(field => {
            if (!field.name || field.type === 'hidden' || !submitted.has(field.name)) return;
            if (field.type === 'checkbox') field.defaultChecked = submitted.get(field.name) === field.value;
            else if (field.tagName === 'SELECT') [...field.options].forEach(option => { option.defaultSelected = option.value === submitted.get(field.name); });
            else field.defaultValue = submitted.get(field.name);
        });
        paint(form);
    }
    forms.forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        try { await save(form); notify('Service Bulletin saved.', 'success'); }
        catch (error) { notify(error.message); }
        finally { busy = false; }
    }));
    const rows = () => [...table.querySelectorAll('tbody tr')].filter(row => row.querySelector('[data-sb-id]'));
    const renumber = () => rows().forEach((row, index) => { row.querySelector('[data-sb-sort]').textContent = index + 1; });
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const animations = new Map(), highlights = new Map();
    let movingUntil = 0;
    function highlight(row) {
        clearTimeout(highlights.get(row));
        row.classList.add('sb-row-moved');
        highlights.set(row, setTimeout(() => {
            row.classList.remove('sb-row-moved'); highlights.delete(row);
        }, 1500));
    }
    function moveRows(change, selected) {
        const before = new Map(rows().map(row => [row, row.getBoundingClientRect().top]));
        animations.forEach(animation => animation.cancel()); animations.clear();
        change();
        const duration = reducedMotion.matches ? 0 : 520;
        movingUntil = performance.now() + duration;
        rows().forEach(row => {
            const delta = before.get(row) - row.getBoundingClientRect().top;
            if (!delta || !duration) return;
            const animation = row.animate([
                { transform: `translateY(${delta}px)` }, { transform: 'translateY(0)' },
            ], { duration, easing: 'cubic-bezier(.22, .61, .36, 1)' });
            animations.set(row, animation);
            animation.finished.then(() => {
                if (animations.get(row) === animation) animations.delete(row);
            }).catch(() => {});
        });
        if (selected) highlight(selected);
    }
    async function reorder(previous) {
        if (rows().every((row, index) => row === previous[index])) return;
        busy = true;
        renumber();
        try {
            await request(table.dataset.reorderUrl, { method: 'PATCH', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: rows().map(row => Number(row.querySelector('[data-sb-id]').dataset.sbId)) }) });
            notify('SB order saved.', 'success');
        } catch (error) {
            moveRows(() => previous.forEach(row => table.tBodies[0].append(row)));
            renumber(); notify(error.message);
        } finally { busy = false; }
    }
    table.addEventListener('dragstart', event => {
        const handle = event.target.closest('.sb-drag-handle');
        if (!handle || busy) { event.preventDefault(); return; }
        dragged = handle.closest('tr'); oldOrder = rows();
        dragged.classList.add('sb-row-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', handle.dataset.sbId);
        const rect = dragged.getBoundingClientRect();
        event.dataTransfer.setDragImage(dragged, Math.max(0, event.clientX - rect.left), event.clientY - rect.top);
    });
    table.addEventListener('dragover', event => {
        if (!dragged) return;
        const target = event.target.closest('tr');
        if (!target?.querySelector('[data-sb-id]')) return;
        event.preventDefault();
        if (target !== dragged && performance.now() >= movingUntil) {
            const rect = target.getBoundingClientRect();
            const next = event.clientY < rect.top + rect.height / 2 ? target : target.nextSibling;
            if (next !== dragged && dragged.nextSibling !== next) {
                moveRows(() => target.parentNode.insertBefore(dragged, next), dragged);
            }
        }
    });
    table.addEventListener('drop', event => {
        if (!dragged) return;
        event.preventDefault(); dragged.classList.remove('sb-row-dragging'); highlight(dragged);
        dragged = null; void reorder(oldOrder);
    });
    table.addEventListener('dragend', () => {
        if (dragged) {
            dragged.classList.remove('sb-row-dragging');
            moveRows(() => oldOrder.forEach(row => table.tBodies[0].append(row)), dragged); dragged = null;
        }
    });
    table.addEventListener('keydown', event => {
        if (!event.target.closest('.sb-drag-handle') || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault(); if (busy || performance.now() < movingUntil) return;
        const previous = rows(), row = event.target.closest('tr'), index = previous.indexOf(row);
        const target = previous[index + (event.key === 'ArrowUp' ? -1 : 1)];
        if (!target) return;
        moveRows(() => target.parentNode.insertBefore(row, event.key === 'ArrowUp' ? target : target.nextSibling), row);
        event.target.closest('button').focus(); void reorder(previous);
    });
    async function leave(action) {
        if (busy) { notify('Wait for saving to finish.', 'warning'); return; }
        if (prompting) return;
        if (typeof window.confirmDialog !== 'function') { notify('Confirmation is unavailable. Save your changes first.'); return; }
        prompting = true;
        const choice = await window.confirmDialog({ title: 'Unsaved Service Bulletin changes',
            message: 'Save the changed rows before leaving?', okText: 'Save and leave',
            discardText: 'Leave without saving', cancelText: 'Cancel' });
        prompting = false;
        if (choice !== true && choice !== 'discard') return;
        busy = true;
        try {
            if (choice === true) { for (const form of forms.filter(dirty)) await save(form); }
            else { forms.forEach(form => { form.reset(); baseline.set(form, values(form)); paint(form); }); }
            if (forms.some(dirty)) throw new Error('Fields changed while saving. Save again before leaving.');
            busy = false;
            action();
        } catch (error) { notify(error.message); }
        finally { busy = false; }
    }
    // Capture before Bootstrap's delegated tab handler. No native beforeunload prompt.
    window.addEventListener('click', event => {
        const link = event.target.closest('a[href], [data-bs-toggle="tab"]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.getAttribute('href'), tab = link.matches('[data-bs-toggle="tab"]');
        if (!tab && (!href || href === '#' || href.startsWith('javascript:'))) return;
        if (tab && link.getAttribute('data-bs-target') === '#nav-sb') return;
        if (!forms.some(dirty) && !busy) return;
        event.preventDefault(); event.stopImmediatePropagation();
        void leave(() => { if (tab) bootstrap.Tab.getOrCreateInstance(link).show(); else window.location.assign(link.href); });
    }, true);
    window.addEventListener('submit', event => {
        if (forms.includes(event.target) || (!forms.some(dirty) && !busy)) return;
        event.preventDefault(); event.stopImmediatePropagation();
        const form = event.target, submitter = event.submitter;
        void leave(() => form.requestSubmit(submitter || undefined));
    }, true);
})();
