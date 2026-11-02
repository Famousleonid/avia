(function() {
function initTdrInlineCreate() {
    const row = document.getElementById('tdrInlineCreateRow');
    const addRow = document.getElementById('tdrInlineAddRow');
    const addBtn = document.getElementById('tdrInlineAddBtn');
    const form = document.getElementById('tdrInlineCreateForm');
    const manualPicker = document.getElementById('tdrInlineManualPicker');
    if (!row || !addBtn || !form) return;
    if (row.dataset.inlineCreateInitialized === '1') return;
    row.dataset.inlineCreateInitialized = '1';

    const $ = window.$ || null;
    let activeInlineSelect = null;
    const defaultManualId = window.TdrinspectionConfig1.value2;
    const logCardSerialsByComponent = window.TdrinspectionConfig1.value37;
    const missingConditionId = window.TdrinspectionConfig1.value38;
    const orderNewNecessaryId = window.TdrinspectionConfig1.value39;
    const canManageAllManualParts = window.TdrinspectionConfig1.value0;
    const allowedManualIds = window.TdrinspectionConfig1.value1;
    const allowedManualSet = new Set((allowedManualIds || []).map(function(v) { return parseInt(v, 10); }));

    const manualSelect = document.getElementById('tdr_inline_manual_id');
    const componentSelect = document.getElementById('tdr_inline_component_id');
    const codeSelect = document.getElementById('tdr_inline_codes_id');
    const necessarySelect = document.getElementById('tdr_inline_necessaries_id');
    const orderComponentSelect = document.getElementById('tdr_inline_order_component_id');
    const orderComponentIdInput = document.getElementById('tdr_inline_order_component_id_value');
    const orderComponentAssemblyInput = document.getElementById('tdr_inline_order_component_assembly_id');
    const orderComponentGroup = document.getElementById('tdr_inline_order_component_group');
    const descriptionInput = document.getElementById('tdr_inline_description');
    const qtyInput = document.getElementById('tdr_inline_qty');
    const qtyOriginalParent = qtyInput?.parentElement || null;
    const qtyOriginalNextSibling = qtyInput?.nextSibling || null;
    const orderQtyMount = document.getElementById('tdr_inline_order_qty_mount');
    const orderQtyControl = orderQtyMount?.querySelector('.tdr-inline-qty-control') || null;
    const orderQtyLabel = orderQtyMount?.querySelector('.tdr-inline-qty-label') || null;
    const serialInput = document.getElementById('tdr_inline_serial_number');
    const assySerialInput = document.getElementById('tdr_inline_assy_serial_number');
    const useTdrInput = document.getElementById('tdr_inline_use_tdr');
    const useProcessFormsInput = document.getElementById('tdr_inline_use_process_forms');
    const conditionsInput = document.getElementById('tdr_inline_conditions_id');
    const partsActionsEl = document.getElementById('tdr-inline-parts-actions');
    const addComponentManualId = document.getElementById('addComponentManualId');
    const iplDisplay = document.getElementById('tdr_inline_ipl_display');
    const editImagePreview = document.getElementById('edit_component_image_preview');
    const editImageLink = document.getElementById('edit_component_image_link');
    const editImageThumb = document.getElementById('edit_component_image_thumb');
    const editImageDeleteButton = document.getElementById('edit_component_image_delete');
    let editImageDeleteUrl = '';

    function renderEditComponentImage(image) {
        editImageDeleteUrl = image?.delete_url || '';
        const hasImage = !!(image?.url && image?.thumb_url);
        editImagePreview?.classList.toggle('d-none', !hasImage);
        editImagePreview?.classList.toggle('d-flex', hasImage);
        if (editImageLink) editImageLink.href = hasImage ? image.url : '#';
        if (editImageThumb) editImageThumb.src = hasImage ? image.thumb_url : '';
    }

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

    function selectedComponentOption() {
        return componentSelect?.options[componentSelect.selectedIndex] || null;
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

    function shouldExcludeInlineNpOrderComponents() {
        const codeName = selectedTitle(codeSelect).toLowerCase();
        const necessaryName = selectedTitle(necessarySelect).toLowerCase();
        return codeName === 'missing' || necessaryName === 'order new';
    }

    function renderComponentSelectionText(option) {
        const selectedOption = option?.tagName === 'OPTION' ? option : componentSelect?.options[componentSelect.selectedIndex] || null;
        const partNumber = selectedOption && selectedOption.value ? partNumberFromOption(selectedOption) : '';
        const rendered = document.querySelector('#tdrInlineComponentPicker .select2-selection__rendered');
        if (rendered) {
            const selectionLabel = [selectedOption?.dataset?.ipl, partNumber, selectedOption?.dataset?.title].filter(Boolean).join('\u00a0\u00a0');
            rendered.dataset.partNumber = partNumber ? selectionLabel : '';
            if (partNumber) {
                rendered.title = selectedOption.text.trim();
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
        orderComponentSelect.style.removeProperty('width');
        const container = document.querySelector('#tdr_inline_order_component_group .select2-container');
        if (container) {
            container.style.removeProperty('width');
            container.style.removeProperty('min-width');
        }
        const rendered = document.querySelector('#tdr_inline_order_component_group .select2-selection__rendered');
        if (rendered && text) {
            rendered.textContent = text;
            rendered.title = text;
        }
    }

    function openOrderComponentSelect() {
        if (!orderComponentSelect || orderComponentGroup?.classList.contains('d-none')) return;
        if ($ && $.fn.select2 && $(orderComponentSelect).hasClass('select2-hidden-accessible')) {
            $(orderComponentSelect).select2('open');
            return;
        }
        orderComponentSelect.focus();
        orderComponentSelect.size = Math.min(Math.max(orderComponentSelect.options.length, 2), 8);
    }

    function scheduleOpenOrderComponentSelect() {
        requestAnimationFrame(function() {
            setTimeout(openOrderComponentSelect, 30);
        });
    }

    function orderComponentDisplay(componentLike) {
        const ipl = componentLike?.ipl_num || componentLike?.assy_ipl_num || '';
        const partNumber = componentLike?.part_number || componentLike?.assy_part_number || '';
        const suffix = componentLike?.is_assy ? ' (assy)' : '';
        const name = (componentLike?.name || '') + suffix;
        const left = [ipl, partNumber].filter(Boolean).join(' : ');
        return [left, name].filter(Boolean).join(' - ').trim();
    }

    function componentAssembliesFromOption(option) {
        if (!option || !option.value) return [];
        try {
            const parsed = JSON.parse(option.dataset.assemblies || '[]');
            return Array.isArray(parsed) ? parsed.filter(function(assembly) {
                return assembly && assembly.id;
            }) : [];
        } catch (error) {
            return [];
        }
    }

    function iplSectionKey(ipl) {
        const match = (ipl || '').toString().trim().match(/^(\d+)/);
        return match ? match[1] : '';
    }

    function iplSortKey(ipl) {
        const value = (ipl || '').toString().trim().split(/\r?\n/)[0] || '';
        const match = value.match(/^(\d+)([A-Za-z]*)-(\d+)([A-Za-z0-9]*)$/);
        if (!match) return [1, 0, '', 0, value.toUpperCase()];

        return [0, parseInt(match[1], 10), (match[2] || '').toUpperCase(), parseInt(match[3], 10), (match[4] || '').toUpperCase()];
    }

    function compareIplValues(left, right) {
        const leftKey = iplSortKey(left);
        const rightKey = iplSortKey(right);
        for (let i = 0; i < Math.max(leftKey.length, rightKey.length); i++) {
            if (leftKey[i] < rightKey[i]) return -1;
            if (leftKey[i] > rightKey[i]) return 1;
        }
        return 0;
    }

    function setOrderComponentChoiceFromSelection() {
        if (!orderComponentSelect) return;
        const option = orderComponentSelect.options[orderComponentSelect.selectedIndex];
        if (orderComponentIdInput) orderComponentIdInput.value = option?.dataset?.componentId || '';
        if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = option?.dataset?.assemblyId || '';
    }

    function populateOrderComponentChoices(option) {
        if (!orderComponentSelect) return;
        const selectedOption = option || selectedComponentOption();
        const selectedComponentId = selectedOption?.value || '';
        const selectedSection = iplSectionKey(selectedOption?.dataset?.ipl || '');
        const excludeNp = shouldExcludeInlineNpOrderComponents();

        const previousComponentId = orderComponentIdInput?.value || '';
        const previousAssemblyId = orderComponentAssemblyInput?.value || '';
        orderComponentSelect.replaceChildren(new Option('---', ''));

        Array.from(componentSelect?.options || [])
            .filter(function(componentOption) {
                const componentId = componentOption?.value || '';
                if (!componentId) return false;
                if (excludeNp && componentOption?.dataset?.np === '1') return false;
                if (!selectedSection) return true;

                return iplSectionKey(componentOption?.dataset?.ipl || '') === selectedSection;
            })
            .sort(function(left, right) {
                return compareIplValues(left?.dataset?.ipl || '', right?.dataset?.ipl || '');
            })
            .forEach(function(componentOption) {
            const componentId = componentOption?.value || '';
            if (!componentId) return;
            const componentName = componentOption?.dataset?.title || '';
            const assemblies = componentAssembliesFromOption(componentOption);
            const orderOption = new Option(orderComponentDisplay({
                ipl_num: componentOption?.dataset?.ipl || '',
                part_number: componentOption?.dataset?.partNumber || '',
                name: componentName
            }), 'component:' + componentId);
            orderOption.dataset.ipl = componentOption.dataset.ipl || '';
            orderOption.dataset.partNumber = componentOption.dataset.partNumber || '';
            orderOption.dataset.title = componentName;
            orderOption.dataset.componentId = componentId;
            orderOption.dataset.assemblyId = '';
            orderOption.dataset.np = componentOption?.dataset?.np || '0';
            orderOption.dataset.kit = componentOption?.dataset?.kit || '0';
            orderComponentSelect.appendChild(orderOption);

            assemblies.forEach(function(assembly) {
                const label = orderComponentDisplay({
                    assy_ipl_num: assembly.assy_ipl_num || '',
                    assy_part_number: assembly.assy_part_number || '',
                    name: componentName,
                    is_assy: true
                });
                if (!label) return;
                const assemblyOption = new Option(label, 'assembly:' + (assembly.id || ''));
                assemblyOption.dataset.ipl = assembly.assy_ipl_num || '';
                assemblyOption.dataset.partNumber = assembly.assy_part_number || '';
                assemblyOption.dataset.title = componentName + ' (assy)';
                assemblyOption.dataset.componentId = componentId;
                assemblyOption.dataset.assemblyId = assembly.id || '';
                assemblyOption.dataset.isAssy = '1';
                assemblyOption.dataset.np = componentOption?.dataset?.np || '0';
                assemblyOption.dataset.kit = componentOption?.dataset?.kit || '0';
                orderComponentSelect.appendChild(assemblyOption);
            });
        });

        const orderOptions = Array.from(orderComponentSelect.options);
        const selectedComponentOrderOptions = orderOptions.filter(function(item) {
            return selectedComponentId && (item.dataset.componentId || '') === selectedComponentId;
        });
        const selectedComponentHasAssyChoice = selectedComponentOrderOptions.some(function(item) {
            return (item.dataset.isAssy || '') === '1';
        });

        const optionToRestore = previousAssemblyId
            ? orderOptions.find(function(item) {
                return (item.dataset.assemblyId || '') === previousAssemblyId;
            })
            : orderOptions.find(function(item) {
                return previousComponentId && (item.dataset.componentId || '') === previousComponentId && !(item.dataset.assemblyId || '');
            }) || (!selectedComponentHasAssyChoice ? orderOptions.find(function(item) {
                return selectedComponentId && (item.dataset.componentId || '') === selectedComponentId && !(item.dataset.assemblyId || '');
            }) : null);

        orderComponentSelect.value = optionToRestore ? optionToRestore.value : '';
        if (optionToRestore) {
            optionToRestore.selected = true;
        }
        if ($ && $.fn.select2) {
            $(orderComponentSelect).trigger('change.select2');
        }
        setOrderComponentChoiceFromSelection();
        updateOrderComponentWidth();
    }

    function moveQtyInputToOrderGroup(isOrderNew) {
        if (!qtyInput || !qtyOriginalParent) return;

        if (isOrderNew && orderQtyControl && qtyInput.parentElement !== orderQtyControl) {
            orderQtyControl.appendChild(qtyInput);
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

    function positionComponentDropdown() {
        const activeSelect = [componentSelect, orderComponentSelect].find(function(select) {
            return $(select).data('select2')?.isOpen();
        });
        if (!activeSelect) return;
        const container = $(activeSelect).data('select2')?.$dropdown?.find('.select2-dropdown').parent()[0];
        if (!container) return;
        const bounds = container.getBoundingClientRect();
        const left = Math.max(8, Math.min(bounds.left, window.innerWidth - bounds.width - 8));
        container.style.left = (left + window.scrollX) + 'px';
    }

    function renderInlinePartOption(data) {
        if (!data.id || !data.element) return data.text;
        const option = data.element;
        return $('<span>').addClass('tdr-part-option-columns')
            .attr('title', option.dataset.title || '')
            .append($('<span>').text(option.dataset.ipl || '—'))
            .append($('<span>').text(partNumberFromOption(option)))
            .append($('<span>').text(option.dataset.title || '—'));
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
            if (select === componentSelect || select === orderComponentSelect) {
                const position = () => requestAnimationFrame(positionComponentDropdown);
                const instance = $select.data('select2');
                instance.$dropdown.find('.select2-dropdown').addClass('tdr-inline-part-dropdown')
                    .parent().addClass('tdr-inline-part-dropdown-host');
                instance.on('results:all', position);
                instance.on('results:append', position);
                window.addEventListener('resize', position);
                window.addEventListener('scroll', position, true);
            }
        }

        initSelect(manualSelect);
        initSelect(orderComponentSelect, { templateResult: renderInlinePartOption });

        initSelect(componentSelect, {
            templateResult: renderInlinePartOption,
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
            ? (componentOption.dataset.title || ("" + String(window.TdrinspectionConfig1.value3) + ""))
            : ("" + String(window.TdrinspectionConfig1.value4) + "");
        const partText = componentOption && componentOption.value
            ? (componentOption.dataset.partNumber || ("" + String(window.TdrinspectionConfig1.value5) + ""))
            : ("" + String(window.TdrinspectionConfig1.value6) + "");
        const serial = serialInput?.value || '';
        const code = selectedTitle(codeSelect) || ("" + String(window.TdrinspectionConfig1.value7) + "");
        const necessary = selectedTitle(necessarySelect) || ("" + String(window.TdrinspectionConfig1.value8) + "");

        function setPlaceholder(step, text) {
            const placeholder = inlineCell(step)?.querySelector('.tdr-inline-placeholder');
            if (placeholder) placeholder.textContent = text;
        }

        setPlaceholder('component', partText);
        setPlaceholder('code', code);
        setPlaceholder('necessary', necessary);
        setPlaceholder('serial', serial || ("" + String(window.TdrinspectionConfig1.value9) + ""));
        setPlaceholder('description', description);
    }

    function syncRepairSerialFromLogCard() {
        if (!serialInput) return;

        const isRepair = selectedTitle(necessarySelect).toLowerCase() === 'repair';
        const componentId = (componentSelect?.value || '').toString();
        const candidate = (logCardSerialsByComponent[componentId] || '').toString();
        const previousAuto = serialInput.dataset.autoLogCardSerial || '';
        const current = serialInput.value.trim();

        if (!isRepair) {
            if (previousAuto && current === previousAuto) serialInput.value = '';
            serialInput.dataset.autoLogCardSerial = '';
            return;
        }

        if (candidate && (!current || current === previousAuto)) {
            serialInput.value = candidate;
        }
        serialInput.dataset.autoLogCardSerial = candidate;
    }

    function syncDescriptionFromComponent(option) {
        if (!descriptionInput) return;
        const nextDescription = option && option.value ? (option.dataset.title || '') : '';
        const previousAutoDescription = descriptionInput.dataset.autoComponentDescription || '';
        const currentDescription = descriptionInput.value || '';
        const canReplace = !currentDescription || currentDescription === previousAutoDescription;
        if (canReplace) {
            descriptionInput.value = nextDescription;
        }
        descriptionInput.dataset.autoComponentDescription = nextDescription;
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
        syncDescriptionFromComponent(selectedOption);
        populateOrderComponentChoices(selectedOption);
        updateFieldVisibility();
        updatePlaceholders();
    }

    function resetInlineRow() {
        form.reset();
        // Resetting the same manual must not reload a catalogue already in the DOM.
        manualSelect.value = defaultManualId;
        if ($ && $.fn.select2) $(manualSelect).trigger('change.select2');
        if (componentSelect.dataset.loadedManual !== String(defaultManualId)) loadComponentsByManual(defaultManualId);
        setSelectValue(componentSelect, '');
        setSelectValue(codeSelect, '');
        setSelectValue(necessarySelect, '');
        setSelectValue(orderComponentSelect, '');
        if (orderComponentIdInput) orderComponentIdInput.value = '';
        if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = '';
        populateOrderComponentChoices(null);
        useTdrInput.value = '0';
        useProcessFormsInput.value = '0';
        conditionsInput.value = '';
        if (descriptionInput) {
            descriptionInput.value = '';
            descriptionInput.dataset.autoComponentDescription = '';
        }
        descriptionInput?.classList.add('d-none');
        qtyInput?.classList.add('d-none');
        document.getElementById('tdr_inline_repair_qty_label')?.classList.add('d-none');
        row.classList.remove('tdr-inline-repair-row');
        orderComponentGroup?.classList.add('d-none');
        orderQtyMount?.classList.add('d-none');
        orderQtyLabel?.classList.add('d-none');
        setOrderCellSpan(false);
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

    function setAddButtonVisible(isVisible) {
        addBtn.textContent = ("" + String(window.TdrinspectionConfig1.value10) + "");
        addRow?.classList.toggle('d-none', !isVisible);
    }

    function closeInlineRow() {
        resetInlineRow();
        row.classList.add('d-none');
        form.classList.add('d-none');
        manualPicker?.classList.add('d-none');
        setAddButtonVisible(true);
    }

    function isInlineCreateClickTarget(target) {
        if (!target) return false;
        const closest = typeof target.closest === 'function' ? target.closest.bind(target) : function() { return null; };
        return row.contains(target)
            || addBtn.contains(target)
            || form.contains(target)
            || !!manualPicker?.contains(target)
            || !!closest('.select2-container--open, .select2-dropdown, .modal');
    }

    function setOrderCellSpan(showOrderPicker) {
        inlineCell('serial')?.classList.toggle('d-none', showOrderPicker);
        const descriptionCell = inlineCell('description');
        if (descriptionCell) descriptionCell.colSpan = showOrderPicker ? 2 : 1;
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
        const showOrderPicker = hasComponent && (isMissing || isOrderNew);
        setOrderCellSpan(showOrderPicker);
        const showDescriptionInput = hasComponent && !showOrderPicker && (isManufacture || hasNecessary);
        const isRepair = necessaryName === 'repair';
        row.classList.toggle('tdr-inline-repair-row', isRepair && hasComponent);
        const repairQtyLabel = document.getElementById('tdr_inline_repair_qty_label');
        repairQtyLabel?.classList.toggle('d-none', !(isRepair || isManufacture) || !hasComponent);
        if (repairQtyLabel && isRepair) repairQtyLabel.title = 'Maximum: ' + Math.max(1, parseInt(componentOption?.dataset.unitsAssy, 10) || 1);
        const showQty = hasComponent && (isRepair || (isManufacture && !showOrderPicker) || showOrderPicker);
        if (qtyInput) {
            if (isRepair) qtyInput.max = String(Math.max(1, parseInt(componentOption?.dataset.unitsAssy, 10) || 1));
            else qtyInput.removeAttribute('max');
            qtyInput.title = isRepair ? 'QTY (maximum ' + qtyInput.max + ')' : 'QTY';
        }
        const showSerial = hasNecessary && !isOrderNew;

        if (showOrderPicker) {
            populateOrderComponentChoices(componentOption);
        }

        setInlineCellVisible('code', hasComponent);
        setInlineCellVisible('necessary', !!showNecessary);
        setInlineCellVisible('serial', !!showSerial || showOrderPicker);
        setInlineCellVisible('description', !!showOrderPicker || !!showDescriptionInput);

        if (showDescriptionInput) {
            revealField(inlineCell('description'));
        } else if (showOrderPicker && descriptionInput) {
            descriptionInput.value = '';
            descriptionInput.dataset.autoComponentDescription = '';
            if (serialInput) serialInput.value = '';
            if (assySerialInput) assySerialInput.value = '';
        }
        descriptionInput?.classList.toggle('d-none', !showDescriptionInput);
        qtyInput?.classList.toggle('d-none', !showQty);
        orderComponentGroup?.classList.toggle('d-none', !showOrderPicker);
        orderQtyMount?.classList.toggle('d-none', !showOrderPicker);
        orderQtyLabel?.classList.toggle('d-none', !showOrderPicker);
        moveQtyInputToOrderGroup(showOrderPicker);
        if ((isRepair || isManufacture) && hasComponent && qtyInput) {
            const qtyField = document.getElementById('tdr_inline_repair_qty_field');
            qtyField?.classList.remove('d-none');
            qtyField?.appendChild(qtyInput);
            qtyInput.style.maxWidth = '90px';
        }
        serialInput?.classList.toggle('d-none', showOrderPicker || !showSerial);
        assySerialInput?.classList.toggle('d-none', !(showSerial && hasAssy && !isRepair));
        if (showOrderPicker && !orderComponentSelect?.value) {
            scheduleOpenOrderComponentSelect();
        }

        if (!showOrderPicker) {
            if (orderComponentSelect) orderComponentSelect.size = 0;
            setSelectValue(orderComponentSelect, '');
            if (orderComponentIdInput) orderComponentIdInput.value = '';
            if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = '';
        }
        if (!showOrderPicker && !showDescriptionInput) {
            setInlineCellVisible('description', false);
        }

        syncRepairSerialFromLogCard();
        updatePlaceholders();
    }

    let componentLoadSequence = 0;

    function loadComponentsByManual(manualId) {
        if (!manualId || !$) return;
        const requestSequence = ++componentLoadSequence;
        componentSelect.dataset.loadedManual = '';
        $(componentSelect).empty().append(("<option value=\"\">" + String(window.TdrinspectionConfig1.value11) + "</option>"));
        $(orderComponentSelect).empty().append(("<option value=\"\">" + String(window.TdrinspectionConfig1.value12) + "</option>"));
        setSelectValue(componentSelect, '');
        setSelectValue(orderComponentSelect, '');
        if (orderComponentIdInput) orderComponentIdInput.value = '';
        if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = '';

        $.ajax({
            url: ("" + String(window.TdrinspectionConfig1.value13) + ""),
            method: 'GET',
            data: { manual_id: manualId, workorder_id: window.TdrinspectionConfig1.value14, _token: ("" + String(window.TdrinspectionConfig1.value15) + "") },
            success: function(response) {
                const selectedManualId = manualSelect?.value || defaultManualId;
                if (requestSequence !== componentLoadSequence || String(selectedManualId) !== String(manualId)) return;

                const components = (response.components || [])
                    .filter(function(component) { return String(component.manual_id) === String(manualId); });
                componentSelect.dataset.loadedManual = String(manualId);
                $(componentSelect).empty().append('<option value="">---</option>');
                $(orderComponentSelect).empty().append('<option value="">---</option>');
                components.forEach(function(component) {
                    const assemblies = Array.isArray(component.assemblies) ? component.assemblies : [];
                    const hasAssy = (component.assy_part_number || assemblies.length) ? 'true' : 'false';
                    const title = component.name || '';
                    const ipl = component.ipl_num || '';
                    const partNumber = component.part_number || '';
                    $(componentSelect).append(
                        $('<option>')
                            .val(component.id)
                            .text(ipl + ' : ' + partNumber + ' - ' + title)
                            .attr('data-has_assy', hasAssy)
                            .attr('data-title', title)
                            .attr('data-ipl', ipl)
                            .attr('data-part-number', partNumber)
                            .attr('data-assy-part-number', component.assy_part_number || '')
                            .attr('data-assy-ipl', component.assy_ipl_num || '')
                            .attr('data-units-assy', component.units_assy || '')
                            .attr('data-np', component.np ? '1' : '0')
                            .attr('data-kit', component.kit ? '1' : '0')
                            .attr('data-assemblies', JSON.stringify(assemblies))
                    );
                });
                setSelectValue(componentSelect, '');
                setSelectValue(orderComponentSelect, '');
                if (orderComponentIdInput) orderComponentIdInput.value = '';
                if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = '';
                populateOrderComponentChoices(null);
                updatePlaceholders();
            },
            error: function(_xhr, status) {
                if (status === 'abort' || requestSequence !== componentLoadSequence) return;
                $(componentSelect).empty().append('<option value="">---</option>');
                $(orderComponentSelect).empty().append('<option value="">---</option>');
                setSelectValue(componentSelect, '');
                setSelectValue(orderComponentSelect, '');
                populateOrderComponentChoices(null);
                updatePlaceholders();
            }
        });
    }

    function refreshInlineTdrTableRows() {
        return fetch(window.tdrShowFragmentsUrl, {
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
                if (!doc.getElementById('tdrPartsIndicators') || !doc.querySelector('#tdr_process_Table tbody')) {
                    throw new Error('The TDR list response is incomplete.');
                }
                const freshIndicators = doc.getElementById('tdrPartsIndicators');
                const currentIndicators = document.getElementById('tdrPartsIndicators');
                const freshRows = Array.from(doc.querySelectorAll('#tdr_process_Table tbody tr'))
                    .filter(function(tableRow) {
                        return tableRow.id !== 'tdrInlineCreateRow' && tableRow.id !== 'tdrInlineAddRow';
                    });
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

                replaceModalFromFreshDocument(("missingModal" + String(window.TdrinspectionConfig1.value16) + ""));
                replaceModalFromFreshDocument(("orderModal" + String(window.TdrinspectionConfig1.value17) + ""));
                if (typeof window.syncTdrPrlPaperGroupFromDocument === 'function') {
                    window.syncTdrPrlPaperGroupFromDocument(doc);
                }

                if (freshIndicators && currentIndicators) {
                    currentIndicators.replaceWith(document.importNode(freshIndicators, true));
                }

                if (!currentBody || !currentInlineRow) return;

                Array.from(currentBody.querySelectorAll('tr'))
                    .filter(function(tableRow) {
                        return tableRow.id !== 'tdrInlineCreateRow' && tableRow.id !== 'tdrInlineAddRow';
                    })
                    .forEach(function(tableRow) { tableRow.remove(); });

                freshRows.forEach(function(tableRow) {
                    currentBody.insertBefore(document.importNode(tableRow, true), currentInlineRow);
                });
            });
    }

    function keepInlineRowVisible() {
        if (row.classList.contains('d-none')) return;
        const scroller = row.closest('.tdr-show-right-table-wrapper');
        if (!scroller) return;

        const viewport = scroller.getBoundingClientRect();
        const bounds = row.getBoundingClientRect();
        const headerHeight = row.closest('table')?.tHead?.getBoundingClientRect().height || 0;
        const visibleTop = Math.max(0, viewport.top + scroller.clientTop + headerHeight) + 8;
        let visibleBottom = Math.min(window.innerHeight, viewport.top + scroller.clientTop + scroller.clientHeight) - 8;
        const assistant = document.querySelector('#aiAssistantWidget .ai-widget-toggle');
        if (assistant && assistant.getClientRects().length) {
            const overlay = assistant.getBoundingClientRect();
            if (overlay.left < bounds.right && overlay.right > bounds.left && overlay.bottom > visibleTop) {
                visibleBottom = Math.min(visibleBottom, overlay.top - 8);
            }
        }

        // Only reveal the clipped portion inside this scroller; never move the page or scroll horizontally.
        if (bounds.height > visibleBottom - visibleTop) {
            scroller.scrollTop += bounds.top - visibleTop;
        } else if (bounds.bottom > visibleBottom) {
            scroller.scrollTop += bounds.bottom - visibleBottom;
        } else if (bounds.top < visibleTop) {
            scroller.scrollTop += bounds.top - visibleTop;
        }
    }

    window.addEventListener('resize', function() {
        requestAnimationFrame(keepInlineRowVisible);
    });

    addBtn.addEventListener('click', function() {
        row.classList.remove('d-none');
        form.classList.remove('d-none');
        manualPicker?.classList.remove('d-none');
        setAddButtonVisible(false);
        if (!initInlineSelects()) {
            setTimeout(initInlineSelects, 50);
        }
        resetInlineRow();
        initInlineSelects();
        requestAnimationFrame(keepInlineRowVisible);
    });

    document.addEventListener('click', function(event) {
        if (row.classList.contains('d-none')) return;
        if (isInlineCreateClickTarget(event.target)) return;
        closeInlineRow();
    });

    row.querySelectorAll('[data-tdr-inline-cell]').forEach(function(cell) {
        cell.addEventListener('click', function(event) {
            if (event.target.closest('select, input, textarea, button, a, .select2-container')) return;
            showField(cell, event);
        });
    });

    componentSelect?.addEventListener('change', function() {
        if (serialInput) {
            serialInput.value = '';
            serialInput.dataset.autoLogCardSerial = '';
        }
        if (assySerialInput) assySerialInput.value = '';
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

    if ($ && $.fn.select2 && orderComponentSelect) {
        $(orderComponentSelect).on('select2:select', function() {
            setOrderComponentChoiceFromSelection();
            updateOrderComponentWidth();
        });
        $(orderComponentSelect).on('select2:clear', function() {
            setOrderComponentChoiceFromSelection();
            updateOrderComponentWidth();
        });
    }

    if ($ && $.fn.select2) {
        $(manualSelect)
            .add(componentSelect)
            .add(orderComponentSelect)
            .on('select2:opening', function() {
                const nextSelect = this;
                [manualSelect, componentSelect, orderComponentSelect].forEach(function(select) {
                    if (select !== nextSelect && $(select).data('select2')?.isOpen()) {
                        $(select).select2('close');
                    }
                });
            })
            .on('select2:open', function() {
                activeInlineSelect = this;
                document.body.classList.add('tdr-inline-select2-dropdown-open');
                document.body.classList.toggle('tdr-inline-component-select2-open', this === componentSelect);
                document.body.classList.toggle('tdr-inline-order-select2-open', this === orderComponentSelect);
                const instance = $(this).data('select2');
                if (this === componentSelect || this === orderComponentSelect) {
                    const dropdown = instance?.$dropdown;
                    if (dropdown && !dropdown.find('.tdr-part-option-heading').length) {
                        $('<div>').addClass('tdr-part-option-columns tdr-part-option-heading')
                            .attr('aria-hidden', 'true')
                            .append($('<span>').text('IPL'), $('<span>').text('P/N'), $('<span>').text('Part name'))
                            .insertBefore(dropdown.find('.select2-results'));
                    }
                    requestAnimationFrame(positionComponentDropdown);
                }
                const searchContainer = instance?.$dropdown?.find('.select2-search--dropdown')[0];
                const searchField = instance?.$dropdown?.find('.select2-search__field')[0];
                searchContainer?.classList.remove('select2-search--hide');
                if (searchContainer) searchContainer.style.display = 'block';
                if (searchField) {
                    searchField.style.display = 'block';
                    searchField.focus();
                }
            })
            .on('select2:close', function() {
                if (activeInlineSelect !== this) return;
                activeInlineSelect = null;
                document.body.classList.remove('tdr-inline-select2-dropdown-open');
                document.body.classList.remove('tdr-inline-component-select2-open');
                document.body.classList.remove('tdr-inline-order-select2-open');
            });
    }

    function handleCodeChange() {
        setSelectValue(necessarySelect, '');
        updateFieldVisibility();
    }

    codeSelect?.addEventListener('change', handleCodeChange);

    necessarySelect?.addEventListener('change', updateFieldVisibility);
    orderComponentSelect?.addEventListener('change', function() {
        setOrderComponentChoiceFromSelection();
        updateOrderComponentWidth();
    });
    serialInput?.addEventListener('input', updatePlaceholders);

    function handleInlineManualChange() {
        const manualId = manualSelect.value || defaultManualId;
        if (addComponentManualId) addComponentManualId.value = manualId;
        updatePartsActions(manualId);
        loadComponentsByManual(manualId);
        updatePlaceholders();
    }

    if ($ && manualSelect) {
        $(manualSelect)
            .off('change.tdrInlineManual')
            .on('change.tdrInlineManual', handleInlineManualChange);
    } else {
        manualSelect?.addEventListener('change', handleInlineManualChange);
    }

    document.getElementById('tdr-inline-edit-part-btn')?.addEventListener('click', function(event) {
        event.preventDefault();
        const componentId = componentSelect?.value;
        if (!componentId) {
            (window.tdrShowNotify || function(m) { console.log(m); })('Select part first.', 'warning');
            return;
        }
        if (!$) return;
        const url = ("" + String(window.TdrinspectionConfig1.value18) + "").replace('__ID__', componentId);
        $.get(url, function(response) {
            if (!response.success) {
                (window.tdrShowNotify || function(m) { console.error(m); })('Failed to load part data.', 'error');
                return;
            }
            const c = response.component;
            renderEditComponentImage(c.image);
            $('#edit_name').val(c.name);
            $('#edit_ipl_num').val(c.ipl_num);
            $('#edit_part_number').val(c.part_number);
            $('#edit_assy_ipl_num').val(c.assy_ipl_num);
            $('#edit_assy_part_number').val(c.assy_part_number);
            $('#edit_units_assy').val(c.units_assy);
            $('#edit_log_card').prop('checked', c.log_card);
            $('#edit_is_bush').prop('checked', c.is_bush);
            if (c.is_bush) {
                $('#edit_bush_ipl_container').show();
                $('#edit_bush_ipl_num').val(c.bush_ipl_num);
            } else {
                $('#edit_bush_ipl_container').hide();
                $('#edit_bush_ipl_num').val('');
            }
            $('#editComponentForm').attr('action', ("" + String(window.TdrinspectionConfig1.value19) + "").replace('__ID__', componentId));
            $('#editComponentModal').modal('show');
        }).fail(function() {
            (window.tdrShowNotify || function(m) { console.error(m); })('Error loading part.', 'error');
        });
    });

    editImageDeleteButton?.addEventListener('click', async function() {
        if (!editImageDeleteUrl) return;
        if (typeof window.confirmDialog !== 'function') {
            (window.tdrShowNotify || function(m) { console.error(m); })(("" + String(window.TdrinspectionConfig1.value20) + ""), 'error');
            return;
        }

        const confirmed = await window.confirmDialog({
            title: ("" + String(window.TdrinspectionConfig1.value21) + ""),
            message: ("" + String(window.TdrinspectionConfig1.value22) + ""),
            okText: ("" + String(window.TdrinspectionConfig1.value23) + ""),
            cancelText: ("" + String(window.TdrinspectionConfig1.value24) + ""),
            danger: true,
        });
        if (!confirmed) return;

        editImageDeleteButton.disabled = true;
        try {
            const response = await fetch(editImageDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ("" + String(window.TdrinspectionConfig1.value25) + ""),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(function() { return {}; });
            if (!response.ok || data.success !== true) {
                throw new Error(data.message || ("" + String(window.TdrinspectionConfig1.value26) + ""));
            }
            renderEditComponentImage(null);
            (window.tdrShowNotify || function(m) { console.log(m); })(("" + String(window.TdrinspectionConfig1.value27) + ""), 'success');
            await refreshInlineTdrTableRows();
        } catch (error) {
            (window.tdrShowNotify || function(m) { console.error(m); })(error.message || ("" + String(window.TdrinspectionConfig1.value28) + ""), 'error');
        } finally {
            editImageDeleteButton.disabled = false;
        }
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
        const submitBtn = form.querySelector('button[type="submit"]') || document.querySelector('button[form="tdrInlineCreateForm"][type="submit"]');
        if (submitBtn?.disabled) {
            event.preventDefault();
            return;
        }
        const codeName = selectedTitle(codeSelect).toLowerCase();
        const necessaryName = selectedTitle(necessarySelect).toLowerCase();

        if (!componentSelect?.value) {
            event.preventDefault();
            (window.tdrShowNotify || function(m) { console.warn(m); })(("" + String(window.TdrinspectionConfig1.value29) + ""), 'warning');
            return;
        }

        if (!codeName) {
            event.preventDefault();
            (window.tdrShowNotify || function(m) { console.warn(m); })(("" + String(window.TdrinspectionConfig1.value30) + ""), 'warning');
            return;
        }

        useTdrInput.value = '0';
        useProcessFormsInput.value = '0';
        conditionsInput.value = '';

        if (codeName === 'manufacture') {
            setSelectValue(necessarySelect, '');
            setSelectValue(orderComponentSelect, '');
            if (orderComponentIdInput) orderComponentIdInput.value = '';
            if (orderComponentAssemblyInput) orderComponentAssemblyInput.value = '';
        } else if (codeName === 'missing') {
            useTdrInput.value = '0';
            useProcessFormsInput.value = '0';
            if (descriptionInput) descriptionInput.value = '';
            if (serialInput) serialInput.value = '';
            if (assySerialInput) assySerialInput.value = '';
            if (orderNewNecessaryId) setSelectValue(necessarySelect, orderNewNecessaryId);
            // conditions_id резолвит сервер (Missing → PARTS MISSING по имени).
        } else if (codeName && necessaryName === 'order new') {
            useTdrInput.value = '1';
            useProcessFormsInput.value = '0';
            if (descriptionInput) descriptionInput.value = '';
            if (serialInput) serialInput.value = '';
            if (assySerialInput) assySerialInput.value = '';
            // conditions_id резолвит сервер по имени кода (без магических id/дефолта 39).
        } else if (codeName) {
            useTdrInput.value = '1';
            useProcessFormsInput.value = '1';
        }

        setOrderComponentChoiceFromSelection();

        if ((codeName === 'missing' || necessaryName === 'order new') && !orderComponentIdInput?.value) {
            event.preventDefault();
            (window.tdrShowNotify || function(m) { console.warn(m); })(("" + String(window.TdrinspectionConfig1.value31) + ""), 'warning');
            return;
        }


        event.preventDefault();

        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
            const dots = document.createElement('span');
            dots.className = 'tdr-inline-saving-dots';
            dots.setAttribute('role', 'status');
            dots.setAttribute('aria-label', 'Saving');
            for (let i = 0; i < 3; i++) {
                const dot = document.createElement('span');
                dot.className = 'app-busy-dot';
                dot.setAttribute('aria-hidden', 'true');
                dots.appendChild(dot);
            }
            submitBtn.replaceChildren(dots);
        }

        fetch(form.action, {
            spinner: false,
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ("" + String(window.TdrinspectionConfig1.value33) + "")
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
                    const message = result.data.message || result.data.errors || ("" + String(window.TdrinspectionConfig1.value34) + "");
                    throw new Error(typeof message === 'string' ? message : JSON.stringify(message));
                }

                return refreshInlineTdrTableRows().then(function() {
                    closeInlineRow();
                    (window.tdrShowNotify || function(m) { console.log(m); })(result.data.message || ("" + String(window.TdrinspectionConfig1.value35) + ""), 'success');
                });
            })
            .catch(function(error) {
                (window.tdrShowNotify || function(m) { console.error(m); })(error.message || ("" + String(window.TdrinspectionConfig1.value36) + ""), 'error');
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.removeAttribute('aria-busy');
                    submitBtn.innerHTML = originalText;
                }
            });
    });

    document.getElementById('tab-tdr')?.addEventListener('hide.bs.tab', closeInlineRow);

    componentSelect.dataset.loadedManual = String(defaultManualId);
    initInlineSelects();
    resetInlineRow();
    setAddButtonVisible(true);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTdrInlineCreate);
} else {
    initTdrInlineCreate();
}
})();
