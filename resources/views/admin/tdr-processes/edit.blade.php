@extends('admin.master')

@section('content')
    <style>
        /* Стили для Select2 (темная и светлая темы) */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 25px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: #121212 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }

        /* Стили для светлой темы */
        html[data-bs-theme="light"] .select2-selection--single {
            background-color: #fff !important;
            color: #212529 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-selection__rendered {
            color: #212529 !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
        }

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;
        }
    </style>

    <div class="container mt-3 bg-gradient" style="width: 850px">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Edit Part Processes') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        {{ $current_tdr->component->name }}
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">

                    <!-- Контейнер для строк с процессами -->
                    <div id="processes-container" data-manual-id="{{ $current_tdr->workorder->unit->manual_id ?? '' }}">
                        <div class="process-row mb-3">
                            <div class="row" >
                                <div class="col-md-3" style="width: 200px">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}" {{ $current_tdr_processes->process_names_id == $processName->id ? 'selected' : '' }}>
                                                {{ $processName->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="process">Processes:</label>
                                    <div class="process-options">
                                        {{-- Процессы будут загружены динамически через JavaScript --}}
                                        @if($current_tdr_processes->process_names_id)
                                            {{-- Показываем текущие выбранные процессы при загрузке страницы --}}
                                            @php
                                                $currentProcesses = json_decode($current_tdr_processes->processes, true) ?: [];
                                                $currentProcessNameId = $current_tdr_processes->process_names_id;
                                            @endphp
                                            @foreach ($processes as $process)
                                                @if($process->process_names_id == $currentProcessNameId)
                                                    <div class="form-check" data-process-name-id="{{ $process->process_names_id }}">
                                                        <input type="checkbox" name="processes[0][process][]" value="{{ $process->id }}" class="form-check-input"
                                                            {{ in_array($process->id, $currentProcesses) ? 'checked' : '' }}>
                                                        <label class="form-check-label">{{ $process->process }}</label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
{{--                                    <label for="ec">EC:</label>--}}
                                    <div class="form-check mt-2" id="ec-checkbox-container" style="display: none;">
                                        <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_edit"
                                            {{ $current_tdr_processes->ec ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ec_edit">
                                            EC
                                        </label>
                                    </div>
                                    <div>
                                        <label for="description" class="form-label" style="margin-bottom: -5px">Description</label>
                                        <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $current_tdr_processes->description) }}" placeholder="Enter Description">
                                        <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes', $current_tdr_processes->notes) }}" placeholder="Enter Notes">
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3 me-4">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Update') }}</button>
                        <a href="{{ route('tdr-processes.processes', ['tdrId' => $current_tdr->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Данные ProcessNames для использования в JavaScript
        const processNamesData = @json($processNames->keyBy('id'));
        
        // Динамическое определение ID процесса Machining для EC checkbox
        // Приоритет: 'Machining (EC)' -> 'Machining' -> 'Machining (Blend)'
        let machiningProcessNameId = null;
        @php
            $machiningEC = $processNames->firstWhere('name', 'Machining (EC)');
            $machining = $processNames->firstWhere('name', 'Machining');
            $machiningBlend = $processNames->firstWhere('name', 'Machining (Blend)');
            
            if ($machiningEC) {
                $machiningId = $machiningEC->id;
            } elseif ($machining) {
                $machiningId = $machining->id;
            } elseif ($machiningBlend) {
                $machiningId = $machiningBlend->id;
            } else {
                $machiningId = null;
            }
        @endphp
        @if(isset($machiningId) && $machiningId)
            machiningProcessNameId = {{ $machiningId }};
            console.log('Machining process ID determined:', machiningProcessNameId);
        @else
            machiningProcessNameId = null;
            console.warn('Machining process not found in processNames');
        @endif
        
        // Функция для проверки, является ли процесс Machining
        function isMachiningProcess(processNameId) {
            const result = machiningProcessNameId !== null && processNameId == machiningProcessNameId.toString();
            if (processNameId) {
                console.log('isMachiningProcess check:', {
                    processNameId: processNameId,
                    machiningProcessNameId: machiningProcessNameId,
                    result: result
                });
            }
            return result;
        }

        // Функция для загрузки процессов для строки
        function loadProcessesForRow(selectElement) {
            const processNameId = selectElement.value;
            const processRow = selectElement.closest('.process-row');
            const processOptionsContainer = processRow.querySelector('.process-options');
            const manualId = document.getElementById('processes-container').dataset.manualId;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) {
                return;
            }

            // Получаем индекс строки для правильного именования чекбоксов
            const selectName = selectElement.name;
            const match = selectName.match(/processes\[(\d+)\]/);
            const rowIndex = match ? match[1] : '0';

            // Показываем индикатор загрузки
            processOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Используем processes.getProcesses для получения existingProcesses
            fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || err.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    processOptionsContainer.innerHTML = ''; // Очищаем контейнер
                    
                    let hasProcesses = false;
                    
                    // Отображаем existingProcesses (уже связанные с manual_id)
                    if (data.existingProcesses && data.existingProcesses.length > 0) {
                        // Получаем текущие выбранные процессы из формы (только при первой загрузке)
                        // При изменении Process Name используем пустой массив
                        let currentProcesses = [];
                        const isInitialLoad = !selectElement.dataset.loaded;
                        if (isInitialLoad) {
                            currentProcesses = @json(json_decode($current_tdr_processes->processes, true) ?: []);
                            selectElement.dataset.loaded = 'true';
                        }
                        
                        data.existingProcesses.forEach(process => {
                            const checkbox = document.createElement('div');
                            checkbox.classList.add('form-check');
                            const isChecked = currentProcesses.includes(process.id);
                            checkbox.innerHTML = `
                                <input type="checkbox" name="processes[${rowIndex}][process][]" value="${process.id}" class="form-check-input" ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label">${process.process}</label>
                            `;
                            processOptionsContainer.appendChild(checkbox);
                            hasProcesses = true;
                        });
                    }
                    
                    if (hasProcesses) {
                        saveButton.disabled = false;
                    } else {
                        const noSpecLabel = document.createElement('div');
                        noSpecLabel.classList.add('text-muted', 'mt-2');
                        noSpecLabel.innerHTML = 'No specification. Add specification for this process.';
                        processOptionsContainer.appendChild(noSpecLabel);
                        saveButton.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Ошибка при получении процессов:', error);
                    processOptionsContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;
                    saveButton.disabled = true;
                    
                    if (window.NotificationHandler) {
                        window.NotificationHandler.error('Ошибка при загрузке процессов');
                    } else {
                        alert('Ошибка при загрузке процессов: ' + error.message);
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const processNameSelect = document.querySelector('select[name="processes[0][process_names_id]"]');
            const ecCheckboxContainer = document.getElementById('ec-checkbox-container');
            
            // Инициализация Select2 для Process Name select
            if (typeof $ !== 'undefined' && $.fn.select2 && processNameSelect) {
                $(processNameSelect).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                }).on('select2:select', function (e) {
                    const selectElement = e.target;
                    const processNameId = selectElement.value;
                    const processRow = selectElement.closest('.process-row');
                    
                    // Показываем/скрываем чекбокс EC для Machining
                    const ecCheckbox = processRow.querySelector('input[name*="[ec]"]');
                    if (ecCheckbox) {
                        if (isMachiningProcess(processNameId)) {
                            ecCheckbox.closest('.form-check').style.display = 'block';
                        } else {
                            ecCheckbox.closest('.form-check').style.display = 'none';
                        }
                    }
                    
                    // Загружаем процессы для выбранного Process Name
                    loadProcessesForRow(selectElement);
                });
            }
            
            // Инициализация при загрузке страницы
            if (processNameSelect && processNameSelect.value) {
                const processNameId = processNameSelect.value;
                
                // Показываем/скрываем чекбокс EC в зависимости от выбранного Process Name
                if (ecCheckboxContainer) {
                    if (isMachiningProcess(processNameId)) {
                        ecCheckboxContainer.style.display = 'block';
                    } else {
                        ecCheckboxContainer.style.display = 'none';
                    }
                }
                
                // Загружаем процессы для текущего выбранного Process Name
                if (processNameSelect.value) {
                    loadProcessesForRow(processNameSelect);
                }
            }
        });
    </script>
@endsection
