(() => {
    if (window.bushingSplitInstalled) return;
    window.bushingSplitInstalled = true;
    // Route selection represents a physical bushing row across all operations.
    document.addEventListener('change', event => {
        const box = event.target.closest('.bushing-batch-ungroup-checkbox');
        if (!box?.dataset.routeNumber) return;
        const table = box.closest('table');
        const row = box.closest('tr');
        if (!table || !row) return;
        table.querySelectorAll('.bushing-batch-ungroup-checkbox').forEach(other => {
            if (other.dataset.routeNumber === box.dataset.routeNumber && other.closest('tr') === row) {
                other.checked = box.checked;
            } else if (box.checked && other.dataset.routeNumber !== box.dataset.routeNumber) {
                other.checked = false;
            }
        });
    }, true);
    document.addEventListener('click', async event => {
        const button = event.target.closest('.js-bushing-split');
        if (!button || button.disabled) return;
        const notify = (message, type = 'warning') => window.showNotification?.(message, type);
        const checked = [...button.closest('.bushing-table-outer').querySelectorAll('.bushing-batch-ungroup-checkbox:checked')];
        const routes = new Set(checked.map(el => el.dataset.routeNumber));
        if (!checked.length || routes.size !== 1 || routes.has('')) {
            notify('Select bushings from one batch using the checkboxes beside its B number.'); return;
        }
        if (typeof window.confirmDialog !== 'function') {
            notify('Confirmation dialog is unavailable.', 'error'); return;
        }
        const count = new Set(checked.map(el => el.closest('tr'))).size;
        const members = [...button.closest('.bushing-table-outer').querySelectorAll('.bushing-batch-ungroup-checkbox')]
            .filter(el => el.dataset.routeNumber === checked[0].dataset.routeNumber);
        if (count === new Set(members.map(el => el.closest('tr'))).size) {
            notify('Select part of the batch; leave at least one bushing in the original batch.'); return;
        }
        const ids = [...new Set(checked.map(el => Number(el.dataset.woProcessId)))];
        button.disabled = true;
        try {
            if (!await window.confirmDialog({title: 'Split batch',
                message: `Move ${count} selected bushing row(s), with their full quantities and all processes, into a separate batch? The remaining rows keep their batch.`,
                okText: 'Split batch', cancelText: 'Cancel'})) return;
            const response = await fetch(button.dataset.url, {method: 'POST', headers: {
                'Content-Type': 'application/json', Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }, body: JSON.stringify({wo_bushing_process_ids: ids})});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Unable to split batch.');
            notify(`Created ${result.label}`, 'success');
            window.location.reload();
        } catch (error) { notify(error.message || 'Unable to split batch.', 'error'); }
        finally { button.disabled = false; }
    });
})();
