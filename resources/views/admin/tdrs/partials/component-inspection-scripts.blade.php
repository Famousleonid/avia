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
            if (defaultManualId) {
                $('#i_manual_id').val(defaultManualId).trigger('change');
                $('#addComponentManualId').val(defaultManualId);
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
                width: '100%'
            });
            $('#i_manual_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%'
            });
            $('#order_component_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                dropdownParent: $dropdownParent,
                width: '100%'
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
                if (!componentId) { (typeof showNotification === 'function' ? (m) => showNotification(m, 'warning') : (window.NotificationHandler?.warning || alert))('Select part first.'); return; }
                const url = '{{ route("components.showJson", ["component" => "__ID__"]) }}'.replace('__ID__', componentId);
                $.get(url, function(response) {
                    if (!response.success) { (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))('Failed to load part data.'); return; }
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
                }).fail(function() { (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))('Error loading part.'); });
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
