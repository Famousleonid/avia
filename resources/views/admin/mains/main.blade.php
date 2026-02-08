@extends('admin.master')

@section('style')

    @include('admin.mains.partials.styles')

@endsection

@section('content')

    <style>
        .fp-locked {
            background-image: none !important;
            padding-right: .5rem !important;
            cursor: not-allowed;
        }
        .training-status{
            width: 300px;
            height: 70px;                 /* одинаковая высота */
            padding: 8px 10px;
            border-color: #495057 !important;
            background: rgba(0,0,0,.15);
            flex-shrink: 0;

            display: flex;
            flex-direction: column;
            justify-content: flex-start;    /* вертикально центрируем контент */
            gap: 6px;
        }

        .training-status .training-row{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .training-status .small{
            font-size: 11px;
        }

        .training-status .text-warning.small{
            font-size: 11px;
        }

        .training-status button{
            margin-top: 2px !important;
        }
        .training-status .training-header{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .training-status .training-user{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }

        .training-status .training-status-text{
            white-space: nowrap;          /* одна строка */
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            padding-bottom: 1px;
            font-size: 12px;
        }

        .tippy-box[data-theme~='avia-dark'] {
            border: 1px solid #ADB1B5;
        }

        .tippy-box[data-theme~='avia-dark'] .tippy-content {
            padding: 8px 10px;
        }

        .tippy-box[data-theme~='avia-dark'] .tippy-arrow:before {
            color: #ADB1B5; /* фон стрелки под тёмный */
        }
    </style>

    <div class="card dir-page">
        <div class="card-body p-0 shadow-lg">
            <div class="vh-layout">

                {{-- Top --}}
                <div class="top-pane border-info gradient-pane dir-topbar">
                    <div class="row g-2 align-items-stretch">

                        {{-- Manual image --}}
                        <div class="col-12 col-md-2 col-lg-1 d-flex">
                            <div class="card h-100 w-100 bg-dark text-light border-secondary d-flex align-items-center justify-content-center p-2">
                                @php
                                    $previewHref = $imgFull ?: $imgThumb; // если нет full — открываем то, что показано
                                @endphp

                                <a href="{{ $previewHref }}" data-fancybox="wo-manual" title="Manual">
                                    <img class="rounded-circle" src="{{ $imgThumb }}" width="70" height="70" alt="Manual preview">
                                </a>
                            </div>
                        </div>

                        {{-- Main info --}}
                        <div class="col-12 col-md-10 col-lg-11">
                            <div class="card bg-dark text-light border-secondary h-100">

                                <div class="card-body py-2 d-flex flex-column mb-1">

                                    {{-- TOP LINE: left info + right trainings --}}
                                    <div class="d-flex align-items-start justify-content-between mb-3 gap-3">

                                        {{-- LEFT: number / badges / buttons --}}
                                        <div class="d-flex flex-wrap align-items-center gap-3">

                                            <h5 class="mb-0 text-white">w {{ $current_workorder->number }}</h5>

                                            @if($current_workorder->approve_at)
                                                <span class="badge bg-success">
                                    Approved {{ $current_workorder->approve_at?->format('d-M-y') ?? '—' }}
                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">Not approved</span>
                                            @endif

                                            <span class="ms-2 fs-4 me-3"
                                                  data-tippy-content="{{ $current_workorder->description }}"
                                                  style="cursor:help;">&#9432;</span>

                                            {{-- TDR --}}
                                            <a href="{{ route('tdrs.show', $current_workorder->id) }}"
                                               class="btn btn-outline-success"
                                               data-tippy-content="{{ __('TDR Report') }}"
                                               onclick="showLoadingSpinner()">
                                                <i class="bi bi-hammer" style="font-size:20px; line-height:0;"></i>
                                            </a>

                                            {{-- Pictures --}}
                                            <a class="btn btn-outline-info btn-sm open-photo-modal"
                                               data-tippy-content="{{ __('Pictures') }}"
                                               data-id="{{ $current_workorder->id }}"
                                               data-number="{{ $current_workorder->number }}">
                                                <i class="bi bi-images text-decoration-none" style="font-size:18px"></i>
                                            </a>

                                            {{-- Logs --}}
                                            @role('Admin')
                                            <a class="btn btn-outline-warning btn-sm open-log-modal"
                                               data-tippy-content="{{ __('Logs') }}"
                                               data-url="{{ route('workorders.logs-json', $current_workorder->id) }}">
                                                <i class="bi bi-clock-history" style="font-size:18px"></i>
                                            </a>
                                            @endrole

                                        </div>

                                        {{-- RIGHT: trainings (ONE LINE) --}}
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">

                                            @if($manual_id)
                                                <x-training-status
                                                    :training-user="auth()->user()"
                                                    :training="$trainingAuthLatest"
                                                    :manual-id="$manual_id"
                                                    :history="$trainingHistoryAuth"
                                                    :is-owner="true"
                                                />
                                            @endif

                                            @if($manual_id && $current_workorder->user)
                                                <x-training-status
                                                    :training-user="$current_workorder->user"
                                                    :training="$trainingWoLatest"
                                                    :manual-id="$manual_id"
                                                    :history="$trainingHistoryWo"
                                                    :is-owner="false"
                                                />
                                            @endif

                                        </div>
                                    </div>

                                    {{-- SECOND LINE: three info blocks --}}
                                    <div class="row g-2 flex-fill align-items-stretch">

                                        {{-- 1) Unit / Serial / Instruction --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-1 w-100">
                                                <div class="small">
                                                    <div class="d-flex gap-2">
                                                        <span class="text-info">Component PN:</span>
                                                        <span>{{ $current_workorder->unit->part_number ?? '—' }}</span>
                                                        @if($current_workorder->modified)
                                                            <span>&nbsp;<span class="text-muted">mod: </span>{{ $current_workorder->modified }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info">Serial number:</span>
                                                        <span class="ms-4">{{ $current_workorder->serial_number ?? ($current_workorder->unit->serial_number ?? '—') }}</span>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="text-info me-4">Instruction:</span>
                                                        <span class="ms-3">{{ $current_workorder->instruction->name ?? '—' }}</span>
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
                                                        <span class="text-muted small ms-4">Lib:</span>
                                                        <span class="text-light">{{ $manual->lib ?? '—' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 3) Parts --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-1 w-100">
                                                <div class="small d-flex align-items-center gap-2 parts-line">
                                                    <span class="text-info me-4">Parts:</span>

                                                    <span class="text-muted ms-3">Ordered:</span>
                                                    <span id="orderedQty{{ $current_workorder->number }}">{{ $orderedQty ?? 0 }}</span>

                                                    <span class="text-muted">Received:</span>
                                                    <span id="receivedQty{{ $current_workorder->number }}">{{ $receivedQty ?? 0 }}</span>

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

                                </div>{{-- card-body --}}
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
                                            class="btn flex-fill js-gt-btn {{ ($gtAllFinished[$gt->id] ?? false) ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                            data-gt-id="{{ $gt->id }}">
                                        {{ $gt->name }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Tables --}}
                            <div class="flex-grow-1 min-h-0 js-gt-container" data-wo-id="{{$current_workorder->id}}" hidden>

                                @foreach($general_tasks as $i => $gt)

                                    <div class="js-gt-pane h-100 d-none"
                                         data-gt-id="{{ $gt->id }}">
                                        <div class="table-responsive border border-secondary rounded h-100"
                                             style="overflow:auto;">

                                            @php
                                                $gtTasks = ($tasksByGeneral[$gt->id] ?? collect());
                                                $showStartCol = $gtTasks->contains(fn($t) => (bool) $t->task_has_start_date);
                                            @endphp

                                            <table
                                                class="table table-dark table-hover table-bordered mb-0 align-middle tasks-table mt-1">
                                                <colgroup>
                                                    <col class="col-ignore">
                                                    <col class="col-tech">
                                                    <col class="col-task">
                                                    @if($showStartCol)
                                                        <col class="col-start">
                                                    @endif
                                                    <col class="col-finish">
                                                    @role('Admin')
                                                    <col class="col-log">
                                                    @endrole
                                                </colgroup>
                                                <tbody>
                                                @forelse(($tasksByGeneral[$gt->id] ?? collect()) as $task)
                                                    @php
                                                        $restrictedDateTaskIds = config('mains.restricted_date_task_ids', []);

                                                        $isRestrictedByConfig = in_array($task->id, $restrictedDateTaskIds, true);
                                                        $canEditRestrictedDates = auth()->check() && auth()->user()->hasAnyRole('Admin|Manager');

                                                        $lockDates = $isRestrictedByConfig && !$canEditRestrictedDates;

                                                        $main   = $mainsByTask[$task->id] ?? null;
                                                        $action = $main ? route('mains.update', $main->id) : route('mains.store');
                                                        $isIgnored = (bool) ($main?->ignore_row ?? false);

                                                    @endphp

                                                    <tr class="align-middle">
                                                        {{-- чекбокс ignore --}}
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

                                                                {{-- hidden всегда 0, а JS (applyIgnoreState) перепишет на 1/0 при клике --}}
                                                                <input type="hidden"
                                                                       name="ignore_row"
                                                                       value="0"
                                                                       class="js-ignore-hidden">
                                                                @if(!$lockDates)
                                                                    <input class="form-check-input m-0 js-ignore-row {{ $isIgnored ? 'is-ignored' : '' }}"
                                                                           type="checkbox"
                                                                           name="ignore_row"
                                                                           value="1"
                                                                           {{ $isIgnored ? 'checked' : '' }}
                                                                           title="Ignore this row">
                                                                @endif
                                                            </form>

                                                        </td>

                                                        {{-- user --}}
                                                        <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ $main?->user?->name ?? '' }}"
                                                        >
                                                            {{ $main?->user?->name ?? '' }}

                                                        </td>

                                                        {{-- task --}}
                                                        <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}"
                                                            title="{{ $main?->task?->name ?? $task->name }}">
                                                             <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                                             {{ $main?->task?->name ?? $task->name }}</span>
                                                        </td>

                                                        {{-- START (если он вообще есть у таска) --}}
                                                        @if($showStartCol)
                                                            <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}">
                                                                @if($task->task_has_start_date)
                                                                    <form method="POST"
                                                                          action="{{ $action }}"
                                                                          class="js-auto-submit">
                                                                        @csrf
                                                                        @if($main)
                                                                            @method('PATCH')
                                                                        @endif

                                                                        @unless($main)
                                                                            <input type="hidden" name="workorder_id" value="{{ $current_workorder->id }}">
                                                                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                                        @endunless

                                                                        <input type="text"
                                                                               name="date_start"
                                                                               class="form-control form-control-sm js-start finish-input {{ $isIgnored ? 'is-ignored' : '' }}"
                                                                               value="{{ optional($main?->date_start)->format('Y-m-d') }}"
                                                                               placeholder="..."
                                                                               data-fp
                                                                               @if($isIgnored || $lockDates) disabled @endif>
                                                                        @if($isIgnored || $lockDates)
                                                                            <span class="lock-icon text-warning"
                                                                                  data-tippy-content="{{ $lockDates ? 'Only Admin/Manager can edit this date' : 'Row ignored' }}">
                                                                                  <i class="bi bi-lock-fill"></i>
                                                                            </span>
                                                                        @endif
                                                                    </form>
                                                                @else
                                                                    {{-- пусто, но ячейка есть, чтобы сетка была --}}
                                                                    <span class="text-muted small">—</span>
                                                                @endif
                                                            </td>
                                                        @endif


                                                        {{-- FINISH --}}
                                                        <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}">
                                                            <div class="position-relative d-inline-block w-100">
                                                                <form method="POST"
                                                                      action="{{ $action }}"
                                                                      class="js-auto-submit"
                                                                      @if($task->id === 10) data-tippy-content="Edit in the Workrorder section"@endif
                                                                >
                                                                    @csrf
                                                                    @if($main)
                                                                        @method('PATCH')
                                                                    @endif

                                                                    @unless($main)
                                                                        <input type="hidden" name="workorder_id" value="{{ $current_workorder->id }}">
                                                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                                    @endunless

                                                                    <input type="text"
                                                                           name="date_finish"
                                                                           class="form-control form-control-sm js-finish finish-input {{ $isIgnored ? 'is-ignored' : '' }}"
                                                                           value="{{ optional($main?->date_finish)->format('Y-m-d') }}"
                                                                           placeholder="..."
                                                                           data-fp

                                                                           @if($isIgnored || $lockDates) disabled @endif
                                                                           @if($task->id === 10) data-fp-locked @endif>

                                                                    @if($isIgnored || $lockDates)
                                                                        <span class="lock-icon text-warning"
                                                                              data-tippy-content="Only the manager can edit">
                                                                              <i class="bi bi-lock-fill"></i>
                                                                        </span>
                                                                    @endif

                                                                </form>
                                                            </div>
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
                                            {{-- Workorder Notes --}}

                                            <div class="mt-3 border border-secondary rounded wo-notes-box">
                                                {{-- Header --}}
                                                <div class="wo-notes-head">
                                                    <div class="wo-notes-title">Workorder Notes</div>

                                                    <div class="wo-notes-right">
                                                        <div class="wo-notes-hint">autosave on blur / Ctrl+Enter</div>
                                                        <i class="bi bi-save text-warning d-none js-notes-save-indicator" title="Unsaved"></i>
                                                        <span class="text-muted small d-none js-notes-saving">Saving...</span>
                                                        {{-- 💾 индикатор изменений (появляется только если текст изменён) --}}
                                                        <i class="bi bi-save text-warning d-none js-notes-save-indicator" title="Unsaved"></i>
                                                        @hasanyrole('Admin|Manager')
                                                        {{-- кнопка логов --}}
                                                        <button type="button"
                                                                class="btn btn-outline-info btn-sm js-open-wo-notes-log"
                                                                data-url="{{ route('workorders.notes.logs', $current_workorder) }}"
                                                                title="Notes log">
                                                            <i class="bi bi-clock-history"></i>
                                                        </button>
                                                        @endhasanyrole
                                                    </div>
                                                </div>

                                                {{-- Body --}}
                                                <div class="p-2 pt-1">
                                                    <form method="POST"
                                                          action="{{ route('workorders.notes.update', $current_workorder) }}"
                                                          class="js-ajax"
                                                          data-no-spinner>
                                                        @csrf
                                                        @method('PATCH')

                                                        <textarea name="notes"
                                                                  class="form-control form-control-sm bg-dark text-light border-secondary wo-notes-textarea"
                                                                  rows="4"
                                                                  placeholder="Type notes..."
                                                                  data-original="{{ $current_workorder->notes ?? '' }}">{{ $current_workorder->notes ?? '' }}</textarea>
                                                    </form>
                                                </div>
                                            </div>


                                        </div>
                                    </div>

                                @endforeach

                            </div>

                        </div>
                    </div>

                    {{-- Right panel: Components / Processes --}}
                    <div class="bottom-col right border-info gradient-pane">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 text-primary">Parts</h6>
                                <span class="text-info">({{ $components->count() }})</span>
                                <h6 class="mb-0 text-primary">&nbsp;& Repair Processes</h6>
                            </div>

                            <form method="get"
                                  action="{{ url()->current() }}"
                                  class="d-flex align-items-center gap-2">
                                @foreach(request()->except('show_all') as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <div class="form-check form-switch">

                                    {{-- всегда отправляем 0, если чекбокс снят --}}
                                    <input type="hidden" name="show_all" value="0">

                                    {{-- если чекбокс включён, уйдёт show_all=1 и перекроет 0 --}}
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="showAll"
                                           name="show_all"
                                           value="1"
                                           {{ $showAll ? 'checked' : '' }}
                                           autocomplete="off"
                                           onclick="this.form.submit()">

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
                                                            <th style="width:10%; text-align:center" class="fw-normal text-muted">Technik</th>
                                                            <th style="width:30%;">
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
                                                                <td class="text-center small text-info js-last-user"
                                                                    data-tippy-content="
                                                                    <span style='color:#adb5bd'>Updated by:</span>
                                                                    <span style='color:#0dcaf0;font-weight:500'>
                                                                        {{ $pr->updatedBy?->name ?? '—' }}
                                                                        </span>
                                                                        <br>
                                                                        <span style='color:#adb5bd'>Updated at:</span>
                                                                        <span style='color:#20c997;font-weight:500'>
                                                                        {{ $pr->updated_at?->format('d-M-Y H:i') ?? '—' }}
                                                                        </span>">
                                                                    {{ $pr->updatedBy?->name ?? '—' }}
                                                                </td>
                                                                <td>{{ $pr->processName->name ?? '—' }}</td>

                                                                <td>
                                                                    @hasanyrole('Admin|Manager')
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateRepairOrder', $pr) }}"
                                                                          class="auto-submit-form js-auto-submit auto-submit-order position-relative js-ajax"
                                                                          data-no-spinner>
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
                                                                    @else
                                                                        {{-- только просмотр --}}
                                                                        <input type="text"
                                                                               class="form-control form-control-sm pe-4 bg-dark"
                                                                               value="{{ $pr->repair_order ?? '' }}"
                                                                               readonly>
                                                                        @endhasanyrole
                                                                </td>

                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                                          class="auto-submit-form js-ajax"
                                                                          data-no-spinner>
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="text" data-fp name="date_start"
                                                                               class="form-control form-control-sm finish-input"
                                                                               value="{{ $pr->date_start?->format('Y-m-d') }}"
                                                                               data-original="{{ $pr->date_start?->format('Y-m-d') ?? '' }}"
                                                                               placeholder="...">
                                                                    </form>
                                                                </td>
                                                                <td>
                                                                    <form method="POST"
                                                                          action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                                          class="auto-submit-form js-ajax"
                                                                          data-no-spinner>
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="text" data-fp name="date_finish"
                                                                               class="form-control form-control-sm finish-input"
                                                                               value="{{ $pr->date_finish?->format('Y-m-d') }}"
                                                                               data-original="{{ $pr->date_finish?->format('Y-m-d') ?? '' }}"
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

    {{-- modal log notes --}}
    <div class="modal fade" id="woNotesLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title">Workorder Notes Log</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered align-middle mb-0">
                            <thead>
                            <tr class="text-muted small">
                                <th style="width: 270px;">Date</th>
                                <th style="width: 280px;">User</th>
                                <th style="width: 35%;">Old</th>
                                <th style="width: 35%;">New</th>
                            </tr>
                            </thead>
                            <tbody id="woNotesLogTbody">
                            <tr>
                                <td colspan="4" class="text-muted small">Loading...</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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

    {{--  Общие --}}
    @include('admin.mains.partials.js.mains-common')

    {{--  GeneralTask, Task, date, ignore_row --}}
    @include('admin.mains.partials.js.mains-general-tasks')

    {{-- Photo and Logs --}}
    @include('admin.mains.partials.js.mains-photos-logs')

    {{-- Parts / PO / TDRS / Training --}}
    @include('admin.mains.partials.js.mains-parts-training')

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // 1) submit
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form?.classList?.contains('js-ajax')) return;

                e.preventDefault();
                if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
            }, true);

            // 2) change — для дат и прочего
            document.addEventListener('change', (e) => {
                const input = e.target;
                if (!input?.closest) return;

                const form = input.closest('form.js-ajax');
                if (!form) return;

                if (input.name === 'repair_order') return;
                if (input.name === 'notes') return;

                if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
            }, true);

            // 3) input — индикатор 💾 для repair_order
            document.addEventListener('input', (e) => {
                const input = e.target;
                if (!input || !input.closest) return;

                if (input?.name !== 'repair_order') return;

                const form = input.closest?.('form.js-ajax');
                if (!form) return;

                const original = input.getAttribute('data-original') ?? '';
                const icon = form.querySelector('.save-indicator');
                if (!icon) return;

                icon.classList.toggle('d-none', (input.value ?? '') === original);
            }, true);

            // 4) blur — сохранить repair_order
            document.addEventListener('blur', (e) => {
                const input = e.target;
                if (!input || !input.closest) return;
                if (input?.name !== 'repair_order') return;

                const form = input.closest?.('form.js-ajax');
                if (!form) return;

                const original = input.getAttribute('data-original') ?? '';
                if ((input.value ?? '') === original) return;

                if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
            }, true);

            // 5) Enter в repair_order — сохранить
            document.addEventListener('keydown', (e) => {
                const input = e.target;
                if (input?.name !== 'repair_order') return;

                if (e.key === 'Enter') {
                    e.preventDefault();

                    const form = input.closest?.('form.js-ajax');
                    if (!form) return;

                    const original = input.getAttribute('data-original') ?? '';
                    if ((input.value ?? '') === original) return;

                    if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
                }
            }, true);

            document.addEventListener('click', async (e) => {
                const btn = e.target.closest('.js-open-wo-notes-log');
                if (!btn) return;

                const url = btn.getAttribute('data-url');
                const tbody = document.getElementById('woNotesLogTbody');
                if (!url || !tbody) return;

                tbody.innerHTML = `<tr><td colspan="4" class="text-muted small">Loading...</td></tr>`;

                try {
                    const res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    const json = await res.json();

                    const rows = (json?.data || []);
                    if (!rows.length) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-muted small">No logs</td></tr>`;
                    } else {
                        tbody.innerHTML = rows.map(r => `
                <tr>
                    <td class="text-muted small">${escapeHtml(r.date ?? '')}</td>
                    <td class="text-info small">${escapeHtml(r.user ?? '')}</td>
                    <td><div class="small" style="white-space:pre-wrap">${escapeHtml(r.old ?? '')}</div></td>
                    <td><div class="small" style="white-space:pre-wrap">${escapeHtml(r.new ?? '')}</div></td>
                </tr>
            `).join('');
                    }

                    const modalEl = document.getElementById('woNotesLogModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();

                } catch (err) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-danger small">Error loading logs</td></tr>`;
                }
            }, true);

// маленький helper
            function escapeHtml(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }


        });
    </script>



@endsection
