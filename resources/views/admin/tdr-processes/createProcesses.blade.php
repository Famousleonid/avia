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
                    <div id="processes-container">
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

            // Инициализация Select2 для нового элемента
            // $(newRow).find('.select2-process').select2();
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

                // Очистка контейнера
                processOptionsContainer.innerHTML = '';

                // Загрузка чекбоксов для выбранного имени процесса
                if (processNameId) {
                    fetch(`/admin/get-process/${processNameId}`)
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
                            console.error('Error fetching processes:', error);
                        });
                }
            }
        });
    </script>
@endsection
