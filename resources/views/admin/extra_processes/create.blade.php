@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 850px;
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
                    <h4 class="text-primary">{{__('Add Extra Component Processes')}}</h4>
                    <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <button class="btn btn-outline-primary" type="button" style="width: 120px" id="add-process">
                            Add Process
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="createForm" role="form" method="POST" action="{{route('extra_processes.store')}}" class="createForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="form-group d-flex mb-3">
                        <label for="i_component_id" class="form-label pe-2">Component</label>

                        <select name="component_id" id="i_component_id" class="form-control" style="width: 300px" required>
                            <option selected value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}"
                                        data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                        data-title="{{ $component->name }}">
                                    {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#addComponentModal">{{ __('Add Component') }}
                        </button>
                    </div>



                    <div class="form-group mb-3">
                        <label for="qty" class="form-label">Quantity</label>
                        <input type="number" name="qty" id="qty" class="form-control" value="1" min="1" required>
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
                                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
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

    <!-- Modal - Add component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('components.storeFromExtra') }}" method="POST" id="addComponentForm">
                    @csrf

                    <div class="modal-body">
                        <input type="hidden" name="manual_id" value="{{$current_wo->unit->manual_id}}">
                        <input type="hidden" name="current_wo" value="{{$current_wo->id}}">
                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input id='name' type="text" class="form-control" name="name" required>
                        </div>
                        <div class="d-flex">

                            <div class="d-flex">
                                <div class="m-3">
                                    <div class="">
                                        <label for="ipl_num">{{ __('IPL Number') }}</label>
                                        <input id='ipl_num' type="text" class="form-control" name="ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                               required>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group">
                                            <strong>{{__('Image:')}}</strong>
                                            <input type="file" name="img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="part_number">{{ __('Part Number') }}</label>
                                        <input id='part_number' type="text" class="form-control"
                                               name="part_number" required>
                                    </div>

                                </div>

                                <div class="m-3">
                                    <div class="">
                                        <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                        <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                        >
                                    </div>

                                    <div class=" col-xs-12 col-sm-12 col-md-12 mt-2" >
                                        <div class="form-group">
                                            <strong>{{__(' Assy Image:')}}</strong>
                                            <input type="file" name="assy_img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="assy_part_number">{{ __(' Assembly Part Number') }}</label>
                                        <input id='assy_part_number' type="text" class="form-control"
                                               name="assy_part_number" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  id="log_card" name="log_card">
                                <label class="form-check-label" for="log_card">
                                    Log Card
                                </label>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Component</button>
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
        // Инициализация select2 для выбора компонента
        $(document).ready(function() {
            // Инициализация select2 для выбора компонента
            $('#i_component_id').select2({
                placeholder: 'Select a component',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No components found";
                    }
                }
            });
        });

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
            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
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
        document.getElementById('createForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const workorderId = document.querySelector('input[name="workorder_id"]').value;
            const componentId = document.querySelector('select[name="component_id"]').value;
            const qty = document.querySelector('input[name="qty"]').value;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            let hasSelectedRadio = false;

            processRows.forEach(row => {
                const processNameSelect = row.querySelector('select[name*="[process_names_id]"]');
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

            if (!componentId) {
                alert('Please select a component.');
                return;
            }

            // Отладочная информация
            console.log('Sending data:', {
                workorder_id: workorderId,
                component_id: componentId,
                qty: qty,
                processes: processesData
            });

            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('workorder_id', workorderId);
            formData.append('component_id', componentId);
            formData.append('qty', qty);
            formData.append('processes', JSON.stringify(processesData));

            const requestBody = {
                workorder_id: workorderId,
                component_id: componentId,
                qty: qty,
                processes: JSON.stringify(processesData)
            };

            console.log('Request body:', requestBody);

            fetch(`{{ route('extra_processes.store') }}`, {
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

        // Обновление чекбоксов при изменении выбранного имени процесса
        document.addEventListener('change', function (event) {
            if (event.target.name && event.target.name.includes('[process_names_id]')) {
                const processNameId = event.target.value;
                const processRow = event.target.closest('.process-row');
                const processOptionsContainer = processRow.querySelector('.process-options');
                const manualId = document.getElementById('processes-container').dataset.manualId;
                const saveButton = document.querySelector('button[type="submit"]');

                // Получаем индекс строки
                const container = document.getElementById('processes-container');
                const rows = container.querySelectorAll('.process-row');
                const index = Array.from(rows).indexOf(processRow);

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
            const select = currentRow.querySelector('select[name*="[process_names_id]"]');
            const processNameId = select.value;
            const processNameText = select.options[select.selectedIndex].text;
            document.getElementById('modalProcessName').innerText = processNameText;
            document.getElementById('modalProcessNameId').value = processNameId;

            const manualId = document.getElementById('processes-container').dataset.manualId;

            fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const container = document.getElementById('existingProcessContainer');
                    container.innerHTML = '';
                    if (data.availableProcesses && data.availableProcesses.length > 0) {
                        data.availableProcesses.forEach(process => {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `
                                <input type="radio" class="form-check-input" name="modal_processes" value="${process.id}" id="modal_process_${process.id}">
                                <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<div>No available processes</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading processes:', error);
                    document.getElementById('existingProcessContainer').innerHTML = '<div>Error loading processes</div>';
                });
        });

        // Обработка нажатия кнопки "Save Process" в модальном окне
        document.getElementById('saveProcessModal').addEventListener('click', function() {
            const processNameId = document.getElementById('modalProcessNameId').value;
            const newProcess = document.getElementById('newProcessInput').value.trim();
            const selectedRadio = document.querySelector('#existingProcessContainer input[type="radio"]:checked');

            if (newProcess === '' && !selectedRadio) {
                alert("Введите новый процесс или выберите существующий.");
                return;
            }

            const manualId = document.getElementById('processes-container').dataset.manualId;

            if (currentRow) {
                const processOptionsContainer = currentRow.querySelector('.process-options');
                const saveButton = document.querySelector('button[type="submit"]');

                if (newProcess !== '') {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    formData.append('process_names_id', processNameId);
                    formData.append('process', newProcess);
                    formData.append('manual_id', manualId);

                    fetch("{{ route('processes.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response:', data);
                            if (data.success) {
                                // Получаем индекс строки для правильного именования
                                const container = document.getElementById('processes-container');
                                const rows = container.querySelectorAll('.process-row');
                                const rowIndex = Array.from(rows).indexOf(currentRow);

                                const div = document.createElement('div');
                                div.classList.add('form-check');
                                div.innerHTML = `
                                <input type="radio" class="form-check-input" name="processes[${rowIndex}][process]" value="${data.process.id}" checked required>
                                <label class="form-check-label">${data.process.process}</label>
                            `;
                                processOptionsContainer.appendChild(div);
                                saveButton.disabled = false;

                                const noSpecLabel = processOptionsContainer.querySelector('.text-muted');
                                if (noSpecLabel) {
                                    noSpecLabel.remove();
                                }

                                document.getElementById('newProcessInput').value = '';
                            } else {
                                alert(data.message || "Ошибка при добавлении нового процесса.");
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert("Ошибка при добавлении нового процесса: " + error.message);
                        });
                }

                if (selectedRadio) {
                    const processId = selectedRadio.value;
                    const processLabel = selectedRadio.nextElementSibling.innerText;

                    // Получаем индекс строки для правильного именования
                    const container = document.getElementById('processes-container');
                    const rows = container.querySelectorAll('.process-row');
                    const rowIndex = Array.from(rows).indexOf(currentRow);

                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    div.innerHTML = `
                        <input type="radio" class="form-check-input" name="processes[${rowIndex}][process]" value="${processId}" checked required>
                        <label class="form-check-label">${processLabel}</label>
                    `;
                    processOptionsContainer.appendChild(div);
                    saveButton.disabled = false;
                }
            }

            const modalEl = document.getElementById('addProcessModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();
        });

        function applyTheme() {
            const isDark = document.documentElement.getAttribute('data-bs-theme');
            const selectContainer = $('.select2-container');
            if (isDark === 'dark') {
                selectContainer.addClass('select2-dark').removeClass('select2-light');
                $('.select2-container .select2-dropdown').addClass('select2-dark').removeClass('select2-light');
            } else {
                selectContainer.addClass('select2-light').removeClass('select2-dark');
                $('.select2-container .select2-dropdown').addClass('select2-light').removeClass('select2-dark');
            }
        }

        // Обработка успешного добавления компонента
        document.getElementById('addComponentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch("{{ route('components.storeFromExtra') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Закрываем модальное окно
                        const modalEl = document.getElementById('addComponentModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        modalInstance.hide();

                        // Показываем сообщение об успехе
                        alert('Component created successfully!');

                        // Перезагружаем страницу для обновления списка компонентов
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error creating component');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating component: ' + error.message);
                });
        });
    </script>
@endsection
