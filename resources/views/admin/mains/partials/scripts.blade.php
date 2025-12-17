<script>
    document.addEventListener('DOMContentLoaded', () => {

        const safeShowSpinner = () => {
            try {
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
            } catch (_) {
            }
        };
        const safeHideSpinner = () => {
            try {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            } catch (_) {
            }
        };

        safeHideSpinner();
        window.addEventListener('pageshow', safeHideSpinner);

        const debounce = (fn, ms) => {
            let t;
            return (...a) => {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(null, a), ms);
            }
        };

        const form = document.getElementById('general_task_form');
        const taskInput = document.getElementById('task_id');
        const addBtn = document.getElementById('addBtn');
        const pickerBtn = document.getElementById('taskPickerBtn');
        const pickedSummary = document.getElementById('pickedSummary');

        const generalTabs = Array.from(document.querySelectorAll('#generalTab .nav-link[data-general-id]'));
        const taskPanes = Array.from(document.querySelectorAll('#taskTabContent .tab-pane'));
        const taskButtons = Array.from(document.querySelectorAll('.select-task'));

        // ----- Task picker -----
        function showPaneForGeneral(btn) {
            const gid = btn.dataset.generalId;
            generalTabs.forEach(b => b.classList.remove('active'));
            taskPanes.forEach(p => p.classList.remove('show', 'active'));
            btn.classList.add('active');
            const pane = document.getElementById('pane-g-' + gid);
            if (pane) pane.classList.add('active', 'show');
        }

        function generalNameById(gid) {
            const b = document.getElementById('tab-g-' + gid);
            return (b ? b.textContent : '').trim();
        }

        function updatePickedSummary(gName, tName) {
            if (!pickedSummary) return;
            pickedSummary.textContent = (gName && tName) ? `${gName} → ${tName}` : (tName || '');
        }

        function activateAddButton() {
            if (!addBtn) return;
            addBtn.removeAttribute('disabled');
            addBtn.classList.remove('disabled');
        }

        function initTaskPicker() {
            generalTabs.forEach(btn => {
                btn.addEventListener('mouseenter', () => showPaneForGeneral(btn));
                btn.addEventListener('click', e => e.preventDefault());
            });

            taskButtons.forEach(item => {
                item.addEventListener('click', () => {
                    const taskId = item.dataset.taskId;
                    const taskName = item.dataset.taskName;
                    const gid = item.dataset.generalId;

                    if (taskInput) taskInput.value = taskId;
                    updatePickedSummary(generalNameById(gid), taskName);
                    activateAddButton();

                    if (pickerBtn && window.bootstrap?.Dropdown) {
                        const dd = bootstrap.Dropdown.getOrCreateInstance(pickerBtn);
                        dd?.hide();
                    }
                });
            });

            if (generalTabs[0]) showPaneForGeneral(generalTabs[0]);
            if (taskInput?.value) activateAddButton();
        }

        // ----- submit add task -----
        function bindFormSubmit() {
            if (!form) return;
            form.addEventListener('submit', (e) => {
                if (!taskInput?.value) {
                    e.preventDefault();
                    alert('Please choose a task first');
                    return;
                }
                safeShowSpinner();
                if (addBtn) {
                    addBtn.setAttribute('disabled', 'disabled');
                    addBtn.classList.add('disabled');
                }
            });
        }

        //       ----- flatpickr для всех input[data-fp] -----
        function initDatePickers() {
            if (typeof flatpickr === 'undefined') return;

            document.querySelectorAll('input[data-fp]').forEach(src => {
                if (src._flatpickr) return;

                flatpickr(src, {
                    altInput: true,
                    altFormat: "d.m.Y",
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    disableMobile: true,

                    onChange(selectedDates, dateStr, instance) {
                        const form = src.closest('form');
                        if (!form) return;

                        safeShowSpinner();
                        if (form.requestSubmit) form.requestSubmit();
                        else form.submit();
                    },

                    onReady(selectedDates, dateStr, instance) {
                        instance.altInput.classList.add('form-control', 'form-control-sm', 'w-100', 'fp-alt');

                        // если хочешь сохранить стили для finish
                        if (src.classList.contains('finish-input')) instance.altInput.classList.add('finish-input');
                        if (src.value) instance.altInput.classList.add('has-finish');

                        src.style.display = 'none';
                    }
                });
            });

            document.body.classList.add('fp-ready');
        }

        function initAutoSubmitOrder() {

            document.querySelectorAll('.auto-submit-order').forEach(form => {

                const input = form.querySelector('input[name="repair_order"]');
                const icon = form.querySelector('.save-indicator');
                if (!input || !icon) return;

                // текущее сохранённое значение
                let savedValue = input.dataset.original ?? '';

                // начальное состояние при загрузке
                if (savedValue) {
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                }

                // ✏️ при вводе — показываем 💾, убираем зелёный
                input.addEventListener('input', function () {
                    if (this.value !== savedValue) {
                        icon.classList.remove('d-none');
                        this.classList.remove('is-valid');
                    } else {
                        icon.classList.add('d-none');
                        if (this.value) this.classList.add('is-valid');
                    }
                });

                // 💾 сохранение ТОЛЬКО по Enter
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        safeShowSpinner();
                        icon.classList.add('d-none');

                        // считаем, что это новое сохранённое значение
                        savedValue = this.value;
                        input.dataset.original = savedValue;

                        // визуальное состояние после сохранения
                        if (savedValue) {
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                        }

                        form.submit();
                    }
                });

                // (опционально) при потере фокуса без Enter — ничего не сохраняем,
                // просто возвращаем корректный визуал
                input.addEventListener('blur', function () {
                    if (this.value === savedValue) {
                        icon.classList.add('d-none');
                        if (this.value) this.classList.add('is-valid');
                        else this.classList.remove('is-valid');
                    }
                });
            });
        }

        // ----- delete (tasks / mains / tdrprocesses через общий modal) -----
        const modalEl = document.getElementById('useConfirmDelete');
        const confirmBt = document.getElementById('confirmDeleteBtn');
        const delForm = document.getElementById('deleteForm');
        let pendingAction = null;

        modalEl?.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            pendingAction = trigger?.getAttribute('data-action') || null;

            const title = trigger?.getAttribute('data-title') || 'Delete Confirmation';
            const lbl = document.getElementById('confirmDeleteLabel');
            if (lbl) lbl.textContent = title;
        });

        confirmBt?.addEventListener('click', function () {
            if (!pendingAction) return;
            delForm.setAttribute('action', pendingAction);
            safeShowSpinner();
            delForm.submit();
        });

        // ----- Show all components switch -----
        document.getElementById('showAll')?.addEventListener('change', function () {
            safeShowSpinner();
            if (this.form?.requestSubmit) this.form.requestSubmit();
            else this.form?.submit();
        });

        // ===== ЛОГИКА ФОТО =====
        const confirmPhotoBtn = document.getElementById('confirmPhotoDeleteBtn');

        confirmPhotoBtn?.addEventListener('click', async function () {
            const {mediaId, photoBlock} = window.pendingDelete || {};
            if (!mediaId) return;

            safeShowSpinner();

            try {
                const response = await fetch(`/workorders/photo/delete/${mediaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    if (photoBlock) {
                        photoBlock.style.transition = 'opacity 0.3s ease';
                        photoBlock.style.opacity = '0';
                    }

                    setTimeout(() => {
                        photoBlock?.remove();
                        if (window.currentWorkorderId) {
                            loadPhotoModal(window.currentWorkorderId);
                        }
                    }, 300);

                    const toastEl = document.getElementById('photoDeletedToast');
                    if (toastEl) {
                        const toast = new bootstrap.Toast(toastEl);
                        toast.show();
                    }
                } else {
                    alert('Failed to delete photo');
                }
            } catch (err) {
                console.error('Delete error:', err);
                alert('Server error');
            } finally {
                safeHideSpinner();
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeletePhotoModal'));
                modal?.hide();
                window.pendingDelete = null;
            }
        });

        // Открытие модалки с фото
        document.querySelectorAll('.open-photo-modal').forEach(button => {
            button.addEventListener('click', async function () {

                const workorderId = this.dataset.id;
                const workorderNumber = this.dataset.number;

                window.currentWorkorderId = workorderId;
                window.currentWorkorderNumber = workorderNumber;

                await loadPhotoModal(workorderId);
                new bootstrap.Modal(document.getElementById('photoModal')).show();
            });
        });

        // загрузка фото в модалку
        async function loadPhotoModal(workorderId) {
            const modalContent = document.getElementById('photoModalContent');
            if (!modalContent) return;

            safeShowSpinner();

            try {
                const response = await fetch(`/workorders/${workorderId}/photos`);

                if (!response.ok) {
                    throw new Error('Response not ok');
                }

                const data = await response.json();

                let html = '';

                // какие группы есть и как их подписывать
                const groupsConfig = {
                    photos: 'Photos',
                    damages: 'Damage',
                    logs: 'Log card',
                    final: 'Final assy'
                };

                Object.entries(groupsConfig).forEach(([group, label]) => {
                    const items = data[group] || [];

                    html += `
                <div class="col-12">
                    <h6 class="text-primary text-uppercase mt-2">${label}</h6>
                    <div class="row g-2">
            `;

                    if (!items.length) {
                        html += `
                    <div class="col-12 text-muted small">No photos</div>
                `;
                    } else {
                        items.forEach(media => {
                            html += `
                        <div class="col-4 col-md-2 col-lg-1 photo-item">
                            <div class="position-relative d-inline-block w-100">
                                <a data-fancybox="${group}" href="${media.big}" data-caption="${label}">
                                    <img src="${media.thumb}" class="photo-thumbnail border border-primary rounded" />
                                </a>
                                <button class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                                        style="top: -6px; right: -6px; width: 20px; height: 20px; z-index: 10;"
                                        data-id="${media.id}" title="Delete">
                                    <i class="bi bi-x" style="font-size: 12px;"></i>
                                </button>
                            </div>
                        </div>
                    `;
                        });
                    }

                    html += `
                    </div>
                </div>
            `;
                });

                modalContent.innerHTML = html;
                bindDeleteButtons();

            } catch (e) {
                console.error('Load photo error', e);
                modalContent.innerHTML = '<div class="text-danger">Failed to load photos</div>';
            } finally {
                safeHideSpinner();
            }
        }

        // навесить обработчики на кнопки удаления
        function bindDeleteButtons() {
            document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const mediaId = this.dataset.id;
                    const photoBlock = this.closest('.photo-item');

                    window.pendingDelete = {mediaId, photoBlock};
                    new bootstrap.Modal(document.getElementById('confirmDeletePhotoModal')).show();
                });
            });
        }

        // Скачивание ZIP
        document.getElementById('saveAllPhotos')?.addEventListener('click', function () {
            const workorderId = window.currentWorkorderId;
            const workorderNumber = window.currentWorkorderNumber || 'workorder';
            if (!workorderId) return alert('Workorder ID missing');

            safeShowSpinner();

            fetch(`/workorders/download/${workorderId}/all`)
                .then(response => {
                    if (!response.ok) throw new Error('Download failed');
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `workorder_${workorderNumber}_images.zip`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(err => {
                    console.error('Error downloading ZIP:', err);
                    alert('Download failed');
                })
                .finally(() => {
                    safeHideSpinner();
                });
        });

        // init
        initTaskPicker();
        bindFormSubmit();
        initDatePickers();
        document.body.classList.add('fp-ready');
        if (typeof initAutoSubmit === 'function') initAutoSubmit();
        initAutoSubmitOrder();

        // ===== ЛОГИ =====
        document.querySelectorAll('.open-log-modal').forEach(btn => {
            btn.addEventListener('click', async function () {
                const url = this.dataset.url;
                await loadLogModal(url);
                new bootstrap.Modal(document.getElementById('logModal')).show();
            });
        });

        async function loadLogModal(url) {
            const container = document.getElementById('logModalContent');
            if (!container) return;

            container.innerHTML = '<div class="text-muted small">Loading...</div>';

            try {
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                const resp = await fetch(url, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin'
                });

                if (!resp.ok) throw new Error('Response not ok');

                const data = await resp.json();

                if (!data || !data.length) {
                    container.innerHTML = '<div class="text-muted small">No log entries for this workorder.</div>';
                    return;
                }

                let html = '';

                data.forEach(item => {
                    const created = item.created_at ?? '';
                    const desc = item.description ?? '';
                    const event = item.event ?? '';
                    const causer = item.causer_name ?? '';
                    const changes = item.changes ?? [];

                    // Цвет и иконки
                    let badgeClass, icon;
                    if (event === 'created') {
                        badgeClass = 'bg-success';
                        icon = '<i class="bi bi-check-circle me-1"></i>';
                    } else if (event === 'updated') {
                        badgeClass = 'bg-warning text-dark';
                        icon = '<i class="bi bi-pencil-square me-1"></i>';
                    } else if (event === 'deleted') {
                        badgeClass = 'bg-danger';
                        icon = '<i class="bi bi-x-circle me-1"></i>';
                    } else {
                        badgeClass = 'bg-secondary';
                        icon = '<i class="bi bi-info-circle me-1"></i>';
                    }

                    html += `
                <div class="p-3 mb-3 border rounded bg-dark bg-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
    <strong class="text-info">${created}</strong>
    ${causer ? `<span class="text-muted ms-2">${causer}</span>` : ''}
    ${desc ? `<span class="ms-2 text-light">${desc}</span>` : ''}
</div>
                        <span class="badge rounded-pill ${badgeClass}">
                        ${icon}${event || 'log'}
                        </span>
                        </div>
                        `;

                    if (changes.length) {
                        html += `<ul class="mt-2 mb-0 ps-3">`;
                        changes.forEach(ch => {
                            html += `
                        <li>
                        <strong>${ch.label}:</strong>
                        <span class="text-danger">${ch.old ?? '—'}</span>
                        <span class="text-muted mx-1">→</span>
                        <span class="text-success">${ch.new ?? '—'}</span>
                        </li>
                        `;
                        });
                        html += `</ul>`;
                    }

                    html += `</div>`;
                });

                container.innerHTML = html;

            } catch (e) {
                console.error('Load log error', e);
                container.innerHTML = '<div class="text-danger">Failed to load log</div>';
            } finally {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }
        }

    });
</script>

<script>
    (function () {
        'use strict';

        // Конфигурация
        const CONFIG = {
            debounceDelay: 500,
            modalOpenDelay: 300,
            qtyColumnIndex: 4
        };

        // Утилиты для работы с CSRF токеном
        const TokenUtils = {
            getCsrfToken: function () {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                return metaTag
                    ? metaTag.getAttribute('content')
                    : '{{ csrf_token() }}';
            }
        };

        // Утилиты для работы с DOM
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

        // API для сохранения данных
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

        // API для работы с transfers
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

        // Управление счетчиками
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

        // Управление полем PO NO
        const PoNoManager = {
            setReceivedToday: function (selectElement, tdrsId, workorderNumber) {
                const row = selectElement.closest('tr');
                const receivedInput = row ? row.querySelector('.received-date') : null;
                if (!receivedInput) return;

                // Если уже есть дата — не трогаем, пользователь может менять её вручную через календарь
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
                const tdrsId = selectElement.getAttribute('data-tdrs-id');
                const workorderNumber = selectElement.getAttribute('data-workorder-number');
                const value = selectElement.value;
                const prevValue = selectElement.dataset.prevValue || '';
                const input = DomUtils.getPoNoInput(selectElement);

                if (value === 'INPUT') {
                    PoNoManager.showInput(input);
                } else if (value === 'Transfer from WO') {
                    PoNoManager.hideInput(input);

                    const targetWo = prompt('Enter source Work Order number (from which to transfer part):', '');
                    if (!targetWo) {
                        // Отменили или не ввели номер – откатываем выбор
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
                            const saveValue = specialValues.includes(value) ? value : '';
                            const fullValue = `${saveValue} ${targetWo}`;
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
                    const saveValue = specialValues.includes(value) ? value : '';
                    // Если раньше был Transfer from WO, удаляем transfer-запись
                    const deletePromise = prevValue === 'Transfer from WO'
                        ? TransferApi.deleteByTdr(tdrsId)
                        : Promise.resolve();

                    deletePromise
                        .then(() => PartsApi.saveField(tdrsId, 'po_num', saveValue, workorderNumber))
                        .then(() => {
                            // Для Customer дату НЕ трогаем, для остальных (PO No. и т.п.) — авто-дата
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
                const tdrsId = inputElement.getAttribute('data-tdrs-id');
                const workorderNumber = inputElement.getAttribute('data-workorder-number');
                const value = inputElement.value;

                PoNoDebounceManager.debounceSave(tdrsId, workorderNumber, value);

                // Если это первое заполнение PO No. и дата Received ещё пустая — считаем, что деталь пришла сегодня
                const row = inputElement.closest('tr');
                const selectElement = row ? row.querySelector('.po-no-select') : null;
                if (row && selectElement) {
                    PoNoManager.setReceivedToday(selectElement, tdrsId, workorderNumber);
                }
            }
        };

        // Debounce менеджер для PO NO input
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

        // Управление полем Received
        const ReceivedManager = {
            handleDateChange: function (inputElement) {
                const tdrsId = inputElement.getAttribute('data-tdrs-id');
                const workorderNumber = inputElement.getAttribute('data-workorder-number');
                const value = inputElement.value;

                PartsApi.saveField(tdrsId, 'received', value, workorderNumber);
            }
        };

        // Обработчики событий
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
                const target = button.getAttribute('data-bs-target');
                const workorderNumber = target.replace('#partsModal', '');

                setTimeout(function () {
                    PartsCounter.updateReceivedCount(workorderNumber);
                }, CONFIG.modalOpenDelay);
            }
        };

        // Инициализация
        const PartsModal = {
            init: function () {
                this.attachEventListeners();
                this.initModalButtons();
            },

            attachEventListeners: function () {
                document.addEventListener('change', EventHandlers.handleChange);
                document.addEventListener('input', EventHandlers.handleInput);
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

        // Запуск при загрузке
        PartsModal.init();

    })();
</script>

{{-- Training functions --}}
<script>
    function createTrainings(manualId) {
        if (confirm('Create new trainings for this unit?')) {
            // Перенаправляем на страницу создания тренировок с предзаполненным manual_id и URL возврата на mains.main
            const returnUrl = '{{ route('mains.show', $current_workorder->id) }}';
            window.location.href = `{{ route('trainings.create') }}?manual_id=${manualId}&return_url=${encodeURIComponent(returnUrl)}`;
        }
    }

    // Функция обновления тренировки на сегодняшнюю дату
    function updateTrainingToToday(manualId, lastTrainingDate, autoUpdate = false) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Если сегодня пятница - используем сегодня, иначе последнюю прошедшую пятницу
        let trainingDate;
        if (today.getDay() === 5) { // 5 = пятница
            trainingDate = today;
        } else {
            // Находим последнюю прошедшую пятницу
            const dayOfWeek = today.getDay();
            let daysToSubtract;
            if (dayOfWeek === 0) { // Воскресенье - пятница была вчера (1 день назад)
                daysToSubtract = 1;
            } else if (dayOfWeek === 6) { // Суббота - пятница была вчера (1 день назад)
                daysToSubtract = 1;
            } else { // Понедельник-четверг - пятница была (dayOfWeek + 2) дней назад
                daysToSubtract = dayOfWeek + 2;
            }
            trainingDate = new Date(today);
            trainingDate.setDate(today.getDate() - daysToSubtract);
        }

        const todayStr = trainingDate.toISOString().split('T')[0];
        const lastTraining = new Date(lastTrainingDate);
        const monthsDiff = Math.floor((today - lastTraining) / (1000 * 60 * 60 * 24 * 30));

        // Если автоматическое обновление, не показываем подтверждение
        if (!autoUpdate) {
            const confirmationMessage = `Update training to today's date?\n\n` +
                `Last training: ${lastTrainingDate} (${monthsDiff} months ago)\n` +
                `New training date: ${todayStr}\n\n` +
                `This will create a new training record and update the training status.`;

            if (!confirm(confirmationMessage)) {
                return;
            }
        }

        const trainingData = {
            manuals_id: [manualId],
            date_training: [todayStr],
            form_type: ['112']
        };

        fetch('{{ route('trainings.updateToToday') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(trainingData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!autoUpdate) {
                        alert(`Training updated to today!\nCreated: ${data.created} training record(s)`);
                    }
                    // Возврат на страницу mains.main
                    window.location.href = '{{ route('mains.show', $current_workorder->id) }}';
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-gt-btn');
            if (!btn) return;
            const gtId = btn.dataset.gtId;
            document.querySelectorAll('.js-gt-btn').forEach(b => {
                const on = (b === btn);
                b.classList.toggle('active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            document.querySelectorAll('.js-gt-pane').forEach(p => {
                p.classList.toggle('d-none', p.dataset.gtId !== gtId);
            });

            if (typeof initDatePickers === 'function') initDatePickers();
        });

        // autosubmit on change
        document.addEventListener('change', (e) => {
            const input = e.target.closest('form.js-auto-submit input');
            if (!input) return;
            input.form.submit();
        });

        // open logs
        const logModalEl = document.getElementById('logModal');
        const logModal = logModalEl ? new bootstrap.Modal(logModalEl) : null;

        document.addEventListener('click', async (e) => {
            const b = e.target.closest('.js-open-log');
            if (!b) return;

            const url = b.dataset.url;
            const box = document.getElementById('logModalContent');

            box.innerHTML = `<div class="text-muted small">Loading…<br><span class="text-muted">${url}</span></div>`;
            new bootstrap.Modal(document.getElementById('logModal')).show();

            try {
                const r = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const text = await r.text();

                if (!r.ok) {
                    box.innerHTML = `<div class="text-danger small">
        HTTP ${r.status} ${r.statusText}<br>
        <div class="mt-2 text-muted" style="white-space:pre-wrap;max-height:240px;overflow:auto;">${text}</div>
      </div>`;
                    return;
                }
                js-ignore-finish
                box.innerHTML = text;
            } catch (err) {
                box.innerHTML = `<div class="text-danger small">Fetch failed: ${err.message}</div>`;
            }
        });

        document.addEventListener('submit', function(e){
            const form = e.target.closest('.js-row-form');
            if (!form) return;

            const gtId = form.dataset.gtId;
            if (!gtId) return;

            const btn = document.querySelector(`.js-gt-btn[data-gt-id="${gtId}"]`);
            if (!btn) return;

            // пока не знаем итог — сделаем нейтрально (или оставь как есть)
            btn.classList.add('btn-warning');
        }, true);

        document.addEventListener('change', function (e) {
            const cb = e.target.closest('.js-ignore-finish');
            if (!cb) return;

            const form = cb.closest('form');
            if (!form) return;

            const finish = form.querySelector('.js-finish');
            const hidden = form.querySelector('.js-ignore-hidden');

            const on = cb.checked;

            // 0 / 1 в request
            if (hidden) hidden.value = on ? '1' : '0';

            if (finish) {
                finish.disabled = on;
                finish.classList.toggle('is-ignored', on);

                // optional: очистить значение при ignore
                if (on) {
                    finish.value = '';
                }
            }

            // автосабмит
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });


    });
</script>
