@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 650px;
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
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Add Component Processes') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        {{ $current_tdr->component->name }}
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}
                    </div>
                    <button class="btn btn-outline-primary" type="button" style="width: 120px" id="add-process">
                        Add Process
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tdr-processes.store', $current_tdr->id) }}" enctype="multipart/form-data" id="createCPForm">
                    @csrf
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">

                    <!-- Контейнер для строк с процессами -->
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
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('admin.tdrs.processes', ['workorder_id' => $current_tdr->workorder->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>

            </div>
            <!-- Модальное окно для работы с процессом -->
            <div class="modal fade" id="addProcessModal" tabindex="-1" aria-labelledby="addProcessModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content bg-gradient">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProcessModalLabel">
                                Add Process for: <span id="modalProcessName"></span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Скрытое поле для передачи ID выбранного Process Name -->
                            <input type="hidden" id="modalProcessNameId" name="process_names_id">

                            <!-- Переключатель сценария -->
                            <div class="mb-3">
                                <label class="form-check-label me-2">Выберите сценарий:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="processOption" id="optionNew" value="new" checked>
                                    <label class="form-check-label" for="optionNew">Ввести новый процесс</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="processOption" id="optionExisting" value="existing">
                                    <label class="form-check-label" for="optionExisting">Выбрать существующий</label>
                                </div>
                            </div>

                            <!-- Панель для ввода нового процесса -->
                            <div id="newProcessPanel" class="mb-3">
                                <div class="mb-3">
                                    <label for="newProcessInput" class="form-label">Название процесса</label>
                                    <input type="text" class="form-control" id="newProcessInput" name="new_process">
                                </div>
                            </div>

                            <!-- Панель для выбора существующего процесса -->
                            <div id="existingProcessPanel" class="mb-3" style="display: none;">
                                <div id="modalProcessCheckboxes">
                                    Loading...
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-outline-primary" id="saveProcessModal">Сохранить</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

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
            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                            @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="process">Processes:</label>
            <div class="process-options">
                <!-- Здесь будут чекбоксы для выбранного имени процесса -->
            </div>
        </div>
    </div>`;

            container.appendChild(newRow);

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

                if (selectedProcessIds.length > 0) {
                    processesData.push({
                        process_names_id: processNameId,
                        processes: selectedProcessIds // Сохраняем массив ID процессов
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

            fetch(`/admin/tdr-processes`, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Success:', data);
                    if (data.message) {
                        alert(data.message); // Показываем сообщение об успехе
                    }
                    // Если есть URL для перенаправления, выполняем редирект
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving processes.');
                });
        });

        // Обновление чекбоксов при изменении выбранного имени процесса
        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('select2-process')) {
                const processNameId = event.target.value;
                const processOptionsContainer = event.target.closest('.process-row').querySelector('.process-options');
                const manualId = document.getElementById('processes-container').dataset.manualId; // Получаем manual_id

                // Очистка контейнера для чекбоксов
                processOptionsContainer.innerHTML = '';

                // Если имя процесса выбрано, загружаем связанные процессы
                if (processNameId) {
                    console.log(`/admin/get-process/${processNameId}?manual_id=${manualId}`);
                    fetch(`/admin/get-process/${processNameId}?manual_id=${manualId}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(process => {
                                const checkbox = document.createElement('div');
                                checkbox.classList.add('form-check');
                                checkbox.innerHTML = `
                            <input type="checkbox" name="processes[${processNameId}][process][]" value="${process.id}" class="form-check-input">
                            <label class="form-check-label">${process.process}</label>
                        `;
                                processOptionsContainer.appendChild(checkbox);
                            });
                        })
                        .catch(error => {
                            console.error('Ошибка при получении процессов:', error);
                        });
                }
            }
        });

        // Глобальная переменная для хранения текущей строки, из которой вызвали модальное окно
        let currentRow = null;

        // Функция для загрузки существующих процессов по выбранному Process Name
        function loadExistingProcesses() {
            const processNameId = document.getElementById('modalProcessNameId').value;
            const manualId = document.getElementById('processes-container').dataset.manualId;
            fetch(`/admin/get-process/${processNameId}?manual_id=${manualId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('modalProcessCheckboxes');
                    container.innerHTML = ''; // Очистка контейнера
                    if(data.length > 0) {
                        data.forEach(process => {
                            const checkboxDiv = document.createElement('div');
                            checkboxDiv.className = 'form-check';
                            checkboxDiv.innerHTML = `
                            <input type="checkbox" name="modal_processes[]" value="${process.id}" class="form-check-input">
                            <label class="form-check-label">${process.process}</label>
                        `;
                            container.appendChild(checkboxDiv);
                        });
                    } else {
                        container.innerHTML = '<div>Нет доступных процессов</div>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка при загрузке процессов:', error);
                    document.getElementById('modalProcessCheckboxes').innerHTML = '<div>Error loading processes</div>';
                });
        }

        // При клике на кнопку открытия модального окна внутри строки сохраняем ссылку на строку
        document.querySelectorAll('.btn.btn-link[data-bs-target="#addProcessModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                currentRow = this.closest('.process-row');
                const select = currentRow.querySelector('.select2-process');
                const processNameText = select.options[select.selectedIndex].text;
                const processNameId = select.value;
                // Заполняем заголовок модального окна и скрытое поле
                document.getElementById('modalProcessName').innerText = processNameText;
                document.getElementById('modalProcessNameId').value = processNameId;
                // По умолчанию выбираем режим нового процесса
                document.getElementById('optionNew').checked = true;
                document.getElementById('newProcessPanel').style.display = 'block';
                document.getElementById('existingProcessPanel').style.display = 'none';
            });
        });

        // Переключение между сценариями
        document.querySelectorAll('input[name="processOption"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'new') {
                    document.getElementById('newProcessPanel').style.display = 'block';
                    document.getElementById('existingProcessPanel').style.display = 'none';
                } else if (this.value === 'existing') {
                    document.getElementById('newProcessPanel').style.display = 'none';
                    document.getElementById('existingProcessPanel').style.display = 'block';
                    loadExistingProcesses();
                }
            });
        });

        // Обработчик для кнопки "Сохранить" в модальном окне
        document.getElementById('saveProcessModal').addEventListener('click', function() {
            const selectedOption = document.querySelector('input[name="processOption"]:checked').value;
            const processNameId = document.getElementById('modalProcessNameId').value;

            if (selectedOption === 'new') {
                const newProcess = document.getElementById('newProcessInput').value.trim();
                if (!newProcess) {
                    alert('Введите название нового процесса');
                    return;
                }
                // Подготовка данных для нового процесса
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('process_names_id', processNameId);
                formData.append('process', newProcess);
                // Отправляем AJAX-запрос для сохранения нового процесса
                fetch(`/admin/processes/add`, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Процесс успешно добавлен');
                            // Добавляем новый процесс в текущую строку в виде чекбокса
                            if (currentRow) {
                                const processOptionsContainer = currentRow.querySelector('.process-options');
                                const checkboxDiv = document.createElement('div');
                                checkboxDiv.className = 'form-check';
                                checkboxDiv.innerHTML = `
                            <input type="checkbox" name="processes[${processNameId}][process][]" value="${data.process.id}" class="form-check-input" checked>
                            <label class="form-check-label">${data.process.process}</label>
                        `;
                                processOptionsContainer.appendChild(checkboxDiv);
                            }
                            // Закрываем модальное окно
                            const modalElement = document.getElementById('addProcessModal');
                            const modalInstance = bootstrap.Modal.getInstance(modalElement);
                            modalInstance.hide();
                            // Сброс значения поля
                            document.getElementById('newProcessInput').value = '';
                        } else {
                            alert('Ошибка при добавлении процесса');
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        alert('Ошибка при добавлении процесса');
                    });
            } else if (selectedOption === 'existing') {
                // Собираем выбранные чекбоксы из существующих процессов
                const checkboxes = document.querySelectorAll('#modalProcessCheckboxes input[type="checkbox"]:checked');
                if (checkboxes.length === 0) {
                    alert('Выберите хотя бы один процесс');
                    return;
                }
                if (currentRow) {
                    const processOptionsContainer = currentRow.querySelector('.process-options');
                    checkboxes.forEach(checkbox => {
                        const processId = checkbox.value;
                        const processLabel = checkbox.nextElementSibling.innerText;
                        const checkboxDiv = document.createElement('div');
                        checkboxDiv.className = 'form-check';
                        checkboxDiv.innerHTML = `
                        <input type="checkbox" name="processes[${processNameId}][process][]" value="${processId}" class="form-check-input" checked>
                        <label class="form-check-label">${processLabel}</label>
                    `;
                        processOptionsContainer.appendChild(checkboxDiv);
                    });
                }
                // Закрываем модальное окно
                const modalElement = document.getElementById('addProcessModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                modalInstance.hide();
            }
        });



    </script>
@endsection
