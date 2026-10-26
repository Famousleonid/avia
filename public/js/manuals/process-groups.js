(() => {
    'use strict';
    async function init() {
        const pane = document.getElementById('nav-processes');
        if (!pane) return;
        const scope = 'manuals.show';
        const key = 'processGroupsOpen:' + pane.dataset.manualId;
        const container = pane.querySelector('.process-table-container');
        let open = new Set();
        let saveQueue = Promise.resolve();
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
        try {
            const saved = await window.UserUiSettings.get(scope, key, []);
            open = new Set(Array.isArray(saved) ? saved.map(String) : []);
        } catch (_) { notify('Could not load open process groups.', 'error'); }
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
        document.addEventListener('manual-processes-refreshed', render);
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
