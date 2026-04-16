{{-- Component Inspection form scripts (from component-inspection) --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const compModal = document.getElementById('componentInspectionModal');
    if (!compModal) return;

    compModal.addEventListener('shown.bs.modal', function() {
        if (window.$) $(compModal).off('focusin.bs.modal');
    });

    const initComponentInspection = function() {
        if (window.$ && window.$.fn.select2) {
            const $ = window.$;
            const defaultManualId = {{ $manual_id }};

            const canManageAllManualParts = {!! json_encode($canManageAllManualParts ?? false) !!};
            const allowedManualIds = {!! json_encode($allowedManualIds ?? []) !!};
            const allowedManualSet = new Set((allowedManualIds || []).map(v => parseInt(v, 10)));

            const partsActionsEl = document.getElementById('js-parts-actions');

            // Hide immediately to avoid "appear then disappear" flicker.
            if (partsActionsEl) {
                partsActionsEl.classList.add('d-none');
            }

            const updatePartsActions = function (manualId) {
                if (!partsActionsEl) return;
                const mid = parseInt(manualId, 10);
                const allowed = canManageAllManualParts || (Number.isFinite(mid) && allowedManualSet.has(mid));
                partsActionsEl.classList.toggle('d-none', !allowed);
            };

            if (defaultManualId) {
                $('#i_manual_id').val(defaultManualId).trigger('change');
                $('#addComponentManualId').val(defaultManualId);
                updatePartsActions(defaultManualId);
            }

            var $dropdownParent = $(compModal);
            $('#i_component_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%',
                minimumResultsForSearch: 0
            });
            $('#codes_id, #necessaries_id, #c_conditions_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%',
                minimumResultsForSearch: 0
            });
            $('#i_manual_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%',
                minimumResultsForSearch: 0
            });
            $('#order_component_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%',
                minimumResultsForSearch: 0
            });

            function hideAllGroups() {
                $('#necessary').hide();
                $('#order_component_group').hide();
                $('#description_group').hide();
                $('#qty').hide();
                $('#sns-group').hide();
                $('#conditions').hide();
            }

            function updateFieldVisibility() {
                const codeName = ($('#codes_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();
                const necessaryName = ($('#necessaries_id option:selected').attr('data-title') || '').toString().trim();
                const hasAssy = $('#i_component_id option:selected').data('has_assy') === true;

                if (!codeName) { hideAllGroups(); return; }

                const isManufacture = codeName === 'manufacture';
                const showNecessary = codeName && codeName !== 'missing' && !isManufacture;

                if (isManufacture) {
                    $('#necessary').hide();
                    $('#description_group').show();
                    $('#qty').show();
                } else {
                    $('#qty').toggle(codeName === 'missing' || necessaryName.toLowerCase() === 'order new');
                    $('#necessary').toggle(showNecessary);
                    $('#description_group').toggle(showNecessary);
                }

                if (codeName && !isManufacture && codeName !== 'missing' && necessaryName && necessaryName.toLowerCase() !== 'order new') {
                    $('#sns-group').show();
                    $('#serial_number').parent().show();
                    $('#assy_serial_number').parent().toggle(hasAssy);
                } else {
                    $('#sns-group').hide();
                }

                if (necessaryName.toLowerCase() === 'order new') {
                    $('#order_component_group').show();
                    $('#order_component_id').val($('#i_component_id').val()).trigger('change');
                } else {
                    $('#order_component_group').hide();
                    $('#order_component_id').val('').trigger('change');
                }
            }

            $('#i_component_id').on('change', function() {
                $('#codes_id').val(null).trigger('change');
                $('#necessaries_id').val(null).trigger('change');
                hideAllGroups();
            });
            $('#codes_id').on('change', function() {
                updateFieldVisibility();
                $('#necessaries_id').val(null).trigger('change');
            });
            $('#necessaries_id').on('change', updateFieldVisibility);
            hideAllGroups();

            $('#editComponentBtn').on('click', function(e) {
                e.preventDefault();
                const componentId = $('#i_component_id').val();
                if (!componentId) { (window.tdrShowNotify || function(m) { console.log(m); })('Select part first.', 'warning'); return; }
                const url = '{{ route("components.showJson", ["component" => "__ID__"]) }}'.replace('__ID__', componentId);
                $.get(url, function(response) {
                    if (!response.success) { (window.tdrShowNotify || function(m) { console.error(m); })('Failed to load part data.', 'error'); return; }
                    const c = response.component;
                    $('#edit_name').val(c.name);
                    $('#edit_ipl_num').val(c.ipl_num);
                    $('#edit_part_number').val(c.part_number);
                    $('#edit_assy_ipl_num').val(c.assy_ipl_num);
                    $('#edit_assy_part_number').val(c.assy_part_number);
                    $('#edit_eff_code').val(c.eff_code);
                    $('#edit_units_assy').val(c.units_assy);
                    $('#edit_log_card').prop('checked', c.log_card);
                    $('#edit_repair').prop('checked', c.repair);
                    $('#edit_is_bush').prop('checked', c.is_bush);
                    if (c.is_bush) {
                        $('#edit_bush_ipl_container').show();
                        $('#edit_bush_ipl_num').val(c.bush_ipl_num);
                    } else {
                        $('#edit_bush_ipl_container').hide();
                        $('#edit_bush_ipl_num').val('');
                    }
                    $('#editComponentForm').attr('action', '{{ route("components.updateFromInspection", ["component" => "__ID__"]) }}'.replace('__ID__', componentId));
                    $('#editComponentModal').modal('show');
                }).fail(function() { (window.tdrShowNotify || function(m) { console.error(m); })('Error loading part.', 'error'); });
            });

            $('#is_bush').on('change', function() {
                if (this.checked) {
                    $('#bush_ipl_container').show();
                    $('#bush_ipl_num').prop('required', true);
                } else {
                    $('#bush_ipl_container').hide();
                    $('#bush_ipl_num').prop('required', false).val('');
                }
            });
            $('#edit_is_bush').on('change', function() {
                if (this.checked) {
                    $('#edit_bush_ipl_container').show();
                } else {
                    $('#edit_bush_ipl_container').hide();
                    $('#edit_bush_ipl_num').val('');
                }
            });

            $('#i_manual_id').on('change', function() {
                const manualId = $(this).val() || {{ $manual_id }};
                $('#addComponentManualId').val(manualId);
                if (manualId) loadComponentsByManual(manualId);
                updatePartsActions(manualId);
            });

            function loadComponentsByManual(manualId) {
                $.ajax({
                    url: '{{ route("api.get-components-by-manual") }}',
                    method: 'GET',
                    data: { manual_id: manualId, _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        $('#i_component_id').empty().append('<option value="">---</option>');
                        $('#order_component_id').empty().append('<option value="">---</option>');
                        response.components.forEach(function(component) {
                            $('#i_component_id').append(
                                '<option value="' + component.id + '" data-has_assy="' + (component.assy_part_number ? 'true' : 'false') + '" data-title="' + component.name + '">' +
                                component.ipl_num + ' : ' + component.part_number + ' - ' + component.name + '</option>'
                            );
                            const displayPartNumber = component.assy_part_number || component.part_number;
                            $('#order_component_id').append(
                                '<option value="' + component.id + '">' + displayPartNumber + ' - ' + component.name + ' (' + component.ipl_num + ')</option>'
                            );
                        });
                        $('#i_component_id').trigger('change');
                        $('#order_component_id').trigger('change');
                    }
                });
            }

            $('#createForm').on('submit', function(e) {
                const codeName = ($('#codes_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();
                const necessaryName = ($('#necessaries_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();

                function setHiddenInput(name, value) {
                    let $input = $('#createForm').find('input[name="' + name + '"]');
                    if ($input.length) $input.val(value);
                    else $('<input>').attr({ type: 'hidden', name: name, value: value }).appendTo('#createForm');
                }

                setHiddenInput('component_id', $('#i_component_id').val());

                if (codeName === 'manufacture') {
                    $('#necessaries_id').val('');
                    $('#order_component_id').val('');
                } else if (codeName === 'missing') {
                    setHiddenInput('use_tdr', '0');
                    setHiddenInput('use_process_forms', '0');
                    setHiddenInput('necessaries_id', '2');
                    setHiddenInput('conditions_id', '1');
                } else if (codeName !== 'missing' && necessaryName === 'order new') {
                    setHiddenInput('use_tdr', '1');
                    setHiddenInput('use_process_forms', '0');
                    setHiddenInput('order_component_id', $('#order_component_id').val());
                    let conditionId = '39';
                    $('#c_conditions_id option').each(function() {
                        const condName = ($(this).attr('data-title') || '').toString().trim().toLowerCase();
                        if (condName === codeName) { conditionId = $(this).val(); return false; }
                    });
                    setHiddenInput('conditions_id', conditionId);
                } else if (codeName !== 'missing' && necessaryName !== 'order new') {
                    setHiddenInput('use_tdr', '1');
                    setHiddenInput('use_process_forms', '1');
                }
            });
        }
    };

    compModal.addEventListener('shown.bs.modal', function() {
        if (!window.$componentInspectionInit) {
            initComponentInspection();
            window.$componentInspectionInit = true;
        }
    });
});
</script>
<script>
(function() {
function initTdrInlineCreate() {
    const row = document.getElementById('tdrInlineCreateRow');
    const addBtn = document.getElementById('tdrInlineAddBtn');
    const form = document.getElementById('tdrInlineCreateForm');
    const manualPicker = document.getElementById('tdrInlineManualPicker');
    if (!row || !addBtn || !form) return;
    if (row.dataset.inlineCreateInitialized === '1') return;
    row.dataset.inlineCreateInitialized = '1';

    const $ = window.$ || null;
    const defaultManualId = {{ $manual_id }};
    const missingConditionId = @json($missingCondition?->id);
    const orderNewNecessaryId = @json($necessary?->id);
    const canManageAllManualParts = {!! json_encode($canManageAllManualParts ?? false) !!};
    const allowedManualIds = {!! json_encode($allowedManualIds ?? []) !!};
    const allowedManualSet = new Set((allowedManualIds || []).map(function(v) { return parseInt(v, 10); }));

    const manualSelect = document.getElementById('tdr_inline_manual_id');
    const componentSelect = document.getElementById('tdr_inline_component_id');
    const codeSelect = document.getElementById('tdr_inline_codes_id');
    const necessarySelect = document.getElementById('tdr_inline_necessaries_id');
    const orderComponentSelect = document.getElementById('tdr_inline_order_component_id');
    const orderComponentGroup = document.getElementById('tdr_inline_order_component_group');
    const descriptionInput = document.getElementById('tdr_inline_description');
    const qtyInput = document.getElementById('tdr_inline_qty');
    const qtyOriginalParent = qtyInput?.parentElement || null;
    const qtyOriginalNextSibling = qtyInput?.nextSibling || null;
    const orderQtyMount = document.getElementById('tdr_inline_order_qty_mount');
    const assySerialInput = document.getElementById('tdr_inline_assy_serial_number');
    const useTdrInput = document.getElementById('tdr_inline_use_tdr');
    const useProcessFormsInput = document.getElementById('tdr_inline_use_process_forms');
    const conditionsInput = document.getElementById('tdr_inline_conditions_id');
    const partsActionsEl = document.getElementById('tdr-inline-parts-actions');
    const addComponentManualId = document.getElementById('addComponentManualId');
    const iplDisplay = document.getElementById('tdr_inline_ipl_display');

    function selectedTitle(select) {
        if (!select) return '';
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) return '';
        return (option?.dataset?.title || option?.text || '').toString().trim();
    }

    function selectedPartNumber(select) {
        if (!select) return '';
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) return '';
        return partNumberFromOption(option);
    }

    function partNumberFromOption(option) {
        if (!option) return '';
        const text = (option.dataset?.partNumber || option.text || '').toString().trim();
        return text.split(' - ')[0].split(' : ').pop().trim();
    }

    function componentOptionText(option) {
        if (!option || !option.value) return option?.text || '';
        const ipl = option.dataset.ipl || '';
        const partNumber = option.dataset.partNumber || option.text || '';
        const title = option.dataset.title || '';
        return [ipl, partNumber].filter(Boolean).join(' : ') + (title ? ' - ' + title : '');
    }

    function renderComponentSelectionText(option) {
        const selectedOption = option || componentSelect?.options[componentSelect.selectedIndex] || null;
        const partNumber = selectedOption && selectedOption.value ? partNumberFromOption(selectedOption) : '';
        const rendered = document.querySelector('#tdrInlineComponentPicker .select2-selection__rendered');
        if (rendered) {
            rendered.dataset.partNumber = partNumber;
            if (partNumber) {
                rendered.title = partNumber;
            } else {
                rendered.removeAttribute('title');
            }
        }
    }

    function scheduleRenderComponentSelectionText() {
        renderComponentSelectionText();
        requestAnimationFrame(renderComponentSelectionText);
        setTimeout(renderComponentSelectionText, 50);
    }

    function updateOrderComponentWidth() {
        if (!orderComponentSelect) return;
        const option = orderComponentSelect.options[orderComponentSelect.selectedIndex];
        const text = (option?.text || '').toString().trim();
        const partNumber = text.split(' - ')[0].trim();
        const width = partNumber ? Math.min(Math.max(partNumber.length + 2, 8), 18) + 'ch' : '8ch';
        orderComponentSelect.style.width = width;
        const container = document.querySelector('#tdr_inline_order_component_group .select2-container');
        if (container) {
            container.style.width = width;
        }
        const rendered = document.querySelector('#tdr_inline_order_component_group .select2-selection__rendered');
        if (rendered && partNumber) {
            rendered.textContent = partNumber;
            rendered.title = partNumber;
        }
    }

    function moveQtyInputToOrderGroup(isOrderNew) {
        if (!qtyInput || !qtyOriginalParent) return;

        if (isOrderNew && orderQtyMount && qtyInput.parentElement !== orderQtyMount) {
            orderQtyMount.appendChild(qtyInput);
            qtyInput.style.maxWidth = '8ch';
            return;
        }

        if (!isOrderNew && qtyInput.parentElement !== qtyOriginalParent) {
            qtyOriginalParent.insertBefore(qtyInput, qtyOriginalNextSibling);
            qtyInput.style.maxWidth = '90px';
        }
    }

    function setSelectValue(select, value) {
        if (!select) return;
        select.value = value || '';
        if ($ && $.fn.select2) {
            $(select).val(value || '').trigger('change.select2');
        }
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function updatePartsActions(manualId) {
        if (!partsActionsEl) return;
        const mid = parseInt(manualId, 10);
        const allowed = canManageAllManualParts || (Number.isFinite(mid) && allowedManualSet.has(mid));
        partsActionsEl.classList.toggle('d-none', !allowed);
    }

    function showField(cell, event) {
        if (!cell || cell.classList.contains('tdr-inline-cell-disabled')) return;
        event?.preventDefault();
        event?.stopPropagation();
        cell.querySelector('.tdr-inline-placeholder')?.classList.add('d-none');
        const field = cell.querySelector('.tdr-inline-field');
        field?.classList.remove('d-none');
        const focusable = field?.querySelector('select, input, textarea');
        if (!focusable) return;
        focusable.focus();
    }

    function revealField(cell) {
        if (!cell || cell.classList.contains('tdr-inline-cell-disabled')) return;
        cell.querySelector('.tdr-inline-placeholder')?.classList.add('d-none');
        cell.querySelector('.tdr-inline-field')?.classList.remove('d-none');
    }

    function inlineCell(step) {
        return row.querySelector('[data-inline-step="' + step + '"]');
    }

    function setInlineCellVisible(step, visible) {
        const cell = inlineCell(step);
        if (!cell) return;
        cell.classList.toggle('tdr-inline-cell-disabled', !visible);
        if (!visible) {
            cell.querySelector('.tdr-inline-field')?.classList.add('d-none');
            cell.querySelector('.tdr-inline-placeholder')?.classList.add('d-none');
            return;
        }
        cell.querySelector('.tdr-inline-placeholder')?.classList.add('d-none');
        cell.querySelector('.tdr-inline-field')?.classList.remove('d-none');
    }

    function setInlinePlaceholderVisible(step, visible) {
        const cell = inlineCell(step);
        if (!cell) return;
        cell.classList.toggle('tdr-inline-cell-disabled', !visible);
        cell.querySelector('.tdr-inline-placeholder')?.classList.toggle('d-none', !visible);
        cell.querySelector('.tdr-inline-field')?.classList.add('d-none');
    }

    function initInlineSelects() {
        if (!$ || !$.fn.select2) return false;
        const dropdownParent = $(document.body);

        function initSelect(select, options) {
            if (!select) return;
            const $select = $(select);
            if ($select.hasClass('select2-hidden-accessible')) return;
            $select.select2(Object.assign({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                minimumResultsForSearch: 0,
                dropdownParent: dropdownParent,
                width: '100%'
            }, options || {}));
        }

        [
            manualSelect,
            orderComponentSelect
        ].forEach(function(select) {
            initSelect(select);
        });

        initSelect(componentSelect, {
            templateResult: function(data) {
                return componentOptionText(data.element);
            },
            templateSelection: function(data) {
                if (!data.id) return data.text;
                return partNumberFromOption(data.element) || data.text;
            }
        });

        scheduleRenderComponentSelectionText();
        updateOrderComponentWidth();
        return true;
    }

    function updatePlaceholders() {
        const componentOption = componentSelect?.options[componentSelect.selectedIndex];
        if (iplDisplay) {
            iplDisplay.textContent = componentOption && componentOption.value
                ? (componentOption.dataset.ipl || '')
                : '';
        }
        scheduleRenderComponentSelectionText();
        updateOrderComponentWidth();
        const description = componentOption && componentOption.value
            ? (componentOption.dataset.title || '{{ __("Description") }}')
            : '{{ __("Description") }}';
        const partText = componentOption && componentOption.value
            ? (componentOption.dataset.partNumber || '{{ __("P/N") }}')
            : '{{ __("P/N") }}';
        const serial = document.getElementById('tdr_inline_serial_number')?.value || '';
        const code = selectedTitle(codeSelect) || '{{ __("Click code") }}';
        const necessary = selectedTitle(necessarySelect) || '{{ __("Click necessary") }}';

        function setPlaceholder(step, text) {
            const placeholder = inlineCell(step)?.querySelector('.tdr-inline-placeholder');
            if (placeholder) placeholder.textContent = text;
        }

        setPlaceholder('component', partText);
        setPlaceholder('code', code);
        setPlaceholder('necessary', necessary);
        setPlaceholder('serial', serial || '{{ __("S/N") }}');
        setPlaceholder('description', description);
    }

    function applyComponentSelection(option) {
        const selectedOption = option || componentSelect?.options[componentSelect.selectedIndex] || null;
        if (selectedOption && selectedOption.value && componentSelect.value !== selectedOption.value) {
            componentSelect.value = selectedOption.value;
        }
        if (iplDisplay) {
            iplDisplay.textContent = selectedOption && selectedOption.value
                ? (selectedOption.dataset.ipl || '')
                : '';
        }
        renderComponentSelectionText(selectedOption);
        updateFieldVisibility();
        updatePlaceholders();
    }

    function resetInlineRow() {
        form.reset();
        setSelectValue(manualSelect, defaultManualId);
        setSelectValue(componentSelect, '');
        setSelectValue(codeSelect, '');
        setSelectValue(necessarySelect, '');
        setSelectValue(orderComponentSelect, '');
        useTdrInput.value = '0';
        useProcessFormsInput.value = '0';
        conditionsInput.value = '';
        descriptionInput?.classList.add('d-none');
        qtyInput?.classList.add('d-none');
        orderComponentGroup?.classList.add('d-none');
        moveQtyInputToOrderGroup(false);
        assySerialInput?.classList.add('d-none');
        row.querySelectorAll('.tdr-inline-field').forEach(function(field) { field.classList.add('d-none'); });
        row.querySelectorAll('.tdr-inline-placeholder').forEach(function(placeholder) { placeholder.classList.remove('d-none'); });
        setInlineCellVisible('code', false);
        setInlineCellVisible('necessary', false);
        setInlineCellVisible('serial', false);
        setInlineCellVisible('description', false);
        document.getElementById('tdrInlineComponentPicker')?.classList.remove('d-none');
        updatePartsActions(defaultManualId);
        updatePlaceholders();
    }

    function setAddButtonOpenState(isOpen) {
        addBtn.textContent = isOpen ? '{{ __("Cancel") }}' : '{{ __("Add") }}';
        addBtn.classList.toggle('btn-outline-info', !isOpen);
        addBtn.classList.toggle('btn-outline-secondary', isOpen);
    }

    function closeInlineRow() {
        resetInlineRow();
        row.classList.add('d-none');
        form.classList.add('d-none');
        manualPicker?.classList.add('d-none');
        setAddButtonOpenState(false);
    }

    function findConditionIdForCode(codeName) {
        let conditionId = '39';
        const options = @json($component_conditions->map(fn($condition) => ['id' => $condition->id, 'name' => $condition->name])->values());
        options.some(function(condition) {
            if ((condition.name || '').toString().trim().toLowerCase() === codeName) {
                conditionId = condition.id;
                return true;
            }
            return false;
        });
        return conditionId;
    }

    function updateFieldVisibility() {
        const codeName = selectedTitle(codeSelect).toLowerCase();
        const necessaryName = selectedTitle(necessarySelect).toLowerCase();
        const componentOption = componentSelect?.options[componentSelect.selectedIndex];
        const hasAssy = componentOption?.dataset?.has_assy === 'true';
        const hasComponent = !!componentSelect?.value;
        const isManufacture = codeName === 'manufacture';
        const isMissing = codeName === 'missing';
        const isOrderNew = necessaryName === 'order new';
        const showNecessary = hasComponent && codeName && !isMissing && !isManufacture;
        const hasNecessary = showNecessary && necessaryName;
        const showDescriptionInput = hasComponent && (isManufacture || hasNecessary);
        const showQty = hasComponent && (isManufacture || isMissing || isOrderNew);
        const showSerial = hasNecessary && !isOrderNew;

        setInlineCellVisible('code', hasComponent);
        setInlineCellVisible('necessary', !!showNecessary);
        setInlinePlaceholderVisible('description', hasComponent);
        setInlineCellVisible('serial', !!showSerial);

        if (showDescriptionInput) {
            revealField(inlineCell('description'));
        }
        descriptionInput?.classList.toggle('d-none', !showDescriptionInput);
        qtyInput?.classList.toggle('d-none', !showQty);
        orderComponentGroup?.classList.toggle('d-none', !isOrderNew);
        moveQtyInputToOrderGroup(isOrderNew);
        assySerialInput?.classList.toggle('d-none', !(showSerial && hasAssy));

        if (isOrderNew && componentSelect?.value && !orderComponentSelect?.value) {
            setSelectValue(orderComponentSelect, componentSelect.value);
        }
        if (!isOrderNew) {
            setSelectValue(orderComponentSelect, '');
        }

        updatePlaceholders();
    }

    function loadComponentsByManual(manualId) {
        if (!manualId || !$) return;
        $.ajax({
            url: '{{ route("api.get-components-by-manual") }}',
            method: 'GET',
            data: { manual_id: manualId, _token: '{{ csrf_token() }}' },
            success: function(response) {
                const components = response.components || [];
                $(componentSelect).empty().append('<option value="">---</option>');
                $(orderComponentSelect).empty().append('<option value="">---</option>');
                components.forEach(function(component) {
                    const hasAssy = component.assy_part_number ? 'true' : 'false';
                    const title = component.name || '';
                    const ipl = component.ipl_num || '';
                    const partNumber = component.part_number || '';
                    $(componentSelect).append(
                        '<option value="' + component.id + '" data-has_assy="' + hasAssy + '" data-title="' + title + '" data-ipl="' + ipl + '" data-part-number="' + partNumber + '">' +
                        ipl + ' : ' + partNumber + ' - ' + title + '</option>'
                    );
                    $(orderComponentSelect).append(
                        '<option value="' + component.id + '">' + (component.assy_part_number || partNumber) + ' - ' + title + ' (' + ipl + ')</option>'
                    );
                });
                setSelectValue(componentSelect, '');
                setSelectValue(orderComponentSelect, '');
                updatePlaceholders();
            }
        });
    }

    function refreshInlineTdrTableRows() {
        return fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(function(html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const freshInspectRows = Array.from(doc.querySelectorAll('#tdr_inspect_Table tbody tr'));
                const currentInspectBody = document.querySelector('#tdr_inspect_Table tbody');
                const freshRows = Array.from(doc.querySelectorAll('#tdr_process_Table tbody tr'))
                    .filter(function(tableRow) { return tableRow.id !== 'tdrInlineCreateRow'; });
                const currentBody = document.querySelector('#tdr_process_Table tbody');
                const currentInlineRow = document.getElementById('tdrInlineCreateRow');

                function replaceModalFromFreshDocument(modalId) {
                    const freshModal = doc.getElementById(modalId);
                    const currentModal = document.getElementById(modalId);
                    if (!freshModal || !currentModal) return;
                    const instance = window.bootstrap?.Modal?.getInstance(currentModal);
                    if (instance) instance.dispose();
                    currentModal.replaceWith(document.importNode(freshModal, true));
                }

                replaceModalFromFreshDocument('missingModal{{ $current_wo->number }}');
                replaceModalFromFreshDocument('orderModal{{ $current_wo->number }}');

                if (currentInspectBody) {
                    currentInspectBody.replaceChildren();
                    freshInspectRows.forEach(function(tableRow) {
                        currentInspectBody.appendChild(document.importNode(tableRow, true));
                    });
                }

                if (!currentBody || !currentInlineRow) return;

                Array.from(currentBody.querySelectorAll('tr'))
                    .filter(function(tableRow) { return tableRow.id !== 'tdrInlineCreateRow'; })
                    .forEach(function(tableRow) { tableRow.remove(); });

                freshRows.forEach(function(tableRow) {
                    currentBody.insertBefore(document.importNode(tableRow, true), currentInlineRow);
                });
            });
    }

    addBtn.addEventListener('click', function() {
        if (!row.classList.contains('d-none')) {
            closeInlineRow();
            return;
        }

        row.classList.remove('d-none');
        form.classList.remove('d-none');
        manualPicker?.classList.remove('d-none');
        setAddButtonOpenState(true);
        if (!initInlineSelects()) {
            setTimeout(initInlineSelects, 50);
        }
        resetInlineRow();
        initInlineSelects();
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    row.querySelectorAll('[data-tdr-inline-cell]').forEach(function(cell) {
        cell.addEventListener('click', function(event) {
            if (event.target.closest('select, input, textarea, button, a, .select2-container')) return;
            showField(cell, event);
        });
    });

    componentSelect?.addEventListener('change', function() {
        setSelectValue(codeSelect, '');
        setSelectValue(necessarySelect, '');
        setSelectValue(orderComponentSelect, '');
        applyComponentSelection();
    });

    if ($ && $.fn.select2 && componentSelect) {
        $(componentSelect).on('select2:select', function(event) {
            applyComponentSelection(event.params?.data?.element || null);
        });
        $(componentSelect).on('select2:clear', function() {
            applyComponentSelection(null);
        });
    }

    if ($ && $.fn.select2) {
        $(manualSelect)
            .add(componentSelect)
            .add(orderComponentSelect)
            .on('select2:open', function() {
                document.body.classList.add('tdr-inline-select2-dropdown-open');
                document.body.classList.toggle('tdr-inline-component-select2-open', this === componentSelect);
                const searchContainer = document.querySelector('.select2-container--open .select2-search--dropdown');
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                searchContainer?.classList.remove('select2-search--hide');
                if (searchContainer) searchContainer.style.display = 'block';
                if (searchField) {
                    searchField.style.display = 'block';
                    searchField.focus();
                }
            })
            .on('select2:close', function() {
                document.body.classList.remove('tdr-inline-select2-dropdown-open');
                document.body.classList.remove('tdr-inline-component-select2-open');
            });
    }

    function handleCodeChange() {
        setSelectValue(necessarySelect, '');
        updateFieldVisibility();
    }

    codeSelect?.addEventListener('change', handleCodeChange);

    necessarySelect?.addEventListener('change', updateFieldVisibility);
    orderComponentSelect?.addEventListener('change', updateOrderComponentWidth);
    document.getElementById('tdr_inline_serial_number')?.addEventListener('input', updatePlaceholders);

    manualSelect?.addEventListener('change', function() {
        const manualId = manualSelect.value || defaultManualId;
        if (addComponentManualId) addComponentManualId.value = manualId;
        updatePartsActions(manualId);
        loadComponentsByManual(manualId);
        updatePlaceholders();
    });

    document.getElementById('tdr-inline-edit-part-btn')?.addEventListener('click', function(event) {
        event.preventDefault();
        const componentId = componentSelect?.value;
        if (!componentId) {
            (window.tdrShowNotify || function(m) { console.log(m); })('Select part first.', 'warning');
            return;
        }
        if (!$) return;
        const url = '{{ route("components.showJson", ["component" => "__ID__"]) }}'.replace('__ID__', componentId);
        $.get(url, function(response) {
            if (!response.success) {
                (window.tdrShowNotify || function(m) { console.error(m); })('Failed to load part data.', 'error');
                return;
            }
            const c = response.component;
            $('#edit_name').val(c.name);
            $('#edit_ipl_num').val(c.ipl_num);
            $('#edit_part_number').val(c.part_number);
            $('#edit_assy_ipl_num').val(c.assy_ipl_num);
            $('#edit_assy_part_number').val(c.assy_part_number);
            $('#edit_eff_code').val(c.eff_code);
            $('#edit_units_assy').val(c.units_assy);
            $('#edit_log_card').prop('checked', c.log_card);
            $('#edit_repair').prop('checked', c.repair);
            $('#edit_is_bush').prop('checked', c.is_bush);
            if (c.is_bush) {
                $('#edit_bush_ipl_container').show();
                $('#edit_bush_ipl_num').val(c.bush_ipl_num);
            } else {
                $('#edit_bush_ipl_container').hide();
                $('#edit_bush_ipl_num').val('');
            }
            $('#editComponentForm').attr('action', '{{ route("components.updateFromInspection", ["component" => "__ID__"]) }}'.replace('__ID__', componentId));
            $('#editComponentModal').modal('show');
        }).fail(function() {
            (window.tdrShowNotify || function(m) { console.error(m); })('Error loading part.', 'error');
        });
    });

    document.getElementById('is_bush')?.addEventListener('change', function() {
        const container = document.getElementById('bush_ipl_container');
        const input = document.getElementById('bush_ipl_num');
        if (!container || !input) return;
        if (this.checked) {
            container.style.display = '';
            input.required = true;
        } else {
            container.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    });

    document.getElementById('edit_is_bush')?.addEventListener('change', function() {
        const container = document.getElementById('edit_bush_ipl_container');
        const input = document.getElementById('edit_bush_ipl_num');
        if (!container || !input) return;
        if (this.checked) {
            container.style.display = '';
        } else {
            container.style.display = 'none';
            input.value = '';
        }
    });

    form.addEventListener('submit', function(event) {
        const codeName = selectedTitle(codeSelect).toLowerCase();
        const necessaryName = selectedTitle(necessarySelect).toLowerCase();

        useTdrInput.value = '0';
        useProcessFormsInput.value = '0';
        conditionsInput.value = '';

        if (codeName === 'manufacture') {
            setSelectValue(necessarySelect, '');
            setSelectValue(orderComponentSelect, '');
        } else if (codeName === 'missing') {
            useTdrInput.value = '0';
            useProcessFormsInput.value = '0';
            if (orderNewNecessaryId) setSelectValue(necessarySelect, orderNewNecessaryId);
            conditionsInput.value = missingConditionId || '';
        } else if (codeName && necessaryName === 'order new') {
            useTdrInput.value = '1';
            useProcessFormsInput.value = '0';
            if (componentSelect?.value && !orderComponentSelect?.value) {
                setSelectValue(orderComponentSelect, componentSelect.value);
            }
            conditionsInput.value = findConditionIdForCode(codeName);
        } else if (codeName) {
            useTdrInput.value = '1';
            useProcessFormsInput.value = '1';
        }

        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]') || document.querySelector('button[form="tdrInlineCreateForm"][type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '{{ __("Saving...") }}';
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            credentials: 'same-origin'
        })
            .then(function(response) {
                return response.json().catch(function() { return {}; }).then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (!result.ok || result.data.success !== true) {
                    const message = result.data.message || result.data.errors || '{{ __("Failed to save.") }}';
                    throw new Error(typeof message === 'string' ? message : JSON.stringify(message));
                }

                return refreshInlineTdrTableRows().then(function() {
                    closeInlineRow();
                    (window.tdrShowNotify || function(m) { console.log(m); })(result.data.message || '{{ __("Saved.") }}', 'success');
                });
            })
            .catch(function(error) {
                (window.tdrShowNotify || function(m) { console.error(m); })(error.message || '{{ __("Failed to save.") }}', 'error');
            })
            .finally(function() {
                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                } else if (typeof window.hideLoadingSpinner === 'function') {
                    window.hideLoadingSpinner();
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
    });

    document.getElementById('tab-tdr')?.addEventListener('hide.bs.tab', closeInlineRow);

    initInlineSelects();
    resetInlineRow();
    setAddButtonOpenState(false);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTdrInlineCreate);
} else {
    initTdrInlineCreate();
}
})();
</script>
