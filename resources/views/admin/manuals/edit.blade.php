@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: none;
            width: 100%;
            padding-left: 12px;
            padding-right: 12px;
        }
        .fs-7{
            font-size: .7rem;
        }

        .cmm-page {
            height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .cmm-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #editCMMForm {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* Чтобы страница Edit CMM не выходила за границы маленьких экранов:
           ограничиваем высоту карточки и делаем прокрутку внутри. */
        .edit-cmm-card-body{
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            width: 100%;
        }

        .edit-cmm-form-actions{
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: rgba(18, 18, 18, .92);
            backdrop-filter: blur(6px);
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .edit-cmm-layout{
            display: grid;
            grid-template-columns:
                minmax(0, calc((85% - 2rem) / 3))
                minmax(0, calc((85% - 2rem) / 3))
                minmax(0, calc((85% - 2rem) / 3))
                minmax(0, 15%);
            gap: 1rem;
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            align-items: stretch;
            width: 100%;
            max-width: 100%;
        }

        .edit-cmm-main{
            display: contents;
        }

        .edit-cmm-main-panel{
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: .5rem;
            padding: 1rem;
            min-width: 0;
            min-height: 0;
        }

        .edit-cmm-main-panel-compact{
            max-width: none;
        }

        .edit-cmm-units-wrap{
            display: flex;
            flex-direction: column;
            align-self: stretch;
            min-width: 0;
            max-width: none;
            height: 100%;
            min-height: 0;
            border-left: 1px solid rgba(255, 255, 255, .08);
            padding-left: 14px;
            overflow: hidden;
        }

        .edit-cmm-units-panel{
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
        }

        .edit-cmm-units-panel .input-group{
            flex-wrap: nowrap;
        }

        .edit-cmm-units-panel .unit-field > .form-control:first-child{
            flex: 0 1 42%;
            width: auto !important;
        }

        .edit-cmm-units-panel .unit-field > .form-control.unit-name-input{
            flex: 1 1 auto;
            width: auto !important;
        }

        .edit-cmm-units-panel .removeUnitField{
            flex: 0 0 auto;
        }

        .edit-cmm-units-panel .form-control{
            min-width: 0;
        }

        .edit-cmm-access-wrap{
            min-width: 0;
            max-width: none;
            display: flex;
            flex-direction: column;
            align-self: stretch;
            min-height: 0;
            border-left: 1px solid rgba(255, 255, 255, .08);
            padding-left: 14px;
            overflow: hidden;
        }

        .edit-cmm-access-list{
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 0;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 8px 10px;
        }

        .section-title {
            font-size: .82rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .55);
            margin: .25rem 0 .5rem;
        }

        .form-label {
            margin-bottom: .25rem;
        }

        .inline-add {
            white-space: nowrap;
            padding-left: .5rem;
            padding-right: .5rem;
        }

        .edit-cmm-access-list label {
            min-width: 0;
        }

        .edit-cmm-access-list .text-truncate {
            display: inline-block;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <div class="container mt-1 cmm-page">
        <div class="card bg-gradient cmm-card">
            <div class="card-header">
                <h5 class="text-info mb-0">{{ __('Edit CMM:') }} {{ $cmm->number }}</h5>
            </div>

            <div class="card-body edit-cmm-card-body">
                <form method="POST"
                      action="{{ route('manuals.update', [ 'manual' => $cmm->id] ) }}"
                      enctype="multipart/form-data"   id="editCMMForm">
                    @csrf
                    @method('PUT')

                    <div class="edit-cmm-layout">
                        <div class="edit-cmm-main">
                        <div class="edit-cmm-main-panel">
                            <div>
                                <label for="cmm_num" class="form-label">{{ __('CMM Number') }}</label>
                                <input id='cmm_num' type="text"
                                       class="form-control" name="number" value="{{ old('number', $cmm->number) }}"
                                       required>
                                @error('number')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mt-2">
                                <label for="title" class="form-label">{{ __('Description') }}</label>
                                <input id='title' type="text" class="form-control" name="title"
                                       value="{{ old('title', $cmm->title) }}" required>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('Image:')}}</strong>
                                    <input type="file" name="img" class="form-control" placeholder="Image">
                                </div>
                            </div>

                            <div class="mt-2">
                                <label for="revision_number" class="form-label">{{ __('Revision Number') }}</label>
                                <input id="revision_number" type="text" class="form-control" name="revision_number"
                                       maxlength="255" value="{{ old('revision_number', $cmm->revision_number) }}">
                            </div>
                            <div class="mt-2">
                                <label for="revision_date" class="form-label">{{ __('Revision Date') }}</label>
                                <input id='revision_date' type="date" class="form-control" name="revision_date"
                                       value="{{ old('revision_date', $cmm->revision_date) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name" class="form-label">{{ __('Units PN') }}</label>
                                <input id='unit_name' type="text" class="form-control"
                                       name="unit_name"
                                       value="{{ old ('unit_name', $cmm->unit_name) }}" required>
                            </div>
                            <div class="mt-2">
                                <label for="unit_name_training" class="form-label">{{ __('Units Training PN') }}</label>
                                <input id='unit_name_training' type="text" class="form-control"
                                       name="unit_name_training"
                                       value="{{ old ('unit_name_training', $cmm->unit_name_training) }}" required>
                            </div>
                            <div class="mt-2 mb-2">
                                <label for="training_hours" class="form-label">{{ __('Unit First Training Hours') }}</label>
                                <input id='training_hours' type="text"
                                       class="form-control"
                                       name="training_hours"
                                       value="{{ old('training_hours', $cmm->training_hours) }}" required>
                            </div>
                        </div>
                        <div class="edit-cmm-main-panel edit-cmm-main-panel-compact">
                            <div class="mb-1">
                                <label for="ovh_life" class="form-label">{{ __('Overhaul Life') }}</label>
                                <input id='ovh_life' type="text"
                                       class="form-control"
                                       name="ovh_life"
                                       value="{{ old('ovh_life', $cmm->ovh_life) }}" required>
                            </div>
                            <div class="mb-1">
                                <label for="reg_sb" class="form-label">{{ __('Inspection Req.SB') }}</label>
                                <input id='reg_sb' type="text"
                                       class="form-control"
                                       name="reg_sb"
                                       value="{{ old('reg_sb', $cmm->reg_sb) }}" required>
                            </div>
                            <div class="form-group ">
                                {{-- multi: COMPONENTS-style picker — Add opens a modal, picked
                                     planes render below as chip rows with × (plane-picker partial). --}}
                                @php $cmmPlaneIds = array_values(array_map('intval', (array) old('planes', $cmm->planes->pluck('id')->all() ?: [$cmm->planes_id]))); @endphp
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <label class="form-label mb-0">{{ __('AirCraft Type') }}</label>
                                    <button type="button" class="btn btn-outline-info btn-sm py-0 px-2 ms-auto" data-plane-picker="#planes_id">
                                        {{ __('Add') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm py-0 px-2 inline-add" data-bs-toggle="modal"
                                            data-bs-target="#addAirCraftModal"
                                            title="{{ __('Create a NEW aircraft type in the dictionary') }}">+ {{ __('New type') }}</button>
                                </div>
                                <div id="planes_id" class="plane-chip-box d-flex flex-column gap-1"
                                     data-init='@json($cmmPlaneIds)'></div>
                            </div>

                            <div class="form-group mt-3">
                                <label for="builders_id" class="form-label">{{ __('MFR') }}</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <select id="builders_id" name="builders_id" class="form-select" required>
                                        <option value="">{{ __('Select MFR') }}</option>
                                        @foreach ($builders as $builder)
                                            <option value="{{ $builder->id }}" {{ (string) old('builders_id', $cmm->builders_id) === (string) $builder->id ? 'selected' : '' }}>{{ $builder->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-info inline-add" data-bs-toggle="modal"
                                            data-bs-target="#addMFRModal">+ {{ __('Add') }}</button>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label for="scopes_id" class="form-label">{{ __('Scope') }}</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <select id="scopes_id" name="scopes_id" class="form-select" required>
                                        <option value="">{{ __('Select Scope') }}</option>
                                        @foreach ($scopes as $scope)
                                            <option value="{{ $scope->id }}" {{ (string) old('scopes_id', $cmm->scopes_id) === (string) $scope->id ? 'selected' : '' }}>{{ $scope->scope }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-info inline-add" data-bs-toggle="modal"
                                            data-bs-target="#addScopeModal">+ {{ __('Add') }}</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="lib" class="form-label">{{ __('Library Number') }}</label>
                                <input id='lib' type="text" class="form-control" name="lib"
                                       value="{{ old('lib', $cmm->lib) }}" required>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                <div class="form-group">
                                    <strong>{{__('Log Card Image:')}}</strong>
                                    <input type="file" name="log_img" class="form-control" placeholder="Image">
                                </div>
                            </div>

                        </div>

                        <div class="edit-cmm-units-wrap">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="section-title mb-0">{{ __('Components') }}</div>
                                <button class="btn btn-outline-primary btn-sm" type="button" id="addUnitField">Add</button>
                            </div>
                            <div id="unitInputs" class="edit-cmm-units-panel">
                                @php
                                    $oldUnits = old('units');
                                    $oldUnitNames = old('unit_names');
                                @endphp
                                @if(is_array($oldUnits))
                                    @foreach($oldUnits as $index => $oldUnitPartNumber)
                                        <div class="input-group mb-2 d-flex unit-field">
                                            <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                                   name="units[]" value="{{ $oldUnitPartNumber }}" required>
                                            <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" style="width: 180px"
                                                   name="unit_names[]" value="{{ $oldUnitNames[$index] ?? old('title', $cmm->title) }}"
                                                   data-user-edited="1">
                                            <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="Remove component">&times;</button>
                                        </div>
                                    @endforeach
                                @elseif($cmm->units && $cmm->units->count() > 0)
                                    @foreach($cmm->units as $unit)
                                        <div class="input-group mb-2 d-flex unit-field">
                                            <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                                   name="units[]" value="{{ $unit->part_number }}" required>
                                            <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" style="width: 180px"
                                                   name="unit_names[]" value="{{ $unit->name }}" data-user-edited="1">
                                            <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="Remove component">&times;</button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 unit-field">
                                        <input type="text" class="form-control" placeholder="Enter Unit PN" style="width: 130px"
                                               name="units[]" required>
                                        <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" style="width: 180px"
                                               name="unit_names[]" value="{{ old('title', $cmm->title) }}">
                                        <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="Remove component">&times;</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        </div>

                        @if(auth()->user()?->roleIs('Admin'))
                            <div class="edit-cmm-access-wrap">
                                <div class="section-title mb-2">{{ __('Users with access') }}</div>
                                <div class="mt-1 d-flex flex-column flex-grow-1">
                                    <div class="edit-cmm-access-list small">
                                        @foreach(($users ?? collect()) as $u)
                                            @php
                                                $selectedUserIds = collect(old('permitted_user_ids', $permittedUserIds ?? []))
                                                    ->map(fn ($id) => (int) $id)
                                                    ->all();
                                                $checked = in_array((int) $u->id, $selectedUserIds, true);
                                            @endphp
                                            <label class="d-flex align-items-center gap-2" style="white-space: nowrap; cursor: pointer; margin-bottom: 6px;">
                                                <input
                                                    id="permitted_user_{{ $u->id }}"
                                                    type="checkbox"
                                                    name="permitted_user_ids[]"
                                                    value="{{ $u->id }}"
                                                    {{ $checked ? 'checked' : '' }}
                                                >
                                                <span class="text-truncate">
                                                    {{ $u->selection_name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-2 fs-7">
                                        {{ __('By default, users have no permissions. Admin selects allowed users here.') }}
                                    </small>
                                </div>
                            </div>
                        @endif

                    </div>

                    <div class="edit-cmm-form-actions">
                        <div class="d-flex justify-content-end gap-2 py-2">
                            <button type="submit" class="btn btn-outline-primary text-center ">
                                {{ __('UpDate') }}
                            </button>
                            <a href="{{ route('manuals.index') }}" class="btn btn-outline-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
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
        function syncManualUnitNamesFromDescription() {
            const manualDescription = (document.getElementById('title')?.value || '').trim();
            document.querySelectorAll('#unitInputs .unit-name-input').forEach((input) => {
                if (input.dataset.userEdited !== '1') {
                    input.value = manualDescription;
                }
            });
        }
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
                        if (select.tagName !== 'SELECT') {
                            // chip-контейнер мульти-Plane: новый тип — в справочник и сразу в набор
                            if (window.PLANE_TYPES) window.PLANE_TYPES[data[dataKey]] = data[dataValue];
                            window.planeChipsAdd?.(select, data[dataKey], data[dataValue]);
                        } else {
                            let option = document.createElement('option');
                            option.value = data[dataKey];
                            option.text = data[dataValue];
                            select.add(option);
                        }

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

                if (partNumberInput && partNumberInput.value.trim() === '') {
                    // Если part_number пустой, удаляем весь блок
                    field.remove();
                }
            });
        });

        // Функциональность для управления полями units
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('addUnitField').addEventListener('click', function () {
                const newUnitField = document.createElement('div');
                newUnitField.className = 'input-group mb-2 unit-field';
                newUnitField.innerHTML = `
                    <input type="text" class="form-control" placeholder="Enter Unit Part Number" style="width: 130px" name="units[]" required>
                    <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" style="width: 180px" name="unit_names[]">
                    <button class="btn btn-outline-danger removeUnitField" type="button">Remove</button>
                `;
                document.getElementById('unitInputs').appendChild(newUnitField);
                syncManualUnitNamesFromDescription();
            });

            document.getElementById('unitInputs').addEventListener('click', function (event) {
                if (event.target.classList.contains('removeUnitField')) {
                    event.target.parentElement.remove();
                }
            });

            document.getElementById('title')?.addEventListener('input', syncManualUnitNamesFromDescription);

            document.getElementById('unitInputs').addEventListener('input', function (event) {
                if (event.target.classList.contains('unit-name-input')) {
                    event.target.dataset.userEdited = '1';
                }
            });

            syncManualUnitNamesFromDescription();
        });
    </script>
    @include('admin.manuals.partials.plane-picker')
@endsection
