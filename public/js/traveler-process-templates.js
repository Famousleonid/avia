(() => {
    'use strict';
    if (window.aviaTravelerTemplatesLoaded) return;
    window.aviaTravelerTemplatesLoaded = true;
    const el = (tag, cls, text) => {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    function error(root, message) {
        const box = root.querySelector('[data-template-error]');
        box.textContent = message || '';
        box.classList.toggle('d-none', !message);
    }
    async function confirmAction(message) {
        if (typeof window.confirmDialog !== 'function') {
            window.showNotification?.('Confirmation dialog is unavailable. Please reload.', 'error');
            throw new Error('Confirmation dialog is unavailable. Please reload.');
        }
        return window.confirmDialog({title: 'Traveler template', message, okText: 'Continue'});
    }
    async function request(url, method = 'GET', data) {
        const response = await fetch(url, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
            ...(data ? {body: JSON.stringify(data)} : {})
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || `HTTP ${response.status}`);
        return result;
    }
    function pickerTemplate(root) {
        const id = Number(root.dataset.templateId);
        return JSON.parse(root.querySelector('[data-template-data]').textContent).find(item => item.id === id);
    }
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-template-apply]');
        if (!button || button.disabled) return;
        const root = button.closest('[data-traveler-picker]');
        if (button.dataset.blockedReason) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(button.dataset.blockedReason, 'warning');
            } else {
                error(root, button.dataset.blockedReason);
            }
            return;
        }
        const template = pickerTemplate(root);
        if (!template) return;
        button.disabled = true;
        error(root, '');
        try {
            if (!await confirmAction(`Add ${template.steps.length} steps from “${template.name}”? Existing processes will not be changed.`)) return;
            await request(root.dataset.url, 'POST', {template_id: template.id, revision: template.revision});
            document.dispatchEvent(new CustomEvent('traveler-template-applied', {detail: {tdrId: root.dataset.tdrId}}));
        } catch (err) { error(root, err.message); }
        finally { if (button.isConnected) button.disabled = false; }
    });

    async function initEditor(root) {
        const form = root.querySelector('[data-template-form]');
        const rows = form.querySelector('[data-template-steps]');
        const list = root.querySelector('[data-template-list]');
        const status = root.querySelector('[data-template-status]');
        const detailsSave = root.querySelector('[data-template-details-save]');
        let data, current = null, busy = false, headerBaseline = '';
        const header = () => ({ part_number: form.elements.part_number.value.trim(), name: form.elements.name.value.trim(), active: form.elements.active.checked });
        const values = row => ({ process_id: Number(row.querySelector('[data-process]').value) || null, description: row.querySelector('[data-description]').value, in_traveler: row.querySelector('[data-in-traveler]').checked });
        const rowDirty = row => !row.dataset.id || JSON.stringify(values(row)) !== row.dataset.baseline;
        const headerDirty = () => !form.hidden && JSON.stringify(header()) !== headerBaseline;
        const dirty = () => headerDirty() || [...rows.children].some(rowDirty);
        function refresh() {
            detailsSave.hidden = !headerDirty();
            [...rows.children].forEach((row, index) => {
                row.querySelector('[data-step-number]').textContent = 'Step ' + (index + 1);
                row.querySelector('[data-step-save]').hidden = !rowDirty(row);
                row.querySelector('[data-step-up]').disabled = busy || !row.dataset.id || !row.previousElementSibling?.dataset.id;
                row.querySelector('[data-step-down]').disabled = busy || !row.dataset.id || !row.nextElementSibling?.dataset.id;
            });
            status.textContent = busy ? 'Saving…' : dirty() ? 'Not saved' : current ? 'Saved · revision ' + current.revision : '';
        }
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        form.addEventListener('submit', event => event.preventDefault());
        // Native beforeunload dialogs cannot be styled. Guard app-controlled links instead.
        let leavePromptOpen = false;
        window.addEventListener('click', async event => {
            const link = event.target.closest('a[href]');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
                || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            const url = new URL(link.href, location.href);
            if (!['http:', 'https:'].includes(url.protocol)
                || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
            if (!busy && !dirty()) return;
            event.preventDefault(); event.stopImmediatePropagation();
            if (leavePromptOpen) return;
            if (busy) { window.showNotification?.('Please wait until saving finishes.', 'warning'); return; }
            leavePromptOpen = true;
            try {
                if (await resolveUnsaved()) { window.safeShowSpinner?.(); location.assign(url.href); }
            } catch (err) { error(root, err.message); }
            finally { leavePromptOpen = false; }
        }, true);
        function renderList() {
            list.replaceChildren(new Option(data.templates.length ? 'Select template…' : 'No templates.', ''));
            data.templates.forEach(t => list.append(new Option(t.part_number + ' — ' + t.name + (t.active ? '' : ' (inactive)'), t.id)));
            list.disabled = busy || !data.templates.length;
            list.value = current ? String(current.id) : '';
        }
        function accept(result) {
            current = result.template;
            data.templates = data.templates.filter(t => t.id !== current.id).concat(current);
            renderList();
        }
        async function run(action) {
            if (busy) return;
            busy = true; error(root, '');
            root.querySelectorAll('button, input, select, textarea').forEach(n => { n.disabled = true; });
            refresh();
            try { await action(); return true; }
            catch (err) { error(root, err.message); return false; }
            finally {
                busy = false;
                root.querySelectorAll('button, input, select, textarea').forEach(n => { n.disabled = false; });
                renderList(); refresh();
            }
        }
        function validHeader() {
            return [form.elements.part_number, form.elements.name].every(n => n.reportValidity());
        }
        async function saveHeader() {
            const result = await request(root.dataset.url + (current ? '/' + current.id + '/metadata' : '/metadata'),
                current ? 'PATCH' : 'POST', { ...header(), revision: current?.revision });
            accept(result); headerBaseline = JSON.stringify(header());
        }
        detailsSave.addEventListener('click', async () => {
            if (!validHeader()) return;
            await run(saveHeader);
        });
        async function saveRow(row) {
            if (!current) await saveHeader();
            const result = await request(root.dataset.url + '/' + current.id + '/step', 'POST',
                { action: 'save', id: row.dataset.id ? Number(row.dataset.id) : null, revision: current.revision, ...values(row) });
            accept(result); row.dataset.id = result.step_id;
            row.dataset.baseline = JSON.stringify(values(row));
        }
        async function resolveUnsaved() {
            if (!dirty()) return true;
            if (typeof window.confirmDialog !== 'function') throw new Error('Confirmation dialog is unavailable. Please reload.');
            const choice = await window.confirmDialog({title: 'Unsaved changes',
                message: 'This template has unsaved changes. What would you like to do?',
                cancelText: 'Return', okText: 'Save and leave', discardText: 'Leave without saving'});
            if (choice === 'discard') return true;
            if (choice !== true) return false;
            const changed = [...rows.children].filter(rowDirty);
            if (!validHeader() || changed.some(row => !row.querySelector('[data-process]').reportValidity())) return false;
            return await run(async () => {
                if (headerDirty() || !current) await saveHeader();
                for (const row of changed) await saveRow(row);
            });
        }
        function addStep(step = {}) {
            const row = el('div', 'traveler-template-step');
            if (step.id) row.dataset.id = step.id;
            const head = el('div', 'traveler-template-step-head');
            const number = el('span', 'text-info'); number.dataset.stepNumber = ''; head.append(number);
            const travelerLabel = el('label', 'traveler-step-membership small');
            const travelerCheck = el('input'); travelerCheck.type = 'checkbox'; travelerCheck.dataset.inTraveler = '';
            travelerCheck.checked = Boolean(step.traveler_group);
            travelerLabel.append(travelerCheck, document.createTextNode('Include in Traveler')); head.append(travelerLabel);
            const save = el('button', 'btn btn-sm btn-outline-warning');
            save.type = 'button'; save.dataset.stepSave = '';
            save.title = 'Edited — save this step'; save.setAttribute('aria-label', save.title);
            save.innerHTML = '<i class="bi bi-pencil-square" aria-hidden="true"></i>';
            head.append(save);
            [['↑', 'Move step up', 'up'], ['↓', 'Move step down', 'down'], ['×', 'Remove step', 'delete']].forEach(([label, title, action]) => {
                const button = el('button', 'btn btn-sm btn-outline-secondary', label);
                button.type = 'button'; button.title = title; button.setAttribute('aria-label', title);
                button.dataset['step' + action[0].toUpperCase() + action.slice(1)] = '';
                button.addEventListener('click', async () => {
                    if (busy) return;
                    await run(async () => {
                        if (action === 'delete' && !await confirmAction('Delete this step?')) return;
                        if (!row.dataset.id) { destroyPickers(row); row.remove(); return; }
                        const result = await request(root.dataset.url + '/' + current.id + '/step', 'POST',
                            { action, id: Number(row.dataset.id), revision: current.revision });
                        accept(result);
                        if (action === 'delete') { destroyPickers(row); row.remove(); }
                        if (action === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
                        if (action === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
                    });
                });
                head.append(button);
            });
            const processLabel = el('div', 'traveler-process-picker mb-2');
            const nameLabel = el('label', 'traveler-step-process-name');
            nameLabel.append(el('span', 'visually-hidden', 'Process name'));
            const names = el('select', 'form-select form-select-sm'); names.dataset.processName = '';
            names.title = 'Process name';
            names.append(new Option('Select process…', ''));
            [...new Set(data.processes.map(p => p.name))].sort().forEach(name => names.append(new Option(name, name)));
            nameLabel.append(names);
            head.insertBefore(nameLabel, travelerLabel);
            const specLabel = el('label', 'd-block small', 'Specification');
            const select = el('select', 'form-select form-select-sm mb-2'); select.required = true; select.dataset.process = '';
            names.value = data.processes.find(p => p.id === step.process_id)?.name || '';
            function populate(selected = '') {
                select.replaceChildren(new Option('Select specification…', ''));
                data.processes.filter(p => p.name === names.value)
                    .forEach(p => select.append(new Option(p.specification || '', p.id)));
                if (selected && !data.processes.some(p => String(p.id) === String(selected))) select.append(new Option('Unavailable process — select a replacement', selected));
                select.value = selected;
                window.jQuery?.(select).trigger('change.select2');
            }
            populate(step.process_id || '');
            names.addEventListener('change', () => { populate(); updateHint(); refresh(); });
            specLabel.append(select); processLabel.append(specLabel);
            const descriptionLabel = el('label', 'd-block small mb-0', 'Description');
            const description = el('textarea', 'form-control form-control-sm'); description.rows = 3;
            description.maxLength = 10000; description.dataset.description = ''; description.value = step.description || '';
            descriptionLabel.append(description);
            const hint = el('small', 'text-info d-block');
            const updateHint = () => {
                const process = data.processes.find(p => p.id === Number(select.value));
                const required = [process?.requires_fig ? 'FIG' : '', process?.requires_zone ? 'ZONE' : ''].filter(Boolean);
                hint.textContent = required.length ? 'Description requires: ' + required.join(', ') : '';
            };
            select.addEventListener('change', updateHint); updateHint(); descriptionLabel.append(hint);
            row.append(head, processLabel, descriptionLabel); rows.append(row);
            if (window.jQuery?.fn?.select2) {
                const $select = window.jQuery(select);
                $select.select2({
                    theme: 'bootstrap-5', width: '100%', minimumResultsForSearch: 0,
                    dropdownParent: window.jQuery(document.body),
                    matcher: (params, option) => {
                        const process = data.processes.find(p => String(p.id) === String(option.id));
                        const query = (params.term || '').trim().toLowerCase();
                        return !query || [option.text, process?.comment].filter(Boolean).join(' ').toLowerCase().includes(query) ? option : null;
                    },
                    templateResult: option => {
                        const process = data.processes.find(p => String(p.id) === String(option.id));
                        const result = el('div', 'traveler-specification-option', option.text);
                        if (process?.comment?.trim()) result.append(el('span', 'traveler-specification-comment', process.comment));
                        return window.jQuery(result);
                    }
                }).on('select2:select', () => select.dispatchEvent(new Event('change', {bubbles: true})))
                    .on('select2:open', () => {
                        const search = document.querySelector('.select2-container--open .select2-search__field');
                        if (search) { search.placeholder = 'Search specification or comment…'; search.focus(); }
                    });
                $select.data('select2').$dropdown.addClass('traveler-step-dropdown');
            }
            row.dataset.baseline = JSON.stringify(values(row));
            save.addEventListener('click', async () => {
                if (!select.reportValidity() || (!current && !validHeader())) return;
                await run(() => saveRow(row));
            });
            refresh();
        }
        function destroyPickers(container) {
            if (window.jQuery?.fn?.select2) window.jQuery(container).find('[data-process].select2-hidden-accessible').select2('destroy');
        }
        async function edit(template) {
            if (busy) return;
            try {
                if (!await resolveUnsaved()) return;
                if (template) template = data.templates.find(t => t.id === template.id) || template;
                current = template || null; form.hidden = false;
                form.elements.part_number.value = current?.part_number || '';
                form.elements.name.value = current?.name || '';
                form.elements.active.checked = current?.active ?? true;
                headerBaseline = JSON.stringify(header());
                destroyPickers(rows); rows.replaceChildren(); (current?.steps || [{}]).forEach(addStep);
                error(root, ''); refresh();
            } catch (err) { error(root, err.message); }
            renderList();
        }
        root.querySelector('[data-template-new]').addEventListener('click', () => { if (data) edit(null); });
        list.addEventListener('change', async () => {
            const template = data?.templates.find(t => String(t.id) === list.value);
            if (template) await edit(template);
            list.value = current ? String(current.id) : '';
        });
        root.querySelector('[data-template-step-add]').addEventListener('click', () => { if (!busy) addStep(); });
        try {
            data = await request(root.dataset.url);
            const parts = root.querySelector('datalist'), seen = new Set();
            data.parts.forEach(part => {
                if (seen.has(part.part_number)) return; seen.add(part.part_number);
                parts.append(new Option((part.ipl_num || '') + ' · ' + part.name, part.part_number));
            });
            renderList();
        } catch (err) { error(root, err.message); }
    }
    const init = () => document.querySelectorAll('[data-traveler-editor]').forEach(initEditor);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
