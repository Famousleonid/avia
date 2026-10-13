(function () {
    'use strict';
    if (window.BushingSelection) { window.BushingSelection.refresh(); return; }
    let state = null, applying = false, busy = false, refreshing = false;
    const dirtyForms = new WeakSet(), staleForms = new WeakSet();
    const printStyle = document.createElement('style');
    printStyle.textContent = '@media print { #bushing-selection-dialog, #bushing-selection-dialog::backdrop { display:none !important; } }';
    document.head.append(printStyle);
    const config = () => window.bushingSelectionConfig;
    const notify = (text, type = 'error') => window.showNotification?.(text, type);
    const componentId = control => Number((control.name || '').match(/\[items\]\[(\d+)\]/)?.[1]);
    const forms = () => [...document.querySelectorAll('form#bushings-form')];
    const field = (checkbox, suffix) => checkbox.form?.querySelector('[name="' + CSS.escape(checkbox.name.replace('[selected]', '[' + suffix + ']')) + '"]' + (['do_not_order', 'need_processes'].includes(suffix) ? '[type="checkbox"]' : ''));
    const groupFor = id => state?.groups.find(g => g.options.some(o => o.id === id));
    function hidden(form, name, value) {
        let input = form.querySelector('input[name="' + name + '"]');
        if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = name; form.append(input); }
        input.value = value;
    }
    function render(next, own = false) {
        const externalChange = state && state.revision !== next.revision && !own;
        state = next;
        applying = true;
        try {
            const options = new Map(state.groups.flatMap(g => g.options).map(o => [o.id, o]));
            const drafts = new Map((state.drafts || []).map(d => [Number(d.bushing), d]));
            forms().forEach(form => {
                if (externalChange && dirtyForms.has(form)) {
                    staleForms.add(form);
                    notify('Bushing selection changed on another screen. Reopen the editor before saving process changes.');
                }
                if (!staleForms.has(form) && state.draft_token) hidden(form, 'draft_token', state.draft_token);
                if (state.bushing_id && form.classList.contains('bushing-create-form')) {
                    form.action = config().updateFormUrl + '/' + state.bushing_id;
                    hidden(form, '_method', 'PUT');
                }
                form.querySelectorAll('.component-checkbox').forEach(checkbox => {
                    const option = options.get(componentId(checkbox));
                    if (!option) return;
                    const wasChecked = checkbox.checked;
                    checkbox.checked = option.selected;
                    checkbox.disabled = option.locked || option.history || busy;
                    checkbox.title = option.reason || 'Saved automatically; shared with KIT / Extra parts.';
                    const qty = field(checkbox, 'qty'), code = field(checkbox, 'codes_id'), noOrder = field(checkbox, 'do_not_order');
                    if (qty) qty.value = option.qty;
                    if (noOrder) noOrder.checked = option.do_not_order;
                    // Restore process fields only when the server restores a previously unselected row.
                    const draft = drafts.get(option.id);
                    if (!wasChecked && option.selected && draft && !dirtyForms.has(form)) {
                        const need = field(checkbox, 'need_processes');
                        if (need) need.checked = draft.need_processes;
                        Object.entries(draft.processes || {}).forEach(([key, value]) => {
                            const control = field(checkbox, key) || field(checkbox, key + '][');
                            if (control) control.value = Array.isArray(value) ? (value[0] || '') : (value || '');
                        });
                    }
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    const currentCode = field(checkbox, 'codes_id') || code;
                    if (currentCode && option.codes_id) currentCode.value = option.codes_id;
                    if (qty) qty.disabled = !option.selected || option.locked || option.history || busy;
                });
            });
            document.querySelectorAll('[data-bushing-selection-id]').forEach(el => {
                const option = options.get(Number(el.dataset.bushingSelectionId));
                if (!option) return;
                el.dataset.kitPrlControllerCrossedOut = option.locked ? '1' : '0';
                el.dataset.kitPrlManualCrossedOut = !option.locked && option.crossed_out ? '1' : '0';
                el.classList.toggle('kit-prl-option-crossed-out', option.crossed_out);
                el.classList.toggle('prl-part-number-crossed-out', option.crossed_out);
                el.classList.toggle('kit-prl-option-locked', option.locked);
                if (option.crossed_out) el.dataset.prlPartNumberCrossedOut = '1'; else delete el.dataset.prlPartNumberCrossedOut;
                el.title = option.reason || 'Click to change selection / QTY. Shared with Bushings.';
                const row = el.closest('.data-row-prl');
                if (row) {
                    const group = groupFor(option.id);
                    const crossed = group.options.every(o => o.crossed_out);
                    row.classList.toggle('prl-row-crossed-out', crossed);
                    if (crossed) row.dataset.prlCrossedOut = '1'; else delete row.dataset.prlCrossedOut;
                }
            });
        } finally { applying = false; }
        if (externalChange && forms().length === 0) window.refreshSharedBushingView?.();
    }
    async function refresh() {
        if (busy || refreshing || !config()) return;
        refreshing = true;
        try {
            const response = await fetch(config().url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Could not refresh bushing selection.');
            render(await response.json());
        } catch (e) { notify(e.message); } finally { refreshing = false; }
    }
    async function save(id, items, revision = state?.revision) {
        if (busy || !state) return false;
        busy = true;
        try {
            const response = await fetch(config().url + '/' + id, {
                method: 'PATCH', headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config().csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ revision, items }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Selection was not saved.');
            busy = false;
            render(data, true);
            return true;
        } catch (e) {
            notify(e.message); busy = false; await refresh(); return false;
        } finally { busy = false; }
    }
    function editGroup(id, desiredSelected) {
        if (!state || busy || document.getElementById('bushing-selection-dialog')) return;
        const group = groupFor(id), target = group?.options.find(o => o.id === id);
        const revision = state.revision;
        if (!target) return;
        if (target.locked || target.history) { notify(target.reason); return; }
        const dialog = document.createElement('dialog');
        dialog.id = 'bushing-selection-dialog';
        dialog.style.cssText = 'width:min(600px,94vw);max-height:85vh;overflow:auto;padding:20px;border:1px solid #7b8b98;border-radius:8px;background:#22313f;color:#f1f4f7;z-index:11000;font:14px Arial';
        const title = document.createElement('h3'); title.textContent = 'Bushing selection'; title.style.fontSize = '20px';
        const hint = document.createElement('p');
        hint.textContent = 'Total QTY: max ' + group.capacity + '. Split between Original / Oversize if needed. No selected variants means all excluded.';
        dialog.append(title, hint);
        const controls = [];
        group.options.forEach(option => {
            const row = document.createElement('label'); row.style.cssText = 'display:flex;gap:12px;align-items:center;margin:10px 0';
            const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = option.id === id ? desiredSelected : option.selected;
            checkbox.disabled = option.locked || option.history;
            const label = document.createElement('span'); label.textContent = option.ipl + ' — ' + option.part_number; label.style.flex = '1';
            const qty = document.createElement('input'); qty.type = 'number'; qty.min = '1'; qty.max = String(group.capacity); qty.value = option.qty; qty.style.cssText = 'width:70px;padding:4px';
            qty.disabled = option.locked || option.history; qty.setAttribute('aria-label', 'QTY ' + option.part_number);
            row.title = option.reason; row.append(checkbox, label, qty); dialog.append(row);
            controls.push({ option, checkbox, qty, doNotOrder: option.id === id && desiredSelected ? false : option.do_not_order });
        });
        const error = document.createElement('p'); error.style.color = '#ffb5b5'; error.setAttribute('role', 'alert');
        const actions = document.createElement('div'); actions.style.cssText = 'display:flex;gap:12px;justify-content:flex-end;margin-top:16px';
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Cancel'; cancel.className = 'btn btn-secondary';
        const submit = document.createElement('button'); submit.type = 'button'; submit.textContent = 'Save selection'; submit.className = 'btn btn-primary';
        cancel.onclick = () => dialog.close();
        submit.onclick = async () => {
            const items = {}, total = controls.reduce((sum, c) => sum + (c.checkbox.checked && !c.doNotOrder ? Number(c.qty.value) : 0), 0);
            if (controls.some(c => c.checkbox.checked && (!Number.isInteger(Number(c.qty.value)) || Number(c.qty.value) < 1)) || total > group.capacity) {
                error.textContent = 'Enter positive whole quantities. Total QTY must not exceed ' + group.capacity + '.'; return;
            }
            controls.forEach(c => { if (!c.option.locked && !c.option.history) items[c.option.id] = { selected: c.checkbox.checked, qty: Number(c.qty.value) || 1, do_not_order: c.doNotOrder }; });
            submit.disabled = true;
            await save(id, items, revision);
            dialog.close();
            submit.disabled = false;
        };
        actions.append(cancel, submit); dialog.append(error, actions); document.body.append(dialog);
        dialog.addEventListener('close', () => dialog.remove()); dialog.showModal();
    }
    document.addEventListener('change', async event => {
        if (applying) return;
        const control = event.target, form = control.closest('form#bushings-form');
        if (!form) return;
        const id = componentId(control), group = groupFor(id), option = group?.options.find(o => o.id === id);
        if (!option) return;
        if (!control.matches('.component-checkbox,.bushing-qty-input,.bushing-code-select,.bushing-do-not-order')) { dirtyForms.add(form); return; }
        if (busy || !state) { notify('Wait for the current selection to finish saving.'); if (state) render(state, true); return; }
        const checkbox = [...form.querySelectorAll('.component-checkbox')].find(c => componentId(c) === id);
        if (!checkbox) return;
        const qty = Number(field(checkbox, 'qty')?.value || option.qty);
        const code = field(checkbox, 'codes_id')?.value;
        const desired = { selected: checkbox.checked, qty, do_not_order: field(checkbox, 'do_not_order')?.checked || false };
        if (code) desired.codes_id = Number(code);
        // If the group is already full, open the same quantity editor used by KIT.
        const otherQty = group.options.filter(o => o.id !== id && o.selected && !o.do_not_order).reduce((n, o) => n + o.qty, 0);
        if (desired.selected && !desired.do_not_order && otherQty + qty > group.capacity) {
            render(state, true); editGroup(id, true); return;
        }
        await save(id, { [id]: desired });
    });
    async function clickPrint(event) {
        const target = event.target.closest?.('[data-bushing-selection-id]');
        if (!target || (event.type === 'keydown' && !['Enter', ' '].includes(event.key))) return;
        event.preventDefault();
        if (!state) { await refresh(); if (!state) return; }
        const id = Number(target.dataset.bushingSelectionId), group = groupFor(id), option = group?.options.find(o => o.id === id);
        if (!option) return;
        if (option.locked || option.history) { notify(option.reason); return; }
        // A first decision, restoration, or split quantity needs an explicit group choice.
        if (!group.decided || option.crossed_out) editGroup(id, option.crossed_out);
        else await save(id, { [id]: { selected: false, qty: option.qty } });
    }
    document.addEventListener('click', clickPrint);
    document.addEventListener('keydown', clickPrint);
    document.addEventListener('submit', event => {
        if (event.target.matches('form#bushings-form') && (busy || staleForms.has(event.target))) {
            event.preventDefault(); event.stopImmediatePropagation();
            notify(busy ? 'Wait for selection to finish saving.' : 'Reopen the editor before saving: bushing data changed on another screen.');
        }
    }, true);
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    async function clear(form) {
        if (!state || busy) return;
        const ids = new Set([...form.querySelectorAll('.component-checkbox')].map(componentId));
        for (const group of state.groups) {
            const options = group.options.filter(o => ids.has(o.id) && o.selected && !o.history && !o.locked);
            if (!options.length) continue;
            const items = Object.fromEntries(options.map(o => [o.id, { selected: false, qty: o.qty }]));
            if (!await save(options[0].id, items)) break;
        }
    }
    window.BushingSelection = { refresh, clear, getState: () => state };
    refresh();
})();
