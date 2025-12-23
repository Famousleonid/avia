@extends('mobile.master')

@section('style')
    <style>
        .tasks-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            padding: 5px;
        }

        .task-card {
            position: relative;
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            background-color: #2b3035;
            border-radius: 10px;
            border: 1px solid #495057;
            padding: 12px;
            color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        .gradient-pane { background: #343A40; color: #f8f9fa; }

        /* ===== different bg for GeneralTask vs Task ===== */
        .gt-item { background:#1f2327; }
        .gt-btn  { background:#1f2327 !important; color:#f8f9fa !important; }
        .gt-body { background:#23272b; }

        .task-item { background:#2b3035; }
        .task-btn  { background:#2b3035 !important; color:#f8f9fa !important; }
        .task-body { background:#262a2f; }

        .meta { font-size: .8rem; }
        .badge-status { font-size: .7rem; }

        .task-dates-line{
            font-size:.78rem;
            color:#adb5bd;
            margin-top:.25rem;
            display:flex;
            justify-content:space-between;
            gap:.75rem;
            white-space:nowrap;
        }
        .task-dates-line span{
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .lock-icon{
            position:absolute;
            top:50%;
            right:8px;
            transform:translateY(-50%);
            pointer-events:none;
            opacity:.9;
        }
    </style>
@endsection

@section('content')
    <div class="tasks-wrapper bg-dark p-0">

        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">
            <div class="d-flex justify-content-between align-items-center w-100 fw-bold fs-2 ms-3">
                @if(!$workorder->isDone())
                    <span class="text-info">W {{ $workorder->number }}</span>
                @else
                    <span class="text-secondary">{{ $workorder->number }}</span>
                @endif

                @if($workorder->open_at)
                    <span class="text-secondary fw-normal fs-6 me-4">
                    Open at: {{ $workorder->open_at->format('d-M-Y') }}
                </span>
                @endif
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-2">

        <div class="task-card" id="task-card">

            {{-- ===== ACCORDION: GENERAL TASKS (vertical, full width) ===== --}}
            <div class="accordion gt-acc" id="gtAccordion">

                @foreach($general_tasks as $gt)
                    @php
                        $gtDone   = (bool)($gtAllFinished[$gt->id] ?? false);

                        $gtHeadId = "gt-head-{$gt->id}";
                        $gtColId  = "gt-col-{$gt->id}";
                        $gtAccId  = "task-acc-gt-{$gt->id}";
                    @endphp

                    <div class="accordion-item gt-item border border-secondary rounded-2 mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="{{ $gtHeadId }}">
                            <button class="accordion-button collapsed gt-btn"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $gtColId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $gtColId }}">

                                <div class="w-100 d-flex justify-content-between align-items-center gap-2">
                                    <div class="fw-semibold">{{ $gt->name }}</div>
                                    <span class="badge rounded-pill {{ $gtDone ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $gtDone ? 'Done' : 'Open' }}
                                </span>
                                </div>
                            </button>
                        </h2>

                        <div id="{{ $gtColId }}"
                             class="accordion-collapse collapse"
                             aria-labelledby="{{ $gtHeadId }}"
                             data-bs-parent="#gtAccordion">

                            <div class="accordion-body gt-body">

                                {{-- ===== ACCORDION: TASKS inside GeneralTask ===== --}}
                                <div class="accordion accordion-flush task-acc" id="{{ $gtAccId }}">

                                    @forelse(($tasksByGeneral[$gt->id] ?? collect()) as $task)
                                        @php
                                            $main = $mainsByTask[$task->id] ?? null;

                                            // action must exist (no undefined var)
                                            $action = $main
                                                ? route('mains.update', $main->id)
                                                : route('mains.store');

                                            $isWaitingApprove   = ($task->name === 'Waiting approve');
                                            $isCompleteTask     = ($task->name === 'Completed');
                                            $isRestrictedFinish = $isWaitingApprove || $isCompleteTask;

                                            $isIgnored     = (bool)($main?->ignore_row ?? false);
                                            $canEditFinish = !$isRestrictedFinish || (auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'));

                                            $status = $isIgnored ? 'Ignored' : (!empty($main?->date_finish) ? 'Done' : 'Open');

                                            $tHeadId = $gtAccId."-head-".$task->id;
                                            $tColId  = $gtAccId."-col-".$task->id;

                                            $startTxt  = $main?->date_start  ? $main->date_start->format('d.m.Y')  : '—';
                                            $finishTxt = $main?->date_finish ? $main->date_finish->format('d.m.Y') : '—';
                                        @endphp

                                        <div class="accordion-item task-item border border-secondary rounded-2 mb-2 overflow-hidden">

                                            <h2 class="accordion-header" id="{{ $tHeadId }}">
                                                <button class="accordion-button collapsed task-btn"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $tColId }}"
                                                        aria-expanded="false"
                                                        aria-controls="{{ $tColId }}">

                                                    <div class="w-100">
                                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                                            <div class="fw-semibold">
                                                                {{ $main?->task?->name ?? $task->name }}
                                                            </div>

                                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                            <span class="badge rounded-pill badge-status
                                                                {{ $status === 'Done' ? 'text-bg-success' : ($status === 'Ignored' ? 'text-bg-secondary' : 'text-bg-warning') }}">
                                                                {{ $status }}
                                                            </span>

                                                                @if($isIgnored || !$canEditFinish)
                                                                    <span class="text-warning" title="Only manager can edit">
                                                                    <i class="bi bi-lock-fill"></i>
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- 2nd line in header: user + dates --}}
                                                        <div class="task-dates-line">
                                                            <span>{{ $main?->user?->name ?? '—' }}</span>
                                                            <span>Start: {{ $startTxt }}</span>
                                                            <span>Finish: {{ $finishTxt }}</span>
                                                        </div>
                                                    </div>

                                                </button>
                                            </h2>

                                            <div id="{{ $tColId }}"
                                                 class="accordion-collapse collapse"
                                                 aria-labelledby="{{ $tHeadId }}"
                                                 data-bs-parent="#{{ $gtAccId }}">

                                                <div class="accordion-body task-body">

                                                    {{-- ignore + log --}}
                                                    <div class="d-flex align-items-center justify-content-between mb-2">

                                                        <form method="POST"
                                                              action="{{ $action }}"
                                                              class="js-auto-submit js-row-form"
                                                              data-gt-id="{{ $gt->id }}">
                                                            @csrf
                                                            @if($main) @method('PATCH') @endif

                                                            @unless($main)
                                                                <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                                                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                            @endunless

                                                            <input type="hidden" name="ignore_row" value="0" class="js-ignore-hidden">

                                                            <label class="d-flex align-items-center gap-2">
                                                                <input class="form-check-input m-0 js-ignore-row"
                                                                       type="checkbox"
                                                                       name="ignore_row"
                                                                       value="1"
                                                                    {{ $isIgnored ? 'checked' : '' }}>
                                                                <span class="small text-secondary">Ignore row</span>
                                                            </label>
                                                        </form>

                                                        @role('Admin')
                                                        @if($main)
                                                            <button type="button"
                                                                    class="btn btn-outline-info btn-sm js-open-log"
                                                                    data-url="{{ route('mains.activity', $main->id) }}"
                                                                    title="Activity log">
                                                                <i class="bi bi-journal-text"></i>
                                                            </button>
                                                        @endif
                                                        @endrole

                                                    </div>

                                                    {{-- dates inputs --}}
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <div class="text-secondary small mb-1">Start</div>

                                                            @if($task->task_has_start_date)
                                                                <form method="POST" action="{{ $action }}" class="js-auto-submit">
                                                                    @csrf
                                                                    @if($main) @method('PATCH') @endif

                                                                    @unless($main)
                                                                        <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                                                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                                    @endunless

                                                                    <input type="text"
                                                                           name="date_start"
                                                                           class="form-control form-control-sm"
                                                                           value="{{ optional($main?->date_start)->format('Y-m-d') }}"
                                                                           placeholder="—"
                                                                           data-fp
                                                                           @if($isIgnored) disabled @endif>
                                                                </form>
                                                            @else
                                                                <div class="text-muted small">—</div>
                                                            @endif
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="text-secondary small mb-1">Finish</div>

                                                            <div class="position-relative">
                                                                <form method="POST" action="{{ $action }}" class="js-auto-submit">
                                                                    @csrf
                                                                    @if($main) @method('PATCH') @endif

                                                                    @unless($main)
                                                                        <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                                                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                                    @endunless

                                                                    <input type="text"
                                                                           name="date_finish"
                                                                           class="form-control form-control-sm"
                                                                           value="{{ optional($main?->date_finish)->format('Y-m-d') }}"
                                                                           placeholder="—"
                                                                           data-fp
                                                                           @if($isIgnored || !$canEditFinish) disabled @endif>
                                                                </form>

                                                                @if($isIgnored || !$canEditFinish)
                                                                    <span class="lock-icon text-warning" title="Only manager can edit">
                                                                    <i class="bi bi-lock-fill"></i>
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>
                                    @empty
                                        <div class="text-center text-muted small py-2">No tasks</div>
                                    @endforelse

                                </div>
                                {{-- /tasks accordion --}}

                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
            {{-- /general tasks accordion --}}

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // re-init flatpickr after expanding any accordion section
        document.addEventListener('shown.bs.collapse', () => {
            if (typeof initDatePickers === 'function') initDatePickers();
        });

        // open first GeneralTask by default (optional)
        document.addEventListener('DOMContentLoaded', () => {
            const first = document.querySelector('#gtAccordion .accordion-button');
            if (first) first.click();
        });
    </script>
@endsection
