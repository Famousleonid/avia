(() => {
    'use strict';
    async function init() {
        const pane = document.getElementById('nav-processes');
        if (!pane) return;
        const scope = 'manuals.show';
        const key = 'processGroupsOpen:' + pane.dataset.manualId;
        const sortKey = 'processSort:' + pane.dataset.manualId;
        const container = pane.querySelector('.process-table-container');
        let open = new Set();
        let saveQueue = Promise.resolve();
        let sortColumn = 'process';
        let sortDirection = 1;
        let sortChanged = false;
        let sortSaveQueue = Promise.resolve();
        // Compare the complete text from its first character; numbers are text too.
        const collator = new Intl.Collator('en', { sensitivity: 'base', numeric: false, ignorePunctuation: false });
        function sortText(value) {
            // Imported line breaks / padding must not outrank the next letter or digit.
            // Keep the original display and punctuation; normalize only the comparison key.
            return value.normalize('NFC').replace(/[\u200B\uFEFF]/g, '')
                .replace(/\s+/g, ' ').replace(/\s*([:;,])\s*/g, '$1').trim();
        }
        function sortRows() {
            const top = container.scrollTop;
            const left = container.scrollLeft;
            const selector = sortColumn === 'comment' ? '.manual-process-comment' : '.manual-process-specification-text';
            pane.querySelectorAll('.manual-process-group-row').forEach(group => {
                const children = rows(group.dataset.processNameId).map(row => {
                    const text = sortText(row.querySelector(selector)?.textContent || '');
                    return { row, text: sortColumn === 'comment' && text === '-' ? '' : text };
                });
                children.sort((a, b) => {
                    // Empty comments remain at the bottom in either direction.
                    if (!a.text !== !b.text) return a.text ? -1 : 1;
                    return sortDirection * collator.compare(a.text, b.text)
                        || Number(a.row.dataset.manualProcessId) - Number(b.row.dataset.manualProcessId);
                });
                let previous = group;
                children.forEach(({ row }) => { previous.after(row); previous = row; });
            });
            pane.querySelectorAll('[data-process-sort]').forEach(button => {
                const active = button.dataset.processSort === sortColumn;
                button.closest('th').setAttribute('aria-sort', active ? (sortDirection === 1 ? 'ascending' : 'descending') : 'none');
                button.querySelector('[data-sort-indicator]').textContent = active ? (sortDirection === 1 ? '↑' : '↓') : '↕';
            });
            container.scrollTop = top;
            container.scrollLeft = left;
        }
        function notify(message, type) { window.showNotification?.(message, type); }
        function persist() {
            const value = [...open];
            saveQueue = saveQueue.then(() => window.UserUiSettings.set(scope, key, value, { keepalive: true }))
                .catch(() => notify('Could not save open process groups.', 'error'));
        }
        function rows(id) {
            return [...pane.querySelectorAll('.manual-process-child-row')].filter(row => row.dataset.processNameId === id);
        }
        function render() {
            pane.querySelectorAll('.manual-process-group-row').forEach(group => {
                const id = group.dataset.processNameId;
                const expanded = open.has(id);
                const button = group.querySelector('[data-process-group-toggle]');
                button.disabled = false;
                button.setAttribute('aria-expanded', String(expanded));
                button.querySelector('i').className = expanded ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
                const children = rows(id);
                group.querySelector('[data-process-group-count]').textContent = '(' + children.length + ')';
                children.forEach(row => { row.hidden = !expanded; });
            });
        }
        sortRows();
        pane.addEventListener('click', event => {
            const button = event.target.closest('[data-process-sort]');
            if (!button) return;
            sortDirection = sortColumn === button.dataset.processSort ? -sortDirection : 1;
            sortColumn = button.dataset.processSort;
            sortChanged = true;
            sortRows();
            const value = { column: sortColumn, direction: sortDirection };
            sortSaveQueue = sortSaveQueue.then(() => window.UserUiSettings.set(scope, sortKey, value, { keepalive: true }))
                .catch(() => notify('Could not save process sorting.', 'error'));
        });
        const loadSort = window.UserUiSettings.get(scope, sortKey, null).then(saved => {
            // A slow settings response must not replace a choice already made on this page.
            if (!sortChanged && saved && ['process', 'comment'].includes(saved.column) && [1, -1].includes(saved.direction)) {
                sortColumn = saved.column;
                sortDirection = saved.direction;
                sortRows();
            }
        }).catch(() => notify('Could not load process sorting.', 'error'));
        try {
            const saved = await window.UserUiSettings.get(scope, key, []);
            open = new Set(Array.isArray(saved) ? saved.map(String) : []);
        } catch (_) { notify('Could not load open process groups.', 'error'); }
        await loadSort;
        render();
        pane.addEventListener('click', event => {
            const toggle = event.target.closest('[data-process-group-toggle]');
            const add = event.target.closest('[data-add-group-process]');
            if ((!toggle && !add) || (toggle || add).disabled) return;
            const id = (toggle || add).closest('.manual-process-group-row').dataset.processNameId;
            if (toggle && open.has(id)) open.delete(id); else open.add(id);
            render();
            persist();
        });
        document.addEventListener('manual-processes-refreshed', () => { sortRows(); render(); });
        document.addEventListener('manual-process-version-saved', sortRows);
        pane.addEventListener('submit', async event => {
            const form = event.target.closest('[data-manual-process-archive-form]');
            if (!form) return;
            event.preventDefault();
            if (form.dataset.busy === '1') return;
            if (typeof window.confirmDialog !== 'function') {
                notify('Archive confirmation dialog is not available.', 'error');
                return;
            }
            form.dataset.busy = '1';
            const button = form.querySelector('[type="submit"]');
            const wasDisabled = button.disabled;
            button.disabled = true;
            try {
                const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
                const impactResponse = await fetch(form.dataset.deletionImpactUrl, { headers });
                const impact = await impactResponse.json();
                if (!impactResponse.ok) throw new Error(impact.message || 'Could not check process links.');
                const confirmed = await window.confirmDialog({
                    title: form.dataset.confirmTitle || 'Archive process?',
                    message: impact.message,
                    okText: 'Archive', cancelText: 'Cancel', danger: true,
                });
                if (!confirmed || impact.blocked) return;
                const response = await fetch(form.action, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { ...headers, 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.success !== true) throw new Error(data.message || 'Could not archive process.');
                const top = container.scrollTop;
                const left = container.scrollLeft;
                const row = form.closest('.manual-process-child-row');
                const id = row.dataset.processNameId;
                row.remove();
                if (!rows(id).length) {
                    [...pane.querySelectorAll('.manual-process-group-row')]
                        .find(group => group.dataset.processNameId === id)?.remove();
                }
                render();
                container.scrollTop = top;
                container.scrollLeft = left;
                const count = document.querySelector('#nav-processes-tab .manual-parts-tab-count');
                if (count) count.textContent = '(' + pane.querySelectorAll('.manual-process-child-row').length + ')';
                document.dispatchEvent(new CustomEvent('manual-process-version-saved'));
                notify(data.message, 'success');
            } catch (error) { notify(error.message || 'Could not archive process.', 'error'); }
            finally { delete form.dataset.busy; button.disabled = wasDisabled; }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
