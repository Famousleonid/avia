@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 750px;
        }
    </style>

    <div class="container ">
        <div class="card bg-gradient">
            <div class="card-header">
                <h5><strong>{{__('Edit CMM:')}}</strong> {{ $cmm->number }}</h5>
            </div>

            <div class="card-body">
                <form method="POST"
                      action="{{ route('admin.manuals.update', [ 'manual' => $cmm->id] ) }}"
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
{{--                                    <strong>{{__('CSV File d.c(e.g. ndt_stsv):')}}</strong>--}}
                                    <strong>{{__('CSV FileS: ')}}</strong>
                                    @php
                                        $csvMedia = $cmm->getMedia('csv_files')->first();
                                        $processType = $csvMedia ? $csvMedia->getCustomProperty('process_type') : null;
                                    @endphp
                                    @if($cmm->getCsvFileName())
                                        <div class="mb-2">
                                            <span class="badge bg-outline-info">{{ $cmm->getCsvFileName() }}</span>
{{--                                            @if($processType)--}}
{{--                                                <span class="badge bg-secondary">{{ $processType }}</span>--}}
{{--                                            @endif--}}
{{--                                            <a href="{{ route('admin.manuals.csv.download', $cmm) }}" class="btn btn-sm--}}
{{--                                            btn-outline-primary">--}}
{{--                                                <i class="fas fa-download"></i> {{__('Download')}}--}}
{{--                                            </a>--}}
                                            <a href="{{ route('admin.manuals.csv.view', $cmm) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> {{__('View')}}
                                            </a>
                                        </div>
                                    @endif

                                    <select name="process_type" class="form-control mb-2">
                                        <option value="">{{ __('Select Process Type (Optional)') }}</option>
                                        <option value="ndt" {{ old('process_type', $processType) == 'ndt' ? 'selected' : '' }}>{{ __('NDT') }}</option>
                                        <option value="cad" {{ old('process_type', $processType) == 'cad' ? 'selected' : '' }}>{{ __('Cad') }}</option>
                                        <option value="stress_relief" {{ old('process_type', $processType) == 'stress_relief' ? 'selected' : '' }}>{{ __('Stress Relief') }}</option>
                                        <option value="other" {{ old('process_type', $processType) == 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                                    </select>
                                    <input type="file" name="csv_file" class="form-control" accept=".csv,.txt">
                                    <small class="text-muted">{{__('Upload CSV file with component process requirements')}}</small>
                                </div>
                            </div>

                            <div class="">
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

                    </div>

                    <button type="submit" class="btn btn-outline-primary text-center ">
                        {{ __('UpDate') }}
                    </button>
                    <a href="{{ route('admin.manuals.index') }}" class="btn btn-outline-secondary">
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

        handleFormSubmission('addAirCraftForm', 'addAirCraftModal', '{{ route('admin.planes.store') }}',
            'planes_id', 'id', 'type');
        handleFormSubmission('addMFRForm', 'addMFRModal', '{{ route('admin.builders.store') }}', 'builders_id', 'id',
            'name');
        handleFormSubmission('addScopeForm', 'addScopeModal', '{{ route('admin.scopes.store') }}', 'scopes_id', 'id', 'scope');
    </script>
@endsection
