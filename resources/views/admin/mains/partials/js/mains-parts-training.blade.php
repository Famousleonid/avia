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
                            alert(data?.message || 'Failed to create transfer');
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
                        alert('Error creating transfer');
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
                        alert('Error deleting transfer');
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
// TRAINING (Update = выбор даты; Add = форма как в training.create + 360 дней)
// ===============================

const mainsCreateTrainingUrl = @json(route('trainings.createTraining'));
const mainsShowUrl = @json(route('mains.show', $current_workorder->id));
const mainsCsrfToken = @json(csrf_token());
const DAYS_THRESHOLD = 360;

function getTodayYmd() {
    const t = new Date();
    t.setHours(0, 0, 0, 0);
    return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
}


// ----- Update training: модалка с одной датой (по умолчанию сегодня) -----
document.addEventListener('DOMContentLoaded', function () {
    let mainsUpdateManualId = null;

    document.querySelectorAll('.mains-update-training-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            mainsUpdateManualId = this.getAttribute('data-manual-id');
            const input = document.getElementById('mainsUpdateTrainingDateInput');
            if (input) input.value = getTodayYmd();
            const modal = new bootstrap.Modal(document.getElementById('mainsUpdateTrainingModal'));
            modal.show();
        });
    });

    document.getElementById('mainsUpdateTrainingSaveBtn').addEventListener('click', function () {
        if (!mainsUpdateManualId) return;
        const input = document.getElementById('mainsUpdateTrainingDateInput');
        const dateYmd = input && input.value ? input.value.trim() : '';
        if (!dateYmd) {
            alert('{{ __("Please select a date.") }}');
            return;
        }
        const trainingData = {
            manuals_id: [parseInt(mainsUpdateManualId, 10)],
            date_training: [dateYmd],
            form_type: ['112']
        };
        fetch(mainsCreateTrainingUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mainsCsrfToken },
            body: JSON.stringify(trainingData)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    alert(data.message || '{{ __("Training added.") }}');
                    window.location.href = mainsShowUrl;
                } else {
                    alert('{{ __("Error") }}: ' + (data.message || ''));
                }
            })
            .catch(function (err) {
                alert('{{ __("An error occurred") }}: ' + err.message);
            });
    });
});

// ----- Add trainings: форма как в training.create + 360 дней -----
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mainsAddTrainingsForm');
    const manualIdInput = document.getElementById('mainsAddTrainingsManualId');
    const listEl = document.getElementById('mains_training_dates_list');
    const addBtn = document.getElementById('mains_add_training_date_btn');
    const additionalGroup = document.getElementById('mains_additional_training_date_group');
    const additionalInput = document.getElementById('mains_additional_training_date');
    const additionalModal = document.getElementById('mainsAdditionalTrainingModal');
    let additionalTrainingAsked = false;
    let formPendingSubmit = false;

    document.querySelectorAll('.mains-add-trainings-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const manualId = this.getAttribute('data-manual-id');
            if (manualIdInput) manualIdInput.value = manualId;
            if (listEl) listEl.innerHTML = '';
            if (form && form.reset) form.reset();
            if (manualIdInput) manualIdInput.value = manualId;
            additionalTrainingAsked = false;
            if (additionalGroup) additionalGroup.style.display = 'none';
            if (additionalInput) additionalInput.value = '';
            const modal = new bootstrap.Modal(document.getElementById('mainsAddTrainingsModal'));
            modal.show();
        });
    });

    function addMainsTrainingDateRow(value) {
        if (!listEl) return;
        const row = document.createElement('div');
        row.className = 'input-group input-group-sm mb-1';
        row.innerHTML = '<input type="date" name="training_dates[]" class="form-control" value="' + (value || '') + '">' +
            '<button type="button" class="btn btn-outline-danger btn-sm mains-remove-training-date" aria-label="Remove"><i class="bi bi-dash"></i></button>';
        listEl.appendChild(row);
        row.querySelector('.mains-remove-training-date').addEventListener('click', function () { row.remove(); });
    }

    if (addBtn) addBtn.addEventListener('click', function () { addMainsTrainingDateRow(''); });

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
        if (listEl) listEl.querySelectorAll('input[name="training_dates[]"]').forEach(function (inp) {
            if (inp.value) dates.push(inp.value);
        });
        return dates.sort();
    }
    function getMainsLastEnteredDate() {
        const dates = getMainsTrainingDatesEntered();
        if (dates.length === 0) return null;
        return new Date(dates[dates.length - 1] + 'T00:00:00');
    }
    function daysBetween(d1, d2) {
        const a = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
        const b = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
        return Math.floor((b - a) / (24 * 60 * 60 * 1000));
    }

    document.getElementById('mainsAdditionalModalNo').addEventListener('click', function () {
        additionalTrainingAsked = true;
        bootstrap.Modal.getInstance(additionalModal).hide();
        if (formPendingSubmit && form) {
            form.dataset.allowSubmit = '1';
            form.submit();
        }
        formPendingSubmit = false;
    });
    document.getElementById('mainsAdditionalModalYes').addEventListener('click', function () {
        additionalTrainingAsked = true;
        bootstrap.Modal.getInstance(additionalModal).hide();
        if (additionalGroup) additionalGroup.style.display = 'block';
        if (additionalInput && !additionalInput.value) additionalInput.value = getTodayYmd();
        if (formPendingSubmit && form) {
            form.dataset.allowSubmit = '1';
            form.submit();
        }
        formPendingSubmit = false;
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.allowSubmit === '1') {
                form.removeAttribute('data-allow-submit');
                return;
            }
            e.preventDefault();
            const subsequentDates = getMainsTrainingDatesEntered();
            const lastEntered = subsequentDates.length ? getMainsLastEnteredDate() : getMainsFirstDate();
            const today = getMainsTodayStart();
            const needAsk = lastEntered && daysBetween(lastEntered, today) >= DAYS_THRESHOLD;
            const hasAdditional = additionalInput && additionalInput.value && additionalGroup && additionalGroup.style.display !== 'none';

            if (needAsk && !additionalTrainingAsked && !hasAdditional) {
                formPendingSubmit = true;
                new bootstrap.Modal(additionalModal).show();
                return;
            }
            formPendingSubmit = false;
            form.dataset.allowSubmit = '1';
            form.submit();
        });
    }
});
</script>
