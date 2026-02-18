/**
 * Create Processes Form - модуль для формы добавления процессов TDR
 * Используется на странице tdr-processes.createProcesses
 */
(function (window) {
    'use strict';

    const CreateProcessesForm = {
        config: null,

        init(config) {
            this.config = config;
            if (!this.config || !document.getElementById('createCPForm')) return;

            this.bindAddProcess();
            this.bindFormSubmit();
            this.bindProcessNameSelect();
            this.bindModal();
            this.bindCheckboxChange();
            this.initFirstRowSelect2();
        },

        isNdtProcess(processNameId) {
            return (this.config.ndtProcessNames || []).includes(parseInt(processNameId));
        },

        isEcEligibleProcess(processNameId) {
            return (this.config.ecEligibleProcessNameIds || []).includes(parseInt(processNameId));
        },

        getSelectedProcessNameId(element) {
            let select;
            if (element.classList?.contains('select2-process')) select = element;
            else if (element.classList?.contains('process-row')) select = element.querySelector('.select2-process');
            else if (element.target) select = element.target;
            return select?.value || null;
        },

        toggleEcCheckbox(processRow, processNameId) {
            const ecCheckbox = processRow?.querySelector('input[name*="[ec]"]');
            if (ecCheckbox) {
                ecCheckbox.closest('.form-check').style.display = this.isEcEligibleProcess(processNameId) ? 'block' : 'none';
            }
        },

        getRowHtml(index, optionsHtml, ndtOptionsHtml) {
            const plusImg = this.config.plusImgSrc || '/img/plus.png';
            const processNamesData = JSON.stringify(this.config.processNamesData || {}).replace(/"/g, '&quot;');
            return `
                <div class="row">
                    <div class="col-md-3">
                        <label>Process Name:</label>
                        <select name="processes[${index}][process_names_id]" class="form-control select2-process" required data-process-data='${processNamesData}'>${optionsHtml}</select>
                    </div>
                    <div class="col-md-5">
                        <label>Processes (Specification):</label>
                        <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal" data-bs-target="#addProcessModal"><img src="${plusImg}" alt="+" style="width:20px"></button>
                        <div class="process-options"></div>
                        <div class="ndt-plus-process-container mt-3" style="display:none;visibility:visible">
                            <label>Additional NDT Process(es):</label>
                            <select name="processes[${index}][ndt_plus_process][]" class="form-control select2-ndt-plus" id="ndt_plus_process_${index}" data-row-index="${index}" multiple style="width:100%;min-height:70px">${ndtOptionsHtml}</select>
                            <div class="ndt-plus-process-options mt-2"></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2" style="display:none">
                            <input type="checkbox" name="processes[${index}][ec]" value="1" class="form-check-input" id="ec_${index}">
                            <label class="form-check-label" for="ec_${index}">EC</label>
                        </div>
                        <div>
                            <label class="form-label" style="margin-bottom:-5px">Description</label>
                            <input type="text" class="form-control" name="processes[${index}][description]" placeholder="CMM fig.___ pg. ___">
                            <label class="form-label" style="margin-bottom:-5px">Notes</label>
                            <input type="text" class="form-control" name="processes[${index}][notes]" placeholder="Enter Notes">
                        </div>
                    </div>
                </div>`;
        },

        bindAddProcess() {
            const btn = document.getElementById('add-process');
            if (!btn) return;

            btn.addEventListener('click', () => {
                const container = document.getElementById('processes-container');
                const index = container.children.length;
                const optionsHtml = this.buildProcessOptionsHtml();
                const ndtOptionsHtml = this.buildNdtOptionsHtml();
                const rowHtml = this.getRowHtml(index, optionsHtml, ndtOptionsHtml);

                const newRow = document.createElement('div');
                newRow.classList.add('process-row', 'mb-3');
                newRow.innerHTML = rowHtml;
                container.appendChild(newRow);

                this.initRowSelect2(newRow);
            });
        },

        buildProcessOptionsHtml() {
            const names = this.config.processNames || [];
            return '<option value="">Select Process Name</option>' + names.map(p => {
                let attrs = `data-process-id="${p.id}"`;
                if (p.id === 1) attrs += ' data-is-special="true"';
                else if (p.id >= 5 && p.id <= 10) attrs += ' data-is-range="true"';
                if ([2, 3, 4].includes(p.id)) attrs += ' data-is-group="true"';
                return `<option value="${p.id}" ${attrs}>${p.name}</option>`;
            }).join('');
        },

        buildNdtOptionsHtml() {
            return (this.config.ndtProcessNamesData || []).map(p =>
                `<option value="${p.id}" data-process-name="${p.name}">${p.name}</option>`
            ).join('');
        },

        initRowSelect2(row) {
            if (typeof $ === 'undefined' || !$.fn.select2) return;

            const self = this;
            $(row).find('.select2-process').select2({ theme: 'bootstrap-5', width: '100%' })
                .on('select2:select', function (e) {
                    const select = e.target;
                    const processNameId = select.value;
                    const processRow = select.closest('.process-row');
                    self.toggleEcCheckbox(processRow, processNameId);
                    self.loadProcessesForRow(select);
                    if (self.isNdtProcess(processNameId)) {
                        const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                });

            $(row).find('.select2-ndt-plus').select2({
                theme: 'bootstrap-5', width: '100%', multiple: true,
                placeholder: 'Select Additional NDT Process(es)'
            });
        },

        initFirstRowSelect2() {
            const firstRow = document.querySelector('.process-row');
            if (firstRow) this.initRowSelect2(firstRow);
        },

        loadProcessesForRow(selectElement) {
            const processNameId = selectElement.value;
            const processRow = selectElement.closest('.process-row');
            const processOptionsContainer = processRow?.querySelector('.process-options');
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) return;

            const match = selectElement.name?.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            processOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error || e.message); }))
                .then(data => {
                    processOptionsContainer.innerHTML = '';
                    let hasProcesses = false;

                    if (data.existingProcesses?.length) {
                        data.existingProcesses.forEach(process => {
                            const div = document.createElement('div');
                            div.classList.add('form-check');
                            div.innerHTML = `<input type="checkbox" name="processes[${rowIndex}][process][]" value="${process.id}" class="form-check-input"><label class="form-check-label">${process.process}</label>`;
                            processOptionsContainer.appendChild(div);
                            hasProcesses = true;
                        });
                    }

                    if (hasProcesses) {
                        saveButton.disabled = false;
                        if (this.isNdtProcess(processNameId)) this.showNdtPlusForRow(processRow, processNameId);
                    } else {
                        processOptionsContainer.innerHTML = '<div class="text-muted mt-2">No specification. Add specification for this process.</div>';
                        saveButton.disabled = true;
                    }
                })
                .catch(err => {
                    processOptionsContainer.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
                    saveButton.disabled = true;
                });
        },

        showNdtPlusForRow(processRow, processNameId) {
            const ndtPlusContainer = processRow?.querySelector('.ndt-plus-process-container');
            const checkedBoxes = processRow?.querySelectorAll('.process-options input[type="checkbox"]:checked');
            if (!ndtPlusContainer || !checkedBoxes?.length) return;

            ndtPlusContainer.style.display = 'block';
            ndtPlusContainer.style.visibility = 'visible';

            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2 && !$(ndtPlusSelect).hasClass('select2-hidden-accessible')) {
                const self = this;
                $(ndtPlusSelect).select2({ theme: 'bootstrap-5', width: '100%', multiple: true, placeholder: 'Select Additional NDT Process(es)', dropdownParent: $(ndtPlusContainer) })
                    .on('select2:select select2:unselect', function (e) {
                        const sel = e.target;
                        if (($(sel).val() || []).length) self.loadNdtPlusProcesses(sel);
                        else (processRow.querySelector('.ndt-plus-process-options') || {}).innerHTML = '';
                    });
                $(ndtPlusSelect).find(`option[value="${processNameId}"]`).prop('disabled', true);
            }
        },

        loadNdtPlusProcesses(selectElement) {
            const processRow = selectElement.closest('.process-row');
            const ndtPlusOptionsContainer = processRow?.querySelector('.ndt-plus-process-options');
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;
            const rowIndex = selectElement.getAttribute('data-row-index') || '0';
            const selectedIds = typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')
                ? ($(selectElement).val() || []) : Array.from(selectElement.selectedOptions || []).map(o => o.value);

            if (!selectedIds.length || !ndtPlusOptionsContainer) {
                ndtPlusOptionsContainer.innerHTML = '';
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading...</div>';

            Promise.all(selectedIds.map(processNameId =>
                fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                    .then(r => r.json())
                    .then(data => ({ processNameId, processes: data.existingProcesses || [] }))
            )).then(results => {
                ndtPlusOptionsContainer.innerHTML = '';
                results.forEach(({ processNameId, processes }) => {
                    if (processes.length) {
                        const opt = $(selectElement).find(`option[value="${processNameId}"]`);
                        const name = opt.length ? opt.text() : `NDT-${processNameId}`;
                        const header = document.createElement('div');
                        header.className = 'mt-2 mb-1';
                        header.innerHTML = `<strong>${name}:</strong>`;
                        ndtPlusOptionsContainer.appendChild(header);
                        processes.forEach(process => {
                            const div = document.createElement('div');
                            div.classList.add('form-check');
                            div.innerHTML = `<input type="checkbox" name="processes[${rowIndex}][ndt_plus_processes][]" value="${process.id}" class="form-check-input ndt-plus-process-checkbox" data-ndt-process-name-id="${processNameId}"><label class="form-check-label">${process.process}</label>`;
                            ndtPlusOptionsContainer.appendChild(div);
                        });
                    }
                });
                if (!ndtPlusOptionsContainer.children.length) ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">No processes available</div>';
            }).catch(err => {
                ndtPlusOptionsContainer.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
            });
        },

        bindFormSubmit() {
            const form = document.getElementById('createCPForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const tdrId = document.querySelector('input[name="tdrs_id"]')?.value;
                const processRows = document.querySelectorAll('.process-row');
                const processesData = [];
                let hasChecked = false;

                processRows.forEach(row => {
                    const processNameSelect = row.querySelector('.select2-process');
                    const processNameId = processNameSelect?.value;
                    const checkboxes = row.querySelectorAll('.process-options input[type="checkbox"]:checked');
                    const selectedProcessIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

                    const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                    let ndtPlusProcessIds = [];
                    const ndtPlusProcessNameIds = [];
                    if (ndtPlusSelect) {
                        const selectedNdtIds = Array.from(ndtPlusSelect.selectedOptions).map(o => o.value);
                        selectedNdtIds.forEach(id => { if (id && !ndtPlusProcessNameIds.includes(id)) ndtPlusProcessNameIds.push(id); });
                        row.querySelectorAll('.ndt-plus-process-checkbox:checked').forEach(cb => {
                            if (selectedNdtIds.includes(cb.getAttribute('data-ndt-process-name-id'))) ndtPlusProcessIds.push(parseInt(cb.value));
                        });
                    }

                    const ecValue = row.querySelector('input[name*="[ec]"]')?.checked || false;
                    const description = row.querySelector('input[name*="[description]"]')?.value?.trim() || null;
                    const notes = row.querySelector('input[name*="[notes]"]')?.value?.trim() || null;

                    checkboxes.forEach(() => { hasChecked = true; });

                    if (selectedProcessIds.length) {
                        const allProcessIds = [...selectedProcessIds, ...ndtPlusProcessIds];
                        const plusProcess = ndtPlusProcessNameIds.length ? ndtPlusProcessNameIds.sort((a, b) => parseInt(a) - parseInt(b)).join(',') : null;
                        processesData.push({ process_names_id: processNameId, plus_process: plusProcess, processes: allProcessIds, ec: ecValue, description, notes });
                    }
                });

                if (!hasChecked) {
                    showNotification('Process not added because no checkbox is selected.', 'warning');
                    return;
                }

                fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.config.csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tdrs_id: tdrId, processes: processesData })
                })
                    .then(r => r.ok ? r.json() : r.json().then(err => { throw new Error(err.error || err.message); }))
                    .then(data => {
                        showNotification(data.message || 'Saved', 'success');
                        if (data.redirect) window.location.href = data.redirect;
                    })
                    .catch(err => {
                        const msg = err.message || 'Error saving processes.';
                        showNotification('Ошибка: ' + msg, 'error');
                    });
            });
        },

        bindProcessNameSelect() {
            document.addEventListener('change', (e) => {
                if (!e.target.matches('.process-options input[type="checkbox"]')) return;
                const row = e.target.closest('.process-row');
                const processNameId = row?.querySelector('.select2-process')?.value;
                if (processNameId && this.isNdtProcess(processNameId)) {
                    const ndtPlusContainer = row.querySelector('.ndt-plus-process-container');
                    const checked = row.querySelectorAll('.process-options input[type="checkbox"]:checked').length;
                    if (ndtPlusContainer && checked) {
                        ndtPlusContainer.style.display = 'block';
                        ndtPlusContainer.style.visibility = 'visible';
                        this.showNdtPlusForRow(row, processNameId);
                    }
                }
            });
        },

        bindModal() {
            const modal = document.getElementById('addProcessModal');
            const saveBtn = document.getElementById('saveProcessModal');
            if (!modal || !saveBtn) return;

            modal.addEventListener('show.bs.modal', () => {
                const activeRow = document.querySelector('.process-row:has(.btn-link[data-bs-target="#addProcessModal"]:focus), .process-row:last-of-type');
                const select = (activeRow || document.querySelector('.process-row'))?.querySelector('.select2-process');
                const processNameId = select?.value;
                const processName = select?.options[select.selectedIndex]?.text || '';

                window.CreateProcessesCurrentRow = activeRow;
                document.getElementById('modalProcessName').textContent = processName;
                document.getElementById('modalProcessNameId').value = processNameId || '';
                document.getElementById('newProcessInput').value = '';

                const container = document.getElementById('availableProcessContainer');
                container.innerHTML = '<div class="text-muted">Loading...</div>';

                const manualId = document.getElementById('processes-container')?.dataset?.manualId;
                fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                    .then(r => r.json())
                    .then(data => {
                        container.innerHTML = '';
                        (data.availableProcesses || []).forEach(process => {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `<input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}"><label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>`;
                            container.appendChild(div);
                        });
                        if (!container.children.length) container.innerHTML = '<div class="text-muted">No available processes</div>';
                    })
                    .catch(() => { container.innerHTML = '<div class="text-danger">Error loading</div>'; });
            });

            saveBtn.addEventListener('click', () => this.saveModalProcesses());
        },

        saveModalProcesses() {
            const currentRow = window.CreateProcessesCurrentRow;
            const processNameId = document.getElementById('modalProcessNameId').value;
            const newProcessInput = document.getElementById('newProcessInput').value.trim();
            const selectedCheckboxes = document.querySelectorAll('#availableProcessContainer input[type="checkbox"]:checked');
            const processOptionsContainer = currentRow?.querySelector('.process-options');
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;
            const match = currentRow?.querySelector('.select2-process')?.name?.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';
            const saveButton = document.querySelector('button[type="submit"]');

            const addCheckbox = (id, label) => {
                const div = document.createElement('div');
                div.classList.add('form-check');
                div.innerHTML = `<input type="checkbox" name="processes[${rowIndex}][process][]" value="${id}" class="form-check-input" checked><label class="form-check-label">${label}</label>`;
                processOptionsContainer?.appendChild(div);
                saveButton.disabled = false;
                document.querySelector('.process-options .text-muted')?.remove();
            };

            if (newProcessInput) {
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'text-muted';
                loadingDiv.textContent = 'Saving...';
                processOptionsContainer?.appendChild(loadingDiv);

                fetch(this.config.processesStoreUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.config.csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ process_names_id: processNameId, process: newProcessInput, manual_id: manualId })
                })
                    .then(r => r.json())
                    .then(data => {
                        loadingDiv.remove();
                        if (data.success && data.process) {
                            addCheckbox(data.process.id, data.process.process);
                            document.getElementById('newProcessInput').value = '';
                            selectedCheckboxes.forEach(cb => { cb.checked = false; });
                            this.reloadModalProcesses(processNameId, manualId);
                            bootstrap.Modal.getInstance(document.getElementById('addProcessModal'))?.hide();
                        } else {
                            (window.NotificationHandler || { error: alert }).error(data.message || 'Error');
                        }
                    })
                    .catch(err => {
                        loadingDiv.remove();
                        (window.NotificationHandler?.error || alert)('Error: ' + (err.message || 'Unknown'));
                    });
            } else if (selectedCheckboxes.length) {
                selectedCheckboxes.forEach(cb => {
                    const label = cb.nextElementSibling?.textContent || cb.value;
                    addCheckbox(cb.value, label);
                });
                bootstrap.Modal.getInstance(document.getElementById('addProcessModal'))?.hide();
            }
        },

        reloadModalProcesses(processNameId, manualId) {
            const container = document.getElementById('availableProcessContainer');
            container.innerHTML = '<div class="text-muted">Reloading...</div>';
            fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(r => r.json())
                .then(data => {
                    container.innerHTML = '';
                    (data.availableProcesses || []).forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `<input type="checkbox" class="form-check-input" name="modal_processes[]" value="${p.id}"><label class="form-check-label">${p.process}</label>`;
                        container.appendChild(div);
                    });
                    if (!container.children.length) container.innerHTML = '<div class="text-muted">No available processes</div>';
                });
        },

        bindCheckboxChange() {
            document.addEventListener('change', (e) => {
                if (!e.target.matches('.select2-process')) return;
                const select = e.target;
                const processNameId = select.value;
                const row = select.closest('.process-row');
                this.toggleEcCheckbox(row, processNameId);
            });
        }
    };

    window.CreateProcessesForm = CreateProcessesForm;
})(window);
