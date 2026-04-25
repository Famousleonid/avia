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
    </style>
@endsection

@section('content')
    @php
        $detailItems = $detailItems ?? collect();
        $fmtDisp = static function ($d) {
            if (!$d) {
                return '...';
            }

            return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
        };
    @endphp

    <div class="container-fluid machining-mobile-wrap">
        <div class="machining-mobile-card">
            <div class="machining-detail-wo-title">WO {{ $workorder->number }}</div>

            @php
                $machiningPhotoCount = $machiningPhotoCount ?? 0;
                $pdfCount = $pdfCount ?? 0;
            @endphp
            <input type="file" class="d-none js-machining-input-photo" accept="image/*" multiple>
            <input type="file" class="d-none js-machining-input-doc" accept="image/*">
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
                @php
                    $step = $item->step;
                    $p = $item->date_parent ?? null;
                    $finishYmd = $step->date_finish?->format('Y-m-d') ?? '';
                    $finishDisp = $fmtDisp($step->date_finish);
                    $startLine = $p && $p->date_start ? $fmtDisp($p->date_start) : '—';
                @endphp
                <div class="machining-detail-block js-detail-block" data-kind="step">
                    <div class="machining-detail-line">
                        {{ $item->detail_name ?? '—' }}<br>
                        <span class="text-secondary">{{ $item->detail_label ?? '' }}</span>
                    </div>
                    <div class="machining-detail-dates-step">
                        <span class="k">Start</span>
                        <span class="v">{{ $startLine }}</span>
                        <span class="k">Finish</span>
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
                    </div>
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

                if (form.classList.contains('js-mobile-machining-step-form') && payload.date_finish !== undefined) {
                    const ymd = payload.date_finish || '';
                    if (real) real.value = ymd;
                    if (display) {
                        display.value = ymd ? formatMachiningDateYmd(ymd) : '...';
                        display.classList.toggle('has-finish', !!ymd);
                    }
                    if (input) input.value = ymd;
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
            const inputPhoto = document.querySelector('.js-machining-input-photo');
            const inputDoc = document.querySelector('.js-machining-input-doc');
            const badgePhotos = document.querySelector('.js-machining-badge-photos');
            const badgePdfs = document.querySelector('.js-machining-badge-pdfs');

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

            if (btnPhoto && inputPhoto) {
                btnPhoto.addEventListener('click', function () {
                    if (!isOnline()) {
                        if (typeof window.notifyError === 'function') {
                            window.notifyError('No network connection.', 3500);
                        }
                        return;
                    }
                    inputPhoto.click();
                });
                inputPhoto.addEventListener('change', async function () {
                    const files = inputPhoto.files;
                    if (!files || !files.length) {
                        return;
                    }
                    if (typeof safeShowSpinner === 'function') {
                        safeShowSpinner();
                    }
                    const fd = new FormData();
                    for (let i = 0; i < files.length; i += 1) {
                        fd.append('photos[]', files[i]);
                    }
                    try {
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
                    } catch (e) {
                        const msg = e?.message || 'Upload failed';
                        if (typeof window.notifyError === 'function') {
                            window.notifyError(msg, 4000);
                        } else {
                            console.error(msg);
                        }
                    } finally {
                        inputPhoto.value = '';
                        if (typeof safeHideSpinner === 'function') {
                            safeHideSpinner();
                        }
                    }
                });
            }

            if (btnDoc && inputDoc) {
                btnDoc.addEventListener('click', function () {
                    if (!isOnline()) {
                        if (typeof window.notifyError === 'function') {
                            window.notifyError('No network connection.', 3500);
                        }
                        return;
                    }
                    inputDoc.click();
                });
                inputDoc.addEventListener('change', async function () {
                    const f = inputDoc.files && inputDoc.files[0];
                    if (!f) {
                        return;
                    }
                    if (typeof safeShowSpinner === 'function') {
                        safeShowSpinner();
                    }
                    const fd = new FormData();
                    fd.append('image', f);
                    try {
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
                    } catch (e) {
                        const msg = e?.message || 'Could not build PDF';
                        if (typeof window.notifyError === 'function') {
                            window.notifyError(msg, 4000);
                        } else {
                            console.error(msg);
                        }
                    } finally {
                        inputDoc.value = '';
                        if (typeof safeHideSpinner === 'function') {
                            safeHideSpinner();
                        }
                    }
                });
            }
        })();
    </script>
@endsection
