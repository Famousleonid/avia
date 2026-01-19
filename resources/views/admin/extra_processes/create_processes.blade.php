@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1050px;
        }

        /* Стили для длинного текста процесса */
        .process-text-long {
            font-size: 0.65rem;
            line-height: 0.9;
            letter-spacing: -0.3px;
            transform: scale(0.9);
            transform-origin: left;
        }

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"]  .select2-selection--single {
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

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
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

        /* Увеличенный размер для дополнительного NDT селекта */
        .select2-ndt-plus + .select2-container .select2-selection--multiple {
            min-height: 70px !important;
            padding: 12px !important;
            font-size: 16px !important;
            line-height: 1.5 !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__rendered {
            padding: 8px 12px !important;
            min-height: 60px !important;
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-end !important;
            align-items: center !important;
            flex-grow: 1 !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__choice {
            margin: 6px 6px 6px 0 !important;
            padding: 8px 12px !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline {
            order: -1 !important;
            flex-grow: 0 !important;
            margin-right: auto !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline .select2-search__field {
            padding: 8px 12px !important;
            font-size: 16px !important;
            min-height: 40px !important;
            width: auto !important;
            min-width: 200px !important;
        }

        /* Скрываем поле поиска, когда есть выбранные элементы */
        .select2-ndt-plus + .select2-container.has-selections .select2-search--inline {
            display: none !important;
        }

        /* Выделение выбранных тегов дополнительных NDT процессов */
        .select2-ndt-plus + .select2-container .select2-selection__choice {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: white !important;
            font-weight: 500 !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__choice__remove {
            color: white !important;
            opacity: 0.8 !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__choice__remove:hover {
            color: white !important;
            opacity: 1 !important;
        }

        /* Серый стиль для блока с деталями дополнительных NDT процессов */
        .ndt-plus-process-options {
            opacity: 0.6;
            color: #6c757d;
        }

        .ndt-plus-process-options .form-check-label {
            color: #6c757d !important;
        }

        .ndt-plus-process-options .fw-bold {
            color: #6c757d !important;
        }

        .card-body {
            max-height: 75vh;
            overflow-y: auto;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{__('Add Extra Processes to Component')}}</h4>
                    <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
                </div>
                <div class="d-flex justify-content-between align-items-center position-relative">
                    <div>
                        <strong>Component:</strong> {{ $component->name }}<br>
                        <strong>IPL:</strong> {{ $component->ipl_num }}<br>
                        <strong>Part Number:</strong> {{ $component->part_number }}
                    </div>
                    <div class="position-absolute start-50 translate-middle-x">
                        <button class="btn btn-outline-primary" type="button" style="width: 120px" id="add-process">
                            Add Process
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" form="createProcessesForm" class="btn btn-outline-primary" disabled>{{ __('Save') }}</button>
                        <a href="{{ route('extra_processes.show_all', ['id' => $current_wo->id]) }}"
                           class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
{{--                @if($existingExtraProcess && $existingExtraProcess->processes)--}}
{{--                    <div class="alert alert-secondary">--}}
{{--                        <strong class="m-1">Existing Processes:</strong><br>--}}
{{--                        @if(is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1))--}}
{{--                             Старая структура: ассоциативный массив --}}
{{--                            @foreach($existingExtraProcess->processes as $processNameId => $processId)--}}
{{--                                @php--}}
{{--                                    $processName = \App\Models\ProcessName::find($processNameId);--}}
{{--                                    $process = \App\Models\Process::find($processId);--}}
{{--                                @endphp--}}
{{--                                @if($processName && $process)--}}
{{--                                    <span class="badge bg-secondary ms-5  @if(strlen($process->process) > 40) process-text-long--}}
{{--                                    @endif">{{ $processName->name }}: {{ $process->process }}</span>--}}
{{--                                @endif--}}
{{--                            @endforeach--}}
{{--                        @else--}}
{{--                             Новая структура: массив объектов --}}
{{--                            @foreach($existingExtraProcess->processes as $processItem)--}}
{{--                                @php--}}
{{--                                    $processName = \App\Models\ProcessName::find($processItem['process_name_id']);--}}
{{--                                    $process = \App\Models\Process::find($processItem['process_id']);--}}
{{--                                @endphp--}}
{{--                                @if($processName && $process)--}}
{{--                                    <span class="badge bg-secondary ms-5 @if(strlen($process->process) > 40) process-text-long--}}
{{--                                    @endif">{{ $processName->name }}: {{ $process->process }}</span>--}}
{{--                                @endif--}}
{{--                            @endforeach--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                @endif--}}

                <form id="createProcessesForm" role="form" method="POST" action="{{route('extra_processes.store_processes')}}" class="createProcessesForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">
                    <input type="hidden" name="component_id" value="{{$component->id }}">

                    <div class="form-group mb-3">
{{--                        <div class="d-flex justify-content-around">--}}
{{--                            <div>--}}
{{--                                <label for="serial_num" class="form-label">Serial Number</label>--}}
{{--                                <input type="text" name="serial_num" id="serial_num" class="form-control" style="width: 250px" value="{{ $existingExtraProcess->serial_num ?? '' }}">--}}
{{--                            </div>--}}
{{--                            <div style="width: 150px">--}}
{{--                                <label for="qty" class="form-label">Quantity</label>--}}
{{--                                <input type="number" name="qty" id="qty" class="form-control" value="{{ $existingExtraProcess->qty ?? 1 }}" min="1" required>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </div>

                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <!-- Начальная строка -->
                        <div class="process-row mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process"

                                            required>
                                        <option value="">Select Process Name</option>
                            @foreach ($processNames as $processName)
                                @php
                                    $isSelected = false;

                                    // Исключаем процесс, если он уже выбран в других строках (для всех процессов, включая NDT)
                                    if ($existingExtraProcess && $existingExtraProcess->processes) {
                                        if (is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1)) {
                                            // Старая структура: ассоциативный массив
                                            $isSelected = isset($existingExtraProcess->processes[$processName->id]);
                                        } else {
                                            // Новая структура: массив объектов
                                            foreach ($existingExtraProcess->processes as $processItem) {
                                                // Проверяем только основной process_name_id, не plus_process_names
                                                if (isset($processItem['process_name_id']) && $processItem['process_name_id'] == $processName->id) {
                                                    $isSelected = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @if(!$isSelected)
                                    <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                @endif
                            @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="process">Processes (Specification):</label>

                                    <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                            data-bs-target="#addProcessModal">
                                        <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                             style="width: 20px;" class="" >
                                    </button>

                                    <div class="process-options">
                                        <!-- Здесь будут radio buttons для выбранного имени процесса -->
                                    </div>

{{--                                     Дополнительный селект для NDT процессов (скрыт по умолчанию)--}}
                                    <div class="ndt-plus-process-container mt-3" style="display: none; visibility: visible;">
                                        <label for="ndt_plus_process_0">Additional NDT Process(es):</label>
                                        <select name="processes[0][ndt_plus_process][]"
                                                class="form-control select2-ndt-plus"
                                                id="ndt_plus_process_0"
                                                data-row-index="0"
                                                multiple
                                                style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                                            @foreach ($ndtProcessNames as $ndtProcessName)
                                                <option value="{{ $ndtProcessName->id }}"
                                                        data-process-name="{{ $ndtProcessName->name }}">
                                                    {{ $ndtProcessName->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ndt-plus-process-options mt-2">
                                            <!-- Здесь будут radio buttons для дополнительных NDT процессов -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div>
                                        <label for="description_0" class="form-label" style="margin-bottom: -5px">Description</label>
                                        <input type="text" class="form-control" id="description_0" name="processes[0][description]" placeholder="CMM fig.___ pg. ___">
                                    </div>
                                    <div>
                                        <label for="notes_0" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes_0" name="processes[0][notes]" placeholder="Enter Notes">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления процесса -->
    <div class="modal fade" id="addProcessModal" tabindex="-1" aria-labelledby="addProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Заголовок модального окна -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addProcessModalLabel">
                        Enter Process (<span id="modalProcessName"></span>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Тело модального окна -->
                <div class="modal-body">
                    <!-- Поле для ввода нового процесса -->
                    <div class="mb-3">
                        <label for="newProcessInput" class="form-label">New Process</label>
                        <input type="text" class="form-control" id="newProcessInput" placeholder="Enter new process">
                    </div>
                    <!-- Секция "Existing Processes" – подгружаются availableProcesses -->
                    <div class="mb-3">
                        <h6>Existing Processes</h6>
                        <div id="existingProcessContainer">
                            Loading processes...
                        </div>
                    </div>
                    <!-- Скрытое поле для хранения выбранного process_name_id -->
                    <input type="hidden" id="modalProcessNameId">
                </div>
                <!-- Футер модального окна -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProcessModal">Save Process</button>
                </div>
            </div>
        </div>
    </div>
@endsection

    @section('scripts')
    <script>
        // Получаем все NDT process_names_id для проверки
        const ndtProcessNames = @json($ndtProcessNames->pluck('id')->toArray());
        const ndtProcessNamesData = @json($ndtProcessNames->keyBy('id'));

        // Функция для проверки, является ли процесс NDT
        function isNdtProcess(processNameId) {
            return ndtProcessNames.includes(parseInt(processNameId));
        }

        // Динамическое добавление новых строк
        document.getElementById('add-process').addEventListener('click', function () {
            const container = document.getElementById('processes-container');
            const index = container.children.length;

            const newRow = document.createElement('div');
            newRow.classList.add('process-row', 'mb-3');
            newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label for="process_names">Process Name:</label>
                        <select name="processes[${index}][process_names_id]" class="form-control select2-process" required>
                            <option value="">Select Process Name</option>
                            @foreach ($processNames as $processName)
                                @php
                                    $isSelected = false;

                                    // Исключаем процесс, если он уже выбран в других строках (для всех процессов, включая NDT)
                                    if ($existingExtraProcess && $existingExtraProcess->processes) {
                                        if (is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1)) {
                                            // Старая структура: ассоциативный массив
                                            $isSelected = isset($existingExtraProcess->processes[$processName->id]);
                                        } else {
                                            // Новая структура: массив объектов
                                            foreach ($existingExtraProcess->processes as $processItem) {
                                                // Проверяем только основной process_name_id, не plus_process_names
                                                if (isset($processItem['process_name_id']) && $processItem['process_name_id'] == $processName->id) {
                                                    $isSelected = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @if(!$isSelected)
                                    <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="process">Processes:</label>
                        <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                data-bs-target="#addProcessModal">
                            <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                 style="width: 20px;" class="" >
                        </button>
                        <div class="process-options">
                            <!-- Здесь будут radio buttons для выбранного имени процесса -->
                        </div>

                        {{-- Дополнительный селект для NDT процессов (скрыт по умолчанию) --}}
                        <div class="ndt-plus-process-container mt-3" style="display: none; visibility: visible;">
                            <label for="ndt_plus_process_${index}">Additional NDT Process(es):</label>
                            <select name="processes[${index}][ndt_plus_process][]"
                                    class="form-control select2-ndt-plus"
                                    id="ndt_plus_process_${index}"
                                    data-row-index="${index}"
                                    multiple
                                    style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                                @foreach ($ndtProcessNames as $ndtProcessName)
                                    <option value="{{ $ndtProcessName->id }}"
                                            data-process-name="{{ $ndtProcessName->name }}">
                                        {{ $ndtProcessName->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="ndt-plus-process-options mt-2">
                                <!-- Здесь будут radio buttons для дополнительных NDT процессов -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div>
                            <label for="description_${index}" class="form-label" style="margin-bottom: -5px">Description</label>
                            <input type="text" class="form-control" id="description_${index}" name="processes[${index}][description]" placeholder="CMM fig.___ pg. ___">
                        </div>
                        <div>
                            <label for="notes_${index}" class="form-label" style="margin-bottom: -5px">Notes</label>
                            <input type="text" class="form-control" id="notes_${index}" name="processes[${index}][notes]" placeholder="Enter Notes">
                        </div>
                    </div>
                </div>`;

            container.appendChild(newRow);

            // Инициализируем Select2 для нового селекта Process Name
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(newRow).find('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                }).on('select2:select', function (e) {
                    const selectElement = e.target;
                    const processNameId = selectElement.value;
                    console.log('select2:select event fired', { processNameId, selectElement });

                    const processRow = selectElement.closest('.process-row');

                    // Показываем/скрываем селект для дополнительных NDT процессов
                    const ndtPlusContainer = processRow ? processRow.querySelector('.ndt-plus-process-container') : null;
                    if (ndtPlusContainer) {
                        if (isNdtProcess(processNameId)) {
                            ndtPlusContainer.style.display = 'block';
                            // Инициализируем Select2 для дополнительного селекта NDT
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');
                                if (!isSelect2Initialized) {
                                    $(ndtPlusSelect).select2({
                                        theme: 'bootstrap-5',
                                        width: '100%',
                                        multiple: true,
                                        placeholder: 'Select Additional NDT Process(es)'
                                    }).on('select2:select select2:unselect select2:close', function (e) {
                                        loadNdtPlusProcesses(this);
                                        updateSelect2SearchVisibility(this);
                                    });
                                }
                                // Исключаем выбранный NDT процесс из опций
                                $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                                $(ndtPlusSelect).trigger('change');

                                // Обновляем видимость поля поиска
                                setTimeout(() => {
                                    updateSelect2SearchVisibility(ndtPlusSelect);
                                }, 50);
                            }
                        } else {
                            ndtPlusContainer.style.display = 'none';
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                $(ndtPlusSelect).val(null).trigger('change');
                            }
                        }
                    }

                    // Загружаем процессы для выбранного Process Name
                    loadProcessesForRow(selectElement);
                });
            }

            // Инициализируем Select2 для дополнительного селекта NDT в новой строке
            if (typeof $ !== 'undefined' && $.fn.select2) {
                const ndtPlusSelect = newRow.querySelector('.select2-ndt-plus');
                if (ndtPlusSelect) {
                    $(ndtPlusSelect).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        multiple: true,
                        placeholder: 'Select Additional NDT Process(es)'
                    }).on('select2:select select2:unselect select2:close', function (e) {
                        loadNdtPlusProcesses(this);
                        updateSelect2SearchVisibility(this);
                    });
                }
            }
        });

        // Обработка отправки формы
        document.getElementById('createProcessesForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const workorderId = document.querySelector('input[name="workorder_id"]').value;
            const componentId = document.querySelector('input[name="component_id"]').value;
            const serial_numInput = document.querySelector('input[name="serial_num"]');
            const qtyInput = document.querySelector('input[name="qty"]');
            const serial_num = serial_numInput ? serial_numInput.value : null;
            const qty = qtyInput ? qtyInput.value : 1;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            let hasSelectedRadio = false;

            processRows.forEach((row, rowIndex) => {
                const processNameSelect = row.querySelector('.select2-process');
                if (!processNameSelect) {
                    return;
                }

                const processNameId = processNameSelect.value;
                if (!processNameId) {
                    return;
                }

                const processName = processNameSelect.options[processNameSelect.selectedIndex] ?
                    processNameSelect.options[processNameSelect.selectedIndex].text : '';

                const selectedRadio = row.querySelector('.process-options input[type="radio"]:checked');

                // Получаем значение description
                const descriptionInput = row.querySelector('input[name*="[description]"]');
                const descriptionValue = descriptionInput ? descriptionInput.value.trim() : null;

                // Получаем значение notes
                const notesInput = row.querySelector('input[name*="[notes]"]');
                const notesValue = notesInput ? notesInput.value.trim() : null;

                if (selectedRadio) {
                    const processId = selectedRadio.value;

                    // Собираем данные о дополнительных NDT процессах
                    const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                    const plusProcessNames = [];
                    const plusProcessIds = [];

                    if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                        const selectedNdtPlusIds = $(ndtPlusSelect).val() || [];
                        if (selectedNdtPlusIds.length > 0) {
                            plusProcessNames.push(...selectedNdtPlusIds.map(id => parseInt(id)));

                            // Собираем process_id из radio buttons для дополнительных NDT
                            const ndtPlusRadios = row.querySelectorAll('.ndt-plus-process-options input[type="radio"]:checked');
                            ndtPlusRadios.forEach(radio => {
                                plusProcessIds.push(parseInt(radio.value));
                            });
                        }
                    }

                    // Формируем объект процесса
                    const processData = {
                        process_names_id: processNameId,
                        processes: [parseInt(processId)], // Массив с одним элементом для совместимости
                        description: descriptionValue || null,
                        notes: notesValue || null
                    };

                    // Если это NDT процесс с дополнительными NDT, добавляем поля
                    if (isNdtProcess(processNameId) && plusProcessNames.length > 0) {
                        processData.plus_process_names = plusProcessNames;
                        processData.plus_process_ids = plusProcessIds;
                    }

                    processesData.push(processData);
                    hasSelectedRadio = true;
                }
            });

            if (!hasSelectedRadio) {
                alert('Process not added because no process is selected.');
                return;
            }

            const requestBody = {
                workorder_id: workorderId,
                component_id: componentId,
                serial_num: serial_num,
                qty: qty,
                processes: JSON.stringify(processesData)
            };

            fetch(`{{ route('extra_processes.store_processes') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        let errorData;
                        try {
                            errorData = JSON.parse(text);
                        } catch (e) {
                            errorData = { message: text || `HTTP ${response.status}: ${response.statusText}` };
                        }
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }

                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.message) {
                    alert(data.message);
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                console.error('Error stack:', error.stack);
                alert('Error saving processes: ' + error.message);
            });
        });

        // Обновление radio buttons при изменении выбранного имени процесса
        // Этот обработчик для нативных select элементов (если Select2 не инициализирован)
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('select2-process') && !$(event.target).hasClass('select2-hidden-accessible')) {
                // Только для нативных select, не для Select2
                loadProcessesForRow(event.target);
            }
        });

        // Функция для загрузки процессов для выбранного Process Name
        function loadProcessesForRow(selectElement) {
            const processNameId = selectElement.value;
            const processRow = selectElement.closest('.process-row');
            const processOptionsContainer = processRow ? processRow.querySelector('.process-options') : null;
            const manualId = document.getElementById('processes-container') ? document.getElementById('processes-container').dataset.manualId : null;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) {
                return;
            }

            processOptionsContainer.innerHTML = '';

            if (processNameId) {
                fetch(`/get-process/${processNameId}?manual_id=${manualId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.length > 0) {
                            // Получаем индекс строки для правильного именования
                            const container = document.getElementById('processes-container');
                            const rows = container.querySelectorAll('.process-row');
                            const index = Array.from(rows).indexOf(processOptionsContainer.closest('.process-row'));

                            data.forEach(process => {
                                const radioDiv = document.createElement('div');
                                radioDiv.classList.add('form-check');
                                radioDiv.innerHTML = `
                                    <input type="radio" name="processes[${index}][process]" value="${process.id}" class="form-check-input" required>
                                    <label class="form-check-label">${process.process}</label>
                                `;
                                processOptionsContainer.appendChild(radioDiv);
                            });
                            if (saveButton) {
                                saveButton.disabled = false;
                            }
                        } else {
                            const noSpecLabel = document.createElement('div');
                            noSpecLabel.classList.add('text-muted', 'mt-2');
                            noSpecLabel.innerHTML = 'No specification. Add specification for this process.';
                            processOptionsContainer.appendChild(noSpecLabel);
                            if (saveButton) {
                                saveButton.disabled = true;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка при получении процессов:', error);
                        if (saveButton) {
                            saveButton.disabled = true;
                        }
                    });
            }
        }

        // Функция для управления видимостью поля поиска в Select2
        function updateSelect2SearchVisibility(selectElement) {
            if (!selectElement || typeof $ === 'undefined') return;

            const selectedValues = $(selectElement).val() || [];
            const select2Container = $(selectElement).next('.select2-container');

            if (select2Container.length) {
                if (selectedValues.length > 0) {
                    select2Container.addClass('has-selections');
                } else {
                    select2Container.removeClass('has-selections');
                }
            }
        }

        // Функция для загрузки процессов для дополнительных NDT
        function loadNdtPlusProcesses(selectElement) {
            const selectedValues = $(selectElement).val() || [];
            const processRow = selectElement.closest('.process-row');
            const ndtPlusOptionsContainer = processRow.querySelector('.ndt-plus-process-options');
            const manualId = document.getElementById('processes-container').dataset.manualId;

            if (!ndtPlusOptionsContainer || selectedValues.length === 0) {
                if (ndtPlusOptionsContainer) {
                    ndtPlusOptionsContainer.innerHTML = '';
                }
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Загружаем процессы для каждого выбранного дополнительного NDT
            const loadPromises = selectedValues.map(ndtProcessNameId => {
                return fetch(`/get-process/${ndtProcessNameId}?manual_id=${manualId}`)
                    .then(response => response.json())
                    .then(data => {
                        return { ndtProcessNameId, processes: data || [] };
                    });
            });

            Promise.all(loadPromises).then(results => {
                ndtPlusOptionsContainer.innerHTML = '';

                // Получаем индекс строки
                const container = document.getElementById('processes-container');
                const rows = container.querySelectorAll('.process-row');
                const index = Array.from(rows).indexOf(processRow);

                results.forEach(({ ndtProcessNameId, processes }) => {
                    if (processes.length > 0) {
                        const ndtProcessName = ndtProcessNamesData[ndtProcessNameId];
                        const processNameLabel = ndtProcessName ? ndtProcessName.name : `NDT-${ndtProcessNameId}`;

                        const label = document.createElement('div');
                        label.className = 'fw-bold mt-2';
                        label.textContent = processNameLabel + ':';
                        ndtPlusOptionsContainer.appendChild(label);

                        processes.forEach(process => {
                            const radioDiv = document.createElement('div');
                            radioDiv.classList.add('form-check');
                            radioDiv.innerHTML = `
                                <input type="radio"
                                       name="processes[${index}][ndt_plus_process_radio][${ndtProcessNameId}]"
                                       value="${process.id}"
                                       class="form-check-input ndt-plus-process-radio"
                                       data-ndt-process-name-id="${ndtProcessNameId}"
                                       required>
                                <label class="form-check-label">${process.process}</label>
                            `;
                            ndtPlusOptionsContainer.appendChild(radioDiv);
                        });
                    }
                });

                // Обновляем видимость поля поиска после загрузки
                updateSelect2SearchVisibility(selectElement);
            }).catch(error => {
                console.error('Ошибка при загрузке процессов для дополнительных NDT:', error);
                ndtPlusOptionsContainer.innerHTML = '<div class="text-danger">Error loading processes</div>';
            });
        }

        // Инициализация для начальной строки
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализируем Select2 для начальной строки
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                }).on('select2:select', function (e) {
                    const selectElement = e.target;
                    const processNameId = selectElement.value;
                    console.log('select2:select event fired', { processNameId, selectElement });

                    const processRow = selectElement.closest('.process-row');

                    // Показываем/скрываем селект для дополнительных NDT процессов
                    const ndtPlusContainer = processRow ? processRow.querySelector('.ndt-plus-process-container') : null;
                    if (ndtPlusContainer) {
                        if (isNdtProcess(processNameId)) {
                            ndtPlusContainer.style.display = 'block';
                            // Инициализируем Select2 для дополнительного селекта NDT
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');
                                if (!isSelect2Initialized) {
                                    $(ndtPlusSelect).select2({
                                        theme: 'bootstrap-5',
                                        width: '100%',
                                        multiple: true,
                                        placeholder: 'Select Additional NDT Process(es)'
                                    }).on('select2:select select2:unselect select2:close', function (e) {
                                        loadNdtPlusProcesses(this);
                                        updateSelect2SearchVisibility(this);
                                    });
                                }
                                // Исключаем выбранный NDT процесс из опций
                                $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                                $(ndtPlusSelect).trigger('change');

                                // Обновляем видимость поля поиска
                                setTimeout(() => {
                                    updateSelect2SearchVisibility(ndtPlusSelect);
                                }, 50);
                            }
                        } else {
                            ndtPlusContainer.style.display = 'none';
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                $(ndtPlusSelect).val(null).trigger('change');
                            }
                        }
                    }

                    // Загружаем процессы для выбранного Process Name
                    loadProcessesForRow(selectElement);
                });
            }
        });

        // Глобальная переменная для хранения ссылки на текущую строку
        let currentRow = null;

        // Используем делегирование событий на контейнере
        document.getElementById('processes-container').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn[data-bs-target="#addProcessModal"]');
            if (!btn) return;

            currentRow = btn.closest('.process-row');
            const select = currentRow.querySelector('.select2-process');
            const processNameId = select.value;
            const processNameText = select.options[select.selectedIndex].text;
            document.getElementById('modalProcessName').innerText = processNameText;
            document.getElementById('modalProcessNameId').value = processNameId;

            const manualId = document.getElementById('processes-container').dataset.manualId;

            fetch(`/get-process/${processNameId}?manual_id=${manualId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('existingProcessContainer');
                    container.innerHTML = '';

                    if (data && data.length > 0) {
                        data.forEach(process => {
                            const div = document.createElement('div');
                            div.classList.add('form-check');
                            div.innerHTML = `
                                <input type="radio" name="existingProcess" value="${process.id}" class="form-check-input">
                                <label class="form-check-label">${process.process}</label>
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<p class="text-muted">No existing processes found.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading existing processes:', error);
                    document.getElementById('existingProcessContainer').innerHTML = '<p class="text-danger">Error loading processes.</p>';
                });
        });

        // Обработка сохранения нового процесса
        document.getElementById('saveProcessModal').addEventListener('click', function() {
            const newProcessInput = document.getElementById('newProcessInput');
            const processNameId = document.getElementById('modalProcessNameId').value;
            const newProcessName = newProcessInput.value.trim();
            const manualId = document.getElementById('processes-container').dataset.manualId;

            if (!newProcessName) {
                alert('Please enter a process name.');
                return;
            }

            // Отправляем запрос на создание нового процесса
            fetch('/store-process', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    process_name_id: processNameId,
                    process: newProcessName,
                    manual_id: manualId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закрываем модальное окно
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProcessModal'));
                    modal.hide();

                    // Очищаем поле ввода
                    newProcessInput.value = '';

                    // Обновляем список процессов для текущего process name
                    const currentSelect = currentRow.querySelector('.select2-process');
                    if (currentSelect) {
                        currentSelect.dispatchEvent(new Event('change'));
                    }

                    alert('Process added successfully!');
                } else {
                    alert('Error adding process: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding process: ' + error.message);
            });
        });
    </script>
@endsection
