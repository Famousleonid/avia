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
            padding: .25rem;
            margin: 0;
        }
        .machining-mobile-table {
            color: #e9ecef;
            font-size: .72rem;
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }
        .machining-mobile-table thead th {
            color: #9fb0c0;
            font-size: .67rem;
            white-space: nowrap;
            padding: .2rem .18rem;
        }
        .machining-mobile-col-queue {
            text-align: center;
            width: 24px;
            max-width: 24px;
        }
        .machining-mobile-table td, .machining-mobile-table th {
            vertical-align: middle;
            padding: .2rem .18rem;
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
        #mobileLostCarousel .carousel-control-prev-icon,
        #mobileLostCarousel .carousel-control-next-icon {
            filter: brightness(0) saturate(100%) invert(70%) sepia(90%) saturate(1314%) hue-rotate(152deg) brightness(102%) contrast(101%);
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
        .machining-mobile-date {
            min-width: 68px;
            white-space: nowrap;
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .machining-mobile-date-head {
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .machining-mobile-date .js-mobile-date-display {
            width: 84px;
            max-width: 84px;
            font-size: .66rem;
            padding: .08rem .2rem;
            height: calc(1.25em + .25rem + 2px);
            margin-left: auto;
            text-align: center;
        }
        .machining-mobile-date .js-mobile-date-display.has-finish {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }
        .machining-mobile-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            padding: 0 .15rem .25rem;
            font-size: .66rem;
            color: #9fb0c0;
        }
    </style>
@endsection

@section('content')
    @php
        $rows = $rows ?? collect();
        $fmt = static function ($d) {
            if (!$d) {
                return '—';
            }

            return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
        };
    @endphp

    <div class="container-fluid machining-mobile-wrap">
        <div class="machining-mobile-card">
            <div class="machining-mobile-toolbar">
                <label class="d-inline-flex align-items-center gap-1 m-0">
                    <input type="checkbox" id="js-hide-closed-rows">
                    <span>Hide closed rows</span>
                </label>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-sm machining-mobile-table" id="js-mobile-machining-table">
                    <colgroup>
                        <col style="width:36px">
                        <col style="width:70px">
                        <col>
                        <col style="width:92px">
                        <col style="width:92px">
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="machining-mobile-col-queue">Queue</th>
                        <th>WO</th>
                        <th>Aircraft</th>
                        <th class="machining-mobile-date-head">Start</th>
                        <th class="machining-mobile-date-head">Finish</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $wo = $row->workorder;
                            $editTp = $row->edit_machining_process ?? null;
                            $startYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                            $startDisp = $editTp?->date_start ? ($editTp->date_start->format('d') . '.' . strtolower($editTp->date_start->format('M')) . '.' . $editTp->date_start->format('Y')) : '...';
                            $finishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                            $finishDisp = $editTp?->date_finish ? ($editTp->date_finish->format('d') . '.' . strtolower($editTp->date_finish->format('M')) . '.' . $editTp->date_finish->format('Y')) : '...';
                        @endphp
                        <tr class="js-machining-row"
                            data-wo-number="{{ (int) $wo->number }}"
                            data-queue-pos="{{ $row->queue_position !== null ? (int) $row->queue_position : '' }}"
                            data-start-ymd="{{ $startYmd }}"
                            data-finish-ymd="{{ $finishYmd }}">
                            <td class="machining-mobile-col-queue text-info js-queue-cell">{{ $row->queue_position !== null ? str_pad((string) $row->queue_position, 2, '0', STR_PAD_LEFT) : '—' }}</td>
                            <td>{{ $wo->number }}</td>
                            <td>{{ $row->plane_type !== '' ? $row->plane_type : '—' }}</td>
                            <td class="machining-mobile-date">
                                @if($editTp)
                                    <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-machining-date-form m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="from_machining_index" value="1">
                                        <input type="hidden" name="date_start" value="{{ $startYmd }}" class="js-mobile-date-real">
                                        <input type="date" value="{{ $startYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                        <input type="text"
                                               class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $startYmd !== '' ? 'has-finish' : '' }}"
                                               value="{{ $startDisp }}"
                                               placeholder="..."
                                               readonly>
                                    </form>
                                @else
                                    {{ $fmt($row->date_start) }}
                                @endif
                            </td>
                            <td class="machining-mobile-date">
                                @if($editTp)
                                    <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-machining-date-form m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="from_machining_index" value="1">
                                        <input type="hidden" name="date_finish" value="{{ $finishYmd }}" class="js-mobile-date-real">
                                        <input type="date" value="{{ $finishYmd }}" class="d-none js-mobile-date-picker" tabindex="-1">
                                        <input type="text"
                                               class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $finishYmd !== '' ? 'has-finish' : '' }}"
                                               value="{{ $finishDisp }}"
                                               placeholder="..."
                                               readonly>
                                    </form>
                                @else
                                    {{ $fmt($row->date_finish) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-3">No machining workorders.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
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
            const form = display.closest('.js-mobile-machining-date-form');
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

            const form = input.closest('.js-mobile-machining-date-form');
            if (!form) return;
            const row = form.closest('.js-machining-row');
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
                    if (real) {
                        real.value = '';
                    }
                    if (display) {
                        display.value = '...';
                        display.classList.remove('has-finish');
                    }
                    delete input.dataset.openedAt;
                    return;
                }
                if (msSinceOpen < 85) {
                    input.value = '';
                    if (real) {
                        real.value = '';
                    }
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

                if (row) {
                    const startReal = row.querySelector('input[name="date_start"].js-mobile-date-real');
                    const finishReal = row.querySelector('input[name="date_finish"].js-mobile-date-real');
                    row.dataset.startYmd = startReal?.value || '';
                    row.dataset.finishYmd = finishReal?.value || '';

                    if ((real?.name || '') === 'date_finish' && (real?.value || '') !== '') {
                        row.dataset.queuePos = '';
                        const queueCell = row.querySelector('.js-queue-cell');
                        if (queueCell) queueCell.textContent = '—';
                    }
                    normalizeAndSortMachiningRows();
                }
            } catch (err) {
                if (real) real.value = prevRealValue;
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

        function normalizeAndSortMachiningRows() {
            const tbody = document.querySelector('#js-mobile-machining-table tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr.js-machining-row'));
            const queued = rows
                .filter((r) => Number.parseInt(r.dataset.queuePos || '', 10) > 0)
                .sort((a, b) => Number.parseInt(a.dataset.queuePos, 10) - Number.parseInt(b.dataset.queuePos, 10));
            const unqueued = rows
                .filter((r) => !(Number.parseInt(r.dataset.queuePos || '', 10) > 0))
                .sort((a, b) => Number.parseInt(b.dataset.woNumber || '0', 10) - Number.parseInt(a.dataset.woNumber || '0', 10));

            queued.forEach((r, idx) => {
                const pos = idx + 1;
                r.dataset.queuePos = String(pos);
                const queueCell = r.querySelector('.js-queue-cell');
                if (queueCell) queueCell.textContent = String(pos).padStart(2, '0');
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
            document.querySelectorAll('#js-mobile-machining-table tbody tr.js-machining-row').forEach((row) => {
                const closed = (row.dataset.startYmd || '') !== '' && (row.dataset.finishYmd || '') !== '';
                row.style.display = (needHide && closed) ? 'none' : '';
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'js-hide-closed-rows') {
                try {
                    sessionStorage.setItem('mobile_machining_hide_closed', e.target.checked ? '1' : '0');
                } catch (_) {}
                applyClosedFilter();
            }
        });

        (function restoreHideClosedState() {
            const checkbox = document.getElementById('js-hide-closed-rows');
            if (!checkbox) return;
            try {
                checkbox.checked = sessionStorage.getItem('mobile_machining_hide_closed') === '1';
            } catch (_) {
                checkbox.checked = false;
            }
        })();

        normalizeAndSortMachiningRows();
    </script>
@endsection
