(() => {
    const root = document.getElementById('bushingGuide');
    if (!root) return;
    document.title = root.querySelector('h1').textContent;
    const cells = [...root.querySelectorAll('[data-guide-cell]')];
    const saved = new Map(cells.map(cell => [cell, cell.value]));
    const save = document.getElementById('guideSave');
    const discard = document.getElementById('guideDiscard');
    let saving = false;
    const dirty = () => cells.filter(cell => cell.value !== saved.get(cell));
    const resize = cell => { if (!cell.offsetWidth) return; cell.style.height = 'auto'; cell.style.height = cell.scrollHeight + 'px'; };
    const measure = document.createElement('canvas').getContext('2d');
    function sizeItems() {
        const table = root.querySelector('.guide-items-table');
        if (!table?.offsetWidth || !measure) return;
        const cols = [...table.querySelectorAll('col')];
        const widths = cols.map(() => 18);
        table.querySelectorAll('tr').forEach(row => [...row.cells].forEach(td => {
            const input = td.querySelector('textarea');
            if (!input) return;
            // Rowspans remove cells from later DOM rows: use the workbook column,
            // not the cell's position in that row.
            const letters = input.dataset.address.match(/^[A-Z]+/)[0];
            const i = [...letters].reduce((index, letter) => index * 26 + letter.charCodeAt(0) - 64, 0) - 1;
            const style = getComputedStyle(input || td);
            measure.font = `${style.fontStyle} ${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
            const needed = Math.max(...input.value.split(/\r?\n/).map(line => Math.ceil(measure.measureText(line).width) + 24));
            const span = Math.min(td.colSpan, cols.length - i);
            const current = widths.slice(i, i + span).reduce((a, b) => a + b, 0);
            if (needed > current) for (let column = i; column < i + span; column++) widths[column] += (needed - current) / span;
        }));
        const total = widths.reduce((a, b) => a + b, 0);
        cols.forEach((col, i) => col.style.width = `${widths[i] / total * 100}%`);
        table.style.setProperty('--guide-table-width', `${Math.ceil(total)}px`);
    }
    const resizeVisible = () => { sizeItems(); cells.forEach(resize); };
    function syncRepeatedHeaders() {
        root.querySelectorAll('.guide-table').forEach(table => {
            const values = row => JSON.stringify([...row.querySelectorAll('.guide-cell-text')].map(cell => cell.textContent));
            const headings = [...table.querySelectorAll('thead tr')].map(values);
            table.querySelectorAll('tbody tr').forEach(row => row.classList.toggle('guide-repeated-heading', headings.includes(values(row))));
        });
    }
    function refresh() {
        const changed = dirty();
        if (save) {
            save.disabled = saving || !changed.length;
            save.textContent = saving ? 'Saving…' : 'Save';
        }
        if (discard) discard.disabled = saving || !changed.length;
    }
    async function confirmDiscard() {
        if (typeof window.confirmDialog !== 'function') {
            window.showNotification?.('Cannot confirm leaving. Save your changes before leaving.', 'error');
            return false;
        }
        return await window.confirmDialog({ message: 'Discard unsaved changes?', title: 'Unsaved changes', okText: 'Discard changes', cancelText: 'Keep editing' });
    }
    root.addEventListener('input', event => {
        if (!event.target.matches('[data-guide-cell]')) return;
        const cell = event.target;
        cell.classList.toggle('is-dirty', cell.value !== saved.get(cell));
        if (cell.closest('.guide-items-table')) sizeItems();
        resize(cell); refresh();
    });
    root.querySelectorAll('[data-guide-tab]').forEach(button => button.addEventListener('click', () => {
        root.querySelectorAll('[data-guide-tab]').forEach(tab => {
            const active = tab === button;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        root.querySelectorAll('[data-guide-sheet]').forEach(sheet => sheet.classList.toggle('is-active', sheet.dataset.guideSheet === button.dataset.guideTab));
        resizeVisible();
    }));
    root.querySelector('[role="tablist"]').addEventListener('keydown', event => {
        const tabs = [...root.querySelectorAll('[data-guide-tab]')];
        const index = tabs.indexOf(event.target);
        if (index < 0 || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        tabs[next].click(); tabs[next].focus();
    });
    save?.addEventListener('click', async () => {
        const changed = dirty();
        if (saving || !changed.length) return;
        saving = true; cells.forEach(cell => cell.disabled = true); refresh();
        try {
            const response = await fetch(root.dataset.saveUrl, {
                method: 'PUT', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ revision: Number(root.dataset.revision), changes: changed.map(cell => ({ sheet: Number(cell.dataset.sheet), address: cell.dataset.address, value: cell.value })) }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not save the form.');
            root.dataset.revision = data.revision;
            root.querySelectorAll('[data-guide-revision]').forEach(el => el.textContent = data.revision);
            changed.forEach(cell => { saved.set(cell, cell.value); cell.classList.remove('is-dirty'); cell.nextElementSibling.textContent = cell.value; });
            syncRepeatedHeaders();
            window.showNotification?.('Form saved.', 'success');
        } catch (error) {
            window.showNotification?.(error.message || 'Could not save the form. Your changes are still on screen.', 'error');
        } finally {
            saving = false; cells.forEach(cell => cell.disabled = false); refresh(); resizeVisible();
        }
    });
    discard?.addEventListener('click', async () => {
        if (saving || !dirty().length || !await confirmDiscard()) return;
        cells.forEach(cell => { cell.value = saved.get(cell); cell.classList.remove('is-dirty'); });
        refresh(); resizeVisible();
    });
    let confirmingNavigation = false;
    // Window capture runs before the document's global navigation spinner.
    window.addEventListener('click', async event => {
        const anchor = event.target.closest('a[href]');
        if (!anchor || event.defaultPrevented || (!dirty().length && !saving)) return;
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self')) return;
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || anchor.hasAttribute('data-bs-toggle')) return;
        const destination = new URL(anchor.href, location.href);
        if (!['http:', 'https:'].includes(destination.protocol)) return;
        if (destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (saving) { window.showNotification?.('Please wait until saving finishes.', 'warning'); return; }
        if (confirmingNavigation) return;
        confirmingNavigation = true;
        try {
            if (await confirmDiscard()) {
                window.safeShowSpinner?.();
                window.location.assign(destination.href);
            }
        } finally { confirmingNavigation = false; }
    }, true);
    root.querySelectorAll('[data-guide-print]').forEach(button => button.addEventListener('click', () => {
        if (saving || dirty().length) { window.showNotification?.('Save or cancel your changes before printing.', 'warning'); return; }
        window.print();
    }));
    window.addEventListener('beforeprint', syncRepeatedHeaders);
    window.addEventListener('resize', resizeVisible);
    resizeVisible();
    syncRepeatedHeaders();
})();
