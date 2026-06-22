<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    @php
        $tdrFormConfig = config('tdr_forms.cadFormStd');
        $componentName = (string) $current_wo->displayDescription();
        $manualNumber = substr((string) ($manual->number ?? ''), 0, 8);
        $cad_table_pages = $cad_table_pages ?? [[]];
        $cad_total_pages = max(1, count($cad_table_pages));
        $cadGlobalRowIndex = 1;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAD PLATE PROCESS SHEET</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
    <style>
        .std-page--cad .std-header {
            gap: 18px;
        }

        .std-page--cad .std-header-title--cad {
            transform: translateX(-28px);
        }

        .std-page--cad .std-meta-column {
            gap: 10px;
        }

        .std-page--cad .std-meta-row {
            grid-template-columns: 170px minmax(0, 1fr);
            min-height: 36px;
        }

        .std-page--cad .std-meta-row--right {
            grid-template-columns: 90px minmax(0, 1fr);
        }

        .std-page--cad .std-meta-label {
            white-space: nowrap;
        }

        .std-page--cad .std-meta-value {
            min-height: 30px;
        }

        .std-page--cad {
            --std-row-min-height: 34px;
        }

        .std-page--cad .std-cad-instruction {
            display: block;
            font-size: 17px;
            line-height: 1.2;
            margin-top: 10px;
            overflow: visible;
            white-space: nowrap;
        }

        @media print {
            .std-page--cad .std-header {
                gap: 18px;
            }

            .std-page--cad .std-meta-column {
                gap: 9px;
            }

            .std-page--cad .std-meta-row {
                grid-template-columns: 150px minmax(0, 1fr);
                min-height: 30px;
            }

            .std-page--cad .std-meta-row--right {
                grid-template-columns: 82px minmax(0, 1fr);
            }

            .std-page--cad .std-meta-label {
                white-space: nowrap;
            }

            .std-page--cad .std-meta-value {
                min-height: 25px;
            }

            .std-page--cad .std-cad-instruction {
                font-size: 15px;
                line-height: 1.15;
                margin-top: 8px;
                overflow: visible;
                white-space: nowrap;
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

@foreach($cad_table_pages as $cadPageIndex => $cadPageRows)
    @php $cadPageNum = $cadPageIndex + 1; @endphp
    <div class="container-fluid std-sheet-container {{ $cadPageNum === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
        <div class="std-page std-page--cad page data-page {{ $cadPageNum === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $cadPageNum }}">
            <div class="std-header header-page">
                <div class="std-header-top">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                    <h2 class="std-header-title std-header-title--cad">CADMIUM PLATING PROCESS SHEET</h2>
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
                            <div class="std-meta-value"><strong>Micro Custom</strong></div>
                        </div>
                    </div>
                </div>

                <div class="std-instruction std-cad-instruction">
                    Perform the CAD plate as specified under Process No. and in accordance with SMM No.
                </div>
            </div>

            <div class="std-table table-header" style="--std-table-columns: 1fr 2fr 2.7fr 3.8fr 0.9fr 1.6fr;">
                <div class="std-grid-row std-grid-row--header">
                    <div class="std-cell">ITEM No.</div>
                    <div class="std-cell">PART No.</div>
                    <div class="std-cell">DESCRIPTION</div>
                    <div class="std-cell">PROCESS No.</div>
                    <div class="std-cell">QTY</div>
                    <div class="std-cell">CMM No.</div>
                </div>
            </div>

            <div class="all-rows-container page-rows-container" style="--std-table-columns: 1fr 2fr 2.7fr 3.8fr 0.9fr 1.6fr;">
                @if(empty($cad_components))
                    <div class="data-row std-grid-row std-grid-row--full">
                        <div class="std-cell"><strong>No CAD components with cad_list flag</strong></div>
                    </div>
                @endif

                @foreach($cadPageRows as $cadEntry)
                    @if(($cadEntry['kind'] ?? '') === 'manual')
                        <div class="data-row manual-row std-grid-row std-grid-row--manual std-grid-row--full" data-row-index="{{ $cadGlobalRowIndex }}">
                            <div class="std-cell"><strong>{{ $cadEntry['text'] ?? '' }}</strong></div>
                        </div>
                        @php $cadGlobalRowIndex++; @endphp
                    @elseif(($cadEntry['kind'] ?? '') === 'data')
                        @php
                            $component = $cadEntry['component'];
                            $rowHeight = max(34, (int) ($component->row_height ?? 34));
                            $processText = (string) ($component->process_name ?? '');
                            $processLength = mb_strlen($processText);
                            $processFitClass = $processLength > 48
                                ? 'std-cell-fit--xs'
                                : ($processLength > 38 ? 'std-cell-fit--sm' : '');
                        @endphp
                        <div class="data-row std-grid-row" data-row-index="{{ $cadGlobalRowIndex }}" style="--std-row-min-height: {{ $rowHeight }}px;">
                            <div class="std-cell">
                                <span class="std-cell--multiline">{{ $component->ipl_num }}</span>
                            </div>
                            <div class="std-cell">{{ $component->part_number }}</div>
                            <div class="std-cell">{{ $component->name }}</div>
                            <div class="std-cell std-cell-fit {{ $processFitClass }}">
                                <span>{{ $processText }}</span>
                            </div>
                            <div class="std-cell">{{ $component->qty }}</div>
                            <div class="std-cell">{{ $manualNumber }}</div>
                        </div>
                        @php $cadGlobalRowIndex++; @endphp
                    @else
                        <div class="data-row empty-row std-grid-row">
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
                {{ __('Total QTY:') }} <strong>{{ $cadSum['total_qty'] }}</strong>
            </div>
            <footer class="std-footer">
                <div class="std-footer-grid">
                    <div class="std-footer-left">{{ __('Form # 014') }}</div>
                    <div class="std-footer-center">
                        {{ __('Page') }} <span class="page-number" data-tdr-footer-page>{{ $cadPageNum }}</span>
                        {{ __('of') }} <span class="total-pages" data-tdr-footer-total>{{ $cad_total_pages }}</span>
                    </div>
                    <div class="std-footer-right">
                        {{ __('Rev#0, 15/Dec/2012') }}
                    </div>
                </div>
            </footer>
        </div>
    </div>
@endforeach

@include('shared.tdr-forms._print-settings-modal', ['formType' => 'cadFormStd', 'formConfig' => $tdrFormConfig])

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
@include('shared.tdr-forms._scripts', ['formType' => 'cadFormStd', 'formConfig' => $tdrFormConfig])
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/cad/cad-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/cad/cad-form-main.js') }}"></script>
</body>
</html>
