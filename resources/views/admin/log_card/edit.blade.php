@extends(request()->query('modal') ? 'admin.master-embed' : 'admin.master')

@section('content')
    <style>
        .container { max-width: 1200px; }
        .serial-number-input { min-width: 120px; font-size: 0.875rem; }
        .table td { vertical-align: middle; }
        .component-radio { margin: 0; }
        .align-middle { vertical-align: middle !important; }
        .reason-select { min-width: 150px; }
        .table-scroll-container {
            max-height: 75vh;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        .table-scroll-container thead th {
            position: sticky;
            top: 0;
            background-color: #031e3a;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }
        .table-scroll-container table {
            margin-bottom: 0;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <form id="editForm" method="POST" action="{{ route('log_card.update', $log_card->id) }}">
                @csrf
                @method('PUT')
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}} {{__('Edit Log Card')}}</h4>
                <div>
                    <button type="submit" class="btn btn-success" id="editLogCardSubmitBtn">
                        <i class="fas fa-save"></i> {{ __('Update Log Card') }}
                    </button>
                    @if(request()->query('modal'))
                        <button type="button" class="btn btn-secondary" id="editLogCardCancelBtn">{{ __('Cancel') }}</button>
                    @else
                        <a href="{{ route('log_card.show', $current_wo->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                <input type="hidden" name="component_data" id="component_data_input">

                <div class="table-responsive table-scroll-container">
                    <table class="table table-bordered dir-table table-hover">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th>Part Number / Assy PN</th>
                            <th>Select</th>
                            <th>Serial Number</th>
                            <th>ASSY Serial Number</th>
                            <th>Reason for Remove (Optional)</th>
                            {{--                            <th>Action</th>--}}
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($groupedComponents as $groupIndex => $group)

                            @php
                                // Определяем выбранный в группе компонент (имеющий сохраненные данные)
                                $selectedInGroup = collect($group['components'])->first(function($c){ return !empty($c['existing_data']); });
                                $groupExisting = $selectedInGroup['existing_data'] ?? null;
                                $groupSerial = $groupExisting['serial_number'] ?? '';
                                $groupAssySerial = $groupExisting['assy_serial_number'] ?? '';
                                $groupReason = $groupExisting['reason'] ?? '';
                            @endphp

                            @foreach($group['components'] as $i => $componentData)
                                @php
                                    $component = $componentData['component'];
                                    $existingData = $componentData['existing_data'];

                                    // Определяем, выбран ли этот компонент
                                    $isChecked = $existingData !== null;

                                    // Получаем значения из существующих данных
                                    $serialValue = $existingData['serial_number'] ?? '';
                                    $assySerialValue = $existingData['assy_serial_number'] ?? '';
                                    $reasonValue = $existingData['reason'] ?? '';

                                @endphp

                                <tr>
                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            {{ $component->name }}
                                        </td>
                                    @endif
                                    <td>
                                        {{ $component->part_number }}
                                        @if($component->assy_part_number)
                                            / {{ $component->assy_part_number }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="selected_component[{{ $groupIndex }}]" value="{{ $component->id }}"
                                            {{ $isChecked ? 'checked' : '' }}
                                            data-component-id="{{ $component->id }}"
                                            data-is-checked="{{ $isChecked ? 'true' : 'false' }}">
                                    </td>
                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            <input type="text" class="form-control form-control-sm"
                                                   name="serial_numbers[{{ $groupIndex }}]"
                                                   value="{{ $groupSerial }}"
                                                   placeholder="Serial Number"
                                                   data-component-id="{{ $component->id }}"
                                                   data-serial-value="{{ $groupSerial }}">

                                        </td>
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            @if($component->assy_part_number)
                                                <input type="text" class="form-control form-control-sm"
                                                       name="assy_serial_numbers[{{ $groupIndex }}]"
                                                       value="{{ $groupAssySerial }}"
                                                       placeholder="ASSY Serial Number">
                                            @endif
                                        </td>
                                    @endif

                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            <select class="form-control form-control-sm reason-select" name="reasons[{{ $groupIndex }}]"
{{--                                                    style="background-color: {{ $groupReason ? '#e8f5e8' : '#f8f8f8' }};"--}}
                                                    data-component-id="{{ $component->id }}"
                                                    data-reason-value="{{ $groupReason }}">
                                                <option value="">Reason (Optional)</option>
                                                @foreach($codes as $code)
                                                    <option value="{{ $code->id }}"
                                                            @if($groupReason && $groupReason == $code->id) selected @endif>
                                                        {{ $code->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach

                        @foreach($separateComponents as $index => $componentData)
                            @php
                                $component = $componentData['component'];
                                $existingData = $componentData['existing_data'];
                                $unitIndex = $componentData['unit_index'];
                                $unitsAssy = $componentData['units_assy'];

                                // Определяем, выбран ли этот компонент
                                $isChecked = $existingData !== null;

                                // Получаем значения из существующих данных
                                $serialValue = $existingData['serial_number'] ?? '';
                                $assySerialValue = $existingData['assy_serial_number'] ?? '';
                                $reasonValue = $existingData['reason'] ?? '';

                            @endphp
                            <tr>
                                <td>
                                    {{ $component->name }}
                                </td>
                                <td>
                                    {{ $component->part_number }}
                                    @if($component->assy_part_number)
                                        / {{ $component->assy_part_number }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="selected_component[separate_{{ $index }}]" value="{{ $component->id }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           name="serial_numbers[separate_{{ $index }}]"
                                           value="{{ $serialValue }}"
                                           placeholder="Serial Number">
                                </td>
                                <td>
                                    @if($component->assy_part_number)
                                        <input type="text" class="form-control form-control-sm"
                                               name="assy_serial_numbers[separate_{{ $index }}]"
                                               value="{{ $assySerialValue }}"
                                               placeholder="ASSY Serial Number">
                                    @endif
                                </td>
                                <td>
                                    <select class="form-control form-control-sm reason-select" name="reasons[separate_{{ $index }}]">
                                        <option value="">Reason (Optional)</option>
                                        @foreach($codes as $code)
                                            <option value="{{ $code->id }}"
                                                    @if($reasonValue && $reasonValue == $code->id) selected @endif>
                                                {{ $code->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var inModal = {{ request()->query('modal') ? 'true' : 'false' }};

            var cancelBtn = document.getElementById('editLogCardCancelBtn');
            if (cancelBtn && inModal && window.parent !== window) {
                cancelBtn.addEventListener('click', function() {
                    window.parent.postMessage({ type: 'editLogCardCancel' }, '*');
                });
            }

            let groupMap = {};
            @foreach($groupedComponents as $groupIndex => $group)
                groupMap[{{ $groupIndex }}] = '{{ $group['ipl_group'] }}';
            @endforeach

            function buildComponentData() {
                let data = [];
                let allGroups = new Set();
                document.querySelectorAll('input[type=radio][name^="selected_component["]').forEach(function(radio) {
                    let index = radio.name.match(/selected_component\[(.*)\]/)[1];
                    if (!index.includes('separate_')) allGroups.add(index);
                });
                document.querySelectorAll('input[type=radio]:checked').forEach(function(radio) {
                    let index = radio.name.match(/selected_component\[(.*)\]/)[1];
                    let component_id = radio.value;
                    let serial_number = (document.querySelector('input[name="serial_numbers[' + index + ']"]') || {}).value || '';
                    let assy_serial_number = '';
                    let assyInput = document.querySelector('input[name="assy_serial_numbers[' + index + ']"]');
                    if (assyInput) assy_serial_number = assyInput.value;
                    let reasonSelect = document.querySelector('select[name="reasons[' + index + ']"]');
                    let reason = reasonSelect ? reasonSelect.value : '';
                    if (!index.includes('separate_')) {
                        data.push({ component_id: component_id, ipl_group: groupMap[index], serial_number: serial_number, assy_serial_number: assy_serial_number, reason: reason });
                    } else {
                        data.push({ component_id: component_id, serial_number: serial_number, assy_serial_number: assy_serial_number, reason: reason });
                    }
                });
                if (data.length !== allGroups.size + (document.querySelectorAll('input[type=radio][name*="separate_"]:checked').length || 0)) {
                    return null;
                }
                return data;
            }

            document.getElementById('editForm').addEventListener('submit', function(e) {
                let data = buildComponentData();
                if (!data) {
                    (typeof showNotification === 'function' ? showNotification : alert)('Выберите компонент в каждой группе!', 'warning');
                    e.preventDefault();
                    return false;
                }
                document.getElementById('component_data_input').value = JSON.stringify(data);

                if (inModal && window.parent !== window) {
                    e.preventDefault();
                    var form = this;
                    var submitBtn = document.getElementById('editLogCardSubmitBtn');
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + (submitBtn.textContent || ''); }
                    var formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                    .then(function(r) { return r.json().catch(function() { return {}; }); })
                    .then(function(res) {
                        if (res.success) {
                            window.parent.postMessage({ type: 'editLogCardSuccess' }, '*');
                        } else {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Update Log Card") }}'; }
                            alert(res.message || (res.errors ? Object.values(res.errors).flat().join(', ') : 'Error'));
                        }
                    })
                    .catch(function() {
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Update Log Card") }}'; }
                        alert('Error');
                    });
                    return false;
                }
            });
        });
    </script>
@endsection
