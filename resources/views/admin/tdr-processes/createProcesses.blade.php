@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 950px;
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
                        <div>
                            PN: {{ $current_tdr->component->part_number }}
                            SN: {{ $current_tdr->serial_number }}
                        </div>

                    </div>
                    <button class="btn btn-outline-primary" type="button" style="width: 120px" id="add-process">
                        Add Process
                    </button>
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
                                <div class="col-md-5">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
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
                                <div class="col-md-2">
{{--                                    <label for="ec">EC:</label>--}}
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_0">
                                        <label class="form-check-label" for="ec_0">
                                            EC
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('tdrs.processes', ['workorder_id' => $current_tdr->workorder->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
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
                <div class="row ">
                    <div class="col-md-5">
                        <label for="process_names">Process Name:</label>
                        <select name="processes[${index}][process_names_id]" class="form-control select2-process" required>
                            <option value="">Select Process Name</option>
                            @foreach ($processNames as $processName)
            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
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
                <!-- Здесь будут чекбоксы для выбранного имени процесса -->
            </div>
        </div>
        <div class="col-md-2">
             {{--                                    <label for="ec">EC:</label>--}}
            <div class="form-check mt-2">
                <input type="checkbox" name="processes[${index}][ec]" value="1" class="form-check-input" id="ec_${index}">
                <label class="form-check-label" for="ec_${index}">
                    EC
                </label>
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

                // Получаем значение чекбокса EC
                const ecCheckbox = row.querySelector('input[name*="[ec]"]');
                const ecValue = ecCheckbox ? ecCheckbox.checked : false;

                if (selectedProcessIds.length > 0) {
                    processesData.push({
                        process_names_id: processNameId,
                        processes: selectedProcessIds, // Сохраняем массив ID процессов
                        ec: ecValue // Добавляем значение EC
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
                    processes: JSON.stringify(processesData)
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
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
                const saveButton = document.querySelector('button[type="submit"]');

                // Очистка контейнера для чекбоксов
                processOptionsContainer.innerHTML = '';

                // Если имя процесса выбрано, загружаем связанные процессы
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
                                    const checkbox = document.createElement('div');
                                    checkbox.classList.add('form-check');
                                    checkbox.innerHTML = `
                                <input type="checkbox" name="processes[${processNameId}][process][]" value="${process.id}" class="form-check-input">
                                <label class="form-check-label">${process.process}</label>
                            `;
                                    processOptionsContainer.appendChild(checkbox);
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

                // Загружаем availableProcesses для выбранного process_name_id и manualId
                fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        const container = document.getElementById('existingProcessContainer');
                        container.innerHTML = ''; // Очищаем контейнер
                        if (data.availableProcesses && data.availableProcesses.length > 0) {
                            data.availableProcesses.forEach(process => {
                                // Создаем элемент чекбокса для каждого процесса
                                const div = document.createElement('div');
                                div.className = 'form-check';
                                div.innerHTML = `
                            <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
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
                        <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
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
            const selectedCheckboxes = document.querySelectorAll('#existingProcessContainer input[type="checkbox"]:checked');

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

                // Если введён новый процесс – отправляем AJAX-запрос для его создания
                if (newProcess !== '') {
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
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response:', data); // Добавляем для отладки
                            if (data.success) {
                                // Добавляем созданный процесс в виде чекбокса в контейнер текущей строки
                                const div = document.createElement('div');
                                div.classList.add('form-check');
                                div.innerHTML = `
                            <input type="checkbox" class="form-check-input" name="processes[${processNameId}][process][]" value="${data.process.id}" checked>
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

                                // Очищаем выбранные чекбоксы в модальном окне
                                document.querySelectorAll('#existingProcessContainer input[type="checkbox"]:checked').forEach(checkbox => {
                                    checkbox.checked = false;
                                });

                                // Показываем сообщение об успехе
                                alert('Процесс успешно добавлен!');

                                // Перезагружаем список существующих процессов в модальном окне
                                const manualId = document.getElementById('processes-container').dataset.manualId;
                                fetch(`{{ route('processes.getProcesses') }}?processNameId=${processNameId}&manualId=${manualId}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        const container = document.getElementById('existingProcessContainer');
                                        container.innerHTML = '';
                                        if (data.availableProcesses && data.availableProcesses.length > 0) {
                                            data.availableProcesses.forEach(process => {
                                                const div = document.createElement('div');
                                                div.className = 'form-check';
                                                div.innerHTML = `
                                                    <input type="checkbox" class="form-check-input" name="modal_processes[]" value="${process.id}" id="modal_process_${process.id}">
                                                    <label class="form-check-label" for="modal_process_${process.id}">${process.process}</label>
                                                `;
                                                container.appendChild(div);
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error reloading processes:', error);
                                    });
                            } else {
                                alert(data.message || "Ошибка при добавлении нового процесса.");
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert("Ошибка при добавлении нового процесса: " + error.message);
                        });
                }

                // Если выбраны существующие процессы – добавляем их в контейнер текущей строки
                if (selectedCheckboxes.length > 0) {
                    selectedCheckboxes.forEach(checkbox => {
                        const processId = checkbox.value;
                        const processLabel = checkbox.nextElementSibling.innerText;
                        const div = document.createElement('div');
                        div.classList.add('form-check');
                        div.innerHTML = `
                    <input type="checkbox" class="form-check-input" name="processes[${processNameId}][process][]" value="${processId}" checked>
                    <label class="form-check-label">${processLabel}</label>
                `;
                        processOptionsContainer.appendChild(div);
                    });
                    saveButton.disabled = false; // Активируем кнопку Save
                }
            }

            // Закрываем модальное окно
            const modalEl = document.getElementById('addProcessModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();
        });


    </script>
@endsection
