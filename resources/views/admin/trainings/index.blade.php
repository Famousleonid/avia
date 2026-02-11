@extends('admin.master')

@php use Carbon\Carbon; @endphp

@section('content')
    <style>


        @media (max-width: 1100px) {
            .table th:nth-child(5), .table td:nth-child(5) {
                display: none;
            }
        }

        @media (max-width: 770px) {
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(4), .table td:nth-child(4),
            .table th:nth-child(5), .table td:nth-child(5) {
                display: none;
            }
        }

        @media (max-width: 590px) {
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(4), .table td:nth-child(4),
            .table th:nth-child(5), .table td:nth-child(5) {
                display: none;
            }

            /* ВАЖНО: 6-й столбец (Actions) НЕ скрываем */
        }

        .actions-cell {
            white-space: nowrap;
        }

        .actions-cell .btn {
            padding: .25rem .5rem;
            line-height: 1.1;
        }

        .actions-cell span {
            display: inline-block;
        }

        /* На очень узких экранах ещё ужмёмся */
        @media (max-width: 576px) {
            .actions-cell .btn {
                padding: .2rem .4rem;
                font-size: .85rem;
            }
        }
    </style>


    <div class="container ">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between flex-wrap align-items-center gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h3 class="mb-0">{{ __('Trainings') }}</h3>
                        @roles("Admin|Manager")
                        @if($canViewAllUsers && $users->isNotEmpty())
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 small text-muted">{{ __('User') }}:</label>
                                <select class="form-select form-select-sm" id="userSelectDropdown" style="min-width: 180px;"
                                        onchange="window.location.href='{{ route('trainings.index') }}?user_id=' + this.value">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                            {{ $u->stamp }} — {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @endroles
                    </div>
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox"
                               id="trainingNotUpdated">
                        <label class="form-check-label"
                               for="trainingNotUpdated">Not updated
                            trainings</label>
                    </div>
{{--                    @admin--}}
{{--                    <div>--}}
{{--                        <a href="{{route('trainings.showAll')}}"  class="btn btn-primary align-middle">{{__('Training All')}}</a>--}}
{{--                    </div>--}}
{{--                    @endadmin--}}
                    <div class="align-middle">
                        <a href="{{ route('trainings.create') }}{{ $canViewAllUsers && $selectedUserId != auth()->id() ? '?user_id=' . $selectedUserId : '' }}"
                           class="btn btn-primary align-middle">
                            {{ __('Add Unit') }}</a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table id="trainingsTable" data-toggle="table"
                       data-search="true" data-pagination="false"
                       data-page-size="5" class="table table-bordered">
                    <thead>
                    <tr>
                        <th data-priority="1" data-visible="true" class="text-center align-middle">{{ __('Component Description')
                        }}</th>
                        <th data-priority="2" data-visible="true" class="text-center align-middle">{{ __('Component PN') }}</th>
                        <th data-priority="3" data-visible="true"
                            class="text-center align-middle">{{ __('First Training Date')}}</th>
                        <th data-priority="4" data-visible="true"
                            class="text-center align-middle">{{ __('Last Training Date') }}</th>
                        <th data-priority="5" data-visible="true" class="text-center align-middle">{{ __('Form 132') }}</th>
                        <th data-priority="6" data-visible="true" class="text-center align-middle">{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($formattedTrainingLists as $trainingList)
                        <tr>
                            <td class="text-center">
                                <a href="" data-bs-toggle="modal"
                                   data-bs-target="#cmmModal{{$trainingList['first_training']->manual->id }}">
                                    {{ $trainingList['first_training']->manual->title ?? 'N/A' }}
                                </a>
                            </td>

                            <td class="text-center">{{
                                $trainingList['first_training']->manual->unit_name_training ?? 'N/A' }}</td>

                            <td class="text-center">
                                {{ isset($trainingList['first_training']) ? Carbon::parse
                                ($trainingList['first_training']->date_training)->format('M-d-Y') : 'N/A' }}
                            </td>

                            <td class="text-center"
                                @if(isset($trainingList['last_training']) && Carbon::parse($trainingList['last_training']->date_training)->diffInDays(Carbon::now()) > 340)
                                    style="color: red"
                                @endif>
                                {{ isset($trainingList['last_training']) ? Carbon::parse
                                ($trainingList['last_training']->date_training)->format('M-d-Y') : 'N/A' }}
                            </td>

                            <td class="text-center">
                                @if(isset($trainingList['first_training']) && $trainingList['first_training']->form_type == 132)
                                    <label>OK</label>
                                @else
                                    <label>No</label>
                                @endif
                            </td>

                            <td class="text-center">
                                <div
                                    class="actions-cell d-inline-flex align-items-center justify-content-center gap-2 flex-nowrap">

{{--                                    <span data-tippy-content="{{ (isset($trainingList['last_training']) && Carbon::parse($trainingList['last_training']->date_training)->diffInDays(Carbon::now()) < 340) ? __('Training is up to date (less than 340 days)') : __('Update') }}">--}}
                                    <span data-tippy-content="{{__('Update') }}">
                                        <button
                                            class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 update-training-btn"
                                            data-manuals-id="{{ $trainingList['first_training']->manuals_id }}"
                                            data-date-training="{{ $trainingList['last_training']->date_training ?? $trainingList['first_training']->date_training }}"
                                            data-manuals-title="{{ $trainingList['first_training']->manual->title ?? 'N/A' }}"

                                            @if(isset($trainingList['last_training']) && Carbon::parse($trainingList['last_training']->date_training)->diffInDays(Carbon::now()) < 340)
                                                disabled
                                            @endif>

                                            <i class="bi bi-check-circle"></i>
{{--                                        <span class="d-none d-sm-inline">{{ __('Update') }}</span>--}}
                                        </button>
                                    </span>

                                    <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#trainingModal{{ $trainingList['first_training']->manuals_id }}"
                                            data-tippy-content="{{ __('View Training Reports') }}"
                                            data-tippy-placement="top">
                                        <i class="bi bi-journal-text"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTrainingModal{{ $trainingList['first_training']->manuals_id }}"
                                            data-tippy-content="{{ __('Edit training dates') }}"
                                            data-tippy-placement="top">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @roles("Admin|Manager")
                                    <button
                                        class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1 delete-training-btn"
                                        data-user-id="{{ $selectedUserId }}"
                                        data-manual-id="{{ $trainingList['first_training']->manuals_id }}"
                                        data-title="{{ $trainingList['first_training']->manual->title ?? 'N/A' }}"
                                        data-tippy-content="{{ __('Delete Training') }}"
                                        data-tippy-placement="top">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endroles
                                </div>
                            </td>
                            <!-- Модальное окно -->
                            <div class="modal fade" id="trainingModal{{ $trainingList['first_training']->manuals_id }}"
                                 tabindex="-1"
                                 aria-labelledby="trainingModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header justify-content-between">
                                            <h5 class="modal-title" id="trainingModalLabel">
                                                Training for {{ $trainingList['first_training']->manual->title }}
                                                <br> PN {{ $trainingList['first_training']->manual->unit_name_training}}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Закрыть"></button>
                                        </div>
                                        <div class="modal-body">
                                            @foreach($trainingList['trainings'] as $training)
                                                <div class="form-group">
                                                    <div class="row d-flex">


                                                        <div class="col">
                                                            <label>{{ Carbon::parse($training->date_training)->format('M.d.Y') }}
                                                                (Form: {{ $training->form_type }} )
                                                            </label>
                                                        </div>
                                                        <div class="col">
                                                            @if($training->form_type == '112')
                                                                <a href="{{ route('trainings.form112', ['id'=> $training->id, 'showImage' => 'false']) }}"
                                                                   class="btn btn-success mb-1 formLink " target="_blank"
                                                                   id="formLink{{ $trainingList['first_training']->manuals_id }}">
                                                                    View/Print Form 112
                                                                </a>
                                                            @elseif($training->form_type == '132')
                                                                <a href="{{ route('trainings.form132', ['id' => $training->id, 'showImage' => 'false']) }}"
                                                                   class="btn  btn-info mb-1 formLink " target="_blank"
                                                                   id="formLink{{ $trainingList['first_training']->manuals_id }}">
                                                                    View/Print Form 132
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="modal-footer">
{{--                                            @if(Auth::user()->role !== null && Auth::user()->role->name !== 'Technician')--}}
                                            @roles("Admin|Team Leader|Manager|Shop Certifying Authority (SCA)")
                                                <div class="form-check ">
                                                    <input type="checkbox" class="form-check-input"
                                                           id="showImage{{ $trainingList['first_training']->manuals_id }}">
                                                    <label
                                                        class="form-check-label"
                                                        for="showImage{{ $trainingList['first_training']->manuals_id }}">
                                                        {{__('Sign In')}}
                                                    </label>
                                                </div>
{{--                                            @endif--}}
                                            @endroles
                                            <button type="button" class="btn btn-secondary ms-5" data-bs-dismiss="modal">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Модальное окно Edit: редактирование дат тренировок -->
                            <div class="modal fade" id="editTrainingModal{{ $trainingList['first_training']->manuals_id }}" tabindex="-1"
                                 aria-labelledby="editTrainingModalLabel{{ $trainingList['first_training']->manuals_id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editTrainingModalLabel{{ $trainingList['first_training']->manuals_id }}">
                                                {{ __('Edit training dates') }} — {{ $trainingList['first_training']->manual->title ?? 'N/A' }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @foreach($trainingList['trainings'] as $training)
                                                <div class="row mb-2 align-items-center edit-training-row"
                                                     data-training-id="{{ $training->id }}"
                                                     data-original-date="{{ \Carbon\Carbon::parse($training->date_training)->format('Y-m-d') }}">
                                                    <div class="col-auto">
                                                        <span class="text-muted">{{ __('Form') }} {{ $training->form_type }}</span>
                                                    </div>
                                                    <div class="col">
                                                        <input type="date" class="form-control form-control-sm edit-training-date-input"
                                                               value="{{ \Carbon\Carbon::parse($training->date_training)->format('Y-m-d') }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        @if($training->form_type == '112')
                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-sm delete-training-date-btn"
                                                                    data-training-id="{{ $training->id }}">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                            <hr class="my-3">
                                            <div class="row mb-2 align-items-center add-training-row" data-manuals-id="{{ $trainingList['first_training']->manuals_id }}">
                                                <div class="col-auto">
                                                    <span class="text-muted">{{ __('Add training') }}</span>
                                                </div>
                                                <div class="col">
                                                    <input type="date" class="form-control form-control-sm add-training-date-input" placeholder="">
                                                </div>
                                                <div class="col-auto">
                                                    <button type="button" class="btn btn-outline-success btn-sm add-training-in-edit-btn">
                                                        <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                            <button type="button" class="btn btn-primary edit-training-save-btn"
                                                    data-modal-id="editTrainingModal{{ $trainingList['first_training']->manuals_id }}">
                                                {{ __('Save') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{--    <!-- Модальное окно для просмотра деталей CMM -->--}}

                            <div class="modal fade" id="cmmModal{{$trainingList['first_training']->manual->id }}"
                                 tabindex="-1" role="dialog"
                                 aria-labelledby="cmmModalLabel{{$trainingList['first_training']->manual->id }}"
                                 aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-gradient">
                                            <div>
                                                <h5 class="modal-title"
                                                    id="imageModalLabel{{ $trainingList['first_training']->manual->id }}">
                                                    {{ $trainingList['first_training']->manual->title }}{{__(': ')}}
                                                </h5>
                                                <h6>{{$trainingList['first_training']->manual->unit_name_training }}</h6>
                                            </div>
                                            <button type="button" class="btn-close pb-2" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body bg-white">
                                            <div class="d-flex bg-white">
                                                <div class="me-2">
                                                    @if($trainingList['first_training']->manual->getFirstMediaBigUrl('manuals'))
                                                        <img
                                                            src="{{$trainingList['first_training']->manual->getFirstMediaBigUrl('manuals') }}"
                                                            style="width: 200px;"
                                                            alt="{{ $trainingList['first_training']->manual->title }}">
                                                    @else
                                                        <p>No image available</p>
                                                    @endif

                                                </div>
                                                <div class="bg-white text-black">
                                                    <p>
                                                        <strong>{{ __('CMM:') }}</strong> {{ $trainingList['first_training']->manual->number }}
                                                    </p>
                                                    <p><strong>{{ __('Description:') }}</strong>
                                                        {{ $trainingList['first_training']->manual->title }}</p>
                                                    <p>
                                                        <strong>{{ __('Revision Date:')}}</strong> {{ $trainingList['first_training']->manual->revision_date }}
                                                    </p>
                                                    <p><strong>{{ __('AirCraft Type:')}}</strong>
                                                        {{ $planes[$trainingList['first_training']->manual->planes_id] ?? 'N/A' }}
                                                    </p>
                                                    <p>
                                                        <strong>{{ __('MFR:') }}</strong> {{$builders[$trainingList['first_training']->manual->builders_id] ?? 'N/A' }}
                                                    </p>
                                                    <p>
                                                        <strong>{{ __('Scope:') }}</strong> {{$scopes[$trainingList['first_training']->manual->scopes_id] ?? 'N/A' }}
                                                    </p>
                                                    <p>
                                                        <strong>{{ __('Library:') }}</strong> {{$trainingList['first_training']->manual->lib }}
                                                    </p>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>


                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal для подтверждения удаления -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete ALL training records for <span id="manualTitle"></span>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Update — одна дата нового тренинга -->
    <div class="modal fade" id="updateTrainingModal" tabindex="-1" aria-labelledby="updateTrainingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateTrainingModalLabel">{{ __('Update training') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="updateTrainingModalSubtitle"></p>
                    <label class="form-label">{{ __('Training date') }}</label>
                    <input type="date" id="updateTrainingDateInput" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="updateTrainingConfirmBtn">{{ __('Add training') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const selectedUserId = {{ $selectedUserId }};
        const authUserId = {{ auth()->id() }};
        const canEditOtherUser = {{ $canViewAllUsers ? 'true' : 'false' }};

        function handleUpdateTraining(manualsId, dateTraining, manualsTitle) {
            document.getElementById('updateTrainingModalLabel').textContent = '{{ __("Update training") }}: ' + manualsTitle;
            document.getElementById('updateTrainingModalSubtitle').textContent = '';

            const inputEl = document.getElementById('updateTrainingDateInput');
            const today = new Date();
            inputEl.value = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

            const modal = new bootstrap.Modal(document.getElementById('updateTrainingModal'));
            modal.show();

            document.getElementById('updateTrainingConfirmBtn').onclick = function () {
                const dateYmd = inputEl.value;
                if (!dateYmd) {
                    alert('{{ __("Please select a date.") }}');
                    return;
                }

                const trainingData = {
                    manuals_id: [manualsId],
                    date_training: [dateYmd],
                    form_type: ['112']
                };
                if (canEditOtherUser && selectedUserId !== authUserId) {
                    trainingData.user_id = selectedUserId;
                }

                fetch('{{ route('trainings.createTraining') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(trainingData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = '{{ __("Training added.") }}';
                            if (data.created !== undefined && data.created > 0) {
                                message = '{{ __("Created") }}: ' + data.created;
                            }
                            if (data.skipped > 0) {
                                message += ' ({{ __("already exists") }})';
                            }
                            alert(message);
                            modal.hide();
                            location.reload();
                        } else {
                            alert('{{ __("Error") }}: ' + (data.message || ''));
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        alert('{{ __("An error occurred") }}: ' + error.message);
                    });
            };
        }

        // Обработчик для кнопки Update
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.update-training-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (this.disabled) return;
                    const manualsId = this.getAttribute('data-manuals-id');
                    const dateTraining = this.getAttribute('data-date-training');
                    const manualsTitle = this.getAttribute('data-manuals-title');
                    handleUpdateTraining(manualsId, dateTraining, manualsTitle);
                });
            });
        });


        // Edit training dates: Save — PATCH изменённых дат
        document.addEventListener('DOMContentLoaded', function () {
            const baseUrl = '{{ url("trainings") }}';
            const csrfToken = '{{ csrf_token() }}';

            // Сохранение изменённых дат
            document.querySelectorAll('.edit-training-save-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const modalId = this.getAttribute('data-modal-id');
                    const modalEl = document.getElementById(modalId);
                    if (!modalEl) return;

                    const rows = modalEl.querySelectorAll('.edit-training-row');
                    const updates = [];
                    rows.forEach(function (row) {
                        const trainingId = row.getAttribute('data-training-id');
                        const originalDate = row.getAttribute('data-original-date');
                        const input = row.querySelector('.edit-training-date-input');
                        if (!input || !trainingId) return;
                        const newDate = input.value.trim();
                        if (newDate && newDate !== originalDate) {
                            updates.push({ id: trainingId, date_training: newDate });
                        }
                    });

                    if (updates.length === 0) {
                        bootstrap.Modal.getInstance(modalEl).hide();
                        return;
                    }

                    let done = 0;
                    let errors = [];

                    updates.forEach(function (u) {
                        fetch(baseUrl + '/' + u.id, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ date_training: u.date_training })
                        })
                            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                            .then(function (result) {
                                done++;
                                if (!result.ok) {
                                    errors.push(result.data.message || 'ID ' + u.id);
                                }
                                if (done === updates.length) {
                                    bootstrap.Modal.getInstance(modalEl).hide();
                                    if (errors.length > 0) {
                                        alert('{{ __("Error") }}: ' + errors.join(', '));
                                    } else {
                                        alert('{{ __("Training dates updated.") }}');
                                        location.reload();
                                    }
                                }
                            })
                            .catch(function (err) {
                                done++;
                                errors.push(err.message);
                                if (done === updates.length) {
                                    bootstrap.Modal.getInstance(modalEl).hide();
                                    alert('{{ __("Error") }}: ' + errors.join(', '));
                                }
                            });
                    });
                });
            });

            // Добавление тренинга в модальном окне Edit
            document.querySelectorAll('.add-training-in-edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = this.closest('.add-training-row');
                    if (!row) return;
                    const manualsId = row.getAttribute('data-manuals-id');
                    const input = row.querySelector('.add-training-date-input');
                    if (!input || !manualsId) return;

                    const dateYmd = input.value.trim();
                    if (!dateYmd) {
                        alert('{{ __("Please select a date.") }}');
                        return;
                    }

                    const trainingData = {
                        manuals_id: [manualsId],
                        date_training: [dateYmd],
                        form_type: ['112']
                    };
                    if (canEditOtherUser && selectedUserId !== authUserId) {
                        trainingData.user_id = selectedUserId;
                    }

                    fetch('{{ route('trainings.createTraining') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(trainingData)
                    })
                        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                        .then(function (result) {
                            if (result.ok && result.data.success) {
                                alert(result.data.message || '{{ __("Training added.") }}');
                                location.reload();
                            } else {
                                alert('{{ __("Error") }}: ' + (result.data.message || ''));
                            }
                        })
                        .catch(function (err) {
                            alert('{{ __("An error occurred") }}: ' + err.message);
                        });
                });
            });

            // Удаление отдельной даты тренинга (форма 112)
            document.querySelectorAll('.delete-training-date-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const trainingId = this.getAttribute('data-training-id');
                    if (!trainingId) return;

                    if (!confirm('{{ __("Are you sure you want to delete this training date?") }}')) {
                        return;
                    }

                    fetch(baseUrl + '/' + trainingId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                        .then(function (result) {
                            if (!result.ok) {
                                alert('{{ __("Error") }}: ' + (result.data.message || ''));
                                return;
                            }
                            alert(result.data.message || '{{ __("Training date deleted.") }}');
                            location.reload();
                        })
                        .catch(function (err) {
                            alert('{{ __("Error") }}: ' + err.message);
                        });
                });
            });
        });

        function getWeekNumber(d) {
            const oneJan = new Date(d.getFullYear(), 0, 1);
            const numberOfDays = Math.floor((d - oneJan) / (24 * 60 * 60 * 1000));
            return Math.ceil((numberOfDays + oneJan.getDay() + 1) / 7);
        }

        function getDateFromWeekAndYear(week, year) {
            const firstJan = new Date(year, 0, 1);
            const days = (week - 1) * 7 - firstJan.getDay() + 1;
            return new Date(year, 0, 1 + days);
        }


        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const showImage = this.checked ? 'true' : 'false';  // Получаем значение параметра showImage
                // const manualsId = this.id.replace('showImage', ''); // Получаем manuals_id из id чекбокса
                const formLinks = document.querySelectorAll(`.formLink`); // Находим все ссылки на формы

                formLinks.forEach(link => {
                    let url = new URL(link.href); // Получаем текущий URL
                    url.searchParams.set('showImage', showImage); // Устанавливаем значение showImage в URL
                    link.href = url.toString(); // Обновляем href ссылки
                    console.log('Updated URL: ', link.href); // Выводим в консоль обновленный URL
                });
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const trainingNotUpdatedCheckbox = document.getElementById('trainingNotUpdated');
            const trainingsTableBody = document.querySelector('#trainingsTable tbody');

            trainingNotUpdatedCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;

                // Проходим по каждой строке таблицы и проверяем условие
                Array.from(trainingsTableBody.rows).forEach(row => {
                    const lastTrainingDateCell = row.cells[3]; // ячейка с датой последней тренировки (теперь 4-я колонка, индекс 3)

                    if (isChecked) {
                        // Показываем строки, где дата последней тренировки больше 340 дней от текущей даты
                        const lastTrainingDate = new Date(lastTrainingDateCell.textContent.trim());
                        const daysDiff = Math.floor((new Date() - lastTrainingDate) / (1000 * 60 * 60 * 24));
                        if (daysDiff <= 340) {
                            row.style.display = 'none';
                        } else {
                            row.style.display = '';
                        }
                    } else {
                        // Показываем все строки, если переключатель не активен
                        row.style.display = '';
                    }
                });
            });
        });

        // Обработка удаления тренировок
        document.addEventListener('DOMContentLoaded', function () {
            let currentUserId, currentManualId;

            // Обработчик клика по кнопке удаления
            document.querySelectorAll('.delete-training-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentUserId = this.getAttribute('data-user-id');
                    currentManualId = this.getAttribute('data-manual-id');
                    const manualTitle = this.getAttribute('data-title');

                    document.getElementById('manualTitle').textContent = manualTitle;
                    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                    modal.show();
                });
            });

            // Обработчик подтверждения удаления
            document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
                if (!currentUserId || !currentManualId) return;

                fetch('{{ route("trainings.deleteAll") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: currentUserId,
                        manual_id: currentManualId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('All training records deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error deleting records: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting records');
                    })
                    .finally(() => {
                        bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide();
                    });
            });
        });

    </script>

@endsection
