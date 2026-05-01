@extends(request()->query('modal') ? 'admin.master-embed' : 'admin.master')

@section('content')
    <style>
        .container { max-width: 1200px; }
        .container.modal-fit { max-width: 100%; }
        .table-scroll-container {
            max-height: 75vh;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
            width: 100%;
        }
        .table-scroll-container thead th {
            position: sticky;
            top: 0;
            background-color: #031e3a;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }
        .table-scroll-container table { margin-bottom: 0; width: 100%; table-layout: fixed; }

        .table th, .table td {
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .table th:nth-child(1), .table td:nth-child(1) { white-space: nowrap; }
        .table th:nth-child(2), .table td:nth-child(2) {
            text-align: center;
            vertical-align: top;
        }
        .table th:nth-child(3), .table td:nth-child(3) { text-align: center; }
        .table td:nth-child(n+4) { white-space: nowrap; }

        .table thead th {
            height: 60px;
            vertical-align: middle;
        }

        .form-select, .form-control {
            font-size: 0.8rem;
            padding: 0.25rem 0.4rem;
            max-width: 100%;
        }
        .table .form-select { width: 100%; }

        /* Стили для длинного текста процесса */
        .process-text-long {
            font-size: 0.65rem;
            line-height: 0.9;
            letter-spacing: -0.3px;
            transform: scale(0.9);
            transform-origin: left;
        }

        .header-row th {
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .sub-header-row th {
            border-top: none;
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }

        .bushing-checkbox {
            transform: scale(1.2);
        }

        .qty-input {
            width: 100%;
            max-width: 60px;
            text-align: center;
        }

        .table-info {
            background-color: #d1ecf1 !important;
        }

        .table-info td {
            border-bottom: 2px solid #bee5eb !important;
            font-weight: bold;
            color: #0c5460;
        }

        .ps-4 {
            padding-left: 1.5rem !important;
        }

        .text-start {
            text-align: left !important;
        }

        .component-checkbox {
            margin-bottom: 0.25rem;
        }

        .bushing-ipl {
            display: inline-block;
            font-size: 10px !important;
            font-weight: 400 !important;
            line-height: 1.05 !important;
        }

        .bushing-part-number {
            font-size: 12px !important;
        }

        /* Стили для чекбоксов NDT */
        .ndt-checkboxes {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            max-height: 200px;
            overflow: auto;
            padding: 0.25rem;
        }

        .ndt-checkboxes .form-check {
            margin-bottom: 0;
        }

        .ndt-checkboxes .form-check-input {
            margin-top: 0.25rem;
        }

        .ndt-checkboxes .form-check-label {
            margin-left: 0.25rem;
            cursor: pointer;
        }

        /* Стили для неактивных полей */
        .form-control:disabled,
        .form-select:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>

    <div class="container mt-3{{ request()->query('modal') ? ' modal-fit' : '' }}">
        <div class="card bg-gradient">
            @if($bushings->flatten()->count() > 0)
                <form id="bushings-form" method="POST" action="{{ route('wo_bushings.update', $woBushing->id) }}">
                    @csrf
                    @method('PUT')
            @endif
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="text-primary mb-0">{{__('WO')}} {{$current_wo->number}} {{__('Update Bushings List')}}</h4>
                <div>
                    @if($bushings->flatten()->count() > 0)
                        <button type="submit" class="btn btn-success" id="editBushingSubmitBtn">
                            <i class="fas fa-save"></i> {{ __('Update Bushings Data') }}
                        </button>
                    @endif
                    @if(request()->query('modal'))
                        <button type="button" class="btn btn-secondary" id="editBushingCancelBtn">{{ __('Cancel') }}</button>
                    @else
                        <a href="{{ route('wo_bushings.show', $current_wo->id) }}" id="editBushingBackBtn" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    @endif
                </div>
            </div>

            @if($bushings->flatten()->count() > 0)
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @if(request()->query('modal'))
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-processes-url="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => route('wo_bushings.edit', $woBushing->id)]) }}">
                            <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-part-url="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => request()->fullUrl()]) }}">
                            <i class="fas fa-plus"></i> {{ __('Add Part') }}
                        </button>
                    @else
                        <a href="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => route('wo_bushings.edit', $woBushing->id)]) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
                        </a>
                        <a href="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => request()->fullUrl()]) }}"
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> {{ __('Add Part') }}
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearForm()">
                        <i class="fas fa-eraser"></i> {{ __('Clear All') }}
                    </button>
                </div>

                <div class="table-responsive table-scroll-container">
                        <table class="table table-bordered dir-table table-hover align-middle">
                            <colgroup>
                                <col style="width: 14%;">
                                <col style="width: 8%;">
                                <col style="width: 8%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                            </colgroup>
                            <thead>
                                <tr class="header-row">
                                    <th class="text-primary text-center">Bushings</th>
                                    <th class="text-primary text-center"
                                        title="{{ __("Check or uncheck to include or exclude each bushing from this row's group. Processes apply to all selected bushings in the row.") }}">Select</th>
                                    <th class="text-primary text-center">QTY</th>
                                    <th class="text-primary text-center">Machining</th>
                                    <th class="text-primary text-center">Stress Relief</th>
                                    <th class="text-primary text-center">NDT</th>
                                    <th class="text-primary text-center">Passivation</th>
                                    <th class="text-primary text-center">CAD</th>
                                    <th class="text-primary text-center">Anodizing</th>
                                    <th class="text-primary text-center">Xylan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bushings as $bushIplNum => $bushingGroup)
                                    @php
                                        // Только строки bushData, относящиеся к этой группе (group_key из sync = ключ группы в форме)
                                        $normRowKey = (string) ($bushIplNum ?: 'no_ipl');
                                        $selectedComponentsMap = [];
                                        $groupData = [];

                                        foreach($bushData as $bushItem) {
                                            if(isset($bushItem['bushing'])) {
                                                $gk = $bushItem['group_key'] ?? null;
                                                $normGk = ($gk === null || $gk === '') ? 'no_ipl' : (string) $gk;
                                                if ($normGk === 'No Bush IPL Number') {
                                                    $normGk = 'no_ipl';
                                                }
                                                if ($normGk !== $normRowKey) {
                                                    continue;
                                                }

                                                $componentId = $bushItem['bushing'];

                                                // Нормализуем NDT: всегда массив id
                                                $ndtValue = $bushItem['processes']['ndt'] ?? [];
                                                if (is_null($ndtValue)) {
                                                    $ndtValue = [];
                                                } elseif (!is_array($ndtValue)) {
                                                    $ndtValue = [$ndtValue];
                                                }

                                                $selectedComponentsMap[$componentId] = [
                                                    'qty' => $bushItem['qty'],
                                                    'machining' => $bushItem['processes']['machining'] ?? null,
                                                    'stress_relief' => $bushItem['processes']['stress_relief'] ?? null,
                                                    'ndt' => $ndtValue,
                                                    'passivation' => $bushItem['processes']['passivation'] ?? null,
                                                    'cad' => $bushItem['processes']['cad'] ?? null,
                                                    'anodizing' => $bushItem['processes']['anodizing'] ?? null,
                                                    'xylan' => $bushItem['processes']['xylan'] ?? null,
                                                ];
                                            }
                                        }

                                        // Определяем выбранные компоненты в данной группе
                                        $selectedComponentsInGroup = [];
                                        foreach($bushingGroup as $bushing) {
                                            if(isset($selectedComponentsMap[$bushing->id])) {
                                                $selectedComponentsInGroup[] = $bushing->id;
                                                if(empty($groupData)) {
                                                    $groupData = $selectedComponentsMap[$bushing->id];
                                                }
                                            }
                                        }
                                        $groupSelected = count($selectedComponentsInGroup) > 0;
                                    @endphp

                                    {{-- Group row with all bushings in first column and group controls --}}
                                    <tr>
                                        <td class="ps-2">
                                            @foreach($bushingGroup as $bushing)
                                                <div class="mb-1">
                                                    <span><span class="bushing-ipl" style="font-size: 10px !important; font-weight: 400 !important; line-height: 1.05 !important;">{{ $bushing->ipl_num }}</span> - <span class="bushing-part-number" style="font-size: 12px !important;">{{ $bushing->part_number
                                                    }}</span></span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            <div class="text-start">
                                                @foreach($bushingGroup as $bushing)
                                                    <div class="mb-1">
                                                        <input type="checkbox" name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][components][]"
                                                               value="{{ $bushing->id }}" class="form-check-input me-1 component-checkbox"
                                                               data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                               data-units-assy="{{ $bushing->units_assy ?? 1 }}"
                                                               title="{{ __('Include this bushing in this group (shared QTY and processes for the row)') }}"
                                                               {{ in_array($bushing->id, $selectedComponentsInGroup) ? 'checked' : '' }}>
                                                        <small>{{ $bushing->ipl_num }}</small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][qty]"
                                                   class="form-control qty-input" min="0"
                                                   value="{{ $groupData['qty'] ?? '' }}"
                                                   data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                   {{ !$groupSelected ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][machining]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select Machining --</option>
                                                @foreach($machiningProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['machining']) && $groupData['machining'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][stress_relief]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select Stress Relief --</option>
                                                @foreach($stressReliefProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['stress_relief']) && $groupData['stress_relief'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @php
                                                $selectedNdtIds = isset($groupData['ndt']) ? (array)$groupData['ndt'] : [];
                                            @endphp
                                            <div class="ndt-checkboxes" data-group="{{ $bushIplNum ?: 'no_ipl' }}">
                                                @foreach($ndtProcesses as $process)
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][ndt][]"
                                                               value="{{ $process->id }}"
                                                               class="form-check-input"
                                                               data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                               {{ in_array($process->id, $selectedNdtIds) ? 'checked' : '' }}
                                                               {{ !$groupSelected ? 'disabled' : '' }}>
                                                        <label class="form-check-label" style="font-size: 0.875rem;">
                                                            {{ $process->process_name->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][passivation]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select Passivation --</option>
                                                @foreach($passivationProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['passivation']) && $groupData['passivation'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][cad]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select CAD --</option>
                                                @foreach($cadProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['cad']) && $groupData['cad'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][anodizing]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select Anodizing --</option>
                                                @foreach($anodizingProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['anodizing']) && $groupData['anodizing'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][xylan]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select Xylan --</option>
                                                @foreach($xylanProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['xylan']) && $groupData['xylan'] == $process->id ? 'selected' : '' }}>
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
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
            @else
            <div class="card-body text-center py-5">
                <h3 class="text-muted">{{__('No Bushings available for this Work Order')}}</h3>
                <p class="text-muted">{{__('No components with "Is Bush" marked are found for this manual.')}}</p>
                <a href="{{ route('components.create') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> {{__('Add Components')}}
                </a>
            </div>
        @endif
        </div>
    </div>

    <script>
        function clearForm() {
            if(confirm('{{__("Are you sure you want to clear all data?")}}')) {
                document.getElementById('bushings-form').reset();
                // После сброса формы деактивируем все поля и устанавливаем значения по умолчанию
                document.querySelectorAll('[data-group]').forEach(function(field) {
                    if (!field.classList.contains('component-checkbox')) {
                        field.disabled = true;
                        if (field.classList.contains('qty-input')) {
                            field.value = '1';
                        }
                    }
                });
            }
        }

        // Функция для управления активностью полей группы (по data-group; без CSS-селектора — ключ IPL может содержать кавычки и т.д.)
        function toggleGroupFields(groupName) {
            if (groupName === null || groupName === undefined) {
                return;
            }
            var g = String(groupName);
            var groupCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.component-checkbox')).filter(function (cb) {
                return cb.getAttribute('data-group') === g;
            });
            var groupFields = Array.prototype.slice.call(document.querySelectorAll('[data-group]')).filter(function (el) {
                return el.getAttribute('data-group') === g && !el.classList.contains('component-checkbox');
            });

            const hasSelected = groupCheckboxes.some(function (checkbox) { return checkbox.checked; });
            const firstChecked = groupCheckboxes.find(function (cb) { return cb.checked; });
            const unitsAssy = firstChecked ? (firstChecked.getAttribute('data-units-assy') || '1') : '1';

            groupFields.forEach(field => {
                if (field.type === 'checkbox' && field.name && field.name.includes('[ndt]')) {
                    field.disabled = !hasSelected;
                    if (!hasSelected) field.checked = false;
                } else if (field.tagName === 'SELECT' || field.classList.contains('qty-input')) {
                    field.disabled = !hasSelected;
                    if (!hasSelected) {
                        if (field.classList.contains('qty-input')) {
                            field.value = '1';
                        } else {
                            field.value = '';
                        }
                    } else if (field.classList.contains('qty-input') && hasSelected) {
                        field.value = unitsAssy;
                    }
                }
            });
        }

        // Валидация формы
        document.addEventListener('DOMContentLoaded', function() {
            var inModal = {{ request()->query('modal') ? 'true' : 'false' }};

            // Инициализация: проверяем состояние полей при загрузке на основе выбранных чекбоксов
            const allGroups = new Set();
            document.querySelectorAll('.component-checkbox').forEach(function(checkbox) {
                const groupName = checkbox.getAttribute('data-group');
                allGroups.add(groupName);
            });

            // Инициализируем состояние для каждой группы
            allGroups.forEach(function(groupName) {
                toggleGroupFields(groupName);
            });

            var bushingsForm = document.getElementById('bushings-form');
            if (bushingsForm) {
                bushingsForm.addEventListener('change', function (e) {
                    var t = e.target;
                    if (t && t.classList && t.classList.contains('component-checkbox')) {
                        toggleGroupFields(t.getAttribute('data-group'));
                    }
                });
            }

            var cancelBtn = document.getElementById('editBushingCancelBtn');
            if (cancelBtn && inModal && window.parent !== window) {
                cancelBtn.addEventListener('click', function() {
                    (window.top || window.parent).postMessage({ type: 'editBushingCancel' }, '*');
                });
            }

            if (inModal && window.parent !== window) {
                document.querySelectorAll('button[data-add-processes-url]').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var url = this.getAttribute('data-add-processes-url');
                        if (url) {
                            (window.top || window.parent).postMessage({ type: 'openAddProcessesModal', url: url }, '*');
                        }
                    });
                });
                document.querySelectorAll('button[data-add-part-url]').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var url = this.getAttribute('data-add-part-url');
                        if (url) {
                            (window.top || window.parent).postMessage({ type: 'openAddPartModal', url: url }, '*');
                        }
                    });
                });
            }

            const form = document.getElementById('bushings-form');

            form.addEventListener('submit', function(e) {
                const selectedComponents = document.querySelectorAll('.component-checkbox:checked');

                if (selectedComponents.length === 0) {
                    e.preventDefault();
                    window.showNotification('{{__("Please select at least one component before submitting.")}}');
                    return false;
                }

                // Проверяем, что для групп с выбранными компонентами заполнено количество
                let hasErrors = false;
                const groupsWithSelectedComponents = new Set();

                selectedComponents.forEach(function(checkbox) {
                    const groupName = checkbox.getAttribute('data-group');
                    groupsWithSelectedComponents.add(groupName);
                });

                groupsWithSelectedComponents.forEach(function(groupName) {
                    const qtyInput = document.querySelector(`input[name="group_bushings[${groupName}][qty]"]`);

                    if (!qtyInput.value || qtyInput.value <= 0) {
                        qtyInput.style.borderColor = 'red';
                        hasErrors = true;
                    } else {
                        qtyInput.style.borderColor = '';
                    }
                });

                if (hasErrors) {
                    e.preventDefault();
                    window.showNotification('{{__("Please enter quantity for all groups with selected components.")}}');
                    return false;
                }

                if (inModal && window.parent !== window) {
                    e.preventDefault();
                    var submitBtn = document.getElementById('editBushingSubmitBtn');
                    var origHtml = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
                    var fd = new FormData(form);
                    fd.append('_method', 'PUT');
                    fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                    .then(function(r) { return r.json().catch(function() { return {}; }); })
                    .then(function(res) {
                        if (res.success) {
                            window.parent.postMessage({ type: 'editBushingSuccess' }, '*');
                        } else {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
                            window.notifyError(res.message || (res.errors ? Object.values(res.errors).flat().join(', ') : '{{ __("Error") }}'));
                        }
                    })
                    .catch(function() {
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
                        window.notifyError('{{ __("Error") }}');
                    });
                    return false;
                }
            });
        });
    </script>

@endsection
