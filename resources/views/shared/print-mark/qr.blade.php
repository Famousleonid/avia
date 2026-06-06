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
    $printMarkQrSize = max(24, min(96, (int) ($printMarkQrSize ?? 64)));
    $printMarkQrTop = $printMarkQrTop ?? '8px';
    $printMarkQrRight = $printMarkQrRight ?? 'max(8px, calc((100vw - 1040px) / 2 + 8px))';
    $printMarkQrPrintTop = $printMarkQrPrintTop ?? '0';
    $printMarkQrPrintRight = $printMarkQrPrintRight ?? '8mm';
    $printMarkQrScreenPlacement = $printMarkQrScreenPlacement ?? 'viewport';
    $printMarkQrHostSelector = $printMarkQrHostSelector ?? '.std-page, .page, .std-sheet-container';
@endphp

@if($printMarkWoNumber !== '' && $printMarkQrEnabled)
    @php
        $printMarkPrintedAt = $printMarkPrintedAt ?? now();
        $printMarkPrintedBy = $printMarkPrintedBy ?? (optional(auth()->user())->name ?: 'system');
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
            .system-print-qr {
                background: #fff;
                height: {{ $printMarkQrSize }}px;
                pointer-events: none;
                position: fixed;
                right: {{ $printMarkQrRight }};
                top: {{ $printMarkQrTop }};
                width: {{ $printMarkQrSize }}px;
                z-index: 9999;
            }

            .system-print-qr[data-screen-placement="page"] {
                position: absolute;
                visibility: hidden;
            }

            .system-print-qr.system-print-qr--attached {
                visibility: visible;
            }

            .system-print-qr svg,
            .system-print-qr img {
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
            }
        </style>
        @endonce

        <div
            id="{{ $printMarkQrId }}"
            class="system-print-qr"
            data-screen-placement="{{ $printMarkQrScreenPlacement }}"
            aria-hidden="true"
        >{!! \App\Support\SimpleQrSvg::svg($printMarkPayload, $printMarkQrSize) !!}</div>

        @if($printMarkQrScreenPlacement === 'page')
            <script>
                (function attachPrintMarkQrToPage() {
                    const qr = document.getElementById(@json($printMarkQrId));
                    const hostSelector = @json($printMarkQrHostSelector);
                    if (!qr) return;

                    function attach() {
                        const host = document.querySelector(hostSelector);
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
                        qr.classList.add('system-print-qr--attached');
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
