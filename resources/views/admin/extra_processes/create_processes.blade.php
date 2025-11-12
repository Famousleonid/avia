@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 850px;
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
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{__('Add Extra Processes to Component')}}</h4>
                    <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>Component:</strong> {{ $component->name }}<br>
                        <strong>IPL:</strong> {{ $component->ipl_num }}<br>
                        <strong>Part Number:</strong> {{ $component->part_number }}
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" type="button" style="width: 120px" id="add-process">
                            Add Process
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($existingExtraProcess && $existingExtraProcess->processes)
                    <div class="alert alert-secondary">
                        <strong class="m-1">Existing Processes:</strong><br>
                        @if(is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1))
                            {{-- Старая структура: ассоциативный массив --}}
                            @foreach($existingExtraProcess->processes as $processNameId => $processId)
                                @php
                                    $processName = \App\Models\ProcessName::find($processNameId);
                                    $process = \App\Models\Process::find($processId);
                                @endphp
                                @if($processName && $process)
                                    <span class="badge bg-secondary ms-5  @if(strlen($process->process) > 40) process-text-long
                                    @endif">{{ $processName->name }}: {{ $process->process }}</span>
                                @endif
                            @endforeach
                        @else
                            {{-- Новая структура: массив объектов --}}
                            @foreach($existingExtraProcess->processes as $processItem)
                                @php
                                    $processName = \App\Models\ProcessName::find($processItem['process_name_id']);
                                    $process = \App\Models\Process::find($processItem['process_id']);
                                @endphp
                                @if($processName && $process)
                                    <span class="badge bg-secondary ms-5 @if(strlen($process->process) > 40) process-text-long
                                    @endif">{{ $processName->name }}: {{ $process->process }}</span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @endif

                <form id="createProcessesForm" role="form" method="POST" action="{{route('extra_processes.store_processes')}}" class="createProcessesForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">
                    <input type="hidden" name="component_id" value="{{$component->id }}">

                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-around">
                            <div>
                                <label for="serial_num" class="form-label">Serial Number</label>
                                <input type="text" name="serial_num" id="serial_num" class="form-control" style="width: 250px" value="{{ $existingExtraProcess->serial_num ?? '' }}">
                            </div>
                            <div style="width: 150px">
                                <label for="qty" class="form-label">Quantity</label>
                                <input type="number" name="qty" id="qty" class="form-control" value="{{ $existingExtraProcess->qty ?? 1 }}" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <!-- Начальная строка -->
                        <div class="process-row mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            @php
                                                $isSelected = false;
                                                if ($existingExtraProcess && $existingExtraProcess->processes) {
                                                    if (is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1)) {
                                                        // Старая структура: ассоциативный массив
                                                        $isSelected = isset($existingExtraProcess->processes[$processName->id]);
                                                    } else {
                                                        // Новая структура: массив объектов
                                                        foreach ($existingExtraProcess->processes as $processItem) {
                                                            if ($processItem['process_name_id'] == $processName->id) {
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
                                <div class="col-md-6">
                                    <label for="process">Processes (Specification):</label>

                                    <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                            data-bs-target="#addProcessModal">
                                        <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                             style="width: 20px;" class="" >
                                    </button>

                                    <div class="process-options">
                                        <!-- Здесь будут radio buttons для выбранного имени процесса -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3" disabled>{{ __('Save') }}</button>
                        <a href="{{ route('extra_processes.show_all', ['id' => $current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
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
        // Динамическое добавление новых строк
        document.getElementById('add-process').addEventListener('click', function () {
            const container = document.getElementById('processes-container');
            const index = container.children.length;

            const newRow = document.createElement('div');
            newRow.classList.add('process-row', 'mb-3');
            newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label for="process_names">Process Name:</label>
                        <select name="processes[${index}][process_names_id]" class="form-control select2-process" required>
                            <option value="">Select Process Name</option>
                            @foreach ($processNames as $processName)
                                @php
                                    $isSelected = false;
                                    if ($existingExtraProcess && $existingExtraProcess->processes) {
                                        if (is_array($existingExtraProcess->processes) && array_keys($existingExtraProcess->processes) !== range(0, count($existingExtraProcess->processes) - 1)) {
                                            // Старая структура: ассоциативный массив
                                            $isSelected = isset($existingExtraProcess->processes[$processName->id]);
                                        } else {
                                            // Новая структура: массив объектов
                                            foreach ($existingExtraProcess->processes as $processItem) {
                                                if ($processItem['process_name_id'] == $processName->id) {
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
                    <div class="col-md-6">
                        <label for="process">Processes:</label>
                        <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                data-bs-target="#addProcessModal">
                            <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                 style="width: 20px;" class="" >
                        </button>
                        <div class="process-options">
                            <!-- Здесь будут radio buttons для выбранного имени процесса -->
                        </div>
                    </div>
                </div>`;

            container.appendChild(newRow);
        });

        // Обработка отправки формы
        document.getElementById('createProcessesForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const workorderId = document.querySelector('input[name="workorder_id"]').value;
            const componentId = document.querySelector('input[name="component_id"]').value;
            const serial_num = document.querySelector('input[name="serial_num"]').value;
            const qty = document.querySelector('input[name="qty"]').value;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            let hasSelectedRadio = false;

            processRows.forEach(row => {
                const processNameSelect = row.querySelector('.select2-process');
                const processNameId = processNameSelect.value;
                const processName = processNameSelect.options[processNameSelect.selectedIndex].text;

                const selectedRadio = row.querySelector('.process-options input[type="radio"]:checked');

                if (selectedRadio) {
                    const processId = selectedRadio.value;
                    processesData.push({
                        process_names_id: processNameId,
                        processes: [parseInt(processId)] // Массив с одним элементом для совместимости
                    });
                    hasSelectedRadio = true;
                }
            });

            if (!hasSelectedRadio) {
                alert('Process not added because no process is selected.');
                return;
            }

            // Отладочная информация
            console.log('Sending data:', {
                workorder_id: workorderId,
                component_id: componentId,
                serial_num: serial_num,
                qty: qty,
                processes: processesData
            });

            console.log('Processes order:', processesData.map((item, index) => ({
                order: index + 1,
                process_name_id: item.process_names_id,
                process_id: item.processes[0]
            })));

            console.log('Raw processesData structure:', JSON.stringify(processesData, null, 2));

            const requestBody = {
                workorder_id: workorderId,
                component_id: componentId,
                serial_num: serial_num,
                qty: qty,
                processes: JSON.stringify(processesData)
            };

            console.log('Request body:', requestBody);
            console.log('Request body JSON:', JSON.stringify(requestBody, null, 2));

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
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Success:', data);
                if (data.message) {
                    alert(data.message);
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving processes: ' + error.message);
            });
        });

        // Обновление radio buttons при изменении выбранного имени процесса
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('select2-process')) {
                const processNameId = event.target.value;
                const processOptionsContainer = event.target.closest('.process-row').querySelector('.process-options');
                const manualId = document.getElementById('processes-container').dataset.manualId;
                const saveButton = document.querySelector('button[type="submit"]');

                processOptionsContainer.innerHTML = '';

                if (processNameId) {
                    console.log(`/get-process/${processNameId}?manual_id=${manualId}`);
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
                            saveButton.disabled = true;
                        });
                }
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
