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
// TRAINING
// ===============================

function createTrainings(manualId) {
    if (!confirm('Create new trainings for this unit?')) return;

    const returnUrl = @json(route('mains.show', $current_workorder->id));
    const baseUrl   = @json(route('trainings.create'));

    window.location.href =
        `${baseUrl}?manual_id=${manualId}&return_url=${encodeURIComponent(returnUrl)}`;
}

function updateTrainingToToday(manualId, lastTrainingDate, autoUpdate = false) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let trainingDate;
    if (today.getDay() === 5) {
        trainingDate = today;
    } else {
        const dayOfWeek    = today.getDay();
        let daysToSubtract;
        if (dayOfWeek === 0) {
            daysToSubtract = 1;
        } else if (dayOfWeek === 6) {
            daysToSubtract = 1;
        } else {
            daysToSubtract = dayOfWeek + 2;
        }
        trainingDate = new Date(today);
        trainingDate.setDate(today.getDate() - daysToSubtract);
    }

    const todayStr     = trainingDate.toISOString().split('T')[0];
    const lastTraining = new Date(lastTrainingDate);
    const monthsDiff   = Math.floor((today - lastTraining) / (1000 * 60 * 60 * 24 * 30));

    if (!autoUpdate) {
        const confirmationMessage =
            `Update training to today's date?\n\n` +
            `Last training: ${lastTrainingDate} (${monthsDiff} months ago)\n` +
            `New training date: ${todayStr}\n\n` +
            `This will create a new training record and update the training status.`;

        if (!confirm(confirmationMessage)) return;
    }

    const trainingData = {
        manuals_id:    [manualId],
        date_training: [todayStr],
        form_type:     ['112']
    };

    const updateUrl  = @json(route('trainings.updateToToday'));
    const mainsShow  = @json(route('mains.show', $current_workorder->id));

    fetch(updateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token())
        },
        body: JSON.stringify(trainingData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (!autoUpdate) {
                    alert(`Training updated to today!\nCreated: ${data.created} training record(s)`);
                }
                window.location.href = mainsShow;
            } else {
                if (!autoUpdate) {
                    alert('Error updating training: ' + (data.message || 'Unknown error'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (!autoUpdate) {
                alert('An error occurred: ' + error.message);
            }
        });
}
</script>
