@extends('admin.master')

@section('content')

        <style>
            .container { max-width: 1180px; }

            /* page fits viewport */
            .cmm-page{
                height: calc(100vh - 70px);
                display:flex;
                flex-direction:column;
                min-height:0;
            }
            .cmm-card{
                flex:1 1 auto;
                display:flex;
                flex-direction:column;
                min-height:0;
            }
            .cmm-card .card-body{
                flex:1 1 auto;
                min-height:0;
                overflow:auto; /* scroll only form */
            }

            /* sticky footer */
            .cmm-footer{
                position: sticky;
                bottom: 0;
                z-index: 10;
                background: rgba(18, 18, 18, .92);
                backdrop-filter: blur(6px);
                border-top: 1px solid rgba(255,255,255,.08);
            }

            /* compact + aligned */
            .form-label{ margin-bottom:.25rem; }
            .form-control,.form-select{ padding-top:.35rem; padding-bottom:.35rem; }

            /* "Add ..." button at right of select row */
            .inline-add{
                white-space:nowrap;
                padding-left:.5rem;
                padding-right:.5rem;
            }

            /* Right column sections */
            .right-panel{
                border-left: 1px solid rgba(255,255,255,.08);
                padding-left: 14px;
            }
            @media (max-width: 991.98px){
                .right-panel{
                    border-left: 0;
                    padding-left: 0;
                    border-top: 1px solid rgba(255,255,255,.08);
                    padding-top: 14px;
                }
            }

            .section-title{
                font-size: .85rem;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: rgba(255,255,255,.55);
                margin: .25rem 0 .5rem;
            }
        </style>

        <div class="container mt-1 cmm-page">
            <div class="card bg-gradient cmm-card">

                <div class="card-header">
                    <h5 class="text-info mb-0">Create new CMM</h5>
                </div>

                <form method="POST" action="{{ route('manuals.store') }}" enctype="multipart/form-data" id="createCMMForm">
                    @csrf

                    <div class="card-body">
                        <div class="row g-3">

                            {{-- LEFT: main fields --}}
                            <div class="col-12 col-lg-8">
                                <div class="row g-3">

                                    <div class="col-12 col-md-6">
                                        <label for="wo" class="form-label">{{ __('CMM No:') }}</label>
                                        <input id="wo" type="text" class="form-control" name="number" required>
                                        @error('number')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="lib" class="form-label">{{ __('Library No:') }}</label>
                                        <input id="lib" type="text" class="form-control" name="lib" required>

                                    </div>

                                    <div class="col-12">
                                        <label for="title" class="form-label">{{ __('Description') }}</label>
                                        <input id="title" type="text" class="form-control" name="title" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="unit_name" class="form-label">{{ __('Component Part No:') }}</label>
                                        <input id="unit_name" type="text" class="form-control" name="unit_name" required>
                                    </div>

                                    {{-- Select rows with "Add" button on the right --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('AirCraft Type') }}</label>
                                        <div class="d-flex gap-2 align-items-start">
                                            <select id="planes_id" name="planes_id" class="form-select" required>
                                                <option value="">{{ __('Select AirCraft') }}</option>
                                                @foreach ($planes as $plane)
                                                    <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                                @endforeach
                                            </select>

                                            <button type="button"
                                                    class="btn btn-outline-info inline-add"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addAirCraftModal">
                                                + {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="unit_name_training" class="form-label">{{ __('Component Training Part No:') }}</label>
                                        <input id="unit_name_training" type="text" class="form-control" name="unit_name_training" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('MFR') }}</label>
                                        <div class="d-flex gap-2 align-items-start">
                                            <select id="builders_id" name="builders_id" class="form-select" required>
                                                <option value="">{{ __('Select MFR') }}</option>
                                                @foreach ($builders as $builder)
                                                    <option value="{{ $builder->id }}">{{ $builder->name }}</option>
                                                @endforeach
                                            </select>

                                            <button type="button"
                                                    class="btn btn-outline-info inline-add"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addMFRModal">
                                                + {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="training_hours" class="form-label">{{ __('Component First Training (hh)') }}</label>
                                        <input id="training_hours" type="text" class="form-control" name="training_hours">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('Scope') }}</label>
                                        <div class="d-flex gap-2 align-items-start">
                                            <select id="scopes_id" name="scopes_id" class="form-select" required>
                                                <option value="">{{ __('Select Scope') }}</option>
                                                @foreach ($scopes as $scope)
                                                    <option value="{{ $scope->id }}">{{ $scope->scope }}</option>
                                                @endforeach
                                            </select>

                                            <button type="button"
                                                    class="btn btn-outline-info inline-add"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addScopeModal">
                                                + {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label for="ovh_life" class="form-label">{{ __('Overhaul Life') }}</label>
                                        <input id="ovh_life" type="text" class="form-control" name="ovh_life" >
                                    </div>

                                    <div class="col-12 col-md-3">
                                        <label for="reg_sb" class="form-label">{{ __('Inspection Req.SB') }}</label>
                                        <input id="reg_sb" type="text" class="form-control" name="reg_sb">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="revision_date">{{ __('Revision Date') }}</label>
                                        <input id="revision_date"
                                               type="text"
                                               class="form-control "
                                               name="revision_date"
                                               data-fp

                                               required>
                                    </div>

                                </div>
                            </div>

                            {{-- RIGHT: all file inputs on top + Units below --}}
                            <div class="col-12 col-lg-4 right-panel">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Image component:') }}</label>
                                    <input type="file" name="img" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('Log Card Image:') }}</label>
                                    <input type="file" name="log_img" class="form-control">
                                </div>

{{--                                <div class="mb-3">--}}
{{--                                    <label class="form-label"><strong>{{ __('CSV Files:') }}</strong></label>--}}

{{--                                    --}}{{-- list placeholder (будет заполняться JS-ом если понадобится) --}}
{{--                                    <div id="csvFilesList" class="csv-files-list mt-2" style="display:none;"></div>--}}

{{--                                    <button type="button" class="btn btn-outline-primary mt-2"--}}
{{--                                            data-bs-toggle="modal" data-bs-target="#csvUploadModal">--}}
{{--                                        <i class="fas fa-upload"></i> {{ __('Add CSV Files') }}--}}
{{--                                    </button>--}}
{{--                                    <small class="text-muted d-block">--}}
{{--                                        {{ __('Upload CSV files with component process requirements') }}--}}
{{--                                    </small>--}}
{{--                                </div>--}}

                                <hr class="opacity-25">

                                <div class="section-title">Units</div>

                                <div id="unitInputs">
                                    <div class="input-group mb-2 unit-field">
                                        <input type="text" class="form-control" placeholder="Enter Unit PN"
                                               name="units[]" required>
                                        <input type="text" class="form-control" placeholder="Enter EFF Code"
                                               name="eff_codes[]">
                                        <button class="btn btn-outline-primary" type="button" id="addUnitField">
                                            Add
                                        </button>
                                    </div>
                                </div>

                                <small class="text-muted">Add Unit PN / EFF Code pairs.</small>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer cmm-footer">
                        <div class="d-flex justify-content-end gap-2 py-2">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Add CMM') }}</button>
                            <a href="{{ route('manuals.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Add AirCraft --}}
        <div class="modal fade" id="addAirCraftModal" tabindex="-1" aria-labelledby="addAirCraftModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAirCraftModalLabel">{{ __('Add AirCraft') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" id="addAirCraftForm" data-no-spinner>
                        @csrf
                        <div class="modal-body">
                            <label for="planeName" class="form-label">{{ __('AirCraft Type') }}</label>
                            <input type="text" class="form-control" id="planeName" name="type" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Add MFR --}}
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
                            <label for="builderName" class="form-label">{{ __('Name MFR') }}</label>
                            <input type="text" class="form-control" id="builderName" name="name" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Add Scope --}}
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
                            <label for="scopeName" class="form-label">{{ __('Scope') }}</label>
                            <input type="text" class="form-control" id="scopeName" name="scope" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CSV Upload Modal (no JS here; just UI) --}}
        <div class="modal fade" id="csvUploadModal" tabindex="-1" aria-labelledby="csvUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="csvUploadModalLabel">{{ __('Add CSV Files') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="csvProcessType" class="form-label">{{ __('Process Type') }}</label>
                            <select id="csvProcessType" class="form-select" disabled>
                                <option>{{ __('Select Process Type') }}</option>
                            </select>
                            <small class="text-muted d-block">UI placeholder (upload handled elsewhere)</small>
                        </div>

                        <div class="mb-3">
                            <label for="csvFileInput" class="form-label">{{ __('CSV File') }}</label>
                            <input type="file" id="csvFileInput" class="form-control" disabled>
                            <small class="text-muted">{{ __('Select CSV or TXT file to upload') }}</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('scripts')

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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') return;

            flatpickr('[data-fp]', {
                altInput: true,
                altFormat: "d.m.Y",   // что видит пользователь
                dateFormat: "Y-m-d",  // что отправляется на сервер
                allowInput: false,
                disableMobile: true,
            });
        });
    </script>
@endsection
