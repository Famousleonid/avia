@extends('mobile.master')

@section('style')
    <style>
        /* =========================================================
           0) Layout wrapper (оставляем как есть)
           ========================================================= */
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

        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

        /* =========================================================
           1) Accordion colors (оставляем)
           ========================================================= */
        .gt-item {
            background: #1f2327;
        }

        .gt-btn {
            background: #1f2327 !important;
            color: #f8f9fa !important;
        }

        .gt-body {
            background: #23272b;
        }

        .task-row-simple {
            background: #2b3035;
        }

        /* =========================================================
           2) Flatpickr: работаем с altInput
           ========================================================= */
        body.fp-ready [data-fp] {
            opacity: 0;
            pointer-events: none;
        }

        .flatpickr-calendar {
            z-index: 2000 !important;
        }

        input::placeholder,
        .flatpickr-input::placeholder {
            color: #6c757d;
            opacity: 1;
        }

        /* =========================================================
           3) Base input (чёрный) — это состояние “пусто”
           ВАЖНО: это применяется и к altInput, и к disabled заглушке
           ========================================================= */
        .fp-alt {
            height: calc(1.8125rem + 2px) !important;
            padding: .25rem .5rem !important;
            line-height: 1.2 !important;

            background-color: #212529 !important;
            color: #f8f9fa !important;

            border: 1px solid #495057 !important;
            border-radius: .375rem !important;
            box-shadow: none !important;
        }

        /* =========================================================
           4) Icons + green ONLY when .date-field.has-finish
           ========================================================= */
        .date-field {
            position: relative;
        }

        /* зелёный + место под иконки (только если есть дата) */
        body.fp-ready .date-field.has-finish .fp-alt {
            background-color: rgba(25, 135, 84, .15) !important;
            border-color: #198754 !important;
            padding-right: 3.2rem !important;
        }

        /* календарик (только если есть дата) */
        body.fp-ready .date-field.has-finish::after {
            content: "\F214"; /* bi-calendar3 */
            font-family: "bootstrap-icons";
            position: absolute;
            right: .6rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #adb5bd;
            pointer-events: none;
        }

        /* галочка (только если есть дата) */
        body.fp-ready .date-field.has-finish::before {
            content: "\F26E"; /* bi-check2 */
            font-family: "bootstrap-icons";
            position: absolute;
            right: 2.0rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #20c997;
            pointer-events: none;
        }

        /* disabled “...” (для пустого Start-слота) */
        .fp-alt:disabled {
            background-color: #212529 !important;
            color: #6c757d !important;
            border-color: #495057 !important;
        }

        .no-collapse-anim {
            transition: none !important;
        }

        .lock-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 2;
        }
    </style>
@endsection


@section('content')
    <div class="tasks-wrapper bg-dark p-0">

        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">
            <div class="d-flex  align-items-center w-100 fw-bold fs-2 ms-3">
                <div class="d-flex align-items-center">
                    @if(!$workorder->isDone())
                        <span class="text-info">W {{ $workorder->number }}</span>
                    @else
                        <span class="text-secondary">{{ $workorder->number }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center ms-3">
                    @if($workorder->approve_at)
                        <img src="{{ asset('img/ok.png') }}" width="20"
                             title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                    @else
                        <img src="{{ asset('img/icon_no.png') }}" width="12">
                    @endif
                </div>
                <div class="d-flex align-items-center ms-auto ">
                    @if($workorder->open_at)
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: {{ $workorder->open_at->format('d-M-Y') }}</span>
                    @else
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: - null - </span>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-2">

        <div class="task-card" id="task-card">

            {{-- ===== ACCORDION: GENERAL TASKS (vertical, full width) ===== --}}
            <div class="accordion gt-acc" id="gtAccordion">

                @foreach($general_tasks as $gt)
                    @php
                        $gtDone = (bool)($gtDoneMap[$gt->id] ?? false); // map из контроллера
                        $gtHeadId = "gt-head-{$gt->id}";
                        $gtColId  = "gt-col-{$gt->id}";
                    @endphp

                    <div class="accordion-item gt-item border border-secondary rounded-2 mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="{{ $gtHeadId }}">
                            <button class="accordion-button collapsed gt-btn  "
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $gtColId }}"
                                    aria-expanded="false"
                                    aria-controls="{{ $gtColId }}">

                                <div class="w-100 d-flex justify-content-between align-items-center gap-2 {{ $gtDone ? 'text-success' : 'text-white' }}">
                                    <div class="fw-semibold">{{ $gt->name }}</div>
                                </div>
                            </button>
                        </h2>

                        <div id="{{ $gtColId }}"
                             class="accordion-collapse collapse"
                             aria-labelledby="{{ $gtHeadId }}"
                             data-bs-parent="#gtAccordion">

                            <div class="accordion-body gt-body">

                                @forelse(($tasksByGeneral[$gt->id] ?? collect()) as $task)
                                    @php
                                        $main = $mainsByTask[$task->id] ?? null;
                                        $action = $main
                                            ? route('mains.update', $main->id)
                                            : route('mains.store');
                                        $isWaitingApprove = ($task->name === 'Approved');
                                        $isCompleteTask   = ($task->name === 'Completed');
                                        $isRestrictedFinish = $isWaitingApprove || $isCompleteTask;
                                        $isIgnored = (bool) ($main?->ignore_row ?? false);
                                        $canEditFinish = !$isRestrictedFinish  || (auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'));
                                    @endphp
                                    <div class="border border-secondary rounded-2 p-2 mb-2 task-row-simple" id="task-{{ $task->id }}">

                                        <div class="fw-semibold mb-2">
                                            {{ $task->name }}
                                        </div>

                                        <div class="row g-2">

                                            {{-- START (или пустое место) --}}
                                            <div class="col-6">
                                                @if($task->task_has_start_date)
                                                    <div class="text-secondary small mb-1">Start</div>
                                                    <form method="POST" action="{{ $action }}" class="js-auto-submit">
                                                        @csrf
                                                        @if($main) @method('PATCH') @endif

                                                        @unless($main)
                                                            <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                                                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                        @endunless
                                                        <div class="date-field">
                                                            <input type="text"
                                                                   name="date_start"
                                                                   class="form-control form-control-sm fp-alt"
                                                                   value="{{ optional($main?->date_start)->format('Y-m-d') }}"
                                                                   placeholder="..."
                                                                   data-fp
                                                                   data-gt="{{ $gt->id }}"
                                                                   data-task="{{ $task->id }}"
                                                                   data-field="date_start"
                                                                   readonly
                                                                   @if($isIgnored) disabled @endif>

                                                        </div>
                                                    </form>
                                                @else
                                                    {{-- ПУСТОЕ МЕСТО под Start --}}
                                                    <div class="start-placeholder"></div>
                                                @endif
                                            </div>

                                            {{-- FINISH (ВСЕГДА справа) --}}
                                            <div class="col-6">
                                                <div class="text-secondary small mb-1">Finish</div>

                                                <form method="POST" action="{{ $action }}" class="js-auto-submit">
                                                    @csrf
                                                    @if($main) @method('PATCH') @endif

                                                    @unless($main)
                                                        <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                    @endunless
                                                    <div class="date-field position-relative">
                                                        <input type="text"
                                                               name="date_finish"
                                                               class="form-control form-control-sm fp-alt"
                                                               value="{{ optional($main?->date_finish)->format('Y-m-d') }}"
                                                               placeholder="..."
                                                               data-fp
                                                               data-gt="{{ $gt->id }}"
                                                               data-task="{{ $task->id }}"
                                                               data-field="date_finish"
                                                               readonly
                                                               @if($isIgnored || !$canEditFinish) disabled @endif>

                                                        @if($isIgnored || !$canEditFinish)
                                                            <span class="lock-icon text-warning"
                                                                  data-tippy-content="Only the manager can edit"><i class="bi bi-lock-fill"></i>
                                                               </span>
                                                        @endif
                                                    </div>

                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <div class="text-center text-muted small py-2">No tasks</div>
                                @endforelse

                            </div>


                        </div>
                    </div>
                @endforeach
            </div> {{-- /general tasks accordion --}}
        </div>
    </div>
@endsection

@section('scripts')
    <script>

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof initDatePickers === 'function') initDatePickers();
        });


        document.addEventListener('shown.bs.collapse', () => {
            if (typeof initDatePickers === 'function') initDatePickers();
        });


    </script>
@endsection
