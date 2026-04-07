@extends('mobile.master')

@section('style')
    <style>
        .paint-mobile-wrap {
            padding: 0 0 max(11rem, calc(env(safe-area-inset-bottom, 0px) + 8rem));
            box-sizing: border-box;
        }
        .paint-mobile-bottom-spacer {
            min-height: 5.5rem;
            height: max(5.5rem, calc(env(safe-area-inset-bottom, 0px) + 2rem));
            flex-shrink: 0;
            background: linear-gradient(to bottom, transparent, rgba(52, 58, 64, 0.55));
            border-radius: 0 0 0.35rem 0.35rem;
            pointer-events: none;
        }
        .paint-mobile-card .table-responsive {
            padding-bottom: 1.25rem;
        }
        .paint-mobile-card {
            background: rgba(20, 24, 28, .9);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .35rem;
            padding: .35rem;
            margin: 0;
        }
        .paint-mobile-table {
            color: #e9ecef;
            font-size: .88rem;
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }
        .paint-mobile-table thead th {
            color: #9fb0c0;
            font-size: .78rem;
            white-space: nowrap;
            padding: .28rem .22rem;
        }
        .paint-mobile-col-queue {
            text-align: center;
            width: 28px;
            max-width: 36px;
        }
        .paint-mobile-col-detail {
            font-size: .8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 0;
        }
        .paint-mobile-table td, .paint-mobile-table th {
            vertical-align: middle;
            padding: .26rem .22rem;
        }
        tr.paint-mobile-group-start td {
            border-top: 2px solid rgba(255, 255, 255, .14);
        }
        tr.paint-mobile-group-follow td {
            border-top: 1px solid rgba(255, 255, 255, .06);
        }
        .lost-carousel-img {
            width: 100%;
            max-height: 270px;
            object-fit: contain;
            background: #111;
            border-radius: .45rem;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        .lost-carousel-title {
            font-size: .86rem;
            font-weight: 600;
            color: #e9ecef;
            margin-top: .45rem;
            text-align: center;
        }
        .lost-coverflow-wrap {
            position: relative;
            margin: 0 -0.2rem;
            padding: 0.35rem 0 0.25rem;
        }
        .lost-coverflow-track {
            display: flex;
            flex-direction: row;
            gap: 0.65rem;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: 14%;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 0.4rem 14% 1.1rem;
            touch-action: pan-x pinch-zoom;
            overscroll-behavior-x: contain;
        }
        .lost-coverflow-track::-webkit-scrollbar {
            display: none;
        }
        .lost-coverflow-slide {
            flex: 0 0 72%;
            max-width: 320px;
            scroll-snap-align: center;
            scroll-snap-stop: always;
        }
        .lost-coverflow-card {
            position: relative;
            border-radius: 0.55rem;
            overflow: hidden;
            background: #0d0f12;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.55);
            transform: scale(0.92);
            opacity: 0.55;
            transition: transform 0.28s ease, opacity 0.28s ease, box-shadow 0.28s ease;
        }
        .lost-coverflow-slide.is-active .lost-coverflow-card {
            transform: scale(1);
            opacity: 1;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.65);
            z-index: 2;
        }
        .lost-coverflow-nav {
            position: absolute;
            top: 38%;
            transform: translateY(-50%);
            z-index: 5;
            width: 2.1rem;
            height: 2.1rem;
            border: 0;
            border-radius: 50%;
            padding: 0;
            background: rgba(20, 28, 36, 0.82);
            color: #b8d4e8;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }
        .lost-coverflow-nav:disabled {
            opacity: 0.25;
            pointer-events: none;
        }
        .lost-coverflow-prev {
            left: 0.15rem;
        }
        .lost-coverflow-next {
            right: 0.15rem;
        }
        .lost-coverflow-nav .bi {
            font-size: 1.1rem;
            line-height: 1;
        }
        .lost-del-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            padding: 0;
            line-height: 20px;
            font-size: 13px;
            z-index: 20;
            pointer-events: auto;
        }
        .paint-mobile-date {
            min-width: 76px;
            white-space: nowrap;
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .paint-mobile-date-head {
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .paint-mobile-date-native-wrap {
            position: relative;
            width: 100%;
            min-height: 2rem;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .paint-mobile-date .js-mobile-date-display {
            width: 100%;
            max-width: 100%;
            font-size: .8rem;
            padding: .2rem .28rem;
            min-height: 2rem;
            margin-left: auto;
            text-align: center;
            pointer-events: none;
        }
        .paint-mobile-date .js-mobile-date-picker {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            font-size: 16px;
            box-sizing: border-box;
        }
        .paint-mobile-date .js-mobile-date-display.has-finish {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }
        .paint-mobile-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            padding: 0 .15rem .35rem;
            font-size: .78rem;
            color: #9fb0c0;
        }
        .paint-mobile-lost-pane {
            padding-top: 1rem;
        }
    </style>
@endsection

@section('content')
    @php
        $activeTab = $activeTab ?? 'wo';
        $rows = $rows ?? collect();
        $lostParts = $lostParts ?? collect();
        $fmt = static function ($d) {
            return $d ? $d->format('d.m.Y') : '—';
        };
    @endphp

    <div class="container-fluid paint-mobile-wrap">
        @if($activeTab === 'wo')
            <div class="paint-mobile-card">
                <div class="paint-mobile-toolbar">
                    <label class="d-inline-flex align-items-center gap-1 m-0">
                        <input type="checkbox" id="js-hide-closed-rows">
                        <span>Hide closed rows</span>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-sm paint-mobile-table" id="js-mobile-paint-table">
                        <colgroup>
                            <col style="width:34px">
                            <col style="width:64px">
                            <col>
                            <col style="width:104px">
                            <col style="width:104px">
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="paint-mobile-col-queue" aria-label="Position"></th>
                            <th>WO</th>
                            <th>Detail</th>
                            <th class="paint-mobile-date-head">Start</th>
                            <th class="paint-mobile-date-head">Finish</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $wo = $row->workorder;
                                $editTp = $row->edit_paint_process ?? null;
                                $startYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                                $finishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                                $startDisp = $editTp?->date_start?->format('m/d/Y') ?? '';
                                $finishDisp = $editTp?->date_finish?->format('m/d/Y') ?? '';
                                $lineStart = $editTp?->date_start ?? $row->date_start;
                                $lineFinish = $editTp?->date_finish ?? $row->date_finish;
                                $dataStartYmd = $lineStart ? $lineStart->format('Y-m-d') : '';
                                $dataFinishYmd = $lineFinish ? $lineFinish->format('Y-m-d') : '';
                                $qp = $wo->paint_queue_order !== null ? $row->paint_queue_position : null;
                                $isMaster = (bool) ($row->is_queue_master ?? false);
                            @endphp
                            <tr class="js-paint-row {{ $isMaster ? 'paint-mobile-group-start' : 'paint-mobile-group-follow' }}"
                                data-wo-number="{{ (int) $wo->number }}"
                                data-wo-id="{{ (int) $wo->id }}"
                                data-sort-order="{{ (int) $loop->index }}"
                                data-queue-pos="{{ $qp !== null ? (int) $qp : '' }}"
                                data-start-ymd="{{ $dataStartYmd }}"
                                data-finish-ymd="{{ $dataFinishYmd }}">
                                <td class="paint-mobile-col-queue text-info js-queue-cell">{{ $qp !== null ? str_pad((string) $qp, 2, '0', STR_PAD_LEFT) : '—' }}</td>
                                <td>{{ $wo->number }}</td>
                                <td class="paint-mobile-col-detail text-secondary" title="{{ $row->detail_label ?? '' }}">{{ $row->detail_label ?? '—' }}</td>
                                <td class="paint-mobile-date">
                                    @if($editTp)
                                        <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-paint-date-form m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="from_paint_index" value="1">
                                            <input type="hidden" name="date_start" value="{{ $startYmd }}" class="js-mobile-date-real">
                                            <div class="paint-mobile-date-native-wrap" aria-label="Set start date">
                                                <input type="text"
                                                       class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $startYmd !== '' ? 'has-finish' : '' }}"
                                                       value="{{ $startDisp }}"
                                                       placeholder="Tap"
                                                       readonly
                                                       inputmode="none"
                                                       autocomplete="off"
                                                       tabindex="-1">
                                                <input type="date"
                                                       value="{{ $startYmd }}"
                                                       class="js-mobile-date-picker"
                                                       tabindex="0"
                                                       aria-label="Start date">
                                            </div>
                                        </form>
                                    @else
                                        {{ $fmt($row->date_start) }}
                                    @endif
                                </td>
                                <td class="paint-mobile-date">
                                    @if($editTp)
                                        <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-paint-date-form m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="from_paint_index" value="1">
                                            <input type="hidden" name="date_finish" value="{{ $finishYmd }}" class="js-mobile-date-real">
                                            <div class="paint-mobile-date-native-wrap" aria-label="Set finish date">
                                                <input type="text"
                                                       class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $finishYmd !== '' ? 'has-finish' : '' }}"
                                                       value="{{ $finishDisp }}"
                                                       placeholder="Tap"
                                                       readonly
                                                       inputmode="none"
                                                       autocomplete="off"
                                                       tabindex="-1">
                                                <input type="date"
                                                       value="{{ $finishYmd }}"
                                                       class="js-mobile-date-picker"
                                                       tabindex="0"
                                                       aria-label="Finish date">
                                            </div>
                                        </form>
                                    @else
                                        {{ $fmt($row->date_finish) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-3">No paint workorders.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="paint-mobile-lost-pane">
            <div class="paint-mobile-card mb-1">
                @if($lostParts->isEmpty())
                    <div class="text-secondary small">No lost parts recorded.</div>
                @else
                    <div class="lost-coverflow-wrap">
                        <div class="lost-coverflow-track js-lost-coverflow-track" id="mobileLostCoverflow">
                            @foreach($lostParts as $lost)
                                @php
                                    $big = $lost->getFirstMediaBigUrl('lost');
                                    $thumb = $lost->getFirstMediaThumbnailUrl('lost');
                                    $imgSrc = ($thumb !== null && $thumb !== '') ? $thumb : $big;
                                    $caption = trim($lost->part_number . (($lost->serial_number ?? '') !== '' ? ' · S/N: ' . $lost->serial_number : ''));
                                @endphp
                                <div class="lost-coverflow-slide {{ $loop->first ? 'is-active' : '' }}">
                                    <div class="lost-coverflow-card">
                                        <div class="position-relative">
                                            <img src="{{ $imgSrc }}"
                                                 draggable="false"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="lost-carousel-img js-lost-fancybox-trigger"
                                                 alt="{{ $lost->part_number }}"
                                                 data-big="{{ $big }}"
                                                 data-caption="{{ $caption }}">
                                            <form method="POST"
                                                  action="{{ route('mobile.paint.lost.destroy', $lost) }}"
                                                  class="m-0 js-lost-delete-form"
                                                  data-no-spinner>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger lost-del-btn js-lost-delete-btn"
                                                        aria-label="Delete"
                                                        title="Delete">&times;</button>
                                            </form>
                                        </div>
                                        <div class="lost-carousel-title">{{ $lost->part_number }}</div>
                                        @if(($lost->comment ?? '') !== '')
                                            <div class="small text-secondary px-1">{{ $lost->comment }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button"
                                class="lost-coverflow-nav lost-coverflow-prev js-lost-coverflow-prev"
                                aria-label="Previous slide">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </button>
                        <button type="button"
                                class="lost-coverflow-nav lost-coverflow-next js-lost-coverflow-next"
                                aria-label="Next slide">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </div>

            <div class="paint-mobile-card">
                <button type="button"
                        class="btn btn-success btn-sm w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#mobilePaintLostAddModal">+</button>
            </div>

            </div>

            <div class="modal fade" id="mobilePaintLostAddModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light border-secondary">
                        <form id="mobilePaintLostAddForm"
                              method="POST"
                              action="{{ route('mobile.paint.lost.store') }}"
                              enctype="multipart/form-data"
                              data-no-spinner>
                            @csrf
                            <div class="modal-header border-secondary py-2">
                                <h6 class="modal-title">Add lost part</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Part number</label>
                                    <input type="text" name="part_number" class="form-control form-control-sm" required maxlength="255">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Serial #</label>
                                    <input type="text" name="serial_number" class="form-control form-control-sm" maxlength="255">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Comment</label>
                                    <input type="text" name="comment" class="form-control form-control-sm" maxlength="2000">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Photo</label>
                                    <input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm" required>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary py-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success btn-sm js-mobile-paint-lost-save">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
        <div class="paint-mobile-bottom-spacer" aria-hidden="true"></div>
    </div>
@endsection

@section('scripts')
    <script>
        (function initMobilePaintLostModal() {
            const el = document.getElementById('mobilePaintLostAddModal');
            if (!el || !window.bootstrap || typeof window.bootstrap.Modal !== 'function') {
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(el, {
                focus: false,
                backdrop: true
            });
        })();

        (function initMobilePaintLostSave() {
            const btn = document.querySelector('.js-mobile-paint-lost-save');
            const form = document.getElementById('mobilePaintLostAddForm');
            if (!btn || !form) {
                return;
            }
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        })();

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

        document.addEventListener('change', async function (e) {
            const input = e.target;
            if (!input || !input.classList.contains('js-mobile-date-picker')) return;

            const form = input.closest('.js-mobile-paint-date-form');
            if (!form) return;
            const row = form.closest('.js-paint-row');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const real = form.querySelector('.js-mobile-date-real');
            const display = form.querySelector('.js-mobile-date-display');
            const prevRealValue = real ? real.value : '';
            const prevDisplayValue = display ? display.value : '';
            const prevHasFinish = display ? display.classList.contains('has-finish') : false;
            const openedAt = parseInt(String(input.dataset.openedAt || '0'), 10);
            const msSinceOpen = openedAt ? (Date.now() - openedAt) : 999999;
            if (!prevRealValue && input.value && msSinceOpen < 600) {
                input.value = '';
                if (real) {
                    real.value = '';
                }
                if (display) {
                    display.value = '';
                    display.classList.remove('has-finish');
                }
                return;
            }
            if (real) real.value = input.value || '';
            if (display) {
                display.value = input.value ? new Date(input.value + 'T00:00:00').toLocaleDateString('en-US') : '';
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

                if (row) {
                    const startReal = row.querySelector('input[name="date_start"].js-mobile-date-real');
                    const finishReal = row.querySelector('input[name="date_finish"].js-mobile-date-real');
                    row.dataset.startYmd = startReal?.value || '';
                    row.dataset.finishYmd = finishReal?.value || '';

                    if ((real?.name || '') === 'date_finish' && (real?.value || '') !== '') {
                        const woId = row?.dataset?.woId;
                        const targets = woId
                            ? document.querySelectorAll('tr.js-paint-row[data-wo-id="' + woId + '"]')
                            : [row];
                        targets.forEach((r) => {
                            r.dataset.queuePos = '';
                            const queueCell = r.querySelector('.js-queue-cell');
                            if (queueCell) {
                                queueCell.textContent = '—';
                            }
                        });
                    }
                    normalizeAndSortPaintRows();
                }
            } catch (err) {
                if (real) real.value = prevRealValue;
                if (input) {
                    input.value = prevRealValue;
                    delete input.dataset.openedAt;
                }
                if (display) {
                    display.value = prevDisplayValue;
                    display.classList.toggle('has-finish', prevHasFinish);
                }
                if (row) {
                    const startReal = row.querySelector('input[name="date_start"].js-mobile-date-real');
                    const finishReal = row.querySelector('input[name="date_finish"].js-mobile-date-real');
                    row.dataset.startYmd = startReal?.value || '';
                    row.dataset.finishYmd = finishReal?.value || '';
                }
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

        function normalizeAndSortPaintRows() {
            const tbody = document.querySelector('#js-mobile-paint-table tbody');
            if (!tbody) return;

            function queuePosNum(r) {
                const n = Number.parseInt(String(r.dataset.queuePos || '').trim(), 10);
                return Number.isFinite(n) ? n : 0;
            }

            const rows = Array.from(tbody.querySelectorAll('tr.js-paint-row'));
            function tieBreakQueued(a, b) {
                const d = queuePosNum(a) - queuePosNum(b);
                if (d !== 0) {
                    return d;
                }
                const woCmp = Number.parseInt(String(a.dataset.woNumber || '0'), 10) - Number.parseInt(String(b.dataset.woNumber || '0'), 10);
                if (woCmp !== 0) {
                    return woCmp;
                }
                return (parseInt(String(a.dataset.sortOrder || '0'), 10) || 0) - (parseInt(String(b.dataset.sortOrder || '0'), 10) || 0);
            }

            const queued = rows
                .filter((r) => queuePosNum(r) > 0)
                .sort(tieBreakQueued);
            const unqueued = rows
                .filter((r) => queuePosNum(r) <= 0)
                .sort((a, b) => {
                    const woCmp = Number.parseInt(String(b.dataset.woNumber || '0'), 10) - Number.parseInt(String(a.dataset.woNumber || '0'), 10);
                    if (woCmp !== 0) {
                        return woCmp;
                    }
                    return (parseInt(String(a.dataset.sortOrder || '0'), 10) || 0) - (parseInt(String(b.dataset.sortOrder || '0'), 10) || 0);
                });

            let queueSlot = 0;
            let lastWoId = null;
            queued.forEach((r) => {
                const wid = String(r.dataset.woId || '');
                if (wid !== lastWoId) {
                    lastWoId = wid;
                    queueSlot++;
                }
                r.dataset.queuePos = String(queueSlot);
                const queueCell = r.querySelector('.js-queue-cell');
                if (queueCell) {
                    queueCell.textContent = String(queueSlot).padStart(2, '0');
                }
            });

            unqueued.forEach((r) => {
                r.dataset.queuePos = '';
                const queueCell = r.querySelector('.js-queue-cell');
                if (queueCell) queueCell.textContent = '—';
            });

            const ordered = queued.concat(unqueued);
            ordered.forEach((r) => tbody.appendChild(r));
            applyClosedFilter();
        }

        function applyClosedFilter() {
            const hideClosed = document.getElementById('js-hide-closed-rows');
            const needHide = !!hideClosed?.checked;
            document.querySelectorAll('#js-mobile-paint-table tbody tr.js-paint-row').forEach((row) => {
                const closed = (row.dataset.startYmd || '') !== '' && (row.dataset.finishYmd || '') !== '';
                row.style.display = (needHide && closed) ? 'none' : '';
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'js-hide-closed-rows') {
                try {
                    sessionStorage.setItem('mobile_paint_hide_closed', e.target.checked ? '1' : '0');
                } catch (_) {}
                applyClosedFilter();
            }
        });

        (function initLostFancyboxDoubleTap() {
            let lastTapAt = 0;
            document.addEventListener('click', function (e) {
                const img = e.target.closest('.js-lost-fancybox-trigger');
                if (!img) return;

                const now = Date.now();
                const delta = now - lastTapAt;
                lastTapAt = now;

                if (delta > 350) return; // open only on second tap

                if (typeof Fancybox !== 'undefined' && typeof Fancybox.show === 'function') {
                    Fancybox.show([{
                        src: img.dataset.big || img.src,
                        type: 'image',
                        caption: img.dataset.caption || ''
                    }]);
                }
            });
        })();

        document.addEventListener('submit', async function (e) {
            const form = e.target;
            if (!form || !form.classList.contains('js-lost-delete-form')) return;

            e.preventDefault();

            let confirmed = false;
            if (typeof window.confirmDialog === 'function') {
                confirmed = await window.confirmDialog({
                    title: 'Delete image',
                    message: 'Delete this lost image?',
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    danger: true
                });
            } else {
                confirmed = window.confirm('Delete this lost image?');
            }

            if (!confirmed) {
                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                }
                return;
            }

            if (typeof window.safeShowSpinner === 'function') {
                window.safeShowSpinner();
            }
            form.submit();
        });

        // Не даем карусели/свайпу перехватывать нажатие на удаление.
        ['pointerdown', 'touchstart', 'click'].forEach((evtName) => {
            document.addEventListener(evtName, function (e) {
                const delBtn = e.target.closest('.js-lost-delete-btn');
                if (!delBtn) return;
                e.stopPropagation();
            }, { passive: false });
        });

        (function restoreHideClosedState() {
            const checkbox = document.getElementById('js-hide-closed-rows');
            if (!checkbox) return;
            try {
                checkbox.checked = sessionStorage.getItem('mobile_paint_hide_closed') === '1';
            } catch (_) {
                checkbox.checked = false;
            }
        })();

        normalizeAndSortPaintRows();

        (function initLostCoverflow() {
            const track = document.querySelector('.js-lost-coverflow-track');
            if (!track) {
                return;
            }
            const slides = track.querySelectorAll('.lost-coverflow-slide');
            if (!slides.length) {
                return;
            }

            function updateActive() {
                const tr = track.getBoundingClientRect();
                const mid = tr.left + tr.width / 2;
                let best = null;
                let bestDist = Infinity;
                slides.forEach(function (slide) {
                    const r = slide.getBoundingClientRect();
                    const c = r.left + r.width / 2;
                    const d = Math.abs(c - mid);
                    if (d < bestDist) {
                        bestDist = d;
                        best = slide;
                    }
                });
                slides.forEach(function (s) {
                    s.classList.toggle('is-active', s === best);
                });
            }

            var raf = 0;
            function schedule() {
                if (raf) {
                    cancelAnimationFrame(raf);
                }
                raf = requestAnimationFrame(updateActive);
            }

            track.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule);

            function scrollByStep(dir) {
                var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth - 1);
                var step = Math.max(120, Math.floor(track.clientWidth * 0.45));
                if (maxScroll <= 2) {
                    return;
                }
                if (dir > 0) {
                    if (track.scrollLeft >= maxScroll - 6) {
                        track.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        track.scrollBy({ left: step, behavior: 'smooth' });
                    }
                } else {
                    if (track.scrollLeft <= 6) {
                        track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                    } else {
                        track.scrollBy({ left: -step, behavior: 'smooth' });
                    }
                }
                window.setTimeout(updateActive, 450);
            }

            var prevBtn = document.querySelector('.js-lost-coverflow-prev');
            var nextBtn = document.querySelector('.js-lost-coverflow-next');
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    scrollByStep(-1);
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    scrollByStep(1);
                });
            }

            if (slides.length <= 1) {
                if (prevBtn) {
                    prevBtn.style.display = 'none';
                }
                if (nextBtn) {
                    nextBtn.style.display = 'none';
                }
            }

            schedule();
            window.setTimeout(schedule, 80);
            window.setTimeout(schedule, 350);
        })();
    </script>
@endsection
