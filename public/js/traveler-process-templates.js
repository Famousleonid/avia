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
        if (message && typeof window.showNotification === 'function') {
            // Server validation messages are text, while the shared toast accepts HTML.
            window.showNotification(el('span', '', message).innerHTML, 'error');
            if (box) { box.textContent = ''; box.classList.add('d-none'); }
            return;
        }
        if (!box) return;
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
        const multipart = data instanceof FormData;
        const response = await fetch(url, {
            method, credentials: 'same-origin',
            headers: {'Accept': 'application/json', ...(multipart ? {} : {'Content-Type': 'application/json'}),
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
            ...(data ? {body: multipart ? data : JSON.stringify(data)} : {})
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
            const result = await request(root.dataset.url, 'POST', {template_id: template.id, revision: template.revision});
            if (result.warnings?.length) {
                window.showNotification('Traveler added. ' + result.warnings.join(' '), 'warning');
            }
            document.dispatchEvent(new CustomEvent('traveler-template-applied', {detail: {tdrId: root.dataset.tdrId}}));
        } catch (err) { error(root, err.message); }
        finally { if (button.isConnected) button.disabled = false; }
    });

    async function initEditor(root) {
        const form = root.querySelector('[data-template-form]');
        const rows = form.querySelector('[data-template-steps]');
        const list = root.querySelector('[data-template-list]');
        const preview = root.querySelector('[data-template-preview]');
        const status = root.querySelector('[data-template-status]');
        const detailsSave = root.querySelector('[data-template-details-save]');
        const imageFiles = root.querySelector('[data-template-image-files]');
        const imageUpload = root.querySelector('[data-template-image-upload]');
        const imageList = root.querySelector('[data-template-images]');
        let data, current = null, busy = false, headerBaseline = '';
        let openSteps = new Set(), stepDomId = 0, editGeneration = 0;
        let stepStateQueue = Promise.resolve();
        const stepStateKey = id => 'openSteps:' + id;
        function rememberOpenSteps() {
            if (!current) return;
            const key = stepStateKey(current.id), value = [...openSteps];
            stepStateQueue = stepStateQueue.then(() => window.UserUiSettings.set('traveler.templates', key, value, {keepalive: true}))
                .catch(() => error(root, 'Could not save open Traveler steps.'));
        }
        function expandStep(row, expanded, remember = true) {
            row.querySelector('[data-step-body]').hidden = !expanded;
            row.querySelector('[data-step-toggle]').setAttribute('aria-expanded', String(expanded));
            row.querySelector('[data-step-chevron]').className = expanded ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
            row.classList.toggle('is-expanded', expanded);
            if (!expanded && window.jQuery?.fn?.select2) window.jQuery(row).find('[data-process].select2-hidden-accessible').select2('close');
            if (row.dataset.id) {
                if (expanded) openSteps.add(String(row.dataset.id)); else openSteps.delete(String(row.dataset.id));
                if (remember) rememberOpenSteps();
            }
        }
        function validateStep(row) {
            const select = row.querySelector('[data-process]');
            if (!select.checkValidity()) expandStep(row, true);
            return select.reportValidity();
        }

        const header = () => ({ part_number: form.elements.part_number.value.trim(), name: form.elements.name.value.trim(), active: form.elements.active.checked });
        const values = row => ({ process_id: Number(row.querySelector('[data-process]').value) || null, description: row.querySelector('[data-description]').value, in_traveler: row.querySelector('[data-in-traveler]').checked });
        const rowDirty = row => !row.dataset.id || JSON.stringify(values(row)) !== row.dataset.baseline;
        const headerDirty = () => !form.hidden && JSON.stringify(header()) !== headerBaseline;
        const dirty = () => headerDirty() || [...rows.children].some(rowDirty) || imageFiles.files.length > 0;
        function refresh() {
            if (preview) {
                const enabled = !!current && !busy;
                preview.classList.toggle('disabled', !enabled);
                preview.setAttribute('aria-disabled', String(!enabled));
                preview.tabIndex = enabled ? 0 : -1;
                if (enabled) preview.href = `${root.dataset.url}/${current.id}/preview`;
                else preview.removeAttribute('href');
            }
            detailsSave.hidden = !headerDirty();
            imageUpload.disabled = busy;
            imageUpload.setAttribute('aria-busy', String(busy));
            imageList.querySelectorAll('button').forEach(button => { button.disabled = busy; });
            [...rows.children].forEach((row, index) => {
                row.querySelector('[data-step-number]').textContent = 'Step ' + (index + 1);
                row.querySelector('[data-step-save]').hidden = !rowDirty(row);
                row.querySelector('[data-step-up]').disabled = busy || !row.dataset.id || !row.previousElementSibling?.dataset.id;
                row.querySelector('[data-step-down]').disabled = busy || !row.dataset.id || !row.nextElementSibling?.dataset.id;
            });
            status.textContent = busy ? 'Saving…' : dirty() ? 'Not saved' : current ? 'Saved · revision ' + current.revision : '';
        }
        form.addEventListener('input', refresh);
        preview?.addEventListener('click', event => {
            if (!current || busy || dirty()) {
                event.preventDefault();
                window.showNotification?.('Save the template changes before opening the preview.', 'warning');
            }
        });
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
        function templateListEntry(template) {
            const partNumber = String(template.part_number || '').trim();
            const name = String(template.name || '').replace(/\s*\[[^\]]*\]\s*$/, '').trim();
            const match = name.match(/\((\d+[A-Za-z]*-\d+[A-Za-z]*(?:\s*\/\s*\d+[A-Za-z]*)?)\)/);
            const numbers = partNumber.split(';').map(value => value.trim().toUpperCase());
            const candidates = data.parts.filter(part => numbers.includes(String(part.part_number).trim().toUpperCase()));
            const part = candidates.find(part => String(part.ipl_num).trim() === match?.[1]) || candidates[0];
            const ipl = match?.[1] || String(part?.ipl_num || '').trim();
            let description = (match ? name.replace(match[0], '').trim() : name);
            // Imported names may already contain the P/N before the description;
            // normalized names contain it at the end. Keep formatting idempotent.
            if (partNumber && description.toUpperCase().startsWith(partNumber.toUpperCase() + ' ')) {
                description = description.slice(partNumber.length).replace(/^[\s—–-]+/, '');
            }
            if (partNumber && description.toUpperCase().endsWith(' ' + partNumber.toUpperCase())) {
                description = description.slice(0, -partNumber.length).replace(/[\s—–-]+$/, '');
            }
            if (description.toUpperCase() === partNumber.toUpperCase()) description = '';
            description ||= part?.name || '';
            const formattedName = [ipl ? '(' + ipl + ')' : '', description, partNumber].filter(Boolean).join(' ');
            return { template, ipl, name: formattedName, label: formattedName + (template.active ? '' : ' (inactive)') };
        }
        function renderList() {
            list.replaceChildren(new Option(data.templates.length ? 'Select template…' : 'No templates.', ''));
            const compare = new Intl.Collator('en', { numeric: true, sensitivity: 'base' });
            data.templates.map(templateListEntry).sort((a, b) =>
                Number(!a.ipl) - Number(!b.ipl) || compare.compare(a.ipl, b.ipl)
                || compare.compare(a.label, b.label) || Number(a.template.id) - Number(b.template.id)
            ).forEach(entry => list.append(new Option(entry.label, entry.template.id)));
            list.disabled = busy || !data.templates.length;
            list.value = current ? String(current.id) : '';
        }
        function accept(result) {
            current = result.template;
            data.templates = data.templates.filter(t => t.id !== current.id).concat(current);
            renderList();
            renderImages();
        }
        function renderImages() {
            imageList.replaceChildren();
            const images = current?.images || [];
            if (!images.length) imageList.append(el('span', 'small text-muted', 'No images yet.'));
            images.forEach(image => {
                const card = el('div', 'traveler-template-image-card'); card.dataset.imageId = image.id;
                const sheet = el('div', 'traveler-attachment-sheet');
                const preview = el('a', 'traveler-attachment-preview');
                preview.href = image.url; preview.target = '_blank'; preview.rel = 'noopener'; preview.dataset.noSpinner = '';
                preview.title = 'Open ' + image.name; preview.setAttribute('aria-label', 'Open ' + image.name);
                const icon = el('i', 'bi bi-file-earmark-text-fill'); icon.setAttribute('aria-hidden', 'true');
                preview.append(icon);
                const remove = el('button', 'traveler-attachment-remove', '×');
                remove.type = 'button'; remove.title = 'Remove ' + image.name;
                remove.setAttribute('aria-label', 'Remove ' + image.name);
                remove.dataset.imageAction = 'delete';
                remove.addEventListener('click', async () => {
                    if (busy) return;
                    await run(async () => {
                        if (!await confirmAction('Remove this attachment? This also changes attachments in previously created Travelers.')) return;
                        accept(await request(`${root.dataset.url}/${current.id}/images/${image.id}`, 'POST', {revision: current.revision, action: 'delete'}));
                    });
                });
                sheet.append(preview, remove);
                card.append(sheet, el('div', 'small traveler-image-name', image.name));
                imageList.append(card);
            });
        }

        async function uploadImages() {
            if (!imageFiles.files.length) return;
            if (!current) await saveHeader();
            const payload = new FormData(); payload.append('revision', current.revision);
            [...imageFiles.files].forEach(file => payload.append('images[]', file));
            accept(await request(`${root.dataset.url}/${current.id}/images`, 'POST', payload));
            imageFiles.value = '';
        }
        async function uploadSelectedImages() {
            if (busy || !imageFiles.files.length || (!current && !validHeader())) return;
            await run(uploadImages);
        }
        imageUpload.addEventListener('click', () => { if (!busy) imageFiles.click(); });
        imageFiles.addEventListener('change', uploadSelectedImages);
        imageUpload.addEventListener('dragover', event => {
            event.preventDefault();
            if (!busy) imageUpload.classList.add('is-dragover');
        });
        imageUpload.addEventListener('dragleave', event => {
            if (!imageUpload.contains(event.relatedTarget)) imageUpload.classList.remove('is-dragover');
        });
        imageUpload.addEventListener('drop', async event => {
            event.preventDefault(); imageUpload.classList.remove('is-dragover');
            if (busy || !event.dataTransfer?.files.length) return;
            imageFiles.files = event.dataTransfer.files;
            await uploadSelectedImages();
        });
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
                { action: 'save', id: row.dataset.id ? Number(row.dataset.id) : null, step_number: [...rows.children].indexOf(row) + 1, revision: current.revision, ...values(row) });
            accept(result); row.dataset.id = result.step_id;
            expandStep(row, !row.querySelector('[data-step-body]').hidden);
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
            if (!validHeader() || changed.some(row => !validateStep(row))) return false;
            return await run(async () => {
                if (headerDirty() || !current) await saveHeader();
                for (const row of changed) await saveRow(row);
                await uploadImages();
            });
        }
        function addStep(step = {}) {
            const row = el('div', 'traveler-template-step');
            if (step.id) row.dataset.id = step.id;
            const head = el('div', 'traveler-template-step-head');
            const toggle = el('button', 'traveler-step-toggle text-info'); toggle.type = 'button'; toggle.dataset.stepToggle = '';
            const chevron = el('i', 'bi bi-chevron-right'); chevron.dataset.stepChevron = ''; chevron.setAttribute('aria-hidden', 'true');
            const number = el('span'); number.dataset.stepNumber = '';
            toggle.append(chevron, number); head.append(toggle);
            const body = el('div', 'traveler-step-body'); body.dataset.stepBody = ''; body.id = 'traveler-step-body-' + (++stepDomId);
            toggle.setAttribute('aria-controls', body.id);
            toggle.addEventListener('click', () => expandStep(row, body.hidden));
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
                        if (action === 'delete') { openSteps.delete(String(row.dataset.id)); rememberOpenSteps(); destroyPickers(row); row.remove(); }
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
                data.processes.filter(p => p.name === names.value && (!p.historical || String(p.id) === String(step.process_id)))
                    .forEach(p => select.append(new Option((p.historical ? '[Saved version] ' : '') + (p.specification || ''), p.id)));
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
            body.append(processLabel, descriptionLabel); row.append(head, body); rows.append(row);
            expandStep(row, openSteps.has(String(step.id)), false);
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
                if (!validateStep(row) || (!current && !validHeader())) return;
                await run(() => saveRow(row));
            });
            refresh();
        }
        function destroyPickers(container) {
            if (window.jQuery?.fn?.select2) window.jQuery(container).find('[data-process].select2-hidden-accessible').select2('destroy');
        }
        async function edit(template) {
            if (busy) return;
            const generation = ++editGeneration;
            try {
                if (!await resolveUnsaved()) return;
                if (template) template = data.templates.find(t => t.id === template.id) || template;
                await stepStateQueue;
                const saved = template ? await window.UserUiSettings.get('traveler.templates', stepStateKey(template.id), []) : [];
                if (generation !== editGeneration) return;
                openSteps = new Set(Array.isArray(saved) ? saved.map(String) : []);
                current = template || null; form.hidden = false;
                imageFiles.value = ''; renderImages();
                form.elements.part_number.value = current?.part_number || '';
                form.elements.name.value = current?.name || '';
                form.elements.active.checked = current?.active ?? true;
                headerBaseline = JSON.stringify(header());
                if (current) form.elements.name.value = templateListEntry(current).name;
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
        document.addEventListener('manual-process-version-saved', async () => {
            try {
                if (!data) return;
                const latest = await request(root.dataset.url);
                data.processes = latest.processes;
                if (current && !busy && !dirty()) await edit(current);
            } catch (err) { error(root, err.message); }
        });
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
