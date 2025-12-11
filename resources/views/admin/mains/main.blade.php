@extends('admin.master')

@section('style')
    <style>
        .sf {
            font-size: 12px;
        }

        [data-fp] {
            opacity: 0;
        }

        .flatpickr-input[readonly] {
            opacity: 1 !important;
        }

        .flatpickr-calendar {
            z-index: 2000 !important;
        }

        input::placeholder,
        .flatpickr-input::placeholder {
            color: #6c757d;
            opacity: 1;
        }

        .gradient-pane {
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
        }

        .vh-layout {
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .top-pane {
            flex: 0 0 20%;
            min-height: 165px;
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: .5rem;
            padding: 1rem;
            overflow: auto;
        }

        .bottom-row {
            flex: 1 1 auto;
            display: flex;
            gap: .75rem;
            min-height: 260px;
        }

        .bottom-col {
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: .5rem;
            padding: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 200px;
        }

        @media (min-width: 992px) {
            .bottom-col.left {
                width: 50%
            }

            .bottom-col.right {
                width: 50%
            }
        }

        @media (max-width: 991.98px) {
            .bottom-row {
                flex-direction: column
            }

            .bottom-col {
                width: 100%
            }
        }

        .select-task {
            border: 0;
            width: 100%;
            text-align: left;
            padding: .5rem .75rem;
            background: transparent;
            border-radius: .5rem;
        }

        .select-task:hover {
            background: rgba(0, 123, 255, .15);
            cursor: pointer;
        }

        #taskTabContent {
            max-height: 40vh;
            overflow: auto;
        }

        .eqh-sm {
            height: calc(1.8125rem + 2px);
        }

        .is-valid {
            box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25);
        }

        #taskPickerBtn.eqh {
            height: calc(1.8125rem + 2px);
        }

        .left-pane {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            height: 100%;
        }

        .table-wrap {
            flex: 1 1 auto;
            min-height: 180px;
        }

        .table-wrap .table-responsive {
            height: 100%;
            max-height: 100%;
            overflow: auto;
        }

        @media (max-width: 991.98px) {
            #taskTabContent {
                max-height: 50vh;
            }

            .table-wrap .table-responsive {
                max-height: 50vh;
            }

            .table td, .table th {
                white-space: nowrap;
            }
        }

        .task-cell {
            background: linear-gradient(90deg, rgba(0, 123, 255, .1), rgba(0, 200, 255, .05));
            border-radius: .25rem;
            padding: .25rem .5rem;
            font-size: .8rem;
            line-height: 1.2;
        }

        .task-cell .general-name {
            font-weight: 600;
            color: #0d6efd;
        }

        .task-cell .task-name {
            font-weight: 400;
            color: #333;
        }

        .gradient-table {
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
            border-radius: .5rem;
            overflow: hidden;
        }

        .gradient-table th {
            background-color: rgba(0, 0, 0, .25);
            color: #dee2e6;
            font-size: .8rem;
        }

        .gradient-table td {
            background-color: rgba(255, 255, 255, .02);
            font-size: .85rem;
            vertical-align: middle;
        }

        .task-col {
            font-size: .8rem;
            font-weight: 500;
            color: #f8f9fa;
        }

        .task-col .arrow {
            margin: 0 .25rem;
            color: #adb5bd;
        }

        /* Календарик + галочка */
        .finish-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .5rem center;
            background-size: 1rem 1rem;
            padding-right: 3.5rem;
        }

        .finish-input.has-finish {
            background-color: rgba(25, 135, 84, .1);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E"),
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23198754' viewBox='0 0 16 16'%3E%3Cpath d='M13.485 1.929a.75.75 0 010 1.06L6.818 9.657a.75.75 0 01-1.06 0L2.515 6.414a.75.75 0 111.06-1.06L6 7.778l6.425-6.425a.75.75 0 011.06 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat, no-repeat;
            background-position: right .5rem center, right 2rem center;
            background-size: 1rem 1rem, 1rem 1rem;
        }

        #taskPickerBtn .picked {
            max-width: 55%;
            font-size: .8rem;
            opacity: .95;
            text-align: right;
            direction: rtl;
            unicode-bidi: plaintext;
            color: var(--bs-info);
        }

        @media (max-width: 575.98px) {
            #taskPickerBtn .picked {
                max-width: 60%;
                font-size: .8rem;
            }
        }

        .gradient-top {
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
        }

        #addBtn.btn-success {
            background-color: var(--bs-success) !important;
            border-color: var(--bs-success) !important;
            color: #fff !important;
            border-width: 1px;
        }

        #addBtn.btn-success:focus {
            box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .35);
        }

        #addBtn:not(:disabled) {
            opacity: 1;
        }

        .fp-alt, .finish-input.fp-alt {
            cursor: pointer;
        }

        /* Миниатюры фото в модалке */
        .photo-thumbnail {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }

        .log-entry {
            font-size: .85rem;
        }

        .log-entry .log-meta {
            font-size: .75rem;
            color: #adb5bd;
        }

        .log-entry pre {
            white-space: pre-wrap;
            word-break: break-word;
            font-size: .75rem;
            background: rgba(0, 0, 0, .15);
            padding: .25rem .5rem;
            border-radius: .25rem;
        }

    </style>
@endsection


@section('content')
    <div class="card shadow">
        <div class="card-body">
            <div class="vh-layout">

                {{-- Top --}}
                <div class="top-pane border-info gradient-pane">
                    <div class="row g-3 align-items-stretch">

                        {{-- Manual image --}}
                        <div class="col-12 col-md-2 col-lg-1 d-flex">
                            <div
                                class="card h-100 w-100 bg-dark text-light border-secondary d-flex align-items-center justify-content-center p-3">
                                @if($imgFull)
                                    <a href="{{ $imgFull }}" data-fancybox="wo-manual" title="Manual">
                                        <img class="rounded-circle" src="{{ $imgThumb }}" width="90" height="90"
                                             alt="Manual preview">
                                    </a>
                                @else
                                    <img class="rounded-circle" src="{{ $imgThumb }}" width="80" height="80"
                                         alt="No image">
                                @endif
                            </div>
                        </div>

                        {{-- Main info --}}
                        <div class="col-12 col-md-10 col-lg-11">
                            <div class="card bg-dark text-light border-secondary h-100">
                                <div class="card-body py-3 d-flex flex-column">

                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                        <div class="d-flex flex-wrap align-items-center gap-3">

                                            <h5 class="mb-0 text-info">w {{ $current_workorder->number }}</h5>

                                            @if($current_workorder->approve_at)
                                                <span class="badge bg-success">
                                                    Approved {{ $current_workorder->approve_at?->format('d-M-y') ?? '—' }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">Not approved</span>
                                            @endif

                                            <span class="ms-2 fs-4 me-5"
                                                  data-tippy-content="{{ $current_workorder->description }}"
                                                  style="cursor:help;">&#9432;</span>

                                            {{-- TDR --}}
                                            <a href="{{ route('tdrs.show', $current_workorder->id) }}"
                                               class="btn btn-outline-success ms-5"
                                               data-tippy-content="{{ __('TDR Report') }}"
                                               data-tippy-placement="top"
                                               onclick="showLoadingSpinner()">
                                                <i class="bi bi-hammer" style="font-size:20px; line-height:0;"></i>
                                            </a>

                                            <a class="btn btn-outline-info btn-sm open-photo-modal"
                                               data-tippy-content="{{ __('Pictures') }}"
                                               data-id="{{ $current_workorder->id }}"
                                               data-number="{{ $current_workorder->number }}">
                                                <i class="bi bi-images text-decoration-none"
                                                   style="font-size: 18px"></i>
                                            </a>
                                            @admin
                                            <a class="btn btn-outline-warning btn-sm open-log-modal"
                                               data-tippy-content="{{ __('Logs') }}"
                                               data-url="{{ route('workorders.logs-json', $current_workorder->id) }}">
                                                <i class="bi bi-clock-history" style="font-size: 18px"></i>
                                            </a>
                                            @endadmin
                                        </div>

                                        <div class="small text-end">
                                            <span
                                                class="text-light">{{ $current_workorder->instruction->name ?? '—' }}</span>
                                            <span class="mx-2 text-primary">•</span>
                                            <span class="text-light">{{ $manual->number ?? '—' }}</span>
                                            <span class="mx-2 text-primary">•</span>
                                            <span class="text-light">{{ $current_workorder->description ?? '—' }}</span>
                                            <span class="mx-2 text-primary">•</span>
                                            <span class="text-muted small">Lib:</span>
                                            <span class="text-light">{{ $manual->lib ?? '—' }}</span>
                                            <span class="mx-2 text-primary">•</span>
                                            <span class="text-muted small">Open:</span>
                                            <span
                                                class="text-light">{{ $current_workorder->open_at?->format('d-M-y') ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="row g-3 flex-fill">
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="small d-flex flex-wrap align-items-center gap-2">
                                                    <span class="text-info">Unit Part number:</span>
                                                    <span>{{ $current_workorder->unit->part_number ?? '—' }}</span>

                                                    <span class="text-info">Serial:</span>
                                                    <span>{{ $current_workorder->serial_number ?? ($current_workorder->unit->serial_number ?? '—') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-3 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="small d-flex align-items-center">
                                                    <span class="text-info small me-1">Customer:</span>
                                                    <span>{{ $current_workorder->customer->name ?? '—' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-2 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div><span
                                                        class="text-info small">Technik:</span> {{ $current_workorder->user->name ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-3 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="small d-flex justify-content-between">
                                                    <span class="text-info  me-2">Parts:</span>
                                                    Ordered: <span
                                                        id="orderedQty{{$current_workorder->number}}">{{ $orderedQty ?? 0 }}</span>
                                                    Received: <span
                                                        id="receivedQty{{$current_workorder->number}}">{{ $receivedQty ?? 0 }}</span>
                                                    <button type="button" class="btn btn-success fs-6"
                                                            style="--bs-btn-padding-y: .02rem; --bs-btn-padding-x: 1rem;
                                                            --bs-btn-font-size: .7rem;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#partsModal{{$current_workorder->number}}">
                                                        Parts
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Bottom --}}
                <div class="bottom-row">

                    {{-- Left panel: tasks --}}
                    <div class="bottom-col left gradient-pane border-info">
                        <div class="left-pane">

                            {{-- Add task form --}}
                            <form id="general_task_form" method="POST" action="{{ route('mains.store') }}"
                                  class="w-100">
                                @csrf
                                <input type="hidden" name="workorder_id" value="{{ $current_workorder->id }}">
                                <input type="hidden" name="task_id" id="task_id" value="{{ old('task_id') }}">

                                {{-- Task picker --}}
                                <div class="dropdown mb-2">
                                    <button id="taskPickerBtn"
                                            class="btn btn-outline-primary eqh w-100 d-flex align-items-center justify-content-between dropdown-toggle"
                                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                            aria-expanded="false">
                                        <span>Choose task</span>
                                        <span id="pickedSummary" class="picked text-truncate"></span>
                                    </button>

                                    <div class="dropdown-menu p-3" style="min-width:100%;max-width:100%;">
                                        <div class="row g-3">
                                            <div class="col-5">
                                                <ul class="nav nav-pills flex-column" id="generalTab" role="tablist">
                                                    @foreach ($general_tasks as $general)
                                                        <li class="nav-item">
                                                            <button
                                                                class="nav-link @if($loop->first) active @endif w-100 text-start"
                                                                id="tab-g-{{ $general->id }}"
                                                                data-bs-toggle="pill"
                                                                data-bs-target="#pane-g-{{ $general->id }}"
                                                                type="button" role="tab"
                                                                aria-controls="pane-g-{{ $general->id }}"
                                                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                                data-general-id="{{ $general->id }}">
                                                                {{ $general->name }}
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <div class="col-7">
                                                <div class="tab-content" id="taskTabContent">
                                                    @foreach ($general_tasks as $general)
                                                        <div class="tab-pane fade @if($loop->first) show active @endif"
                                                             id="pane-g-{{ $general->id }}" role="tabpanel"
                                                             aria-labelledby="tab-g-{{ $general->id }}">
                                                            @php
                                                                $group = $tasks->where('general_task_id', $general->id);
                                                            @endphp

                                                            @forelse ($group as $task)
                                                                <button type="button"
                                                                        class="select-task list-group-item list-group-item-action mb-1"
                                                                        data-task-id="{{ $task->id }}"
                                                                        data-task-name="{{ $task->name }}"
                                                                        data-general-id="{{ $general->id }}">
                                                                    {{ $task->name }}
                                                                </button>
                                                            @empty
                                                                <div class="text-muted small">No tasks</div>
                                                            @endforelse
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Row with user + dates + Add btn --}}
                                <div class="row g-2 align-items-stretch">
                                    <div class="col-12 col-sm-6 col-xl-4 border-secondary">
                                        <select name="user_id" class="form-select-sm eqh-sm">
                                            <option value="">Current ({{ auth()->user()->name ?? 'You' }})</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}" @selected(old('user_id')==$u->id)>
                                                    {{ $u->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6 col-xl-3">
                                        <input type="text" data-fp name="date_start"
                                               class="form-control-sm eqh-sm"
                                               value="{{ old('date_start', now()->format('Y-m-d')) }}"
                                               placeholder="Start...">
                                    </div>

                                    <div class="col-6 col-xl-3">
                                        <input type="text" data-fp name="date_finish"
                                               class="form-control-sm eqh-sm"
                                               value="{{ old('date_finish') }}"
                                               placeholder="Finish...">
                                    </div>

                                    <div class="col-12 col-xl-2 d-grid" data-tippy-content="{{ __('Add task') }}">
                                        <button type="submit" id="addBtn"
                                                class="btn-sm btn-success" disabled
                                        >Add</button>
                                    </div>
                                </div>
                            </form>

                            {{-- Tasks table --}}
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle gradient-table table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th class="fw-normal">Technik</th>
                                            <th class="fw-normal">Task</th>
                                            <th class="fw-normal">Start</th>
                                            <th class="fw-normal">Finish (edit)</th>
                                            <th class="text-end fw-normal">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($mains as $m)
                                            <tr id="main-row-{{ $m->id }}">
                                                <td>{{ $m->user->name ?? '—' }}</td>
                                                <td class="task-col text-info">
                                                    {{ $m->task->generalTask->name ?? '—' }}
                                                    <span class="arrow">→</span>
                                                    {{ $m->task->name ?? '—' }}
                                                </td>
                                                <td style="width: 80px">
                                                    {{ optional($m->date_start)->format('d-M-y') }}
                                                </td>
                                                <td style="width:150px;">
                                                    {{-- Форма с автосабмитом --}}
                                                    <form method="POST"
                                                          action="{{ route('mains.update', $m) }}"
                                                          class="auto-submit-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="text"
                                                               name="date_finish"
                                                               class="form-control form-control-sm finish-input"
                                                               value="{{ optional($m->date_finish)->format('Y-m-d') }}"
                                                               placeholder="..."
                                                               data-fp>
                                                    </form>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm btn-icon-compact"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#useConfirmDelete"
                                                            data-action="{{ route('mains.destroy', $m) }}"
                                                            title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-muted">No tasks yet</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Right panel: Components / Processes --}}
                    <div class="bottom-col right border-info gradient-pane">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 text-primary">Components</h6>
                                <span class="text-info">({{ $components->count() }})</span>
                                <h6 class="mb-0 text-primary">&nbsp;& Processes</h6>
                                <span class="badge text-info">{{ $tdrProcessesTotal }} total</span>
                                <span class="badge text-info">{{ $tdrProcessesOpen }} open</span>
                            </div>

                            <form method="get"
                                  action="{{ route('mains.show', $current_workorder->id) }}"
                                  class="d-flex align-items-center gap-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="showAll" name="show_all" value="1"
                                           {{ $showAll ? 'checked' : '' }} autocomplete="off">
                                    <label class="form-check-label small" for="showAll">Show all</label>
                                </div>
                            </form>
                        </div>

                        @if($components->isEmpty())
                            <div class="text-muted small">
                                No components with processes {{ $showAll ? '(all)' : '(open only)' }}.
                            </div>
                        @else
                            <div class="list-group list-group-flush" style="overflow:auto;">
                                @foreach($components as $cmp)
                                    <div class="list-group-item bg-transparent text-light border-secondary">
                                        @forelse($cmp->tdrs as $tdr)
                                            @php $prs = $tdr->tdrProcesses; @endphp
                                            @if($prs->isNotEmpty())
                                                <div class="mt-2 ps-2">
                                                    <table
                                                        class="table table-sm table-dark table-bordered mb-2 align-middle">
                                                        <thead>
                                                        <tr>
                                                            <th style="width:40%;">
                                                                <div class="fw-semibold text-info">
                                                                    {{ $cmp->name ?? ('#'.$cmp->id) }}
                                                                </div>
                                                            </th>
                                                            <th style="width:30%; text-align: center"
                                                                class="fw-normal text-muted">
                                                                Sent (edit)
                                                            </th>
                                                            <th style="width:30%; text-align: center"
                                                                class="fw-normal text-muted">
                                                                Returned (edit)
                                                            </th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($prs as $pr)
                                                            <tr>
                                                                <td>{{ $pr->processName->name ?? '—' }}</td>
                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                                          class="auto-submit-form">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="text" data-fp name="date_start"
                                                                               class="form-control form-control-sm"
                                                                               value="{{ $pr->date_start?->format('Y-m-d') }}"
                                                                               placeholder="...">
                                                                    </form>
                                                                </td>
                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                                          class="auto-submit-form">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="text" data-fp name="date_finish"
                                                                               class="form-control form-control-sm finish-input"
                                                                               value="{{ $pr->date_finish?->format('Y-m-d') }}"
                                                                               placeholder="...">
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        @empty
                                            <div class="text-muted small">
                                                No TDRs for this component on this workorder.
                                            </div>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

            </div>
        </div>
    </div>

    {{-- Форма для delete через модалку (mains / tdrprocesses) --}}
    <form id="deleteForm" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    @include('components.delete')

    <!--  Parts Modal -->
    <div class="modal fade" id="partsModal{{$current_workorder->number}}" tabindex="-1"
         role="dialog" aria-labelledby="orderModalLabel{{$current_workorder->number}}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-gradient" style="width: 900px">
                <div class="modal-header" style="width: 900px">
                    <div class="d-flex ">
                        <h4 class="modal-title">{{__('Work order ')}}{{$current_workorder->number}}</h4>
                        <h4 class="modal-title ms-4">{{__('Extra Parts  ')}}</h4>
                    </div>
                    <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                @if(count($ordersPartsNew))
                    <div class="table-wrapper">
                        <table class="display table table-cm table-hover table-striped align-middle table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-primary  bg-gradient " data-direction="asc">{{__('IPL')}}</th>
                                <th class="text-primary  bg-gradient "
                                    data-direction="asc">{{__('Part Description') }}</th>
                                <th class="text-primary  bg-gradient " style="width: 250px;"
                                    data-direction="asc">{{__('Part Number')}}</th>
                                <th class="text-primary  bg-gradient " data-direction="asc">{{__('QTY')}}</th>
                                <th class="text-primary  bg-gradient ">{{__('PO NO.')}} </th>
                                <th class="text-primary  bg-gradient ">{{__('Received')}}</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($prl_parts as $part)
                                @php
                                    $currentComponent = $part->orderComponent ?? $part->component;
                                @endphp
                                <tr>

                                    <td class="" style="width: 100px"> {{$currentComponent->ipl_num ?? ''}} </td>
                                    <td class="" style="width: 250px"> {{$currentComponent->name ?? ''}} </td>
                                    <td class="" style="width: 120px;"> {{$currentComponent->part_number ?? ''}} </td>
                                    <td class="" style="width: 150px;"> {{$part->qty}} </td>
                                    <td class="" style="width: 150px;">
                                        <div class="po-no-container">
                                            <select class="form-select form-select-sm po-no-select"
                                                    data-tdrs-id="{{ $part->id }}"
                                                    data-workorder-number="{{ $current_workorder->number }}"
                                                    style="width: 100%;">
                                                <option value="">-- Select --</option>
                                                <option
                                                    value="Customer" {{ $part->po_num === 'Customer' ? 'selected' : '' }}>
                                                    Customer
                                                </option>
                                                <option
                                                    value="INPUT" {{ $part->po_num && $part->po_num !== 'Customer' ? 'selected' : '' }}>
                                                    PO No.
                                                </option>
                                            </select>
                                            <input type="text"
                                                   class="form-control form-control-sm po-no-input mt-1"
                                                   data-tdrs-id="{{ $part->id }}"
                                                   data-workorder-number="{{ $current_workorder->number }}"
                                                   placeholder="Po No."
                                                   value="{{ $part->po_num && $part->po_num !== 'Customer' ? $part->po_num : '' }}"
                                                   style="display: {{ $part->po_num && $part->po_num !== 'Customer' ? 'block' : 'none' }};">
                                        </div>
                                    </td>
                                    <td class="" style="width: 150px;">
                                        <input type="date"
                                               class="form-control form-control-sm received-date"
                                               data-tdrs-id="{{ $part->id }}"
                                               data-workorder-number="{{ $current_workorder->number }}"
                                               value="{{ $part->received ? \Carbon\Carbon::parse($part->received)->format('Y-m-d') : '' }}">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <h5 class="text-center mt-3 mb-3 text-primary">{{__('No Ordered Parts')}}</h5>
                @endif


            </div>
        </div>
    </div>



    {{-- Photo modal --}}
    <div class="modal fade" id="photoModal" tabindex="-1"
         aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #343A40">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Photos</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="photoModalContent" class="row g-3"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="saveAllPhotos">Download All</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm delete photo --}}
    <div class="modal fade" id="confirmDeletePhotoModal" tabindex="-1"
         aria-labelledby="confirmDeletePhotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeletePhotoLabel">Confirm Deletion</h5>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this photo?
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancel
                    </button>
                    <button id="confirmPhotoDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="photoDeletedToast"
             class="toast bg-success text-white" role="alert"
             aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                Photo deleted successfully.
            </div>
        </div>
    </div>

    {{-- LOG MODAL --}}
    <div class="modal fade" id="logModal" tabindex="-1"
         aria-labelledby="logModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="background-color:#212529;color:#f8f9fa;">
                <div class="modal-header">
                    <h5 class="modal-title" id="logModalLabel">Activity log</h5>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="logModalContent">
                        {{-- сюда подставится список логов --}}
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // безопасные врапперы вокруг спиннера
            const safeShowSpinner = () => {
                try {
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
                } catch (_) {
                }
            };
            const safeHideSpinner = () => {
                try {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                } catch (_) {
                }
            };

            safeHideSpinner();
            window.addEventListener('pageshow', safeHideSpinner);

            const debounce = (fn, ms) => {
                let t;
                return (...a) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(null, a), ms);
                }
            };

            const form = document.getElementById('general_task_form');
            const taskInput = document.getElementById('task_id');
            const addBtn = document.getElementById('addBtn');
            const pickerBtn = document.getElementById('taskPickerBtn');
            const pickedSummary = document.getElementById('pickedSummary');

            const generalTabs = Array.from(document.querySelectorAll('#generalTab .nav-link[data-general-id]'));
            const taskPanes = Array.from(document.querySelectorAll('#taskTabContent .tab-pane'));
            const taskButtons = Array.from(document.querySelectorAll('.select-task'));

            // ----- Task picker -----
            function showPaneForGeneral(btn) {
                const gid = btn.dataset.generalId;
                generalTabs.forEach(b => b.classList.remove('active'));
                taskPanes.forEach(p => p.classList.remove('show', 'active'));
                btn.classList.add('active');
                const pane = document.getElementById('pane-g-' + gid);
                if (pane) pane.classList.add('active', 'show');
            }

            function generalNameById(gid) {
                const b = document.getElementById('tab-g-' + gid);
                return (b ? b.textContent : '').trim();
            }

            function updatePickedSummary(gName, tName) {
                if (!pickedSummary) return;
                pickedSummary.textContent = (gName && tName) ? `${gName} → ${tName}` : (tName || '');
            }

            function activateAddButton() {
                if (!addBtn) return;
                addBtn.removeAttribute('disabled');
                addBtn.classList.remove('disabled');
            }

            function initTaskPicker() {
                generalTabs.forEach(btn => {
                    btn.addEventListener('mouseenter', () => showPaneForGeneral(btn));
                    btn.addEventListener('click', e => e.preventDefault());
                });

                taskButtons.forEach(item => {
                    item.addEventListener('click', () => {
                        const taskId = item.dataset.taskId;
                        const taskName = item.dataset.taskName;
                        const gid = item.dataset.generalId;

                        if (taskInput) taskInput.value = taskId;
                        updatePickedSummary(generalNameById(gid), taskName);
                        activateAddButton();

                        if (pickerBtn && window.bootstrap?.Dropdown) {
                            const dd = bootstrap.Dropdown.getOrCreateInstance(pickerBtn);
                            dd?.hide();
                        }
                    });
                });

                if (generalTabs[0]) showPaneForGeneral(generalTabs[0]);
                if (taskInput?.value) activateAddButton();
            }

            // ----- submit add task -----
            function bindFormSubmit() {
                if (!form) return;
                form.addEventListener('submit', (e) => {
                    if (!taskInput?.value) {
                        e.preventDefault();
                        alert('Please choose a task first');
                        return;
                    }
                    safeShowSpinner();
                    if (addBtn) {
                        addBtn.setAttribute('disabled', 'disabled');
                        addBtn.classList.add('disabled');
                    }
                });
            }

            // ----- flatpickr для всех input[data-fp] -----
            function initDatePickers() {
                if (typeof flatpickr === 'undefined') return;

                document.querySelectorAll('input[data-fp]').forEach(src => {
                    flatpickr(src, {
                        altInput: true,
                        altFormat: "d-M-y",
                        dateFormat: "Y-m-d",
                        allowInput: true,
                        disableMobile: true,
                        onChange(selectedDates, dateStr, instance) {
                            instance.altInput?.dispatchEvent(new Event('change', {bubbles: true}));
                        },
                        onReady(selectedDates, dateStr, instance) {
                            if (src.getAttribute('placeholder')) {
                                instance.altInput.setAttribute('placeholder', src.getAttribute('placeholder'));
                            }
                            instance.altInput.classList.add('fp-alt');
                            if (src.classList.contains('finish-input')) instance.altInput.classList.add('finish-input');
                            if ((src.name === 'date_finish' || src.classList.contains('finish-input')) && src.value) {
                                instance.altInput.classList.add('has-finish');
                            }
                            src.style.display = 'none';
                            instance.altInput.style.opacity = '1';
                        }
                    });
                });
            }

            // ----- автосабмит дат (левая + правая панель) -----
            function initAutoSubmit() {
                document.querySelectorAll('.auto-submit-form .fp-alt').forEach(inp => {
                    const submitDebounced = debounce(form => form.submit(), 250);
                    inp.addEventListener('change', function () {
                        if (this.name === 'date_finish' || this.classList.contains('finish-input')) {
                            if (this.value) this.classList.add('has-finish', 'is-valid');
                            else {
                                this.classList.remove('has-finish');
                                this.classList.add('is-valid');
                            }
                            setTimeout(() => this.classList.remove('is-valid'), 800);
                        }
                        safeShowSpinner();
                        submitDebounced(this.form);
                    });
                });
            }

            // ----- delete (tasks / mains / tdrprocesses через общий modal) -----
            const modalEl = document.getElementById('useConfirmDelete');
            const confirmBt = document.getElementById('confirmDeleteBtn');
            const delForm = document.getElementById('deleteForm');
            let pendingAction = null;

            modalEl?.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                pendingAction = trigger?.getAttribute('data-action') || null;

                const title = trigger?.getAttribute('data-title') || 'Delete Confirmation';
                const lbl = document.getElementById('confirmDeleteLabel');
                if (lbl) lbl.textContent = title;
            });

            confirmBt?.addEventListener('click', function () {
                if (!pendingAction) return;
                delForm.setAttribute('action', pendingAction);
                safeShowSpinner();
                delForm.submit();
            });

            // ----- Show all components switch -----
            document.getElementById('showAll')?.addEventListener('change', function () {
                safeShowSpinner();
                if (this.form?.requestSubmit) this.form.requestSubmit();
                else this.form?.submit();
            });

            // ===== ЛОГИКА ФОТО =====

            const confirmPhotoBtn = document.getElementById('confirmPhotoDeleteBtn');

            confirmPhotoBtn?.addEventListener('click', async function () {
                const {mediaId, photoBlock} = window.pendingDelete || {};
                if (!mediaId) return;

                safeShowSpinner();

                try {
                    const response = await fetch(`/workorders/photo/delete/${mediaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        if (photoBlock) {
                            photoBlock.style.transition = 'opacity 0.3s ease';
                            photoBlock.style.opacity = '0';
                        }

                        setTimeout(() => {
                            photoBlock?.remove();
                            if (window.currentWorkorderId) {
                                loadPhotoModal(window.currentWorkorderId);
                            }
                        }, 300);

                        const toastEl = document.getElementById('photoDeletedToast');
                        if (toastEl) {
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }
                    } else {
                        alert('Failed to delete photo');
                    }
                } catch (err) {
                    console.error('Delete error:', err);
                    alert('Server error');
                } finally {
                    safeHideSpinner();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeletePhotoModal'));
                    modal?.hide();
                    window.pendingDelete = null;
                }
            });

            // Открытие модалки с фото
            document.querySelectorAll('.open-photo-modal').forEach(button => {
                button.addEventListener('click', async function () {

                    const workorderId = this.dataset.id;
                    const workorderNumber = this.dataset.number;

                    window.currentWorkorderId = workorderId;
                    window.currentWorkorderNumber = workorderNumber;

                    await loadPhotoModal(workorderId);
                    new bootstrap.Modal(document.getElementById('photoModal')).show();
                });
            });

            // загрузка фото
            async function loadPhotoModal(workorderId) {
                const modalContent = document.getElementById('photoModalContent');
                if (!modalContent) return;

                safeShowSpinner();

                try {
                    const response = await fetch(`/workorders/${workorderId}/photos`);

                    if (!response.ok) throw new Error('Response not ok');
                    const data = await response.json();

                    let html = '';
                    ['photos', 'damages', 'logs'].forEach(group => {
                        html += `
                            <div class="col-12">
                                <h6 class="text-primary text-uppercase mt-2">${group}</h6>
                                <div class="row g-2">
                        `;

                        data[group].forEach(media => {
                            html += `
                                <div class="col-4 col-md-2 col-lg-1 photo-item">
                                    <div class="position-relative d-inline-block w-100">
                                        <a data-fancybox="${group}" href="${media.big}" data-caption="${group}">
                                            <img src="${media.thumb}" class="photo-thumbnail border border-primary rounded" />
                                        </a>
                                        <button class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                                            style="top: -6px; right: -6px; width: 20px; height: 20px; z-index: 10;"
                                            data-id="${media.id}" title="Delete">
                                            <i class="bi bi-x" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });

                        html += `</div></div>`;
                    });

                    modalContent.innerHTML = html;
                    bindDeleteButtons();
                } catch (e) {
                    console.error('Load photo error', e);
                    modalContent.innerHTML = '<div class="text-danger">Failed to load photos</div>';
                } finally {
                    safeHideSpinner();
                }
            }

            // навесить обработчики на кнопки удаления
            function bindDeleteButtons() {
                document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const mediaId = this.dataset.id;
                        const photoBlock = this.closest('.photo-item');

                        window.pendingDelete = {mediaId, photoBlock};
                        new bootstrap.Modal(document.getElementById('confirmDeletePhotoModal')).show();
                    });
                });
            }

            // Скачивание ZIP
            document.getElementById('saveAllPhotos')?.addEventListener('click', function () {
                const workorderId = window.currentWorkorderId;
                const workorderNumber = window.currentWorkorderNumber || 'workorder';
                if (!workorderId) return alert('Workorder ID missing');

                safeShowSpinner();

                fetch(`/workorders/download/${workorderId}/all`)
                    .then(response => {
                        if (!response.ok) throw new Error('Download failed');
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `workorder_${workorderNumber}_images.zip`;
                        a.click();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch(err => {
                        console.error('Error downloading ZIP:', err);
                        alert('Download failed');
                    })
                    .finally(() => {
                        safeHideSpinner();
                    });
            });

            // init
            initTaskPicker();
            bindFormSubmit();
            initDatePickers();
            initAutoSubmit();

            // ===== ЛОГИ =====
            document.querySelectorAll('.open-log-modal').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const url = this.dataset.url;
                    await loadLogModal(url);
                    new bootstrap.Modal(document.getElementById('logModal')).show();
                });
            });


            async function loadLogModal(url) {
                const container = document.getElementById('logModalContent');
                if (!container) return;

                container.innerHTML = '<div class="text-muted small">Loading...</div>';

                try {
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                    const resp = await fetch(url, {
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        credentials: 'same-origin'
                    });

                    if (!resp.ok) throw new Error('Response not ok');

                    const data = await resp.json();

                    if (!data || !data.length) {
                        container.innerHTML = '<div class="text-muted small">No log entries for this workorder.</div>';
                        return;
                    }

                    let html = '';

                    data.forEach(item => {
                        const created = item.created_at ?? '';
                        const desc = item.description ?? '';
                        const event = item.event ?? '';
                        const causer = item.causer_name ?? '';
                        const changes = item.changes ?? [];

                        // Цвет и иконки
                        let badgeClass, icon;
                        if (event === 'created') {
                            badgeClass = 'bg-success';
                            icon = '<i class="bi bi-check-circle me-1"></i>';
                        } else if (event === 'updated') {
                            badgeClass = 'bg-warning text-dark';
                            icon = '<i class="bi bi-pencil-square me-1"></i>';
                        } else if (event === 'deleted') {
                            badgeClass = 'bg-danger';
                            icon = '<i class="bi bi-x-circle me-1"></i>';
                        } else {
                            badgeClass = 'bg-secondary';
                            icon = '<i class="bi bi-info-circle me-1"></i>';
                        }

                        html += `
                <div class="p-3 mb-3 border rounded bg-dark bg-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-info">${created}</strong>
                            ${causer ? `<span class="text-muted ms-2">${causer}</span>` : ''}
                        </div>


                        <span class="badge rounded-pill ${badgeClass}">
                        ${icon}${event || 'log'}
                        </span>
                        </div>
                        `;

                        // Показываем description, если это НЕ "created/updated/deleted"
                        if (desc && desc !== event) {
                            html += `<div class="mt-2">${desc}</div>`;
                        }

                        if (changes.length) {
                            html += `<ul class="mt-2 mb-0 ps-3">`;
                            changes.forEach(ch => {
                                html += `
                        <li>
                        <strong>${ch.label}:</strong>
                        <span class="text-danger">${ch.old ?? '—'}</span>
                        <span class="text-muted mx-1">→</span>
                        <span class="text-success">${ch.new ?? '—'}</span>
                        </li>
                        `;
                            });
                            html += `</ul>`;
                        }

                        html += `</div>`;
                    });

                    container.innerHTML = html;

                } catch (e) {
                    console.error('Load log error', e);
                    container.innerHTML = '<div class="text-danger">Failed to load log</div>';
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                }
            }


        });
    </script>
    <script>
        (function () {
            'use strict';

            console.log('Parts Modal JavaScript loaded');

            // Конфигурация
            const CONFIG = {
                debounceDelay: 500,
                modalOpenDelay: 300,
                qtyColumnIndex: 4
            };

            // Утилиты для работы с CSRF токеном
            const TokenUtils = {
                getCsrfToken: function () {
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    return metaTag
                        ? metaTag.getAttribute('content')
                        : '{{ csrf_token() }}';
                }
            };

            // Утилиты для работы с DOM
            const DomUtils = {
                getModal: function (workorderNumber) {
                    return document.getElementById('partsModal' + workorderNumber);
                },

                getReceivedCounter: function (workorderNumber) {
                    return document.getElementById('receivedQty' + workorderNumber);
                },

                getPoNoInput: function (selectElement) {
                    return selectElement.closest('.po-no-container').querySelector('.po-no-input');
                },

                getTableRows: function (modal) {
                    return modal ? modal.querySelectorAll('tbody tr') : [];
                },

                getQtyFromRow: function (row) {
                    const qtyCell = row.querySelector('td:nth-child(' + CONFIG.qtyColumnIndex + ')');
                    return qtyCell ? parseInt(qtyCell.textContent.trim()) || 0 : 0;
                }
            };

            // API для сохранения данных
            const PartsApi = {
                saveField: function (tdrsId, field, value, workorderNumber) {
                    const csrfToken = TokenUtils.getCsrfToken();
                    const url = '{{ route("tdrs.updatePartField", ":id") }}'.replace(':id', tdrsId);

                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            field: field,
                            value: value
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (field === 'received') {
                                    PartsCounter.updateReceivedCount(workorderNumber);
                                }
                                return data;
                            }
                            throw new Error('Save failed');
                        })
                        .catch(error => {
                            console.error('Error saving field:', error);
                            throw error;
                        });
                }
            };

            // Управление счетчиками
            const PartsCounter = {
                updateReceivedCount: function (workorderNumber) {
                    const modal = DomUtils.getModal(workorderNumber);
                    if (!modal) return;

                    const rows = DomUtils.getTableRows(modal);
                    let receivedQty = 0;

                    rows.forEach(function (row) {
                        const receivedInput = row.querySelector('.received-date');
                        if (receivedInput && receivedInput.value) {
                            receivedQty += DomUtils.getQtyFromRow(row);
                        }
                    });

                    const receivedSpan = DomUtils.getReceivedCounter(workorderNumber);
                    if (receivedSpan) {
                        receivedSpan.textContent = receivedQty;
                    }
                }
            };

            // Управление полем PO NO
            const PoNoManager = {
                handleSelectChange: function (selectElement) {
                    const tdrsId = selectElement.getAttribute('data-tdrs-id');
                    const workorderNumber = selectElement.getAttribute('data-workorder-number');
                    const value = selectElement.value;
                    const input = DomUtils.getPoNoInput(selectElement);

                    if (value === 'INPUT') {
                        PoNoManager.showInput(input);
                    } else {
                        PoNoManager.hideInput(input);
                        const saveValue = value === 'Customer' ? 'Customer' : '';
                        PartsApi.saveField(tdrsId, 'po_num', saveValue, workorderNumber);
                    }
                },

                showInput: function (input) {
                    if (input) {
                        input.style.display = 'block';
                        input.focus();
                    }
                },

                hideInput: function (input) {
                    if (input) {
                        input.style.display = 'none';
                        input.value = '';
                    }
                },

                handleInputChange: function (inputElement) {
                    const tdrsId = inputElement.getAttribute('data-tdrs-id');
                    const workorderNumber = inputElement.getAttribute('data-workorder-number');
                    const value = inputElement.value;

                    PoNoDebounceManager.debounceSave(tdrsId, workorderNumber, value);
                }
            };

            // Debounce менеджер для PO NO input
            const PoNoDebounceManager = {
                timeouts: {},

                debounceSave: function (tdrsId, workorderNumber, value) {
                    const timeoutKey = tdrsId + '_' + workorderNumber;

                    if (this.timeouts[timeoutKey]) {
                        clearTimeout(this.timeouts[timeoutKey]);
                    }

                    this.timeouts[timeoutKey] = setTimeout(function () {
                        PartsApi.saveField(tdrsId, 'po_num', value, workorderNumber);
                        delete PoNoDebounceManager.timeouts[timeoutKey];
                    }, CONFIG.debounceDelay);
                }
            };

            // Управление полем Received
            const ReceivedManager = {
                handleDateChange: function (inputElement) {
                    const tdrsId = inputElement.getAttribute('data-tdrs-id');
                    const workorderNumber = inputElement.getAttribute('data-workorder-number');
                    const value = inputElement.value;

                    PartsApi.saveField(tdrsId, 'received', value, workorderNumber);
                }
            };

            // Обработчики событий
            const EventHandlers = {
                handleChange: function (e) {
                    if (e.target.classList.contains('po-no-select')) {
                        PoNoManager.handleSelectChange(e.target);
                    } else if (e.target.classList.contains('received-date')) {
                        ReceivedManager.handleDateChange(e.target);
                    }
                },

                handleInput: function (e) {
                    if (e.target.classList.contains('po-no-input')) {
                        PoNoManager.handleInputChange(e.target);
                    }
                },

                handleModalOpen: function (button) {
                    const target = button.getAttribute('data-bs-target');
                    const workorderNumber = target.replace('#partsModal', '');

                    setTimeout(function () {
                        PartsCounter.updateReceivedCount(workorderNumber);
                    }, CONFIG.modalOpenDelay);
                }
            };

            // Инициализация
            const PartsModal = {
                init: function () {
                    this.attachEventListeners();
                    this.initModalButtons();
                },

                attachEventListeners: function () {
                    document.addEventListener('change', EventHandlers.handleChange);
                    document.addEventListener('input', EventHandlers.handleInput);
                },

                initModalButtons: function () {
                    document.addEventListener('DOMContentLoaded', function () {
                        document.querySelectorAll('[data-bs-target^="#partsModal"]').forEach(function (button) {
                            button.addEventListener('click', function () {
                                EventHandlers.handleModalOpen(this);
                            });
                        });
                    });
                }
            };

            // Запуск при загрузке
            PartsModal.init();

        })();
    </script>

@endsection
