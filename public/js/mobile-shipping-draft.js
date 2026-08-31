(() => {
    'use strict';

    const form = document.getElementById('draftForm');
    const cameraButton = document.querySelector('[data-draft-nameplate-camera]');
    const photoInput = document.getElementById('draftNameplatePhoto');
    const photoState = document.getElementById('draftNameplatePhotoState');
    const preview = document.getElementById('draftNameplatePreview');
    const fileName = document.getElementById('draftNameplateFileName');
    const status = document.getElementById('draftNameplateStatus');
    const results = document.getElementById('draftNameplateResults');
    const partNumberSelect = document.getElementById('draftRecognizedPn');
    const serialNumberSelect = document.getElementById('draftRecognizedSn');
    const applyButton = document.querySelector('[data-apply-draft-nameplate]');
    const unitSelect = document.getElementById('unit_id');
    const pendingPartNumber = document.getElementById('unitPn');
    const serialNumberInput = document.getElementById('draftSerialNumber');

    if (!form || !cameraButton || !photoInput) return;

    let previewUrl = null;

    function notify(message, type = 'info') {
        if (typeof window.notify === 'function') {
            window.notify(message, type);
            return;
        }
        if (type === 'error' && typeof window.notifyError === 'function') window.notifyError(message);
    }

    async function confirmAction(options) {
        if (typeof window.confirmDialog !== 'function') {
            notify('Confirmation dialog is unavailable. Nothing was submitted.', 'error');
            return false;
        }

        return window.confirmDialog(options);
    }

    function normalizeIdentifier(value) {
        return String(value || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function fillCandidateSelect(select, values, emptyLabel) {
        if (!select) return;
        select.innerHTML = '';

        const candidates = Array.isArray(values) ? values.filter(value => String(value || '').trim() !== '') : [];
        if (!candidates.length) {
            select.add(new Option(emptyLabel, ''));
            select.disabled = true;
            return;
        }

        candidates.forEach(value => select.add(new Option(String(value), String(value))));
        select.disabled = false;
    }

    function resetRecognition() {
        results?.classList.add('d-none');
        fillCandidateSelect(partNumberSelect, [], 'P/N not found');
        fillCandidateSelect(serialNumberSelect, [], 'S/N not found');
    }

    function showSelectedPhoto(file) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        fileName.textContent = file.name || 'Camera photo';
        photoState.classList.remove('d-none');
        photoState.classList.add('d-flex');
    }

    function selectExistingUnit(partNumber) {
        const normalized = normalizeIdentifier(partNumber);
        if (!normalized || !unitSelect) return false;

        const option = Array.from(unitSelect.options).find(candidate =>
            normalizeIdentifier(candidate.dataset.partNumber) === normalized
        );
        if (!option) return false;

        unitSelect.value = option.value;
        if (window.jQuery && window.jQuery(unitSelect).data('select2')) {
            window.jQuery(unitSelect).val(option.value).trigger('change');
        } else {
            unitSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        return true;
    }

    cameraButton.addEventListener('click', () => {
        photoInput.click();
    });

    photoInput.addEventListener('change', async () => {
        const file = photoInput.files?.[0];
        if (!file) return;

        if (!await confirmAction({
            title: 'Read As Received photo',
            message: 'Keep this photo for the Draft and send it to Avi to read P/N and S/N?',
            okText: 'Continue',
        })) {
            photoInput.value = '';
            return;
        }

        showSelectedPhoto(file);
        resetRecognition();
        status.textContent = 'Reading the nameplate…';

        const payload = new FormData();
        payload.append('photo', file);

        try {
            if (typeof window.showLoadingSpinner === 'function') window.showLoadingSpinner();
            const response = await fetch(form.dataset.nameplateRecognitionUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Avi could not read the nameplate.');
            }

            const recognition = data.recognition || {};
            fillCandidateSelect(partNumberSelect, recognition.part_numbers, 'P/N not found');
            fillCandidateSelect(serialNumberSelect, recognition.serial_numbers, 'S/N not found');
            results?.classList.remove('d-none');
            status.textContent = `Avi finished reading · confidence: ${recognition.confidence || 'low'}`;
        } catch (error) {
            status.textContent = `${error.message} The photo is still staged for As received.`;
            notify(status.textContent, 'warning');
        } finally {
            if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
        }
    });

    applyButton?.addEventListener('click', () => {
        const partNumber = String(partNumberSelect?.value || '').trim();
        const serialNumber = String(serialNumberSelect?.value || '').trim();
        let pendingUnitRequired = false;

        if (serialNumber && serialNumberInput) serialNumberInput.value = serialNumber;

        if (partNumber && !selectExistingUnit(partNumber)) {
            pendingUnitRequired = true;
            if (pendingPartNumber) pendingPartNumber.value = partNumber;
            const modalElement = document.getElementById('addUnitModal');
            if (modalElement && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        }

        if (!partNumber && !serialNumber) {
            notify('Avi did not find a P/N or S/N to apply.', 'warning');
            return;
        }

        status.textContent = pendingUnitRequired
            ? 'S/N was filled. Confirm creation of the recognized P/N as a Pending Unit.'
            : 'Recognized numbers were applied to the Draft form. Review them before saving.';
        notify(status.textContent, 'success');
    });

    form.addEventListener('submit', async event => {
        if (form.dataset.confirmed === '1') return;

        event.preventDefault();
        const withPhoto = !!photoInput.files?.length;
        if (!await confirmAction({
            title: 'Create Draft Workorder',
            message: withPhoto
                ? 'Create this Draft and save the selected photo in As received?'
                : 'Create this Draft without an As received photo?',
            okText: 'Create Draft',
        })) return;

        form.dataset.confirmed = '1';
        form.requestSubmit();
    });

    window.addEventListener('beforeunload', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
})();
