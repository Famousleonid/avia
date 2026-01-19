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
                @php
                    // Определяем переменные для использования в форме
                    $currentPlusProcess = $current_tdr_processes->plus_process ?? '';
                    $currentProcessName = $current_tdr_processes->processName;
                    $isNdtProcess = $currentProcessName && strpos($currentProcessName->name, 'NDT-') === 0;
                    $currentPlusProcessIds = !empty($currentPlusProcess) ? explode(',', $currentPlusProcess) : [];
                @endphp
                <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">
                    <input type="hidden" name="processes[0][plus_process]" id="plus_process_hidden" value="{{ $currentPlusProcess }}">

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

                                    {{-- Дополнительный селект для NDT процессов (скрыт по умолчанию, показывается через JavaScript) --}}
                                    <div class="ndt-plus-process-container mt-3" style="display: {{ $isNdtProcess ? 'block' : 'none' }};">
                                        <label for="ndt_plus_process_0">Additional NDT Process(es):</label>
                                        <select name="processes[0][ndt_plus_process][]"
                                                class="form-control select2-ndt-plus"
                                                id="ndt_plus_process_0"
                                                data-row-index="0"
                                                multiple
                                                style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                                            @foreach ($ndtProcessNames as $ndtProcessName)
                                                <option value="{{ $ndtProcessName->id }}"
                                                        data-process-name="{{ $ndtProcessName->name }}"
                                                        {{ in_array((string)$ndtProcessName->id, $currentPlusProcessIds) ? 'selected' : '' }}>
                                                    {{ $ndtProcessName->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ndt-plus-process-options mt-2">
                                            {{-- Здесь будут чекбоксы для дополнительных NDT процессов --}}
                                        </div>
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
                        <button type="submit" class="btn btn-outline-primary mt-3" id="updateButton">{{ __('Update') }}</button>
                        <a href="{{ route('tdr-processes.processes', ['tdrId' => $current_tdr->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Данные ProcessNames для использования в JavaScript
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

                    // Показываем/скрываем селект для дополнительных NDT процессов
                    const ndtPlusContainer = processRow.querySelector('.ndt-plus-process-container');
                    if (ndtPlusContainer) {
                        if (isNdtProcess(processNameId)) {
                            // Показываем контейнер
                            ndtPlusContainer.style.display = 'block';

                            // Инициализируем Select2, если еще не инициализирован
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');

                                if (!isSelect2Initialized) {
                                    // Инициализируем Select2
                                    $(ndtPlusSelect).select2({
                                        theme: 'bootstrap-5',
                                        width: '100%',
                                        multiple: true,
                                        placeholder: 'Select Additional NDT Process(es)'
                                    }).on('select2:select select2:unselect', function (e) {
                                        updatePlusProcessAndProcesses();
                                        loadNdtPlusProcesses(this);
                                    });
                                }

                                // Восстанавливаем все опции NDT процессов
                                const allNdtOptions = @json($ndtProcessNames->pluck('id')->toArray());
                                const currentOptions = Array.from(ndtPlusSelect.options).map(opt => opt.value);

                                // Добавляем опции, которых нет
                                allNdtOptions.forEach(ndtId => {
                                    if (ndtId != processNameId && !currentOptions.includes(ndtId.toString())) {
                                        const ndtProcessName = ndtProcessNamesData[ndtId];
                                        if (ndtProcessName) {
                                            const option = new Option(ndtProcessName.name, ndtId, false, false);
                                            option.setAttribute('data-process-name', ndtProcessName.name);
                                            ndtPlusSelect.add(option);
                                        }
                                    }
                                });

                                // Исключаем выбранный NDT процесс из опций
                                $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();

                                // Обновляем Select2
                                $(ndtPlusSelect).trigger('change');
                            }
                        } else {
                            // Скрываем контейнер
                            ndtPlusContainer.style.display = 'none';

                            // Очищаем выбранные дополнительные NDT процессы
                            const ndtPlusSelect = ndtPlusContainer.querySelector('.select2-ndt-plus');
                            if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                                $(ndtPlusSelect).val(null).trigger('change');
                            }

                            // Очищаем скрытое поле plus_process
                            document.getElementById('plus_process_hidden').value = '';

                            // Очищаем контейнер с чекбоксами дополнительных NDT процессов
                            const ndtPlusOptionsContainer = processRow.querySelector('.ndt-plus-process-options');
                            if (ndtPlusOptionsContainer) {
                                ndtPlusOptionsContainer.innerHTML = '';
                            }
                        }
                    }

                    // Загружаем процессы для выбранного Process Name
                    loadProcessesForRow(selectElement);
                });
            }

            // Инициализация Select2 для дополнительного селекта NDT (только если контейнер виден)
            const ndtPlusContainer = document.querySelector('.ndt-plus-process-container');
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');
            if (ndtPlusSelect && ndtPlusContainer && ndtPlusContainer.style.display !== 'none' && typeof $ !== 'undefined' && $.fn.select2) {
                const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');
                if (!isSelect2Initialized) {
                    $(ndtPlusSelect).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        multiple: true,
                        placeholder: 'Select Additional NDT Process(es)'
                    }).on('select2:select select2:unselect', function (e) {
                        updatePlusProcessAndProcesses();
                        loadNdtPlusProcesses(this);
                    });
                }
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

                // Показываем/скрываем селект для дополнительных NDT процессов при загрузке
                if (ndtPlusContainer) {
                    if (isNdtProcess(processNameId)) {
                        ndtPlusContainer.style.display = 'block';
                        // Инициализируем Select2, если еще не инициализирован
                        if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                            const isSelect2Initialized = $(ndtPlusSelect).hasClass('select2-hidden-accessible');
                            if (!isSelect2Initialized) {
                                $(ndtPlusSelect).select2({
                                    theme: 'bootstrap-5',
                                    width: '100%',
                                    multiple: true,
                                    placeholder: 'Select Additional NDT Process(es)'
                                }).on('select2:select select2:unselect', function (e) {
                                    updatePlusProcessAndProcesses();
                                    loadNdtPlusProcesses(this);
                                });
                            }
                        }
                        // Исключаем выбранный NDT процесс из опций
                        if (ndtPlusSelect && typeof $ !== 'undefined' && $.fn.select2) {
                            $(ndtPlusSelect).find(`option[value="${processNameId}"]`).remove();
                            $(ndtPlusSelect).trigger('change');
                        }
                    } else {
                        ndtPlusContainer.style.display = 'none';
                    }
                }

                // Загружаем процессы для текущего выбранного Process Name
                if (processNameSelect.value) {
                    loadProcessesForRow(processNameSelect);
                }
            }

            // Обработчик изменения чекбоксов процессов
            document.addEventListener('change', function(event) {
                if (event.target.matches('.process-options input[type="checkbox"]')) {
                    updatePlusProcessAndProcesses();
                }
            });
        });

        // Функция для обновления plus_process и синхронизации с processes (JSON)
        function updatePlusProcessAndProcesses() {
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');
            const plusProcessHidden = document.getElementById('plus_process_hidden');
            const processRow = document.querySelector('.process-row');

            if (!ndtPlusSelect || !plusProcessHidden) {
                return;
            }

            // Получаем выбранные дополнительные NDT process_names_id
            const selectedNdtPlusIds = $(ndtPlusSelect).val() || [];
            const selectedNdtPlusIdsInt = selectedNdtPlusIds.map(id => parseInt(id));

            // Обновляем скрытое поле plus_process
            if (selectedNdtPlusIdsInt.length > 0) {
                plusProcessHidden.value = selectedNdtPlusIdsInt.sort((a, b) => a - b).join(',');
            } else {
                plusProcessHidden.value = '';
            }

            // Получаем текущие выбранные процессы из основного Process Name
            const mainProcessCheckboxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
            const mainProcessIds = Array.from(mainProcessCheckboxes).map(cb => parseInt(cb.value));

            // Получаем процессы для дополнительных NDT
            const ndtPlusProcessCheckboxes = processRow.querySelectorAll('.ndt-plus-process-options input[type="checkbox"]:checked');
            const ndtPlusProcessIds = Array.from(ndtPlusProcessCheckboxes).map(cb => parseInt(cb.value));

            // Объединяем все процессы
            const allProcessIds = [...mainProcessIds, ...ndtPlusProcessIds];

            // Обновляем скрытое поле для processes (будет использовано при отправке формы)
            // На самом деле, процессы собираются из чекбоксов при отправке формы,
            // но мы можем обновить их здесь для синхронизации
            console.log('Updated plus_process:', plusProcessHidden.value);
            console.log('All process IDs:', allProcessIds);
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
                updatePlusProcessAndProcesses();
                return;
            }

            ndtPlusOptionsContainer.innerHTML = '<div class="text-muted">Loading processes...</div>';

            // Загружаем процессы для каждого выбранного дополнительного NDT
            const loadPromises = selectedValues.map(ndtProcessNameId => {
                return fetch(`{{ route('processes.getProcesses') }}?processNameId=${ndtProcessNameId}&manualId=${manualId}`)
                    .then(response => response.json())
                    .then(data => {
                        return { ndtProcessNameId, processes: data.existingProcesses || [] };
                    });
            });

            Promise.all(loadPromises).then(results => {
                ndtPlusOptionsContainer.innerHTML = '';

                results.forEach(({ ndtProcessNameId, processes }) => {
                    if (processes.length > 0) {
                        const ndtProcessName = ndtProcessNamesData[ndtProcessNameId];
                        const processNameLabel = ndtProcessName ? ndtProcessName.name : `NDT-${ndtProcessNameId}`;

                        const label = document.createElement('div');
                        label.className = 'fw-bold mt-2';
                        label.textContent = processNameLabel + ':';
                        ndtPlusOptionsContainer.appendChild(label);

                        processes.forEach(process => {
                            const checkbox = document.createElement('div');
                            checkbox.classList.add('form-check');
                            checkbox.innerHTML = `
                                <input type="checkbox"
                                       name="processes[0][ndt_plus_process][]"
                                       value="${process.id}"
                                       class="form-check-input ndt-plus-process-checkbox"
                                       data-ndt-process-name-id="${ndtProcessNameId}">
                                <label class="form-check-label">${process.process}</label>
                            `;
                            ndtPlusOptionsContainer.appendChild(checkbox);
                        });
                    }
                });

                updatePlusProcessAndProcesses();
            }).catch(error => {
                console.error('Ошибка при загрузке процессов для дополнительных NDT:', error);
                ndtPlusOptionsContainer.innerHTML = '<div class="text-danger">Error loading processes</div>';
            });
        }

        // Инициализация загрузки процессов для дополнительных NDT при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const ndtPlusSelect = document.querySelector('.select2-ndt-plus');
            const ndtPlusContainer = document.querySelector('.ndt-plus-process-container');
            // Загружаем процессы только если контейнер виден и есть выбранные дополнительные NDT
            if (ndtPlusSelect && ndtPlusContainer && ndtPlusContainer.style.display !== 'none' && typeof $ !== 'undefined' && $.fn.select2) {
                const selectedValues = $(ndtPlusSelect).val();
                if (selectedValues && selectedValues.length > 0) {
                    loadNdtPlusProcesses(ndtPlusSelect);
                }
            }
        });

        // Обработчик отправки формы - собираем все данные
        document.getElementById('editCPForm').addEventListener('submit', function(event) {
            const processRow = document.querySelector('.process-row');

            // Собираем процессы из основного Process Name
            const mainProcessCheckboxes = processRow.querySelectorAll('.process-options input[type="checkbox"]:checked');
            const mainProcessIds = Array.from(mainProcessCheckboxes).map(cb => parseInt(cb.value));

            // Собираем процессы из дополнительных NDT
            const ndtPlusProcessCheckboxes = processRow.querySelectorAll('.ndt-plus-process-options input[type="checkbox"]:checked');
            const ndtPlusProcessIds = Array.from(ndtPlusProcessCheckboxes).map(cb => parseInt(cb.value));

            // Добавляем скрытые поля для процессов дополнительных NDT
            ndtPlusProcessIds.forEach(processId => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'processes[0][process][]';
                hiddenInput.value = processId;
                processRow.querySelector('.process-options').appendChild(hiddenInput);
            });

            // Обновляем скрытое поле plus_process
            const plusProcessHidden = document.getElementById('plus_process_hidden');
            const ndtPlusSelect = processRow.querySelector('.select2-ndt-plus');
            if (ndtPlusSelect && plusProcessHidden) {
                const selectedNdtPlusIds = $(ndtPlusSelect).val() || [];
                if (selectedNdtPlusIds.length > 0) {
                    plusProcessHidden.value = selectedNdtPlusIds.map(id => parseInt(id)).sort((a, b) => a - b).join(',');
                } else {
                    plusProcessHidden.value = '';
                }
            }
        });
    </script>
@endsection

