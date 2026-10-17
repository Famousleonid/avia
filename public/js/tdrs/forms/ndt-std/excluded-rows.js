(() => {
    const button = document.querySelector('#ndt-toggle-excluded, #cad-toggle-excluded');
    if (!button) return;
    button.addEventListener('click', async () => {
        const show = button.getAttribute('aria-pressed') !== 'true';
        const url = new URL(window.location.href);
        if (show) url.searchParams.set('show_excluded', '1');
        else url.searchParams.delete('show_excluded');
        button.disabled = true;
        try {
            const response = await fetch(url, {headers: {'Accept': 'text/html'}});
            if (!response.ok || response.redirected) throw new Error('Could not load process sheet rows. Please refresh the page and try again.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const sheets = [...doc.querySelectorAll('.std-sheet-container')];
            const first = document.querySelector('.std-sheet-container');
            if (!sheets.length || !first || !doc.getElementById(button.id)) throw new Error('Could not load process sheet rows.');
            // Keep the first page/header and its live QR host; replace the paginated table only.
            for (const selector of ['.page-rows-container', '.std-table-summary', '.std-footer']) {
                first.querySelector(selector).replaceWith(document.importNode(sheets[0].querySelector(selector), true));
            }
            document.querySelectorAll('.std-sheet-container').forEach(sheet => { if (sheet !== first) sheet.remove(); });
            let last = first;
            for (const sheet of sheets.slice(1)) {
                const next = document.importNode(sheet, true);
                last.after(next);
                last = next;
            }
            button.setAttribute('aria-pressed', String(show));
            button.textContent = show ? 'Hide crossed-out rows' : 'Show crossed-out rows';
            window.history.replaceState(null, '', url);
        } catch (error) {
            window.showNotification(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
})();
