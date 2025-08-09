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

        /* Стили для кнопок добавления процессов */
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
        }

        .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>

    <div class="card-shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-primary ms-2">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                        <h4 class="ps-xl-5">{{__('CREATE BUSHINGS')}}</h4>
                    </div>
                </div>
                <div class="">
                    <a href="{{ route('wo_bushings.show', $current_wo->id) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">
                        {{ __('Back to Bushings') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($bushings->flatten()->count() > 0)
            <div class="d-flex justify-content-center mt-3">
                <div class="table-wrapper me-3">
                    <form id="bushings-form" method="POST" action="{{ route('wo_bushings.store') }}">
                        @csrf
                        <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">

                        <table class="display table shadow table-hover align-middle table-bordered bg-gradient">
                            <thead>
                                <tr class="header-row">
                                    <th class="text-primary text-center">Bushings</th>
                                    <th class="text-primary text-center">Select</th>
                                    <th class="text-primary text-center">QTY</th>
                                    <th class="text-primary text-center">Machining</th>
                                    <th class="text-primary text-center">NDT</th>
                                    <th class="text-primary text-center">Passivation</th>
                                    <th class="text-primary text-center">CAD</th>
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
                                                        {{ $process->process }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][ndt]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select NDT --</option>
                                                @foreach($ndtProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        {{ $process->process_name->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][passivation]"
                                                    class="form-select" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                                <option value="">-- Select Passivation --</option>
                                                @foreach($passivationProcesses as $process)
                                                    <option value="{{ $process->id }}">
                                                        {{ $process->process }}
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
                                                        {{ $process->process }}
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
                                                        {{ $process->process }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-center mt-3 mb-3">
                            <button type="submit" class="btn btn-success btn-lg me-2">
                                <i class="fas fa-plus"></i> Create Bushings Data
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg me-2" onclick="clearForm()">
                                <i class="fas fa-eraser"></i> Clear All
                            </button>
                            <a href="{{ route('wo_bushings.show', $current_wo->id) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
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
                field.disabled = !hasSelected;
                if (!hasSelected) {
                    if (field.classList.contains('qty-input')) {
                        field.value = '1'; // Возвращаем значение по умолчанию для QTY
                    } else {
                        field.value = ''; // Очищаем значения для остальных полей
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

@endsection
