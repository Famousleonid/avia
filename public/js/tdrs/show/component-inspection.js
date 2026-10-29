document.addEventListener('DOMContentLoaded', function() {
    const compModal = document.getElementById('componentInspectionModal');
    if (!compModal) return;

    compModal.addEventListener('shown.bs.modal', function() {
        if (window.$) $(compModal).off('focusin.bs.modal');
    });

    const initComponentInspection = function() {
        if (window.$ && window.$.fn.select2) {
            const $ = window.$;
            const defaultManualId = window.TdrinspectionConfig0.value2;
            // The hidden dialog gets its catalogue only when first opened.
            const sourceSelect = document.getElementById('tdr_inline_component_id');
            if (sourceSelect?.dataset.loadedManual === String(defaultManualId) && !document.getElementById('i_component_id').options[1]) {
                sourceSelect.querySelectorAll('option').forEach(option => {
                    if (option.value) document.getElementById('i_component_id').appendChild(option.cloneNode(true));
                });
            }
            const logCardSerialsByComponent = window.TdrinspectionConfig0.value11;

            const canManageAllManualParts = window.TdrinspectionConfig0.value0;
            const allowedManualIds = window.TdrinspectionConfig0.value1;
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

            function firstIplLine(value) {
                return (value || '').toString().trim().split(/\r?\n/)[0].trim();
            }

            function iplSectionKey(value) {
                const match = firstIplLine(value).match(/^(\d+)/);
                return match ? match[1] : '';
            }

            function iplSortKey(value) {
                const ipl = firstIplLine(value);
                const match = ipl.match(/^(\d+)([A-Za-z]*)-(\d+)([A-Za-z0-9]*)$/);
                if (!match) return [1, 0, '', 0, ipl.toUpperCase()];

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

            function componentAssembliesFromOption(option) {
                try {
                    const parsed = JSON.parse(option?.dataset?.assemblies || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            }

            function componentLikeFromOption(option) {
                return {
                    id: option?.value || '',
                    ipl_num: option?.dataset?.ipl || '',
                    part_number: option?.dataset?.partNumber || '',
                    name: option?.dataset?.title || '',
                    np: option?.dataset?.np === '1',
                    kit: option?.dataset?.kit === '1',
                    assemblies: componentAssembliesFromOption(option)
                };
            }

            function shouldExcludeNpOrderComponents() {
                const codeName = ($('#codes_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();
                const necessaryName = ($('#necessaries_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();
                return codeName === 'missing' || necessaryName === 'order new';
            }

            function appendOrderComponentOption(component) {
                if (shouldExcludeNpOrderComponents() && component.np) return;

                const assemblies = Array.isArray(component.assemblies) ? component.assemblies : [];
                const componentId = component.id || '';
                const componentName = component.name || '';
                const componentText = (component.assy_part_number || component.part_number || '') + ' - ' + componentName + ' (' + (component.ipl_num || '') + ')';

                $('#order_component_id').append($('<option>')
                    .val(componentId)
                    .text(componentText)
                    .attr('data-component-id', componentId)
                    .attr('data-assembly-id', '')
                    .attr('data-ipl', component.ipl_num || '')
                    .attr('data-np', component.np ? '1' : '0')
                    .attr('data-kit', component.kit ? '1' : '0'));

                assemblies.forEach(function(assembly) {
                    const assyPart = assembly.assy_part_number || '';
                    const assyIpl = assembly.assy_ipl_num || '';
                    if (!assyPart && !assyIpl) return;
                    $('#order_component_id').append($('<option>')
                        .val(componentId)
                        .text(assyPart + ' - ' + componentName + ' (' + assyIpl + ') (assy)')
                        .attr('data-component-id', componentId)
                        .attr('data-assembly-id', assembly.id || '')
                        .attr('data-ipl', assyIpl || component.ipl_num || '')
                        .attr('data-np', component.np ? '1' : '0')
                        .attr('data-kit', component.kit ? '1' : '0'));
                });
            }

            function rebuildOrderComponentOptions() {
                const selectedOption = $('#i_component_id option:selected')[0] || null;
                const selectedSection = iplSectionKey(selectedOption?.dataset?.ipl || '');
                const previousValue = $('#order_component_id').val() || '';
                const previousAssemblyId = $('#order_component_assembly_id').val() || '';

                $('#order_component_id').empty().append('<option value="">---</option>');

                Array.from(document.getElementById('i_component_id')?.options || [])
                    .filter(function(option) {
                        if (!option.value) return false;
                        if (!selectedSection) return true;

                        return iplSectionKey(option.dataset?.ipl || '') === selectedSection;
                    })
                    .sort(function(left, right) {
                        const iplCompare = compareIplValues(left.dataset?.ipl || '', right.dataset?.ipl || '');
                        if (iplCompare !== 0) return iplCompare;

                        return (left.dataset?.partNumber || '').localeCompare(right.dataset?.partNumber || '', undefined, { numeric: true });
                    })
                    .forEach(function(option) {
                        appendOrderComponentOption(componentLikeFromOption(option));
                    });

                if (previousAssemblyId) {
                    const restoreAssembly = $('#order_component_id option').filter(function() {
                        return ($(this).attr('data-assembly-id') || '') === previousAssemblyId;
                    }).first();
                    if (restoreAssembly.length) {
                        $('#order_component_id option').prop('selected', false);
                        restoreAssembly.prop('selected', true);
                    }
                } else if (previousValue) {
                    const restoreComponent = $('#order_component_id option').filter(function() {
                        return !($(this).attr('data-assembly-id') || '') && $(this).val() === previousValue;
                    }).first();
                    if (restoreComponent.length) {
                        $('#order_component_id option').prop('selected', false);
                        restoreComponent.prop('selected', true);
                    }
                }

                $('#order_component_id').trigger('change');
            }

            function syncOrderComponentAssembly() {
                $('#order_component_assembly_id').val($('#order_component_id option:selected').attr('data-assembly-id') || '');
            }

            $('#order_component_id').on('change', syncOrderComponentAssembly);

            function hideAllGroups() {
                $('#necessary').hide();
                $('#order_component_group').hide();
                $('#description_group').hide();
                $('#qty').hide();
                $('#sns-group').hide();
                $('#conditions').hide();
            }

            function syncRepairSerialFromLogCard() {
                const input = document.getElementById('serial_number');
                if (!input) return;

                const necessaryName = ($('#necessaries_id option:selected').attr('data-title') || '').toString().trim().toLowerCase();
                const componentId = ($('#i_component_id').val() || '').toString();
                const candidate = (logCardSerialsByComponent[componentId] || '').toString();
                const previousAuto = input.dataset.autoLogCardSerial || '';
                const current = input.value.trim();

                if (necessaryName !== 'repair') {
                    if (previousAuto && current === previousAuto) input.value = '';
                    input.dataset.autoLogCardSerial = '';
                    return;
                }

                if (candidate && (!current || current === previousAuto)) {
                    input.value = candidate;
                }
                input.dataset.autoLogCardSerial = candidate;
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
                    $('#qty').toggle(codeName === 'missing' || ['order new', 'repair'].includes(necessaryName.toLowerCase()));
                    $('input[name="qty"]').attr('min', 1).attr('max', necessaryName.toLowerCase() === 'repair' ? Math.max(1, parseInt($('#i_component_id option:selected').attr('data-units-assy'), 10) || 1) : null);
                    $('#necessary').toggle(showNecessary);
                    $('#description_group').toggle(showNecessary);
                }

                if (codeName && !isManufacture && codeName !== 'missing' && necessaryName && necessaryName.toLowerCase() !== 'order new') {
                    $('#sns-group').show();
                    $('#serial_number').parent().show();
                    $('#assy_serial_number').parent().toggle(hasAssy && necessaryName.toLowerCase() !== 'repair');
                } else {
                    $('#sns-group').hide();
                }

                if (codeName === 'missing' || necessaryName.toLowerCase() === 'order new') {
                    $('#order_component_group').show();
                    rebuildOrderComponentOptions();
                    if (!$('#order_component_id').val()) {
                        $('#order_component_id').val($('#i_component_id').val()).trigger('change');
                    }
                } else {
                    $('#order_component_group').hide();
                    $('#order_component_id').val('').trigger('change');
                }

                syncRepairSerialFromLogCard();
            }

            $('#i_component_id').on('change', function() {
                $('#serial_number, #assy_serial_number').val('');
                document.getElementById('serial_number')?.removeAttribute('data-auto-log-card-serial');
                $('#codes_id').val(null).trigger('change');
                $('#necessaries_id').val(null).trigger('change');
                rebuildOrderComponentOptions();
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
                const url = ("" + String(window.TdrinspectionConfig0.value3) + "").replace('__ID__', componentId);
                $.get(url, function(response) {
                    if (!response.success) { (window.tdrShowNotify || function(m) { console.error(m); })('Failed to load part data.', 'error'); return; }
                    const c = response.component;
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
                    $('#editComponentForm').attr('action', ("" + String(window.TdrinspectionConfig0.value4) + "").replace('__ID__', componentId));
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
                const manualId = $(this).val() || window.TdrinspectionConfig0.value5;
                $('#addComponentManualId').val(manualId);
                if (manualId) loadComponentsByManual(manualId);
                updatePartsActions(manualId);
            });

            let componentLoadSequence = 0;
            if (defaultManualId && !document.getElementById('i_component_id').options[1]) {
                loadComponentsByManual(defaultManualId);
            }

            function loadComponentsByManual(manualId) {
                const requestSequence = ++componentLoadSequence;
                $('#i_component_id').empty().append(("<option value=\"\">" + String(window.TdrinspectionConfig0.value6) + "</option>")).trigger('change');
                $('#order_component_id').empty().append(("<option value=\"\">" + String(window.TdrinspectionConfig0.value7) + "</option>")).trigger('change');

                $.ajax({
                    url: ("" + String(window.TdrinspectionConfig0.value8) + ""),
                    method: 'GET',
                    data: { manual_id: manualId, workorder_id: window.TdrinspectionConfig0.value9, _token: ("" + String(window.TdrinspectionConfig0.value10) + "") },
                    success: function(response) {
                        const selectedManualId = $('#i_manual_id').val() || defaultManualId;
                        if (requestSequence !== componentLoadSequence || String(selectedManualId) !== String(manualId)) return;

                        $('#i_component_id').empty().append('<option value="">---</option>');
                        $('#order_component_id').empty().append('<option value="">---</option>');
                        (response.components || [])
                            .filter(function(component) { return String(component.manual_id) === String(manualId); })
                            .forEach(function(component) {
                            const assemblies = Array.isArray(component.assemblies) ? component.assemblies : [];
                            $('#i_component_id').append($('<option>')
                                .val(component.id)
                                .text((component.ipl_num || '') + ' : ' + (component.part_number || '') + ' - ' + (component.name || ''))
                                .attr('data-has_assy', (component.assy_part_number || assemblies.length) ? 'true' : 'false')
                                .attr('data-title', component.name || '')
                                .attr('data-ipl', component.ipl_num || '')
                                .attr('data-part-number', component.part_number || '')
                                .attr('data-np', component.np ? '1' : '0')
                                .attr('data-kit', component.kit ? '1' : '0')
                                .attr('data-assemblies', JSON.stringify(assemblies)));
                        });
                        $('#i_component_id').trigger('change');
                        rebuildOrderComponentOptions();
                    },
                    error: function(_xhr, status) {
                        if (status === 'abort' || requestSequence !== componentLoadSequence) return;
                        $('#i_component_id').empty().append('<option value="">---</option>').trigger('change');
                        $('#order_component_id').empty().append('<option value="">---</option>').trigger('change');
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
                    setHiddenInput('order_component_id', $('#order_component_id').val());
                    setHiddenInput('order_component_assembly_id', $('#order_component_assembly_id').val());
                    setHiddenInput('necessaries_id', '2');
                    // conditions_id резолвит сервер (Missing → PARTS MISSING по имени).
                } else if (codeName !== 'missing' && necessaryName === 'order new') {
                    setHiddenInput('use_tdr', '1');
                    setHiddenInput('use_process_forms', '0');
                    setHiddenInput('order_component_id', $('#order_component_id').val());
                    setHiddenInput('order_component_assembly_id', $('#order_component_assembly_id').val());
                    // conditions_id резолвит сервер по имени кода (без магических id/дефолта 39).
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
