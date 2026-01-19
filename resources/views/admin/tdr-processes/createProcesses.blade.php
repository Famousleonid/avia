
@extends('admin.master')

@section('content')

    <style>
        .container {
            max-width: 1080px;
        }

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

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
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

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;
        }

        .card-body {
            max-height: 80vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Стили для скроллбара в card-body */
        .card-body::-webkit-scrollbar {
            width: 8px;
        }

        .card-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .card-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .card-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Стили для скроллбара в темной теме */
        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-track {
            background: #2d2d2d;
        }

        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-thumb {
            background: #555;
        }

        html[data-bs-theme="dark"] .card-body::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center position-relative">
                    <div>
                        <h4 class="text-primary mb-0">{{ __('Add Part Processes') }}</h4>
                        <div class="mt-2">
                            {{ $current_tdr->component->name }}
                            <div>
                                PN: {{ $current_tdr->component->part_number }}
                                SN: {{ $current_tdr->serial_number }}
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute start-50 translate-middle-x">
                        <button class="btn btn-outline-success" type="button" style="width: 120px" id="add-process">
                            Add Process
                        </button>
                    </div>
                    <div class="align-items-center">
                        <h4 class="pe-3 mb-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                        <div>
                            <button type="submit" form="createCPForm" class="btn btn-outline-primary">{{ __('Save') }}</button>
                            <a href="{{ route('tdr-processes.processes', ['tdrId' => $current_tdr->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        </div>
                        </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tdr-processes.store', $current_tdr->id) }}" enctype="multipart/form-data" id="createCPForm">
                    @csrf
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">

                    <!-- Контейнер для строк с процессами -->
                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <!-- Начальная строка -->
                        <div class="process-row mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required
                                            data-process-data='@json($processNames->keyBy('id'))'>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}"
                                                    data-process-id="{{ $processName->id }}"
                                                    {{-- Примеры использования id в Blade @if --}}
                                                    @if($processName->id == 1)
                                                        data-is-special="true"
                                                    @elseif($processName->id >= 5 && $processName->id <= 10)
                                                        data-is-range="true"
                                                    @endif
                                                    @if(in_array($processName->id, [2, 3, 4]))
                                                        data-is-group="true"
                                                    @endif>
                                                {{ $processName->name }}
                                            </option>
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
                                        <!-- Здесь будут чекбоксы для выбранного имени процесса -->
                                    </div>

                                    <!-- Дополнительный селект для NDT процессов (скрыт по умолчанию) -->
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
                                            <!-- Здесь будут чекбоксы для дополнительных NDT процессов -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
{{--                                    <label for="ec">EC:</label>--}}
{{--                                    <div class="mb-2">--}}
{{--                                        <small class="text-muted">Process ID: <span id="process-name-id-0" class="fw-bold">-</span></small>--}}
{{--                                    </div>--}}

                                    <div class="form-check mt-2" style="display: none;">
                                        <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_0">
                                        <label class="form-check-label" for="ec_0">
                                            EC
                                        </label>
                                    </div>
                                    <div>
                                        <label for="description" class="form-label" style="margin-bottom:
                                        -5px">Description</label>
                                        <input type="text" class="form-control" id="description_0"
                                               name="processes[0][description]" placeholder="CMM fig.___ pg. ___">

                                        <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes" name="processes[0][notes]"
                                               placeholder="Enter Notes">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

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
                            <!-- Секция "Available Processes" – доступные для выбора процессы -->
                            <!-- Existing Processes не отображаются в модальном окне -->
                            <div class="mb-3">
                                <h6>Available Processes</h6>
                                <div id="availableProcessContainer">
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

        </div>
    </div>

    <script>
        // Универсальная функция для получения id выбранного Process Name
        // Параметр: element - элемент select или строка process-row
        function getSelectedProcessNameId(element) {
            let select;

            // Если передан select элемент напрямую
            if (element.classList && element.classList.contains('select2-process')) {
                select = element;
            }
            // Если передан элемент строки (process-row)
            else if (element.classList && element.classList.contains('process-row')) {
                select = element.querySelector('.select2-process');
            }
            // Если передан event объект
            else if (element.target) {
                select = element.target;
            }

            if (select) {
                return select.value; // Возвращает id выбранного Process Name
            }

            return null;
        }

        // Данные процессов для использования в динамически создаваемых строках
        const processNamesData = @json($processNames->keyBy('id'));

        // Получаем все NDT process_names_id для проверки
        const ndtProcessNames = @json($ndtProcessNames->pluck('id')->toArray());
        const ndtProcessNamesData = @json($ndtProcessNames->keyBy('id'));

        // Функция для проверки, является ли процесс NDT
        function isNdtProcess(processNameId) {
            return ndtProcessNames.includes(parseInt(processNameId));
        }

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
            machiningProcessNameId = null; // Процесс Machining не найден
            console.warn('Machining process not found in processNames');
        @endif

        // Функция для проверки, является ли процесс Machining
        function isMachiningProcess(processNameId) {
            const result = machiningProcessNameId !== null && processNameId == machiningProcessNameId.toString();
            // Отладочная информация (можно убрать в продакшене)
            if (processNameId) {
                console.log('isMachiningProcess check:', {
                    processNameId: processNameId,
                    machiningProcessNameId: machiningProcessNameId,
                    result: result
                });
            }
            return result;
        }

        // Динамическое добавление новых строк
        document.getElementById('add-process').addEventListener('click', function () {
            const container = document.getElementById('processes-container');
            const index = container.children.length;

            // Формируем опции для select
            let optionsHtml = '<option value="">Select Process Name</option>';
            @foreach ($processNames as $processName)
                optionsHtml += `<option value="{{ $processName->id }}"
                    data-process-id="{{ $processName->id }}"
                    @if($processName->id == 1)
                        data-is-special="true"
                    @elseif($processName->id >= 5 && $processName->id <= 10)
                        data-is-range="true"
                    @endif
                    @if(in_array($processName->id, [2, 3, 4]))
                        data-is-group="true"
                    @endif>{{ $processName->name }}</option>`;
            @endforeach

            const newRow = document.createElement('div');
            newRow.classList.add('process-row', 'mb-3');
            newRow.innerHTML = `
                <div class="row ">
                    <div class="col-md-3">
                        <label for="process_names">Process Name:</label>
                        <select name="processes[${index}][process_names_id]"
                                class="form-control select2-process"
                                required
                                data-process-data='@json($processNames->keyBy('id'))'>
                            ${optionsHtml}
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
                <!-- Здесь будут чекбоксы для выбранного имени процесса -->
            </div>

            <!-- Дополнительный селект для NDT процессов (скрыт по умолчанию) -->
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
                    <!-- Здесь будут чекбоксы для дополнительных NDT процессов -->
                </div>
            </div>
        </div>
        <div class="col-md-2">
             {{--                                    <label for="ec">EC:</label>
            <div class="mb-2">
                <small class="text-muted">Process ID: <span id="process-name-id-${index}" class="fw-bold">-</span></small>
            </div>--}}
            <div class="form-check mt-2" style="display: none;">
                <input type="checkbox" name="processes[${index}][ec]" value="1" class="form-check-input" id="ec_${index}">
                <label class="form-check-label" for="ec_${index}">
                    EC
                </label>
            </div>
            <div>
                <label for="description_${index}" class="form-label" style="margin-bottom: -5px">Description</label>
                <input type="text" class="form-control" id="description_${index}" name="processes[${index}][description]" placeholder="CMM fig.___ pg. ___">
                <label for="notes_${index}" class="form-label" style="margin-bottom: -5px">Notes</label>
                <input type="text" class="form-control" id="notes_${index}" name="processes[${index}][notes]" placeholder="Enter Notes">
            </div>
        </div>
    </div>`;

            container.appendChild(newRow);

            // Инициализируем Select2 для нового select элемента
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(newRow).find('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                }).on('select2:select', function (e) {
                    // Обработчик события Select2 для загрузки процессов
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

                    loadProcessesForRow(selectElement);

                    // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                });

                // Инициализируем Select2 для дополнительного селекта NDT (множественный выбор)
                $(newRow).find('.select2-ndt-plus').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    multiple: true,
                    placeholder: 'Select Additional NDT Process(es)'
                });

                // Исключаем уже выбранный NDT процесс из дополнительного селекта при инициализации
                const processNameSelect = newRow.querySelector('.select2-process');
                if (processNameSelect && processNameSelect.value) {
                    const processNameId = processNameSelect.value;
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = newRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                }
            }
        });



        // Обработка отправки формы
        document.getElementById('createCPForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const tdrId = document.querySelector('input[name="tdrs_id"]').value;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            let hasCheckedCheckbox = false;

            processRows.forEach(row => {
                const processNameSelect = row.querySelector('.select2-process');
                const processNameId = processNameSelect.value;
                const processName = processNameSelect.options[processNameSelect.selectedIndex].text;

                const checkboxes = row.querySelectorAll('.process-options input[type="checkbox"]:checked');
                const selectedProcessIds = [];

                checkboxes.forEach(checkbox => {
                    const processId = checkbox.value; // Получаем ID процесса
                    selectedProcessIds.push(parseInt(processId)); // Сохраняем ID
                    hasCheckedCheckbox = true; // Хотя бы один чекбокс отмечен
                });

                // Собираем данные о дополнительных NDT процессах (множественный выбор)
                const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                const ndtPlusProcessNameIds = [];
                const ndtPlusProcessIds = [];

                if (ndtPlusSelect) {
                    // Получаем все выбранные дополнительные NDT process_names_id из селекта
                    const selectedNdtPlusProcessNameIds = Array.from(ndtPlusSelect.selectedOptions).map(opt => opt.value);

                    // Получаем выбранные процессы для всех дополнительных NDT
                    const ndtPlusCheckboxes = row.querySelectorAll('.ndt-plus-process-checkbox:checked');
                    ndtPlusCheckboxes.forEach(checkbox => {
                        const ndtProcessNameId = checkbox.getAttribute('data-ndt-process-name-id');
                        // Проверяем, что это процессы для одного из выбранных дополнительных NDT
                        if (selectedNdtPlusProcessNameIds.includes(ndtProcessNameId)) {
                            ndtPlusProcessIds.push(parseInt(checkbox.value));
                        }
                    });

                    // Добавляем все выбранные process_names_id дополнительных NDT
                    selectedNdtPlusProcessNameIds.forEach(id => {
                        if (id && !ndtPlusProcessNameIds.includes(id)) {
                            ndtPlusProcessNameIds.push(id);
                        }
                    });
                }

                // Получаем значение чекбокса EC
                const ecCheckbox = row.querySelector('input[name*="[ec]"]');
                const ecValue = ecCheckbox ? ecCheckbox.checked : false;

                // Получаем значение description
                const descriptionInput = row.querySelector('input[name*="[description]"]');
                const descriptionValue = descriptionInput ? descriptionInput.value.trim() : null;

                // Получаем значение notes
                const notesInput = row.querySelector('input[name*="[notes]"]');
                const notesValue = notesInput ? notesInput.value.trim() : null;

                if (selectedProcessIds.length > 0) {
                    // Объединяем все процессы (основные + дополнительные NDT)
                    const allProcessIds = [...selectedProcessIds, ...ndtPlusProcessIds];

                    // Формируем строку plus_process из всех выбранных дополнительных NDT process_names_id
                    const plusProcessString = ndtPlusProcessNameIds.length > 0
                        ? ndtPlusProcessNameIds.sort((a, b) => parseInt(a) - parseInt(b)).join(',')
                        : null;

                    processesData.push({
                        process_names_id: processNameId,
                        plus_process: plusProcessString, // Дополнительные NDT process_names_id через запятую (отсортированные)
                        processes: allProcessIds, // Объединенный массив ID процессов
                        ec: ecValue, // Добавляем значение EC
                        description: descriptionValue || null, // Добавляем значение description
                        notes: notesValue || null // Добавляем значение notes
                    });
                }
            });

            // Если ни один чекбокс не отмечен, выводим сообщение
            if (!hasCheckedCheckbox) {
                alert('Process not added because no checkbox is selected.');
                return; // Прерываем выполнение
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('tdrs_id', tdrId);
            formData.append('processes', JSON.stringify(processesData));

            fetch(`{{ route('tdr-processes.store') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tdrs_id: tdrId,
                    processes: processesData
                })
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || err.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Success:', data);
                    if (data.message) {
                        // Показываем сообщение об успехе
                        if (window.NotificationHandler) {
                            window.NotificationHandler.success(data.message);
                        } else {
                            alert(data.message);
                        }
                    }
                    // Если есть URL для перенаправления, выполняем редирект
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMessage = error.message || 'Error saving processes.';

                    // Показываем уведомление об ошибке
                    if (window.NotificationHandler) {
                        window.NotificationHandler.error('Ошибка при сохранении процессов: ' + errorMessage);
                    } else {
                        alert('Ошибка при сохранении процессов: ' + errorMessage);
                    }
                });
        });

        // Функция для загрузки и отображения процессов
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

            // Используем processes.getProcesses для получения existingProcesses и availableProcesses
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

                    // Отображаем только existingProcesses (уже связанные с manual_id)
                    // Не отмечены, без пометки "(existing)"
                    if (data.existingProcesses && data.existingProcesses.length > 0) {
                        data.existingProcesses.forEach(process => {
                            const checkbox = document.createElement('div');
                            checkbox.classList.add('form-check');
                            checkbox.innerHTML = `
                                <input type="checkbox" name="processes[${rowIndex}][process][]" value="${process.id}" class="form-check-input">
                                <label class="form-check-label">${process.process}</label>
                            `;
                            processOptionsContainer.appendChild(checkbox);
                            hasProcesses = true;
                        });
                    }

                    // availableProcesses не отображаем на странице, только в модальном окне

                    if (hasProcesses) {
                        saveButton.disabled = false;

                        // Если это NDT процесс и есть выбранные процессы, показываем дополнительный селект
                        if (isNdtProcess(processNameId)) {
                            const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                            if (ndtPlusContainer) {
                                // Проверяем, есть ли уже выбранные процессы
                                const checkedBoxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
                                if (checkedBoxes.length > 0) {
                                    // Убеждаемся, что контейнер виден
                                    ndtPlusContainer.style.display = 'block';
                                    ndtPlusContainer.style.visibility = 'visible';
                                    ndtPlusContainer.style.opacity = '1';

                                    // Инициализируем Select2, если еще не инициализирован
                                    const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                                    if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                        if (!$(ndtPlusSelect).hasClass('select2-hidden-accessible')) {
                                            setTimeout(function() {
                                                $(ndtPlusSelect).select2({
                                                    theme: 'bootstrap-5',
                                                    width: '100%',
                                                    multiple: true,
                                                    placeholder: 'Select Additional NDT Process(es)',
                                                    dropdownParent: $(ndtPlusContainer)
                                                }).on('select2:select select2:unselect', function (e) {
                                                    const selectElement = e.target;
                                                    const selectedValues = $(selectElement).val() || [];
                                                    if (selectedValues.length > 0) {
                                                        loadNdtPlusProcesses(selectElement);
                                                    } else {
                                                        const processRow = selectElement.closest('.process-row');
                                                        const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                        if (ndtPlusOptionsContainer) {
                                                            ndtPlusOptionsContainer.innerHTML = '';
                                                        }
                                                    }
                                                });

                                                // Исключаем выбранный основной NDT процесс из опций
                                                $(ndtPlusSelect).find('option').each(function() {
                                                    if ($(this).val() === processNameId) {
                                                        $(this).prop('disabled', true);
                                                    } else {
                                                        $(this).prop('disabled', false);
                                                    }
                                                });
                                            }, 100);
                                        }
                                    }
                                }
                            }
                        }
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

                    // Показываем уведомление об ошибке
                    if (window.NotificationHandler) {
                        window.NotificationHandler.error('Ошибка при загрузке процессов');
                    } else {
                        alert('Ошибка при загрузке процессов: ' + error.message);
                    }
                });
        }

        // Функция для загрузки процессов дополнительных NDT (для множественного выбора)
        function loadNdtPlusProcesses(selectElement) {
            // Получаем выбранные значения через Select2 API или напрямую
            let selectedNdtProcessNameIds = [];
            if (typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')) {
                // Если Select2 инициализирован, используем его API
                selectedNdtProcessNameIds = $(selectElement).val() || [];
            } else {
                // Если Select2 не инициализирован, используем обычный способ
                selectedNdtProcessNameIds = Array.from(selectElement.selectedOptions || selectElement.options).filter(opt => opt.selected).map(opt => opt.value);
            }
            const processRow = selectElement.closest('.process-row');
            const ndtPlusOptionsContainer = processRow.querySelector('.ndt-plus-process-options');
            const manualId = document.getElementById('processes-container').dataset.manualId;
            const rowIndex = selectElement.getAttribute('data-row-index') || '0';

            if (!selectedNdtProcessNameIds.length || !ndtPlusOptionsContainer) {
                ndtPlusOptionsContainer.innerHTML = '';
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Загружаем процессы для всех выбранных дополнительных NDT
            const loadPromises = selectedNdtProcessNameIds.map(processNameId => {
                return fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.error || err.message || 'Network response was not ok');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        return { processNameId, processes: data.existingProcesses || [] };
                    });
            });

            Promise.all(loadPromises)
                .then(results => {
                    ndtPlusOptionsContainer.innerHTML = '';

                    results.forEach(({ processNameId, processes }) => {
                        if (processes.length > 0) {
                            // Получаем название процесса для заголовка
                            let processName = `NDT-${processNameId}`;
                            if (typeof $ !== 'undefined' && $(selectElement).hasClass('select2-hidden-accessible')) {
                                const processNameOption = $(selectElement).find(`option[value="${processNameId}"]`);
                                if (processNameOption.length > 0) {
                                    processName = processNameOption.text();
                                }
                            } else {
                                const processNameOption = selectElement.querySelector(`option[value="${processNameId}"]`);
                                if (processNameOption) {
                                    processName = processNameOption.textContent;
                                }
                            }

                            // Добавляем заголовок для группы процессов
                            const header = document.createElement('div');
                            header.classList.add('mt-2', 'mb-1');
                            header.innerHTML = `<strong>${processName}:</strong>`;
                            ndtPlusOptionsContainer.appendChild(header);

                            // Добавляем чекбоксы для процессов
                            processes.forEach(process => {
                                const checkbox = document.createElement('div');
                                checkbox.classList.add('form-check');
                                checkbox.innerHTML = `
                                    <input type="checkbox" name="processes[${rowIndex}][ndt_plus_processes][]"
                                           value="${process.id}"
                                           class="form-check-input ndt-plus-process-checkbox"
                                           data-ndt-process-name-id="${processNameId}">
                                    <label class="form-check-label">${process.process}</label>
                                `;
                                ndtPlusOptionsContainer.appendChild(checkbox);
                            });
                        }
                    });

                    if (ndtPlusOptionsContainer.children.length === 0) {
                        ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">No processes available</div>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка при получении дополнительных NDT процессов:', error);
                    ndtPlusOptionsContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;
                });
        }

        // Обработчик изменения чекбоксов процессов - показываем/скрываем дополнительный селект NDT
        document.addEventListener('change', function(event) {
            if (event.target.matches('.process-options input[type="checkbox"]')) {
                const checkbox = event.target;
                const processRow = checkbox.closest('.process-row');

                if (!processRow) {
                    console.error('Process row not found');
                    return;
                }

                const processNameSelect = processRow.querySelector('.select2-process');
                const processNameId = processNameSelect ? processNameSelect.value : null;

                console.log('Checkbox changed, processNameId:', processNameId);
                console.log('Process row:', processRow);

                // Ищем контейнер разными способами
                let ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                if (!ndtPlusContainer) {
                    // Пробуем найти в col-md-5
                    const colMd5 = processRow.querySelector('.col-md-5');
                    if (colMd5) {
                        ndtPlusContainer = colMd5.querySelector('.ndt-plus-process-container');
                    }
                }
                if (!ndtPlusContainer) {
                    // Пробуем найти по ID
                    const rowIndex = processRow.querySelector('.select2-process')?.name?.match(/processes\[(\d+)\]/)?.[1] || '0';
                    ndtPlusContainer = document.getElementById(`ndt_plus_process_${rowIndex}`)?.closest('.ndt-plus-process-container');
                }

                console.log('NDT Plus container found:', ndtPlusContainer);

                if (processNameId && isNdtProcess(processNameId)) {
                    if (!ndtPlusContainer) {
                        console.error('NDT Plus container not found in DOM!');
                        console.log('Process row HTML:', processRow.innerHTML.substring(0, 500));
                        return;
                    }

                    // Проверяем, есть ли хотя бы один выбранный процесс
                    const checkedBoxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
                    console.log('Checked boxes count:', checkedBoxes.length);

                    if (checkedBoxes.length > 0) {
                        // Используем requestAnimationFrame для гарантии, что изменения применятся после всех других обработчиков
                        requestAnimationFrame(function() {
                        // Принудительно показываем контейнер
                        ndtPlusContainer.style.display = 'block';
                        ndtPlusContainer.style.visibility = 'visible';
                        ndtPlusContainer.style.opacity = '1';
                        ndtPlusContainer.style.height = 'auto';
                        ndtPlusContainer.style.overflow = 'visible';

                        // Убеждаемся, что все дочерние элементы видимы
                        const allChildren = ndtPlusContainer.querySelectorAll('*');
                        allChildren.forEach(child => {
                            if (child.style) {
                                if (child.style.display === 'none') {
                                    child.style.display = '';
                                }
                                if (child.style.visibility === 'hidden') {
                                    child.style.visibility = '';
                                }
                            }
                        });

                        console.log('NDT Plus container display:', window.getComputedStyle(ndtPlusContainer).display);
                        console.log('NDT Plus container visibility:', window.getComputedStyle(ndtPlusContainer).visibility);

                        // Устанавливаем z-index и position для контейнера, чтобы он не был перекрыт
                        ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                        ndtPlusContainer.style.setProperty('z-index', '1000', 'important');

                        // Инициализируем Select2 для дополнительного селекта, если еще не инициализирован
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            console.log('NDT Plus container shown, select found:', ndtPlusSelect);

                            // Исключаем уже выбранный NDT процесс из опций
                            if (processNameId) {
                                const optionToRemove = ndtPlusSelect.querySelector(`option[value="${processNameId}"]`);
                                if (optionToRemove) {
                                    optionToRemove.remove();
                                }
                            }

                            if (typeof $ !== 'undefined' && $.fn.select2) {
                                // Используем setTimeout для инициализации после показа контейнера
                                setTimeout(function() {
                                    // Проверяем, инициализирован ли уже Select2
                                    const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');

                                    if (!isSelect2Initialized) {
                                        console.log('Initializing Select2 for NDT Plus select');
                                        $(ndtPlusSelect).select2({
                                            theme: 'bootstrap-5',
                                            width: '100%',
                                            multiple: true,
                                            placeholder: 'Select Additional NDT Process(es)'
                                        }).on('select2:select select2:unselect', function (e) {
                                            // Обработчик изменения выбора дополнительных NDT процессов
                                            const selectElement = e.target;
                                            const selectedValues = $(selectElement).val() || [];
                                            if (selectedValues.length > 0) {
                                                loadNdtPlusProcesses(selectElement);
                                            } else {
                                                const processRow = selectElement.closest('.process-row');
                                                const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                if (ndtPlusOptionsContainer) {
                                                    ndtPlusOptionsContainer.innerHTML = '';
                                                }
                                            }
                                        });

                                        // Принудительно показываем контейнер и Select2 элементы после инициализации
                                        setTimeout(function() {
                                            // Проверяем и устанавливаем стили для контейнера
                                            const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                console.warn('Container hidden after Select2 init, forcing visibility');
                                                ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                                ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                                ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                                ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                                                ndtPlusContainer.style.setProperty('z-index', '1000', 'important');
                                            }

                                            // Проверяем и устанавливаем стили для Select2 контейнера
                                            const select2Container = $(ndtPlusSelect).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                const select2ComputedStyle = window.getComputedStyle(select2Container[0]);
                                                if (select2ComputedStyle.display === 'none' || select2ComputedStyle.visibility === 'hidden') {
                                                    console.warn('Select2 container hidden after init, forcing visibility');
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'position': 'relative !important',
                                                        'z-index': '1001 !important'
                                                    });
                                                }

                                                // Проверяем видимость через getBoundingClientRect
                                                const rect = select2Container[0].getBoundingClientRect();
                                                console.log('Select2 container bounding rect:', {
                                                    top: rect.top,
                                                    left: rect.left,
                                                    width: rect.width,
                                                    height: rect.height,
                                                    visible: rect.width > 0 && rect.height > 0
                                                });

                                                if (rect.width === 0 || rect.height === 0) {
                                                    console.error('Select2 container has zero dimensions!');
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'width': '100% !important',
                                                        'min-height': '38px !important'
                                                    });
                                                }
                                            } else {
                                                console.error('Select2 container not found after initialization!');
                                            }
                                        }, 100);
                                    } else {
                                        console.log('Select2 already initialized for NDT Plus select, updating visibility...');
                                        // Если Select2 уже инициализирован, обновляем видимость визуального контейнера
                                        // Сначала уничтожаем старый экземпляр
                                        $(ndtPlusSelect).select2('destroy');

                                        // Затем инициализируем заново
                                        $(ndtPlusSelect).select2({
                                            theme: 'bootstrap-5',
                                            width: '100%',
                                            multiple: true,
                                            placeholder: 'Select Additional NDT Process(es)'
                                        }).on('select2:select select2:unselect', function (e) {
                                            // Обработчик изменения выбора дополнительных NDT процессов
                                            const selectElement = e.target;
                                            const selectedValues = $(selectElement).val() || [];
                                            if (selectedValues.length > 0) {
                                                loadNdtPlusProcesses(selectElement);
                                            } else {
                                                const processRow = selectElement.closest('.process-row');
                                                const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                                                if (ndtPlusOptionsContainer) {
                                                    ndtPlusOptionsContainer.innerHTML = '';
                                                }
                                            }
                                        });

                                        // Принудительно показываем контейнер и Select2 элементы после переинициализации
                                        setTimeout(function() {
                                            // Проверяем и устанавливаем стили для контейнера
                                            const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                console.warn('Container hidden after Select2 re-init, forcing visibility');
                                                ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                                ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                                ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                                ndtPlusContainer.style.setProperty('position', 'relative', 'important');
                                                ndtPlusContainer.style.setProperty('z-index', '1000', 'important');
                                            }

                                            // Проверяем и устанавливаем стили для Select2 контейнера
                                            const select2Container = $(ndtPlusSelect).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                const select2ComputedStyle = window.getComputedStyle(select2Container[0]);
                                                if (select2ComputedStyle.display === 'none' || select2ComputedStyle.visibility === 'hidden') {
                                                    console.warn('Select2 container hidden after re-init, forcing visibility');
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'position': 'relative !important',
                                                        'z-index': '1001 !important'
                                                    });
                                                }

                                                // Проверяем видимость через getBoundingClientRect
                                                const rect = select2Container[0].getBoundingClientRect();
                                                console.log('Select2 container bounding rect after re-init:', {
                                                    top: rect.top,
                                                    left: rect.left,
                                                    width: rect.width,
                                                    height: rect.height,
                                                    visible: rect.width > 0 && rect.height > 0
                                                });

                                                if (rect.width === 0 || rect.height === 0) {
                                                    console.error('Select2 container has zero dimensions after re-init!');
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important',
                                                        'width': '100% !important',
                                                        'min-height': '38px !important'
                                                    });
                                                }
                                            } else {
                                                console.error('Select2 container not found after re-initialization!');
                                            }
                                        }, 100);
                                    }

                                    // Исключаем выбранный основной NDT процесс из опций
                                    $(ndtPlusSelect).find('option').each(function() {
                                        if ($(this).val() === processNameId) {
                                            $(this).prop('disabled', true);
                                        } else {
                                            $(this).prop('disabled', false);
                                        }
                                    });

                                    // Обновляем Select2 для применения изменений
                                    $(ndtPlusSelect).trigger('change');

                                    // Убеждаемся, что оригинальный select скрыт (это нормально для Select2)
                                    $(ndtPlusSelect).css('display', 'none');

                                    // Убеждаемся, что визуальный контейнер Select2 виден
                                    // Select2 создает контейнер после оригинального select
                                    setTimeout(function() {
                                        const select2Container = $(ndtPlusSelect).next('.select2-container');
                                        if (select2Container.length > 0) {
                                            // Принудительно показываем контейнер и все его элементы
                                            select2Container.css({
                                                'display': 'inline-block',
                                                'visibility': 'visible',
                                                'opacity': '1',
                                                'height': 'auto',
                                                'width': '100%',
                                                'min-height': '38px'
                                            });

                                            // Проверяем и показываем внутренние элементы
                                            const select2Selection = select2Container.find('.select2-selection');
                                            if (select2Selection.length > 0) {
                                                select2Selection.css({
                                                    'display': 'flex',
                                                    'visibility': 'visible',
                                                    'opacity': '1',
                                                    'min-height': '38px'
                                                });
                                                console.log('Select2 selection found and made visible');
                                            }

                                            const select2SelectionRendered = select2Container.find('.select2-selection__rendered');
                                            if (select2SelectionRendered.length > 0) {
                                                select2SelectionRendered.css({
                                                    'display': 'block',
                                                    'visibility': 'visible'
                                                });
                                            }

                                            console.log('Select2 container found and made visible:', select2Container);
                                            console.log('Select2 container computed display:', window.getComputedStyle(select2Container[0]).display);
                                            console.log('Select2 container computed visibility:', window.getComputedStyle(select2Container[0]).visibility);
                                            console.log('Select2 container computed height:', window.getComputedStyle(select2Container[0]).height);
                                            console.log('Select2 container computed width:', window.getComputedStyle(select2Container[0]).width);
                                            console.log('Select2 container position:', select2Container.position());

                                            // Убеждаемся, что контейнер находится в правильном месте в DOM
                                            const selectParent = $(ndtPlusSelect).parent();
                                            const containerParent = select2Container.parent();
                                            console.log('Select parent:', selectParent[0]);
                                            console.log('Container parent:', containerParent[0]);
                                            console.log('Are parents the same?', selectParent[0] === containerParent[0]);

                                            // Убеждаемся, что контейнер находится сразу после select
                                            const selectNextSibling = ndtPlusSelect.nextElementSibling;
                                            if (!selectNextSibling || !selectNextSibling.classList.contains('select2-container')) {
                                                console.log('Moving Select2 container to correct position after select');
                                                // Если контейнер уже существует, перемещаем его
                                                if (select2Container.length > 0) {
                                                    select2Container.detach();
                                                    $(ndtPlusSelect).after(select2Container);
                                                }
                                            } else {
                                                console.log('Select2 container is already in correct position');
                                            }

                                            // Убеждаемся, что контейнер имеет правильную ширину
                                            const containerWidth = ndtPlusContainer.offsetWidth || $(ndtPlusContainer).width();
                                            if (containerWidth > 0) {
                                                select2Container.css('width', containerWidth + 'px');
                                                console.log('Set Select2 container width to:', containerWidth + 'px');
                                            }

                                            // Дополнительная проверка через небольшую задержку
                                            setTimeout(function() {
                                                const computedStyle = window.getComputedStyle(select2Container[0]);
                                                if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                                    console.warn('Select2 container still hidden, forcing visibility again');
                                                    select2Container.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important'
                                                    });
                                                }

                                                // Проверяем, что контейнер действительно виден на странице
                                                const rect = select2Container[0].getBoundingClientRect();
                                                console.log('Select2 container bounding rect:', {
                                                    top: rect.top,
                                                    left: rect.left,
                                                    width: rect.width,
                                                    height: rect.height,
                                                    visible: rect.width > 0 && rect.height > 0
                                                });
                                            }, 100);
                                        } else {
                                            console.warn('Select2 container not found, trying to find by class');
                                            // Пробуем найти контейнер другим способом
                                            const allSelect2Containers = $('.select2-container');
                                            console.log('Total Select2 containers found:', allSelect2Containers.length);
                                            // Ищем контейнер, который связан с нашим селектом
                                            const select2Id = $(ndtPlusSelect).attr('data-select2-id');
                                            if (select2Id) {
                                                const containerById = $(`.select2-container[data-select2-id="${select2Id}"]`);
                                                if (containerById.length > 0) {
                                                    containerById.css({
                                                        'display': 'inline-block !important',
                                                        'visibility': 'visible !important',
                                                        'opacity': '1 !important'
                                                    });
                                                    console.log('Select2 container found by ID and made visible');
                                                }
                                            }
                                        }
                                    }, 200);
                                }, 200);
                            } else {
                                console.warn('jQuery or Select2 not available');
                            }
                        } else {
                            console.error('NDT Plus select not found in container');
                        }

                        });

                        // Защита от скрытия контейнера - используем MutationObserver для отслеживания изменений
                        const checkedBoxesCount = checkedBoxes.length;
                        if (checkedBoxesCount > 0 && ndtPlusContainer) {
                            // Функция для принудительного показа контейнера
                            const forceShowContainer = function() {
                                const currentCheckedBoxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
                                if (currentCheckedBoxes.length > 0) {
                                    const computedStyle = window.getComputedStyle(ndtPlusContainer);
                                    if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
                                        console.warn('Container was hidden, forcing visibility');
                                        ndtPlusContainer.style.setProperty('display', 'block', 'important');
                                        ndtPlusContainer.style.setProperty('visibility', 'visible', 'important');
                                        ndtPlusContainer.style.setProperty('opacity', '1', 'important');
                                        ndtPlusContainer.style.setProperty('height', 'auto', 'important');

                                        // Также проверяем Select2 контейнер
                                        const ndtPlusSelectAfter = ndtPlusContainer.querySelector('.select2-ndt-plus');
                                        if (ndtPlusSelectAfter && typeof $ !== 'undefined' && $.fn.select2) {
                                            const select2Container = $(ndtPlusSelectAfter).next('.select2-container');
                                            if (select2Container.length > 0) {
                                                select2Container.css({
                                                    'display': 'inline-block !important',
                                                    'visibility': 'visible !important',
                                                    'opacity': '1 !important'
                                                });
                                            }
                                        }
                                    }
                                }
                            };

                            // Создаем наблюдатель за изменениями стилей контейнера
                            const observer = new MutationObserver(function(mutations) {
                                mutations.forEach(function(mutation) {
                                    if (mutation.type === 'attributes' && (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                                        setTimeout(forceShowContainer, 10);
                                    }
                                });
                            });

                            // Начинаем наблюдение за изменениями атрибутов
                            observer.observe(ndtPlusContainer, {
                                attributes: true,
                                attributeFilter: ['style', 'class'],
                                attributeOldValue: true
                            });

                            // Также наблюдаем за родительскими элементами
                            const parentObserver = new MutationObserver(function(mutations) {
                                setTimeout(forceShowContainer, 10);
                            });

                            // Наблюдаем за родительским элементом
                            const parentElement = ndtPlusContainer.parentElement;
                            if (parentElement) {
                                parentObserver.observe(parentElement, {
                                    attributes: true,
                                    attributeFilter: ['style', 'class'],
                                    childList: true,
                                    subtree: true
                                });
                            }

                            // Сохраняем наблюдатели для последующей очистки
                            ndtPlusContainer._visibilityObserver = observer;
                            ndtPlusContainer._parentObserver = parentObserver;

                            // Также проверяем через задержку периодически
                            const checkInterval = setInterval(function() {
                                const currentCheckedBoxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
                                if (currentCheckedBoxes.length === 0) {
                                    clearInterval(checkInterval);
                                    if (ndtPlusContainer._visibilityObserver) {
                                        ndtPlusContainer._visibilityObserver.disconnect();
                                    }
                                    if (ndtPlusContainer._parentObserver) {
                                        ndtPlusContainer._parentObserver.disconnect();
                                    }
                                } else {
                                    forceShowContainer();
                                }
                            }, 200);

                            // Очищаем интервал через 30 секунд (защита от утечек памяти)
                            setTimeout(function() {
                                clearInterval(checkInterval);
                            }, 30000);
                        }
                    } else {
                        ndtPlusContainer.style.display = 'none';
                        // Очищаем выбранные дополнительные NDT процессы
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            if (typeof $ !== 'undefined' && $.fn.select2) {
                                $(ndtPlusSelect).val(null).trigger('change');
                            } else {
                                ndtPlusSelect.value = '';
                            }
                        }
                        const ndtPlusOptions = ndtPlusContainer.querySelector('.ndt-plus-process-options');
                        if (ndtPlusOptions) {
                            ndtPlusOptions.innerHTML = '';
                        }
                    }
                }
            }

            // Обработчик выбора дополнительных NDT процессов (множественный выбор)
            // Работает как для обычного select, так и для Select2
            if (event.target.matches('.select2-ndt-plus') || event.target.closest('.select2-ndt-plus')) {
                const selectElement = event.target.matches('.select2-ndt-plus')
                    ? event.target
                    : document.querySelector('.select2-ndt-plus');

                if (selectElement) {
                    const selectedValues = Array.from(selectElement.selectedOptions || selectElement.options).filter(opt => opt.selected).map(opt => opt.value);
                    if (selectedValues.length > 0) {
                        loadNdtPlusProcesses(selectElement);
                    } else {
                        const processRow = selectElement.closest('.process-row');
                        const ndtPlusOptionsContainer = processRow ? processRow.querySelector('.ndt-plus-process-options') : null;
                        if (ndtPlusOptionsContainer) {
                            ndtPlusOptionsContainer.innerHTML = '';
                        }
                    }
                }
            }
        });

        // Обновление чекбоксов при изменении выбранного имени процесса
        // Обработчик для обычного события change
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('select2-process')) {
                const processNameId = event.target.value;
                const processRow = event.target.closest('.process-row');
                const processOptionsContainer = processRow.querySelector('.process-options');
                const manualId = document.getElementById('processes-container').dataset.manualId; // Получаем manual_id
                const saveButton = document.querySelector('button[type="submit"]');

                // Обновление отображения id выбранного Process Name
                // Получаем индекс строки из name атрибута select
                const selectName = event.target.name;
                const match = selectName.match(/processes\[(\d+)\]/);
                if (match) {
                    const rowIndex = match[1];
                    const processIdElement = document.getElementById(`process-name-id-${rowIndex}`);
                    if (processIdElement) {
                        processIdElement.textContent = processNameId || '-';
                    }
                }

                // ============================================
                // ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ ID ДЛЯ УСЛОВНОЙ ЛОГИКИ
                // ============================================

                // // СПОСОБ 1: Использование id для сравнения в JavaScript
                // if (processNameId) {
                //     // Пример: если id равен определенному значению
                //     if (processNameId == '1') {
                //         console.log('Выбран Process Name с id = 1');
                //         // Здесь можно выполнить какие-то действия
                //     }
                //
                //     // Пример: если id в определенном диапазоне
                //     if (processNameId >= 1 && processNameId <= 10) {
                //         console.log('ID в диапазоне от 1 до 10');
                //     }
                //
                //     // Пример: проверка на несколько значений
                //     if ([1, 5, 10].includes(parseInt(processNameId))) {
                //         console.log('ID равен 1, 5 или 10');
                //     }
                // }

                // // СПОСОБ 2: Использование data-атрибутов для получения дополнительных данных
                // const selectedOption = event.target.options[event.target.selectedIndex];
                // if (selectedOption) {
                //     const isSpecial = selectedOption.getAttribute('data-is-special') === 'true';
                //     if (isSpecial) {
                //         console.log('Выбран специальный процесс');
                //         // Показать/скрыть дополнительные элементы
                //     }
                // }
                //
                // // СПОСОБ 3: Использование данных из data-process-data атрибута
                // const processDataStr = event.target.getAttribute('data-process-data');
                // if (processDataStr) {
                //     try {
                //         const processData = JSON.parse(processDataStr);
                //         const selectedProcess = processData[processNameId];
                //         if (selectedProcess) {
                //             // Теперь можно использовать данные процесса для условий
                //             // Например: if (selectedProcess.type === 'special') { ... }
                //             console.log('Данные процесса:', selectedProcess);
                //         }
                //     } catch (e) {
                //         console.error('Ошибка парсинга данных процесса:', e);
                //     }
                // }

                // СПОСОБ 4: Условное отображение элементов на основе id

                const ecCheckbox = processRow.querySelector('input[name*="[ec]"]');
                if (ecCheckbox) {
                    // Показываем чекбокс EC только для процесса Machining (определяется динамически)
                    if (isMachiningProcess(processNameId)) {
                        ecCheckbox.closest('.form-check').style.display = 'block';
                    } else {
                        ecCheckbox.closest('.form-check').style.display = 'none';
                    }
                }

                // Используем функцию для загрузки процессов
                loadProcessesForRow(event.target);

                // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                if (isNdtProcess(processNameId)) {
                    const processRow = event.target.closest('.process-row');
                    const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                    const ndtPlusSelect = ndtPlusContainer ? ndtPlusContainer.querySelector('.select2-ndt-plus') : null;

                    // НЕ скрываем контейнер, если уже есть выбранные процессы
                    const checkedBoxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
                    if (checkedBoxes.length > 0 && ndtPlusContainer) {
                        // Убеждаемся, что контейнер виден
                        ndtPlusContainer.style.display = 'block';
                        ndtPlusContainer.style.visibility = 'visible';
                    }

                    if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                        // Исключаем выбранный NDT процесс из опций
                        $(ndtPlusSelect).find('option').each(function() {
                            if ($(this).val() === processNameId) {
                                $(this).prop('disabled', true);
                                // Удаляем из выбранных, если был выбран
                                if ($(ndtPlusSelect).val() && Array.isArray($(ndtPlusSelect).val()) && $(ndtPlusSelect).val().includes($(this).val())) {
                                    const newValues = $(ndtPlusSelect).val().filter(v => v !== $(this).val());
                                    $(ndtPlusSelect).val(newValues).trigger('change');
                                }
                            } else {
                                $(this).prop('disabled', false);
                            }
                        });
                        $(ndtPlusSelect).trigger('change');
                    }
                } else {
                    // Если не NDT, скрываем дополнительный селект
                    const processRow = event.target.closest('.process-row');
                    const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                    if (ndtPlusContainer) {
                        ndtPlusContainer.style.display = 'none';
                        // Очищаем выбранные значения
                        const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                            $(ndtPlusSelect).val(null).trigger('change');
                        }
                    }
                }
            }
        });

        // Глобальная переменная для хранения ссылки на текущую строку (например, process-row)
        let currentRow = null;

        // При клике на кнопку, открывающую модальное окно, сохраняем текущую строку и устанавливаем данные модального окна
        document.querySelectorAll('.btn[data-bs-target="#addProcessModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                // Предполагается, что кнопка находится в блоке с классом .process-row
                currentRow = this.closest('.process-row');
                const select = currentRow.querySelector('.select2-process');
                const processNameId = select.value;
                const processNameText = select.options[select.selectedIndex].text;
                // Устанавливаем заголовок модального окна и значение скрытого поля
                document.getElementById('modalProcessName').innerText = processNameText;
                document.getElementById('modalProcessNameId').value = processNameId;

                // Очищаем поле ввода нового процесса
                document.getElementById('newProcessInput').value = '';

                // Получаем manual_id (в данном примере из скрытого поля формы)
                // const manualId = document.querySelector('input[name="manual_id"]').value;
                const manualId = document.getElementById('processes-container').dataset.manualId;

                // Загружаем только availableProcesses для выбранного process_name_id и manualId
                // Existing Processes не отображаются в модальном окне
                const availableContainer = document.getElementById('availableProcessContainer');
                availableContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

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
                        // Отображаем только Available Processes
                        availableContainer.innerHTML = '';
                        if (data.availableProcesses && data.availableProcesses.length > 0) {
                            data.availableProcesses.forEach(process => {
                                const div = document.createElement('div');
                                div.className = 'form-check';
                                div.innerHTML = `
                                    <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
                                    <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                                `;
                                availableContainer.appendChild(div);
                            });
                        } else {
                            availableContainer.innerHTML = '<div class="text-muted">No available processes</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading processes:', error);
                        availableContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;

                        if (window.NotificationHandler) {
                            window.NotificationHandler.error('Ошибка при загрузке процессов');
                        }
                    });
            });
        });

        // Используем делегирование событий на контейнере, где находятся все process-row
        document.getElementById('processes-container').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn[data-bs-target="#addProcessModal"]');
            if (!btn) return;

            currentRow = btn.closest('.process-row');
            const select = currentRow.querySelector('.select2-process');
            const processNameId = select.value;
            const processNameText = select.options[select.selectedIndex].text;
            document.getElementById('modalProcessName').innerText = processNameText;
            document.getElementById('modalProcessNameId').value = processNameId;

            // Очищаем поле ввода нового процесса
            document.getElementById('newProcessInput').value = '';

            // Получаем manual_id через data-атрибут
            const manualId = document.getElementById('processes-container').dataset.manualId;

            // Загружаем только availableProcesses
            // Existing Processes не отображаются в модальном окне
            const availableContainer = document.getElementById('availableProcessContainer');
            availableContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

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
                    // Отображаем только Available Processes
                    availableContainer.innerHTML = '';
                    if (data.availableProcesses && data.availableProcesses.length > 0) {
                        data.availableProcesses.forEach(process => {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `
                                <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
                                <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                            `;
                            availableContainer.appendChild(div);
                        });
                    } else {
                        availableContainer.innerHTML = '<div class="text-muted">No available processes</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading processes:', error);
                    availableContainer.innerHTML = `<div class="text-danger">Error loading processes: ${error.message}</div>`;

                    if (window.NotificationHandler) {
                        window.NotificationHandler.error('Ошибка при загрузке процессов');
                    }
                });
        });


        // Обработка нажатия кнопки "Save Process" в модальном окне
        document.getElementById('saveProcessModal').addEventListener('click', function() {
            const processNameId = document.getElementById('modalProcessNameId').value;
            const newProcess = document.getElementById('newProcessInput').value.trim();
            const selectedCheckboxes = document.querySelectorAll('#availableProcessContainer input[type="checkbox"]:checked');

            // Если ни новое название не введено, ни выбран хотя бы один существующий процесс – предупреждаем пользователя
            if (newProcess === '' && selectedCheckboxes.length === 0) {
                alert("Введите новый процесс или выберите существующий.");
                return;
            }

            // Получаем manual_id через data-атрибут
            const manualId = document.getElementById('processes-container').dataset.manualId;

            if (currentRow) {
                const processOptionsContainer = currentRow.querySelector('.process-options');
                const saveButton = document.querySelector('button[type="submit"]');

                // Получаем индекс строки для правильного именования чекбоксов
                const select = currentRow.querySelector('.select2-process');
                const selectName = select ? select.name : '';
                const match = selectName.match(/processes\[(\d+)\]/);
                const rowIndex = match ? match[1] : '0';

                // Если введён новый процесс – отправляем AJAX-запрос для его создания
                if (newProcess !== '') {
                    // Показываем индикатор загрузки
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'text-muted';
                    loadingDiv.textContent = 'Saving process...';
                    processOptionsContainer.appendChild(loadingDiv);

                    fetch("{{ route('processes.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            process_names_id: processNameId,
                            process: newProcess,
                            manual_id: manualId
                        })
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || err.message || 'Network response was not ok');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response:', data);
                            // Удаляем индикатор загрузки
                            if (loadingDiv.parentNode) {
                                loadingDiv.remove();
                            }

                            if (data.success) {
                                // Добавляем созданный процесс в виде чекбокса в контейнер текущей строки
                                const div = document.createElement('div');
                                div.classList.add('form-check');
                                div.innerHTML = `
                            <input type="checkbox" class="form-check-input" name="processes[${rowIndex}][process][]" value="${data.process.id}" checked>
                            <label class="form-check-label">${data.process.process}</label>
                        `;
                                processOptionsContainer.appendChild(div);
                                saveButton.disabled = false; // Активируем кнопку Save

                                // Очищаем сообщение "No specification"
                                const noSpecLabel = processOptionsContainer.querySelector('.text-muted');
                                if (noSpecLabel) {
                                    noSpecLabel.remove();
                                }

                                                // Очищаем поле ввода нового процесса
                                document.getElementById('newProcessInput').value = '';

                                // Очищаем выбранные чекбоксы в модальном окне (только доступные, не existing)
                                document.querySelectorAll('#availableProcessContainer input[type="checkbox"]:checked').forEach(checkbox => {
                                    checkbox.checked = false;
                                });

                                // Показываем сообщение об успехе
                                if (window.NotificationHandler) {
                                    window.NotificationHandler.success('Процесс успешно добавлен!');
                                } else {
                                    alert('Процесс успешно добавлен!');
                                }

                                // Перезагружаем список процессов в модальном окне (только availableProcesses)
                                const manualId = document.getElementById('processes-container').dataset.manualId;
                                const availableContainer = document.getElementById('availableProcessContainer');
                                availableContainer.innerHTML = '<div class="text-muted">Reloading processes...</div>';

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
                                        // Отображаем только Available Processes
                                        availableContainer.innerHTML = '';
                                        if (data.availableProcesses && data.availableProcesses.length > 0) {
                                            data.availableProcesses.forEach(process => {
                                                const div = document.createElement('div');
                                                div.className = 'form-check';
                                                div.innerHTML = `
                                                    <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
                                                    <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                                                `;
                                                availableContainer.appendChild(div);
                                            });
                                        } else {
                                            availableContainer.innerHTML = '<div class="text-muted">No available processes</div>';
                                        }

                                        // Закрываем модальное окно после успешного сохранения
                                        const modalEl = document.getElementById('addProcessModal');
                                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                        if (modalInstance) {
                                            modalInstance.hide();
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error reloading processes:', error);
                                        container.innerHTML = `<div class="text-danger">Error reloading processes: ${error.message}</div>`;

                                        if (window.NotificationHandler) {
                                            window.NotificationHandler.error('Ошибка при перезагрузке списка процессов');
                                        }

                                        // Закрываем модальное окно даже при ошибке перезагрузки списка
                                        const modalEl = document.getElementById('addProcessModal');
                                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                        if (modalInstance) {
                                            modalInstance.hide();
                                        }
                                    });
                            } else {
                                const errorMsg = data.message || "Ошибка при добавлении нового процесса.";
                                if (window.NotificationHandler) {
                                    window.NotificationHandler.error(errorMsg);
                                } else {
                                    alert(errorMsg);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            // Удаляем индикатор загрузки при ошибке
                            if (loadingDiv.parentNode) {
                                loadingDiv.remove();
                            }

                            const errorMsg = "Ошибка при добавлении нового процесса: " + (error.message || 'Unknown error');
                            if (window.NotificationHandler) {
                                window.NotificationHandler.error(errorMsg);
                            } else {
                                alert(errorMsg);
                            }
                        });
                }

                // Если выбраны существующие процессы – сохраняем их в manual_processes и добавляем в контейнер
                if (selectedCheckboxes.length > 0) {
                    // Создаем массив промисов для всех запросов
                    const savePromises = [];

                    // Показываем индикатор загрузки
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'text-muted';
                    loadingDiv.textContent = 'Saving processes...';
                    processOptionsContainer.appendChild(loadingDiv);

                    selectedCheckboxes.forEach(checkbox => {
                        const processId = checkbox.value;
                        const processLabel = checkbox.nextElementSibling.innerText;

                        // Отправляем AJAX-запрос для сохранения связи в manual_processes
                        const savePromise = fetch("{{ route('processes.store') }}", {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                process_names_id: processNameId,
                                selected_process_id: processId,
                                manual_id: manualId
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw new Error(err.error || err.message || 'Network response was not ok');
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Удаляем индикатор загрузки после первого успешного запроса
                                if (loadingDiv.parentNode) {
                                    loadingDiv.remove();
                                }

                                // Добавляем процесс в виде чекбокса в контейнер текущей строки
                                const div = document.createElement('div');
                                div.classList.add('form-check');
                                div.innerHTML = `
                                    <input type="checkbox" class="form-check-input" name="processes[${rowIndex}][process][]" value="${processId}" checked>
                                    <label class="form-check-label">${processLabel}</label>
                                `;
                                processOptionsContainer.appendChild(div);
                                return data;
                            } else {
                                throw new Error(data.message || 'Error saving process');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка при сохранении процесса:', error);
                            const errorMsg = "Ошибка при сохранении процесса " + processLabel + ": " + (error.message || 'Unknown error');
                            if (window.NotificationHandler) {
                                window.NotificationHandler.error(errorMsg);
                            } else {
                                alert(errorMsg);
                            }
                            return null;
                        });

                        savePromises.push(savePromise);
                    });

                    // Ждем завершения всех запросов
                    Promise.all(savePromises).then(results => {
                        const successCount = results.filter(r => r !== null).length;
                        if (successCount > 0) {
                            saveButton.disabled = false; // Активируем кнопку Save

                            // Очищаем сообщение "No specification"
                            const noSpecLabel = processOptionsContainer.querySelector('.text-muted');
                            if (noSpecLabel) {
                                noSpecLabel.remove();
                            }

                            // Очищаем выбранные чекбоксы в модальном окне (только доступные, не existing)
                            selectedCheckboxes.forEach(checkbox => {
                                if (!checkbox.disabled) {
                                    checkbox.checked = false;
                                }
                            });

                            // Перезагружаем список процессов в модальном окне (только availableProcesses)
                            const availableContainer = document.getElementById('availableProcessContainer');
                            availableContainer.innerHTML = '<div class="text-muted">Reloading processes...</div>';

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
                                    // Отображаем только Available Processes
                                    availableContainer.innerHTML = '';
                                    if (data.availableProcesses && data.availableProcesses.length > 0) {
                                        data.availableProcesses.forEach(process => {
                                            const div = document.createElement('div');
                                            div.className = 'form-check';
                                            div.innerHTML = `
                                                <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
                                                <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                                            `;
                                            availableContainer.appendChild(div);
                                        });
                                    } else {
                                        availableContainer.innerHTML = '<div class="text-muted">No available processes</div>';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error reloading processes:', error);
                                    availableContainer.innerHTML = `<div class="text-danger">Error reloading processes: ${error.message}</div>`;

                                    if (window.NotificationHandler) {
                                        window.NotificationHandler.error('Ошибка при перезагрузке списка процессов');
                                    }
                                });

                            // Удаляем индикатор загрузки
                            if (loadingDiv.parentNode) {
                                loadingDiv.remove();
                            }

                            // Показываем сообщение об успехе
                            if (successCount === selectedCheckboxes.length) {
                                if (window.NotificationHandler) {
                                    window.NotificationHandler.success('Все процессы успешно добавлены!');
                                } else {
                                    alert('Все процессы успешно добавлены!');
                                }
                            } else {
                                const msg = `Добавлено ${successCount} из ${selectedCheckboxes.length} процессов.`;
                                if (window.NotificationHandler) {
                                    window.NotificationHandler.warning(msg);
                                } else {
                                    alert(msg);
                                }
                            }
                        }

                        // Закрываем модальное окно после завершения всех запросов
                        const modalEl = document.getElementById('addProcessModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }).catch(error => {
                        console.error('Ошибка при сохранении процессов:', error);
                        // Удаляем индикатор загрузки при ошибке
                        if (loadingDiv.parentNode) {
                            loadingDiv.remove();
                        }

                        const errorMsg = 'Ошибка при сохранении процессов: ' + (error.message || 'Unknown error');
                        if (window.NotificationHandler) {
                            window.NotificationHandler.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }

                        // Закрываем модальное окно даже при ошибке
                        const modalEl = document.getElementById('addProcessModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    });
                } else {
                    // Если не было выбрано существующих процессов и не был введен новый процесс,
                    // закрываем модальное окно (это уже обработано выше для нового процесса)
                    if (newProcess === '') {
                        const modalEl = document.getElementById('addProcessModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                }
            } else {
                // Если currentRow не определен, просто закрываем модальное окно
                const modalEl = document.getElementById('addProcessModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        // Инициализация Select2 и отображения id и чекбокса EC для всех существующих строк при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализируем Select2 для всех существующих select элементов
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.select2-process').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                }).on('select2:select', function (e) {
                    // Обработчик события Select2 для загрузки процессов
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

                    loadProcessesForRow(selectElement);

                    // Если это NDT процесс, обновляем опции дополнительного селекта (исключаем выбранный)
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    }
                });

                // НЕ инициализируем Select2 для скрытых дополнительных селектов NDT при загрузке страницы
                // Они будут инициализированы динамически при показе контейнера
                // Это предотвращает проблемы с видимостью визуального элемента Select2
            }

            const processRows = document.querySelectorAll('.process-row');
            processRows.forEach(row => {
                const select = row.querySelector('.select2-process');
                if (select) {
                    const processNameId = select.value;
                    const ecCheckbox = row.querySelector('input[name*="[ec]"]');

                    // Показываем/скрываем чекбокс EC в зависимости от выбранного id
                    if (ecCheckbox) {
                        // Показываем чекбокс EC только для процесса Machining (определяется динамически)
                        if (isMachiningProcess(processNameId)) {
                            ecCheckbox.closest('.form-check').style.display = 'block';
                        } else {
                            ecCheckbox.closest('.form-check').style.display = 'none';
                        }
                    }

                    // Если это NDT процесс, исключаем его из дополнительного селекта
                    if (isNdtProcess(processNameId)) {
                        const ndtPlusSelect = row.querySelector('.select2-ndt-plus');
                        if (ndtPlusSelect) {
                            // Удаляем опцию с выбранным NDT процессом
                            const optionToRemove = ndtPlusSelect.querySelector(`option[value="${processNameId}"]`);
                            if (optionToRemove) {
                                optionToRemove.remove();
                            }
                            // Если Select2 уже инициализирован, обновляем его
                            if (typeof $ !== 'undefined' && $.fn.select2 && $(ndtPlusSelect).hasClass('select2-hidden-accessible')) {
                                $(ndtPlusSelect).trigger('change');
                            }
                        }
                    }

                    const selectName = select.name;
                    const match = selectName.match(/processes\[(\d+)\]/);
                    if (match) {
                        const rowIndex = match[1];
                        const processIdElement = document.getElementById(`process-name-id-${rowIndex}`);
                        if (processIdElement) {
                            processIdElement.textContent = processNameId || '-';
                        }
                    }
                }
            });
        });


    </script>
@endsection
