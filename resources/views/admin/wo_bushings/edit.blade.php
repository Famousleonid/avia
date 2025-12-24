@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 120px;
            max-width: 200px;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 180px;
            max-width: 250px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 120px;
            max-width: 150px;
            text-align: center;
            vertical-align: top;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 80px;
            max-width: 100px;
            text-align: center;
        }

        .table thead th {
            position: sticky;
            height: 60px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
            background-color: #f8f9fa;
        }

        .form-select, .form-control {
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
        }

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
            width: 70px;
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

        /* Стили для неактивных полей */
        .form-control:disabled,
        .form-select:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>

    <div class="card-shadow">
        @if($bushings->flatten()->count() > 0)
            <form id="bushings-form" method="POST" action="{{ route('wo_bushings.update', $woBushing->id) }}">
                @csrf
                @method('PUT')
        @endif
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-primary ms-2 mb-0">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                        <h4 class="ps-xl-5 mb-0">{{__('EDIT BUSHINGS')}}</h4>
                    </div>
                </div>
                <div>
                    <a href="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => route
                    ('wo_bushings.edit', $woBushing->id)]) }}"
                       class="btn btn-outline-primary me-2" style="height: 60px;width: 100px">
                        <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
                    </a>
                    <a href="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => request()->fullUrl()]) }}"
                       class="btn btn-outline-primary me-2" style="height: 60px;width: 110px">
                        <i class="fas fa-plus"></i> {{ __('Add Component') }}
                    </a>
                </div>
                <div class="d-flex align-items-center">
                    @if($bushings->flatten()->count() > 0)
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-save"></i> Update Bushings Data
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg me-2" onclick="clearForm()">
                            <i class="fas fa-eraser"></i> Clear All
                        </button>
                    @endif
                    <a href="{{ route('wo_bushings.show', $current_wo->id) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">
                        {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>

        @if($bushings->flatten()->count() > 0)
            <div class="d-flex justify-content-center mt-3">
                <div class="table-wrapper me-3">
                        <table class="display table shadow table-hover align-middle table-bordered bg-gradient">
                            <thead>
                                <tr class="header-row">
                                    <th class="text-primary text-center">Bushings</th>
                                    <th class="text-primary text-center">Select</th>
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
                                        // Создаем карту выбранных компонентов из новой структуры bushData
                                        $selectedComponentsMap = [];
                                        $groupData = [];

                                        foreach($bushData as $bushItem) {
                                            if(isset($bushItem['bushing'])) {
                                                $componentId = $bushItem['bushing'];
                                                $selectedComponentsMap[$componentId] = [
                                                    'qty' => $bushItem['qty'],
                                                    'machining' => $bushItem['processes']['machining'] ?? null,
                                                    'stress_relief' => $bushItem['processes']['stress_relief'] ?? null,
                                                    'ndt' => $bushItem['processes']['ndt'] ?? null,
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
                                                    <span><strong>{{ $bushing->ipl_num }}</strong> - {{ $bushing->part_number
                                                    }}</span>
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
                                                               {{ in_array($bushing->id, $selectedComponentsInGroup) ? 'checked' : '' }}
                                                               onchange="toggleGroupFields('{{ $bushIplNum ?: 'no_ipl' }}')">
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
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][ndt]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                    {{ !$groupSelected ? 'disabled' : '' }}>
                                                <option value="">-- Select NDT --</option>
                                                @foreach($ndtProcesses as $process)
                                                    <option value="{{ $process->id }}"
                                                            {{ isset($groupData['ndt']) && $groupData['ndt'] == $process->id ? 'selected' : '' }}>
                                                        {{ $process->process_name->name }}
                                                    </option>
                                                @endforeach
                                            </select>
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
            <div class="text-center mt-5">
                <h3 class="text-muted">{{__('No Bushings available for this Work Order')}}</h3>
                <p class="text-muted">{{__('No components with "Is Bush" marked are found for this manual.')}}</p>
                <a href="{{ route('components.create') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> {{__('Add Components')}}
                </a>
            </div>
        @endif
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

        // Функция для управления активностью полей группы
        function toggleGroupFields(groupName) {
            const groupCheckboxes = document.querySelectorAll(`.component-checkbox[data-group="${groupName}"]`);
            const groupFields = document.querySelectorAll(`[data-group="${groupName}"]:not(.component-checkbox)`);

            // Проверяем, есть ли выбранные чекбоксы в группе
            const hasSelected = Array.from(groupCheckboxes).some(checkbox => checkbox.checked);

            // Активируем/деактивируем поля группы
            groupFields.forEach(field => {
                field.disabled = !hasSelected;
                if (!hasSelected) {
                    if (field.classList.contains('qty-input')) {
                        field.value = '1'; // Возвращаем значение по умолчанию для QTY
                    } else {
                        field.value = ''; // Очищаем значения для остальных полей
                    }
                }
            });
        }

        // Валидация формы
        document.addEventListener('DOMContentLoaded', function() {
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

            const form = document.getElementById('bushings-form');

            form.addEventListener('submit', function(e) {
                const selectedComponents = document.querySelectorAll('.component-checkbox:checked');

                if (selectedComponents.length === 0) {
                    e.preventDefault();
                    alert('{{__("Please select at least one component before submitting.")}}');
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
                    alert('{{__("Please enter quantity for all groups with selected components.")}}');
                    return false;
                }
            });
        });
    </script>

@endsection
