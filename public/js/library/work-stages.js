(() => {
    const library = document.querySelector('[data-stage-library]');
    if (!library) return;

    const template = library.querySelector('[data-stage-editor]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const mutationHeaders = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
    const staleSections = new Set();
    let draft = null;
    let busy = false;

    function setBusy(value) {
        busy = value;
        library.querySelectorAll('[data-stage-edit], [data-stage-delete], [data-stage-action], [data-stage-refresh], [data-stage-color], [data-stage-color-reset]')
            .forEach(control => { control.disabled = value; });
    }

    function showError(message) {
        window.showNotification(message, 'error');
    }

    async function mutate(url, method, body) {
        const response = await fetch(url, { method, body, headers: mutationHeaders, spinner: false });
        if (response.status === 204 && !response.redirected) return;

        const error = await response.json().catch(() => ({}));
        const message = Object.values(error.errors ?? {}).flat().join(' ') || error.message;
        throw new Error(message || 'Could not save changes.');
    }

    async function saveColor(input, color) {
        const previous = input.dataset.savedColor;
        if (busy || color === previous) return;

        const row = input.closest('[data-stage-row]');
        const reset = row.querySelector('[data-stage-color-reset]');
        const body = new FormData();
        body.set('_method', 'PATCH');
        body.set('text_color', color);
        setBusy(true);
        try {
            await mutate(input.dataset.url, 'POST', body);
            input.dataset.savedColor = color;
            row.querySelector('.work-stage-color').style.setProperty('--work-stage-color', color);
            reset.hidden = !color;
        } catch (error) {
            showError(error.message);
        } finally {
            input.value = input.dataset.savedColor || '#e8eef5';
            setBusy(false);
            input.focus({ preventScroll: true });
        }
    }

    async function refresh(section) {
        const key = section.dataset.section;
        const retry = section.closest('.stage-catalog').querySelector('[data-stage-refresh]');
        const url = new URL(library.dataset.url);
        url.searchParams.set('section', key);
        section.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) throw new Error('Refresh failed');
            const archiveWasOpen = section.querySelector('[data-stage-archive-list]')?.open;
            section.innerHTML = await response.text();
            const archive = section.querySelector('[data-stage-archive-list]');
            if (archive && archiveWasOpen) archive.open = true;
            staleSections.delete(key);
            retry.hidden = true;
            return true;
        } catch {
            staleSections.add(key);
            retry.hidden = false;
            showError('The table could not be refreshed. Use Refresh table.');
            return false;
        } finally {
            section.removeAttribute('aria-busy');
        }
    }

    function closeEditor({ restoreFocus = true } = {}) {
        const { row, trigger } = draft;
        draft = null;
        row.remove();
        if (restoreFocus) trigger.focus({ preventScroll: true });
    }

    function openEditor(section, trigger) {
        if (draft) {
            draft.form.elements.name.focus();
            return;
        }

        const source = trigger.closest('[data-stage-row]');
        const kind = trigger.dataset.stageEdit;
        const task = kind === 'task';
        const updating = trigger.hasAttribute('data-update');
        const row = template.content.firstElementChild.cloneNode(true);
        row.firstElementChild.colSpan = Array.from(section.querySelector('thead tr').cells)
            .reduce((count, cell) => count + cell.colSpan, 0);
        const form = row.querySelector('form');
        const fields = form.elements;
        fields.name.value = updating ? source.dataset.name : '';
        fields.name.maxLength = task ? 255 : 250;
        fields.sort_order.value = updating ? source.dataset.order : (trigger.dataset.nextOrder ?? '0');
        form.querySelector('[data-group-field]').hidden = !task;
        form.querySelector('[data-start-field]').hidden = !task;
        fields.task_has_start_date.disabled = !task;

        if (task) {
            fields.general_task_id.append(section.querySelector('[data-general-task-options]').content.cloneNode(true));
            fields.general_task_id.value = updating ? source.dataset.group : trigger.dataset.group;
            fields.general_task_id.required = true;
            fields.general_task_id.disabled = updating && source.hasAttribute('data-linked');
            fields.task_has_start_date.checked = updating && source.dataset.start === '1';
        } else {
            fields.general_task_id.disabled = true;
        }

        draft = { section, trigger, row, form, method: updating ? 'PATCH' : 'POST' };
        if (source) source.after(row);
        else section.querySelector('tbody').prepend(row);
        fields.name.focus();
    }

    library.addEventListener('change', event => {
        const input = event.target;
        if (input.matches('[data-stage-color]')) saveColor(input, input.value);
    });

    library.addEventListener('click', async event => {
        const button = event.target.closest('button');
        if (!button || busy) return;

        const card = button.closest('.stage-catalog');
        const section = card?.querySelector('[data-section]');
        if (button.hasAttribute('data-stage-color-reset')) {
            await saveColor(button.closest('[data-stage-row]').querySelector('[data-stage-color]'), '');
        } else if (button.hasAttribute('data-stage-cancel')) {
            closeEditor();
        } else if (button.hasAttribute('data-stage-refresh')) {
            setBusy(true);
            await refresh(section);
            setBusy(false);
        } else if (button.hasAttribute('data-stage-edit') && !staleSections.has(section.dataset.section)) {
            openEditor(section, button);
        } else if (button.matches('[data-stage-delete], [data-stage-action]') && !staleSections.has(section.dataset.section)) {
            if (draft) {
                draft.form.elements.name.focus();
                return;
            }
            const action = button.dataset.stageAction ?? 'delete';
            if (action !== 'restore' && typeof window.confirmDialog !== 'function') {
                showError('The confirmation dialog is unavailable.');
                return;
            }

            setBusy(true);
            let focus = button;
            try {
                const name = button.closest('[data-stage-row]').dataset.name;
                if (action === 'delete') {
                    const message = button.hasAttribute('data-history-preserved')
                        ? `Remove “${name}” from the library and active workorder stages? Saved workorder dates and history will be kept.`
                        : `Delete “${name}”?`;
                    if (!await window.confirmDialog({ title: 'Delete entry', message, okText: 'Delete', danger: true })) return;
                }
                if (action === 'archive' && !await window.confirmDialog({
                    title: 'Archive task', message: `Archive “${name}”? Workorder history will be kept.`, okText: 'Archive',
                })) return;
                await mutate(button.dataset.url, action === 'delete' ? 'DELETE' : 'PATCH');
                await refresh(section);
                focus = card.querySelector('.stage-catalog-head [data-stage-edit]');
            } catch (error) {
                showError(error.message);
            } finally {
                setBusy(false);
                focus?.focus({ preventScroll: true });
            }
        }
    });

    library.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !draft || busy || !draft.form.contains(event.target)) return;
        event.preventDefault();
        closeEditor();
    });

    library.addEventListener('submit', async event => {
        if (!draft || event.target !== draft.form) return;
        event.preventDefault();
        if (busy) return;

        const { form, trigger, method, section } = draft;
        const body = new FormData(form);
        const errorMessage = form.querySelector('[role="alert"]');
        const controls = Array.from(form.elements).filter(control => !control.disabled);
        controls.forEach(control => { control.disabled = true; });
        errorMessage.hidden = true;
        setBusy(true);
        let focus = null;

        try {
            // PHP only parses multipart bodies for POST. Send Laravel's method override for edits.
            if (method === 'PATCH') body.set('_method', 'PATCH');
            await mutate(trigger.dataset.url, 'POST', body);
            closeEditor({ restoreFocus: false });
            await refresh(section);
            focus = trigger.isConnected ? trigger : Array.from(section.querySelectorAll('[data-stage-edit]'))
                    .find(button => button.dataset.url === trigger.dataset.url && button.dataset.stageEdit === trigger.dataset.stageEdit);
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        } finally {
            controls.forEach(control => { control.disabled = false; });
            setBusy(false);
            focus?.focus({ preventScroll: true });
        }
    });
})();
