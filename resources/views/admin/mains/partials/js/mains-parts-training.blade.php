<script>

// mains-parts-training.js
// Всё про Parts / PO / TDRS + Training
// (это твой IIFE + функции createTrainings/updateTrainingToToday)

(function () {
    'use strict';

    // Конфигурация
    const CONFIG = {
        debounceDelay: 500,
        modalOpenDelay: 300,
        qtyColumnIndex: 4
    };

    // ===== 1. CSRF токен =====
    const TokenUtils = {
        getCsrfToken: function () {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag
                ? metaTag.getAttribute('content')
                : '{{ csrf_token() }}';
        }
    };

    // ===== 2. DOM утилиты =====
    const DomUtils = {
        getModal: function (workorderNumber) {
            return document.getElementById('partsModal' + workorderNumber);
        },

        getReceivedCounter: function (workorderNumber) {
            return document.getElementById('receivedQty' + workorderNumber);
        },

        getPoNoInput: function (selectElement) {
            return selectElement.closest('.po-no-container').querySelector('.po-no-input');
        },

        getTableRows: function (modal) {
            return modal ? modal.querySelectorAll('tbody tr') : [];
        },

        getQtyFromRow: function (row) {
            const qtyCell = row.querySelector('td:nth-child(' + CONFIG.qtyColumnIndex + ')');
            return qtyCell ? parseInt(qtyCell.textContent.trim()) || 0 : 0;
        }
    };

    // ===== 3. API частей =====
    const PartsApi = {
        saveField: function (tdrsId, field, value, workorderNumber) {
            const csrfToken = TokenUtils.getCsrfToken();
            const url = '{{ route("tdrs.updatePartField", ":id") }}'.replace(':id', tdrsId);

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    field: field,
                    value: value
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (field === 'received') {
                            PartsCounter.updateReceivedCount(workorderNumber);
                        }
                        return data;
                    }
                    throw new Error('Save failed');
                })
                .catch(error => {
                    console.error('Error saving field:', error);
                    throw error;
                });
        }
    };

    // ===== 4. API transfers =====
    const TransferApi = {
        createTransfer: function (tdrsId, workorderNumber, targetWorkorderNumber) {
            const csrfToken = TokenUtils.getCsrfToken();
            const url = '{{ route("transfers.create", ":id") }}'.replace(':id', tdrsId);

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    workorder_number: workorderNumber,
                    target_workorder_number: targetWorkorderNumber
                })
            }).then(response => response.json());
        },

        deleteByTdr: function (tdrsId) {
            const csrfToken = TokenUtils.getCsrfToken();
            const url = '{{ route("transfers.deleteByTdr", ":id") }}'.replace(':id', tdrsId);

            return fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            }).then(response => response.json());
        }
    };

    // ===== 5. Счётчик Received Qty =====
    const PartsCounter = {
        updateReceivedCount: function (workorderNumber) {
            const modal = DomUtils.getModal(workorderNumber);
            if (!modal) return;

            const rows = DomUtils.getTableRows(modal);
            let receivedQty = 0;

            rows.forEach(function (row) {
                const receivedInput = row.querySelector('.received-date');
                if (receivedInput && receivedInput.value) {
                    receivedQty += DomUtils.getQtyFromRow(row);
                }
            });

            const receivedSpan = DomUtils.getReceivedCounter(workorderNumber);
            if (receivedSpan) {
                receivedSpan.textContent = receivedQty;
            }
        }
    };

    // ===== 6. Управление полем PO NO =====
    const PoNoManager = {
        setReceivedToday: function (selectElement, tdrsId, workorderNumber) {
            const row = selectElement.closest('tr');
            const receivedInput = row ? row.querySelector('.received-date') : null;
            if (!receivedInput) return;

            if (receivedInput.value) return;

            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;

            receivedInput.value = dateStr;
            PartsApi.saveField(tdrsId, 'received', dateStr, workorderNumber);
        },

        handleSelectChange: function (selectElement) {
            const tdrsId         = selectElement.getAttribute('data-tdrs-id');
            const workorderNumber = selectElement.getAttribute('data-workorder-number');
            const value          = selectElement.value;
            const prevValue      = selectElement.dataset.prevValue || '';
            const input          = DomUtils.getPoNoInput(selectElement);

            if (value === 'INPUT') {
                PoNoManager.showInput(input);
            } else if (value === 'Transfer from WO') {
                PoNoManager.hideInput(input);

                const targetWo = prompt('Enter source Work Order number (from which to transfer part):', '');
                if (!targetWo) {
                    selectElement.value = prevValue;
                    return;
                }

                TransferApi.createTransfer(tdrsId, workorderNumber, targetWo)
                    .then(data => {
                        if (!data?.success) {
                            showNotification(data?.message || 'Failed to create transfer', 'error');
                            selectElement.value = prevValue;
                            return;
                        }
                        const specialValues = ['Customer', 'Transfer from WO'];
                        const saveValue     = specialValues.includes(value) ? value : '';
                        const fullValue     = `${saveValue} ${targetWo}`;
                        return PartsApi.saveField(tdrsId, 'po_num', fullValue, workorderNumber)
                            .then(() => {
                                PoNoManager.setReceivedToday(selectElement, tdrsId, workorderNumber);
                            });
                    })
                    .catch(err => {
                        console.error('Transfer create error:', err);
                        showNotification('Error creating transfer', 'error');
                        selectElement.value = prevValue;
                    });
            } else {
                PoNoManager.hideInput(input);
                const specialValues = ['Customer', 'Transfer from WO'];
                const saveValue     = specialValues.includes(value) ? value : '';

                const deletePromise = prevValue === 'Transfer from WO'
                    ? TransferApi.deleteByTdr(tdrsId)
                    : Promise.resolve();

                deletePromise
                    .then(() => PartsApi.saveField(tdrsId, 'po_num', saveValue, workorderNumber))
                    .then(() => {
                        if (value !== 'Customer') {
                            PoNoManager.setReceivedToday(selectElement, tdrsId, workorderNumber);
                        }
                    })
                    .catch(err => {
                        console.error('Transfer delete error:', err);
                        showNotification('Error deleting transfer', 'error');
                    });
            }
        },

        showInput: function (input) {
            if (input) {
                input.style.display = 'block';
                input.focus();
            }
        },

        hideInput: function (input) {
            if (input) {
                input.style.display = 'none';
                input.value = '';
            }
        },

        handleInputChange: function (inputElement) {
            const tdrsId         = inputElement.getAttribute('data-tdrs-id');
            const workorderNumber = inputElement.getAttribute('data-workorder-number');
            const value          = inputElement.value;

            PoNoDebounceManager.debounceSave(tdrsId, workorderNumber, value);

            const row           = inputElement.closest('tr');
            const selectElement = row ? row.querySelector('.po-no-select') : null;
            if (row && selectElement) {
                PoNoManager.setReceivedToday(selectElement, tdrsId, workorderNumber);
            }
        }
    };

    // ===== 7. Debounce для PO NO =====
    const PoNoDebounceManager = {
        timeouts: {},

        debounceSave: function (tdrsId, workorderNumber, value) {
            const timeoutKey = tdrsId + '_' + workorderNumber;

            if (this.timeouts[timeoutKey]) {
                clearTimeout(this.timeouts[timeoutKey]);
            }

            this.timeouts[timeoutKey] = setTimeout(function () {
                PartsApi.saveField(tdrsId, 'po_num', value, workorderNumber);
                delete PoNoDebounceManager.timeouts[timeoutKey];
            }, CONFIG.debounceDelay);
        }
    };

    // ===== 8. Управление полем Received =====
    const ReceivedManager = {
        handleDateChange: function (inputElement) {
            const tdrsId         = inputElement.getAttribute('data-tdrs-id');
            const workorderNumber = inputElement.getAttribute('data-workorder-number');
            const value          = inputElement.value;

            PartsApi.saveField(tdrsId, 'received', value, workorderNumber);
        }
    };

    // ===== 9. Обработчики событий =====
    const EventHandlers = {
        handleChange: function (e) {
            if (e.target.classList.contains('po-no-select')) {
                PoNoManager.handleSelectChange(e.target);
            } else if (e.target.classList.contains('received-date')) {
                ReceivedManager.handleDateChange(e.target);
            }
        },

        handleFocus: function (e) {
            if (e.target.classList.contains('po-no-select')) {
                e.target.dataset.prevValue = e.target.value || '';
            }
        },

        handleInput: function (e) {
            if (e.target.classList.contains('po-no-input')) {
                PoNoManager.handleInputChange(e.target);
            }
        },

        handleModalOpen: function (button) {
            const target          = button.getAttribute('data-bs-target');
            const workorderNumber = target.replace('#partsModal', '');

            setTimeout(function () {
                PartsCounter.updateReceivedCount(workorderNumber);
            }, CONFIG.modalOpenDelay);
        }
    };

    // ===== 10. Инициализация частей / модалок =====
    const PartsModal = {
        init: function () {
            this.attachEventListeners();
            this.initModalButtons();
        },

        attachEventListeners: function () {
            document.addEventListener('change',  EventHandlers.handleChange);
            document.addEventListener('input',   EventHandlers.handleInput);
            document.addEventListener('focusin', EventHandlers.handleFocus);
        },

        initModalButtons: function () {
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-bs-target^="#partsModal"]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        EventHandlers.handleModalOpen(this);
                    });
                });
            });
        }
    };

    PartsModal.init();
})();

// ===============================
// TRAINING (chooser: Update OR Add) + Add form (360 days) + Update save
// ===============================

'use strict';

const mainsTrainingExistsUrl  = @json(route('trainings.exists'));
const mainsCreateTrainingUrl  = @json(route('trainings.createTraining'));
const mainsShowUrl            = @json(route('mains.show', $current_workorder->id));
const mainsCsrfToken          = @json(csrf_token());
const DAYS_THRESHOLD          = 360;

// global context for Update modal
let mainsUpdateManualId = null;

function getTodayYmd() {
    const t = new Date();
    t.setHours(0, 0, 0, 0);
    return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
}

function daysBetween(d1, d2) {
    const a = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
    const b = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
    return Math.floor((b - a) / (24 * 60 * 60 * 1000));
}

function safeModal(el) {
    if (!el) return null;
    return bootstrap.Modal.getOrCreateInstance(el);
}

async function checkTrainingExists(manualId) {
    const r = await fetch(mainsTrainingExistsUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': mainsCsrfToken
        },
        body: JSON.stringify({ manual_id: parseInt(manualId, 10) })
    });
    return await r.json();
}

document.addEventListener('DOMContentLoaded', function () {

    // -------------------------------
    // Elements (may be missing on some pages)
    // -------------------------------
    const updateModalEl   = document.getElementById('mainsUpdateTrainingModal');
    const updateDateInp   = document.getElementById('mainsUpdateTrainingDateInput');
    const updateSaveBtn   = document.getElementById('mainsUpdateTrainingSaveBtn');

    const addModalEl      = document.getElementById('mainsAddTrainingsModal');
    const addForm         = document.getElementById('mainsAddTrainingsForm');
    const manualIdInput   = document.getElementById('mainsAddTrainingsManualId');
    const listEl          = document.getElementById('mains_training_dates_list');
    const addDateBtn      = document.getElementById('mains_add_training_date_btn');

    const additionalGroup = document.getElementById('mains_additional_training_date_group');
    const additionalInput = document.getElementById('mains_additional_training_date');

    const additionalModalEl = document.getElementById('mainsAdditionalTrainingModal');
    const additionalModalNo = document.getElementById('mainsAdditionalModalNo');
    const additionalModalYes = document.getElementById('mainsAdditionalModalYes');

    // -------------------------------
    // 0) Update modal open via .mains-update-training-btn (if you use it somewhere)
    // -------------------------------
    document.querySelectorAll('.mains-update-training-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            mainsUpdateManualId = this.getAttribute('data-manual-id');
            if (updateDateInp) updateDateInp.value = getTodayYmd();
            safeModal(updateModalEl)?.show();
        });
    });

    // -------------------------------
    // 1) CHOOSER on "+" button: if exists -> Update modal else -> Add modal
    // -------------------------------
    document.querySelectorAll('.mains-add-trainings-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const manualId = this.getAttribute('data-manual-id');
            if (!manualId) return;

            try {
                const data = await checkTrainingExists(manualId);
                if (!data?.success) {
                    showNotification('Error: cannot check trainings.', 'error');
                    return;
                }

                if (data.exists) {
                    // ✅ open UPDATE
                    mainsUpdateManualId = manualId;
                    if (updateDateInp) updateDateInp.value = getTodayYmd();
                    safeModal(updateModalEl)?.show();
                } else {
                    // ✅ open ADD
                    if (addForm && addForm.reset) addForm.reset();
                    if (manualIdInput) manualIdInput.value = manualId;
                    if (listEl) listEl.innerHTML = '';

                    if (additionalGroup) additionalGroup.style.display = 'none';
                    if (additionalInput) additionalInput.value = '';

                    // сброс флагов submit-логики
                    if (addForm) {
                        addForm.removeAttribute('data-allow-submit');
                        addForm.dataset.additionalAsked = '0';
                        addForm.dataset.pendingSubmit = '0';
                    }

                    safeModal(addModalEl)?.show();
                }
            } catch (e) {
                console.error(e);
                showNotification('Error: request failed.', 'error');
            }
        });
    });

    // -------------------------------
    // 2) UPDATE save (one date)
    // -------------------------------
    if (updateSaveBtn) {
        updateSaveBtn.addEventListener('click', function () {
            if (!mainsUpdateManualId) return;

            const dateYmd = updateDateInp && updateDateInp.value ? updateDateInp.value.trim() : '';
            if (!dateYmd) {
                showNotification('{{ __("Please select a date.") }}', 'warning');
                return;
            }

            const trainingData = {
                manuals_id: [parseInt(mainsUpdateManualId, 10)],
                date_training: [dateYmd],
                form_type: ['112']
            };

            fetch(mainsCreateTrainingUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': mainsCsrfToken
                },
                body: JSON.stringify(trainingData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || '{{ __("Training added.") }}', 'success');
                        window.location.href = mainsShowUrl;
                    } else {
                        showNotification('{{ __("Error") }}: ' + (data.message || ''), 'error');
                    }
                })
                .catch(err => {
                    showNotification('{{ __("An error occurred") }}: ' + err.message, 'error');
                });
        });
    }

    // -------------------------------
    // 3) ADD modal helpers: add rows
    // -------------------------------
    function addMainsTrainingDateRow(value) {
        if (!listEl) return;

        const row = document.createElement('div');
        row.className = 'input-group input-group-sm mb-1';
        row.innerHTML =
            '<input type="date" name="training_dates[]" class="form-control" value="' + (value || '') + '">' +
            '<button type="button" class="btn btn-outline-danger btn-sm mains-remove-training-date" aria-label="Remove">' +
            '<i class="bi bi-dash"></i></button>';

        listEl.appendChild(row);

        const rm = row.querySelector('.mains-remove-training-date');
        if (rm) rm.addEventListener('click', function () { row.remove(); });
    }

    if (addDateBtn) {
        addDateBtn.addEventListener('click', function () {
            addMainsTrainingDateRow('');
        });
    }

    function getMainsFirstDate() {
        const el = document.getElementById('mains_date_training');
        return el && el.value ? new Date(el.value + 'T00:00:00') : null;
    }

    function getMainsTodayStart() {
        const t = new Date();
        t.setHours(0, 0, 0, 0);
        return t;
    }

    function getMainsTrainingDatesEntered() {
        const dates = [];
        if (!listEl) return dates;

        listEl.querySelectorAll('input[name="training_dates[]"]').forEach(function (inp) {
            if (inp.value) dates.push(inp.value);
        });

        return dates.sort(); // string sort ok for YYYY-MM-DD
    }

    function getMainsLastEnteredDate() {
        const dates = getMainsTrainingDatesEntered();
        if (!dates.length) return null;
        return new Date(dates[dates.length - 1] + 'T00:00:00');
    }

    // -------------------------------
    // 4) Additional modal (Yes/No) handlers
    // -------------------------------
    function hideAdditionalModal() {
        const inst = bootstrap.Modal.getInstance(additionalModalEl);
        if (inst) inst.hide();
    }

    if (additionalModalNo) {
        additionalModalNo.addEventListener('click', function () {
            if (!addForm) return;
            addForm.dataset.additionalAsked = '1';
            hideAdditionalModal();

            if (addForm.dataset.pendingSubmit === '1') {
                addForm.dataset.allowSubmit = '1';
                addForm.submit();
            }
            addForm.dataset.pendingSubmit = '0';
        });
    }

    if (additionalModalYes) {
        additionalModalYes.addEventListener('click', function () {
            if (!addForm) return;
            addForm.dataset.additionalAsked = '1';
            hideAdditionalModal();

            if (additionalGroup) additionalGroup.style.display = 'block';
            if (additionalInput && !additionalInput.value) additionalInput.value = getTodayYmd();

            if (addForm.dataset.pendingSubmit === '1') {
                addForm.dataset.allowSubmit = '1';
                addForm.submit();
            }
            addForm.dataset.pendingSubmit = '0';
        });
    }

    // -------------------------------
    // 5) ADD form submit intercept (360 days logic)
    // -------------------------------
    if (addForm) {
        // init flags
        addForm.dataset.additionalAsked = addForm.dataset.additionalAsked || '0';
        addForm.dataset.pendingSubmit = addForm.dataset.pendingSubmit || '0';

        addForm.addEventListener('submit', function (e) {
            // allow real submit once
            if (addForm.dataset.allowSubmit === '1') {
                addForm.removeAttribute('data-allow-submit');
                return;
            }

            e.preventDefault();

            const subsequentDates = getMainsTrainingDatesEntered();
            const lastEntered = subsequentDates.length ? getMainsLastEnteredDate() : getMainsFirstDate();
            const today = getMainsTodayStart();

            const needAsk = lastEntered && daysBetween(lastEntered, today) >= DAYS_THRESHOLD;
            const hasAdditionalShown = additionalGroup && additionalGroup.style.display !== 'none';
            const hasAdditionalValue = additionalInput && additionalInput.value;
            const alreadyAsked = addForm.dataset.additionalAsked === '1';

            if (needAsk && !alreadyAsked && !(hasAdditionalShown && hasAdditionalValue)) {
                // show additional modal question
                addForm.dataset.pendingSubmit = '1';
                safeModal(additionalModalEl)?.show();
                return;
            }

            addForm.dataset.pendingSubmit = '0';
            addForm.dataset.allowSubmit = '1';
            addForm.submit();
        });
    }

});
</script>
