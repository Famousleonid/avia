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

            document.addEventListener('DOMContentLoaded', () => this.onDomReady());
        },

        isNdtProcess(processNameId) {
            return (this.config.ndtProcessNames || []).includes(parseInt(processNameId));
        },

        isEcEligibleProcess(processNameId) {
            return (this.config.ecEligibleProcessNameIds || []).includes(parseInt(processNameId));
        },

        toggleEcCheckbox(processRow, processNameId) {
            const ecCheckbox = processRow?.querySelector('input[name*="[ec]"]');
            if (ecCheckbox) {
                ecCheckbox.closest('.form-check').style.display = this.isEcEligibleProcess(processNameId) ? 'block' : 'none';
            }
        },

        onDomReady() {
            const processNameSelect = document.querySelector('select[name="processes[0][process_names_id]"]');
            const ecCheckboxContainer = document.getElementById('ec-checkbox-container');
            const ndtPlusContainer = document.querySelector('.ndt-plus-process-container');
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');

            this.initProcessNameSelect(processNameSelect, ecCheckboxContainer, ndtPlusContainer, ndtPlusSelect);
            this.initNdtPlusSelect(ndtPlusContainer, ndtPlusSelect);
            this.initPageLoad(processNameSelect, ecCheckboxContainer, ndtPlusContainer, ndtPlusSelect);
            this.bindCheckboxChange();
            this.bindFormSubmit();
        },

        initProcessNameSelect(processNameSelect, ecCheckboxContainer, ndtPlusContainer, ndtPlusSelect) {
            if (typeof $ === 'undefined' || !$.fn.select2 || !processNameSelect) return;

            const self = this;
            $(processNameSelect).select2({ theme: 'bootstrap-5', width: '100%' })
                .on('select2:select', function (e) {
                    const select = e.target;
                    const processNameId = select.value;
                    const processRow = select.closest('.process-row');

                    self.toggleEcCheckbox(processRow, processNameId);
                    self.handleNdtContainer(processRow, processNameId, ndtPlusSelect);
                    self.loadProcessesForRow(select);
                });
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
            $(ndtPlusSelect).select2({
                theme: 'bootstrap-5', width: '100%', multiple: true,
                placeholder: 'Select Additional NDT Process(es)'
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

            if (ecCheckboxContainer) {
                ecCheckboxContainer.style.display = this.isEcEligibleProcess(processNameId) ? 'block' : 'none';
            }

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

            const match = selectElement.name?.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            processOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            fetch(`${this.config.getProcessesUrl}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(r => r.ok ? r.json() : r.json().then(e => { throw new Error(e.error || e.message); }))
                .then(data => {
                    processOptionsContainer.innerHTML = '';
                    let hasProcesses = false;

                    const isInitialLoad = !selectElement.dataset.loaded;
                    const currentProcesses = isInitialLoad ? (this.config.currentProcesses || []) : [];
                    if (isInitialLoad) selectElement.dataset.loaded = 'true';

                    if (data.existingProcesses?.length) {
                        data.existingProcesses.forEach(process => {
                            const div = document.createElement('div');
                            div.classList.add('form-check');
                            const checked = currentProcesses.includes(process.id);
                            div.innerHTML = `<input type="checkbox" name="processes[${rowIndex}][process][]" value="${process.id}" class="form-check-input" ${checked ? 'checked' : ''}><label class="form-check-label">${process.process}</label>`;
                            processOptionsContainer.appendChild(div);
                            hasProcesses = true;
                        });
                    }

                    if (hasProcesses) {
                        saveButton.disabled = false;
                    } else {
                        const div = document.createElement('div');
                        div.className = 'text-muted mt-2';
                        div.textContent = 'No specification. Add specification for this process.';
                        processOptionsContainer.appendChild(div);
                        saveButton.disabled = true;
                    }
                })
                .catch(err => {
                    processOptionsContainer.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
                    saveButton.disabled = true;
                    (window.NotificationHandler?.error || alert)('Ошибка при загрузке процессов');
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
                            div.innerHTML = `<input type="checkbox" name="processes[0][ndt_plus_process][]" value="${process.id}" class="form-check-input ndt-plus-process-checkbox" data-ndt-process-name-id="${ndtProcessNameId}"><label class="form-check-label">${process.process}</label>`;
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
                if (e.target.matches('.process-options input[type="checkbox"]')) this.updatePlusProcess();
            });
        },

        bindFormSubmit() {
            const form = document.getElementById('editCPForm');
            if (!form) return;

            form.addEventListener('submit', () => {
                const processRow = document.querySelector('.process-row');
                const mainIds = Array.from(processRow?.querySelectorAll('.process-options input[type="checkbox"]:checked') || []).map(cb => parseInt(cb.value));
                const ndtIds = Array.from(processRow?.querySelectorAll('.ndt-plus-process-options input[type="checkbox"]:checked') || []).map(cb => parseInt(cb.value));
                const optionsContainer = processRow?.querySelector('.process-options');

                ndtIds.forEach(processId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'processes[0][process][]';
                    input.value = processId;
                    optionsContainer?.appendChild(input);
                });

                const plusHidden = document.getElementById('plus_process_hidden');
                const ndtPlusSelect = processRow?.querySelector('.select2-ndt-plus');
                if (ndtPlusSelect && plusHidden) {
                    const selected = $(ndtPlusSelect).val() || [];
                    plusHidden.value = selected.length ? selected.map(id => parseInt(id)).sort((a, b) => a - b).join(',') : '';
                }
            });
        }
    };

    window.TdrProcessEditForm = TdrProcessEditForm;
})(window);
