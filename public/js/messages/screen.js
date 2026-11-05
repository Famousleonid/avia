(() => {
    'use strict';
    const dialog = document.getElementById('screenMessageDialog');
    if (!dialog || dialog.dataset.bound) return;
    dialog.dataset.bound = '1';
    const button = document.getElementById('screenMessageDismiss');
    const error = document.getElementById('screenMessageError');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let active = null, checking = false, dismissing = false, stopped = false, generation = 0;
    let previewing = false, previewFocus = null;
    function render(message) {
        const kinds = { alert: ['Alert', '!'], info: ['Info', 'i'], message: ['Message', '✉'] };
        const kind = Object.hasOwn(kinds, message.kind) ? message.kind : 'info';
        dialog.dataset.kind = kind;
        document.getElementById('screenMessageTitle').textContent = kinds[kind][0];
        document.getElementById('screenMessageIcon').textContent = kinds[kind][1];
        document.getElementById('screenMessageSender').textContent = `From ${message.from_name} · ${message.date}`;
        document.getElementById('screenMessageText').textContent = message.text;
        error.hidden = true;
        button.textContent = previewing ? 'Close preview' : "Close — don't show again";
        dialog.showModal();
        button.focus();
    }
    function close() {
        dialog.close();
        active = null;
        previewing = false;
        if (previewFocus?.isConnected) previewFocus.focus({ preventScroll: true });
        previewFocus = null;
    }
    window.ScreenMessages = {
        preview(message) {
            if (active || dialog.open || dismissing) return false;
            generation++;
            previewing = true;
            previewFocus = document.activeElement;
            render(message);
            return true;
        },
    };
    async function poll() {
        if (checking || dismissing || previewing || stopped || document.hidden) return;
        checking = true;
        const requestGeneration = generation;
        try {
            const response = await fetch(dialog.dataset.pollUrl, { headers: { Accept: 'application/json' }, spinner: false, cache: 'no-store' });
            if ([401, 403, 419].includes(response.status) || response.redirected) { stopped = true; return; }
            if (!response.ok) return;
            const { items = [] } = await response.json();
            if (requestGeneration !== generation) return;
            // Another tab/device may already have acknowledged the active message.
            if (active && !items.some(item => item.id === active.id)) close();
            if (active || !items.length) return;
            // Do not take focus away from another application dialog or unsaved form modal.
            if (document.querySelector('dialog[open], .modal.show')) return;
            active = items[0];
            render(active);
        } catch (_) { /* Retry transient failures on the next poll. */ }
        finally { checking = false; }
    }
    async function dismiss() {
        if (previewing) {
            generation++;
            close();
            return;
        }
        if (!active || dismissing) return;
        dismissing = true;
        generation++;
        button.disabled = true;
        button.textContent = 'Closing…';
        error.hidden = true;
        try {
            const response = await fetch(active.dismiss_url, {
                method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, spinner: false,
            });
            if (!response.ok || response.redirected) throw new Error();
            close();
        } catch (_) {
            error.textContent = 'Could not save dismissal. Please try again.';
            error.hidden = false;
        } finally {
            dismissing = false;
            button.disabled = false;
            button.textContent = "Close — don't show again";
        }
        if (!active) poll();
    }
    button.addEventListener('click', dismiss);
    dialog.addEventListener('cancel', event => { event.preventDefault(); dismiss(); });
    // Bootstrap focus traps must not move focus out of the application's top-layer dialog.
    dialog.addEventListener('focusin', event => event.stopPropagation());
    window.addEventListener('focus', poll);
    document.addEventListener('visibilitychange', poll);
    document.addEventListener('hidden.bs.modal', poll);
    setInterval(poll, 30000);
    poll();
})();
