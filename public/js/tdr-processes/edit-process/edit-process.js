/**
 * Edit Process Form - модуль для формы редактирования процесса TDR
 * Используется на странице tdr-processes.edit
 */
(function (window) {
    'use strict';

    const TdrProcessEditForm = {
        config: null,

        init(config) {
            this.config = config;
            if (!this.config || !document.getElementById('editCPForm')) return;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.onDomReady());
            } else {
                this.onDomReady();
            }
        },

        isNdtProcess(processNameId) {
            return (this.config.ndtProcessNames || []).includes(parseInt(processNameId));
        },

        isMachiningProcess(processNameId) {
            return (this.config.processNamesData || {})[processNameId]?.process_sheet_name === 'MACHINING';
        },

        showDescriptions(container, show) {
            const hasSelection = !!container.querySelector('.edit-spec-list input:checked');
            container.classList.toggle('show-descriptions', show && hasSelection);
            const toggle = container.querySelector('[data-toggle-descriptions]');
            if (toggle) {
                toggle.classList.toggle('d-none', !hasSelection);
                toggle.textContent = show ? 'Choose processes' : 'Back to descriptions';
            }
        },

        renderSpecificationDescriptions(container, processNameId) {
            const target = container.querySelector('.edit-spec-descriptions');
            if (!target) return;
            target.replaceChildren();
            const selected = [...container.querySelectorAll('.edit-spec-list input:checked')];
            if (!selected.length) { this.showDescriptions(container, false); return; }
            selected.forEach(input => {
                const label = document.createElement('label');
                label.className = 'edit-spec-description';
                const title = document.createElement('span');
                title.textContent = input.nextElementSibling.textContent;
                const field = document.createElement('textarea');
                field.className = 'form-control form-control-sm';
                field.name = 'specification_descriptions[' + input.value + ']';
                field.maxLength = 255; field.rows = 3; field.placeholder = 'Description';
                field.setAttribute('aria-label', 'Description: ' + title.textContent);
                this.descriptionDrafts ??= {...(this.config.specificationDescriptions || {})};
                field.value = this.descriptionDrafts[input.value] ?? (document.querySelector('#editCPForm [name="description"]')?.value || '');
                field.addEventListener('input', () => { this.descriptionDrafts[input.value] = field.value; });
                label.append(title, field); target.append(label);
            });
            this.showDescriptions(container, true);
        },

        createSpecificationPicker(container, processNameId) {
            container.replaceChildren();
            container.classList.remove('show-descriptions');
            const search = document.createElement('input');
            search.type = 'search'; search.className = 'form-control form-control-sm mb-2';
            search.placeholder = 'Search any part of process…'; search.setAttribute('aria-label', 'Search processes');
            const grid = document.createElement('div');
            grid.className = 'edit-spec-grid' + (this.isMachiningProcess(processNameId) ? ' is-machining' : '');
            const list = document.createElement('div'); list.className = 'edit-spec-list';
            const empty = document.createElement('div'); empty.className = 'text-muted d-none';
            empty.textContent = 'No matching processes'; empty.setAttribute('role', 'status'); list.append(empty);
            grid.append(list);
            if (this.isMachiningProcess(processNameId)) {
                const descriptions = document.createElement('div'); descriptions.className = 'edit-spec-descriptions';
                grid.append(descriptions);
            }
            container.append(search, grid);
            if (this.isMachiningProcess(processNameId)) {
                const toggle = document.createElement('button');
                toggle.type = 'button'; toggle.className = 'btn btn-sm btn-outline-info mb-2 d-none';
                toggle.dataset.toggleDescriptions = '';
                toggle.addEventListener('click', () => {
                    const show = !container.classList.contains('show-descriptions');
                    this.showDescriptions(container, show);
                    if (!show) { search.focus({preventScroll: true}); search.select(); }
                });
                container.insertBefore(toggle, search);
            }
            search.addEventListener('input', () => {
                const query = search.value.trim().toLocaleLowerCase(); let count = 0;
                list.querySelectorAll('.form-check').forEach(row => {
                    const matches = row.querySelector('label').textContent.toLocaleLowerCase().includes(query);
                    row.classList.toggle('d-none', !matches); if (matches) count++;
                });
                empty.classList.toggle('d-none', count > 0); list.scrollTop = 0;
            });
            list.addEventListener('change', event => {
                this.renderSpecificationDescriptions(container, processNameId);
                if (event.target.checked) container.querySelector('textarea[name="specification_descriptions[' + event.target.value + ']"]')?.focus({preventScroll: true});
            });
        },

        appendSpecification(container, process, processNameId, rowIndex, checked) {
            container.querySelector('.edit-spec-empty-catalog')?.remove();
            const div = document.createElement('div'); div.className = 'form-check';
            const input = document.createElement('input');
            input.type = this.isMachiningProcess(processNameId) ? 'checkbox' : 'radio';
            input.name = 'processes[' + rowIndex + '][process][]'; input.value = process.id;
            input.className = 'form-check-input'; input.id = 'process_' + rowIndex + '_' + process.id; input.checked = checked;
            const label = document.createElement('label'); label.className = 'form-check-label'; label.htmlFor = input.id;
            window.renderProcessChoiceLabel(label, process); div.append(input, label);
            container.querySelector('.edit-spec-list').append(div);
        },

        isEcEligibleProcess(processNameId) {
            return (this.config.ecEligibleProcessNameIds || []).includes(parseInt(processNameId));
        },

        isMachiningEcProcessNameId(processNameId) {
            return (this.config.machiningEcProcessNameIds || []).includes(Number(processNameId));
        },

        processUsesNotes(processNameId) {
            const p =
                (this.config.processNamesData || {})[processNameId] ||
                (this.config.processNamesData || {})[String(processNameId)];

            if (!p) return false;

            return !(Boolean(p.print_form) && String(p.process_sheet_name || '').trim() !== '');
        },

        toggleNotesField(processRow, processNameId) {
            const notesField = processRow?.querySelector('.process-notes-field');
            const notesInput = notesField?.querySelector('input[name="notes"]');
            if (!notesField || !notesInput) return;

            const show = this.processUsesNotes(processNameId);
            notesField.classList.toggle('d-none', !show);
            notesInput.disabled = !show;
        },

        toggleEcCheckbox(processRow, processNameId) {
            const ecContainer =
                (processRow && processRow.querySelector('#ec-checkbox-container')) ||
                document.getElementById('ec-checkbox-container');
            const ecCheckbox = processRow?.querySelector('input[name*="[ec]"]');
            if (!ecContainer || !ecCheckbox) {
                return;
            }

            if (this.isEcEligibleProcess(processNameId)) {
                if (this.isMachiningEcProcessNameId(processNameId)) {
                    ecContainer.style.display = 'none';
                    ecCheckbox.checked = true;
                } else {
                    ecContainer.style.display = 'block';
                }
            } else {
                ecContainer.style.display = 'none';
                ecCheckbox.checked = false;
            }
        },

        onDomReady() {
            const processNameSelect = document.querySelector('[data-edit-process-name]');
            const ecCheckboxContainer = document.getElementById('ec-checkbox-container');
            const ndtPlusContainer = document.querySelector('.ndt-plus-process-container');
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');

            this.initNdtPlusSelect(ndtPlusContainer, ndtPlusSelect);
            this.initPageLoad(processNameSelect, ecCheckboxContainer, ndtPlusContainer, ndtPlusSelect);
            this.bindCheckboxChange();
            this.bindModal();
            this.bindFormSubmit();
        },

        handleNdtContainer(processRow, processNameId, ndtPlusSelect) {
            const ndtPlusContainer = processRow?.querySelector('.ndt-plus-process-container');
            if (!ndtPlusContainer) return;

            if (this.isNdtProcess(processNameId)) {
                ndtPlusContainer.style.display = 'block';
                this.initNdtPlusSelect2(ndtPlusSelect);
                this.restoreNdtOptions(ndtPlusSelect, processNameId);
            } else {
                ndtPlusContainer.style.display = 'none';
                if (ndtPlusSelect && typeof $ !== 'undefined') $(ndtPlusSelect).val(null).trigger('change');
                const hidden = document.getElementById('plus_process_hidden');
                if (hidden) hidden.value = '';
                const opts = processRow?.querySelector('.ndt-plus-process-options');
                if (opts) opts.innerHTML = '';
            }
        },

        initNdtPlusSelect2(ndtPlusSelect) {
            if (!ndtPlusSelect || typeof $ === 'undefined' || !$.fn.select2) return;
            if ($(ndtPlusSelect).hasClass('select2-hidden-accessible')) return;

            const self = this;
            const dropdownParent = (this.config && this.config.dropdownParent) || document.body;
            $(ndtPlusSelect).select2({
                theme: 'bootstrap-5', width: '100%', multiple: true,
                placeholder: 'Select Additional NDT Process(es)',
                dropdownParent: $(dropdownParent)
            }).on('select2:select select2:unselect', function () {
                self.updatePlusProcess();
                self.loadNdtPlusProcesses(this);
            });
        },

        restoreNdtOptions(ndtPlusSelect, processNameId) {
            if (!ndtPlusSelect || typeof $ === 'undefined') return;

            const ndtNames = this.config.ndtProcessNamesData || {};
            const currentOpts = Array.from(ndtPlusSelect.options).map(o => o.value);

            (this.config.ndtProcessNames || []).forEach(ndtId => {
                if (ndtId != processNameId && !currentOpts.includes(String(ndtId))) {
                    const p = ndtNames[ndtId];
                    if (p) {
                        const opt = new Option(p.name, ndtId, false, false);
                        opt.setAttribute('data-process-name', p.name);
                        ndtPlusSelect.add(opt);
                    }
                }
            });

            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
            $(ndtPlusSelect).trigger('change');
        },

        initNdtPlusSelect(ndtPlusContainer, ndtPlusSelect) {
            if (ndtPlusSelect && ndtPlusContainer && ndtPlusContainer.style.display !== 'none') {
                this.initNdtPlusSelect2(ndtPlusSelect);
            }
        },

        initPageLoad(processNameSelect, ecCheckboxContainer, ndtPlusContainer, ndtPlusSelect) {
            if (!processNameSelect?.value) return;

            const processNameId = processNameSelect.value;
            const processRow = processNameSelect.closest('.process-row');
            this.toggleEcCheckbox(processRow, processNameId);
            this.toggleNotesField(processRow, processNameId);

            if (ndtPlusContainer) {
                if (this.isNdtProcess(processNameId)) {
                    ndtPlusContainer.style.display = 'block';
                    this.initNdtPlusSelect2(ndtPlusSelect);
                    if (ndtPlusSelect && typeof $ !== 'undefined') {
                        $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                        $(ndtPlusSelect).trigger('change');
                        const selected = $(ndtPlusSelect).val();
                        if (selected && selected.length) this.loadNdtPlusProcesses(ndtPlusSelect);
                    }
                } else {
                    ndtPlusContainer.style.display = 'none';
                }
            }

            this.loadProcessesForRow(processNameSelect);
        },

        loadProcessesForRow(selectElement) {
            const processNameId = selectElement?.value;
            const processRow = selectElement?.closest('.process-row');
            const processOptionsContainer = processRow?.querySelector('.process-options');
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) return;
            document.querySelector('#editCPForm .process-description-field')?.classList.toggle('d-none', this.isMachiningProcess(processNameId));

            const expandPicker = () => processRow.classList.add('specification-picker-expanded');
            if (!processOptionsContainer.dataset.widePickerBound) {
                processOptionsContainer.dataset.widePickerBound = '1';
                processOptionsContainer.addEventListener('pointerdown', expandPicker);
                processOptionsContainer.addEventListener('focusin', expandPicker);
                processOptionsContainer.addEventListener('change', event => {
                    if (!event.target.matches('input[type="radio"], input[type="checkbox"]')) return;
                    if (event.target.type === 'checkbox') return;
                    processRow.classList.remove('specification-picker-expanded');
                    document.querySelector('#editCPForm [name="description"]')?.focus({preventScroll: true});
                });
            }
            expandPicker();

            const match = selectElement.name?.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            const loadVersion = (this.processLoadVersion || 0) + 1;
            this.processLoadVersion = loadVersion;
            saveButton.disabled = true;
            processOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error || e.message); }))
                .then(data => {
                    if (loadVersion !== this.processLoadVersion) return;
                    this.createSpecificationPicker(processOptionsContainer, processNameId);
                    let hasProcesses = false;

                    const isInitialLoad = !selectElement.dataset.loaded;
                    const currentProcesses = isInitialLoad ? (this.config.currentProcesses || []) : [];
                    if (isInitialLoad) selectElement.dataset.loaded = 'true';

                    if (data.existingProcesses?.length) {
                        const firstId = currentProcesses[0];
                        data.existingProcesses.forEach(process => {
                            const multiple = this.isMachiningProcess(processNameId);
                            const checked = multiple ? currentProcesses.some(id => id == process.id) : process.id == firstId;
                            this.appendSpecification(processOptionsContainer, process, processNameId, rowIndex, checked);
                            hasProcesses = true;
                        });
                    }

                    this.renderSpecificationDescriptions(processOptionsContainer, processNameId);
                    if (hasProcesses) {
                        saveButton.disabled = false;
                    } else {
                        const div = document.createElement('div');
                        div.className = 'text-muted mt-2 edit-spec-empty-catalog';
                        div.textContent = 'No specification. Add specification for this process.';
                        processOptionsContainer.appendChild(div);
                        saveButton.disabled = true;
                    }
                })
                .catch(err => {
                    if (loadVersion !== this.processLoadVersion) return;
                    processOptionsContainer.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
                    saveButton.disabled = true;
            (window.NotificationHandler?.error || window.notifyError)('Error while loading processes');
                });
        },

        updatePlusProcess() {
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');
            const plusHidden = document.getElementById('plus_process_hidden');
            if (!ndtPlusSelect || !plusHidden) return;

            const selected = $(ndtPlusSelect).val() || [];
            plusHidden.value = selected.length ? selected.map(id => parseInt(id)).sort((a, b) => a - b).join(',') : '';
        },

        loadNdtPlusProcesses(selectElement) {
            const selected = typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')
                ? ($(selectElement).val() || []) : Array.from(selectElement?.selectedOptions || []).map(o => o.value);
            const processRow = selectElement?.closest('.process-row');
            const ndtPlusOptionsContainer = processRow?.querySelector('.ndt-plus-process-options');
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;

            if (!ndtPlusOptionsContainer || !selected.length) {
                if (ndtPlusOptionsContainer) ndtPlusOptionsContainer.innerHTML = '';
                this.updatePlusProcess();
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading...</div>';

            Promise.all(selected.map(ndtId =>
                fetch(`${this.config.getProcessesUrl}?processNameId=${ndtId}&manualId=${manualId}`)
                    .then(r => r.json())
                    .then(data => ({ ndtProcessNameId: ndtId, processes: data.existingProcesses || [] }))
            )).then(results => {
                ndtPlusOptionsContainer.innerHTML = '';
                const ndtNames = this.config.ndtProcessNamesData || {};

                results.forEach(({ ndtProcessNameId, processes }) => {
                    if (processes.length) {
                        const label = ndtNames[ndtProcessNameId]?.name || `NDT-${ndtProcessNameId}`;
                        const header = document.createElement('div');
                        header.className = 'fw-bold mt-2';
                        header.textContent = label + ':';
                        ndtPlusOptionsContainer.appendChild(header);

                        processes.forEach(process => {
                            const div = document.createElement('div');
                            div.classList.add('form-check');
                            div.innerHTML = `<input type="checkbox" name="processes[0][ndt_plus_process][]" value="${process.id}" class="form-check-input ndt-plus-process-checkbox" data-ndt-process-name-id="${ndtProcessNameId}"><label class="form-check-label"></label>`;
                            window.renderProcessChoiceLabel(div.querySelector('label'), process);
                            ndtPlusOptionsContainer.appendChild(div);
                        });
                    }
                });
                this.updatePlusProcess();
            }).catch(() => {
                ndtPlusOptionsContainer.innerHTML = '<div class="text-danger">Error loading processes</div>';
            });
        },

        bindCheckboxChange() {
            document.addEventListener('change', (e) => {
                if (e.target.matches('.process-options input[type="radio"]')) this.updatePlusProcess();
            });
        },

        bindModal() {
            const modal = document.getElementById('addProcessModal');
            const saveBtn = document.getElementById('saveProcessModal');
            if (!modal || !saveBtn) return;

            modal.addEventListener('show.bs.modal', (e) => {
                const triggerBtn = e.relatedTarget;
                if (!triggerBtn) return;
                const row = triggerBtn.closest('.process-row');
                const select = row?.querySelector('[data-edit-process-name]');
                const processNameId = (typeof $ !== 'undefined' && select && $(select).hasClass('select2-hidden-accessible'))
                    ? $(select).val() : select?.value;
                if (!processNameId || processNameId === '') {
                    e.preventDefault();
                    (window.NotificationHandler?.warning || window.notifyWarn)('Please select Process Name before adding specification.');
                    return;
                }
                window._editProcessCurrentRow = row;
                const processName = select?.dataset.processName || '';
                document.getElementById('modalProcessName').textContent = processName;
                document.getElementById('modalProcessNameId').value = processNameId;
                document.getElementById('newProcessInput').value = '';
            });

            document.getElementById('processes-container')?.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn[data-bs-target="#addProcessModal"]');
                if (!btn) return;
                const row = btn.closest('.process-row');
                const select = row?.querySelector('[data-edit-process-name]');
                const processNameId = (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible'))
                    ? $(select).val() : select?.value;
                if (!processNameId || processNameId === '') return;
                document.getElementById('modalProcessName').textContent = select?.dataset.processName || '';
                document.getElementById('modalProcessNameId').value = processNameId;
                document.getElementById('newProcessInput').value = '';
                window._editProcessCurrentRow = row;
            });

            saveBtn.addEventListener('click', () => this.saveModalProcess());
        },

        saveModalProcess() {
            const row = window._editProcessCurrentRow || document.querySelector('.process-row');
            const processNameId = document.getElementById('modalProcessNameId')?.value;
            const newProcess = document.getElementById('newProcessInput')?.value?.trim() || '';
            const manualId = document.getElementById('processes-container')?.dataset?.manualId;
            const processOptionsContainer = row?.querySelector('.process-options');
            const saveButton = document.querySelector('button[type="submit"]');

            if (!newProcess) {
                (window.NotificationHandler?.warning || window.notifyWarn)('Please enter the new process name.');
                return;
            }
            if (!processNameId || !manualId || !processOptionsContainer) return;

            const match = row?.querySelector('[data-edit-process-name]')?.name?.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'text-muted';
            loadingDiv.textContent = 'Saving...';
            processOptionsContainer.appendChild(loadingDiv);

            fetch(this.config.processesStoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.config.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    process_names_id: processNameId,
                    process: newProcess,
                    manual_id: manualId
                })
            })
                .then(r => r.json())
                .then(data => {
                    loadingDiv.remove();
                    if (data.success && data.process) {
                        if (!processOptionsContainer.querySelector('.edit-spec-list')) this.createSpecificationPicker(processOptionsContainer, processNameId);
                        this.appendSpecification(processOptionsContainer, data.process, processNameId, rowIndex, true);
                        this.renderSpecificationDescriptions(processOptionsContainer, processNameId);
                        const search = processOptionsContainer.querySelector('input[type="search"]');
                        search.value = ''; search.dispatchEvent(new Event('input'));
                        document.getElementById('newProcessInput').value = '';
                        saveButton.disabled = false;
                        (window.NotificationHandler?.success || window.notifySuccess)('Process added successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('addProcessModal'))?.hide();
                        this.config.currentProcesses = [data.process.id];
                    } else {
                        (window.NotificationHandler?.error || window.notifyError)(data.message || 'Error');
                    }
                })
                .catch(err => {
                    loadingDiv.remove();
                    (window.NotificationHandler?.error || window.notifyError)('Error: ' + (err.message || 'Unknown'));
                });
        },

        bindFormSubmit() {
            const form = document.getElementById('editCPForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                const processRow = document.querySelector('.process-row');
                const checkedRadio = processRow?.querySelector('.process-options input:checked');
                const ndtIds = Array.from(processRow?.querySelectorAll('.ndt-plus-process-options input[type="checkbox"]:checked') || []).map(cb => parseInt(cb.value));

                if (!checkedRadio) {
                    e.preventDefault();
                    (window.NotificationHandler?.warning || window.notifyWarn)('Please select a specification.');
                    return;
                }

                const optionsContainer = processRow?.querySelector('.process-options');
                optionsContainer?.querySelectorAll('.ndt-submit-value').forEach(input => input.remove());
                optionsContainer?.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
                    input.name = 'processes[0][process][]';
                });

                ndtIds.forEach(processId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.className = 'ndt-submit-value';
                    input.name = 'processes[0][process][]';
                    input.value = processId;
                    optionsContainer?.appendChild(input);
                });

                const plusHidden = document.getElementById('plus_process_hidden');
                const ndtPlusSelect = processRow?.querySelector('.select2-ndt-plus');
                if (ndtPlusSelect && plusHidden) {
                    const selected = (typeof $ !== 'undefined' && $(ndtPlusSelect).val()) || [];
                    plusHidden.value = selected.length ? selected.map(id => parseInt(id)).sort((a, b) => a - b).join(',') : '';
                }
            });
        }
    };

    window.TdrProcessEditForm = TdrProcessEditForm;
})(window);
