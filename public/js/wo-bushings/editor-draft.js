(function () {
    'use strict';
    if (window.BushingEditorDraft) { window.BushingEditorDraft.initAll(); return; }
    const selector = 'form#bushings-form';
    const drafts = new WeakMap();
    let confirming = false, replaying = false;
    const notify = message => window.showNotification?.(message, 'warning');
    const controls = form => [...form.elements].filter(el => el.name && !['_token', '_method', 'draft_token'].includes(el.name) && !['submit', 'button'].includes(el.type));
    const value = el => ['checkbox', 'radio'].includes(el.type) ? el.checked : el.multiple ? [...el.selectedOptions].map(o => o.value) : el.value;
    const snapshot = form => controls(form).map(el => [el, value(el)]);
    const signature = entries => JSON.stringify(entries.map(([el, v]) => [el.name, el.type, v]));
    function init(form) {
        if (form && !drafts.has(form)) drafts.set(form, { baseline: snapshot(form), saving: false });
    }
    function initAll() { document.querySelectorAll(selector).forEach(init); }
    function isDirty(form) {
        const draft = drafts.get(form);
        return !!draft && signature(snapshot(form)) !== signature(draft.baseline);
    }
    function isSaving(form) { return !!drafts.get(form)?.saving; }
    function markSaved(form) {
        if (!form) return;
        drafts.get(form)?.finishBusy?.();
        form.inert = false;
        drafts.set(form, { baseline: snapshot(form), saving: false });
    }
    function setSaving(form, saving) {
        init(form);
        const draft = drafts.get(form);
        if (saving && !draft.saving) draft.finishBusy = window.beginAppBusy?.();
        if (!saving) { draft.finishBusy?.(); draft.finishBusy = null; }
        draft.saving = saving;
        form.inert = saving;
    }
    function restore(form) {
        const draft = drafts.get(form);
        if (!draft) return;
        draft.baseline.forEach(([el, v]) => {
            if (['checkbox', 'radio'].includes(el.type)) el.checked = v;
            else if (el.multiple) [...el.options].forEach(o => { o.selected = v.includes(o.value); });
            else el.value = v;
        });
        // Rebuild enabled states, quantity limits and code badges without saving.
        form.querySelectorAll('.component-checkbox').forEach(el => el.dispatchEvent(new Event('change', { bubbles: true })));
        form.querySelectorAll('select').forEach(el => window.jQuery?.(el).trigger('change.select2'));
        markSaved(form);
        window.BushingSelection?.resetEditor(form);
    }
    const affected = root => [...root.querySelectorAll(selector)].filter(form => isDirty(form) || isSaving(form));
    async function leave(forms, action) {
        if (confirming) return;
        if (forms.some(isSaving)) { notify('Please wait until bushing data finishes saving.'); return; }
        if (typeof window.confirmDialog !== 'function') {
            notify('Confirmation dialog is unavailable. Please save your changes before leaving.'); return;
        }
        confirming = true;
        try {
            const pending = window.confirmDialog({
                title: 'Unsaved bushing changes',
                message: 'Selection, quantities, codes and processes have not been saved. Discard these changes?',
                okText: 'Discard changes', cancelText: 'Keep editing', danger: true
            });
            if (await pending) {
                forms.forEach(restore);
                replaying = true;
                try { action(); } finally { replaying = false; }
            } else {
                const editor = forms[0]?.closest('.modal.show');
                if (editor) {
                    document.body.classList.add('modal-open');
                    bootstrap.Modal.getInstance(editor)?._focustrap?.activate();
                }
            }
        } finally { confirming = false; }
    }
    document.addEventListener('hide.bs.modal', event => {
        if (replaying || !(event.target instanceof Element)) return;
        const forms = affected(event.target);
        if (!forms.length) return;
        event.preventDefault();
        leave(forms, () => bootstrap.Modal.getInstance(event.target)?.hide());
    });
    document.addEventListener('hide.bs.tab', event => {
        if (replaying) return;
        const target = event.target.getAttribute('data-bs-target') || event.target.getAttribute('href');
        const pane = target?.startsWith('#') ? document.getElementById(target.slice(1)) : null;
        const forms = pane ? affected(pane) : [];
        if (!forms.length || !event.relatedTarget) return;
        event.preventDefault();
        leave(forms, () => bootstrap.Tab.getOrCreateInstance(event.relatedTarget).show());
    });
    document.addEventListener('click', event => {
        if (replaying || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self') || link.dataset.bsToggle) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || /^javascript:/i.test(href)) return;
        const forms = affected(document);
        if (!forms.length) return;
        event.preventDefault(); event.stopImmediatePropagation();
        leave(forms, () => link.click());
    }, true);
    window.BushingEditorDraft = { init, initAll, isDirty, isSaving, markSaved, setSaving };
    initAll();
})();
