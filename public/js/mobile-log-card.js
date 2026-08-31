(function () {
    'use strict';

    const app = document.getElementById('mobileLogCardApp');
    if (!app) return;

    const content = document.getElementById('mobileLogCardContent');
    const status = document.getElementById('mobileLogCardStatus');
    const gallery = document.getElementById('mobileLogCardGallery');
    const photoCount = document.getElementById('mobileLogCardPhotoCount');
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

    async function request(url, method = 'GET', body = null) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };
        if (body && !(body instanceof FormData)) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, {
            method,
            headers,
            body: body instanceof FormData ? body : (body ? JSON.stringify(body) : null),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false || payload.success === false) {
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
        return `<div class="fw-semibold text-white">${escapeHtml(component.part_number || '—')}</div>
            <div class="mobile-log-card-meta">IPL ${escapeHtml(component.ipl_num || '—')} · ${escapeHtml(component.name || '—')}${unit}</div>`;
    }

    function variantControl(row, readOnly) {
        const component = row.component || {};
        const variants = Array.isArray(row.variants) ? row.variants.filter(item => item.allowed !== false) : [];
        if (variants.length < 2) return '';

        const options = variants.map(item => `<option value="${item.component_id}"
            ${Number(item.component_id) === Number(component.component_id) ? 'selected' : ''}
            data-part-number="${escapeHtml(item.part_number)}">${escapeHtml(item.part_number)} · ${escapeHtml(item.ipl_num)}</option>`).join('');
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

    function draftPhotoButton() {
        return `<button type="button" class="btn btn-sm btn-outline-info text-nowrap" data-draft-photo>
            <i class="bi bi-camera me-1"></i>Photo + read
        </button>`;
    }

    function renderAssyGroup(group, manual, disabled) {
        const choices = group.choices || [];
        const key = `assy-${manual.id}-${group.group_id}`;
        const radios = choices.map((choice, index) => `<label class="d-flex gap-2 align-items-start small mb-1">
            <input type="radio" class="form-check-input mt-1" name="${key}" value="${payloadAttr(choice)}" ${index === 0 ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
            <span><strong>${escapeHtml(choice.part_number || '—')}</strong> · IPL ${escapeHtml(choice.ipl_num || '—')} · ${escapeHtml(choice.name || '')}</span>
        </label>`).join('');
        return `<article class="mobile-log-card-row mobile-log-card-draft-choice p-2 mt-2" data-draft-kind="assy" data-group-key="${escapeHtml(group.group_key)}" data-manual-id="${manual.id}" data-group-id="${group.group_id}">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <span class="fw-semibold min-w-0">${escapeHtml(group.name || 'ASSY group')}</span>
                ${disabled ? '' : draftPhotoButton()}
            </div>
            <div class="mt-2">${radios}</div>
            <div class="mt-2"><input type="text" class="form-control form-control-sm draft-serial" maxlength="255" placeholder="Received S/N" ${disabled ? 'disabled' : ''}></div>
        </article>`;
    }

    function renderRegularGroup(group, manual, disabled) {
        const variants = group.variants || [];
        const options = variants.map(choice => `<option value="${payloadAttr(choice)}" data-part-number="${escapeHtml(choice.part_number)}">
            ${escapeHtml(choice.part_number || '—')} · IPL ${escapeHtml(choice.ipl_num || '—')} · ${escapeHtml(choice.name || '')}
        </option>`).join('');
        return `<article class="mobile-log-card-row mobile-log-card-draft-choice p-2 mt-2" data-draft-kind="regular" data-group-key="${escapeHtml(group.ipl_group)}" data-manual-id="${manual.id}">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <select class="form-select form-select-sm draft-component min-w-0 flex-fill" ${disabled ? 'disabled' : ''}>${options}</select>
                ${disabled ? '' : draftPhotoButton()}
            </div>
            <div class="mt-2"><input type="text" class="form-control form-control-sm draft-serial" maxlength="255" placeholder="Received S/N" ${disabled ? 'disabled' : ''}></div>
        </article>`;
    }

    function renderSeparate(choice, manual, disabled) {
        return `<article class="mobile-log-card-row mobile-log-card-draft-choice p-2 mt-2" data-draft-kind="separate" data-payload="${payloadAttr(choice)}" data-manual-id="${manual.id}">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <span class="min-w-0"><strong>${escapeHtml(choice.part_number || '—')}</strong> · IPL ${escapeHtml(choice.ipl_num || '—')} · ${escapeHtml(choice.name || '')}
                    <span class="d-block mobile-log-card-meta">Unit ${escapeHtml(choice.unit_index || '—')} of ${escapeHtml(choice.units_assy || '—')}</span>
                </span>
                ${disabled ? '' : draftPhotoButton()}
            </div>
            <div class="mt-2"><input type="text" class="form-control form-control-sm draft-serial" maxlength="255" placeholder="Received S/N" ${disabled ? 'disabled' : ''}></div>
        </article>`;
    }

    async function renderCreateCard() {
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
        else setStatus('Review each P/N and enter S/N, or photograph the nameplate.', 'info');

        const sections = templateResponses.map(template => {
            const manual = template.manual || { id: '', label: 'Manual' };
            const items = [];
            (template.assy_groups || []).forEach(group => items.push(renderAssyGroup(group, manual, readOnly)));
            (template.groups || []).forEach(group => items.push(renderRegularGroup(group, manual, readOnly)));
            (template.separate || []).forEach(choice => items.push(renderSeparate(choice, manual, readOnly)));
            return `<section class="mb-3">
                ${items.join('') || '<div class="small text-white-50 px-2 py-3">No Log Card components in this manual.</div>'}
            </section>`;
        }).join('');

        content.innerHTML = `${sections || '<div class="alert alert-secondary mt-2">No components are enabled for Log Card.</div>'}
            ${readOnly ? '' : '<button type="button" class="btn btn-success w-100 mb-3" data-create-log-card><i class="bi bi-card-checklist me-1"></i>Create Log Card</button>'}`;
    }

    function decodePayload(value) {
        return JSON.parse(decodeURIComponent(value));
    }

    function draftRowPayload(card) {
        const kind = card.dataset.draftKind;
        let choice;
        if (kind === 'regular') choice = decodePayload(card.querySelector('.draft-component').value);
        if (kind === 'separate') choice = decodePayload(card.dataset.payload);
        if (kind === 'assy') choice = decodePayload(card.querySelector('input[type="radio"]:checked').value);

        const row = {
            component_id: Number(choice.component_id),
            manual_id: Number(card.dataset.manualId),
            serial_number: card.querySelector('.draft-serial')?.value?.trim() || '',
        };
        if (kind === 'regular') row.ipl_group = card.dataset.groupKey || '';
        if (kind === 'separate') {
            row.unit_index = Number(choice.unit_index || 0) || null;
            row.units_assy = String(choice.units_assy || '');
        }
        if (kind === 'assy') {
            row.ipl_group = card.dataset.groupKey || '';
            row.manual_part_group_id = Number(card.dataset.groupId);
            row.manual_part_group_choice = choice.choice_kind || 'component';
            if (choice.manual_part_group_option_id) row.manual_part_group_option_id = Number(choice.manual_part_group_option_id);
        }
        if (choice.assemblies?.length) row.component_assembly_id = Number(choice.assemblies[0].id);
        return row;
    }

    async function createLogCard() {
        const rows = Array.from(content.querySelectorAll('[data-draft-kind]'))
            .map(draftRowPayload);
        if (!rows.length) {
            notify('No Log Card components are available.', 'warning');
            return;
        }

        if (!await confirmChange(`Create this Log Card with ${rows.length} row(s)?`, {
            title: 'Create Log Card',
            okText: 'Create',
        })) return;

        setBusy(true);
        try {
            await request(app.dataset.storeUrl, 'POST', { rows });
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
            updateGallery(response.photos || []);
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

    function updateGallery(photos) {
        photoCount.textContent = String(photos.length);
        gallery.innerHTML = photos.length
            ? photos.map(photo => `<a href="${escapeHtml(photo.big_url)}" data-fancybox="mobile-log-card-photos">
                <img src="${escapeHtml(photo.thumb_url)}" class="mobile-log-card-thumb" alt="${escapeHtml(photo.alt || 'Log Card photo')}">
            </a>`).join('')
            : '<span class="small text-white-50" data-empty-gallery>No Log Card photos yet.</span>';
        if (window.Fancybox) {
            window.Fancybox.unbind('[data-fancybox="mobile-log-card-photos"]');
            window.Fancybox.bind('[data-fancybox="mobile-log-card-photos"]', {});
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

        const response = await request(app.dataset.photoUrl, 'POST', form);
        updateGallery(response.photos || []);
        pendingRecognitionFile = null;
    }

    function selectedDraftChoice(card) {
        try {
            if (card.dataset.draftKind === 'regular') return decodePayload(card.querySelector('.draft-component').value);
            if (card.dataset.draftKind === 'separate') return decodePayload(card.dataset.payload);
            return decodePayload(card.querySelector('input[type="radio"]:checked').value);
        } catch (_) {
            return null;
        }
    }

    function applyPnToDraft(card, partNumber) {
        if (!partNumber) return true;
        const normalized = normalizePn(partNumber);
        if (card.dataset.draftKind === 'regular') {
            const select = card.querySelector('.draft-component');
            const match = Array.from(select.options).find(option => normalizePn(option.dataset.partNumber) === normalized);
            if (match) select.value = match.value;
            return Boolean(match);
        }
        if (card.dataset.draftKind === 'assy') {
            const match = Array.from(card.querySelectorAll('input[type="radio"]')).find(input => {
                const choice = decodePayload(input.value);
                return normalizePn(choice.part_number) === normalized;
            });
            if (match) match.checked = true;
            return Boolean(match);
        }
        const choice = selectedDraftChoice(card);
        return normalizePn(choice?.part_number) === normalized;
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
            return void startPhoto({ type: 'draft', element });
        }
    });

    content.addEventListener('change', event => {
        const control = event.target;
        if (control.matches('[data-row-variant]')) return void changeVariant(control);
        if (control.matches('[data-row-assembly]')) return void changeAssembly(control);
        if (control.matches('[data-row-field]')) return void saveRowField(control);
    });

    document.querySelector('[data-photo-only]')?.addEventListener('click', () => startPhoto(null, false));
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
