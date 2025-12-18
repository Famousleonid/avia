@extends('admin.master')

@section('style')

    @include('admin.mains.partials.styles')

@endsection

@section('content')

    <div class="card ">
        <div class="card-body p-0 shadow-lg">
            <div class="vh-layout">

                {{-- Top --}}
                <div class="top-pane border-info gradient-pane">
                    <div class="row g-2 align-items-stretch ">

                        {{-- Manual image --}}
                        <div class="col-12 col-md-2 col-lg-1 d-flex">
                            <div
                                class="card h-100 w-100 bg-dark text-light border-secondary d-flex align-items-center justify-content-center p-2">
                                @if($imgFull)
                                    <a href="{{ $imgFull }}" data-fancybox="wo-manual" title="Manual">
                                        <img class="rounded-circle" src="{{ $imgThumb }}" width="70" height="70"
                                             alt="Manual preview">
                                    </a>
                                @else
                                    <img class="rounded-circle" src="{{ $imgThumb }}" width="70" height="70"
                                         alt="No image">
                                @endif
                            </div>
                        </div>

                        {{-- Main info --}}
                        <div class="col-12 col-md-10 col-lg-11">
                            <div class="card bg-dark text-light border-secondary h-100">
                                <div class="card-body py-2 d-flex flex-column mb-1">

                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                        <div class="d-flex flex-wrap align-items-center gap-3">

                                            <h5 class="mb-0 text-white">w {{ $current_workorder->number }}</h5>

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

                                            @if($current_workorder->user->name == auth()->user()->name)
                                                {{-- Training status block --}}
                                                <div class="">
                                                    @if($manual_id)
                                                        <div class="ms-4 fs-8 text-center border rounded  " style="height: 40px;
                                                width: 210px;">
                                                            <div class="ms-1 d-flex justify-content-center">
                                                                @if($trainings && $trainings->date_training && $user->id == $user_wo)
                                                                    @php
                                                                        $trainingDate = \Carbon\Carbon::parse($trainings->date_training);
                                                                        $monthsDiff = $trainingDate->diffInMonths(now());
                                                                        $daysDiff = $trainingDate->diffInDays(now());
                                                                        $isThisMonth = $trainingDate->isCurrentMonth();
                                                                        $isThisYear = $trainingDate->isCurrentYear();
                                                                    @endphp
                                                                    @if($monthsDiff<=12)
                                                                        <div class="d-flex justify-content-center">
                                                                            <div class="pb-0" style="color: lawngreen;">
                                                                                @if($monthsDiff == 0 && $user->id == $user_wo)
                                                                                    @if($isThisMonth)
                                                                                        Last training this month
                                                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                                                    @else
                                                                                        Last training for this unit
                                                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                                                    @endif
                                                                                @elseif($monthsDiff == 1)
                                                                                    @if($user->id == $user_wo)
                                                                                        Last training {{ $monthsDiff }}
                                                                                        month ago
                                                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                                                    @endif
                                                                                @else
                                                                                    @if($monthsDiff >= 6 && $user->id == $user_wo)
                                                                                        Last training {{ $monthsDiff }}
                                                                                        months ago
                                                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                                                    @else
                                                                                        Last training {{ $monthsDiff }}
                                                                                        months ago
                                                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                            @if($monthsDiff >= 6 && $user->id == $user_wo)
                                                                                <div class="text-center ms-2" style="height: 32px;
                                                                        width: 32px">
                                                                                    <button
                                                                                        class="btn mt-1 btn-outline-success btn-sm"
                                                                                        style="height: 32px;width: 32px"
                                                                                        title="{{
                                                                                    __('Update to Today') }}"
                                                                                        onclick="updateTrainingToToday({{ $manual_id }}, '{{ $trainings->date_training }}')">
                                                                                        <i class="bi bi-calendar-check"
                                                                                           style="font-size: 14px;"></i>
                                                                                    </button>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @else
                                                                        <div style="color: red;">
                                                                            Last training {{ $monthsDiff }} months ago
                                                                            ({{ $trainingDate->format('M d, Y') }}).
                                                                            Need Update
                                                                            @if($user->id == $user_wo)
                                                                                <div class="ms-2">
                                                                                    <button
                                                                                        class="btn mt-1 btn-outline-warning btn-sm"
                                                                                        style="height:32px;width: 32px"
                                                                                        title="{{
                                                                                    __('Update to Today') }}"
                                                                                        onclick="updateTrainingToToday({{ $manual_id }}, '{{ $trainings->date_training }}')">
                                                                                        <i class="bi bi-calendar-check"
                                                                                           style="font-size: 14px;"></i>
                                                                                    </button>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                    @if($user->id == $user_wo)
                                                                        <div class="d-flex">
                                                                            <div style="color: red;">
                                                                                There are no trainings
                                                                                <p>for this unit.</p>
                                                                            </div>
                                                                            <div class="ms-2">
                                                                                <button
                                                                                    class=" mt-1 btn btn-outline-primary btn-sm"
                                                                                    style="height: 32px;width: 32px"
                                                                                    title="{{ __
                                                                                ('Create Trainings') }}"
                                                                                    onclick="createTrainings({{ $manual_id }})">
                                                                                    <i class="bi bi-plus-circle" style="font-size: 14px;
                                                                            "></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @admin
                                            <a class="btn btn-outline-warning btn-sm open-log-modal"
                                               data-tippy-content="{{ __('Logs') }}"
                                               data-url="{{ route('workorders.logs-json', $current_workorder->id) }}">
                                                <i class="bi bi-clock-history" style="font-size: 18px"></i>
                                            </a>
                                            @endadmin
                                        </div>

                                    </div>

                                    <div class="row g-2 flex-fill align-items-stretch">

                                        {{-- 1) Unit / Serial / Instruction --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-1 w-100">
                                                <div class="small">
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info ">Unit Part number:</span>
                                                        <span>{{ $current_workorder->unit->part_number ?? '—' }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info me-5">Serial:</span>
                                                        <span
                                                            class="ms-4">{{ $current_workorder->serial_number ?? ($current_workorder->unit->serial_number ?? '—') }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info me-4">Instruction:</span>
                                                        <span
                                                            class="ms-3">{{ $current_workorder->instruction->name ?? '—' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 2) Customer / Technik / Manual --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-1 w-100">

                                                <div class="small">
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info">Customer:</span>
                                                        <span>{{ $current_workorder->customer->name ?? '—' }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info me-3">Technik:</span>
                                                        <span>{{ $current_workorder->user->name ?? '—' }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1 align-items-center flex-wrap">
                                                        <span class="text-info me-3">Manual:</span>
                                                        <span>{{ $manual->number ?? '—' }}</span>
                                                        <span class="text-muted small ms-4"> Lib:</span><span
                                                            class="text-light">{{ $manual->lib ?? '—' }}</span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        {{-- 3) Parts --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-1 w-100">

                                                <div class=" small d-flex align-items-center gap-2 parts-line">
                                                    <span class="text-info me-4">Parts: </span>
                                                    <span class="text-muted ms-3"> Ordered:</span><span
                                                        id="orderedQty{{ $current_workorder->number }}">{{ $orderedQty ?? 0 }}</span>

                                                    <span class="text-muted">Received:</span>
                                                    <span
                                                        id="receivedQty{{ $current_workorder->number }}">{{ $receivedQty ?? 0 }}</span>

                                                    <button type="button"
                                                            class="btn btn-success ms-3"
                                                            style="height: 100%; --bs-btn-padding-y:.02rem; --bs-btn-padding-x:.6rem; --bs-btn-font-size:.7rem;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#partsModal{{ $current_workorder->number }}">
                                                        Parts
                                                    </button>
                                                </div>

                                                <div class="small d-flex gap-1 align-items-center flex-wrap">
                                                    <span class="text-info">Description:</span>&nbsp;
                                                    <span>{{ $current_workorder->description ?? '—' }}</span>
                                                </div>
                                                <div class="small d-flex gap-1 align-items-center flex-wrap">
                                                    <span class="text-info me-4">Opened:</span>
                                                    <span>{{ $current_workorder->open_at?->format('d-M-y') ?? '—' }}</span>
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

                    {{-- Left panel --}}
                    <div class="bottom-col left gradient-pane border-info">
                        <div class="left-pane d-flex flex-column gap-2 h-100">

                            {{-- GeneralTask buttons --}}
                            <div class="d-flex gap-2">
                                @foreach($general_tasks as $i => $gt)
                                    <button type="button"
                                            class="btn flex-fill js-gt-btn {{ ($gtAllFinished[$gt->id] ?? false) ? 'btn-outline-success' : 'btn-outline-danger' }} {{ $i === 0 ? 'active' : '' }}"
                                            data-gt-id="{{ $gt->id }}">
                                        {{ $gt->name }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Tables --}}
                            <div class="flex-grow-1 min-h-0 js-gt-container">

                                @foreach($general_tasks as $i => $gt)

                                    <div class="js-gt-pane h-100 {{ $i === 0 ? '' : 'd-none' }}"
                                         data-gt-id="{{ $gt->id }}">
                                        <div class="table-responsive border border-secondary rounded h-100"
                                             style="overflow:auto;">

                                            <table
                                                class="table table-dark table-hover table-bordered mb-0 align-middle tasks-table mt-4">
                                                <colgroup>
                                                    <col class="col-ignore">
                                                    <col class="col-tech">
                                                    <col class="col-task">
                                                    <col class="col-start">
                                                    <col class="col-finish">
                                                    @role('Admin')
                                                        <col class="col-log">
                                                    @endrole
                                                </colgroup>

                                                <tbody>
                                                @forelse(($tasksByGeneral[$gt->id] ?? collect()) as $task)
                                                    @php
                                                        $main   = $mainsByTask[$task->id] ?? null;
                                                        $action = $main ? route('mains.update', $main->id) : route('mains.store');
                                                        $isWaitingApprove = ($task->name === 'Waiting approve');
                                                    @endphp

                                                    <tr>
                                                        <td class="text-center align-middle">
                                                            <form method="POST"
                                                                  action="{{ $action }}"
                                                                  class="js-auto-submit js-row-form"
                                                                  data-gt-id="{{ $gt->id }}">
                                                                @csrf
                                                                @if($main)
                                                                    @method('PATCH')
                                                                @endif

                                                                @unless($main)
                                                                    <input type="hidden" name="workorder_id" value="{{ $current_workorder->id }}">
                                                                    <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                                @endunless

                                                                {{-- скрытое поле, чтобы всегда было 0/1 --}}
                                                                <input type="hidden"
                                                                       name="ignore_row"
                                                                       value="{{ (int)($main?->ignore_row ?? 0) }}"
                                                                       class="js-ignore-hidden">

                                                                <input class="form-check-input m-0 js-ignore-row"
                                                                       type="checkbox"
                                                                       value="1"
                                                                       {{ ($main?->ignore_row ?? false) ? 'checked' : '' }}
                                                                       title="Ignore this row">
                                                            </form>
                                                        </td>
                                                        <td class="">{{ $main?->user?->name ?? '' }}</td>
                                                        <td class="" title="{{ $task->name }}">{{ $task->name }}</td>

                                                        {{-- Start --}}
                                                        <td>
                                                            @if($task?->task_has_start_date)
                                                                <form method="POST" action="{{ $action }}"
                                                                      class="js-auto-submit">
                                                                    @csrf
                                                                    @if($main)
                                                                        @method('PATCH')
                                                                    @endif

                                                                    @unless($main)
                                                                        <input type="hidden" name="workorder_id"
                                                                               value="{{ $current_workorder->id }}">
                                                                        <input type="hidden" name="task_id"
                                                                               value="{{ $task->id }}">
                                                                    @endunless

                                                                    <input type="text"
                                                                           name="date_start"
                                                                           class="form-control form-control-sm finish-input "
                                                                           value="{{ optional($main?->date_start)->format('Y-m-d') }}"
                                                                           placeholder="..."
                                                                           data-fp>
                                                                    @endif
                                                                </form>
                                                        </td>


                                                        {{-- Finish --}}
                                                        <td>
                                                            <form method="POST"
                                                                  action="{{ $action }}"
                                                                  class="js-auto-submit">
                                                                @csrf
                                                                @if($main)
                                                                    @method('PATCH')
                                                                @endif

                                                                @unless($main)
                                                                    <input type="hidden" name="workorder_id"
                                                                           value="{{ $current_workorder->id }}">
                                                                    <input type="hidden" name="task_id"
                                                                           value="{{ $task->id }}">
                                                                @endunless
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <input type="text"
                                                                           name="date_finish"
                                                                           class="form-control form-control-sm finish-input {{ $isWaitingApprove ? 'noedit' : '' }}"
                                                                           value="{{ optional($main?->date_finish)->format('Y-m-d') }}"
                                                                           placeholder="..."
                                                                           data-fp>

                                                                </div>

                                                            </form>
                                                        </td>

                                                        @role('Admin')
                                                        {{-- Logs --}}
                                                        <td class="text-center">
                                                            @if($main)
                                                                <button type="button"
                                                                        class="btn btn-outline-info btn-sm js-open-log"
                                                                        data-main-id="{{ $main->id }}"
                                                                        data-url="{{ route('mains.activity', $main->id) }}"
                                                                        title="Activity log">
                                                                    <i class="bi bi-journal-text"></i>
                                                                </button>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>
                                                        @endrole
                                                    </tr>

                                                @empty
                                                    <tr>
                                                        <td colspan="5"
                                                            class="text-center text-muted small py-3">
                                                            No tasks
                                                        </td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                @endforeach

                            </div>

                        </div>
                    </div>


                    {{-- LOG MODAL  --}}
                    {{--                    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel"--}}
                    {{--                         aria-hidden="true">--}}
                    {{--                        <div class="modal-dialog modal-lg modal-dialog-scrollable">--}}
                    {{--                            <div class="modal-content" style="background-color:#212529;color:#f8f9fa;">--}}
                    {{--                                <div class="modal-header">--}}
                    {{--                                    <h5 class="modal-title" id="logModalLabel">Activity log</h5>--}}
                    {{--                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"--}}
                    {{--                                            aria-label="Close"></button>--}}
                    {{--                                </div>--}}
                    {{--                                <div class="modal-body">--}}
                    {{--                                    <div id="logModalContent"></div>--}}
                    {{--                                </div>--}}
                    {{--                                <div class="modal-footer">--}}
                    {{--                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>--}}
                    {{--                                </div>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}


                    {{-- Right panel: Components / Processes --}}
                    <div class="bottom-col right border-info gradient-pane">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 text-primary">Components</h6>
                                <span class="text-info">({{ $components->count() }})</span>
                                <h6 class="mb-0 text-primary">&nbsp;& Processes</h6>
                                {{--                                    <span class="badge text-info">{{ $tdrProcessesTotal }} total</span>--}}
                                {{--                                    <span class="badge text-info">{{ $tdrProcessesOpen }} open</span>--}}
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
                                                                <div class=" text-info">
                                                                    {{ $cmp->name ?? ('#'.$cmp->id) }}&nbsp;&nbsp;
                                                                    <span class="text-muted" style="font-size: 12px;">
                                                                        ({{ $cmp->ipl_num ?? '—' }}) &nbsp;&nbsp; p/n: {{ $cmp->part_number ?? '—' }}
                                                                        </span>
                                                                </div>
                                                            </th>
                                                            <th style="width:20%; text-align: center"
                                                                class="fw-normal text-muted">Repair Order
                                                            </th>
                                                            <th style="width:20%; text-align: center"
                                                                class="fw-normal text-muted">Sent (edit)
                                                            </th>
                                                            <th style="width:20%; text-align: center"
                                                                class="fw-normal text-muted">Returned (edit)
                                                            </th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($prs as $pr)
                                                            <tr>
                                                                <td>{{ $pr->processName->name ?? '—' }}</td>
                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateRepairOrder', $pr) }}"
                                                                          class="auto-submit-form auto-submit-order position-relative">
                                                                        @csrf
                                                                        @method('PATCH')

                                                                        <input type="text"
                                                                               name="repair_order"
                                                                               class="form-control form-control-sm pe-4"
                                                                               value="{{ $pr->repair_order ?? '' }}"
                                                                               placeholder="..."
                                                                               autocomplete="off"
                                                                               data-original="{{ $pr->repair_order ?? '' }}">

                                                                        {{-- 💾 индикатор несохранённого --}}
                                                                        <i class="bi bi-save save-indicator d-none"></i>
                                                                    </form>
                                                                </td>
                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                                          class="auto-submit-form">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="text" data-fp name="date_start"
                                                                               class="form-control form-control-sm finish-input"
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

    @include('admin.mains.partials.modals')

@endsection

@section('scripts')

    @include('admin.mains.partials.scripts')

@endsection
