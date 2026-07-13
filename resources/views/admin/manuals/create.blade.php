@extends('admin.master')

@section('content')

    <style>
        .container {
            max-width: 1180px;
        }

        /* page fits viewport */
        .cmm-page {
            height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .cmm-row{
            height: 100%;
        }
        .cmm-row > [class*="col-"]{
            min-height: 0;
        }
        .cmm-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* ВАЖНО: форма тоже flex, чтобы body растягивался */
        #createCMMForm {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* body занимает всё доступное место, БЕЗ общего скролла */
        .cmm-card .card-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden; /* было overflow:auto; */
        }

        /* чтобы row растягивался по высоте */
        .cmm-card .card-body > .row {
            height: 100%;
            align-items: stretch;
        }

        /* sticky footer */
        .cmm-footer {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: rgba(18, 18, 18, .92);
            backdrop-filter: blur(6px);
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        /* compact + aligned */
        .form-label {
            margin-bottom: .25rem;
        }

        .form-control, .form-select {
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        .inline-add {
            white-space: nowrap;
            padding-left: .5rem;
            padding-right: .5rem;
        }

        /* Right column sections */
        .right-panel {
            border-left: 1px solid rgba(255, 255, 255, .08);
            padding-left: 14px;

            /* ВАЖНО: делаем колонку flex */
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        @media (max-width: 991.98px) {
            .right-panel {
                border-left: 0;
                padding-left: 0;
                border-top: 1px solid rgba(255, 255, 255, .08);
                padding-top: 14px;
            }
        }

        .section-title {
            font-size: .85rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .55);
            margin: .25rem 0 .5rem;
        }

        /* Верх справа (файлы) — фиксированная часть */
        .right-top {
            flex: 0 0 auto;
        }

        /* Низ справа (Units) — тянется и скроллится */
        .units-panel {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;

            padding-right: 6px; /* чтобы скролл не “лип” к контенту */
            padding-bottom: 70px; /* подбери под высоту footer */
            -webkit-overflow-scrolling: touch;
        }

        /* чтобы input-group не распирал */
        .units-panel .input-group {
            flex-wrap: nowrap;
        }

        .units-panel .form-control {
            min-width: 0;
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
                    <div class="row g-3 cmm-row">

                        {{-- LEFT: main fields --}}
                        <div class="col-12 col-lg-8">
                            <div class="row g-3">

                                <div class="col-12 col-md-6">
                                    <label for="wo" class="form-label">{{ __('CMM No:') }}</label>
                                    <input id="wo" type="text" class="form-control" name="number" value="{{ old('number') }}" required>
                                    @error('number')
                                    <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="lib" class="form-label">{{ __('Library No:') }}</label>
                                    <input id="lib" type="text" class="form-control" name="lib" value="{{ old('lib') }}" required>

                                </div>

                                <div class="col-12">
                                    <label for="title" class="form-label">{{ __('Description') }}</label>
                                    <input id="title" type="text" class="form-control" name="title" value="{{ old('title') }}" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="unit_name" class="form-label">{{ __('Component Part No:') }}</label>
                                    <input id="unit_name" type="text" class="form-control" name="unit_name" value="{{ old('unit_name') }}" required>
                                </div>

                                {{-- Select rows with "Add" button on the right --}}
                                <div class="col-12 col-md-6">
                                    {{-- multi: a CMM may apply to several planes of one builder.
                                         COMPONENTS-style picker: Add opens a modal, picked planes
                                         render below as chip rows with × (see plane-picker partial). --}}
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <label class="form-label mb-0">{{ __('AirCraft Type') }}</label>
                                        <button type="button" class="btn btn-outline-info btn-sm py-0 px-2 ms-auto" data-plane-picker="#planes_id">
                                            {{ __('Add') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-info btn-sm py-0 px-2 inline-add"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addAirCraftModal"
                                                title="{{ __('Create a NEW aircraft type in the dictionary') }}">
                                            + {{ __('New type') }}
                                        </button>
                                    </div>
                                    <div id="planes_id" class="plane-chip-box d-flex flex-column gap-1"
                                         data-init='@json(array_values(array_map("intval", (array) old("planes", []))))'></div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="unit_name_training" class="form-label">{{ __('Component Training Part No:') }}</label>
                                    <input id="unit_name_training" type="text" class="form-control" name="unit_name_training" value="{{ old('unit_name_training') }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">{{ __('MFR') }}</label>
                                    <div class="d-flex gap-2 align-items-start">
                                        <select id="builders_id" name="builders_id" class="form-select" required>
                                            <option value="">{{ __('Select MFR') }}</option>
                                            @foreach ($builders as $builder)
                                                <option value="{{ $builder->id }}" {{ (string) old('builders_id') === (string) $builder->id ? 'selected' : '' }}>{{ $builder->name }}</option>
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
                                    <input id="training_hours" type="text" class="form-control" name="training_hours" value="{{ old('training_hours') }}">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">{{ __('Scope') }}</label>
                                    <div class="d-flex gap-2 align-items-start">
                                        <select id="scopes_id" name="scopes_id" class="form-select" required>
                                            <option value="">{{ __('Select Scope') }}</option>
                                            @foreach ($scopes as $scope)
                                                <option value="{{ $scope->id }}" {{ (string) old('scopes_id') === (string) $scope->id ? 'selected' : '' }}>{{ $scope->scope }}</option>
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
                                    <input id="ovh_life" type="text" class="form-control" name="ovh_life" value="{{ old('ovh_life') }}">
                                </div>

                                <div class="col-12 col-md-3">
                                    <label for="reg_sb" class="form-label">{{ __('Inspection Req.SB') }}</label>
                                    <input id="reg_sb" type="text" class="form-control" name="reg_sb" value="{{ old('reg_sb') }}">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="revision_date">{{ __('Revision Date') }}</label>
                                    <input id="revision_date"
                                           type="text"
                                           class="form-control "
                                           name="revision_date"
                                           value="{{ old('revision_date') }}"
                                           data-fp

                                           required>
                                </div>

                            </div>
                        </div>

                        {{-- RIGHT: all file inputs on top + Units below --}}
                        <div class="col-12 col-lg-4 right-panel">

                            <div class="right-top">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Image component:') }}</label>
                                    <input type="file" name="img" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('Log Card Image:') }}</label>
                                    <input type="file" name="log_img" class="form-control">
                                </div>

                                <hr class="opacity-25">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="section-title mb-0">Components</div>
                                    <button class="btn btn-outline-primary btn-sm" type="button" id="addUnitField">
                                        Add
                                    </button>
                                </div>
                            </div>

                            <div class="units-panel">
                                <div id="unitInputs">
                                    @php
                                        $oldUnits = old('units');
                                        $oldUnitNames = old('unit_names');
                                    @endphp
                                    @if(is_array($oldUnits) && count($oldUnits) > 0)
                                        @foreach($oldUnits as $index => $oldUnitPartNumber)
                                            <div class="input-group mb-2 unit-field">
                                                <input type="text" class="form-control" placeholder="Enter Unit PN"
                                                       name="units[]" value="{{ $oldUnitPartNumber }}" required>
                                                <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name"
                                                       name="unit_names[]" value="{{ $oldUnitNames[$index] ?? old('title') }}"
                                                       data-user-edited="1">
                                                <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="Remove component">&times;</button>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="input-group mb-2 unit-field">
                                            <input type="text" class="form-control" placeholder="Enter Unit PN"
                                                   name="units[]" required>
                                            <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name"
                                                   name="unit_names[]" value="{{ old('title') }}">
                                            <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="Remove component">&times;</button>
                                        </div>
                                    @endif
                                </div>

                                <small class="text-muted">Add component PN / Name pairs.</small>
                            </div>
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

@endsection

@section('scripts')

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

                        if (select.tagName !== 'SELECT') {
                            // chip-контейнер мульти-Plane: новый тип — в справочник и сразу в набор
                            if (window.PLANE_TYPES) window.PLANE_TYPES[data[dataKey]] = data[dataValue];
                            window.planeChipsAdd?.(select, data[dataKey], data[dataValue]);
                        } else {
                            // Правильное добавление option
                            let newOption = new Option(
                                data[dataValue], // текст
                                data[dataKey],   // value
                                true,
                                true
                            );

                            select.add(newOption);

                            // Уведомляем select что он обновился
                            select.dispatchEvent(new Event('change', {bubbles: true}));

                            // Если используется Select2
                            if (window.jQuery && $(select).data('select2')) {
                                $(select).trigger('change');
                            }
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

            // Обработка отправки формы
            document.getElementById('title')?.addEventListener('input', syncManualUnitNamesFromDescription);

            document.getElementById('unitInputs').addEventListener('input', function (event) {
                if (event.target.classList.contains('unit-name-input')) {
                    event.target.dataset.userEdited = '1';
                }
            });

            syncManualUnitNamesFromDescription();

            document.getElementById('createCMMForm').addEventListener('submit', function (e) {
                // Удаляем пустые поля units перед отправкой
                const unitFields = document.querySelectorAll('.unit-field');
                unitFields.forEach(function (field) {
                    const partNumberInput = field.querySelector('input[name="units[]"]');

                    if (partNumberInput && partNumberInput.value.trim() === '') {
                        // Если part_number пустой, удаляем весь блок
                        field.remove();
                    }
                });

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
    @include('admin.manuals.partials.plane-picker')
@endsection
