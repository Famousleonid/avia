<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    @php
        $tdrFormConfig = config('tdr_forms.ndtFormStd');
        $componentName = $current_wo->unit->name;
        $componentName = (string) $componentName;
        $manualNumber = substr((string) ($manual->number ?? ''), 0, 8);
        $findNdtProcess = function ($processNameId) use ($ndt_processes) {
            if (!$processNameId) {
                return '';
            }

            $process = collect($ndt_processes)->first(function ($item) use ($processNameId) {
                return (int) ($item->process_names_id ?? 0) === (int) $processNameId;
            });

            return (string) ($process->process ?? '');
        };
        $ndtProcessMap = [
            1 => $findNdtProcess($ndt1_name_id ?? null),
            4 => $findNdtProcess($ndt4_name_id ?? null),
            5 => $findNdtProcess($ndt5_name_id ?? null),
            6 => $findNdtProcess($ndt6_name_id ?? null),
        ];
        $ndt_table_pages = $ndt_table_pages ?? [[]];
        $ndt_total_pages = max(1, count($ndt_table_pages));
        $ndtGlobalRowIndex = 1;
        $ndtSettingsStorageKey = $tdrFormConfig['storage_key'] ?? 'ndtFormStd_print_settings';
        $ndtTableRowsKey = $tdrFormConfig['table_rows_key'] ?? 'stdTableRows';
        $ndtDefaultRows = (int) ($tdrFormConfig['table_rows_default'] ?? 14);
    @endphp
    <script>
        (function syncNdtRowsQueryBeforeRender() {
            if (!window.UserScopedStorage) return;
            const storageKey = @json($ndtSettingsStorageKey);
            const rowsKey = @json($ndtTableRowsKey);
            const defaultRows = {{ $ndtDefaultRows }};
            let rows = defaultRows;
            try {
                const saved = window.UserScopedStorage.getItem(storageKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    const savedRows = parseInt(String(parsed?.[rowsKey] ?? '').replace(/[^\d]/g, ''), 10);
                    if (Number.isFinite(savedRows) && savedRows >= 1) rows = savedRows;
                }
            } catch (e) {
                rows = defaultRows;
            }

            const url = new URL(window.location.href);
            const queryRows = parseInt(String(url.searchParams.get('ndt_table_rows') ?? '').replace(/[^\d]/g, ''), 10);
            if (rows === defaultRows) {
                if (url.searchParams.has('ndt_table_rows')) {
                    url.searchParams.delete('ndt_table_rows');
                    window.location.replace(url.href);
                }
                return;
            }
            if (queryRows !== rows) {
                url.searchParams.set('ndt_table_rows', String(rows));
                window.location.replace(url.href);
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDT Form</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
    <style>
        .std-page--ndt .std-header-title--ndt {
            transform: translateX(-38px);
        }

        .std-page--ndt .std-ndt-cmm-label {
            font-size: 16px;
        }

        .std-page--ndt .std-ndt-cmm-box {
            font-size: 16px;
        }

        .std-page--ndt .std-ndt-title {
            transform: translate(12px, 4px);
        }

        .std-page--ndt .std-grid-row--header > .std-cell {
            overflow-wrap: normal;
            padding-left: 3px;
            padding-right: 3px;
            white-space: nowrap;
        }

        @media print {
            .std-page--ndt .std-header {
                gap: 11px;
            }

            .std-page--ndt .std-header-top {
                grid-template-columns: 188px minmax(0, 1fr);
                column-gap: 11px;
            }

            .std-page--ndt .std-header-logo {
                width: 178px;
            }

            .std-page--ndt .std-header-title--ndt {
                font-size: 28px;
                margin-top: 4px;
            }

            .std-page--ndt .std-meta-grid {
                column-gap: 24px;
            }

            .std-page--ndt .std-meta-column {
                gap: 7px;
            }

            .std-page--ndt .std-meta-row {
                grid-template-columns: 134px minmax(0, 1fr);
                min-height: 30px;
            }

            .std-page--ndt .std-meta-row--right {
                grid-template-columns: 82px minmax(0, 1fr);
            }

            .std-page--ndt .std-meta-label {
                font-size: 13px;
            }

            .std-page--ndt .std-meta-value {
                min-height: 24px;
            }

            .std-page--ndt .std-ndt-grid--aligned {
                grid-template-rows: auto 24px auto 24px auto 44px;
                column-gap: 17px;
                row-gap: 4px;
            }

            .std-page--ndt .std-ndt-title {
                font-size: 14px;
            }

            .std-page--ndt .std-ndt-line {
                grid-template-columns: 24px minmax(0, 1fr);
                min-height: 24px;
            }

            .std-page--ndt .std-ndt-index,
            .std-page--ndt .std-ndt-value {
                font-size: 12px;
            }

            .std-page--ndt .std-manual-ref-box {
                min-height: 44px;
            }

            .std-page--ndt .std-table {
                margin-top: 8px;
            }

            .std-page--ndt {
                --std-row-min-height: 35px;
                --std-header-row-height: 34px;
            }

            .std-page--ndt .std-grid-row--header > .std-cell {
                min-height: var(--std-header-row-height);
                font-size: 13px;
            }

            .std-page--ndt .std-footer-grid {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
@include('shared.print-mark.qr', [
    'printMarkWorkorder' => $current_wo ?? null,
    'printMarkQrSize' => 40,
    'printMarkQrScreenPlacement' => 'page',
    'printMarkQrTop' => '3mm',
    'printMarkQrPrintTop' => '3mm',
    'printMarkQrPrintRight' => '4mm',
])
@include('admin.tdrs.partials.std-sheet-toolbar')

@foreach($ndt_table_pages as $ndtPageIndex => $ndtPageRows)
    @php $ndtPageNum = $ndtPageIndex + 1; @endphp
    <div class="container-fluid std-sheet-container {{ $ndtPageNum === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
        <div class="std-page std-page--ndt page data-page {{ $ndtPageNum === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $ndtPageNum }}">
            <div class="std-header header-page">
                <div class="std-header-top">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                    <h1 class="std-header-title std-header-title--ndt">NDT PROCESS SHEET</h1>
                </div>

                <div class="std-meta-grid">
                    <div class="std-meta-column">
                        <div class="std-meta-row">
                            <div class="std-meta-label">COMPONENT NAME:</div>
                            <div class="std-meta-value">
                                <strong>
                                    <span class="std-component-name" @if(strlen($componentName) > 30) data-long="1" @endif>{{ $componentName }}</span>
                                </strong>
                            </div>
                        </div>
                        <div class="std-meta-row">
                            <div class="std-meta-label">PART NUMBER:</div>
                            <div class="std-meta-value"><strong>{{ $current_wo->unit->part_number }}</strong></div>
                        </div>
                        <div class="std-meta-row">
                            <div class="std-meta-label">WORK ORDER No:</div>
                            <div class="std-meta-value"><strong>W{{ $current_wo->number }}</strong></div>
                        </div>
                        <div class="std-meta-row">
                            <div class="std-meta-label">SERIAL No:</div>
                            <div class="std-meta-value"><strong>{{ $current_wo->serial_number }}</strong></div>
                        </div>
                    </div>

                    <div class="std-meta-column">
                        <div class="std-meta-row std-meta-row--right">
                            <div class="std-meta-label">DATE:</div>
                            <div class="std-meta-value"></div>
                        </div>
                        <div class="std-meta-row std-meta-row--right">
                            <div class="std-meta-label">RO No:</div>
                            <div class="std-meta-value"></div>
                        </div>
                        <div class="std-meta-row std-meta-row--right">
                            <div class="std-meta-label">VENDOR:</div>
                            <div class="std-meta-value">Skyservice</div>
                        </div>
                    </div>
                </div>

                <div class="std-ndt-grid std-ndt-grid--aligned">
                    <div class="std-ndt-title std-ndt-title--magnetic">MAGNETIC PARTICLE AS PER:</div>
                    <div class="std-ndt-title std-ndt-title--liquid">LIQUID/FLUID PENETRANT AS PER:</div>
                    <div class="std-ndt-title std-ndt-title--ultrasound">ULTRASOUND AS PER:</div>
                    <div class="std-ndt-title std-ndt-title--eddy">EDDY CURRENT AS PER:</div>

                    <div class="std-ndt-line std-ndt-line--one">
                            <div class="std-ndt-index">#1</div>
                            <div class="std-ndt-value">
                                <span @if(strlen($ndtProcessMap[1]) > 25) class="std-process-long" @endif>{{ $ndtProcessMap[1] }}</span>
                            </div>
                    </div>
                    <div class="std-ndt-line std-ndt-line--two">
                        <div class="std-ndt-index">#2</div>
                        <div class="std-ndt-value"></div>
                    </div>
                    <div class="std-ndt-line std-ndt-line--three">
                        <div class="std-ndt-index">#3</div>
                        <div class="std-ndt-value"></div>
                    </div>

                    <div class="std-ndt-line std-ndt-line--four">
                            <div class="std-ndt-index">#4</div>
                            <div class="std-ndt-value">
                                <span @if(strlen($ndtProcessMap[4]) > 25) class="std-process-long" @endif>{{ $ndtProcessMap[4] }}</span>
                            </div>
                    </div>
                    <div class="std-ndt-line std-ndt-line--five">
                        <div class="std-ndt-index">#5</div>
                        <div class="std-ndt-value">{{ $ndtProcessMap[5] }}</div>
                    </div>
                    <div class="std-ndt-line std-ndt-line--six">
                        <div class="std-ndt-index">#6</div>
                        <div class="std-ndt-value">{{ $ndtProcessMap[6] }}</div>
                    </div>

                    <div class="std-ndt-line std-ndt-line--seven">
                        <div class="std-ndt-index">#7</div>
                        <div class="std-ndt-value"></div>
                    </div>
                    <div class="std-meta-label std-ndt-cmm-label">CMM No:</div>
                    <div class="std-manual-ref-box std-ndt-cmm-box">{{ $manualNumber }}</div>
                </div>
            </div>

            <div class="std-table table-header" style="--std-table-columns: 1fr 2.4fr 3.2fr 2fr 0.9fr 1.25fr 1.25fr;">
                <div class="std-grid-row std-grid-row--header">
                    <div class="std-cell">ITEM No.</div>
                    <div class="std-cell">Part No</div>
                    <div class="std-cell">DESCRIPTION</div>
                    <div class="std-cell">PROCESS No.</div>
                    <div class="std-cell">QTY</div>
                    <div class="std-cell">ACCEPT</div>
                    <div class="std-cell">REJECT</div>
                </div>
            </div>

            <div class="all-rows-container page-rows-container" style="--std-table-columns: 1fr 2.4fr 3.2fr 2fr 0.9fr 1.25fr 1.25fr;">
                @if(empty($ndt_components))
                    <div class="data-row-ndt std-grid-row std-grid-row--full">
                        <div class="std-cell"><strong>No NDT components with ndt_list flag</strong></div>
                    </div>
                @endif

                @foreach($ndtPageRows as $ndtEntry)
                    @if(($ndtEntry['kind'] ?? '') === 'manual')
                        <div class="data-row-ndt manual-row std-grid-row std-grid-row--manual std-grid-row--full" data-row-index="{{ $ndtGlobalRowIndex }}">
                            <div class="std-cell"><strong>{{ $ndtEntry['text'] ?? '' }}</strong></div>
                        </div>
                        @php $ndtGlobalRowIndex++; @endphp
                    @elseif(($ndtEntry['kind'] ?? '') === 'data')
                        @php
                            $component = $ndtEntry['component'];
                            $rowHeight = max(35, (int) ($component->row_height ?? 35));
                        @endphp
                        <div class="data-row-ndt std-grid-row" data-row-index="{{ $ndtGlobalRowIndex }}" style="--std-row-min-height: {{ $rowHeight }}px;">
                            <div class="std-cell">
                                <span class="std-cell--multiline">{{ $component->ipl_num }}</span>
                            </div>
                            <div class="std-cell">{{ $component->part_number }}</div>
                            <div class="std-cell">{{ $component->name }}</div>
                            <div class="std-cell">{{ $component->process_name }}</div>
                            <div class="std-cell">{{ $component->qty }}</div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                        </div>
                        @php $ndtGlobalRowIndex++; @endphp
                    @else
                        <div class="data-row-ndt empty-row std-grid-row">
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                            <div class="std-cell"></div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="std-table-summary">
                {{ __('Total QTY:') }} <strong>{{ $ndtSums['total'] ?? 0 }}</strong>
                ( {{ __('MPI:') }} <strong>{{ $ndtSums['mpi'] ?? 0 }}</strong> {{ __(' ; ') }}
                {{ __('FPI:') }} <strong>{{ $ndtSums['fpi'] ?? 0 }}</strong> )
            </div>

            <footer class="std-footer">
                <div class="std-footer-grid">
                    <div class="std-footer-left">{{ __('Form #016') }}</div>
                    <div class="std-footer-center">
                        {{ __('Page') }} <span class="page-number" data-tdr-footer-page>{{ $ndtPageNum }}</span>
                        {{ __('of') }} <span class="total-pages" data-tdr-footer-total>{{ $ndt_total_pages }}</span>
                    </div>
                    <div class="std-footer-right">
                        {{ __('Rev#0, 15/Dec/2012') }}
                    </div>
                </div>
            </footer>
        </div>
    </div>
@endforeach

@include('shared.tdr-forms._print-settings-modal', ['formType' => 'ndtFormStd', 'formConfig' => $tdrFormConfig])

<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>
<script>
    window.tdrFormApplyTableRowLimits = function () {};
</script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'ndtFormStd', 'formConfig' => $tdrFormConfig])
</body>
</html>
