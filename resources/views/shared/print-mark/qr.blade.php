@php
    $printMarkCandidate = $printMarkWorkorder ?? ($current_wo ?? ($current_workorder ?? ($workorder ?? null)));
    $printMarkWoNumber = null;

    if (is_object($printMarkCandidate)) {
        $printMarkWoNumber = $printMarkCandidate->number ?? null;
    } elseif (is_scalar($printMarkCandidate)) {
        $printMarkWoNumber = $printMarkCandidate;
    }

    $printMarkWoNumber = preg_replace('/\D+/', '', (string) $printMarkWoNumber);
    $printMarkQrEnabled = \App\Models\ProjectSetting::boolean(\App\Models\ProjectSetting::PRINT_FORMS_QR_ENABLED, true);
    $printMarkQrSize = max(24, min(96, (int) ($printMarkQrSize ?? 40)));
    $printMarkQrTop = $printMarkQrTop ?? '3mm';
    $printMarkQrRight = $printMarkQrRight ?? 'max(4mm, calc(100vw - var(--container-margin-left, 0px) - var(--container-max-width, 100vw) + 4mm))';
    $printMarkQrPageRight = $printMarkQrPageRight ?? '4mm';
    $printMarkQrPrintTop = $printMarkQrPrintTop ?? '0';
    $printMarkQrPrintRight = $printMarkQrPrintRight ?? '8mm';
    $printMarkQrScreenPlacement = $printMarkQrScreenPlacement ?? 'page';
    $printMarkQrHostSelector = $printMarkQrHostSelector ?? '.tdr-primary-sheet, .form-page-block, .std-sheet-container, .page, .std-page, .cert-wrap, .page-wrap, .container-fluid, main.content, body';
    $printMarkLabel = trim((string) ($printMarkLabel ?? ''));
    $printMarkWarnings = array_values(array_unique(array_filter((array) ($printMarkWarnings ?? []))));
@endphp

@if($printMarkWoNumber !== '' && $printMarkQrEnabled)
    @php
        $printMarkPrintedAt = $printMarkPrintedAt ?? now();
        $printMarkPrintedBy = $printMarkPrintedBy ?? (optional(auth()->user())->selection_name ?: 'system');
        $printMarkRouteName = optional(request()->route())->getName();
        $printMarkRouteFormNames = [
            'tdrs.wo_BoxTitle' => 'WO Box Title',
            'tdrs.woProcessForm' => 'WO Process Sheet',
            'tdrs.tdrForm' => 'TDR Form',
            'tdrs.prlForm' => 'PRL',
            'tdrs.bushPrlForm' => 'Bushing PRL',
            'tdrs.kitForm' => 'KIT',
            'tdrs.specProcessForm' => 'Special Process Form',
            'tdrs.specProcessFormEmp' => 'Special Process Form',
            'tdrs.logCardForm' => 'Log Card',
            'tdrs.serviceBulletinLog' => 'Service Bulletin Log',
            'tdrs.ndtStd' => 'NDT',
            'tdrs.cadStd' => 'CAD',
            'tdrs.paintStd' => 'Paint',
            'tdrs.stressStd' => 'Stress Relief',
            'tdr-processes.travelForm' => 'Traveler',
            'tdr-processes.packageForms' => 'Package Process Forms',
            'log_card.logCardForm' => 'Log Card',
            'log_card.sertDistrForm' => 'Certificate of Destruction',
            'rm_reports.rmRecordForm' => 'Repair and Modification Record',
            'transfers.transferForm' => 'Transfer Sheet',
            'transfers.transfersForm' => 'Transfer Sheet',
            'wo_bushings.specProcessForm' => 'Bushing Special Process Form',
            'quality.forms.shipment_release' => 'Shipment Release Form',
            'quality.forms.log_card' => 'Log Card',
            'workorders.measurements.fc-table' => 'Fits and Clearances',
        ];
        $printMarkFormName = trim((string) ($printMarkFormName
            ?? ($process_name->process_sheet_name ?? ($process_name->name ?? null))
            ?? ($printMarkRouteFormNames[$printMarkRouteName] ?? 'Printed Form')));

        $printMarkPayload = null;
        try {
            $printMarkRecord = app(\App\Services\PrintMarkService::class)->create([
                'workorder_id' => is_object($printMarkCandidate) ? ($printMarkCandidate->id ?? null) : null,
                'workorder_number' => 'W' . $printMarkWoNumber,
                'form_name' => $printMarkFormName,
                'requirement_warnings' => $printMarkWarnings,
                'printed_by_user_id' => auth()->id(),
                'printed_by_name' => $printMarkPrintedBy,
                'printed_at' => $printMarkPrintedAt,
            ]);
            $printMarkPayload = app(\App\Services\PrintMarkService::class)->publicUrl($printMarkRecord);
        } catch (\Throwable) {
            $printMarkPayload = null;
        }
    @endphp

    @if($printMarkPayload)
        @php
            $printMarkQrId = 'system-print-qr-' . substr(md5($printMarkPayload), 0, 12);
        @endphp
        @once
        <style>
            :root {
                --print-mark-qr-size: {{ $printMarkQrSize }}px;
            }

            .system-print-qr {
                background: #fff;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                height: {{ $printMarkQrSize }}px;
                min-width: {{ $printMarkQrSize }}px;
                pointer-events: auto;
                position: fixed;
                right: {{ $printMarkQrRight }};
                top: {{ $printMarkQrTop }};
                width: auto;
                z-index: 9999;
            }

            .system-print-qr__label {
                color: #000;
                font-family: Arial, sans-serif;
                font-size: 13px;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
            }

            .system-print-qr__code {
                display: block;
                flex: 0 0 {{ $printMarkQrSize }}px;
                height: {{ $printMarkQrSize }}px;
                width: {{ $printMarkQrSize }}px;
            }

            .system-print-qr__warning {
                display: none;
                position: absolute;
                right: 0;
                top: calc(100% + 4px);
                width: max-content;
                max-width: 260px;
                padding: 5px 7px;
                background: #fff;
                border: 1px solid #dc3545;
                color: #dc3545;
                font: 700 12px/1.2 Arial, sans-serif;
                z-index: 1;
            }
            .system-print-qr:hover .system-print-qr__warning,
            .system-print-qr:focus-within .system-print-qr__warning { display: block; }

            @media screen {
                .system-print-qr {
                    left: var(--print-mark-screen-left, auto);
                    right: var(--print-mark-screen-right, {{ $printMarkQrRight }});
                    transform: var(--print-mark-screen-transform, none);
                    transform-origin: var(--print-mark-screen-transform-origin, initial);
                }
            }

            .system-print-qr[data-screen-placement="page"] {
                left: auto;
                position: absolute;
                right: {{ $printMarkQrPageRight }};
                top: {{ $printMarkQrTop }};
                visibility: hidden;
            }

            .system-print-qr.system-print-qr--attached {
                visibility: visible;
            }

            .system-print-qr__code svg,
            .system-print-qr__code img {
                display: block;
                height: {{ $printMarkQrSize }}px;
                width: {{ $printMarkQrSize }}px;
            }

            @media print {
                .system-print-qr {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    position: fixed;
                    visibility: visible;
                    right: {{ $printMarkQrPrintRight }};
                    top: {{ $printMarkQrPrintTop }};
                }
                .system-print-qr__warning { display: none !important; }
            }
        </style>
        @endonce

        <div
            id="{{ $printMarkQrId }}"
            class="system-print-qr"
            data-screen-placement="{{ $printMarkQrScreenPlacement }}"
            aria-label="{{ $printMarkWarnings ? 'Missing required ' . implode(' and ', $printMarkWarnings) : 'Print verification QR code' }}"
        >@if($printMarkLabel !== '')<span class="system-print-qr__label">{{ $printMarkLabel }}</span>@endif<span class="system-print-qr__code">{!! \App\Support\SimpleQrSvg::svg($printMarkPayload, $printMarkQrSize) !!}</span>@if($printMarkWarnings)<span class="system-print-qr__warning">Missing required {{ implode(' and ', $printMarkWarnings) }}</span>@endif</div>

        @if($printMarkQrScreenPlacement === 'page')
            <script>
                (function attachPrintMarkQrToPage() {
                    const qr = document.getElementById(@json($printMarkQrId));
                    const hostSelector = @json($printMarkQrHostSelector);
                    if (!qr) return;

                    function queryFirst(selector, root) {
                        try {
                            return (root || document).querySelector(selector);
                        } catch (error) {
                            return null;
                        }
                    }

                    function resolveHost() {
                        const selectors = String(hostSelector || '')
                            .split(',')
                            .map(function (selector) { return selector.trim(); })
                            .filter(Boolean);

                        for (const selector of selectors) {
                            const host = queryFirst(selector);
                            if (host) return host;
                        }

                        return document.body;
                    }

                    function syncHostScale(host) {
                        if (!host || typeof DOMMatrixReadOnly === 'undefined') return;

                        try {
                            const transform = window.getComputedStyle(host).transform;
                            if (!transform || transform === 'none') {
                                qr.style.removeProperty('--print-mark-screen-transform');
                                qr.style.removeProperty('--print-mark-screen-transform-origin');
                                return;
                            }

                            const matrix = new DOMMatrixReadOnly(transform);
                            const scaleX = Math.hypot(matrix.a, matrix.b);
                            const scaleY = Math.hypot(matrix.c, matrix.d);
                            if (!Number.isFinite(scaleX) || !Number.isFinite(scaleY) || scaleX <= 0 || scaleY <= 0) return;

                            if (Math.abs(scaleX - 1) > 0.001 || Math.abs(scaleY - 1) > 0.001) {
                                qr.style.setProperty('--print-mark-screen-transform-origin', 'top right');
                                qr.style.setProperty('--print-mark-screen-transform', 'scale(' + (1 / scaleX) + ', ' + (1 / scaleY) + ')');
                            } else {
                                qr.style.removeProperty('--print-mark-screen-transform');
                                qr.style.removeProperty('--print-mark-screen-transform-origin');
                            }
                        } catch (error) {
                            qr.style.removeProperty('--print-mark-screen-transform');
                            qr.style.removeProperty('--print-mark-screen-transform-origin');
                        }
                    }

                    function syncPlacement(host) {
                        syncHostScale(host);
                        qr.style.removeProperty('--print-mark-screen-left');
                        qr.style.removeProperty('--print-mark-screen-right');
                    }

                    function attach() {
                        const host = resolveHost();
                        if (!host) {
                            qr.classList.add('system-print-qr--attached');
                            return;
                        }

                        if (window.getComputedStyle(host).position === 'static') {
                            host.style.position = 'relative';
                        }
                        if (qr.parentElement !== host) {
                            host.insertBefore(qr, host.firstChild);
                        }
                        syncPlacement(host);
                        qr.classList.add('system-print-qr--attached');

                        window.addEventListener('resize', function () {
                            syncPlacement(host);
                        });

                        if (window.MutationObserver) {
                            const observer = new MutationObserver(function () {
                                syncPlacement(host);
                            });
                            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['style'] });
                        }
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', attach, { once: true });
                    } else {
                        attach();
                    }
                })();
            </script>
        @endif
    @endif
@endif
