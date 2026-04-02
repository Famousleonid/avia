@extends('admin.master')

@section('style')

    @include('admin.mains.partials.styles')
    <style>
        .fp-locked {
            background-image: none !important;
            padding-right: 0.5rem !important;
            cursor: not-allowed;
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

        /* default: dropzones look normal */
        .group-dropzone {
            border: 0;
            background: transparent;
            min-height: 90px; /* чтобы было куда дропать даже если пусто */
        }

        /* show zones only while dragging */
        #photoModal.dnd-active .group-dropzone {
            border: 2px dashed rgba(255, 255, 255, .28);
            background: rgba(255, 255, 255, .04);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .45);
        }

        /* hovered zone stronger */
        #photoModal.dnd-active .group-dropzone.drop-hover {
            border-color: rgba(13, 202, 240, .95);
            background: rgba(13, 202, 240, .12);
        }

        .group-dropzone.drop-hover {
            border-color: rgba(13, 202, 240, .95);
            background: rgba(13, 202, 240, .12);
        }

        /* Optional "Drop here" hint */
        .group-dropzone::before {
            content: "Drop here";
            display: block;
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .35);
            margin-bottom: 6px;
        }

        .group-dropzone.drop-hover::before {
            color: rgba(13, 202, 240, .95);
        }

        /* dragging style */
        .photo-item.dragging {
            opacity: .55;
        }

        .group-hr {
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .10);
            opacity: 1;
        }

        .finish-input.fp-locked {
            background-image: none !important;
            padding-right: 2rem !important;
        }

        .std-ignored-row td {
            opacity: .55;
        }

        .std-ignored-row td.std-ignore-cell {
            opacity: 1;
        }

        .std-ignore-cell .form-check-input {
            cursor: pointer !important;
            opacity: 1 !important;
        }

        .std-ignored-row .js-std-editable,
        .std-ignored-row .js-std-editable:disabled,
        .std-ignored-row .flatpickr-input,
        .std-ignored-row .fp-alt {
            cursor: not-allowed !important;
        }

        .fp-alt-wrap {
            position: relative;
        }

        .fp-alt-wrap .fp-cal-btn {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #adb5bd;
            padding: 0;
            line-height: 1;
            cursor: pointer;
        }

        .fp-alt-wrap .fp-cal-btn:hover {
            color: #0dcaf0;
        }

        /* WO bushing: без table-secondary — иначе hover светлый поверх table-dark */
        .wo-bushings-table.table-hover > tbody > tr:hover > td {
            background-color: rgba(255, 255, 255, 0.075) !important;
            color: var(--bs-table-color, #fff);
        }
        .wo-bushings-table tr.wo-bush-batch-row > td {
            background-color: rgba(255, 255, 255, 0.06);
            color: var(--bs-table-color, #fff);
        }
        .wo-bushings-table.table-hover > tbody > tr.wo-bush-batch-row:hover > td {
            background-color: rgba(13, 202, 240, 0.12) !important;
            color: var(--bs-table-color, #fff);
        }

    </style>
@endsection

@section('content')

    <div class="card dir-page">
        <div class="card-body p-0 shadow-lg">
            <div class="vh-layout">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible py-2 px-3 mb-0 rounded-0 border-0 border-bottom border-success">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible py-2 px-3 mb-0 rounded-0 border-0 border-bottom border-danger">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Top --}}
                <div class="top-pane border-info gradient-pane dir-topbar">
                    <div class="row g-2 align-items-stretch">

                        {{-- Manual image --}}
                        <div class="col-12 col-md-2 col-lg-1 d-flex">
                            <div
                                class="card h-100 w-100 bg-dark text-light border-secondary d-flex align-items-center justify-content-center p-1">
                                @php
                                    $manualPreview = $imgThumb ?: asset('img/noimage.png');
                                    $previewHref = $imgFull ?: $manualPreview; // если нет full — открываем то, что показано
                                @endphp
                                <a href="{{ $previewHref }}" data-fancybox="wo-manual" title="Manual">
                                    <img class="rounded-2" src="{{ $manualPreview }}" width="80" height="80"
                                         onerror="this.onerror=null;this.src='{{ asset('img/noimage.png') }}';if(this.closest('a')){this.closest('a').setAttribute('href','{{ asset('img/noimage.png') }}');}"
                                         alt="Manual preview">
                                </a>
                            </div>
                        </div>

                        {{-- Main info --}}
                        <div class="col-12 col-md-10 col-lg-11">
                            <div class="card bg-dark text-light border-secondary h-100">

                                <div class="card-body dir-top-compact d-flex flex-column mb-1">
                                    @php
                                        $unitPn = trim((string)($current_workorder->unit->part_number ?? '—'));
                                        $modTag = trim((string)($current_workorder->modified ?? ''));
                                        $pnValue = $unitPn . ($modTag !== '' ? (' | mod: ' . $modTag) : '');
                                        $serialValue = (string)($current_workorder->serial_number ?? ($current_workorder->unit->serial_number ?? '—'));
                                        $instructionValue = (string)($current_workorder->instruction->name ?? '—');
                                        $customerValue = (string)($current_workorder->customer->name ?? '—');
                                        $technikValue = (string)($current_workorder->user->name ?? '—');
                                        $manualValue = (string)(($manual->number ?? '—') . ' | Lib: ' . ($manual->lib ?? '—'));
                                        $descriptionValue = (string)($current_workorder->description ?? '—');
                                        $openedValue = (string)($current_workorder->open_at?->format('d-M-y') ?? '—');
                                    @endphp

                                    {{-- Compact actions line --}}
                                    <div class="dir-top-actions d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <h5 class="mb-0 text-white">w {{ $current_workorder->number }}</h5>

                                            @if($current_workorder->approve_at)
                                                <span class="badge bg-success me-5">Approved {{ $current_workorder->approve_at?->format('d-M-y') ?? '—' }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark me-5">Not approved</span>
                                            @endif

                                            <div class="d-flex align-items-center gap-2 ms-3">
                                                <a href="{{ route('tdrs.show', ['id' => $current_workorder->id]) }}"
                                                   class="btn btn-outline-success dir-top-square-btn"
                                                   data-tippy-content="{{ __('TDR Report') }}"
                                                   onclick="showLoadingSpinner()">
                                                    <i class="bi bi-hammer"></i>
                                                </a>

                                                <a class="btn btn-outline-info dir-top-square-btn open-photo-modal position-relative ms-2"
                                                   data-tippy-content="{{ __('Pictures') }}"
                                                   data-id="{{ $current_workorder->id }}"
                                                   data-number="{{ $current_workorder->number }}">
                                                    <i class="bi bi-images text-decoration-none"></i>
                                                    @if($photoTotalCount)
                                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info"
                                                              style="font-size:10px; min-width:18px;">
                                                            {{ (int)($photoTotalCount ?? 0) }}
                                                        </span>
                                                    @endif
                                                </a>

                                                @role('Admin')
                                                <a class="btn btn-outline-warning dir-top-square-btn open-log-modal ms-2"
                                                   data-tippy-content="{{ __('Logs') }}"
                                                   data-url="{{ route('workorders.logs-json', $current_workorder->id) }}">
                                                    <i class="bi bi-clock-history"></i>
                                                </a>
                                                @endrole
                                            </div>

                                            <span class="dir-top-desc ms-5 text-white font-bold " style="font-size: 1.3rem"
                                                  data-tippy-content="{{ $descriptionValue }}">
                                                {{ $descriptionValue }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            @if($manual_id && $current_workorder->user)
                                                <x-training-status
                                                    :manual-id="$manual_id"
                                                    :unit="$current_workorder->unit"
                                                    :owner-user="$current_workorder->user"
                                                    :owner-training="$trainingWoLatest"
                                                    :owner-history="$trainingHistoryWo"
                                                    :my-training="$trainingAuthLatest"
                                                    :my-history="$trainingHistoryAuth"
                                                />
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Single info block: 4 equal columns, 2 lines each --}}
                                    <div class="dir-top-info-block border rounded mt-2 p-2">
                                        <div class="dir-top-info-grid">
                                            <div class="dir-top-cell">
                                                <div class="dir-top-line" data-tippy-content="Component PN: {{ $pnValue }}">
                                                    <span class="dir-top-k">Component PN:</span>
                                                    <span class="dir-top-v">{{ $pnValue }}</span>
                                                </div>
                                                <div class="dir-top-line" data-tippy-content="Technik: {{ $technikValue }}">
                                                    <span class="dir-top-k">Technik:</span>
                                                    <span class="dir-top-v">{{ $technikValue }}</span>
                                                </div>
                                            </div>
                                            <div class="dir-top-cell">
                                                <div class="dir-top-line" data-tippy-content="Serial: {{ $serialValue }}">
                                                    <span class="dir-top-k">Serial:</span>
                                                    <span class="dir-top-v">{{ $serialValue }}</span>
                                                </div>
                                                <div class="dir-top-line" data-tippy-content="Customer: {{ $customerValue }}">
                                                    <span class="dir-top-k">Customer:</span>
                                                    <span class="dir-top-v">{{ $customerValue }}</span>
                                                </div>
                                            </div>
                                            <div class="dir-top-cell">
                                                <div class="dir-top-line" data-tippy-content="Instruction: {{ $instructionValue }}">
                                                    <span class="dir-top-k">Instruction:</span>
                                                    <span class="dir-top-v">{{ $instructionValue }}</span>
                                                </div>
                                                <div class="dir-top-line" data-tippy-content="Manual: {{ $manualValue }}">
                                                    <span class="dir-top-k">Manual:</span>
                                                    <span class="dir-top-v">{{ $manualValue }}</span>
                                                </div>
                                            </div>
                                            <div class="dir-top-cell">
                                                <div class="dir-top-line align-items-center"
                                                     data-tippy-content="Parts: Ordered {{ $orderedQty ?? 0 }}, Received {{ $receivedQty ?? 0 }}">
                                                    <span class="dir-top-k">Parts:</span>
                                                    <span class="dir-top-v dir-top-v-fit">Ordered: {{ $orderedQty ?? 0 }} | Received: {{ $receivedQty ?? 0 }}</span>
                                                    <button type="button"
                                                            class="btn btn-success btn-sm ms-0 dir-top-parts-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#partsModal{{ $current_workorder->number }}">
                                                        Parts
                                                    </button>
                                                </div>
                                                <div class="dir-top-line"
                                                     data-tippy-content="Opened: {{ $openedValue }}">
                                                    <span class="dir-top-k">Opened:</span>
                                                    <span class="dir-top-v">{{ $openedValue }}</span>
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
                <div class="bottom-row d-flex align-items-stretch">

                    {{-- Left panel --}}
                    <div class="bottom-col left gradient-pane border-info p-1" >
                        <div class="left-pane d-flex flex-column gap-2 h-100">

                            {{-- GeneralTask buttons --}}
                            <div class="d-flex gap-2">
                                @foreach($general_tasks as $i => $gt)
                                    <button type="button"
                                            class="btn btn-sm flex-fill js-gt-btn {{ ($gtAllFinished[$gt->id] ?? false) ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                            data-gt-id="{{ $gt->id }}">
                                        {{ $gt->name }}
                                    </button>
                                @endforeach
                            </div>

                            <div id="mainLeftLoading" class="main-left-loading">
                                <span class="main-left-loading-dots" aria-label="Loading left panel">
                                    <span class="main-left-loading-dot"></span>
                                    <span class="main-left-loading-dot"></span>
                                    <span class="main-left-loading-dot"></span>
                                </span>
                            </div>

                            {{-- Tables --}}
                            <div class="d-flex flex-column flex-grow-1 min-h-0 js-gt-container" data-wo-id="{{$current_workorder->id}}"
                                 hidden>

                                @foreach($general_tasks as $i => $gt)

                                    <div class="js-gt-pane d-none flex-grow-1 min-h-0 d-flex flex-column"
                                         data-gt-id="{{ $gt->id }}">
                                        <div class="main-gt-scroll-area border border-secondary rounded flex-grow-1 min-h-0">
                                            <div class="d-flex flex-column gap-3 main-gt-scroll-inner min-h-0">

                                            @php
                                                $gtTasks = ($tasksByGeneral[$gt->id] ?? collect());
                                                $showStartCol = $gtTasks->contains(fn($t) => (bool) $t->task_has_start_date);
                                            @endphp

                                            <div class="table-responsive flex-shrink-0">
                                            <table
                                                class="table table-dark table-hover table-bordered mb-0 align-middle tasks-table dir-table mt-1">
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
                                                        <td class="text-center align-middle task-ignore-cell">

                                                            <form method="POST"
                                                                  action="{{ $action }}"
                                                                  class="js-row-form js-main-inline-ajax"
                                                                  data-gt-id="{{ $gt->id }}"
                                                                  data-no-spinner
                                                                  data-success="Ignore state saved">
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

                                                                {{-- hidden всегда 0, а JS (applyIgnoreState) перепишет на 1/0 при клике --}}
                                                                <input type="hidden"
                                                                       name="ignore_row"
                                                                       value="0"
                                                                       class="js-ignore-hidden">
                                                                @if(!$lockDates)
                                                                    <input
                                                                        class="form-check-input m-0 js-ignore-row {{ $isIgnored ? 'is-ignored' : '' }}"
                                                                        type="checkbox"
                                                                        name="ignore_row"
                                                                        value="1"
                                                                        {{ $isIgnored ? 'checked' : '' }}
                                                                        title="Ignore this row">
                                                                @endif
                                                            </form>

                                                        </td>

                                                        {{-- user --}}
                                                        <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }} js-last-user"
                                                            data-tippy-content="
                                                                <span style='color:#adb5bd'>Updated by:</span>
                                                                <span style='color:#0dcaf0;font-weight:500'>
                                                                    {{ $main?->user?->name ?? '—' }}
                                                                </span>
                                                                <br>
                                                                <span style='color:#adb5bd'>Updated at:</span>
                                                                <span style='color:#20c997;font-weight:500'>
                                                                    {{ $main?->updated_at?->format('d-M-Y H:i') ?? '—' }}
                                                                </span>">
                                                            {{ $main?->user?->name ?? '' }}

                                                        </td>

                                                        {{-- task --}}
                                                        <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}"
                                                            title="{{ $main?->task?->name ?? $task->name }}">
                                                             <span class="text-truncate d-inline-block"
                                                                   style="max-width: 200px;">
                                                             {{ $main?->task?->name ?? $task->name }}</span>
                                                        </td>

                                                        {{-- START (если он вообще есть у таска) --}}
                                                        @if($showStartCol)
                                                            <td class="js-fade-on-ignore {{ $isIgnored ? 'is-ignored' : '' }}">
                                                                @if($task->task_has_start_date)
                                                                    <form method="POST"
                                                                          action="{{ $action }}"
                                                                          data-no-spinner
                                                                          class="js-main-inline-ajax"
                                                                          data-gt-id="{{ $gt->id }}"
                                                                          data-success="Start date saved">
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
                                                                        <div class="position-relative">
                                                                            <input type="text"
                                                                                   name="date_start"
                                                                                   class="form-control form-control-sm js-start finish-input
                                                                                          {{ $isIgnored ? 'is-ignored' : '' }}
                                                                                          {{ ($isIgnored || $lockDates) ? 'fp-locked' : '' }}"
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
                                                                        </div>
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
                                                                      data-no-spinner
                                                                      class="js-main-inline-ajax"
                                                                      data-gt-id="{{ $gt->id }}"
                                                                      data-success="Finish date saved"
                                                                      @if($task->id === 10) data-tippy-content="Edit in the Workrorder section"@endif
                                                                >
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
                                                                           name="date_finish"
                                                                           class="form-control form-control-sm js-finish finish-input
                                                                               {{ $isIgnored ? 'is-ignored' : '' }}
                                                                               {{ ($isIgnored || $lockDates) ? 'fp-locked' : '' }}"
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
                                            </div>
                                            {{-- Workorder Notes --}}

                                            <div class="border border-secondary rounded wo-notes-box flex-shrink-0">
                                                {{-- Header --}}
                                                <div class="wo-notes-head">
                                                    <div class="wo-notes-title">Workorder Notes</div>

                                                    <div class="wo-notes-right">
                                                        <div class="wo-notes-hint">autosave on blur / Ctrl+Enter</div>
                                                        <i class="bi bi-save text-warning d-none js-notes-save-indicator"
                                                           title="Unsaved"></i>
                                                        <span
                                                            class="text-muted small d-none js-notes-saving">Saving...</span>
                                                        {{-- 💾 индикатор изменений (появляется только если текст изменён) --}}
                                                        <i class="bi bi-save text-warning d-none js-notes-save-indicator"
                                                           title="Unsaved"></i>
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
                                                                  rows="2"
                                                                  placeholder="Type notes..."
                                                                  data-original="{{ $current_workorder->notes ?? '' }}">{{ $current_workorder->notes ?? '' }}</textarea>
                                                    </form>
                                                </div>
                                            </div>

                                            {{-- WO bushing → процессы (по одной строке на процесс), Qty / RO / даты --}}
                                            <div class="border border-secondary rounded wo-bushings-box d-flex flex-column flex-grow-1">
                                                <div class="wo-notes-head">
                                                    <div class="wo-notes-title small">
                                                        WO bushing → WO bushing process:
                                                        <span class="text-info">{{ $bushingTotalPcs }}</span> pcs.
                                                    </div>
                                                    <div class="wo-notes-right">
                                                        <span class="text-muted small">{{ $bushingProcessGroupedRows->count() }} {{ __('processes') }}</span>
                                                    </div>
                                                </div>

                                                <div class="p-2 pt-1 wo-bushings-list">
                                                    @forelse($bushingProcessSections as $section)
                                                        @if($loop->first)
                                                            <div class="accordion wo-bush-strip-accordion" id="woBushingStripAccordion{{ $gt->id }}">
                                                        @endif
                                                                @php
                                                                    $stripCollapseId = 'woBushStrip_gt'.$gt->id.'_'.$section['group_key'];
                                                                    $stripHeadingId = 'woBushStrip_gt'.$gt->id.'_'.$section['group_key'].'_hdr';
                                                                @endphp
                                                                <div class="accordion-item wo-bush-strip-item border-secondary border-start-0 border-end-0 border-top-0">
                                                                    <h2 class="accordion-header" id="{{ $stripHeadingId }}">
                                                                        @php
                                                                            $stripDone = $section['qty_total'] > 0
                                                                                && (int) $section['finished_total'] === (int) $section['qty_total'];
                                                                        @endphp
                                                                        <button class="accordion-button collapsed wo-bush-strip-btn py-2 px-3 rounded-0"
                                                                                type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#{{ $stripCollapseId }}"
                                                                                aria-expanded="false"
                                                                                aria-controls="{{ $stripCollapseId }}">
                                                                            <span class="wo-bush-strip-btn-inner d-flex align-items-center min-w-0 flex-grow-1 me-2">
                                                                                <span class="wo-bush-strip-title text-truncate">{{ $section['group_label'] }}</span>
                                                                                <span class="wo-bush-strip-count {{ $stripDone ? 'wo-bush-strip-count--done' : '' }}">
                                                                                    <span class="wo-bush-strip-count-a">{{ $section['finished_total'] }}</span><span class="wo-bush-strip-count-sep">/</span><span class="wo-bush-strip-count-b">{{ $section['qty_total'] }}</span>
                                                                                </span>
                                                                            </span>
                                                                        </button>
                                                                    </h2>
                                                                    <div id="{{ $stripCollapseId }}"
                                                                         class="accordion-collapse collapse"
                                                                         aria-labelledby="{{ $stripHeadingId }}"
                                                                         data-bs-parent="#woBushingStripAccordion{{ $gt->id }}">
                                                                        <div class="accordion-body p-2 pt-1">
                                                                            @foreach($section['rows'] as $row)
                                                                                <div class="mb-3 wo-bush-process-block">
                                                                                    @php
                                                                                        $rowDone = $row['total_qty'] > 0
                                                                                            && (int) ($row['finished_qty'] ?? 0) === (int) $row['total_qty'];
                                                                                    @endphp
                                                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                                                        <div class="small text-info text-truncate">{{ $row['process_label'] }}</div>
                                                                                        <div class="d-flex align-items-center gap-2">
                                                                                            <span class="wo-bush-strip-count wo-bush-strip-count--sm {{ $rowDone ? 'wo-bush-strip-count--done' : '' }}">
                                                                                                <span class="wo-bush-strip-count-a">{{ $row['finished_qty'] ?? 0 }}</span><span class="wo-bush-strip-count-sep">/</span><span class="wo-bush-strip-count-b">{{ $row['total_qty'] }}</span>
                                                                                            </span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="table-responsive">
                                                                                        <table class="table table-sm table-dark table-bordered table-hover mb-0 align-middle wo-bushings-table dir-table">
                                                                                            <thead>
                                                                                            <tr>
                                                                                                <th>Part number</th>
                                                                                                <th>IPL</th>
                                                                                                <th>Name</th>
                                                                                                <th>{{ __('Process') }}</th>
                                                                                                <th class="text-center">Qty</th>
                                                                                                <th>Repair order</th>
                                                                                                <th>Sent</th>
                                                                                                <th>Returned</th>
                                                                                            </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                            @php
                                                                                                $batchGroupLabels = [];
                                                                                                $batchGroupCounter = 1;
                                                                                            @endphp
                                                                                            @foreach($row['batches'] as $batch)
                                                                                                @if(!empty($batch['is_batch']))
                                                                                                    @php
                                                                                                        $batchCollapseId = 'woBushBatchCollapse_gt'.$gt->id.'_'.$row['process_group_key'].'_b'.$batch['id'];
                                                                                                        $batchLineCount = count($batch['line_items'] ?? []);
                                                                                                        $currentBatchId = (int) ($batch['id'] ?? 0);
                                                                                                        if ($currentBatchId > 0) {
                                                                                                            if (!isset($batchGroupLabels[$currentBatchId])) {
                                                                                                                $batchGroupLabels[$currentBatchId] = 'Grp '.$batchGroupCounter;
                                                                                                                $batchGroupCounter++;
                                                                                                            }
                                                                                                            $batchGroupLabel = $batchGroupLabels[$currentBatchId];
                                                                                                        } else {
                                                                                                            $batchGroupLabel = 'Grp';
                                                                                                        }
                                                                                                    @endphp
                                                                                                    <tr class="wo-bush-batch-row">
                                                                                                        <td colspan="5"
                                                                                                            class="small align-middle wo-bush-batch-toggle user-select-none"
                                                                                                            style="cursor: pointer;"
                                                                                                            data-bs-toggle="collapse"
                                                                                                            data-bs-target="#{{ $batchCollapseId }}"
                                                                                                            role="button"
                                                                                                            tabindex="0"
                                                                                                            title="{{ __('Click to show bushings in this batch') }}">
                                                                                                            <span class="fw-bold text-uppercase">{{ __('Batch') }} {{ $batchGroupLabel }}</span>
                                                                                                            <span class="text-muted mx-1">·</span>
                                                                                                            <span class="badge bg-info text-dark">{{ $batch['qty'] }} {{ __('pcs') }}</span>
                                                                                                            <span class="text-muted small ms-1">({{ $batchLineCount }} {{ __('lines') }})</span>
                                                                                                            <i class="bi bi-chevron-down small ms-1" aria-hidden="true"></i>
                                                                                                        </td>
                                                                                                        <td class="align-middle" onclick="event.stopPropagation();">
                                                                                                            @hasanyrole('Admin|Manager')
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_batches.updateRepairOrder', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-auto-submit auto-submit-order position-relative js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text"
                                                                                                                       name="repair_order"
                                                                                                                       class="form-control form-control-sm pe-4"
                                                                                                                       value="{{ $batch['repair_order'] ?? '' }}"
                                                                                                                       placeholder="…"
                                                                                                                       autocomplete="off"
                                                                                                                       data-original="{{ $batch['repair_order'] ?? '' }}">
                                                                                                                <i class="bi bi-save save-indicator d-none"></i>
                                                                                                            </form>
                                                                                                            @else
                                                                                                            <input type="text" class="form-control form-control-sm bg-dark" value="{{ $batch['repair_order'] ?? '' }}" readonly>
                                                                                                            @endhasanyrole
                                                                                                        </td>
                                                                                                        <td class="align-middle" onclick="event.stopPropagation();">
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_batches.updateDate', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text" data-fp name="date_start" class="form-control form-control-sm finish-input"
                                                                                                                       value="{{ $batch['date_start']?->format('Y-m-d') }}"
                                                                                                                       data-original="{{ $batch['date_start']?->format('Y-m-d') ?? '' }}"
                                                                                                                       placeholder="…" autocomplete="off">
                                                                                                            </form>
                                                                                                        </td>
                                                                                                        <td class="align-middle" onclick="event.stopPropagation();">
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_batches.updateDate', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text" data-fp name="date_finish" class="form-control form-control-sm finish-input"
                                                                                                                       value="{{ $batch['date_finish']?->format('Y-m-d') }}"
                                                                                                                       data-original="{{ $batch['date_finish']?->format('Y-m-d') ?? '' }}"
                                                                                                                       placeholder="…" autocomplete="off">
                                                                                                            </form>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr class="collapse" id="{{ $batchCollapseId }}">
                                                                                                        <td colspan="8" class="p-0 border-secondary bg-opacity-10" style="background: rgba(0,0,0,.15);">
                                                                                                            <div class="p-2">
                                                                                                                <div class="small text-muted mb-1">{{ __('Bushings in this batch') }} — {{ $batch['qty'] }} {{ __('pcs') }}</div>
                                                                                                                <table class="table table-sm table-dark table-bordered mb-0 wo-bush-batch-nested">
                                                                                                                    <thead>
                                                                                                                    <tr class="small">
                                                                                                                        <th>{{ __('Part number') }}</th>
                                                                                                                        <th>{{ __('IPL') }}</th>
                                                                                                                        <th>{{ __('Name') }}</th>
                                                                                                                        <th>{{ __('Process') }}</th>
                                                                                                                        <th class="text-center">{{ __('Qty') }}</th>
                                                                                                                    </tr>
                                                                                                                    </thead>
                                                                                                                    <tbody>
                                                                                                                    @foreach($batch['line_items'] as $item)
                                                                                                                        <tr>
                                                                                                                            <td class="small">{{ ($item['part_number'] ?? '') !== '' ? $item['part_number'] : '—' }}</td>
                                                                                                                            <td class="small">{{ ($item['ipl_num'] ?? '') !== '' ? $item['ipl_num'] : '—' }}</td>
                                                                                                                            <td class="small">{{ ($item['name'] ?? '') !== '' ? $item['name'] : '—' }}</td>
                                                                                                                            <td class="small text-info">{{ ($item['process_detail'] ?? '') !== '' ? $item['process_detail'] : '—' }}</td>
                                                                                                                            <td class="text-center small">{{ $item['qty'] ?? 0 }}</td>
                                                                                                                        </tr>
                                                                                                                    @endforeach
                                                                                                                    </tbody>
                                                                                                                    @php
                                                                                                                        $lineQtySum = collect($batch['line_items'] ?? [])->sum(fn ($i) => (int) ($i['qty'] ?? 0));
                                                                                                                    @endphp
                                                                                                                    <tfoot>
                                                                                                                    <tr class="small">
                                                                                                                        <th colspan="4" class="text-end text-muted">{{ __('Sum') }}</th>
                                                                                                                        <th class="text-center">{{ $lineQtySum }}</th>
                                                                                                                    </tr>
                                                                                                                    </tfoot>
                                                                                                                </table>
                                                                                                            </div>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @else
                                                                                                    @php $item = $batch['line_items'][0] ?? null; @endphp
                                                                                                    <tr data-bush-line-qty="{{ (int) ($item['qty'] ?? 0) }}">
                                                                                                        <td class="small">{{ ($item['part_number'] ?? '') !== '' ? $item['part_number'] : '—' }}</td>
                                                                                                        <td class="small">{{ ($item['ipl_num'] ?? '') !== '' ? $item['ipl_num'] : '—' }}</td>
                                                                                                        <td class="small">{{ ($item['name'] ?? '') !== '' ? $item['name'] : '—' }}</td>
                                                                                                        <td class="small text-info">{{ ($item['process_detail'] ?? '') !== '' ? $item['process_detail'] : '—' }}</td>
                                                                                                        <td class="text-center">{{ $item['qty'] ?? 0 }}</td>
                                                                                                        <td>
                                                                                                            @hasanyrole('Admin|Manager')
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_processes.updateRepairOrder', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-auto-submit auto-submit-order position-relative js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text"
                                                                                                                       name="repair_order"
                                                                                                                       class="form-control form-control-sm pe-4"
                                                                                                                       value="{{ $batch['repair_order'] ?? '' }}"
                                                                                                                       placeholder="…"
                                                                                                                       autocomplete="off"
                                                                                                                       data-original="{{ $batch['repair_order'] ?? '' }}">
                                                                                                                <i class="bi bi-save save-indicator d-none"></i>
                                                                                                            </form>
                                                                                                            @else
                                                                                                            <input type="text" class="form-control form-control-sm bg-dark" value="{{ $batch['repair_order'] ?? '' }}" readonly>
                                                                                                            @endhasanyrole
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_processes.updateDate', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text" data-fp name="date_start" class="form-control form-control-sm finish-input"
                                                                                                                       value="{{ $batch['date_start']?->format('Y-m-d') }}"
                                                                                                                       data-original="{{ $batch['date_start']?->format('Y-m-d') ?? '' }}"
                                                                                                                       placeholder="…" autocomplete="off">
                                                                                                            </form>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <form method="POST"
                                                                                                                  action="{{ route('wo_bushing_processes.updateDate', $batch['id']) }}"
                                                                                                                  class="auto-submit-form js-ajax"
                                                                                                                  data-no-spinner>
                                                                                                                @csrf
                                                                                                                @method('PATCH')
                                                                                                                <input type="text" data-fp name="date_finish" class="form-control form-control-sm finish-input"
                                                                                                                       value="{{ $batch['date_finish']?->format('Y-m-d') }}"
                                                                                                                       data-original="{{ $batch['date_finish']?->format('Y-m-d') ?? '' }}"
                                                                                                                       placeholder="…" autocomplete="off">
                                                                                                            </form>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endif
                                                                                            @endforeach
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                        @if($loop->last)
                                                            </div>
                                                        @endif
                                                    @empty
                                                        <div class="text-muted small">No bushings for this workorder.</div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            </div>
                                        </div>
                                    </div>

                                @endforeach

                            </div>

                        </div>
                    </div>

                    {{-- Right panel: Components / Processes --}}
                    <div class="bottom-col right border-info gradient-pane p-1">

                        <div class="d-flex align-items-center justify-content-between mb-2">

                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 text-primary">Parts</h6>
                                <span class="text-info">({{ $components->count() }})</span>
                                <h6 class="mb-0 text-primary">&nbsp;& Repair Processes</h6>
                            </div>



                            <div class="form-check form-switch" data-no-spinner>
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="showAll"
                                       autocomplete="off"
                                       data-no-spinner>

                                <label class="form-check-label small"
                                       for="showAll"
                                       data-no-spinner>
                                    Show all
                                </label>
                            </div>

                        </div>

                        @if($stdListTdrProcesses && $stdListTdrProcesses->isNotEmpty())
                            <div class="req_standart mb-2 w-100">
                                <table class="table table-sm table-dark table-bordered table-hover mb-0 align-middle dir-table">
                                    <thead>
                                    <tr>
                                        <th style="width:6%; text-align:center" class="fw-normal text-muted small">I</th>
                                        <th style="width:12%; text-align:center" class="fw-normal text-muted small">Technik</th>
                                        <th style="width:18%;" class="fw-normal text-muted small">List</th>
                                        <th style="width:22%; text-align: center"
                                            class="fw-normal text-muted small">Repair Order
                                        </th>
                                        <th style="width:21%; text-align: center"
                                            class="fw-normal text-muted small">Sent (edit)
                                        </th>
                                        <th style="width:21%; text-align: center"
                                            class="fw-normal text-muted small">Returned (edit)
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $stdRows = [
                                            'ndt' => 'NDT list',
                                            'cad' => 'CAD list',
                                            'stress' => 'Stress relief list',
                                            'paint' => 'Paint list',
                                        ];
                                    @endphp
                                    @foreach($stdRows as $key => $label)
                                        @php $pr = $stdListTdrProcesses->get($key); @endphp
                                        @if($pr)
                                            @php
                                                $isClosed = !empty($pr->date_finish);
                                                $isIgnoredStd = (bool) ($pr->ignore_row ?? false);
                                            @endphp
                                            <tr data-closed="{{ $isClosed ? 1 : 0 }}" data-std-row="1"
                                                class="{{ $isIgnoredStd ? 'text-muted std-ignored-row' : '' }}">
                                                <td class="text-center align-middle std-ignore-cell">
                                                    @if(in_array($key, ['ndt', 'cad', 'stress', 'paint'], true))
                                                        <form method="POST"
                                                              action="{{ route('tdrprocesses.updateIgnoreRow', $pr) }}"
                                                              class="js-ajax d-inline"
                                                              data-success="Row ignored"
                                                              data-no-spinner>
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="ignore_row" value="0">
                                                            <input type="checkbox"
                                                                   name="ignore_row"
                                                                   value="1"
                                                                   class="form-check-input m-0 js-std-ignore-row"
                                                                   {{ $pr->ignore_row ? 'checked' : '' }}
                                                                   onchange="window.applyStdIgnoreState?.(this);">
                                                        </form>
                                                    @endif
                                                </td>
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
                                                <td>
                                                    <span class="text-info">{{ $label }}</span>
                                                </td>
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
                                                               class="form-control form-control-sm pe-4 js-std-editable {{ $isIgnoredStd ? 'bg-dark text-secondary' : '' }}"
                                                               value="{{ $pr->repair_order ?? '' }}"
                                                               placeholder="..."
                                                               autocomplete="off"
                                                               data-original="{{ $pr->repair_order ?? '' }}"
                                                               @if($isIgnoredStd) disabled @endif>
                                                        <i class="bi bi-save save-indicator d-none"></i>
                                                    </form>
                                                    @else
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
                                                               class="form-control form-control-sm finish-input js-std-editable {{ $isIgnoredStd ? 'is-ignored' : '' }}"
                                                               value="{{ $pr->date_start?->format('Y-m-d') }}"
                                                               data-original="{{ $pr->date_start?->format('Y-m-d') ?? '' }}"
                                                               placeholder="..."
                                                               @if($isIgnoredStd) disabled @endif>
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
                                                               class="form-control form-control-sm finish-input js-std-editable {{ $isIgnoredStd ? 'is-ignored' : '' }}"
                                                               value="{{ $pr->date_finish?->format('Y-m-d') }}"
                                                               data-original="{{ $pr->date_finish?->format('Y-m-d') ?? '' }}"
                                                               placeholder="..."
                                                               @if($isIgnoredStd) disabled @endif>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($components->isEmpty())
                            <div class="text-muted small">
                                No components with processes.
                            </div>
                        @else
                            <div class="list-group list-group-flush" style="overflow:auto;">
                                @foreach($components as $cmp)
                                    <div class="list-group-item bg-transparent text-light border-secondary p-0">
                                        @forelse($cmp->tdrs as $tdr)
                                            @php
                                                $prs = $tdr->tdrProcesses->filter(function ($p) {
                                                    return optional($p->processName)->show_in_process_picker !== false
                                                        && !((bool) ($p->ignore_row ?? false));
                                                });
                                            @endphp
                                            @if($prs->isNotEmpty())
                                                <div class="mt-2 ps-2">
                                                    <table
                                                        class="table table-sm table-dark table-bordered table-hover mb-2 align-middle dir-table">
                                                        <thead>
                                                        <tr>
                                                            <th style="width:10%; text-align:center"
                                                                class="fw-normal text-muted">Technik
                                                            </th>
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
                                                            @php
                                                                $isClosed = !empty($pr->date_finish);
                                                            @endphp

                                                            <tr data-closed="{{ $isClosed ? 1 : 0 }}">
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
                        <table class="table table-dark table-bordered align-middle dir-table mb-0">
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

    <script>
        window.aiCurrentWorkorder = {
            id: {{ (int)($current_workorder->id ?? 0) }},
            number: {{ (int)($current_workorder->number ?? 0) }},
            manual_id: {{ (int)($manual_id ?? 0) }}
        };
    </script>

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
            window.applyStdIgnoreState = function (checkbox) {
                const tr = checkbox?.closest?.('tr');
                if (!tr) return;

                const isIgnored = !!checkbox.checked;
                tr.classList.toggle('std-ignored-row', isIgnored);
                tr.classList.toggle('text-muted', isIgnored);

                tr.querySelectorAll('.js-std-editable').forEach((el) => {
                    el.disabled = isIgnored;
                    if (el._flatpickr?.altInput) {
                        el._flatpickr.altInput.readOnly = isIgnored;
                        el._flatpickr.altInput.classList.toggle('fp-locked', isIgnored);
                        el._flatpickr.altInput.style.cursor = isIgnored ? 'not-allowed' : '';
                    }
                });
            };

            document.querySelectorAll('.js-std-ignore-row').forEach((cb) => {
                window.applyStdIgnoreState(cb);
            });

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

            // 4) focusout — сохранить repair_order (blur не всплывает; focusout — да, надёжнее для делегирования)
            document.addEventListener('focusout', (e) => {
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

            // WO bushing accordion: после раскрытия полосы прокручиваем ВНЕШНИЙ скролл-контейнер к низу
            // раскрытого блока (там, где даты), чтобы не крутить колесо вручную.
            document.addEventListener('shown.bs.collapse', (e) => {
                const collapseEl = e.target;
                if (!collapseEl?.classList?.contains('accordion-collapse')) return;
                if (!collapseEl.closest('.wo-bush-strip-accordion')) return;

                const scroller = collapseEl.closest('.main-gt-scroll-area');
                if (!scroller) return;

                const scrollToBottom = () => {
                    const lastRow =
                        collapseEl.querySelector('.wo-bush-process-block:last-child tr:last-child') ||
                        collapseEl.querySelector('.wo-bush-process-block:last-child') ||
                        collapseEl;

                    if (lastRow?.scrollIntoView) {
                        lastRow.scrollIntoView({ behavior: 'smooth', block: 'end', inline: 'nearest' });
                    }
                    if (typeof scroller.scrollTo === 'function') {
                        scroller.scrollTo({ top: scroller.scrollHeight, behavior: 'smooth' });
                    } else {
                        scroller.scrollTop = scroller.scrollHeight;
                    }
                };

                // На некоторых браузерах высота дочерних таблиц догружается чуть позже.
                requestAnimationFrame(scrollToBottom);
                setTimeout(scrollToBottom, 120);
                setTimeout(scrollToBottom, 260);
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
    <script>
        document.addEventListener('click', (e) => {
            const isShowAll = e.target?.closest?.('#showAll') || e.target?.closest?.('label[for="showAll"]');
            if (!isShowAll) return;

            // ✅ убиваем глобальные click-спиннеры (data-spinner/.press-spinner)
            e.stopImmediatePropagation();
        }, true); // capture
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const STORAGE_KEY = 'avia_show_all_right';
            const checkbox = document.getElementById('showAll');
            if (!checkbox) return;

            function applyFilter(showAll) {
                const rightPanel = document.querySelector('.bottom-col.right');
                if (!rightPanel) return;

                rightPanel.querySelectorAll('tr[data-closed]').forEach(tr => {
                    if (tr.dataset.stdRow === '1') {
                        tr.style.display = '';
                        return;
                    }
                    const isClosed = tr.dataset.closed === '1';
                    tr.style.display = (!showAll && isClosed) ? 'none' : '';
                });
            }

            // restore
            const saved = localStorage.getItem(STORAGE_KEY);
            const showAll = saved === '1';
            checkbox.checked = showAll;
            applyFilter(showAll);

            // ✅ реагируем на реальное изменение чекбокса
            checkbox.addEventListener('input', () => {
                const state = checkbox.checked;
                localStorage.setItem(STORAGE_KEY, state ? '1' : '0');
                applyFilter(state);
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            async function submitMainInlineForm(form) {
                if (!form || form.dataset.sending === '1') return;

                form.dataset.sending = '1';

                try {
                    const formData = new FormData(form);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    let data = null;
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        data = await response.json();
                    }

                    if (!response.ok) {
                        let errorMessage = 'Save error';

                        if (data?.message) {
                            errorMessage = data.message;
                        } else if (data?.errors) {
                            const firstKey = Object.keys(data.errors)[0];
                            if (firstKey && data.errors[firstKey]?.[0]) {
                                errorMessage = data.errors[firstKey][0];
                            }
                        }

                        throw new Error(errorMessage);
                    }

                    refreshFinishInputState(form, data);
                    applyGeneralTaskButtonState(form, data);

                    form.querySelectorAll('input[type="text"], textarea').forEach(input => {
                        input.setAttribute('data-original', input.value ?? '');
                    });

                    const successText = data?.message || form.getAttribute('data-success') || 'Saved';

                    if (typeof showNotification === 'function') {
                        showNotification(successText, 'success', 2500);

                    }

                } catch (error) {
                    if (typeof showNotification === 'function') {
                        showNotification(error.message || 'Save error', 'error', 4000);
                    }
                    console.error(error);
                } finally {
                    delete form.dataset.sending;
                }
            }

            function refreshFinishInputState(form, responseData = null) {
                if (!form) return;

                form.querySelectorAll('input.finish-input').forEach(input => {
                    let value = input.value ?? '';

                    if (responseData) {
                        if (input.name === 'date_start' && typeof responseData.date_start !== 'undefined') {
                            value = responseData.date_start ?? '';
                            input.value = value;
                        }

                        if (input.name === 'date_finish' && typeof responseData.date_finish !== 'undefined') {
                            value = responseData.date_finish ?? '';
                            input.value = value;
                        }
                    }

                    input.classList.toggle('has-finish', String(value).trim() !== '');
                });
            }

            function applyGeneralTaskButtonState(form, responseData = null) {
                const raw = responseData?.general_task_all_finished;
                if (raw === undefined || raw === null) return;
                const allFinished = raw === true || raw === 1 || raw === '1' || raw === 'true';

                let gtId = form?.dataset?.gtId;
                if (!gtId) {
                    gtId = form?.closest?.('.js-gt-pane')?.dataset?.gtId;
                }
                if (!gtId) return;

                const btn = document.querySelector(`.js-gt-btn[data-gt-id="${gtId}"]`);
                if (!btn) return;
                btn.classList.toggle('btn-outline-success', allFinished);
                btn.classList.toggle('btn-outline-danger', !allFinished);
            }

            // submit
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form?.classList?.contains('js-main-inline-ajax')) return;

                e.preventDefault();
                if (form.dataset.sending === '1') return;
                submitMainInlineForm(form);
            }, true);

            // change для дат
            document.addEventListener('change', (e) => {
                const input = e.target;
                if (!input?.closest) return;

                if (
                    input.matches('input[name="date_start"]') ||
                    input.matches('input[name="date_finish"]')
                ) {
                    const form = input.closest('form.js-main-inline-ajax');
                    if (!form) return;
                    if (form.dataset.sending === '1') return;

                    const original = input.getAttribute('data-original') ?? '';
                    const current = input.value ?? '';

                    if (original === current) return;

                    e.preventDefault();
                    submitMainInlineForm(form);
                }
            }, true);

            // blur для дат
            document.addEventListener('blur', (e) => {
                const input = e.target;
                if (!input?.closest) return;

                if (
                    input.matches('input[name="date_start"]') ||
                    input.matches('input[name="date_finish"]')
                ) {
                    const form = input.closest('form.js-main-inline-ajax');
                    if (!form) return;
                    if (form.dataset.sending === '1') return;

                    const original = input.getAttribute('data-original') ?? '';
                    const current = input.value ?? '';

                    if (original === current) return;

                    submitMainInlineForm(form);
                }
            }, true);

            // Enter для дат
            document.addEventListener('keydown', (e) => {
                const input = e.target;
                if (!input?.closest) return;

                if (
                    e.key === 'Enter' &&
                    (
                        input.matches('input[name="date_start"]') ||
                        input.matches('input[name="date_finish"]')
                    )
                ) {
                    const form = input.closest('form.js-main-inline-ajax');
                    if (!form) return;

                    e.preventDefault();
                    submitMainInlineForm(form);
                }
            }, true);

            // ignore_row
            document.addEventListener('change', (e) => {
                const checkbox = e.target;
                if (!checkbox?.classList?.contains('js-ignore-row')) return;

                const form = checkbox.closest('form.js-main-inline-ajax');
                if (!form) return;
                if (form.dataset.sending === '1') return;

                const hidden = form.querySelector('.js-ignore-hidden');
                if (hidden) {
                    hidden.value = checkbox.checked ? '1' : '0';
                }

                submitMainInlineForm(form);
            }, true);
        });
    </script>

@endsection
