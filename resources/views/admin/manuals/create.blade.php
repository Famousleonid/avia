@extends('layouts.main_dlb')

@section('content')
    <style>
        .container {
            max-width: 600px;
        }

        .push-top {
            margin-top: 50px;
        }
    </style>

    <div class="container">
        <div class="card">
            <div class="card-header">
                Создать новый CMM
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.manuals.store') }}" enctype="multipart/form-data"
                      id="createCMMForm">
                    @csrf

                    <div class="form-group d-flex">
                        <div class="mt-2 m-3  p-2">
                            <div>
                                <label for="wo">{{ __('Номер CMM') }}</label>
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
                                    <input type="file" name="img" class="form-control" placeholder="изображение">
                                </div>
                            </div>

                            <div class="mt-1">
                                <label for="revision_date">{{ __('Revision Date') }}</label>
                                <input id='revision_date' type="date" class="form-control" name="revision_date"
                                       required>
                            </div>
                            <div class="mt-2">
                                <label for="units_pn">{{ __('Units PN') }}</label>
                                <input id='units_pn' type="text" class="form-control" name="units_pn" required>
                            </div>
                            <div class=mt-2">
                                <label for="units_tr">{{ __('Unit First
                                Training')
                                }}</label>
                                <input id='units_tr' type="text"
                                       class="form-control" name="units_tr"
                                       required>
                            </div>
                        </div>
                        <div style="width: 320px" class="m-3 p-2 ">
                            <div class="form-group ">
                                <label for="planes_id">{{ __('AirCraft Type')
                            }}</label>
                                <select id="planes_id" name="planes_id" class="form-control" required>
                                    <option value="">{{ __('Select AirCraft')
                                }}</option>
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

                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary mt-3">
                        {{ __('Add CMM') }}
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
                    <h5 class="modal-title" id="addAirCraftModalLabel">{{ __('Add AirCraft') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST" id="addAirCraftForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="planeName">{{ __('AirCraft Type') }}</label>
                            <input type="text" class="form-control" id="planeName" name="type" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
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
                    <h5 class="modal-title" id="addMFRModalLabel">{{ __('Add MFR') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addMFRForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="builderName">{{ __('Название MFR') }}</label>
                            <input type="text" class="form-control" id="builderName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
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
                    <h5 class="modal-title" id="addScopeModalLabel">{{ __('Add Scope') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addScopeForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="scopeName">{{ __('Scope') }}</label>
                            <input type="text" class="form-control" id="scopeName" name="scope" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
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
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        // Добавляем новый элемент в Select
                        let select = document.getElementById(selectId);
                        let option = document.createElement('option');
                        option.value = data[dataKey];
                        option.text = data[dataValue];
                        option.selected = true; // Автоматически выбираем новый элемент
                        select.add(option);

                        // Закрываем модальное окно
                        let modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                        modal.hide();

                        // Сброс формы
                        document.getElementById(formId).reset();
                        this.submitted = false;
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        this.submitted = false;
                    });
            });
        }

        // Обновляем вызовы функции для передачи правильных ID модальных окон
        handleFormSubmission('addAirCraftForm', '{{ route('admin.planes.store') }}', 'planes_id', 'id', 'type',
            'addAirCraftModal');
        handleFormSubmission('addMFRForm', '{{ route('admin.builders.store') }}', 'builders_id', 'id', 'name',
            'addMFRModal');
        handleFormSubmission('addScopeForm', '{{ route('admin.scopes.store') }}', 'scopes_id', 'id', 'scope', 'addScopeModal');

    </script>
@endsection
