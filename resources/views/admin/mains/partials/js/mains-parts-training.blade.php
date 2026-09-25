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
        saveField: async function (tdrsId, field, value, workorderNumber) {
            const csrfToken = TokenUtils.getCsrfToken();
            const url = @json(route('workorders.part-receipt.update', $current_workorder));
            if (field === 'po_num' && value === 'Customer') {
                if (typeof window.confirmDialog !== 'function') {
                    showNotification('Confirmation is unavailable. Reload the page.', 'error');
                    PartsAppearance.restore(tdrsId, field);
                    return {success: false};
                }
                const approved = await window.confirmDialog({title: 'Part Replacement List', message: 'Save "Customer"?', highlightText: 'Customer', okText: 'Save', cancelText: 'Cancel'});
                if (!approved) { PartsAppearance.restore(tdrsId, field); return {success: false}; }
            }

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    row_key: tdrsId,
                    field: field,
                    value: value
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        PartsAppearance.markSaved(tdrsId, field, value);
                        if (field === 'received') {
                            PartsCounter.updateReceivedCount(workorderNumber);
                        }
                        return data;
                    }
                    showNotification(data.message || 'Save failed', 'error');
                    throw new Error(data.message || 'Save failed');
                })
                .catch(error => {
                    console.error('Error saving field:', error);
                    PartsAppearance.restore(tdrsId, field);
                    showNotification(error.message || 'Save failed', 'error');
                    return {success: false};
                });
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
                if (row.classList.contains('prl-complete')) {
                    receivedQty += 1;
                }
            });

            const receivedSpan = DomUtils.getReceivedCounter(workorderNumber);
            if (receivedSpan) {
                receivedSpan.textContent = receivedQty;
                const orderedSpan = document.getElementById('orderedRows' + workorderNumber);
                receivedSpan.closest('.main-parts-count')?.classList.toggle('is-complete', Number(orderedSpan?.textContent) === receivedQty);
            }
        }
    };

    const TransferPicker = {
        row: null,
        choices: [],
        init: function () {
            this.el = document.getElementById('logCardTransferModal');
            this.source = document.getElementById('logCardTransferSource');
            this.select = document.getElementById('logCardTransferPart');
            this.status = document.getElementById('logCardTransferStatus');
            this.loadButton = document.getElementById('logCardTransferLoad');
            this.saveButton = document.getElementById('logCardTransferSave');
            this.cancelButton = document.getElementById('logCardTransferCancel');
            this.formLink = document.getElementById('logCardTransferForm');
            this.loadButton.addEventListener('click', () => this.load());
            this.source.addEventListener('input', () => { this.select.replaceChildren(); this.saveButton.disabled = true; });
            this.source.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); this.load(); } });
            this.select.addEventListener('change', () => { this.saveButton.disabled = !this.select.value; });
            this.saveButton.addEventListener('click', () => this.save());
            this.cancelButton.addEventListener('click', () => this.cancel());
            this.el.addEventListener('hidden.bs.modal', e => {
                if (e.target === this.el) bootstrap.Modal.getOrCreateInstance(this.row.closest('.modal')).show();
            });
            document.addEventListener('click', e => {
                const button = e.target.closest('.prl-transfer-details');
                if (button) this.open(button.closest('tr'));
            });
        },
        open: function (row) {
            this.row = row;
            this.key = row.querySelector('[data-tdrs-id]').dataset.tdrsId;
            this.source.value = (row.dataset.savedPo || '').replace(/^Transfer from WO\s*/, '');
            if (!(row.dataset.savedPo || '').startsWith('Transfer from WO')) this.source.value = '';
            this.select.replaceChildren(); this.choices = []; this.saveButton.disabled = true;
            const existing = Boolean(row.dataset.transferId);
            this.cancelButton.classList.toggle('d-none', !existing);
            this.formLink.classList.toggle('d-none', !existing);
            if (existing) this.formLink.href = @json(route('transfers.transferForm', ':id')).replace(':id', row.dataset.transferId);
            this.source.disabled = existing; this.loadButton.disabled = existing;
            this.status.textContent = existing ? 'Transfer already recorded. Cancel it before choosing another source.' : '';
            document.getElementById('logCardTransferTarget').textContent = 'To w' + @json((string) $current_workorder->number) + ' — ' + row.cells[2].innerText.trim() + ' — QTY ' + row.cells[3].innerText.trim();
            const parent = row.closest('.modal');
            const hideParent = () => bootstrap.Modal.getOrCreateInstance(parent).hide();
            // Bootstrap ignores hide() while the opening transition is running.
            parent.addEventListener('shown.bs.modal', hideParent, {once: true});
            parent.addEventListener('hidden.bs.modal', () => {
                parent.removeEventListener('shown.bs.modal', hideParent);
                bootstrap.Modal.getOrCreateInstance(this.el).show();
            }, {once: true});
            hideParent();
        },
        request: async function (method, url, body) {
            const response = await fetch(url, {method, headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TokenUtils.getCsrfToken()}, ...(body ? {body: JSON.stringify(body)} : {})});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Transfer failed');
            return data;
        },
        load: async function () {
            if (this.row.dataset.transferId) return;
            this.loadButton.disabled = true; this.saveButton.disabled = true; this.select.replaceChildren();
            this.status.textContent = 'Loading…';
            try {
                const query = new URLSearchParams({row_key: this.key, source_number: this.source.value.trim()});
                const data = await this.request('GET', @json(route('workorders.log-card-transfer.sources', $current_workorder)) + '?' + query);
                this.choices = data.parts;
                for (const part of this.choices) {
                    const option = new Option(part.ipl + ' | ' + part.part_number + ' | S/N: ' + (part.serial_number || '—') + ' | Available: ' + part.available + ' / Required: ' + part.required, part.token);
                    option.disabled = !part.can_transfer;
                    this.select.add(option);
                }
                this.select.selectedIndex = -1;
                this.status.textContent = this.choices.length ? 'Select the Log Card part to transfer.' : 'No matching Log Card parts in this workorder.';
            } catch (error) { this.status.textContent = error.message; }
            finally { this.loadButton.disabled = false; }
        },
        confirm: async function (message, danger = false) {
            if (typeof window.confirmDialog !== 'function') { this.status.textContent = 'Confirmation is unavailable. Reload the page.'; return false; }
            return window.confirmDialog({title: 'Transfer', message, okText: danger ? 'Cancel transfer' : 'Create transfer', cancelText: 'Back', danger});
        },
        save: async function () {
            const part = this.choices.find(p => p.token === this.select.value);
            if (!part) return;
            this.saveButton.disabled = true;
            try {
                if (!await this.confirm('Transfer ' + part.part_number + ' / S/N ' + (part.serial_number || '—') + ', QTY ' + part.required + ', from w' + this.source.value.trim() + ' to w' + @json((string) $current_workorder->number) + '?')) return;
                const data = await this.request('POST', @json(route('workorders.log-card-transfer.store', $current_workorder)), {row_key: this.key, source_number: this.source.value.trim(), source_token: part.token});
                this.row.dataset.transferId = data.transfer_id;
                PartsAppearance.markSaved(this.key, 'po_num', data.po_num);
                PartsAppearance.restore(this.key, 'po_num');
                this.row.querySelector('.prl-transfer-details').style.display = 'block';
                bootstrap.Modal.getOrCreateInstance(this.el).hide();
            } catch (error) { this.status.textContent = error.message; }
            finally { this.saveButton.disabled = false; }
        },
        cancel: async function () {
            this.cancelButton.disabled = true;
            try {
                if (!await this.confirm('Cancel this transfer and clear its PO and receipt date? Log Cards will not change.', true)) return;
                await this.request('DELETE', @json(route('workorders.log-card-transfer.destroy', $current_workorder)), {row_key: this.key});
                this.row.dataset.transferId = '';
                PartsAppearance.markSaved(this.key, 'po_num', ''); PartsAppearance.markSaved(this.key, 'received', '');
                PartsAppearance.restore(this.key, 'po_num'); PartsAppearance.restore(this.key, 'received');
                this.row.querySelector('.prl-transfer-details').style.display = 'none';
                bootstrap.Modal.getOrCreateInstance(this.el).hide();
            } catch (error) { this.status.textContent = error.message; }
            finally { this.cancelButton.disabled = false; }
        }
    };

    const PartsAppearance = {
        restore: function (tdrsId, field) {
            const input = document.querySelector('.main-prl-dialog [data-tdrs-id="' + tdrsId + '"]');
            const row = input?.closest('tr');
            if (!row) return;
            if (field === 'received_qty') {
                row.querySelector('.received-qty').value = row.querySelector('.received-qty').dataset.savedQty || '';
            } else if (field === 'received') {
                const date = row.querySelector('.received-date');
                date._flatpickr.setDate(row.dataset.savedReceived || '', false, 'Y-m-d');
            } else {
                const value = row.dataset.savedPo || '';
                const select = row.querySelector('.po-no-select');
                const number = row.querySelector('.po-no-input');
                select.value = value === 'Customer' ? 'Customer' : value.startsWith('Transfer from WO') ? 'Transfer from WO' : 'INPUT';
                number.value = select.value === 'INPUT' ? value : '';
                number.style.display = select.value === 'INPUT' ? 'block' : 'none';
            }
            PartsAppearance.refresh(row);
            PartsCounter.updateReceivedCount(input.dataset.workorderNumber);
        },
        markSaved: function (tdrsId, field, value) {
            if (field === 'received_qty') {
                const qty = document.querySelector('.received-qty[data-tdrs-id="' + tdrsId + '"]');
                qty.dataset.savedQty = String(value ?? '');
                PartsAppearance.refresh(qty.closest('tr'));
                return;
            }
            const input = document.querySelector('.main-prl-dialog [data-tdrs-id="' + tdrsId + '"].' + (field === 'received' ? 'received-date' : 'po-no-input'));
            if (!input) return;
            const row = input.closest('tr');
            row.dataset[field === 'received' ? 'savedReceived' : 'savedPo'] = String(value || '').trim();
            PartsAppearance.refresh(row);
        },
        refresh: function (row) {
            const select = row.querySelector('.po-no-select');
            const number = row.querySelector('.po-no-input').value.trim();
            const date = row.querySelector('.received-date').value;
            const savedPo = row.dataset.savedPo || '';
            const poComplete = select.value === 'INPUT'
                ? Boolean(number) && number === savedPo
                : Boolean(select.value) && savedPo.startsWith(select.value);
            const qty = row.querySelector('.received-qty');
            const quantityComplete = qty.value !== '' && qty.value === qty.dataset.savedQty && Number(qty.value) === DomUtils.getQtyFromRow(row);
            row.classList.toggle('prl-complete', quantityComplete && poComplete && Boolean(date) && date === row.dataset.savedReceived);
            const modal = row.closest('.modal');
            PartsAppearance.updateStatusCount(modal);
            PartsAppearance.filterRow(row);
            const workorderNumber = row.querySelector('[data-workorder-number]')?.dataset.workorderNumber;
            if (workorderNumber) PartsCounter.updateReceivedCount(workorderNumber);
        },
        updateStatusCount: function (modal) {
            if (!modal) return;
            const pending = modal.querySelector('.main-prl-filter:checked')?.value === 'pending';
            const received = modal.querySelectorAll('tbody tr.prl-complete').length;
            modal.querySelector('.main-prl-counts').classList.toggle('is-pending', pending);
            modal.querySelector('.main-prl-status-label').textContent = pending ? 'Pending' : 'Received';
            modal.querySelector('.main-prl-green-count').textContent = pending ? modal.querySelectorAll('tbody tr').length - received : received;
        },
        filterRow: function (row) {
            const modal = row.closest('.modal');
            const filter = modal?.querySelector('.main-prl-filter:checked')?.value || 'all';
            const query = (modal?.querySelector('.main-prl-search')?.value || '').trim().toLocaleLowerCase();
            const matchesSearch = !query || [0, 1, 2].some(index => row.cells[index].textContent.toLocaleLowerCase().includes(query));
            const complete = row.classList.contains('prl-complete');
            row.hidden = !matchesSearch || (filter === 'received' ? !complete : filter === 'pending' ? complete : false);
        },
        init: function () {
            document.querySelectorAll('.main-prl-search').forEach(input => {
                input.addEventListener('input', () => {
                    const modal = input.closest('.modal');
                    modal.querySelectorAll('tbody tr').forEach(row => PartsAppearance.filterRow(row));
                    modal.querySelector('.modal-body').scrollTop = 0;
                });
            });
            document.querySelectorAll('.main-prl-dialog .received-qty').forEach(input => {
                input.addEventListener('input', () => PartsAppearance.refresh(input.closest('tr')));
                input.addEventListener('change', () => {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        PartsAppearance.restore(input.dataset.tdrsId, 'received_qty');
                        return;
                    }
                    if (input.value !== input.dataset.savedQty) PartsApi.saveField(input.dataset.tdrsId, 'received_qty', input.value, input.dataset.workorderNumber);
                });
            });
            document.querySelectorAll('.main-prl-filter').forEach(input => {
                input.addEventListener('change', () => {
                    input.closest('.modal').querySelectorAll('tbody tr').forEach(row => PartsAppearance.filterRow(row));
                    PartsAppearance.updateStatusCount(input.closest('.modal'));
                    input.closest('.modal').querySelector('.modal-body').scrollTop = 0;
                });
            });
            document.querySelectorAll('.main-prl-dialog .received-date').forEach(input => {
                if (typeof flatpickr !== 'undefined' && !input._flatpickr) {
                    flatpickr(input, {
                        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/M/Y',
                        locale: 'en', allowInput: true, disableMobile: true,
                        onReady: function (_, __, fp) {
                            fp.altInput.classList.remove('received-date');
                            fp.altInput.placeholder = 'dd/Mmm/yyyy';
                        }
                    });
                }
                PartsAppearance.markSaved(input.dataset.tdrsId, 'received', input.value);
            });
            document.querySelectorAll('.main-prl-dialog .po-no-select').forEach(select => {
                const value = select.value === 'INPUT' ? DomUtils.getPoNoInput(select).value : (select.dataset.savedPo || select.value);
                PartsAppearance.markSaved(select.dataset.tdrsId, 'po_num', value);
            });
        }
    };

    // ===== 6. Управление полем PO NO =====
    const PoNoManager = {
        handleSelectChange: function (selectElement) {
            const row = selectElement.closest('tr');
            const key = selectElement.dataset.tdrsId;
            const input = DomUtils.getPoNoInput(selectElement);
            if (selectElement.value === 'Transfer from WO' || row.dataset.transferId) {
                PartsAppearance.restore(key, 'po_num');
                TransferPicker.open(row);
                return;
            }
            if (selectElement.value === 'INPUT') {
                PoNoManager.showInput(input);
                PartsAppearance.refresh(row);
            } else {
                PoNoManager.hideInput(input);
                PartsApi.saveField(key, 'po_num', selectElement.value, selectElement.dataset.workorderNumber);
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
            if (e.target.matches('.po-no-select, .received-date')) PartsAppearance.refresh(e.target.closest('tr'));
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
                PartsAppearance.refresh(e.target.closest('tr'));
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
                PartsAppearance.init();
                TransferPicker.init();
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

</script>
