(function () {
    'use strict';

    const app = document.getElementById('mobileLogCardApp');
    if (!app) return;

    const content = document.getElementById('mobileLogCardContent');
    const status = document.getElementById('mobileLogCardStatus');
    const photoInput = document.getElementById('mobileLogCardPhotoInput');
    const recognitionPanel = document.getElementById('mobileLogCardRecognition');
    const recognitionMeta = document.getElementById('mobileLogCardRecognitionMeta');
    const recognitionPhoto = document.getElementById('mobileLogCardRecognitionPhoto');
    const partNumberWarning = document.getElementById('mobileLogCardPartNumberWarning');
    const recognizedPartNumber = document.getElementById('recognizedPartNumber');
    const recognizedSerialNumber = document.getElementById('recognizedSerialNumber');
    const applyRecognitionButton = document.querySelector('[data-apply-recognition]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let cardData = null;
    let recognitionTarget = null;
    let photoShouldRecognize = true;
    let localPreviewUrl = null;
    let recognitionExpectedPartNumber = '';
    let pendingRecognitionFile = null;
    const draftScope = 'mobile.log-card.draft';
    const draftKey = `wo:${app.dataset.workorderId}`;
    let draftReady = false;
    let draftTimer = null;
    let draftValue = null;
    let draftDirty = false;
    let draftSaving = null;
    const recognitionModal = recognitionPanel && window.bootstrap?.Modal
        ? new window.bootstrap.Modal(recognitionPanel, { backdrop: 'static', keyboard: false })
        : null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const normalizePn = (value) => String(value || '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '');

    function notify(message, type = 'info') {
        if (typeof window.notify === 'function') {
            window.notify(message, type);
        }
    }

    async function confirmChange(message, options = {}) {
        if (typeof window.confirmDialog !== 'function') {
            notify('Confirmation dialog is unavailable. Nothing was saved.', 'error');
            return false;
        }

        return window.confirmDialog({
            title: options.title || 'Confirm Log Card change',
            message,
            okText: options.okText || 'Save',
            cancelText: 'Cancel',
            danger: Boolean(options.danger),
        });
    }

    function setBusy(busy) {
        if (busy && typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
        if (!busy && typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
    }

    async function request(url, method = 'GET', body = null, keepalive = false) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };
        if (body && !(body instanceof FormData)) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, {
            method,
            keepalive,
            headers,
            body: body instanceof FormData ? body : (body ? JSON.stringify(body) : null),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || response.redirected || payload.ok === false || payload.success === false) {
            const validationMessage = Object.values(payload.errors || {}).flat().find(Boolean);
            throw new Error(validationMessage || payload.message || `Request failed (${response.status})`);
        }
        return payload;
    }

    function endpoint(template, cardId, row) {
        return template
            .replace('__CARD__', String(cardId))
            .replace('__ROW__', String(row));
    }

    function setStatus(message, kind = 'info') {
        status.innerHTML = message
            ? `<div class="alert alert-${kind} py-2 px-3 mb-2">${escapeHtml(message)}</div>`
            : '';
    }

    async function loadCard() {
        setBusy(true);
        try {
            const response = await request(app.dataset.dataUrl);
            cardData = response.data;
            if (cardData.exists) {
                renderSavedCard(cardData);
            } else {
                await renderCreateCard();
            }
        } catch (error) {
            content.innerHTML = `<div class="alert alert-danger mt-2">${escapeHtml(error.message)}</div>`;
        } finally {
            setBusy(false);
        }
    }

    function componentTitle(row) {
        const component = row.component || {};
        const unit = row.unit_index && row.units_assy
            ? ` · Unit ${escapeHtml(row.unit_index)} of ${escapeHtml(row.units_assy)}`
            : '';
        return `<div class="fw-semibold text-white">(${escapeHtml(component.ipl_num || '—')}) ${escapeHtml(component.part_number || '—')}</div>
            <div class="mobile-log-card-meta">${escapeHtml(component.name || '—')}${unit}</div>`;
    }

    function variantControl(row, readOnly) {
        const component = row.component || {};
        const variants = Array.isArray(row.variants) ? row.variants.filter(item => item.allowed !== false) : [];
        if (variants.length < 2) return '';

        const options = variants.map(item => `<option value="${item.component_id}"
            ${Number(item.component_id) === Number(component.component_id) ? 'selected' : ''}
            data-part-number="${escapeHtml(item.part_number)}">(${escapeHtml(item.ipl_num)}) ${escapeHtml(item.part_number)}</option>`).join('');
        return `<div class="mt-2">
            <label class="mobile-log-card-field-label">P/N</label>
            <select class="form-select form-select-sm" data-row-variant ${readOnly ? 'disabled' : ''}>${options}</select>
        </div>`;
    }

    function fieldHtml(row, field, label, readOnly) {
        return `<div class="col-12 col-sm-6">
            <label class="mobile-log-card-field-label">${escapeHtml(label)}</label>
            <input type="text" class="form-control form-control-sm" value="${escapeHtml(row[field] || '')}"
                   data-row-field="${field}" maxlength="255" ${readOnly ? 'readonly' : ''}>
        </div>`;
    }

    function renderSavedCard(data) {
        setStatus(data.read_only ? (data.read_only_message || 'Log Card is read only.') : '', data.read_only ? 'warning' : 'info');
        const rows = (data.rows || []).map(row => {
            if (row.kind === 'manual') {
                return '';
            }
            const readOnly = Boolean(data.read_only);
            return `<article class="mobile-log-card-row p-2 mt-2" data-saved-row="${row.index}" data-part-number="${escapeHtml(row.component?.part_number || '')}">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0">${componentTitle(row)}</div>
                    <button type="button" class="btn btn-sm btn-outline-info text-nowrap" data-photo-row="${row.index}" ${readOnly ? 'disabled' : ''}>
                        <i class="bi bi-camera me-1"></i>Photo + read
                    </button>
                </div>
                ${variantControl(row, readOnly)}
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" data-row-field="included" ${row.included ? 'checked' : ''} ${readOnly ? 'disabled' : ''}>
                    <label class="form-check-label small">Include in Log Card</label>
                </div>
                <div class="row g-2 mt-0">
                    ${fieldHtml(row, 'serial_number', 'Received S/N', readOnly)}
                </div>
            </article>`;
        }).join('');

        content.innerHTML = rows || '<div class="alert alert-secondary mt-2">This Log Card has no component rows.</div>';
    }

    function payloadAttr(value) {
        return encodeURIComponent(JSON.stringify(value));
    }

    function rememberDraft(card) {
        if (!draftReady) return;
        card.dataset.draftTouched = '1';
        draftValue = { version: 1, rows: Array.from(content.querySelectorAll('[data-draft-touched]')).map(row => ({
            key: row.dataset.groupKey,
            included: draftIncluded(row),
            componentId: selectedDraftChoice(row)?.component_id || null,
            serial: row.querySelector('.draft-serial').value,
            assemblies: Array.from(row.querySelectorAll('.mobile-group-assy-choice')).map(panel => ({
                componentId: Number(panel.dataset.componentId),
                value: panel.querySelector('select').value,
            })),
        })) };
        draftDirty = true;
        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => flushDraft().catch(draftSaveError), 250);
    }

    function draftSaveError() {
        notify('Could not save your Log Card draft. Keep this page open and try again.', 'error');
    }

    function flushDraft() {
        clearTimeout(draftTimer);
        if (draftSaving) return draftSaving;
        draftSaving = (async () => {
            while (draftDirty) {
                const value = draftValue;
                draftDirty = false;
                try {
                    await request(app.dataset.draftSettingsUrl, 'POST', { scope: draftScope, key: draftKey, value }, true);
                } catch (error) {
                    draftDirty = true;
                    throw error;
                }
            }
        })().finally(() => { draftSaving = null; });
        return draftSaving;
    }

    function restoreDraft(saved) {
        if (saved?.version !== 1 || !Array.isArray(saved.rows)) return;
        content.querySelectorAll('[data-draft-kind]').forEach(card => {
            const row = saved.rows.find(item => item?.key === card.dataset.groupKey);
            if (!row) return;
            const option = Array.from(card.querySelectorAll('.draft-component')).find(item =>
                Number(decodePayload(item.value).component_id) === Number(row.componentId));
            // A removed/unavailable variant must not silently select a different part.
            if (option && row.included === true) selectDraftOption(card, option);
            card.querySelector('.draft-serial').value = typeof row.serial === 'string' ? row.serial.slice(0, 255) : '';
            card.querySelectorAll('.mobile-group-assy-choice').forEach(panel => {
                const assy = (Array.isArray(row.assemblies) ? row.assemblies : []).find(item =>
                    Number(item?.componentId) === Number(panel.dataset.componentId));
                const select = panel.querySelector('select');
                if (assy && Array.from(select.options).some(item => item.value === String(assy.value))) select.value = String(assy.value);
            });
            card.dataset.draftTouched = '1';
            syncDraftCard(card);
        });
    }

    function renderDraftChoices(choices, key, manual, disabled, separate = false) {
        const multiple = choices.length > 1;
        const names = choices.map(choice => (choice.name || '').replace(/\s+(LH|RH)$/i, '').trim());
        const heading = names.length && names.every(name => name.toLowerCase() === names[0].toLowerCase())
            ? names[0] : 'Select part';
        const options = choices.map(choice => `<button type="button" class="mobile-draft-option draft-component"
            ${multiple ? 'role="radio" aria-checked="false"' : 'data-single aria-pressed="false"'}
            value="${payloadAttr(choice)}" ${disabled ? 'disabled' : ''}>
            <span class="mobile-draft-part"><span class="mobile-draft-identity"><span class="mobile-log-card-meta">(${escapeHtml(choice.ipl_num || '—')})</span> <strong>${escapeHtml(choice.part_number || '—')}</strong></span>
                <span class="mobile-draft-description">${escapeHtml(choice.name || '')}</span>
                ${separate ? `<small class="d-block mobile-log-card-meta">Unit ${choice.unit_index} of ${escapeHtml(choice.units_assy)}</small>` : ''}
            </span><span class="mobile-draft-mark" aria-hidden="true"></span>
        </button>`).join('');
        const assemblies = choices.map(choice => {
            const parents = choice.assembly_choices || [];
            const legacy = choice.assemblies || [];
            if (!parents.length && (!legacy.length || choice.choice_kind)) return '';
            const options = parents.length
                ? '<option value="">No ASSY</option>' + parents.map(assy => `<option value="${assy.option_id}" data-group-id="${assy.group_id}" ${parents.length === 1 ? 'selected' : ''}>(${escapeHtml(assy.ipl_num)}) ${escapeHtml(assy.part_number)}</option>`).join('')
                : legacy.map(assy => `<option value="${assy.id}">(${escapeHtml(assy.assy_ipl_num)}) ${escapeHtml(assy.assy_part_number)}</option>`).join('');
            return `<label class="mobile-group-assy-choice small w-100" data-component-id="${choice.component_id}" ${multiple ? 'hidden' : ''}>
                ASSY <select class="form-select form-select-sm ${parents.length ? 'draft-assy-choice' : 'draft-legacy-assy'}" ${disabled ? 'disabled' : ''}>${options}</select>
            </label>`;
        }).join('');
        return `<article class="mobile-log-card-row mobile-log-card-draft-choice" data-draft-kind="${separate ? 'separate' : 'choices'}"
            data-group-key="${escapeHtml(key)}" data-manual-id="${manual.id}">
            <div class="mobile-draft-top"><strong>${escapeHtml(heading)}</strong>
                <button type="button" class="draft-include" aria-pressed="false" aria-label="Include in Log Card" ${disabled ? 'disabled' : ''}>＋ Add</button>
            </div>
            <div class="mobile-draft-options" ${multiple ? 'role="radiogroup" aria-label="Select a part"' : ''}>${options}</div>
            <div class="mobile-draft-details" hidden>${assemblies}
                <label class="mobile-draft-serial-label">Received S/N
                    <input type="text" class="form-control draft-serial" maxlength="255" placeholder="Enter serial number" ${disabled ? 'disabled' : ''}>
                </label>
                <button type="button" class="mobile-draft-photo" data-draft-photo ${disabled ? 'disabled' : ''}><i class="bi bi-camera" aria-hidden="true"></i> Photo + read</button>
            </div>
        </article>`;
    }

    function draftIncluded(card) {
        return card.querySelector('.draft-include').getAttribute('aria-pressed') === 'true';
    }

    function setDraftIncluded(card, included) {
        card.querySelector('.draft-include').setAttribute('aria-pressed', String(included));
        if (!included) card.querySelectorAll('.draft-component[role="radio"]').forEach(option => option.setAttribute('aria-checked', 'false'));
        syncDraftCard(card);
        rememberDraft(card);
    }

    function selectDraftOption(card, option) {
        card.querySelectorAll('.draft-component[role="radio"]').forEach(item => item.setAttribute('aria-checked', String(item === option)));
        setDraftIncluded(card, true);
    }

    function syncDraftCard(card) {
        const choice = selectedDraftChoice(card);
        const included = draftIncluded(card);
        const include = card.querySelector('.draft-include');
        include.textContent = included ? 'Deselect' : '＋ Add';
        include.setAttribute('aria-label', included ? 'Deselect part' : 'Add part to Log Card');
        card.classList.toggle('is-included', included);
        card.querySelectorAll('.draft-component').forEach(option => {
            const selected = included && (option.hasAttribute('data-single') || option.getAttribute('aria-checked') === 'true');
            option.hidden = included && Boolean(choice) && !selected;
            if (option.hasAttribute('data-single')) option.setAttribute('aria-pressed', String(selected));
            option.querySelector('.mobile-draft-mark').textContent = selected ? '✓' : '';
        });
        card.querySelector('.mobile-draft-details').hidden = !included || !choice;
        card.querySelectorAll('.mobile-group-assy-choice').forEach(label => {
            label.hidden = !choice || Number(label.dataset.componentId) !== Number(choice.component_id);
        });
    }

    async function renderCreateCard() {
        draftReady = false;
        const firstResponse = await request(app.dataset.templateUrl);
        const first = firstResponse.data;
        const manuals = first.available_manuals || (first.manual ? [first.manual] : []);
        const templateResponses = await Promise.all(manuals.map(manual => {
            if (Number(manual.id) === Number(first.manual?.id)) return Promise.resolve(first);
            const separator = app.dataset.templateUrl.includes('?') ? '&' : '?';
            return request(`${app.dataset.templateUrl}${separator}manual_id=${encodeURIComponent(manual.id)}`).then(result => result.data);
        }));

        const readOnly = templateResponses.some(template => template.read_only);
        if (readOnly) setStatus(templateResponses.find(template => template.read_only)?.read_only_message || 'Log Card is read only.', 'warning');
        else setStatus('');

        const sections = templateResponses.map(template => {
            const manual = template.manual || { id: '', label: 'Manual' };
            const items = [];
            const rank = choices => Math.min(...choices.map(choice => template.order_ranks?.[choice.component_id ?? choice.id] ?? Number.MAX_SAFE_INTEGER));
            (template.assy_groups || []).forEach(group => items.push({html:renderDraftChoices(group.choices || [], `manual-${manual.id}-${group.group_key}`, manual, readOnly),rank:rank(group.choices || []),part:group.choices?.[0]?.part_number || ""}));
            (template.groups || []).forEach(group => items.push({html:renderDraftChoices(group.variants || [], `manual-${manual.id}-${group.ipl_group}`, manual, readOnly),rank:rank(group.variants || []),part:group.variants?.[0]?.part_number || ""}));
            (template.separate || []).forEach(choice => items.push({html:renderDraftChoices([choice], `manual-${manual.id}-${choice.component_id}-${choice.unit_index}`, manual, readOnly, true),rank:rank([choice]),part:choice.part_number || ""}));
            items.sort((a,b) => (a.rank-b.rank) || a.part.localeCompare(b.part, undefined, {numeric:true, sensitivity:"base"}));
            if (!items.length) return '';
            return `<section class="mb-3 mobile-draft-section">
                <div class="mobile-draft-heading">Manual: ${escapeHtml(manual.number || manual.label || "")}</div>
                ${items.map(item => item.html).join('')}
            </section>`;
        }).join('');

        const settings = await request(`${app.dataset.draftSettingsUrl}?scope=${encodeURIComponent(draftScope)}`);
        content.innerHTML = `${sections}
            ${readOnly || !sections ? '' : '<button type="button" class="btn btn-success w-100 mb-3" data-create-log-card><i class="bi bi-card-checklist me-1"></i>Create Log Card</button>'}`;
        restoreDraft(settings.settings?.[draftKey]);
        draftReady = !readOnly;
    }

    function decodePayload(value) {
        return JSON.parse(decodeURIComponent(value));
    }

    function draftRowPayload(card) {
        const choice = selectedDraftChoice(card);
        if (!choice) throw new Error('Select a part variant for each included row.');
        const row = {
            component_id: Number(choice.component_id), manual_id: Number(card.dataset.manualId), included: '1',
            serial_number: card.querySelector('.draft-serial')?.value?.trim() || '', ipl_group: card.dataset.groupKey || '',
        };
        if (card.dataset.draftKind === 'separate') {
            row.unit_index = Number(choice.unit_index || 0) || null;
            row.units_assy = String(choice.units_assy || '');
        }
        if (choice.choice_kind) {
            row.manual_part_group_id = Number(choice.manual_part_group_id) || null;
            row.manual_part_group_choice = choice.choice_kind;
            if (choice.manual_part_group_option_id) row.manual_part_group_option_id = Number(choice.manual_part_group_option_id);
        }
        const panel = Array.from(card.querySelectorAll('.mobile-group-assy-choice'))
            .find(label => Number(label.dataset.componentId) === Number(choice.component_id));
        const selector = panel?.querySelector('.draft-assy-choice');
        if (selector) {
            row.manual_part_group_choice = 'component';
            row.manual_part_group_id = selector.value ? Number(selector.selectedOptions[0]?.dataset.groupId) : null;
            row.assy_option_id = selector.value ? Number(selector.value) : null;
            row.assy_selection_explicit = '1';
        } else if (panel?.querySelector('.draft-legacy-assy')) {
            row.component_assembly_id = Number(panel.querySelector('.draft-legacy-assy').value);
        }
        return row;
    }

    async function createLogCard() {
        const included = Array.from(content.querySelectorAll('[data-draft-kind]')).filter(draftIncluded);
        const missing = included.find(card => !selectedDraftChoice(card));
        if (missing) {
            notify('Select a part variant for each included row.', 'warning');
            missing.querySelector('.draft-component')?.focus();
            return;
        }
        const rows = included.map(draftRowPayload);
        if (!rows.length) {
            notify('Select the parts to include in this Log Card.', 'warning');
            return;
        }

        if (!await confirmChange(`Create this Log Card with ${rows.length} row(s)?`, {
            title: 'Create Log Card',
            okText: 'Create',
        })) return;

        setBusy(true);
        try {
            await request(app.dataset.storeUrl, 'POST', { rows });
            draftReady = false;
            draftValue = null;
            draftDirty = true;
            await flushDraft().catch(() => notify('Log Card is saved, but its temporary draft could not be cleared.', 'warning'));
            notify('Log Card created.', 'success');
            await loadCard();
        } catch (error) {
            notify(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    async function saveRowField(control) {
        const rowCard = control.closest('[data-saved-row]');
        if (!rowCard || !cardData?.log_card_id) return;
        const row = Number(rowCard.dataset.savedRow);
        const field = control.dataset.rowField;
        const value = control.type === 'checkbox' ? control.checked : control.value;
        if (!await confirmChange(`Save the ${field.replaceAll('_', ' ')} change for this Log Card row?`)) {
            await loadCard();
            return;
        }
        try {
            await request(endpoint(app.dataset.rowUrlTemplate, cardData.log_card_id, row), 'PATCH', { field, value });
            control.classList.add('border-success');
            window.setTimeout(() => control.classList.remove('border-success'), 900);
        } catch (error) {
            notify(error.message, 'error');
            await loadCard();
        }
    }

    async function changeVariant(control) {
        const rowCard = control.closest('[data-saved-row]');
        const row = Number(rowCard.dataset.savedRow);
        const selectedText = control.options[control.selectedIndex]?.textContent?.trim() || control.value;
        if (!await confirmChange(`Change this Log Card row to ${selectedText}?`)) {
            await loadCard();
            return;
        }
        setBusy(true);
        try {
            await request(endpoint(app.dataset.variantUrlTemplate, cardData.log_card_id, row), 'PATCH', {
                component_id: Number(control.value),
            });
            await loadCard();
        } catch (error) {
            notify(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    async function changeAssembly(control) {
        const rowCard = control.closest('[data-saved-row]');
        const row = Number(rowCard.dataset.savedRow);
        const selectedText = control.options[control.selectedIndex]?.textContent?.trim() || control.value;
        if (!await confirmChange(`Change the assembly for this Log Card row to ${selectedText}?`)) {
            await loadCard();
            return;
        }
        setBusy(true);
        try {
            await request(endpoint(app.dataset.assemblyUrlTemplate, cardData.log_card_id, row), 'PATCH', {
                component_assembly_id: Number(control.value),
            });
            await loadCard();
        } catch (error) {
            notify(error.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    function startPhoto(target, shouldRecognize = true) {
        recognitionTarget = target;
        photoShouldRecognize = shouldRecognize;
        document.querySelectorAll('.is-photo-target').forEach(element => element.classList.remove('is-photo-target'));
        target?.element?.classList.add('is-photo-target');
        photoInput.value = '';
        photoInput.click();
    }

    async function uploadPhoto(file) {
        if (photoShouldRecognize) {
            pendingRecognitionFile = file;
            openRecognitionReview(file);
        } else {
            pendingRecognitionFile = null;
        }

        const form = new FormData();
        form.append('photo', file);
        form.append('recognize', photoShouldRecognize ? '1' : '0');
        appendPhotoContext(form);

        setBusy(true);
        try {
            const response = await request(app.dataset.photoUrl, 'POST', form);
            if (photoShouldRecognize) {
                showRecognition(response.recognition || {}, response.photo || null, response.recognition_error || '');
                notify(response.message || 'Photo read by Avi. Confirm to save it.', response.recognition_error ? 'warning' : 'success');
            } else {
                hideRecognition();
                notify(response.message || 'Photo saved.', 'success');
            }
        } catch (error) {
            notify(error.message, 'error');
            if (photoShouldRecognize) showRecognitionUploadError(error.message);
        } finally {
            setBusy(false);
        }
    }

    function releaseLocalPreview() {
        if (!localPreviewUrl) return;
        URL.revokeObjectURL(localPreviewUrl);
        localPreviewUrl = null;
    }

    function expectedPartNumberForTarget() {
        if (recognitionTarget?.type === 'saved') {
            return cardData?.rows?.find(item => Number(item.index) === Number(recognitionTarget.row))?.component?.part_number || '';
        }
        if (recognitionTarget?.type === 'draft') {
            return selectedDraftChoice(recognitionTarget.element)?.part_number || '';
        }
        return '';
    }

    function openRecognitionReview(file) {
        releaseLocalPreview();
        localPreviewUrl = URL.createObjectURL(file);
        recognitionPhoto.src = localPreviewUrl;
        recognitionExpectedPartNumber = expectedPartNumberForTarget();
        recognizedPartNumber.value = recognitionExpectedPartNumber;
        recognizedSerialNumber.value = '';
        recognizedPartNumber.disabled = true;
        recognizedSerialNumber.disabled = true;
        applyRecognitionButton.disabled = true;
        recognitionMeta.textContent = 'Reading the numbers… The photo will be saved after Confirm.';
        recognitionPanel.setAttribute('aria-busy', 'true');
        updatePartNumberWarning();
        recognitionModal?.show();
    }

    function showRecognition(result, photo, errorMessage = '') {
        if (photo?.big_url) {
            recognitionPhoto.src = photo.big_url;
            releaseLocalPreview();
        }
        recognizedPartNumber.value = result.part_numbers?.[0] || expectedPartNumberForTarget();
        recognizedSerialNumber.value = result.serial_numbers?.[0] || '';
        recognizedPartNumber.disabled = false;
        recognizedSerialNumber.disabled = false;
        applyRecognitionButton.disabled = false;
        recognitionMeta.textContent = errorMessage
            ? 'Avi could not read the numbers. Enter them manually or retake the photo.'
            : `Avi finished reading · confidence: ${result.confidence || 'low'}. Check and edit if needed.`;
        recognitionPanel.setAttribute('aria-busy', 'false');
        updatePartNumberWarning();
        recognitionModal?.show();
        recognizedSerialNumber.focus({ preventScroll: true });
    }

    function showRecognitionUploadError(message) {
        recognitionMeta.textContent = `${message} Retake the photo.`;
        recognitionPanel.setAttribute('aria-busy', 'false');
        recognizedPartNumber.disabled = true;
        recognizedSerialNumber.disabled = true;
        applyRecognitionButton.disabled = true;
    }

    function hideRecognition(clearTarget = true) {
        recognitionModal?.hide();
        if (clearTarget) {
            recognitionTarget = null;
            pendingRecognitionFile = null;
        }
        document.querySelectorAll('.is-photo-target').forEach(element => element.classList.remove('is-photo-target'));
    }

    function updatePartNumberWarning() {
        const photographedPartNumber = recognizedPartNumber.value.trim();
        const differs = recognitionExpectedPartNumber
            && photographedPartNumber
            && normalizePn(recognitionExpectedPartNumber) !== normalizePn(photographedPartNumber);

        partNumberWarning.classList.toggle('d-none', !differs);
        partNumberWarning.textContent = differs
            ? `Different P/N on photo: ${photographedPartNumber}. Selected detail: ${recognitionExpectedPartNumber}. Values can still be confirmed.`
            : '';
    }

    function swapRecognizedNumbers() {
        if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
        const partNumber = recognizedPartNumber.value;
        recognizedPartNumber.value = recognizedSerialNumber.value;
        recognizedSerialNumber.value = partNumber;
        updatePartNumberWarning();
    }

    function retakeRecognitionPhoto() {
        const target = recognitionTarget;
        pendingRecognitionFile = null;
        hideRecognition(false);
        startPhoto(target, true);
    }

    function appendPhotoContext(form) {
        if (recognitionTarget?.type === 'saved') {
            const row = cardData.rows.find(item => Number(item.index) === Number(recognitionTarget.row));
            form.append('row_index', String(recognitionTarget.row));
            form.append('expected_part_number', row?.component?.part_number || '');
            form.append('expected_assy_part_number', row?.assy_part_number || '');
        } else if (recognitionTarget?.type === 'draft') {
            const selected = selectedDraftChoice(recognitionTarget.element);
            form.append('expected_part_number', selected?.part_number || '');
        }
    }

    async function saveConfirmedRecognitionPhoto() {
        if (!pendingRecognitionFile) return;

        const form = new FormData();
        form.append('photo', pendingRecognitionFile);
        form.append('recognize', '0');
        appendPhotoContext(form);

        await request(app.dataset.photoUrl, 'POST', form);
        pendingRecognitionFile = null;
    }

    function selectedDraftChoice(card) {
        const input = card?.querySelector('.draft-component[aria-checked="true"], .draft-component[data-single]');
        return input ? decodePayload(input.value) : null;
    }

    function applyPnToDraft(card, partNumber) {
        if (!partNumber) return true;
        const normalized = normalizePn(partNumber);
        const match = Array.from(card.querySelectorAll('.draft-component')).find(input =>
            normalizePn(decodePayload(input.value).part_number) === normalized);
        if (!match) return false;
        selectDraftOption(card, match);
        return true;
    }

    async function applyRecognition() {
        if (!recognitionTarget) {
            notify('Take the photo from a specific Log Card row to apply the result.', 'warning');
            return;
        }
        const partNumber = recognizedPartNumber.value;
        const serialNumber = recognizedSerialNumber.value;
        if (!partNumber && !serialNumber) {
            notify('Avi did not find a P/N or S/N to apply.', 'warning');
            return;
        }

        setBusy(true);
        applyRecognitionButton.disabled = true;
        try {
            await saveConfirmedRecognitionPhoto();

            if (recognitionTarget.type === 'draft') {
                const card = recognitionTarget.element;
                const matched = applyPnToDraft(card, partNumber);
                if (serialNumber) card.querySelector('.draft-serial').value = serialNumber;
                rememberDraft(card);
                notify(matched || !partNumber
                    ? 'Photo saved and values copied into the selected draft row. Review them, then create the Log Card.'
                    : 'Photo and S/N saved. Recognized P/N is not one of the allowed choices for this row.', matched || !partNumber ? 'success' : 'warning');
                hideRecognition();
                return;
            }

            const rowIndex = Number(recognitionTarget.row);
            const row = cardData.rows.find(item => Number(item.index) === rowIndex);
            let pnMatched = !partNumber || normalizePn(row?.component?.part_number) === normalizePn(partNumber);

            if (partNumber && !pnMatched) {
                const variant = (row?.variants || []).find(item => item.allowed !== false && normalizePn(item.part_number) === normalizePn(partNumber));
                if (variant) {
                    await request(endpoint(app.dataset.variantUrlTemplate, cardData.log_card_id, rowIndex), 'PATCH', {
                        component_id: Number(variant.component_id),
                    });
                    pnMatched = true;
                }
            }
            if (serialNumber) {
                await request(endpoint(app.dataset.rowUrlTemplate, cardData.log_card_id, rowIndex), 'PATCH', {
                    field: 'serial_number',
                    value: serialNumber,
                });
            }
            notify(pnMatched
                ? 'Photo saved and recognized values applied. Please verify them against the photo.'
                : 'Photo and S/N saved. Recognized P/N is not an allowed variant for this Log Card row.', pnMatched ? 'success' : 'warning');
            hideRecognition();
            await loadCard();
        } catch (error) {
            notify(error.message, 'error');
        } finally {
            setBusy(false);
            if (recognitionPanel.classList.contains('show')) applyRecognitionButton.disabled = false;
        }
    }

    content.addEventListener('click', event => {
        const include = event.target.closest('.draft-include');
        if (include && !include.disabled) {
            const card = include.closest('[data-draft-kind]');
            if (!draftIncluded(card) && !selectedDraftChoice(card)) {
                notify('Select a part variant.', 'warning');
                card.querySelector('.draft-component')?.focus();
                return;
            }
            setDraftIncluded(card, !draftIncluded(card));
            return;
        }
        const option = event.target.closest('.draft-component');
        if (option && !option.disabled) {
            const card = option.closest('[data-draft-kind]');
            if (option.hasAttribute('data-single')) setDraftIncluded(card, !draftIncluded(card));
            else selectDraftOption(card, option);
            return;
        }
        const createButton = event.target.closest('[data-create-log-card]');
        if (createButton) return void createLogCard();

        const photoRow = event.target.closest('[data-photo-row]');
        if (photoRow) {
            const element = photoRow.closest('[data-saved-row]');
            return void startPhoto({ type: 'saved', row: Number(photoRow.dataset.photoRow), element });
        }

        const draftPhoto = event.target.closest('[data-draft-photo]');
        if (draftPhoto) {
            const element = draftPhoto.closest('[data-draft-kind]');
            if (!selectedDraftChoice(element)) {
                notify('Select a part variant before taking a photo.', 'warning');
                element.querySelector('.draft-component')?.focus();
                return;
            }
            setDraftIncluded(element, true);
            return void startPhoto({ type: 'draft', element });
        }
    });

    content.addEventListener('change', event => {
        const control = event.target;
        if (control.matches('.draft-assy-choice, .draft-legacy-assy')) rememberDraft(control.closest('[data-draft-kind]'));
        if (control.matches('[data-row-variant]')) return void changeVariant(control);
        if (control.matches('[data-row-assembly]')) return void changeAssembly(control);
        if (control.matches('[data-row-field]')) return void saveRowField(control);
    });

    content.addEventListener('input', event => {
        if (event.target.matches('.draft-serial')) rememberDraft(event.target.closest('[data-draft-kind]'));
    });

    // Finish the latest draft before a mobile navigation link replaces this document.
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
            || link.target === '_blank' || link.hasAttribute('download') || (!draftDirty && !draftSaving)) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search)) return;
        event.preventDefault();
        try {
            await flushDraft();
            location.assign(url.href);
        } catch (_) { draftSaveError(); }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && draftDirty) flushDraft().catch(draftSaveError);
    });
    window.addEventListener('pagehide', () => { if (draftDirty) flushDraft().catch(draftSaveError); });

    document.querySelector('[data-apply-recognition]')?.addEventListener('click', applyRecognition);
    document.querySelector('[data-retake-recognition]')?.addEventListener('click', retakeRecognitionPhoto);
    document.querySelector('[data-swap-recognized-numbers]')?.addEventListener('click', swapRecognizedNumbers);
    recognizedPartNumber.addEventListener('input', updatePartNumberWarning);
    recognitionPanel?.addEventListener('hidden.bs.modal', releaseLocalPreview);
    photoInput.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (file) uploadPhoto(file);
    });

    if (window.Fancybox) window.Fancybox.bind('[data-fancybox="mobile-log-card-photos"]', {});
    loadCard();
})();
