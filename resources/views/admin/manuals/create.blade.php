@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1080px;
        }
    </style>

    <div class="container mt-3 ">
        <div class="card  bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">Create new CMM</h4>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('manuals.store') }}" enctype="multipart/form-data"
                      id="createCMMForm">
                    @csrf

                    <div class="form-group d-flex">
                        <div class="mt-2 m-3  p-3">
                            <div>
                                <label for="wo">{{ __('Number CMM') }}</label>
                                <input id='wo' type="text" class="form-control" name="number" required>
                                @error('number')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="">
                                <label for="title">{{ __('Description') }}</label>
                                <input id='title' type="text" class="form-control" name="title" required>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('Image:')}}</strong>
                                    <input type="file" name="img" class="form-control" placeholder="Image">
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('CSV Files:')}}</strong>
                                    <div id="csvFilesList" class="csv-files-list mt-2" style="display: none;">
                                        <!-- Здесь будут отображаться загруженные файлы -->
                                    </div>

                                    <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#csvUploadModal">
                                        <i class="fas fa-upload"></i> {{__('Add CSV Files')}}
                                    </button>
                                    <small class="text-muted d-block">{{__('Upload CSV files with component process requirements')}}</small>
                                </div>
                            </div>

                            <div class="mt-2">
                                <label for="revision_date">{{ __('Revision Date') }}</label>
                                <input id='revision_date' type="date" class="form-control" name="revision_date" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name">{{ __('Units PN') }}</label>
                                <input id='unit_name' type="text" class="form-control"
                                       name="unit_name" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name_training">{{ __('Units Training PN') }}</label>
                                <input id='unit_name_training' type="text" class="form-control" name="unit_name_training" required>
                            </div>
                            <div class=mt-2">
                                <label for="training_hours">{{ __('Unit First Training') }}</label>
                                <input id='training_hours' type="text"
                                       class="form-control" name="training_hours"
                                       >
                            </div>
                        </div>
                        <div style="width: 250px" class="m-3 p-2 ">

                            <div class="">
                                <label for="ovh_life">{{ __('Overhaul Life') }}</label>
                                <input id='ovh_life' type="text" class="form-control" name="ovh_life" required>
                            </div>
                            <div class="">
                                <label for="reg_sb">{{ __('Inspection Req.SB') }}</label>
                                <input id='reg_sb' type="text" class="form-control" name="reg_sb" required>
                            </div>
                            <div class="form-group ">
                                <label for="planes_id">{{ __('AirCraft Type')  }}</label>
                                <select id="planes_id" name="planes_id" class="form-control" required>
                                    <option value="">{{ __('Select AirCraft') }}</option>
                                    @foreach ($planes as $plane)
                                        <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addAirCraftModal">{{ __('Add AirCraft') }}</button>
                            </div>

                            <div class="form-group ">
                                <label for="builders_id">{{ __('MFR') }}</label>
                                <select id="builders_id" name="builders_id" class="form-control" required>
                                    <option value="">{{ __('Select MFR') }}</option>
                                    @foreach ($builders as $builder)
                                        <option value="{{ $builder->id }}">{{ $builder->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addMFRModal">{{ __('Add MFR') }}</button>
                            </div>

                            <div class="form-group ">
                                <label for="scopes_id">{{ __('Scope') }}</label>
                                <select id="scopes_id" name="scopes_id" class="form-control" required>
                                    <option value="">{{ __('Select Scope') }}</option>
                                    @foreach ($scopes as $scope)
                                        <option value="{{ $scope->id }}">{{ $scope->scope }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addScopeModal">{{ __('Add Scope') }}</button>
                            </div>


                            <div>
                                <label for="lib">{{ __('Library Number') }}</label>
                                <input id='lib' type="text" class="form-control" name="lib" required>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('Log Card Image:')}}</strong>
                                    <input type="file" name="log_img" class="form-control" placeholder="Image">
                                </div>
                            </div>

                        </div>
                        <div style="width: 400px" class="m-3 p-1 ">
                            <div class="mt-1">
                                <label for="units">{{ __('Units') }}</label>
                                <div id="unitInputs" class="">
                                    <div class="input-group mb-2 unit-field">
                                        <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                               name="units[]"
                                               required>
                                        <input type="text" class="form-control" placeholder="Enter EFF Code" style="width: 130px"
                                               name="eff_codes[]">
                                        <button class="btn btn-outline-primary" type="button" style="width: 90px" id="addUnitField">Add
                                            Unit</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3 ">{{ __('Add CMM') }}</button>
                        <a href="{{ route('manuals.index') }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления самолета -->
    <div class="modal fade" id="addAirCraftModal" tabindex="-1" aria-labelledby="addAirCraftModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAirCraftModalLabel">{{ __('Add AirCraft') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addAirCraftForm" data-no-spinner>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="planeName">{{ __('AirCraft Type') }}</label>
                            <input type="text" class="form-control" id="planeName" name="type" required>
                        </div>
                    </div>
                    <div class="modal-footer">
{{--                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>--}}
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления MFR -->
    <div class="modal fade" id="addMFRModal" tabindex="-1" aria-labelledby="addMFRModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMFRModalLabel">{{ __('Add MFR') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addMFRForm" data-no-spinner>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="builderName">{{ __('Name MFR') }}</label>
                            <input type="text" class="form-control" id="builderName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
{{--                       <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>--}}
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления Scope -->
    <div class="modal fade" id="addScopeModal" tabindex="-1" aria-labelledby="addScopeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScopeModalLabel">{{ __('Add Scope') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addScopeForm" data-no-spinner>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="scopeName">{{ __('Scope') }}</label>
                            <input type="text" class="form-control" id="scopeName" name="scope" required>
                        </div>
                    </div>
                    <div class="modal-footer">
{{--                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>--}}
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для загрузки CSV файлов -->
    <div class="modal fade" id="csvUploadModal" tabindex="-1" aria-labelledby="csvUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="csvUploadModalLabel">{{ __('Add CSV Files') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form id="csvUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="csvProcessType">{{ __('Process Type') }}</label>
                            <select id="csvProcessType" name="process_type" class="form-control" required>
                                <option value="">{{ __('Select Process Type') }}</option>
                                <option value="ndt">{{ __('NDT') }}</option>
                                <option value="cad">{{ __('CAD') }}</option>
                                <option value="stress_relief">{{ __('Stress Relief') }}</option>
                                <option value="log">{{ __('Log Card') }}</option>
                                <option value="paint">{{ __('Paint') }}</option>
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="csvFileInput">{{ __('CSV File') }}</label>
                            <input type="file" id="csvFileInput" name="csv_file" class="form-control" accept=".csv,.txt" required>
                            <small class="text-muted">{{__('Select CSV or TXT file to upload')}}</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="uploadCsvFile()">{{ __('Upload') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Функция для обработки отправки форм для самолетов, MFR и Scope
        function handleFormSubmission(formId, route, selectId, dataKey, dataValue, modalId) {
            document.getElementById(formId).addEventListener('submit', function (event) {
                event.preventDefault();
                if (this.submitted) {
                    return;
                }
                this.submitted = true;

                let formData = new FormData(this);

                fetch(route, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                    .then(response => response.json())

                    .then(data => {

                        console.log('response:', data); // временно для проверки

                        let select = document.getElementById(selectId);

                        // Правильное добавление option
                        let newOption = new Option(
                            data[dataValue], // текст
                            data[dataKey],   // value
                            true,
                            true
                        );

                        select.add(newOption);

                        // Уведомляем select что он обновился
                        select.dispatchEvent(new Event('change', { bubbles: true }));

                        // Если используется Select2
                        if (window.jQuery && $(select).data('select2')) {
                            $(select).trigger('change');
                        }

                        let modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                        modal.hide();

                        document.getElementById(formId).reset();
                        this.submitted = false;
                    })
            });
        }

        // Обновляем вызовы функции для передачи правильных ID модальных окон
        handleFormSubmission('addAirCraftForm', '{{ route('planes.store') }}', 'planes_id', 'id', 'type',
            'addAirCraftModal');
        handleFormSubmission('addMFRForm', '{{ route('builders.store') }}', 'builders_id', 'id', 'name',
            'addMFRModal');
        handleFormSubmission('addScopeForm', '{{ route('scopes.store') }}', 'scopes_id', 'id', 'scope', 'addScopeModal');



        // Массив для хранения временных CSV файлов
        let tempCsvFiles = [];

        // Функция для загрузки CSV файла через модальное окно
        function uploadCsvFile() {
            const fileInput = document.getElementById('csvFileInput');
            const processType = document.getElementById('csvProcessType').value;

            if (!fileInput.files.length) {
                alert('{{ __("Please select a file") }}');
                return;
            }

            if (!processType) {
                alert('{{ __("Please select a process type") }}');
                return;
            }

            const file = fileInput.files[0];
            const fileId = Date.now(); // Простой ID для временного файла

            // Проверяем, есть ли уже файл с таким process_type
            const existingFileIndex = tempCsvFiles.findIndex(f => f.process_type === processType);
            if (existingFileIndex !== -1) {
                // Заменяем существующий файл
                tempCsvFiles[existingFileIndex] = {
                    id: fileId,
                    file: file,
                    process_type: processType,
                    name: file.name
                };
            } else {
                // Добавляем новый файл
                tempCsvFiles.push({
                    id: fileId,
                    file: file,
                    process_type: processType,
                    name: file.name
                });
            }

            // Обновляем отображение
            updateCsvFilesDisplay();

            // Очищаем форму и закрываем модальное окно
            document.getElementById('csvUploadForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('csvUploadModal'));
            modal.hide();

            alert('{{ __("File added successfully") }}');
        }

        // Функция для обновления отображения CSV файлов
        function updateCsvFilesDisplay() {
            const csvFilesList = document.getElementById('csvFilesList');

            if (tempCsvFiles.length === 0) {
                csvFilesList.style.display = 'none';
                return;
            }

            csvFilesList.style.display = 'block';
            csvFilesList.innerHTML = '';

            tempCsvFiles.forEach(file => {
                const fileElement = document.createElement('div');
                fileElement.className = 'd-flex align-items-center mb-1';
                fileElement.setAttribute('data-process-type', file.process_type);

                fileElement.innerHTML = `
                    <span class="badge bg-outline-info me-2">${file.name}</span>
                    <span class="badge bg-secondary me-2">${file.process_type}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="removeCsvFile('${file.id}')">
                        <i class="fas fa-trash"></i> {{__('Del')}}
                    </button>
                `;

                csvFilesList.appendChild(fileElement);
            });
        }

        // Функция для удаления CSV файла
        function removeCsvFile(fileId) {
            tempCsvFiles = tempCsvFiles.filter(f => f.id != fileId);
            updateCsvFilesDisplay();
        }

        // Функция для добавления CSV файлов к форме перед отправкой
        function addCsvFilesToForm() {
            const form = document.getElementById('createCMMForm');

            // Удаляем старые скрытые поля CSV файлов
            const oldCsvInputs = form.querySelectorAll('input[name^="csv_files"]');
            oldCsvInputs.forEach(input => input.remove());

            // Добавляем новые файлы
            tempCsvFiles.forEach((file, index) => {
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = 'csv_files[]';
                fileInput.style.display = 'none';

                // Создаем DataTransfer для установки файла
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file.file);
                fileInput.files = dataTransfer.files;

                form.appendChild(fileInput);

                // Добавляем скрытое поле для process_type
                const processTypeInput = document.createElement('input');
                processTypeInput.type = 'hidden';
                processTypeInput.name = 'csv_process_types[]';
                processTypeInput.value = file.process_type;
                form.appendChild(processTypeInput);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('addUnitField').addEventListener('click', function () {
                const newUnitField = document.createElement('div');
                newUnitField.className = 'input-group mb-2 unit-field';
                newUnitField.innerHTML = `
            <input type="text" class="form-control" placeholder="Enter Unit Part Number" style="width: 130px" name="units[]" required>
            <input type="text" class="form-control" placeholder="Enter EFF Code" style="width: 130px" name="eff_codes[]">
            <button class="btn btn-outline-danger removeUnitField" type="button">Remove</button>
        `;
                document.getElementById('unitInputs').appendChild(newUnitField);
            });

            document.getElementById('unitInputs').addEventListener('click', function (event) {
                if (event.target.classList.contains('removeUnitField')) {
                    event.target.parentElement.remove();
                }
            });

            // Обработка отправки формы
            document.getElementById('createCMMForm').addEventListener('submit', function(e) {
                // Удаляем пустые поля units перед отправкой
                const unitFields = document.querySelectorAll('.unit-field');
                unitFields.forEach(function(field) {
                    const partNumberInput = field.querySelector('input[name="units[]"]');

                    if (partNumberInput && partNumberInput.value.trim() === '') {
                        // Если part_number пустой, удаляем весь блок
                        field.remove();
                    }
                });

                // Добавляем CSV файлы к форме
                addCsvFilesToForm();
            });
        });



    </script>
@endsection
