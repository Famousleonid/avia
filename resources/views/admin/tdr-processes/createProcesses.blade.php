
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
            return machiningProcessNameId !== null && processNameId == machiningProcessNameId.toString();
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
                    loadProcessesForRow(selectElement);
                });
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
                    processesData.push({
                        process_names_id: processNameId,
                        processes: selectedProcessIds, // Сохраняем массив ID процессов
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
                    loadProcessesForRow(selectElement);
                });
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
