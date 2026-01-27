@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            max-height: 70vh;
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

        /* Стили для чекбоксов NDT */
        .ndt-checkboxes {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            max-height: 200px;
            overflow-y: auto;
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

        /* Стили для кнопок добавления процессов */
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
        }

        .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        /* Select2 theme support */
        [data-bs-theme="dark"] .select2-container--default .select2-selection--single {
            background-color: #212529 !important;
            border: 1px solid #495057 !important;
            color: #fff !important;
            height: 38px;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff !important;
            line-height: 38px;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #fff transparent transparent transparent !important;
        }

        [data-bs-theme="dark"] .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #fff transparent !important;
        }

        [data-bs-theme="dark"] .select2-dropdown {
            background-color: #212529 !important;
            border: 1px solid #495057 !important;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-results__option {
            background-color: #212529 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #212529 !important;
            border: 1px solid #495057 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-selection--single:focus,
        [data-bs-theme="dark"] .select2-container--default .select2-selection--single:active {
            border-color: #0d6efd !important;
            outline: none;
        }

        [data-bs-theme="light"] .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            color: #212529 !important;
            height: 38px;
        }

        [data-bs-theme="light"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #212529 !important;
            line-height: 38px;
        }

        [data-bs-theme="light"] .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        [data-bs-theme="light"] .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #212529 transparent transparent transparent !important;
        }

        [data-bs-theme="light"] .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #212529 transparent !important;
        }

        [data-bs-theme="light"] .select2-dropdown {
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
        }

        [data-bs-theme="light"] .select2-container--default .select2-results__option {
            background-color: #fff !important;
            color: #212529 !important;
        }

        [data-bs-theme="light"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        [data-bs-theme="light"] .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        [data-bs-theme="light"] .select2-search--dropdown .select2-search__field {
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            color: #212529 !important;
        }

        [data-bs-theme="light"] .select2-container--default .select2-selection--single:focus,
        [data-bs-theme="light"] .select2-container--default .select2-selection--single:active {
            border-color: #0d6efd !important;
            outline: none;
        }
    </style>

    <div class="card-shadow">
        @if($bushings->flatten()->count() > 0)
            <form id="bushings-form" method="POST" action="{{ route('wo_bushings.store') }}">
                @csrf
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
        @endif
        <div class="card-header m-1 shadow ">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-primary ms-2 mb-0">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                        <h4 class="ps-xl-5 mb-0">{{__('CREATE BUSHINGS')}}</h4>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-info me-2" style="height: 60px;width: 140px" data-bs-toggle="modal" data-bs-target="#addBushingsFromManualModal">
                        <i class="fas fa-exchange-alt"></i> {{ __('Add from Manual') }}
                    </button>
                    <a href="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => route('wo_bushings.create', $current_wo->id)]) }}"
                       class="btn btn-outline-primary me-2" style="height: 60px;width: 100px">
                        <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
                    </a>
                    <a href="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => route('wo_bushings.create', $current_wo->id)]) }}"
                       class="btn btn-outline-primary me-2" style="height: 60px;width: 110px">
                        <i class="fas fa-plus"></i> {{ __('Add Component') }}
                    </a>
                </div>
                <div class="d-flex align-items-center">
                    @if($bushings->flatten()->count() > 0)
                        <button type="submit" class="btn btn-success btn-lg me-2">
                            <i class="fas fa-plus"></i> Create Bushings Data
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg me-2" onclick="clearForm()">
                            <i class="fas fa-eraser"></i> Clear All
                        </button>
                    @endif
                    <a href="{{ route('wo_bushings.show', $current_wo->id) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">
                        {{ __('Back to Bushings') }}
                    </a>
                </div>
            </div>
        </div>



        @if($bushings->flatten()->count() > 0)
            <div class="d-flex justify-content-center mt-3">
                <div class="table-wrapper me-3">
                        <table class="display table shadow table-hover align-middle table-bordered ">
                            <thead class="">
                                <tr class="header-row bg-gradient">
                                    <th class="text-primary text-center">Bushings</th>
                                    <th class="text-primary text-center "> Select</th>
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
                                                               onchange="toggleGroupFields('{{ $bushIplNum ?: 'no_ipl' }}')">
                                                        <small>{{ $bushing->ipl_num }}</small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][qty]"
                                                   class="form-control qty-input" min="0" value="1"
                                                   data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][machining]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Machining --</option>
                                                @foreach($machiningProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][stress_relief]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Stress Relief --</option>
                                                @foreach($stressReliefProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="ndt-checkboxes" data-group="{{ $bushIplNum ?: 'no_ipl' }}">
                                                @foreach($ndtProcesses as $process)
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][ndt][]"
                                                               value="{{ $process->id }}"
                                                               class="form-check-input"
                                                               data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                                               disabled>
                                                        <label class="form-check-label" style="font-size: 0.875rem;">
                                                            {{ $process->process_name->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][passivation]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Passivation --</option>
                                                @foreach($passivationProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][cad]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select CAD --</option>
                                                @foreach($cadProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][anodizing]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Anodizing --</option>
                                                @foreach($anodizingProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        <span @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</span>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][xylan]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Xylan --</option>
                                                @foreach($xylanProcesses as $process)
                                                    <option value="{{ $process->id }}">
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
                <a href="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => route('wo_bushings.create', $current_wo->id)]) }}" class="btn btn-primary mt-3">
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
            console.log('toggleGroupFields called for group:', groupName);

            const groupCheckboxes = document.querySelectorAll(`.component-checkbox[data-group="${groupName}"]`);
            const groupFields = document.querySelectorAll(`[data-group="${groupName}"]:not(.component-checkbox)`);

            console.log('Found checkboxes:', groupCheckboxes.length);
            console.log('Found fields:', groupFields.length);

            // Проверяем, есть ли выбранные чекбоксы в группе
            const hasSelected = Array.from(groupCheckboxes).some(checkbox => checkbox.checked);

            console.log('Has selected:', hasSelected);

            // Активируем/деактивируем поля группы
            groupFields.forEach(field => {
                // Для чекбоксов NDT используем другой подход
                if (field.type === 'checkbox' && field.name && field.name.includes('[ndt]')) {
                    field.disabled = !hasSelected;
                    if (!hasSelected) {
                        field.checked = false;
                    }
                } else if (field.tagName === 'SELECT' || field.classList.contains('qty-input')) {
                    field.disabled = !hasSelected;
                    if (!hasSelected) {
                        if (field.classList.contains('qty-input')) {
                            field.value = '1'; // Возвращаем значение по умолчанию для QTY
                        } else {
                            field.value = ''; // Очищаем значения для остальных полей
                        }
                    }
                }
                console.log('Field disabled state:', field.disabled, 'for field:', field.name);
            });
        }

        // Валидация формы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация: убеждаемся что все поля неактивны при загрузке
            document.querySelectorAll('[data-group]').forEach(function(field) {
                if (!field.classList.contains('component-checkbox')) {
                    field.disabled = true;
                }
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

    <!-- Modal for adding bushings from another manual -->
    <div class="modal fade" id="addBushingsFromManualModal" tabindex="-1" aria-labelledby="addBushingsFromManualModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBushingsFromManualModalLabel">{{ __('Add Bushings from Another Manual') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="manual_select" class="form-label">{{ __('Select Manual') }}</label>
                        <select class="form-select" id="manual_select" style="width: 100%;">
                            <option value="">{{ __('-- Select Manual --') }}</option>
                            @foreach($manuals as $manual)
                                @if($manual->id != $current_wo->unit->manual_id)
                                    <option value="{{ $manual->id }}">{{ $manual->number }} - {{ $manual->title }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div id="bushings_selection" style="display: none;">
                        <label class="form-label">{{ __('Select Bushings') }}</label>
                        <div id="bushings_list" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 0.25rem;">
                            <!-- Bushings will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="add_selected_bushings_btn" style="display: none;">{{ __('Add Selected Bushings') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedManualBushings = [];
        let selectedManualProcesses = {};

        // Store current manual processes for use when adding bushings from other manuals
        const currentManualProcesses = {
            machining: @json($machiningProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; })),
            stress_relief: @json($stressReliefProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; })),
            ndt: @json($ndtProcesses->map(function($p) { return ['id' => $p->id, 'name' => $p->process_name->name]; })),
            passivation: @json($passivationProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; })),
            cad: @json($cadProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; })),
            anodizing: @json($anodizingProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; })),
            xylan: @json($xylanProcesses->map(function($p) { return ['id' => $p->id, 'process' => $p->process]; }))
        };

        document.addEventListener('DOMContentLoaded', function() {
            const manualSelect = document.getElementById('manual_select');
            const bushingsSelection = document.getElementById('bushings_selection');
            const bushingsList = document.getElementById('bushings_list');
            const addSelectedBtn = document.getElementById('add_selected_bushings_btn');
            const currentManualId = {{ $current_wo->unit->manual_id }};

            // Initialize Select2 for manual dropdown
            $(manualSelect).select2({
                placeholder: '{{ __("-- Select Manual --") }}',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#addBushingsFromManualModal')
            });

            // Handle change event for Select2
            $(manualSelect).on('change', function() {
                const selectedManualId = $(this).val();

                if (!selectedManualId) {
                    bushingsSelection.style.display = 'none';
                    addSelectedBtn.style.display = 'none';
                    selectedManualBushings = [];
                    return;
                }

                // Show loading
                bushingsList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                bushingsSelection.style.display = 'block';

                // Fetch bushings from selected manual
                fetch('{{ route("wo_bushings.getBushingsFromManual") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        manual_id: selectedManualId,
                        current_manual_id: currentManualId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedManualBushings = data.bushings;
                        // Use processes from current manual, not from selected manual
                        selectedManualProcesses = currentManualProcesses;
                        renderBushingsList(data.bushings);
                        addSelectedBtn.style.display = 'block';
                    } else {
                        bushingsList.innerHTML = '<div class="alert alert-danger">Error loading bushings</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    bushingsList.innerHTML = '<div class="alert alert-danger">Error loading bushings</div>';
                });
            });

            function renderBushingsList(bushings) {
                if (bushings.length === 0) {
                    bushingsList.innerHTML = '<div class="alert alert-info">No bushings found in this manual</div>';
                    return;
                }

                // Sort groups by bush_ipl_num (handle 'no_ipl' at the end)
                const sortedBushings = [...bushings].sort(function(a, b) {
                    const aKey = a.bush_ipl_num === 'no_ipl' ? 'zzz_no_ipl' : a.bush_ipl_num;
                    const bKey = b.bush_ipl_num === 'no_ipl' ? 'zzz_no_ipl' : b.bush_ipl_num;
                    return aKey.localeCompare(bKey);
                });

                let html = '<div class="list-group">';
                sortedBushings.forEach(function(group, groupIndex) {
                    const groupKey = group.bush_ipl_num;
                    html += `<div class="list-group-item mb-2">`;
                    html += `<div class="form-check mb-2">`;
                    html += `<input class="form-check-input group-checkbox" type="checkbox" id="group_${groupIndex}" data-group="${groupKey}">`;
                    html += `<label class="form-check-label fw-bold" for="group_${groupIndex}">`;
                    html += `Bush IPL: ${groupKey || 'No IPL'}`;
                    html += `</label>`;
                    html += `</div>`;

                    // Sort components within group by ipl_num
                    const sortedComponents = [...group.components].sort(function(a, b) {
                        // Extract numeric part for sorting
                        const aParts = a.ipl_num.split('-');
                        const bParts = b.ipl_num.split('-');
                        const aNum = parseInt(aParts[aParts.length - 1].replace(/[^0-9]/g, '')) || 0;
                        const bNum = parseInt(bParts[bParts.length - 1].replace(/[^0-9]/g, '')) || 0;
                        return aNum - bNum;
                    });

                    sortedComponents.forEach(function(component, compIndex) {
                        const checkboxId = `comp_${groupIndex}_${compIndex}`;
                        html += `<div class="form-check ms-4 mb-1">`;
                        html += `<input class="form-check-input component-checkbox" type="checkbox" id="${checkboxId}" value="${component.id}" data-group="${groupKey}" data-group-index="${groupIndex}">`;
                        html += `<label class="form-check-label" for="${checkboxId}">`;
                        html += `<strong>${component.ipl_num}</strong> - ${component.part_number}`;
                        html += `</label>`;
                        html += `</div>`;
                    });
                    html += `</div>`;
                });
                html += '</div>';
                bushingsList.innerHTML = html;

                // Add event listeners for group checkboxes
                document.querySelectorAll('.group-checkbox').forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const groupKey = this.getAttribute('data-group');
                        const isChecked = this.checked;
                        document.querySelectorAll(`.component-checkbox[data-group="${groupKey}"]`).forEach(function(compCheckbox) {
                            compCheckbox.checked = isChecked;
                        });
                    });
                });

                // Add event listeners for component checkboxes
                document.querySelectorAll('.component-checkbox').forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const groupKey = this.getAttribute('data-group');
                        const groupCheckboxes = document.querySelectorAll(`.component-checkbox[data-group="${groupKey}"]`);
                        const checkedInGroup = document.querySelectorAll(`.component-checkbox[data-group="${groupKey}"]:checked`);
                        const groupCheckbox = document.querySelector(`.group-checkbox[data-group="${groupKey}"]`);
                        if (groupCheckbox) {
                            groupCheckbox.checked = checkedInGroup.length === groupCheckboxes.length;
                        }
                    });
                });
            }

            addSelectedBtn.addEventListener('click', function() {
                const selectedComponents = [];
                document.querySelectorAll('#bushings_list .component-checkbox:checked').forEach(function(checkbox) {
                    const componentId = parseInt(checkbox.value);
                    const groupKey = checkbox.getAttribute('data-group');
                    const groupIndex = parseInt(checkbox.getAttribute('data-group-index'));

                    // Find the group data by groupKey (since we sorted, need to find by key)
                    const group = selectedManualBushings.find(g => (g.bush_ipl_num || 'no_ipl') === groupKey);
                    if (group) {
                        const component = group.components.find(c => c.id === componentId);
                        if (component) {
                            selectedComponents.push({
                                id: componentId,
                                ipl_num: component.ipl_num,
                                part_number: component.part_number,
                                group_key: groupKey
                            });
                        }
                    }
                });

                if (selectedComponents.length === 0) {
                    alert('{{__("Please select at least one bushing")}}');
                    return;
                }

                // Group selected components by group_key
                const groupedComponents = {};
                selectedComponents.forEach(function(comp) {
                    if (!groupedComponents[comp.group_key]) {
                        groupedComponents[comp.group_key] = [];
                    }
                    groupedComponents[comp.group_key].push(comp);
                });

                // Add rows to the table
                const tbody = document.querySelector('#bushings-form table tbody');
                let groupCounter = 0;

                Object.keys(groupedComponents).forEach(function(groupKey) {
                    const components = groupedComponents[groupKey];
                    const uniqueGroupKey = `manual_${Date.now()}_${groupCounter++}`;

                    // Create table row
                    let rowHtml = '<tr>';

                    // First column - Bushings list
                    rowHtml += '<td class="ps-2">';
                    components.forEach(function(comp) {
                        rowHtml += `<div class="mb-1"><span><strong>${comp.ipl_num}</strong> - ${comp.part_number}</span></div>`;
                    });
                    rowHtml += '</td>';

                    // Second column - Checkboxes
                    rowHtml += '<td class="text-center"><div class="text-start">';
                    components.forEach(function(comp) {
                        rowHtml += `<div class="mb-1">`;
                        rowHtml += `<input type="checkbox" name="group_bushings[${uniqueGroupKey}][components][]" value="${comp.id}" class="form-check-input me-1 component-checkbox" data-group="${uniqueGroupKey}" onchange="toggleGroupFields('${uniqueGroupKey}')">`;
                        rowHtml += `<small>${comp.ipl_num}</small>`;
                        rowHtml += `</div>`;
                    });
                    rowHtml += '</div></td>';

                    // Third column - QTY
                    rowHtml += `<td class="text-center">`;
                    rowHtml += `<input type="number" name="group_bushings[${uniqueGroupKey}][qty]" class="form-control qty-input" min="0" value="1" data-group="${uniqueGroupKey}" disabled>`;
                    rowHtml += `</td>`;

                    // Process columns - use processes from current manual
                    const processTypes = ['machining', 'stress_relief', 'ndt', 'passivation', 'cad', 'anodizing', 'xylan'];
                    const processLabels = {
                        'machining': 'Machining',
                        'stress_relief': 'Stress Relief',
                        'ndt': 'NDT',
                        'passivation': 'Passivation',
                        'cad': 'CAD',
                        'anodizing': 'Anodizing',
                        'xylan': 'Xylan'
                    };

                    processTypes.forEach(function(processType) {
                        rowHtml += `<td>`;
                        
                        // Для NDT используем чекбоксы, для остальных - select
                        if (processType === 'ndt') {
                            rowHtml += `<div class="ndt-checkboxes" data-group="${uniqueGroupKey}">`;
                            if (selectedManualProcesses[processType] && selectedManualProcesses[processType].length > 0) {
                                selectedManualProcesses[processType].forEach(function(process) {
                                    const displayText = process.name;
                                    const escapedText = displayText.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                    rowHtml += `<div class="form-check">`;
                                    rowHtml += `<input type="checkbox" name="group_bushings[${uniqueGroupKey}][ndt][]" value="${process.id}" class="form-check-input" data-group="${uniqueGroupKey}" disabled>`;
                                    rowHtml += `<label class="form-check-label" style="font-size: 0.875rem;">${escapedText}</label>`;
                                    rowHtml += `</div>`;
                                });
                            }
                            rowHtml += `</div>`;
                        } else {
                            rowHtml += `<select name="group_bushings[${uniqueGroupKey}][${processType}]" class="form-select" data-group="${uniqueGroupKey}" disabled>`;
                            rowHtml += `<option value="">-- Select ${processLabels[processType]} --</option>`;

                            // Use processes from current manual
                            if (selectedManualProcesses[processType] && selectedManualProcesses[processType].length > 0) {
                                selectedManualProcesses[processType].forEach(function(process) {
                                    const displayText = process.process;
                                    const escapedText = displayText.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                    rowHtml += `<option value="${process.id}">${escapedText}</option>`;
                                });
                            }

                            rowHtml += `</select>`;
                        }
                        rowHtml += `</td>`;
                    });

                    rowHtml += '</tr>';
                    tbody.insertAdjacentHTML('beforeend', rowHtml);
                });

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addBushingsFromManualModal'));
                modal.hide();

                // Reset modal
                $(manualSelect).val(null).trigger('change');
                bushingsSelection.style.display = 'none';
                addSelectedBtn.style.display = 'none';
                selectedManualBushings = [];
            });

            // Reset Select2 when modal is closed
            $('#addBushingsFromManualModal').on('hidden.bs.modal', function () {
                $(manualSelect).val(null).trigger('change');
                bushingsSelection.style.display = 'none';
                addSelectedBtn.style.display = 'none';
                selectedManualBushings = [];
                bushingsList.innerHTML = '';
            });
        });
    </script>

@endsection
