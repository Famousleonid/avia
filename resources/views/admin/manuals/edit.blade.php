@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1200px;
        }
        .fs-7{
            font-size: .7rem;
        }
    </style>

    <div class="container ">
        <div class="card bg-gradient">
            <div class="card-header">
                <h5><strong>{{__('Edit CMM:')}}</strong> {{ $cmm->number }}</h5>
            </div>

            <div class="card-body">
                <form method="POST"
                      action="{{ route('manuals.update', [ 'manual' => $cmm->id] ) }}"
                      enctype="multipart/form-data"   id="editCMMForm">
                    @csrf
                    @method('PUT')

                    <div class="form-group d-flex ">
                        <div class="mt-2 m-3 border p-2">
                            <div>
                                <label for="cmm_num">{{ __('CMM Number') }}</label>
                                <input id='cmm_num' type="text"
                                       class="form-control" name="number" value="{{ old('number', $cmm->number) }}"
                                       required>
                                @error('number')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mt-2">
                                <label for="title">{{ __('Description') }}</label>
                                <input id='title' type="text" class="form-control" name="title"
                                       value="{{ old('title', $cmm->title) }}" required>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('Image:')}}</strong>
                                    <input type="file" name="img" class="form-control" placeholder="Image">
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <div class="d-flex justify-content-between">
                                        <strong class="pt-3 ">{{__('CSV Files: ')}}</strong>
                                        <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#csvUploadModal">
                                            <i class="fas fa-upload"></i> {{__('Add CSV Files')}}
                                        </button>
                                    </div>

                                    @php
                                        $csvFiles = $cmm->getMedia('csv_files');
                                    @endphp
                                    @if($csvFiles->count() > 0)
                                        <div class="csv-files-list mt-2 mb-1">
                                            @foreach($csvFiles as $csvFile)
                                                <div class="d-flex align-items-center mb-1" data-process-type="{{ $csvFile->getCustomProperty('process_type') }}">
                                                    <span class="badge bg-outline-info me-2">{{ $csvFile->file_name }}</span>
                                                    @if($csvFile->getCustomProperty('process_type'))
                                                        <span class="badge bg-secondary me-2">{{ $csvFile->getCustomProperty('process_type') }}</span>
                                                    @endif
                                                    <a href="{{ route('manuals.csv.view', ['manual' => $cmm->id, 'file' => $csvFile->id]) }}" class="btn btn-sm btn-outline-info me-1">
                                                        <i class="fas fa-eye"></i> {{__('View')}}
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteCsvFile('{{ route('manuals.csv.delete', ['manual' => $cmm->id, 'file' => $csvFile->id]) }}', event)">
                                                        <i class="fas fa-trash"></i> {{__('Del')}}
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <small class="text-muted d-block ps-4 fs-7">{{__('Upload CSV files with component process
                                    requirements')}}</small>
                                </div>
                            </div>

                            <div class="mt-2">
                                <label for="revision_date">{{ __('Revision Date') }}</label>
                                <input id='revision_date' type="date" class="form-control" name="revision_date"
                                       value="{{ old('revision_date', $cmm->revision_date) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name">{{ __('Units PN') }}</label>
                                <input id='unit_name' type="text" class="form-control"
                                       name="unit_name"
                                       value="{{ old ('unit_name', $cmm->unit_name) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name_training">{{ __('Units Training PN') }}</label>
                                <input id='unit_name_training' type="text" class="form-control"
                                       name="unit_name_training"
                                       value="{{ old ('unit_name_training', $cmm->unit_name_training) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="training_hours">{{ __('Unit First Training Hours') }}</label>
                                <input id='training_hours' type="text"
                                       class="form-control"
                                       name="training_hours"
                                       value="{{ old('training_hours', $cmm->training_hours) }}" required>
                            </div>
                        </div>
                        <div style="width: 300px" class="m-3 p-2 border">
                            <div class="mb-3">
                                <label for="ovh_life">{{ __('Overhaul Life') }}</label>
                                <input id='ovh_life' type="text"
                                       class="form-control"
                                       name="ovh_life"
                                       value="{{ old('ovh_life', $cmm->ovh_life) }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="reg_sb">{{ __('Inspection Req.SB') }}</label>
                                <input id='reg_sb' type="text"
                                       class="form-control"
                                       name="reg_sb"
                                       value="{{ old('reg_sb', $cmm->reg_sb) }}" required>
                            </div>
                            <div class="form-group ">
                                <label for="planes_id">{{ __('AirCraft Type') }}</label>
                                <select id="planes_id" name="planes_id" class="form-control" required>
                                    <option value="">{{ __('Select AirCraft') }}</option>
                                    @foreach ($planes as $plane)
                                        <option value="{{ $plane->id }}" {{ $plane->id == $cmm->planes_id ? 'selected' : '' }}>{{ $plane->type }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addAirCraftModal">{{ __('Add AirCraft') }}</button>
                            </div>

                            <div class="form-group mt-2 ">
                                <label for="builders_id">{{ __('MFR') }}</label>
                                <select id="builders_id" name="builders_id" class="form-control" required>
                                    <option value="">{{ __('Select MFR') }}</option>
                                    @foreach ($builders as $builder)
                                        <option value="{{ $builder->id }}" {{ $builder->id == $cmm->builders_id ? 'selected' : '' }}>{{ $builder->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addMFRModal">{{ __('Add MFR') }}</button>
                            </div>

                            <div class="form-group mt-2">
                                <label for="scopes_id">{{ __('Scope') }}</label>
                                <select id="scopes_id" name="scopes_id" class="form-control" required>
                                    <option value="">{{ __('Select Scope') }}</option>
                                    @foreach ($scopes as $scope)
                                        <option value="{{ $scope->id }}" {{ $scope->id == $cmm->scopes_id ? 'selected' : '' }}>{{ $scope->scope }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link " data-bs-toggle="modal"
                                        data-bs-target="#addScopeModal">{{ __('Add Scope') }}</button>
                            </div>
                            <div class="">
                                <label for="lib">{{ __('Library Number') }}</label>
                                <input id='lib' type="text" class="form-control" name="lib"
                                       value="{{ old('lib', $cmm->lib) }}" required>
                            </div>

                        </div>

                        <div class="mt-3" style="width: 350px">
                            <label for="units">{{ __('Units') }}</label>
                            <div id="unitInputs" class="">
                                @if($cmm->units && $cmm->units->count() > 0)
                                    @foreach($cmm->units as $unit)
                                        <div class="input-group mb-2 d-flex unit-field">
                                            <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                                   name="units[]" value="{{ $unit->part_number }}" required>
                                            <input type="text" class="form-control" placeholder="Enter EFF Code" style="width: 130px"
                                                   name="eff_codes[]" value="{{ $unit->eff_code }}">
                                            <button class="btn btn-outline-danger removeUnitField" type="button">Remove</button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 unit-field">
                                        <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                               name="units[]" required>
                                        <input type="text" class="form-control" placeholder="Enter EFF Code" style="width: 130px"
                                               name="eff_codes[]">
                                        <button class="btn btn-outline-danger removeUnitField" type="button">Remove</button>
                                    </div>
                                @endif
                                <button class="btn btn-outline-primary" type="button" id="addUnitField">Add Unit</button>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-outline-primary text-center ">
                        {{ __('UpDate') }}
                    </button>
                    <a href="{{ route('manuals.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
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
                <form method="POST" id="addAirCraftForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="airCraftName">{{ __('Type AirCraft') }}</label>
                            <input type="text" class="form-control" id="airCraftName" name="type" required>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addMFRForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="mfrName">{{ __('Name MFR') }}</label>
                            <input type="text" class="form-control" id="mfrName" name="name" required>
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

    <!-- Модальное окно для добавления Scope -->
    <div class="modal fade" id="addScopeModal" tabindex="-1" aria-labelledby="addScopeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScopeModalLabel">{{ __('Add Scope') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addScopeForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="scopeName">{{ __('Name Scope') }}</label>
                            <input type="text" class="form-control" id="scopeName" name="scope" required>
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
        function handleFormSubmission(formId, modalId, route, selectId, dataKey, dataValue) {
            document.getElementById(formId).addEventListener('submit', function (event) {
                event.preventDefault();
                let formData = new FormData(this);
                fetch(route, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        let select = document.getElementById(selectId);
                        let option = document.createElement('option');
                        option.value = data[dataKey];
                        option.text = data[dataValue];
                        select.add(option);

                        // 2. Закрываем модальное окно вручную
                        let modalElement = document.getElementById(modalId);

                        if (modalElement) {
                            let modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            } else {
                                // Если нет экземпляра, создайте новый и закройте его
                                let newModal = new bootstrap.Modal(modalElement);
                                newModal.hide();
                            }
                        }
                        // 3. Очистка формы
                        // document.getElementById(formId).reset();
                    })
                    .catch(error => console.error('Ошибка:', error));
            });
        }

        handleFormSubmission('addAirCraftForm', 'addAirCraftModal', '{{ route('planes.store') }}',
            'planes_id', 'id', 'type');
        handleFormSubmission('addMFRForm', 'addMFRModal', '{{ route('builders.store') }}', 'builders_id', 'id',
            'name');
        handleFormSubmission('addScopeForm', 'addScopeModal', '{{ route('scopes.store') }}', 'scopes_id', 'id', 'scope');

        function deleteCsvFile(url, event) {
            if (confirm('{{ __("Are you sure you want to delete this file?") }}')) {
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Находим родительский элемент файла и удаляем его
                            const fileElement = event.target.closest('.d-flex');
                            if (fileElement) {
                                fileElement.remove();
                            }
                        } else {
                            throw new Error(data.error || '{{ __("Error deleting file") }}');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || '{{ __("Error deleting file") }}');
                    });
            }
            // Предотвращаем всплытие события
            event.stopPropagation();
            return false;
        }

        // Обработка отправки основной формы
        document.getElementById('editCMMForm').addEventListener('submit', function(e) {
            // Если есть ошибки валидации, не отправляем форму
            if (!this.checkValidity()) {
                e.preventDefault();
                return false;
            }

            // Удаляем пустые поля units перед отправкой
            const unitFields = document.querySelectorAll('.unit-field');
            unitFields.forEach(function(field) {
                const partNumberInput = field.querySelector('input[name="units[]"]');
                const effCodeInput = field.querySelector('input[name="eff_codes[]"]');

                if (partNumberInput && partNumberInput.value.trim() === '') {
                    // Если part_number пустой, удаляем весь блок
                    field.remove();
                }
            });
        });

        // Функция для загрузки CSV файла через модальное окно
        function uploadCsvFile() {
            const fileInput = document.getElementById('csvFileInput');
            const processType = document.getElementById('csvProcessType').value;
            const formData = new FormData();

            if (!fileInput.files.length) {
                alert('{{ __("Please select a file") }}');
                return;
            }

            if (!processType) {
                alert('{{ __("Please select a process type") }}');
                return;
            }

                formData.append('csv_file', fileInput.files[0]);
                formData.append('process_type', processType);

                fetch('{{ route("manuals.csv.store", ["manual" => $cmm->id]) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Если файл с таким process_type уже существовал, удаляем его из DOM
                            const existingFile = document.querySelector(`[data-process-type="${processType}"]`);
                            if (existingFile) {
                                existingFile.remove();
                            }

                            // Добавляем новый файл в список
                            const fileList = document.querySelector('.csv-files-list');
                        if (!fileList) {
                            // Создаем контейнер для файлов, если его нет
                            const csvSection = document.querySelector('.form-group strong:contains("CSV Files")').parentElement;
                            const newFileList = document.createElement('div');
                            newFileList.className = 'csv-files-list mt-2';
                            csvSection.appendChild(newFileList);
                        }

                            const fileElement = createFileElement(data.file);
                        document.querySelector('.csv-files-list').appendChild(fileElement);

                        // Очищаем форму и закрываем модальное окно
                        document.getElementById('csvUploadForm').reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('csvUploadModal'));
                        modal.hide();

                        alert('{{ __("File uploaded successfully") }}');
                        } else {
                            throw new Error(data.error || '{{ __("Error uploading file") }}');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || '{{ __("Error uploading file") }}');
                    });
        }

        // Функция для создания элемента файла
        function createFileElement(file) {
            const div = document.createElement('div');
            div.className = 'd-flex align-items-center mb-1';
            div.setAttribute('data-process-type', file.process_type);

            div.innerHTML = `
                <span class="badge bg-outline-info me-2">${file.name}</span>
                ${file.process_type ? `<span class="badge bg-secondary me-2">${file.process_type}</span>` : ''}
                <a href="/admin/manuals/{{ $cmm->id }}/csv/${file.id}"
                   class="btn btn-sm btn-outline-info me-1">
                    <i class="fas fa-eye"></i> {{__('View')}}
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="deleteCsvFile('/admin/manuals/{{ $cmm->id }}/csv/${file.id}', event)">
                    <i class="fas fa-trash"></i> {{__('Del')}}
            </button>
`;

            return div;
        }

        // Функциональность для управления полями units
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
        });
    </script>
@endsection
