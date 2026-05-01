@extends('mobile.master')

@section('style')
    <style>
        .machining-mobile-wrap {
            padding: 0;
        }
        .machining-mobile-card {
            background: rgba(20, 24, 28, .9);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .35rem;
            padding: .35rem;
            margin: 0;
        }
        .machining-detail-wo-title {
            font-size: .95rem;
            font-weight: 600;
            color: #e9ecef;
            margin-bottom: .35rem;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: .5rem;
        }
        .machining-detail-wo-title .machining-detail-wo-machinist {
            font-weight: 400;
            min-width: 0;
            max-width: 60%;
            text-align: right;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .machining-action-row .btn {
            font-size: .78rem;
        }
        .machining-detail-block {
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: .3rem;
            padding: .45rem .4rem;
            margin-bottom: .5rem;
            background: rgba(0, 0, 0, .2);
        }
        .machining-detail-block:last-child {
            margin-bottom: 0;
        }
        .machining-detail-line {
            font-size: .72rem;
            line-height: 1.2;
            word-break: break-word;
            margin-bottom: .35rem;
        }
        .machining-detail-line .text-secondary {
            font-size: .66rem;
        }
        .machining-detail-dates-step {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .35rem .6rem;
            font-size: .72rem;
            margin-bottom: .1rem;
            align-items: center;
        }
        .machining-detail-dates-step .k {
            color: #9fb0c0;
        }
        .machining-detail-dates-step .v {
            color: #e9ecef;
        }
        .machining-detail-dates-step .step-finish-form-wrap {
            min-width: 0;
        }
        .machining-mobile-date {
            min-width: 0;
            white-space: nowrap;
        }
        .machining-detail-dates-step .machining-mobile-date .js-mobile-date-display {
            max-width: 100%;
        }
        .machining-mobile-date .js-mobile-date-display {
            width: 100%;
            max-width: 11rem;
            font-size: .72rem;
            padding: .1rem .25rem;
            height: calc(1.25em + .25rem + 2px);
            text-align: center;
        }
        .machining-mobile-date .js-mobile-date-display.has-finish {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }
        .machining-detail-processes {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-size: .68rem;
            line-height: 1.25;
            color: #aab9c6;
            text-align: left;
        }
        .machining-detail-parent-desc {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 4;
            line-clamp: 4;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-size: .67rem;
            line-height: 1.3;
            color: #c5d0dc;
            text-align: left;
        }
    </style>
@endsection

@section('content')
    @php
        $detailItems = $detailItems ?? collect();
        $machiningStepMachinistNames = $machiningStepMachinistNames ?? [];
        $fmtDisp = static function ($d) {
            if (!$d) {
                return '...';
            }

            return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
        };
    @endphp

    <div class="container-fluid machining-mobile-wrap">
        <div class="machining-mobile-card">
            <div class="machining-detail-wo-title">
                <span>WO {{ $workorder->number }}</span>
{{--                @if(filled($machinistName ?? null))--}}
{{--                    <span class="text-secondary machining-detail-wo-machinist" title="{{ $machinistName }}">{{ $machinistName }}</span>--}}
{{--                @endif--}}
            </div>

            @php
                $machiningPhotoCount = $machiningPhotoCount ?? 0;
                $pdfCount = $pdfCount ?? 0;
            @endphp
            <form id="js-machining-file-anchor" class="d-none" aria-hidden="true"></form>
            <div class="d-grid gap-1 mt-0 mb-1 machining-action-row" style="grid-template-columns: 1fr 1fr;">
                <button type="button" class="btn btn-sm btn-primary js-machining-btn-photo">Photo</button>
                <button type="button" class="btn btn-sm btn-primary js-machining-btn-doc">Doc</button>
            </div>
            <div class="d-grid gap-1 mt-0 mb-2" style="grid-template-columns: 1fr 1fr;">
                <a href="{{ route('mobile.machining.workorder.machining-photos', $workorder) }}"
                   class="btn btn-sm btn-outline-light text-nowrap">
                    Machining photos <span class="badge bg-secondary align-middle js-machining-badge-photos">{{ $machiningPhotoCount }}</span>
                </a>
                <a href="{{ route('mobile.machining.workorder.pdfs', $workorder) }}"
                   class="btn btn-sm btn-outline-light text-nowrap">
                    PDFs <span class="badge bg-secondary align-middle js-machining-badge-pdfs">{{ $pdfCount }}</span>
                </a>
            </div>

            @foreach($detailItems as $item)
                @if(($item->kind ?? 'step') === 'pending_steps')
                    @php
                        $p = $item->date_parent ?? null;
                        $sendDisp = $p && $p->date_start ? $fmtDisp($p->date_start) : '—';
                    @endphp
                    <div class="machining-detail-block js-detail-block" data-kind="pending_steps">
                        <div class="machining-detail-line">
                            <div class="d-flex justify-content-between">
                                <div>{{ $item->detail_name ?? '—' }}</div>
                                <div class="text-end text-secondary small">No steps yet</div>
                            </div>
                            <span class="text-secondary">
                                PN {{ $item->detail_label ?? '—' }}@if(filled($item->detail_serial ?? null)) · SN {{ $item->detail_serial }}@endif
                            </span>
                            @if(filled($item->processes_label ?? ''))
                                <div class="machining-detail-processes mt-1"
                                     title="{{ e($item->processes_label) }}">{{ $item->processes_label }}</div>
                            @endif
                            @php
                                $parentDescPend = ($p instanceof \App\Models\TdrProcess) ? trim((string) ($p->description ?? '')) : '';
                            @endphp
                            @if($parentDescPend !== '')
                                <div class="machining-detail-parent-desc mt-1"
                                     title="{{ e($parentDescPend) }}">{{ $parentDescPend }}</div>
                            @endif
                        </div>
                        <div class="machining-detail-dates-step">
                            <span class="k" title="Date sent">Sent</span>
                            <span class="v">{{ $sendDisp }}</span>
                            <span class="k">Start</span>
                            <span class="v text-secondary">—</span>
                            <span class="k">Finish</span>
                            <span class="v text-secondary">—</span>
                        </div>
                        <p class="mb-0 mt-2 small text-secondary">
                            Set the number of working steps on the Machining board (desktop), then dates can be filled here.
                        </p>
                    </div>
                    @continue
                @endif
                @if(($item->kind ?? '') === 'step_group')
                    @php
                        $grp = $item;
                        $pGrp = $grp->date_parent ?? null;
                        $sendDispGrp = $pGrp && $pGrp->date_start ? $fmtDisp($pGrp->date_start) : '—';
                        $nStepsGrp = $grp->steps->count();
                        $parentDescGrp = ($pGrp instanceof \App\Models\TdrProcess) ? trim((string) ($pGrp->description ?? '')) : '';
                    @endphp
                    <div class="machining-detail-block js-detail-block" data-kind="step_group">
                        <div class="machining-detail-line">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <div>{{ $grp->detail_name ?? '—' }}</div>
                                <div class="text-end text-secondary small text-nowrap">{{ $nStepsGrp }} {{ $nStepsGrp === 1 ? 'step' : 'steps' }}</div>
                            </div>
                            <span class="text-secondary">
                                PN {{ $grp->detail_label ?? '—' }}@if(filled($grp->detail_serial ?? null)) · SN {{ $grp->detail_serial }}@endif
                            </span>
                            @if(filled($grp->processes_label ?? ''))
                                <div class="machining-detail-processes mt-1"
                                     title="{{ e($grp->processes_label) }}">{{ $grp->processes_label }}</div>
                            @endif
                            @if($parentDescGrp !== '')
                                <div class="machining-detail-parent-desc mt-1"
                                     title="{{ e($parentDescGrp) }}">{{ $parentDescGrp }}</div>
                            @endif
                        </div>
                        <div class="machining-detail-dates-step pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                            <span class="k" title="Date sent">Sent</span>
                            <span class="v">{{ $sendDispGrp }}</span>
                        </div>
                        @foreach($grp->steps as $stepItem)
                            @php
                                $step = $stepItem->step;
                                $listRow = $stepItem->row ?? null;
                                $workStartResolved = $step->date_start ?? $listRow?->date_start;
                                $startYmd = $workStartResolved instanceof \DateTimeInterface
                                    ? $workStartResolved->format('Y-m-d')
                                    : '';
                                $startDisp = $fmtDisp($workStartResolved instanceof \DateTimeInterface ? $workStartResolved : null);
                                $finishYmd = $step->date_finish?->format('Y-m-d') ?? '';
                                $finishDisp = $fmtDisp($step->date_finish);
                                $isStepOne = (int) ($step->step_index ?? 0) === 1;
                                $effectiveStepStart = $stepItem->effective_step_start ?? null;
                                $effectiveStartYmd = $effectiveStepStart instanceof \DateTimeInterface
                                    ? $effectiveStepStart->format('Y-m-d')
                                    : '';
                                $effectiveStartDisp = $fmtDisp($effectiveStepStart instanceof \DateTimeInterface ? $effectiveStepStart : null);
                                $authMachining = Auth::user();
                                $canEditStep = $authMachining !== null
                                    && (int) ($step->machinist_user_id ?? 0) === (int) ($authMachining->id ?? 0);
                                $assignedMachinistId = (int) ($step->machinist_user_id ?? 0);
                                $displayMachinistId = (int) ($stepItem->display_machinist_user_id ?? $assignedMachinistId);
                                $hideOwnMachinistNextToStep = $authMachining !== null
                                    && $authMachining->roleIs(['Machining'])
                                    && $assignedMachinistId > 0
                                    && $assignedMachinistId === (int) ($authMachining->id ?? 0);
                                $stepMachinistName = '';
                                if ($assignedMachinistId > 0 && $assignedMachinistId === $displayMachinistId) {
                                    $step->loadMissing([
                                        'machinist' => static fn ($q) => $q->withTrashed(),
                                    ]);
                                    $stepMachinistName = trim((string) ($step->machinist?->name ?? ''));
                                }
                                if ($stepMachinistName === '' && $displayMachinistId > 0) {
                                    $nm = $machiningStepMachinistNames[$displayMachinistId] ?? $machiningStepMachinistNames[(string) $displayMachinistId] ?? '';
                                    $stepMachinistName = trim((string) $nm);
                                }
                                $stepMachinistDisplay = $stepMachinistName !== '' ? $stepMachinistName : ($displayMachinistId > 0 ? 'user #' . $displayMachinistId : '');
                                $showStepMachinistName = $displayMachinistId > 0 && ! $hideOwnMachinistNextToStep;
                            @endphp
                            <div class="machining-detail-step-sub js-machining-closed-target {{ $loop->first ? '' : 'pt-2 mt-2 border-top border-secondary border-opacity-25' }}"
                                 @if($finishYmd !== '') data-machining-step-closed="1" @endif>
                                <div class="d-flex justify-content-between mb-1" style="font-size:.72rem;line-height:1.2;">
                                    <span class="text-secondary">Step {{ $step->step_index }}</span>
                                    @if($showStepMachinistName)
                                        <span class="text-end text-secondary text-truncate ms-2" style="max-width:58%;">{{ $stepMachinistDisplay }}</span>
                                    @endif
                                </div>
                                <div class="machining-detail-dates-step">
                                    <span class="k" title="{{ $isStepOne ? 'Work start (step 1)' : 'Effective start (previous step finish)' }}">Start</span>
                                    @if($isStepOne)
                                        @if($canEditStep)
                                            <div class="step-finish-form-wrap machining-mobile-date">
                                                <form method="POST" action="{{ route('mobile.machining.steps.update', $step) }}"
                                                      class="js-mobile-machining-step-form m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="date_start" value="{{ $startYmd }}" class="js-mobile-date-real">
                                                    <input type="date" value="{{ $startYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                                    <input type="text"
                                                           class="form-control form-control-sm bg-dark text-light js-mobile-date-display w-100 {{ $startYmd !== '' ? 'has-finish' : '' }}"
                                                           value="{{ $startYmd !== '' ? $startDisp : '...' }}"
                                                           placeholder="…"
                                                           readonly>
                                                </form>
                                            </div>
                                        @else
                                            <span class="v">{{ $effectiveStartYmd !== '' ? $effectiveStartDisp : ($startYmd !== '' ? $startDisp : '—') }}</span>
                                        @endif
                                    @else
                                        <span class="v">{{ $effectiveStartYmd !== '' ? $effectiveStartDisp : '—' }}</span>
                                    @endif
                                    <span class="k">Finish</span>
                                    @if($canEditStep)
                                        <div class="step-finish-form-wrap machining-mobile-date">
                                            <form method="POST" action="{{ route('mobile.machining.steps.update', $step) }}"
                                                  class="js-mobile-machining-step-form m-0">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="date_finish" value="{{ $finishYmd }}" class="js-mobile-date-real">
                                                <input type="date" value="{{ $finishYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                                <input type="text"
                                                       class="form-control form-control-sm bg-dark text-light js-mobile-date-display w-100 {{ $finishYmd !== '' ? 'has-finish' : '' }}"
                                                       value="{{ $finishYmd !== '' ? $finishDisp : '...' }}"
                                                       placeholder="…"
                                                       readonly>
                                            </form>
                                        </div>
                                    @else
                                        <span class="v">{{ $finishYmd !== '' ? $finishDisp : '—' }}</span>
                                    @endif
                                </div>
                                @php $machiningStepNoteGrouped = trim((string) ($step->description ?? '')); @endphp
                                @if($machiningStepNoteGrouped !== '')
                                    <div class="small text-secondary mt-1" style="white-space:pre-wrap;line-height:1.25;">{{ $machiningStepNoteGrouped }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @continue
                @endif
                @php
                    $step = $item->step;
                    $listRow = $item->row ?? null;
                    $p = $item->date_parent ?? null;
                    $sendDisp = $p && $p->date_start ? $fmtDisp($p->date_start) : '—';
                    $workStartResolved = $step->date_start ?? $listRow?->date_start;
                    $startYmd = $workStartResolved instanceof \DateTimeInterface
                        ? $workStartResolved->format('Y-m-d')
                        : '';
                    $startDisp = $fmtDisp($workStartResolved instanceof \DateTimeInterface ? $workStartResolved : null);
                    $finishYmd = $step->date_finish?->format('Y-m-d') ?? '';
                    $finishDisp = $fmtDisp($step->date_finish);
                    $isStepOne = (int) ($step->step_index ?? 0) === 1;
                    $effectiveStepStart = $item->effective_step_start ?? null;
                    $effectiveStartYmd = $effectiveStepStart instanceof \DateTimeInterface
                        ? $effectiveStepStart->format('Y-m-d')
                        : '';
                    $effectiveStartDisp = $fmtDisp($effectiveStepStart instanceof \DateTimeInterface ? $effectiveStepStart : null);
                    $authMachining = Auth::user();
                    /** Даты на mobile — только у назначенного на шаг machinist (не чужие шаги, без исключения для Admin/Manager). */
                    $canEditStep = $authMachining !== null
                        && (int) ($step->machinist_user_id ?? 0) === (int) ($authMachining->id ?? 0);
                    $assignedMachinistId = (int) ($step->machinist_user_id ?? 0);
                    $displayMachinistId = (int) ($item->display_machinist_user_id ?? $assignedMachinistId);
                    $hideOwnMachinistNextToStep = $authMachining !== null
                        && $authMachining->roleIs(['Machining'])
                        && $assignedMachinistId > 0
                        && $assignedMachinistId === (int) ($authMachining->id ?? 0);
                    $stepMachinistName = '';
                    if ($assignedMachinistId > 0 && $assignedMachinistId === $displayMachinistId) {
                        $step->loadMissing([
                            'machinist' => static fn ($q) => $q->withTrashed(),
                        ]);
                        $stepMachinistName = trim((string) ($step->machinist?->name ?? ''));
                    }
                    if ($stepMachinistName === '' && $displayMachinistId > 0) {
                        $nm = $machiningStepMachinistNames[$displayMachinistId] ?? $machiningStepMachinistNames[(string) $displayMachinistId] ?? '';
                        $stepMachinistName = trim((string) $nm);
                    }
                    $stepMachinistDisplay = $stepMachinistName !== '' ? $stepMachinistName : ($displayMachinistId > 0 ? 'user #' . $displayMachinistId : '');
                    $showStepMachinistName = $displayMachinistId > 0 && ! $hideOwnMachinistNextToStep;
                    $machiningParentDesc = ($p instanceof \App\Models\TdrProcess) ? trim((string) ($p->description ?? '')) : '';
                @endphp
                <div class="machining-detail-block js-detail-block js-machining-closed-target"
                     data-kind="step"
                     @if($finishYmd !== '') data-machining-step-closed="1" @endif>
                    <div class="machining-detail-line">
                        <div class="d-flex justify-content-between">
                            <div> {{ $item->detail_name ?? '—' }}</div>
                            <div class="text-end">
                                <span>Step {{ $step->step_index }}</span>@if($showStepMachinistName)<span class="text-secondary fw-normal"> · {{ $stepMachinistDisplay }}</span>@endif
                            </div>
                        </div>
                        <span class="text-secondary">
                            PN {{ $item->detail_label ?? '—' }}@if(filled($item->detail_serial ?? null)) · SN {{ $item->detail_serial }}@endif
                        </span>
                        @if(filled($item->processes_label ?? ''))
                            <div class="machining-detail-processes mt-1"
                                 title="{{ e($item->processes_label) }}">{{ $item->processes_label }}</div>
                        @endif
                        @if($machiningParentDesc !== '')
                            <div class="machining-detail-parent-desc mt-1"
                                 title="{{ e($machiningParentDesc) }}">{{ $machiningParentDesc }}</div>
                        @endif
                    </div>
                    <div class="machining-detail-dates-step">
                        <span class="k" title="Date sent">Sent</span>
                        <span class="v">{{ $sendDisp }}</span>
                        <span class="k" title="{{ $isStepOne ? 'Work start (step 1)' : 'Effective start (previous step finish)' }}">Start</span>
                        @if($isStepOne)
                            @if($canEditStep)
                                <div class="step-finish-form-wrap machining-mobile-date">
                                    <form method="POST" action="{{ route('mobile.machining.steps.update', $step) }}"
                                          class="js-mobile-machining-step-form m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="date_start" value="{{ $startYmd }}" class="js-mobile-date-real">
                                        <input type="date" value="{{ $startYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                        <input type="text"
                                               class="form-control form-control-sm bg-dark text-light js-mobile-date-display w-100 {{ $startYmd !== '' ? 'has-finish' : '' }}"
                                               value="{{ $startYmd !== '' ? $startDisp : '...' }}"
                                               placeholder="…"
                                               readonly>
                                    </form>
                                </div>
                            @else
                                <span class="v">{{ $effectiveStartYmd !== '' ? $effectiveStartDisp : ($startYmd !== '' ? $startDisp : '—') }}</span>
                            @endif
                        @else
                            <span class="v">{{ $effectiveStartYmd !== '' ? $effectiveStartDisp : '—' }}</span>
                        @endif
                        <span class="k">Finish</span>
                        @if($canEditStep)
                            <div class="step-finish-form-wrap machining-mobile-date">
                                <form method="POST" action="{{ route('mobile.machining.steps.update', $step) }}"
                                      class="js-mobile-machining-step-form m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="date_finish" value="{{ $finishYmd }}" class="js-mobile-date-real">
                                    <input type="date" value="{{ $finishYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                    <input type="text"
                                           class="form-control form-control-sm bg-dark text-light js-mobile-date-display w-100 {{ $finishYmd !== '' ? 'has-finish' : '' }}"
                                           value="{{ $finishYmd !== '' ? $finishDisp : '...' }}"
                                           placeholder="…"
                                           readonly>
                                </form>
                            </div>
                        @else
                            <span class="v">{{ $finishYmd !== '' ? $finishDisp : '—' }}</span>
                        @endif
                    </div>
                    @php $machiningStepNote = trim((string) ($step->description ?? '')); @endphp
                    @if($machiningStepNote !== '')
                        <div class="small text-secondary mt-1" style="white-space:pre-wrap;line-height:1.25;">{{ $machiningStepNote }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
    @php
        $jsMachiningPhotoStoreUrl = route('mobile.machining.workorder.photo.store', $workorder);
        $jsMachiningDocPdfUrl = route('mobile.machining.workorder.doc_pdf.store', $workorder);
    @endphp
    <script>
        const MACHINING_WO_PHOTO_URL = @json($jsMachiningPhotoStoreUrl);
        const MACHINING_WO_DOC_PDF_URL = @json($jsMachiningDocPdfUrl);
    </script>
    <script>
        function bindMachiningWoHideClosedToggle() {
            var cb = document.getElementById('js-machining-wo-hide-closed');
            if (!cb || cb.dataset.bound === '1') {
                return;
            }
            cb.dataset.bound = '1';
            function applyMachiningWoHideClosed() {
                var hide = cb.checked;
                document.querySelectorAll('.js-machining-closed-target[data-machining-step-closed="1"]').forEach(function (el) {
                    el.classList.toggle('d-none', hide);
                });
            }
            cb.addEventListener('change', applyMachiningWoHideClosed);
            applyMachiningWoHideClosed();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindMachiningWoHideClosedToggle);
        } else {
            bindMachiningWoHideClosedToggle();
        }

        function formatMachiningDateYmd(ymd) {
            const s = String(ymd || '').trim();
            if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return '...';
            const [y, m, d] = s.split('-').map((v) => Number.parseInt(v, 10));
            const dt = new Date(y, m - 1, d);
            const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            return `${String(dt.getDate()).padStart(2, '0')}.${months[dt.getMonth()]}.${dt.getFullYear()}`;
        }

        function machiningLocalTodayYmd() {
            const n = new Date();
            return n.getFullYear()
                + '-' + String(n.getMonth() + 1).padStart(2, '0')
                + '-' + String(n.getDate()).padStart(2, '0');
        }

        document.addEventListener('pointerdown', function (e) {
            const picker = e.target.closest('.js-mobile-date-picker');
            if (!picker) {
                return;
            }
            picker.dataset.openedAt = String(Date.now());
        }, true);

        document.addEventListener('focusin', function (e) {
            const picker = e.target.closest('.js-mobile-date-picker');
            if (!picker) {
                return;
            }
            if (!picker.dataset.openedAt) {
                picker.dataset.openedAt = String(Date.now());
            }
        }, true);

        document.addEventListener('click', function (e) {
            const display = e.target.closest('.js-mobile-date-display');
            if (!display) return;
            const form = display.closest('.js-mobile-machining-step-form');
            if (!form) return;
            const picker = form.querySelector('.js-mobile-date-picker');
            if (!picker) return;
            picker.dataset.openedAt = String(Date.now());
            if (typeof picker.showPicker === 'function') {
                picker.showPicker();
            } else {
                picker.focus();
            }
        });

        document.addEventListener('change', async function (e) {
            const input = e.target;
            if (!input || !input.classList.contains('js-mobile-date-picker')) return;

            const form = input.closest('.js-mobile-machining-step-form');
            if (!form) return;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const real = form.querySelector('.js-mobile-date-real');
            const display = form.querySelector('.js-mobile-date-display');
            const prevRealValue = real ? real.value : '';
            const prevDisplayValue = display ? display.value : '';
            const prevHasFinish = display ? display.classList.contains('has-finish') : false;
            const openedAt = Number.parseInt(String(input.dataset.openedAt || '0'), 10);
            const msSinceOpen = openedAt ? (Date.now() - openedAt) : 999999;
            if (!prevRealValue && input.value) {
                const todayYmd = machiningLocalTodayYmd();
                if (input.value === todayYmd && msSinceOpen < 450) {
                    input.value = '';
                    if (real) real.value = '';
                    if (display) {
                        display.value = '...';
                        display.classList.remove('has-finish');
                    }
                    delete input.dataset.openedAt;
                    return;
                }
                if (msSinceOpen < 85) {
                    input.value = '';
                    if (real) real.value = '';
                    if (display) {
                        display.value = '...';
                        display.classList.remove('has-finish');
                    }
                    delete input.dataset.openedAt;
                    return;
                }
            }
            if (real) real.value = input.value || '';
            if (display) {
                display.value = input.value ? formatMachiningDateYmd(input.value) : '...';
                display.classList.toggle('has-finish', !!input.value);
            }
            const formData = new FormData(form);

            if (typeof safeShowSpinner === 'function') {
                safeShowSpinner();
            }

            input.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.success === false) {
                    let msg = 'Error';
                    if (payload?.errors) {
                        const firstKey = Object.keys(payload.errors)[0];
                        if (firstKey && payload.errors[firstKey]?.[0]) {
                            msg = payload.errors[firstKey][0];
                        }
                    }
                    throw new Error(msg);
                }

                if (form.classList.contains('js-mobile-machining-step-form')) {
                    const fieldName = real && real.name ? real.name : 'date_finish';
                    const payloadKey = fieldName === 'date_start' ? 'date_start' : 'date_finish';
                    if (payload[payloadKey] !== undefined) {
                        const ymd = payload[payloadKey] || '';
                        if (real) real.value = ymd;
                        if (display) {
                            display.value = ymd ? formatMachiningDateYmd(ymd) : '...';
                            display.classList.toggle('has-finish', !!ymd);
                        }
                        if (input) input.value = ymd;
                        const closedTarget = form.closest('.js-machining-closed-target');
                        if (closedTarget && payloadKey === 'date_finish' && ymd) {
                            closedTarget.setAttribute('data-machining-step-closed', '1');
                            const hideCb = document.getElementById('js-machining-wo-hide-closed');
                            if (hideCb && hideCb.checked) {
                                closedTarget.classList.add('d-none');
                            }
                        }
                    }
                }
            } catch (err) {
                if (real) real.value = prevRealValue;
                if (display) {
                    display.value = prevDisplayValue;
                    display.classList.toggle('has-finish', prevHasFinish);
                }
                if (input) input.value = prevRealValue;
                const msg = err?.message || 'Save failed';
                if (typeof window.notifyError === 'function') {
                    window.notifyError(msg, 3500);
                } else {
                    console.error(msg);
                }
            } finally {
                input.disabled = false;
                if (typeof safeHideSpinner === 'function') {
                    safeHideSpinner();
                }
            }
        });

        (function () {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const btnPhoto = document.querySelector('.js-machining-btn-photo');
            const btnDoc = document.querySelector('.js-machining-btn-doc');
            const badgePhotos = document.querySelector('.js-machining-badge-photos');
            const badgePdfs = document.querySelector('.js-machining-badge-pdfs');
            const fileAnchor = document.getElementById('js-machining-file-anchor');

            function isOnline() {
                return typeof navigator === 'undefined' || navigator.onLine !== false;
            }

            function updateBadges(p, pdf) {
                if (typeof p === 'number' && badgePhotos) {
                    badgePhotos.textContent = String(p);
                }
                if (typeof pdf === 'number' && badgePdfs) {
                    badgePdfs.textContent = String(pdf);
                }
            }

            function firstValidationMessage(payload) {
                if (payload?.message) {
                    return String(payload.message);
                }
                const err = payload?.errors;
                if (err && typeof err === 'object') {
                    const k = Object.keys(err)[0];
                    if (k && err[k]?.[0]) {
                        return err[k][0];
                    }
                }
                return 'Request failed';
            }

            function notifyErr(msg) {
                if (typeof window.notifyError === 'function') {
                    window.notifyError(msg, 4000);
                } else {
                    console.error(msg);
                }
            }

            async function uploadMachiningPhotos(files) {
                const fd = new FormData();
                for (let i = 0; i < files.length; i += 1) {
                    fd.append('photos[]', files[i]);
                }
                const res = await fetch(MACHINING_WO_PHOTO_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(firstValidationMessage(data));
                }
                updateBadges(data.machining_photo_count, data.pdf_count);
            }

            async function uploadMachiningDocPdf(file) {
                const fd = new FormData();
                fd.append('image', file);
                const res = await fetch(MACHINING_WO_DOC_PDF_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(firstValidationMessage(data));
                }
                updateBadges(data.machining_photo_count, data.pdf_count);
            }

            /** Same pattern as mobile index `openCamera()` (show.blade.php): ephemeral file input + capture. */
            function openMachiningNativePicker(opts) {
                const multiple = !!opts.multiple;
                const onFiles = opts.onFiles;
                if (!fileAnchor) return;

                document.getElementById('machining-camera-input')?.remove();

                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.id = 'machining-camera-input';
                fileInput.name = multiple ? 'photos[]' : 'image';
                fileInput.accept = 'image/*';
                fileInput.capture = 'environment';
                if (multiple) {
                    fileInput.multiple = true;
                }
                fileInput.style.display = 'none';

                fileInput.addEventListener('change', async function () {
                    try {
                        if (!fileInput.files?.length) {
                            return;
                        }
                        if (typeof safeShowSpinner === 'function') {
                            safeShowSpinner();
                        }
                        await onFiles(fileInput.files);
                    } catch (e) {
                        notifyErr(e?.message || 'Upload failed');
                    } finally {
                        fileInput.remove();
                        if (typeof safeHideSpinner === 'function') {
                            safeHideSpinner();
                        }
                    }
                });

                fileAnchor.appendChild(fileInput);
                fileInput.click();
            }

            if (btnPhoto) {
                btnPhoto.addEventListener('click', function () {
                    if (!isOnline()) {
                        notifyErr('No network connection.');
                        return;
                    }
                    openMachiningNativePicker({
                        multiple: true,
                        onFiles: (files) => uploadMachiningPhotos(files),
                    });
                });
            }

            if (btnDoc) {
                btnDoc.addEventListener('click', function () {
                    if (!isOnline()) {
                        notifyErr('No network connection.');
                        return;
                    }
                    openMachiningNativePicker({
                        multiple: false,
                        onFiles: (files) => uploadMachiningDocPdf(files[0]),
                    });
                });
            }
        })();
    </script>
@endsection
