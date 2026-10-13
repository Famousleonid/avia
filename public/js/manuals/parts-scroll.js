(() => {
    window.initManualPartsScroll = async ({ table, input, clearInput, scope, manualId }) => {
        const body = table.tBodies[0], container = table.closest('.parts-table-container');
        const more = document.getElementById('manual-parts-more');
        const key = 'partsSearch:' + manualId;
        let offset = body.querySelectorAll('tr[data-component-id]').length;
        let hasMore = offset < Number(table.dataset.total), busy = false, generation = 0, controller, timer;
        let sort = 'ipl_num', direction = 'asc', failed = false;
        const headers = [...table.querySelectorAll('th.sortable')];
        function controls() {
            more.hidden = !hasMore && !busy && !failed;
            more.disabled = busy;
            more.textContent = busy ? 'Loading…' : failed ? 'Could not load parts — retry' : 'Load more';
            headers.forEach((th, i) => {
                th.classList.toggle('sorted-asc', ['ipl_num', 'part_number', 'name'][i] === sort && direction === 'asc');
                th.classList.toggle('sorted-desc', ['ipl_num', 'part_number', 'name'][i] === sort && direction === 'desc');
            });
        }
        function initializeRows() {
            body.querySelectorAll('[data-bs-toggle="popover"]').forEach(node => {
                window.bootstrap?.Popover?.getOrCreateInstance(node, { sanitize: false });
            });
            table.dispatchEvent(new CustomEvent('manual-parts:rows-loaded'));
        }
        async function load(reset = false) {
            if (!reset && (busy || !hasMore)) return;
            if (reset) {
                controller?.abort(); generation++; offset = 0; hasMore = true;
                body.querySelectorAll('[data-bs-toggle="popover"]').forEach(n => window.bootstrap?.Popover?.getInstance(n)?.dispose());
                body.replaceChildren(); container.scrollTop = 0;
            }
            const version = generation;
            controller = new AbortController(); busy = true; failed = false; controls();
            try {
                const url = new URL(table.dataset.rowsUrl, location.origin);
                url.search = new URLSearchParams({ offset, q: input.value.trim(), sort, direction });
                const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Could not load parts');
                const data = await response.json();
                if (version !== generation) return;
                body.querySelector('.components-empty-row')?.remove();
                body.insertAdjacentHTML('beforeend', data.html);
                offset = data.next; hasMore = data.has_more;
                table.dataset.total = data.total;
                document.querySelector('#nav-parts-tab .manual-parts-tab-count').textContent = '(' + data.total + ')';
                if (!offset) body.innerHTML = '<tr class="components-empty-row"><td colspan="19" class="text-muted py-4">PARTS NOT FOUND</td></tr>';
                initializeRows();
            } catch (error) {
                if (error.name !== 'AbortError' && version === generation) failed = true;
            } finally {
                if (version === generation) { busy = false; controls(); }
            }
        }
        function check() {
            if (!failed && container.clientHeight && container.scrollHeight - container.scrollTop - container.clientHeight < 220) load();
        }
        input.value = await window.UserUiSettings.get(scope, key, '');
        input.addEventListener('input', () => {
            controller?.abort(); generation++; busy = false;
            clearTimeout(timer);
            window.UserUiSettings.set(scope, key, input.value);
            timer = setTimeout(() => load(true), 200);
        });
        clearInput?.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('input')); input.focus(); });
        headers.forEach((th, index) => th.addEventListener('click', () => {
            const next = ['ipl_num', 'part_number', 'name'][index];
            direction = next === sort && direction === 'asc' ? 'desc' : 'asc'; sort = next;
            clearTimeout(timer); load(true);
        }));
        more.addEventListener('click', () => load());
        container.addEventListener('scroll', check, { passive: true });
        document.getElementById('nav-parts-tab').addEventListener('shown.bs.tab', check);
        table.addEventListener('manual-parts:row-deleted', () => {
            offset = Math.max(0, offset - 1);
            controls(); check();
        });
        const target = new URLSearchParams(location.search).get('part_id');
        if (target) input.value = '';
        if (input.value || target) await load(true);
        while (target && !document.getElementById('manual-part-row-' + target) && hasMore && !failed) await load();
        controls(); initializeRows();
    };
})();
