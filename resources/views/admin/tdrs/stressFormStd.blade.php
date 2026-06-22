<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    @php
        $tdrFormConfig = config('tdr_forms.stressFormStd');
        $componentName = (string) $current_wo->displayDescription();
        $manualNumber = substr((string) ($manual->number ?? ''), 0, 8);
        $stress_table_pages = $stress_table_pages ?? [[]];
        $stress_total_pages = max(1, count($stress_table_pages));
        $stressGlobalRowIndex = 1;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STRESS RELIEF PROCESS SHEET</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
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

@foreach($stress_table_pages as $stressPageIndex => $stressPageRows)
    @php $stressPageNum = $stressPageIndex + 1; @endphp
    <div class="container-fluid std-sheet-container {{ $stressPageNum === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
        <div class="std-page page data-page {{ $stressPageNum === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $stressPageNum }}">
            <div class="std-header header-page">
                <div class="std-header-top">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                    <h2 class="std-header-title">STRESS RELIEF PROCESS SHEET</h2>
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
                            <div class="std-meta-value">INTERNAL</div>
                        </div>
                        <div class="std-meta-row std-meta-row--right">
                            <div class="std-meta-label">VENDOR:</div>
                            <div class="std-meta-value"><strong>AVIATECHNIK</strong></div>
                        </div>
                    </div>
                </div>

                <div class="std-instruction std-instruction-row">
                    <div>Perform the Stress Relief as specified under Process No. and in accordance with SMM No.</div>
                    <div class="std-manual-ref-label">MANUAL REF:</div>
                    <div class="std-manual-ref-box">{{ $manualNumber }}</div>
                </div>
            </div>

            <div class="std-table table-header" style="--std-table-columns: 1fr 2fr 2fr 4fr 1fr 2fr;">
                <div class="std-grid-row std-grid-row--header">
                    <div class="std-cell">ITEM No.</div>
                    <div class="std-cell">PART No.</div>
                    <div class="std-cell">DESCRIPTION</div>
                    <div class="std-cell">PROCESS No.</div>
                    <div class="std-cell">QTY</div>
                    <div class="std-cell">PERFORMED</div>
                </div>
            </div>

            <div class="all-rows-container page-rows-container" style="--std-table-columns: 1fr 2fr 2fr 4fr 1fr 2fr;">
                @if(empty($stress_components))
                    <div class="data-row std-grid-row std-grid-row--full">
                        <div class="std-cell"><strong>No Stress Relief components with stress_relief_list flag</strong></div>
                    </div>
                @endif

                @foreach($stressPageRows as $stressEntry)
                    @if(($stressEntry['kind'] ?? '') === 'manual')
                        <div class="data-row manual-row std-grid-row std-grid-row--manual std-grid-row--full" data-row-index="{{ $stressGlobalRowIndex }}">
                            <div class="std-cell"><strong>{{ $stressEntry['text'] ?? '' }}</strong></div>
                        </div>
                        @php $stressGlobalRowIndex++; @endphp
                    @elseif(($stressEntry['kind'] ?? '') === 'data')
                        @php
                            $component = $stressEntry['component'];
                            $rowHeight = max(34, (int) ($component->row_height ?? 32));
                        @endphp
                        <div class="data-row std-grid-row" data-row-index="{{ $stressGlobalRowIndex }}" style="--std-row-min-height: {{ $rowHeight }}px;">
                            <div class="std-cell">
                                <span class="std-cell--multiline">{{ $component->ipl_num }}</span>
                            </div>
                            <div class="std-cell">{{ $component->part_number }}</div>
                            <div class="std-cell">{{ $component->name }}</div>
                            <div class="std-cell">{{ $component->process_name }}</div>
                            <div class="std-cell">{{ $component->qty }}</div>
                            <div class="std-cell"></div>
                        </div>
                        @php $stressGlobalRowIndex++; @endphp
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
                {{ __('Total QTY:') }} <strong>{{ $stressSum['total_qty'] }}</strong>
            </div>
            <footer class="std-footer">
                <div class="std-footer-grid">
                    <div class="std-footer-left">{{ __('Form # 015') }}</div>
                    <div class="std-footer-center">
                        {{ __('Page') }} <span class="page-number" data-tdr-footer-page>{{ $stressPageNum }}</span>
                        {{ __('of') }} <span class="total-pages" data-tdr-footer-total>{{ $stress_total_pages }}</span>
                    </div>
                    <div class="std-footer-right">
                        {{ __('Rev#0, 15/Dec/2012') }}
                    </div>
                </div>
            </footer>
        </div>
    </div>
@endforeach

@include('shared.tdr-forms._print-settings-modal', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])

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
<script src="{{ asset('js/tdrs/forms/ndt-std/chartjs-patcher.js') }}"></script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@include('shared.tdr-forms._scripts', ['formType' => 'stressFormStd', 'formConfig' => $tdrFormConfig])
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/processes-form/row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/stress/stress-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/stress/stress-form-main.js') }}"></script>
</body>
</html>
