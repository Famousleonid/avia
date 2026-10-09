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

            /* 6-й столбец (Actions) не скрываем */
        }

        .actions-cell {
            white-space: nowrap;
        }

        .actions-cell .btn {
            padding: .25rem .5rem;
            line-height: 1.1;
            white-space: nowrap; /* «Dates & Forms» в одну строку */
        }

        /* Ширина по контенту: даты и кнопки не переносятся */
        #trainingsTable td,
        #trainingsTable th {
            white-space: nowrap;
        }

        /* Fit below the page header and above the shared footer. */
        .trainings-page,
        .trainings-page > .card {
            display: flex;
            flex-direction: column;
            flex: 1 1 0;
            min-height: 0;
            min-width: 0;
        }

        .trainings-page > .card > .card-header {
            flex-shrink: 0;
        }

        .trainings-page > .card > .card-body {
            min-height: 0;
            overflow: auto;
            padding-top: 0;
        }

        /* The card body owns the main table's vertical and horizontal scroll. */
        .trainings-table-wrap {
            overflow: visible;
        }

        #scaCoursesBody .trainings-table-wrap {
            overflow: auto;
        }

        .trainings-table-wrap #trainingsTable thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--avia-surface);
        }

        .actions-cell span {
            display: inline-block;
        }

        @media (max-width: 576px) {
            .actions-cell .btn {
                padding: .2rem .4rem;
                font-size: .85rem;
            }
        }

        .training-modal-link,
        .training-modal-link:visited,
        .training-modal-link:hover,
        .training-modal-link:focus,
        .training-modal-link:active {
            color: inherit !important;
            text-decoration: none !important;
            background: transparent !important;
            box-shadow: none !important;
            outline: none !important;
        }

        #trainingsTable.table-hover tbody tr:hover > td,
        #trainingsTable.table-hover tbody tr:hover > th {
            background-color: inherit !important;
            color: inherit !important;
        }

        #trainingsTable tbody td:first-child,
        #trainingsTable tbody td:first-child:hover,
        #trainingsTable tbody td:first-child:focus,
        #trainingsTable tbody td:first-child:active {
            background-color: transparent !important;
        }

        #trainingsTable tbody td:first-child .btn,
        #trainingsTable tbody td:first-child .btn:hover,
        #trainingsTable tbody td:first-child .btn:focus,
        #trainingsTable tbody td:first-child .btn:active {
            background-color: transparent !important;
            color: inherit !important;
            box-shadow: none !important;
        }

        /* Select2 (поиск в селекте юзера) под тёмную тему — как на create-форме */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: var(--avia-input) !important;
            color: gray !important;
            height: 31px !important;
            border: 1px solid var(--avia-border) !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 1.9 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: var(--avia-surface-raised) !important;
            color: var(--avia-text);
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: var(--avia-input) !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
        }

        /* Шире выпадашка, имена без переносов, скролл списка */
        .select2-container .select2-dropdown {
            min-width: 320px !important;
            width: auto !important;
            overflow: hidden;
        }

        .select2-container .select2-results__option {
            white-space: nowrap !important;
        }

        .select2-results__options {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }
        .unit-training-modal .modal-body { overflow-x: auto; }
        .unit-training-modal .training-date-grid {
            display: grid;
            grid-template-columns: 72px minmax(145px, 1fr) 65px 76px 34px 32px;
            gap: 8px;
            align-items: center;
            min-width: 470px;
            margin: 0 0 8px;
        }
        .unit-training-modal .training-date-grid > div { width: auto; padding: 0; min-width: 0; }
        .training-hours { text-align: center; font-variant-numeric: tabular-nums; }
        .training-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
        .training-page-heading { display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; white-space: nowrap; font-size: 1rem; font-weight: 400; min-width: 0; overflow-x: auto; margin-bottom: 12px; }
        .training-page-heading h5,
        .training-page-heading .form-select,
        .training-page-heading .select2-selection__rendered { font-size: 1rem !important; }
        html[data-bs-theme] .training-page-heading #userSelectDropdown,
        html[data-bs-theme] .training-page-heading #select2-userSelectDropdown-container { color: rgba(var(--bs-info-rgb), 1) !important; }
        .training-page-heading > * { flex-shrink: 0; }
        .training-page-heading .select2-container { width: 260px !important; }
        .training-page-heading .select2-selection__rendered,
        .training-page-heading label { font-weight: 400 !important; }
    </style>

    <div class="container-fluid px-4 px-xl-5 trainings-page">
        <div class="card shadow">
            <div class="card-header">
                    <div class="training-page-heading">
                        <h5 class="mb-0">{{ __('Trainings') }}:</h5>

                        @roles("Admin|Manager")
                        @if($canViewAllUsers && $users->isNotEmpty())
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm"
                                        id="userSelectDropdown"
                                        aria-label="Technik" style="width: 260px; font-weight: 400;">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                            {{ $u->stamp }} — {{ $u->selection_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @endroles
                        @if(!$canViewAllUsers || !auth()->user()->roleIs(['Admin', 'Manager']) || $users->isEmpty())
                            <span class="text-info">{{ $users->firstWhere('id', $selectedUserId)?->selection_name ?? auth()->user()->selection_name }}</span>
                        @endif
                        <span>—</span>
                        <span class="fw-normal">
                            {{ __('Total training hours') }}:
                            <span id="trainingTotalHours" class="text-info">{{ number_format($totalHoursAll, $totalHoursAll == floor($totalHoursAll) ? 0 : 1, '.', ' ') }}</span>
                        </span>
                    </div>
                <div class="d-flex justify-content-between flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <input type="search"
                               id="trainingsSearch"
                               value="{{ $trainingSearch }}"
                               class="form-control form-control-sm"
                               style="min-width: 200px;"
                               placeholder="{{ __('Search') }}…"
                               aria-label="{{ __('Search') }}">

                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox" id="trainingNotUpdated">
                        <label class="form-check-label" for="trainingNotUpdated">
                            Not updated trainings
                        </label>
                    </div>
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox" id="trainingOnlyActive">
                        <label class="form-check-label" for="trainingOnlyActive">Only active</label>
                    </div>
                    </div>

                    <div class="align-middle">
                        <a href="{{ route('trainings.create') }}{{ $canViewAllUsers && $selectedUserId != auth()->id() ? '?user_id=' . $selectedUserId : '' }}"
                           class="btn btn-primary align-middle" id="openAddUnitModal">
                            {{ __('Add Component') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($scaCourses->isNotEmpty())
                    {{-- SCA-курсы (только для сотрудника с SCA-квалификацией); секция сворачивается --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h6 class="mb-0">{{ __('SCA Courses') }}</h6>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary py-0 px-2"
                                id="scaCoursesToggle"
                                aria-expanded="true"
                                aria-controls="scaCoursesBody">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                    </div>
                    <div id="scaCoursesBody">
                        <div class="trainings-table-wrap mb-3" style="max-height: 40vh;">
                            <table class="table table-bordered table-hover dir-table" id="scaCoursesTable">
                                <thead>
                                <tr>
                                    <th class="text-center align-middle">{{ __('Course') }}</th>
                                    <th class="text-center align-middle">{{ __('First Training Date') }}</th>
                                    <th class="text-center align-middle">{{ __('Last Training Date') }}</th>
                                    <th class="text-center align-middle">{{ __('Records') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($scaCourses as $course)
                                    <tr>
                                        <td>{{ $course['name'] }}</td>
                                        <td class="text-center">
                                            {{ $course['first'] ? Carbon::parse($course['first'])->format('d/M/Y') : '—' }}
                                        </td>
                                        <td class="text-center">
                                            @if(!$course['last'])
                                                <span class="text-muted">{{ __('never') }}</span>
                                            @elseif($course['overdue'])
                                                <span class="text-danger fw-bold"
                                                      title="{{ __(':days days since last training — refresh required (MP-20)', ['days' => $course['days_since']]) }}">
                                                    {{ Carbon::parse($course['last'])->format('d/M/Y') }}
                                                </span>
                                            @else
                                                {{ Carbon::parse($course['last'])->format('d/M/Y') }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($course['count'] > 0)
                                                {{-- Клик по числу — история дат курса (правка/удаление) --}}
                                                <button type="button"
                                                        class="btn btn-link p-0 m-0 align-baseline text-decoration-none"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#courseModal{{ $course['id'] }}">
                                                    {{ $course['count'] }}
                                                </button>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="trainings-table-wrap">
                <table id="trainingsTable"
                       class="table table-bordered table-hover dir-table">
                    <thead>
                    <tr>
                        <th data-priority="1" data-visible="true" class="text-center align-middle">
                            {{ __('Component Description') }}
                        </th>
                        <th data-priority="2"
                            data-visible="true"
                            id="sortComponentPn"
                            class="text-center align-middle sortable-th"
                            style="cursor: pointer; user-select: none;"
                            title="{{ __('Sort by Component PN') }}">
                            {{ __('Component PN') }}
                            <i class="bi bi-arrow-down-up text-muted small ms-1"></i>
                        </th>
                        <th data-priority="3" data-visible="true" class="text-center align-middle">
                            {{ __('First Training Date') }}
                        </th>
                        <th data-priority="4" data-visible="true" class="text-center align-middle">
                            {{ __('Last Training Date') }}
                        </th>
                        <th data-priority="5" data-visible="true" class="text-center align-middle">
                            {{ __('Total Hours') }}
                        </th>
                        <th data-priority="6" data-visible="true" class="text-center align-middle">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($formattedTrainingLists as $trainingList)
                        @php
                            $searchText = implode(' ', [
                                $trainingList['first_training']->manual->title ?? 'N/A',
                                $trainingList['first_training']->manual->unit_name_training ?? 'N/A',
                                format_project_date($trainingList['first_training']->date_training),
                                $trainingList['last_training_112'] ? format_project_date($trainingList['last_training_112']->date_training) : 'N/A',
                                number_format($trainingList['total_hours'], $trainingList['total_hours'] == floor($trainingList['total_hours']) ? 0 : 1, '.', ' ').' hrs',
                                __('Dates & Forms'),
                            ]);
                            $searchMatch = $trainingSelectedManual !== ''
                                ? (string) $trainingList['manuals_id'] === $trainingSelectedManual
                                : str_contains(mb_strtolower($searchText), mb_strtolower(trim($trainingSearch)));
                        @endphp
                        <tr data-manual-id="{{ $trainingList['manuals_id'] }}" data-is-due="{{ $trainingList['is_due_for_update'] ? '1' : '0' }}"
                            data-search-text="{{ $searchText }}" @if(!$searchMatch) style="display: none;" @endif
                            data-is-active="{{ $trainingList['last_training_112'] && $trainingList['days_since_last_training_112'] <= (int) config('trainings.matrix_red_after_days', 350) ? '1' : '0' }}">
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-link p-0 m-0 align-baseline text-decoration-none training-modal-link"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cmmModal{{ $trainingList['first_training']->manual->id }}">
                                    {{ $trainingList['first_training']->manual->title ?? 'N/A' }}
                                </button>
                            </td>

                            <td class="text-center">
                                {{ $trainingList['first_training']->manual->unit_name_training ?? 'N/A' }}
                            </td>

                            <td class="text-center">
                                {{ isset($trainingList['first_training']) ? Carbon::parse($trainingList['first_training']->date_training)->format('d/M/Y') : 'N/A' }}
                            </td>

                            @php
                                // Просрочка как в матрице: >350 дней — красная плашка,
                                // 180–350 (renewal_threshold_days) — оранжевый «скоро»
                                $last112Date = $trainingList['last_training_112']
                                    ? Carbon::parse($trainingList['last_training_112']->date_training)
                                    : null;
                                $daysSince112 = $trainingList['days_since_last_training_112'];
                                $isOverdue = $last112Date && $daysSince112 > (int) config('trainings.matrix_red_after_days', 350);
                                $isDueSoon = $last112Date && !$isOverdue && $trainingList['is_due_for_update'];
                            @endphp
                            <td class="text-center">
                                @if(!$last112Date)
                                    N/A
                                @elseif($isOverdue)
                                    <span class="text-danger fw-bold"
                                          title="{{ __(':days days since last training — refresh required (MP-20)', ['days' => $daysSince112]) }}">
                                        {{ $last112Date->format('d/M/Y') }}
                                    </span>
                                @elseif($isDueSoon)
                                    <span style="color: #e0a800; font-weight: 600;"
                                          title="{{ __(':days days since last training — renewal due', ['days' => $daysSince112]) }}">
                                        {{ $last112Date->format('d/M/Y') }}
                                    </span>
                                @else
                                    {{ $last112Date->format('d/M/Y') }}
                                @endif
                            </td>

                            @php
                                $unitHoursValue = $trainingList['total_hours'];
                                $unitHoursText = number_format($unitHoursValue, $unitHoursValue == floor($unitHoursValue) ? 0 : 1, '.', ' ');
                            @endphp
                            <td class="text-center">
                                {{ $unitHoursText }} hrs
                            </td>

                            <td class="text-center">
                                <div class="actions-cell d-inline-flex align-items-center justify-content-center gap-2 flex-nowrap">
                                    {{-- Не «редактирование», а карточка юнита: даты, печать форм, добавление --}}
                                    <button class="btn btn-outline-info btn-sm d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#trainingModal{{ $trainingList['first_training']->manuals_id }}"
                                            aria-label="{{ __('Training dates & forms') }}">
                                        <i class="bi bi-journal-text"></i> {{ __('Dates & Forms') }}
                                    </button>

                                    @roles("Admin|Manager")
                                    <button class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1 delete-training-btn"
                                            data-user-id="{{ $selectedUserId }}"
                                            data-manual-id="{{ $trainingList['first_training']->manuals_id }}"
                                            data-title="{{ $trainingList['first_training']->manual->title ?? 'N/A' }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endroles
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-label="Add Component" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"></div></div>
    </div>
    <style>
        .unit-training-modal .form-control.training-locked-base-date:disabled {
            background: transparent !important;
            border-color: transparent;
            box-shadow: none;
            opacity: 1;
            color: inherit;
        }
        #addUnitModal .modal-content { min-width: 0; }
        #addUnitModal .modal-body { min-height: 0; overflow-y: auto; }
        #addUnitModal .unit-date-row { display: grid; grid-template-columns: 82px minmax(0, 1fr) 55px 80px 32px; align-items: center; gap: 8px; margin-bottom: 8px; }
        #addUnitModal .unit-papers { display: flex; gap: 4px; }
        #addUnitModal .paper-btn { pointer-events: none; opacity: 1; width: 34px; height: 46px; }
        #addUnitModal #date_training_group { margin-bottom: 0; }
        #addUnitModal #date_training_group + .form-group { margin-top: 0 !important; }
        #addUnitModal .unit-actions { display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--avia-border); padding-top: 16px; margin-top: 16px; }
        #addUnitModal .select2-container { width: 100% !important; }
    </style>
    <script src="{{ asset('js/training-add-unit.js') }}?v={{ filemtime(public_path('js/training-add-unit.js')) }}" defer></script>
    {{-- Все модалки вынесены из <tr> / <table> --}}
    @foreach($formattedTrainingLists as $trainingList)

        <!-- Modal: Training (dates, forms, edit) -->
        <div class="modal fade unit-training-modal"
             id="trainingModal{{ $trainingList['first_training']->manuals_id }}"
             tabindex="-1"
             aria-labelledby="trainingModalLabel{{ $trainingList['first_training']->manuals_id }}"
             aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header justify-content-between">
                        <h5 class="modal-title"
                            id="trainingModalLabel{{ $trainingList['first_training']->manuals_id }}">
                            {{ __('Training') }}: {{ $trainingList['first_training']->manual->title }}
                            <br>
                            <span class="text-info">PN {{ $trainingList['first_training']->manual->unit_name_training }}</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>

                    <div class="modal-body">
                        @if(auth()->user()->canApproveTrainings() && $trainingList['trainings']->contains(fn ($t) => !$t->isApproved()))
                        <div class="training-summary justify-content-end">
                            <button type="button" class="btn btn-outline-success btn-sm approve-unit-btn"
                                    data-user-id="{{ $selectedUserId }}" data-manual-id="{{ $trainingList['first_training']->manuals_id }}">
                                ✓ {{ __('Approve all dates of this unit') }}
                            </button>
                        </div>
                        @endif

                        @php
                            $viewerCanApprove = auth()->user()->canApproveTrainings();
                            $viewerIsDesignated = auth()->user()->canManageApprovedTrainings();
                            $viewerIsAdmin = auth()->user()->roleIs('Admin') || auth()->user()->isAdmin();
                        @endphp
                        @foreach($trainingList['trainings'] as $training)
                            @php
                                $frozen = $training->isApproved() && !$viewerIsDesignated;
                                // 132 редактирует только Admin; принятые — только назначенный
                                $dateLocked = $frozen || ((string) $training->form_type === '132' && !$viewerIsAdmin);
                            @endphp
                            <div class="training-date-grid edit-training-row"
                                 data-training-id="{{ $training->id }}"
                                 data-original-date="{{ \Carbon\Carbon::parse($training->date_training)->format('Y-m-d') }}">
                                <div class="col-auto" style="min-width: 70px;">
                                    <span class="text-muted">{{ __('Form') }} {{ $training->form_type }}</span>
                                </div>

                                <div class="col" title="{{ $frozen ? __('Approved: only the designated manager can edit') : ((string) $training->form_type === '132' ? __('Date: Admin only') : __('Date: employee, Admin or Manager; approved dates require designated permission')) }}">
                                    @if($dateLocked)
                                        <input type="text"
                                               class="form-control form-control-sm {{ (string) $training->form_type === '132' ? 'training-locked-base-date' : '' }}"
                                               value="{{ \Carbon\Carbon::parse($training->date_training)->format('d/M/Y') }}"
                                               disabled>
                                    @else
                                        {{-- data-project-date: проектный flatpickr, формат 24/Aug/2026 --}}
                                        <input type="text"
                                               class="form-control form-control-sm edit-training-date-input"
                                               data-project-date autocomplete="off"
                                               value="{{ \Carbon\Carbon::parse($training->date_training)->format('d/M/Y') }}">
                                    @endif
                                </div>

                                <div class="training-hours" title="{{ __('Hours for this training (Form 112)') }}">
                                    {{ $trainingList['hours_by_training'][$training->id] ?? '—' }}
                                    @if(isset($trainingList['hours_by_training'][$training->id]))<small class="text-muted">{{ __('hrs') }}</small>@endif
                                </div>
                                <div class="col-auto">
                                    @if($training->isApproved())
                                        <span class="badge"
                                              style="background: rgba(46, 125, 79, 0.18); color: #4caf7d; border: 1px solid #2e7d4f;"
                                              title="{{ __('Approved by :name, :date', ['name' => $training->approvedBy->selection_name ?? '?', 'date' => $training->approved_at?->format('d/M/Y')]) }}">
                                            ✓
                                        </span>
                                        @if($viewerIsDesignated)
                                            <button type="button"
                                                    class="btn btn-outline-warning btn-sm unapprove-training-btn"
                                                    data-training-id="{{ $training->id }}"
                                                    title="{{ __('Remove approval (designated only)') }}">
                                                ✗
                                            </button>
                                        @endif
                                    @elseif($viewerCanApprove)
                                        <button type="button"
                                                class="btn btn-outline-success btn-sm approve-training-btn"
                                                data-training-id="{{ $training->id }}" title="{{ __('Approve') }}" aria-label="{{ __('Approve') }}">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="col-auto">
                                    {{-- Paper-кнопка (малый размер) вместо текстовой View/Print --}}
                                    @if($training->form_type == '112')
                                        <x-paper-button
                                            text="112"
                                            href="{{ route('trainings.form112', ['id'=> $training->id, 'showImage' => 'false']) }}"
                                            target="_blank"
                                            color="outline-success"
                                            width="34"
                                            fontSize="72px"
                                            class="formLink"
                                            id="formLink{{ $trainingList['first_training']->manuals_id }}_{{ $training->id }}"
                                        />
                                    @elseif($training->form_type == '132')
                                        <x-paper-button
                                            text="132"
                                            href="{{ route('trainings.form132', ['id' => $training->id, 'showImage' => 'false']) }}"
                                            target="_blank"
                                            color="outline-info"
                                            width="34"
                                            fontSize="72px"
                                            class="formLink"
                                            id="formLink{{ $trainingList['first_training']->manuals_id }}_{{ $training->id }}"
                                        />
                                    @endif
                                </div>

                                <div class="col-auto">
                                    @if($training->form_type == '112' && !$frozen)
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

                        <div class="row g-2 mb-2 align-items-center add-training-row"
                             data-manuals-id="{{ $trainingList['first_training']->manuals_id }}">
                            <div class="col-auto">
                                <span class="text-muted">{{ __('Add training') }}</span>
                            </div>

                            <div class="col">
                                <input type="text"
                                       class="form-control form-control-sm add-training-date-input"
                                       data-project-date autocomplete="off"
                                       placeholder="{{ __('dd/Mon/yyyy') }}">
                            </div>

                            <div class="col-auto">
                                <button type="button"
                                        class="btn btn-outline-success btn-sm add-training-in-edit-btn">
                                    <i class="bi bi-plus-lg"></i> {{ __('Add') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="d-flex align-items-center gap-2 me-auto">
                        @roles("Admin|Team Leader|Manager|Shop Certifying Authority (SCA)")
                        <div class="form-check mb-0">
                            <input type="checkbox"
                                   class="form-check-input sign-in-toggle"
                                   id="showImage{{ $trainingList['first_training']->manuals_id }}"
                                   data-manuals-id="{{ $trainingList['first_training']->manuals_id }}">
                            <label class="form-check-label"
                                   for="showImage{{ $trainingList['first_training']->manuals_id }}">
                                {{ __('Sign In') }}
                            </label>
                        </div>
                        @endroles
                            <button type="button" class="btn btn-outline-info btn-sm training-week-calendar-btn"
                                    data-user-id="{{ $selectedUserId }}" title="{{ __('Training week calendar') }}"
                                    aria-label="{{ __('Training week calendar') }}">
                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                            </button>
                        </div>

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button"
                                class="btn btn-primary edit-training-save-btn"
                                data-modal-id="trainingModal{{ $trainingList['first_training']->manuals_id }}">
                            {{ __('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: CMM details -->
        <div class="modal fade"
             id="cmmModal{{ $trainingList['first_training']->manual->id }}"
             tabindex="-1"
             role="dialog"
             aria-labelledby="cmmModalLabel{{ $trainingList['first_training']->manual->id }}"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-gradient">
                        <div>
                            <h5 class="modal-title"
                                id="cmmModalLabel{{ $trainingList['first_training']->manual->id }}">
                                {{ $trainingList['first_training']->manual->title }}{{ __(':') }}
                            </h5>
                            <h6>{{ $trainingList['first_training']->manual->unit_name_training }}</h6>
                        </div>
                        <button type="button"
                                class="btn-close pb-2"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>

                    <div class="modal-body bg-white">
                        <div class="d-flex bg-white">
                            <div class="me-2">
                                @if($trainingList['first_training']->manual->getFirstMediaBigUrl('manuals'))
                                    <img src="{{ $trainingList['first_training']->manual->getFirstMediaBigUrl('manuals') }}"
                                         style="width: 200px;"
                                         alt="{{ $trainingList['first_training']->manual->title }}">
                                @else
                                    <p>No image available</p>
                                @endif
                            </div>

                            <div class="bg-white text-black">
                                <p>
                                    <strong>{{ __('CMM:') }}</strong>
                                    {{ $trainingList['first_training']->manual->number }}
                                </p>
                                <p>
                                    <strong>{{ __('Description:') }}</strong>
                                    {{ $trainingList['first_training']->manual->title }}
                                </p>
                                <p>
                                    <strong>{{ __('Revision Date:') }}</strong>
                                    {{ format_project_date($trainingList['first_training']->manual->revision_date) ?? '-' }}
                                </p>
                                <p>
                                    <strong>{{ __('AirCraft Type:') }}</strong>
                                    {{ $planes[$trainingList['first_training']->manual->planes_id] ?? 'N/A' }}
                                </p>
                                <p>
                                    <strong>{{ __('MFR:') }}</strong>
                                    {{ $builders[$trainingList['first_training']->manual->builders_id] ?? 'N/A' }}
                                </p>
                                <p>
                                    <strong>{{ __('Scope:') }}</strong>
                                    {{ $scopes[$trainingList['first_training']->manual->scopes_id] ?? 'N/A' }}
                                </p>
                                <p>
                                    <strong>{{ __('Library:') }}</strong>
                                    {{ $trainingList['first_training']->manual->lib }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endforeach

    {{-- Модалки истории дат SCA-курсов: правка/удаление через общие обработчики
         (.edit-training-save-btn / .delete-training-date-btn / approve-кнопки) --}}
    @php
        $courseViewerCanApprove = auth()->user()->canApproveTrainings();
        $courseViewerIsDesignated = auth()->user()->canManageApprovedTrainings();
        $courseViewerManages = auth()->user()->canManageTrainingMatrix();
    @endphp
    @foreach($scaCourses as $course)
        @if($course['count'] > 0)
            <div class="modal fade" id="courseModal{{ $course['id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('SCA Course') }}: {{ $course['name'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                        </div>

                        <div class="modal-body">
                            @foreach($course['records'] as $record)
                                @php $recordFrozen = $record['approved'] && !$courseViewerIsDesignated; @endphp
                                <div class="row g-2 mb-2 align-items-center edit-training-row"
                                     data-training-id="{{ $record['id'] }}"
                                     data-original-date="{{ Carbon::parse($record['date'])->format('Y-m-d') }}">
                                    <div class="col-auto" style="min-width: 60px;">
                                        <span class="text-muted">{{ $loop->iteration }}.</span>
                                    </div>

                                    <div class="col">
                                        @if($recordFrozen || !$courseViewerManages)
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   value="{{ Carbon::parse($record['date'])->format('d/M/Y') }}"
                                                   disabled>
                                        @else
                                            <input type="text"
                                                   class="form-control form-control-sm edit-training-date-input"
                                                   data-project-date autocomplete="off"
                                                   value="{{ Carbon::parse($record['date'])->format('d/M/Y') }}">
                                        @endif
                                    </div>

                                    <div class="col-auto">
                                        @if($record['approved'])
                                            <span class="badge"
                                                  style="background: rgba(46, 125, 79, 0.18); color: #4caf7d; border: 1px solid #2e7d4f;">
                                                ✓ {{ __('Approved') }}
                                            </span>
                                            @if($courseViewerIsDesignated)
                                                <button type="button"
                                                        class="btn btn-outline-warning btn-sm unapprove-training-btn"
                                                        data-training-id="{{ $record['id'] }}"
                                                        title="{{ __('Remove approval (designated only)') }}">
                                                    ✗
                                                </button>
                                            @endif
                                        @elseif($courseViewerCanApprove)
                                            <button type="button"
                                                    class="btn btn-outline-success btn-sm approve-training-btn"
                                                    data-training-id="{{ $record['id'] }}">
                                                ✓ {{ __('Approve') }}
                                            </button>
                                        @endif
                                    </div>

                                    <div class="col-auto">
                                        @if($courseViewerManages && !$recordFrozen)
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm delete-training-date-btn"
                                                    data-training-id="{{ $record['id'] }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                {{ __('Cancel') }}
                            </button>
                            @if($courseViewerManages)
                                <button type="button"
                                        class="btn btn-primary edit-training-save-btn"
                                        data-modal-id="courseModal{{ $course['id'] }}">
                                    {{ __('Save') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <!-- Modal: Confirm delete -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Are you sure you want to delete ALL training records for
                        <span id="manualTitle"></span>?
                    </p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm remove approval -->
    <div class="modal fade" id="confirmUnapproveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Remove approval') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Remove approval from this training?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-warning" id="confirmUnapproveBtn">{{ __('Remove') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectedUserId = {{ $selectedUserId }};
        const authUserId = {{ auth()->id() }};
        const canEditOtherUser = {{ $canViewAllUsers ? 'true' : 'false' }};
        /*

                        console.error('Ошибка:', error);
        */
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

        document.addEventListener('DOMContentLoaded', function () {
            // Сортировка по Component PN
            const sortTh = document.getElementById('sortComponentPn');
            const table = document.getElementById('trainingsTable');

            if (sortTh && table) {
                let sortOrder = 1;

                sortTh.addEventListener('click', function () {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const componentPnIndex = 1;

                    rows.sort(function (a, b) {
                        const aVal = (a.cells[componentPnIndex]?.textContent || '').trim();
                        const bVal = (b.cells[componentPnIndex]?.textContent || '').trim();
                        const cmp = aVal.localeCompare(bVal, 'en', { numeric: false, sensitivity: 'base' });
                        return sortOrder * cmp;
                    });

                    sortOrder *= -1;
                    rows.forEach(function (row) {
                        tbody.appendChild(row);
                    });

                    const icon = sortTh.querySelector('i');
                    if (icon) {
                        icon.className = (sortOrder > 0 ? 'bi bi-arrow-down' : 'bi bi-arrow-up') + ' text-muted small ms-1';
                    }
                });
            }

            // Сворачивание секции SCA-курсов (состояние переживает перезагрузку)
            const scaToggle = document.getElementById('scaCoursesToggle');
            const scaBody = document.getElementById('scaCoursesBody');
            if (scaToggle && scaBody) {
                const applyScaState = function (collapsed) {
                    scaBody.style.display = collapsed ? 'none' : '';
                    const icon = scaToggle.querySelector('i');
                    if (icon) icon.className = collapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
                    scaToggle.setAttribute('aria-expanded', String(!collapsed));
                };
                let scaCollapsed = localStorage.getItem('scaCoursesCollapsed') === '1';
                applyScaState(scaCollapsed);
                scaToggle.addEventListener('click', function () {
                    scaCollapsed = !scaCollapsed;
                    localStorage.setItem('scaCoursesCollapsed', scaCollapsed ? '1' : '0');
                    applyScaState(scaCollapsed);
                });
            }

            // Обработчик Update

            const baseUrl = '{{ url("trainings") }}';
            const csrfToken = '{{ csrf_token() }}';

            function trainingClick(selector, handler) {
                document.addEventListener('click', function (event) {
                    const button = event.target.closest(selector);
                    if (button && !button.disabled) handler.call(button, event);
                });
            }

            async function confirmTraining(modal, options) {
                if (!window.confirmDialog) { showNotification('Confirmation dialog is unavailable.', 'error'); return false; }
                const body = modal.querySelector('.modal-body');
                body.inert = true;
                try {
                    const answer = window.confirmDialog(options);
                    const dialog = document.getElementById('globalConfirmModal');
                    const closed = new Promise(resolve => dialog.addEventListener('hidden.bs.modal', resolve, {once: true}));
                    const accepted = await answer;
                    await closed;
                    // Bootstrap removes this when the confirmation closes, although our editor is still open.
                    if (modal.classList.contains('show')) document.body.classList.add('modal-open');
                    return accepted;
                } finally {
                    body.inert = false;
                }
            }

            // Keep the Bootstrap shell, unsaved dates and footer controls alive.
            async function refreshTrainingModal(modal) {
                modal.querySelector('.modal-body').inert = true;
                try {
                    await loadTrainingModal(modal);
                    modal.dataset.refreshRevision = String(Number(modal.dataset.refreshRevision || 0) + 1);
                } finally {
                    modal.querySelector('.modal-body').inert = false;
                }
            }

            async function loadTrainingModal(modal) {
                const pendingRows = Array.from(modal.querySelectorAll('.pending-training-row'));
                const addDraft = modal.querySelector('.add-training-date-input')?.value || '';
                const drafts = new Map();
                modal.querySelectorAll('.edit-training-row').forEach(row => {
                    const input = row.querySelector('.edit-training-date-input');
                    if (input && projectDateInputToYmd(input) !== row.dataset.originalDate) {
                        drafts.set(row.dataset.trainingId, input.value);
                    }
                });
                const response = await fetch(window.location.href, {headers: {'Accept': 'text/html'}, cache: 'no-store'});
                if (!response.ok || response.redirected) throw new Error('Could not refresh training. Please reload the page.');
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                const fresh = doc.getElementById(modal.id);
                if (!fresh) {
                    // The last date may be deleted; keep Add usable in this same window.
                    modal.querySelectorAll('.edit-training-row, .approve-unit-btn').forEach(node => node.remove());
                    modal.querySelector('.training-summary')?.remove();
                    const manualId = modal.querySelector('.add-training-row')?.dataset.manualsId;
                    const tableRow = manualId && document.querySelector('#trainingsTable tr[data-manual-id="' + manualId + '"]');
                    if (tableRow) {
                        tableRow.cells[2].textContent = 'N/A';
                        tableRow.cells[3].textContent = 'N/A';
                        tableRow.cells[4].textContent = '0 hrs';
                        tableRow.dataset.isDue = '1';
                    }
                    const total = document.getElementById('trainingTotalHours');
                    if (total && doc.getElementById('trainingTotalHours')) total.textContent = doc.getElementById('trainingTotalHours').textContent;
                    return;
                }
                modal.querySelectorAll('[data-project-date]').forEach(input => {
                    input._projectDatePicker?.destroy();
                    delete input._projectDatePicker;
                });
                modal.querySelector('.modal-body').replaceWith(fresh.querySelector('.modal-body'));
                const divider = modal.querySelector('.add-training-row')?.previousElementSibling;
                pendingRows.forEach(row => divider?.before(row));
                const addInput = modal.querySelector('.add-training-date-input');
                if (addInput) addInput.value = addDraft;
                modal.querySelectorAll('.edit-training-row').forEach(row => {
                    const input = row.querySelector('.edit-training-date-input');
                    if (input && drafts.has(row.dataset.trainingId)) input.value = drafts.get(row.dataset.trainingId);
                });
                window.initProjectDatePickers?.(modal);
                modal.querySelector('.sign-in-toggle')?.dispatchEvent(new Event('change', {bubbles: true}));
                // Totals and last dates outside the dialog must reflect the same server calculation.
                const manualId = modal.querySelector('.add-training-row')?.dataset.manualsId;
                if (manualId) {
                    const selector = '#trainingsTable tr[data-manual-id="' + manualId + '"]';
                    const oldRow = document.querySelector(selector);
                    const newRow = doc.querySelector(selector);
                    if (oldRow && newRow) {
                        [2, 3, 4].forEach(index => { oldRow.cells[index].innerHTML = newRow.cells[index].innerHTML; });
                        oldRow.dataset.isDue = newRow.dataset.isDue;
                    }
                }
                const total = document.getElementById('trainingTotalHours');
                if (total && doc.getElementById('trainingTotalHours')) total.textContent = doc.getElementById('trainingTotalHours').textContent;
                bootstrap.Modal.getInstance(modal)?.handleUpdate();
            }

            // Значение проектного date-инпута (24/Aug/2026) → 'Y-m-d' для сервера
            function projectDateInputToYmd(input) {
                const m = String(input.value || '').trim().match(/^(\d{1,2})[\/.]([a-z]{3})[\/.](\d{4})$/i);
                if (!m) return '';
                const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
                const month = months.indexOf(m[2].toLowerCase());
                if (month < 0) return '';
                const date = new Date(Number(m[3]), month, Number(m[1]));
                if (date.getFullYear() !== Number(m[3]) || date.getMonth() !== month || date.getDate() !== Number(m[1])) return '';
                return m[3] + '-' + String(month + 1).padStart(2, '0') + '-' + String(Number(m[1])).padStart(2, '0');
            }

            function trainingDirty(modal) {
                return !!modal.querySelector('.pending-training-row')
                    || !!modal.querySelector('.add-training-date-input')?.value.trim()
                    || Array.from(modal.querySelectorAll('.edit-training-row')).some(row => {
                        const input = row.querySelector('.edit-training-date-input');
                        return input && projectDateInputToYmd(input) !== row.dataset.originalDate;
                    });
            }

            function discardTrainingDraft(modal) {
                modal.querySelectorAll('.pending-training-row').forEach(row => {
                    row.querySelector('input')._projectDatePicker?.destroy();
                    row.remove();
                });
                modal.querySelectorAll('.edit-training-row').forEach(row => {
                    const input = row.querySelector('.edit-training-date-input');
                    if (input) {
                        const [year, month, day] = row.dataset.originalDate.split('-');
                        const value = day + '/' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(month) - 1] + '/' + year;
                        input.value = value;
                        input._projectDatePicker?.setDate(value, true);
                    }
                });
                modal.querySelector('.add-training-date-input')?._projectDatePicker?.clear();
            }

            document.querySelectorAll('.unit-training-modal').forEach(modal => {
                const cancel = modal.querySelector('.modal-footer [data-bs-dismiss="modal"]');
                cancel?.removeAttribute('data-bs-dismiss');
                cancel?.addEventListener('click', () => {
                    if (modal.dataset.saving === '1') return;
                    discardTrainingDraft(modal);
                    bootstrap.Modal.getInstance(modal)?.hide();
                });
                modal.addEventListener('hide.bs.modal', async event => {
                    if (modal.dataset.saving === '1') { event.preventDefault(); return; }
                    if (!trainingDirty(modal)) return;
                    event.preventDefault();
                    if (modal.dataset.closePrompt === '1') return;
                    modal.dataset.closePrompt = '1';
                    try {
                        if (await confirmTraining(modal, {
                            title: 'Unsaved training changes',
                            message: 'Added training dates and edits have not been saved. Close without saving?',
                            okText: 'Discard changes', cancelText: 'Continue editing', danger: true
                        })) {
                            discardTrainingDraft(modal);
                            bootstrap.Modal.getInstance(modal)?.hide();
                        }
                    } finally { delete modal.dataset.closePrompt; }
                });
            });
            // Add is a local draft operation. Only Save sends dates to the server.
            trainingClick('.add-training-in-edit-btn', async function () {
                const modal = this.closest('.modal');
                const source = modal.querySelector('.add-training-date-input');
                if (!source.value.trim()) {
                    source._emptyDateAnimation?.cancel();
                    const border = getComputedStyle(source).borderColor;
                    source._emptyDateAnimation = source.animate([
                        {borderColor: border, boxShadow: '0 0 0 0 transparent'},
                        {borderColor: '#dc3545', boxShadow: '0 0 0 2px rgba(220, 53, 69, .35)', offset: 0.35},
                        {borderColor: '#dc3545', boxShadow: '0 0 0 2px rgba(220, 53, 69, .35)', offset: 0.65},
                        {borderColor: border, boxShadow: '0 0 0 0 transparent'}
                    ], {duration: 600, iterations: 3, easing: 'ease-in-out'});
                    return;
                }
                if (!window.TrainingWeekAvailability || !await window.TrainingWeekAvailability.check(source)) return;
                const ymd = projectDateInputToYmd(source);
                if (!ymd) { showNotification('Enter a valid date: dd/Mmm/yyyy.', 'warning'); return; }
                const today = new Date();
                const date = new Date(ymd + 'T00:00:00');
                if (date > today) { showNotification('Training date cannot be in the future.', 'warning'); return; }
                const row = document.createElement('div');
                row.className = 'training-date-grid pending-training-row';
                row.innerHTML = '<div class="text-muted">Form 112</div>'
                    + '<div><input class="form-control form-control-sm pending-training-date-input" data-project-date autocomplete="off"></div>'
                    + '<div class="training-hours text-muted">—</div>'
                    + '<div class="small text-warning" style="grid-column: span 2">Not saved</div>'
                    + '<div><button type="button" class="btn btn-outline-danger btn-sm remove-pending-training" title="Remove unsaved date"><i class="bi bi-trash"></i></button></div>';
                row.querySelector('input').value = source.value;
                modal.querySelector('.add-training-row').previousElementSibling.before(row);
                window.initProjectDatePickers?.(row);
                source._projectDatePicker?.clear();
                source.value = '';
                bootstrap.Modal.getInstance(modal)?.handleUpdate();
            });
            trainingClick('.remove-pending-training', function () {
                const row = this.closest('.pending-training-row');
                row.querySelector('input')._projectDatePicker?.destroy();
                row.remove();
            });

            document.querySelectorAll('.edit-training-save-btn').forEach(button => {
                button.addEventListener('click', async function () {
                    const modal = document.getElementById(this.dataset.modalId);
                    if (!modal || modal.dataset.saving === '1') return;
                    if (!window.TrainingWeekAvailability || !await window.TrainingWeekAvailability.checkAll(modal)) return;
                    const pending = Array.from(modal.querySelectorAll('.pending-training-row'));
                    const updates = Array.from(modal.querySelectorAll('.edit-training-row')).filter(row => {
                        const input = row.querySelector('.edit-training-date-input');
                        return input && projectDateInputToYmd(input) !== row.dataset.originalDate;
                    });
                    const unusedInput = modal.querySelector('.add-training-date-input');
                    if (unusedInput?.value.trim()) {
                        showNotification('Click +Add to include the entered date, then Save.', 'warning'); return;
                    }
                    const inputs = [...pending.map(row => row.querySelector('input')), ...updates.map(row => row.querySelector('input'))];
                    if (inputs.some(input => !projectDateInputToYmd(input))) {
                        showNotification('Enter a valid date: dd/Mmm/yyyy.', 'warning'); return;
                    }
                    if (inputs.some(input => new Date(projectDateInputToYmd(input) + 'T00:00:00') > new Date())) {
                        showNotification('Training date cannot be in the future.', 'warning'); return;
                    }
                    if (!pending.length && !updates.length) { bootstrap.Modal.getInstance(modal)?.hide(); return; }
                    modal.dataset.saving = '1';
                    this.disabled = true;
                    modal.querySelector('.modal-body').inert = true;
                    async function send(url, method, data) {
                        const response = await fetch(url, {method, headers: {
                            'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
                        }, body: JSON.stringify(data)});
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Could not save training.');
                        return result;
                    }
                    try {
                        // Mark successful rows clean individually; a failed later request stays editable.
                        for (const row of updates) {
                            const date = projectDateInputToYmd(row.querySelector('input'));
                            await send(baseUrl + '/' + row.dataset.trainingId, 'PATCH', {date_training: date});
                            row.dataset.originalDate = date;
                        }
                        if (pending.length) {
                            const manual = modal.querySelector('.add-training-row').dataset.manualsId;
                            const data = {
                                manuals_id: pending.map(() => manual),
                                date_training: pending.map(row => projectDateInputToYmd(row.querySelector('input'))),
                                form_type: pending.map(() => '112')
                            };
                            if (canEditOtherUser && selectedUserId !== authUserId) data.user_id = selectedUserId;
                            const saved = await send('{{ route('trainings.createTraining') }}', 'POST', data);
                            // The database write succeeded: do not retry it if only the animation fails.
                            pending.forEach(row => row.classList.remove('pending-training-row'));
                            for (const [index, row] of pending.entries()) {
                                const record = saved.saved_trainings?.find(item => String(item.manual_id) === String(manual)
                                    && item.date === saved.submitted_dates?.[index]);
                                const status = row.querySelector('.text-warning');
                                const hours = row.querySelector('.training-hours');
                                if (!record) { if (status) status.textContent = 'Saved'; continue; }
                                row.scrollIntoView({block: 'nearest', behavior: 'smooth'});
                                hours.textContent = record.hours + ' hrs';
                                hours.classList.remove('text-muted');
                                hours.style.opacity = '0';
                                row.dataset.saveAnimation = 'running';
                                const oldBox = status.getBoundingClientRect();
                                const newBox = hours.getBoundingClientRect();
                                const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                                await Promise.all([
                                    status.animate([{opacity: 1, transform: 'translateX(0)'},
                                        {opacity: 0, transform: reduced ? 'none' : 'translateX(' + (newBox.x - oldBox.x) + 'px)'}],
                                        {duration: 1000, easing: 'ease-in-out', fill: 'forwards'}).finished,
                                    hours.animate([{opacity: 0}, {opacity: 0, offset: 0.35}, {opacity: 1}],
                                        {duration: 1000, easing: 'ease-in-out', fill: 'forwards'}).finished
                                ]);
                                status.textContent = '';
                                hours.style.opacity = '1';
                                row.dataset.saveAnimation = 'done';
                            }
                        }
                        showNotification('Training dates saved.', 'success');
                        const content = modal.querySelector('.modal-content');
                        const height = content.getBoundingClientRect().height;
                        content.style.overflow = 'hidden';
                        await content.animate([{height: height + 'px', opacity: 1}, {height: '0px', opacity: 0}],
                            {duration: 400, easing: 'ease-in', fill: 'forwards'}).finished;
                        delete modal.dataset.saving;
                        await new Promise(resolve => {
                            modal.addEventListener('hidden.bs.modal', resolve, {once: true});
                            bootstrap.Modal.getInstance(modal)?.hide();
                        });
                        location.reload();
                    } catch (error) {
                        showNotification(error.message, 'error');
                    } finally {
                        delete modal.dataset.saving;
                        this.disabled = false;
                        modal.querySelector('.modal-body').inert = false;
                    }
                });
            });

            // Удаление отдельной даты тренинга — своя модалка вместо браузерного confirm
            trainingClick('.delete-training-date-btn', async function () {
                    const trainingId = this.getAttribute('data-training-id');
                    if (!trainingId) return;
                    if (!await confirmTraining(this.closest('.modal'), {title: 'Delete training', message: 'Delete this training date?', okText: 'Delete', danger: true})) return;
                    const modal = this.closest('.modal');
                    const button = this;
                    button.disabled = true;
                    fetch(baseUrl + '/' + trainingId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (r) {
                            return r.json().then(function (data) {
                                return { ok: r.ok, data: data };
                            });
                        })
                        .then(async function (result) {
                            if (!result.ok) {
                                showNotification('{{ __("Error") }}: ' + (result.data.message || ''), 'error');
                                return;
                            }

                            showNotification(result.data.message || '{{ __("Training date deleted.") }}', 'success');
                            await refreshTrainingModal(modal);
                        })
                        .catch(function (err) {
                            showNotification('{{ __("Error") }}: ' + err.message, 'error');
                        }).finally(() => { button.disabled = false; });
            });

            // Приёмка тренингов: approve / unapprove / approve всего юнита
            function trainingApprovalPost(url) {
                if (Array.from(document.querySelectorAll('.unit-training-modal')).some(trainingDirty)) {
                    showNotification('Save or discard your training changes before approval.', 'warning'); return;
                }
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                    .then(function (result) {
                        if (!result.ok || !result.data.success) {
                            showNotification('{{ __("Error") }}: ' + (result.data.message || ''), 'error');
                            return;
                        }
                        location.reload();
                    })
                    .catch(function (err) {
                        showNotification('{{ __("Error") }}: ' + err.message, 'error');
                    });
            }

            trainingClick('.approve-training-btn', function () {
                    trainingApprovalPost(baseUrl + '/' + this.getAttribute('data-training-id') + '/approve');
            });

            // Снятие приёмки — своя модалка вместо браузерного confirm
            let pendingUnapproveId = null;

            trainingClick('.unapprove-training-btn', function () {
                    pendingUnapproveId = this.getAttribute('data-training-id');
                    new bootstrap.Modal(document.getElementById('confirmUnapproveModal')).show();
            });

            document.getElementById('confirmUnapproveBtn')?.addEventListener('click', function () {
                if (!pendingUnapproveId) return;
                bootstrap.Modal.getInstance(document.getElementById('confirmUnapproveModal'))?.hide();
                trainingApprovalPost(baseUrl + '/' + pendingUnapproveId + '/unapprove');
            });

            trainingClick('.approve-unit-btn', function () {
                    if (Array.from(document.querySelectorAll('.unit-training-modal')).some(trainingDirty)) {
                        showNotification('Save or discard your training changes before approval.', 'warning'); return;
                    }
                    const params = new URLSearchParams({
                        user_id: this.getAttribute('data-user-id'),
                        manual_id: this.getAttribute('data-manual-id'),
                    });
                    fetch('{{ route('trainings.approveUnit') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params.toString()
                    })
                        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                        .then(function (result) {
                            if (!result.ok || !result.data.success) {
                                showNotification('{{ __("Error") }}: ' + (result.data.message || ''), 'error');
                                return;
                            }
                            location.reload();
                        })
                        .catch(function (err) {
                            showNotification('{{ __("Error") }}: ' + err.message, 'error');
                        });
            });

            // Переключение showImage только для конкретной модалки/manuals_id
            document.querySelectorAll('.sign-in-toggle').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const showImage = this.checked ? 'true' : 'false';
                    const manualsId = this.getAttribute('data-manuals-id');

                    const modal = document.getElementById('trainingModal' + manualsId);
                    if (!modal) return;

                    const formLinks = modal.querySelectorAll('.formLink');

                    formLinks.forEach(function (link) {
                        let url = new URL(link.href);
                        url.searchParams.set('showImage', showImage);
                        link.href = url.toString();
                    });
                });
            });

            // Согласованные клиентские фильтры: поиск + тумблёр «Not updated» (AND)
            const trainingNotUpdatedCheckbox = document.getElementById('trainingNotUpdated');
            const trainingOnlyActiveCheckbox = document.getElementById('trainingOnlyActive');
            const trainingsSearchInput = document.getElementById('trainingsSearch');
            const trainingsTableBody = document.querySelector('#trainingsTable tbody');

            if (trainingsTableBody) {
                let selectedManual = @json($trainingSelectedManual) || null;
                let searchSaveTimer, pendingSearch = null, savingSearch = null;
                const flushTrainingSearch = () => {
                    clearTimeout(searchSaveTimer);
                    if (!savingSearch && pendingSearch) {
                        savingSearch = (async () => {
                            while (pendingSearch) {
                                const value = pendingSearch; pendingSearch = null;
                                await window.UserUiSettings.set('trainings', 'search', value, {keepalive: true});
                            }
                        })().finally(() => { savingSearch = null; });
                    }
                };
                const rememberTrainingSearch = () => {
                    pendingSearch = {text: trainingsSearchInput?.value || '', manual_id: selectedManual || ''};
                    // A link's initial component must not override later typed searches on reload.
                    const url = new URL(window.location.href);
                    if (url.searchParams.has('manual_id')) {
                        url.searchParams.delete('manual_id');
                        history.replaceState(history.state, '', url);
                    }
                    clearTimeout(searchSaveTimer);
                    searchSaveTimer = setTimeout(flushTrainingSearch, 200);
                };
                window.addEventListener('pagehide', flushTrainingSearch);
                document.addEventListener('visibilitychange', () => { if (document.hidden) flushTrainingSearch(); });
                const applyTrainingFilters = function () {
                    const onlyDue = trainingNotUpdatedCheckbox ? trainingNotUpdatedCheckbox.checked : false;
                    const onlyActive = trainingOnlyActiveCheckbox ? trainingOnlyActiveCheckbox.checked : false;
                    const term = trainingsSearchInput ? trainingsSearchInput.value.trim().toLowerCase() : '';

                    Array.from(trainingsTableBody.rows).forEach(function (row) {
                        const matchesDue = !onlyDue || row.dataset.isDue === '1';
                        const matchesActive = !onlyActive || row.dataset.isActive === '1';
                        const matchesTerm = selectedManual
                            ? row.dataset.manualId === selectedManual
                            : term === '' || row.dataset.searchText.toLowerCase().includes(term);
                        row.style.display = (matchesDue && matchesActive && matchesTerm) ? '' : 'none';
                    });
                };

                if (trainingNotUpdatedCheckbox) {
                    trainingNotUpdatedCheckbox.addEventListener('change', () => {
                        if (trainingNotUpdatedCheckbox.checked && trainingOnlyActiveCheckbox) trainingOnlyActiveCheckbox.checked = false;
                        applyTrainingFilters();
                    });
                }
                trainingOnlyActiveCheckbox?.addEventListener('change', () => {
                    if (trainingOnlyActiveCheckbox.checked && trainingNotUpdatedCheckbox) trainingNotUpdatedCheckbox.checked = false;
                    applyTrainingFilters();
                });
                if (trainingsSearchInput) {
                    trainingsSearchInput.addEventListener('input', function () {
                        selectedManual = null;
                        applyTrainingFilters();
                        rememberTrainingSearch();
                    });
                }
                document.addEventListener('training:select-component', event => {
                    const manualId = String(event.detail?.manualId || '');
                    const row = Array.from(trainingsTableBody.rows).find(item => item.dataset.manualId === manualId);
                    if (!row) return;
                    selectedManual = manualId;
                    if (trainingsSearchInput) trainingsSearchInput.value = row.cells[1].textContent.trim();
                    if (trainingNotUpdatedCheckbox) trainingNotUpdatedCheckbox.checked = false;
                    if (trainingOnlyActiveCheckbox) trainingOnlyActiveCheckbox.checked = false;
                    applyTrainingFilters();
                    rememberTrainingSearch();
                    row.scrollIntoView({block: 'nearest'});
                    trainingsSearchInput?.focus({preventScroll: true});
                });
                applyTrainingFilters();
                if (new URLSearchParams(window.location.search).has('manual_id')) rememberTrainingSearch();
            }

            // Calendar overlap links open the existing date editor for the selected record.
            const requestedTraining = new URLSearchParams(window.location.search).get('edit_training');
            if (requestedTraining && /^\d+$/.test(requestedTraining)) {
                const row = document.querySelector('.edit-training-row[data-training-id="' + requestedTraining + '"]');
                const editor = row?.closest('.modal');
                if (editor) {
                    editor.addEventListener('shown.bs.modal', () => {
                        row.scrollIntoView({block: 'center'});
                        row.animate([{backgroundColor: 'rgba(229,79,94,.25)'}, {backgroundColor: 'transparent'}], {duration: 1800});
                        row.querySelector('.edit-training-date-input')?.focus({preventScroll: true});
                    }, {once: true});
                    bootstrap.Modal.getOrCreateInstance(editor).show();
                } else {
                    window.showNotification('This training is not available in your date editor.', 'warning');
                }
            }

            // Удаление всех training records
            let currentUserId = null;
            let currentManualId = null;

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

            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function () {
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
                                showNotification('All training records deleted successfully!', 'success');
                                location.reload();
                            } else {
                                showNotification('Error deleting records: ' + (data.message || 'Unknown error'), 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred while deleting records', 'error');
                        })
                        .finally(() => {
                            bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'))?.hide();
                        });
                });
            }
        });
    </script>
    <script>
        // Селект юзера: поиск (select2) + переход при выборе
        window.addEventListener('load', function () {
            const select = document.getElementById('userSelectDropdown');
            if (!select) return;

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(select).select2({
                    theme: 'bootstrap-5',
                    width: 'style',
                    dropdownAutoWidth: true,
                });
                jQuery(select).on('change', function () {
                    window.location.href = '{{ route('trainings.index') }}?user_id=' + this.value;
                });
            } else {
                select.addEventListener('change', function () {
                    window.location.href = '{{ route('trainings.index') }}?user_id=' + this.value;
                });
            }
        });
    </script>
    @include('admin.trainings.partials.friday-snap')
    @include('admin.trainings.partials.week-availability')
@endsection
