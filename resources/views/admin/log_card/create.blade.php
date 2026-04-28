@extends(request()->query('modal') ? 'admin.master-embed' : 'admin.master')

@section('content')
    <style>
        .container {
            max-width: 1200px;
        }
        .component-group {
            margin-bottom: 30px;
        }
        .group-header {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .group-selector {
            margin-bottom: 10px;
        }
        /*.table th {*/
        /*    background-color: #e9ecef;*/
        /*}*/
        .component-item {
            border-bottom: 1px solid #dee2e6;
        }
        .component-group-row {
            border-bottom: 1px solid #dee2e6;
        }
        .serial-number-input {
            min-width: 120px;
            font-size: 0.875rem;
        }
        .table td {
            vertical-align: middle;
        }
        .component-radio {
            margin: 0;
        }
        .align-middle {
            vertical-align: middle !important;
        }
        .reason-select {
            min-width: 150px;
        }
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
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('log_card.store')}}"
                  enctype="multipart/form-data">
                @csrf
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}} {{__('Create Log Card')}}</h4>
                <div>
                    <button type="submit" class="btn btn-success" id="createLogCardSubmitBtn">
                        <i class="fas fa-save"></i> {{ __('Create Log Card') }}
                    </button>
                    @if(request()->query('modal'))
                        <button type="button" class="btn btn-secondary" id="createLogCardCancelBtn">{{ __('Cancel') }}</button>
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

                <!-- Отладочная информация о группировке -->
                {{--                <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px;">--}}
                {{--                    <strong>DEBUG: Группировка компонентов:</strong><br>--}}
                {{--                    @foreach($groupedComponents as $groupIndex => $group)--}}
                {{--                        <strong>Группа {{ $groupIndex }} ({{ $group['ipl_group'] }}):</strong> {{ $group['count'] }} компонентов<br>--}}
                {{--                        @foreach($group['components'] as $componentData)--}}
                {{--                            &nbsp;&nbsp;- {{ $componentData['component']->ipl_num }} ({{ $componentData['component']->name }})<br>--}}
                {{--                        @endforeach--}}
                {{--                    @endforeach--}}
                {{--                </div>--}}

                <div class="table-responsive table-scroll-container">
                    <table class="table table-bordered dir-table table-hover">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-center">Part Number / Assy PN</th>
                            <th>Select</th>
                            <th>Serial Number</th>
                            <th>ASSY Serial Number</th>
                            <th>Reason for Remove (Optional)</th>
                            {{--                                <th>Action</th>--}}
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($groupedComponents as $groupIndex => $group)
                            @foreach($group['components'] as $i => $componentData)
                                @php
                                    $component = $componentData['component'];
                                    $reason = $componentData['reason_for_remove'];
                                @endphp
                                <tr>
                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">{{ $component->name }}
                                            ({{$component->ipl_num}})
                                        </td>
                                    @endif
                                    <td class="text-start ps-3">
                                        {{ $component->part_number }}
                                        @if($component->assy_part_number)
                                            / {{ $component->assy_part_number }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input type="radio" name="selected_component[{{ $groupIndex }}]" value="{{ $component->id }}">
                                    </td>
                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            <input type="text" class="form-control form-control-sm"
                                                   name="serial_numbers[{{ $groupIndex }}]"
                                                   value="{{ $component->serial_number ?? '' }}"
                                                   placeholder="Serial Number">
                                        </td>
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            @if($component->assy_part_number)
                                                <input type="text" class="form-control form-control-sm"
                                                       name="assy_serial_numbers[{{ $groupIndex }}]"
                                                       value="{{ $component->assy_serial_number ?? '' }}"
                                                       placeholder="ASSY Serial Number">
                                            @endif
                                        </td>
                                    @endif

                                    @if($i === 0)
                                        <td rowspan="{{ $group['count'] }}" class="align-middle">
                                            <select class="form-control form-control-sm reason-select" name="reasons[{{ $groupIndex }}]">
                                                <option value="">Reason (Optional)</option>
                                                @foreach($codes as $code)
                                                    <option value="{{ $code->id }}"
                                                            @if($reason && $reason === $code->name) selected @endif>
                                                        {{ $code->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach

                        {{-- Отдельные компоненты с units_assy > 1 --}}
                        {{-- DEBUG: Separate components count: {{ $separateComponents->count() }} --}}
                        @foreach($separateComponents as $index => $componentData)
                            @php
                                $component = $componentData['component'];
                                $reason = $componentData['reason_for_remove'];
                                $unitIndex = $componentData['unit_index'];
                                $unitsAssy = $componentData['units_assy'];
                            @endphp
                            <tr>
                                <td>
                                    {{ $component->name }}
                                    <br><small class="text-muted">Unit {{ $unitIndex }} of {{ $unitsAssy }}</small>
                                </td>
                                <td class="text-start ps-3">
                                    {{ $component->part_number }}
                                    @if($component->assy_part_number)
                                        / {{ $component->assy_part_number }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="selected_component[separate_{{ $index }}]" value="{{ $component->id }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           name="serial_numbers[separate_{{ $index }}]"
                                           value="{{ $component->serial_number ?? '' }}"
                                           placeholder="Serial Number">
                                </td>
                                <td>
                                    @if($component->assy_part_number)
                                        <input type="text" class="form-control form-control-sm"
                                               name="assy_serial_numbers[separate_{{ $index }}]"
                                               value="{{ $component->assy_serial_number ?? '' }}"
                                               placeholder="ASSY Serial Number">
                                    @endif
                                </td>
                                <td>
                                    <select class="form-control form-control-sm reason-select" name="reasons[separate_{{ $index }}]">
                                        <option value="">Reason (Optional)</option>
                                        @foreach($codes as $code)
                                            <option value="{{ $code->id }}"
                                                    @if($reason && $reason === $code->name) selected @endif>
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
            var workorderId = {{ $current_wo->id }};

            var cancelBtn = document.getElementById('createLogCardCancelBtn');
            if (cancelBtn && inModal && window.parent !== window) {
                cancelBtn.addEventListener('click', function() {
                    window.parent.postMessage({ type: 'createLogCardCancel' }, '*');
                });
            }

            let groupMap = {};
            @foreach($groupedComponents as $groupIndex => $group)
                groupMap[@json($groupIndex)] = @json($group['ipl_group']);
            @endforeach

            var formEl = document.getElementById('createForm');

            function buildComponentData() {
                let form = formEl;
                let data = [];
                form.querySelectorAll('input[type=radio][name^="selected_component["]:checked').forEach(function(radio) {
                    let m = radio.name.match(/selected_component\[(.*)\]/);
                    if (!m) return;
                    let index = m[1];
                    let component_id = radio.value;
                    let serialEl = form.querySelector('input[name="serial_numbers[' + index + ']"]');
                    let serial_number = serialEl ? serialEl.value : '';
                    let assy_serial_number = '';
                    let assyInput = form.querySelector('input[name="assy_serial_numbers[' + index + ']"]');
                    if (assyInput) assy_serial_number = assyInput.value;
                    let reasonSelect = form.querySelector('select[name="reasons[' + index + ']"]');
                    let reason = reasonSelect ? reasonSelect.value : '';
                    if (!index.includes('separate_')) {
                        data.push({ component_id: component_id, ipl_group: groupMap[index], serial_number: serial_number, assy_serial_number: assy_serial_number, reason: reason });
                    } else {
                        data.push({ component_id: component_id, serial_number: serial_number, assy_serial_number: assy_serial_number, reason: reason });
                    }
                });
                if (data.length < 1) {
                    return null;
                }
                return data;
            }

            document.getElementById('createForm').addEventListener('submit', function(e) {
                let data = buildComponentData();
                if (!data) {
                    (typeof showNotification === 'function' ? showNotification : alert)('Отметьте хотя бы один компонент (радиокнопку) для Log Card.', 'warning');
                    e.preventDefault();
                    return false;
                }
                document.getElementById('component_data_input').value = JSON.stringify(data);

                if (inModal && window.parent !== window) {
                    e.preventDefault();
                    var form = this;
                    var submitBtn = document.getElementById('createLogCardSubmitBtn');
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
                            window.parent.postMessage({ type: 'createLogCardSuccess', workorderId: workorderId, logCardId: res.log_card_id }, '*');
                        } else {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Create Log Card") }}'; }
                            alert(res.message || (res.errors ? Object.values(res.errors).flat().join(', ') : 'Error'));
                        }
                    })
                    .catch(function() {
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Create Log Card") }}'; }
                        alert('Error');
                    });
                    return false;
                }
            });
        });
    </script>
@endsection
