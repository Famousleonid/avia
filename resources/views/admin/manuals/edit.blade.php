{{--@extends('layouts.main_dlb')--}}

@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 650px;
        }

    </style>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5><strong>{{__('Edit CMM:')}}</strong> {{ $cmm->number }}</h5>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('manuals.update', $cmm->id) }}" enctype="multipart/form-data"
                      id="editCMMForm">
                    @csrf
                    @method('PUT')

                    <div class="form-group d-flex ">
                        <div class="mt-2 m-3 border p-2">
                            <div>
                                <label for="cmm_num">{{ __('CMM Number')
                                }}</label>
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
                                    <input type="file" name="img" class="form-control" placeholder="изображение">
                                    <small>{{__('Leave blank if you do not want to change the image.')}}</small>
                                </div>
                            </div>

                            <div class="">
                                <label for="revision_date">{{ __('Revision Date') }}</label>
                                <input id='revision_date' type="date" class="form-control" name="revision_date"
                                       value="{{ old('revision_date', $cmm->revision_date) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="units_pn">{{ __('Units PN') }}</label>
                                <input id='units_pn' type="text" class="form-control" name="units_pn" value="{{ old
                                ('units_pn',
                                $cmm->units_pn) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="units_tr">{{ __('Unit First
                                Training')
                                }}</label>
                                <input id='units_tr' type="text"
                                       class="form-control" name="units_tr"
                                       value="{{ old('units_tr', $cmm->units_tr) }}" required>
                            </div>
                        </div>
                        <div style="width: 300px" class="m-3 p-2 border">
                            <div class="form-group ">
                                <label for="planes_id">{{ __('AirCraft Type')
                            }}</label>
                                <select id="planes_id" name="planes_id" class="form-control" required>
                                    <option value="">{{ __('Select AirCraft') }}</option>
                                    @foreach ($planes as $plane)
                                        <option value="{{ $plane->id }}" {{ $plane->id == $cmm->planes_id ?
                                        'selected' : '' }}>{{ $plane->type }}</option>
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
                                        <option value="{{ $builder->id }}" {{ $builder->id == $cmm->builders_id ?
                                        'selected' : '' }}>{{ $builder->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                        data-bs-target="#addMFRModal">{{ __('Add MFR') }}</button>
                            </div>

                            <div class="form-group mt-2">
                                <label for="scopes_id">{{ __('Scope') }}</label>
                                <select id="scopes_id" name="scopes_id" class="form-control" required>
                                    <option value="">{{ __('Select Scope')
                                    }}</option>
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

                    <button type="submit" class="btn btn-primary text-center ">
                        {{ __('UpDate') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления самолета -->
    <div class="modal fade" id="addAirCraftModal" tabindex="-1" aria-labelledby="addAirCraftModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAirCraftModalLabel">{{ __
                    ('Add AirCraft') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addAirCraftForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="airCraftName">{{ __('Type AirCraft')
                            }}</label>
                            <input type="text" class="form-control" id="airCraftName" name="type" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        <button type="submit" class="btn btn-primary">{{ __
                        ('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления MFR -->
    <div class="modal fade" id="addMFRModal" tabindex="-1" aria-labelledby="addMFRModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMFRModalLabel">{{ __('Add
                    MFR') }}</h5>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        <button type="submit" class="btn btn-primary">{{ __
                        ('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления Scope -->
    <div class="modal fade" id="addScopeModal" tabindex="-1" aria-labelledby="addScopeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScopeModalLabel">{{ __('Add
                    Scope') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addScopeForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="scopeName">{{ __('Name Scope')
                            }}</label>
                            <input type="text" class="form-control" id="scopeName" name="scope" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        <button type="submit" class="btn btn-primary">{{ __
                        ('Save') }}</button>
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

        handleFormSubmission('addAirCraftForm', 'addAirCraftModal', '{{ route('planes.store') }}',
            'planes_id', 'id', 'type');
        handleFormSubmission('addMFRForm', 'addMFRModal', '{{ route('builders.store') }}', 'builders_id', 'id',
            'name');
        handleFormSubmission('addScopeForm', 'addScopeModal', '{{ route('scopes.store') }}', 'scopes_id', 'id', 'scope');
    </script>
@endsection
