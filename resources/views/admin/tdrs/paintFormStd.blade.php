<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    @php
        $tdrFormConfig = config('tdr_forms.paintFormStd');
        $componentName = (string) $current_wo->displayDescription();
        $manualNumber = substr((string) ($manual->number ?? ''), 0, 8);
        $paint_table_pages = $paint_table_pages ?? [[]];
        $paint_total_pages = max(1, count($paint_table_pages));
        $paintGlobalRowIndex = 1;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAINT PROCESS SHEET</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
</head>
<body>
@include('shared.print-mark.qr', ['printMarkWorkorder' => $current_wo ?? null])
@include('admin.tdrs.partials.std-sheet-toolbar')

@foreach($paint_table_pages as $paintPageIndex => $paintPageRows)
    @php $paintPageNum = $paintPageIndex + 1; @endphp
<div class="container-fluid std-sheet-container {{ $paintPageNum === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
    <div class="std-page page data-page {{ $paintPageNum === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $paintPageNum }}">
        <div class="std-header header-page">
            <div class="std-header-top">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                <h2 class="std-header-title">PAINT PROCESS SHEET</h2>
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
                <div>Perform the Paint process as specified under Process No. and in accordance with SMM No.</div>
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
                <div class="std-cell">CMM No.</div>
            </div>
        </div>

        <div class="all-rows-container page-rows-container" style="--std-table-columns: 1fr 2fr 2fr 4fr 1fr 2fr;">
            @if(empty($paint_components))
                <div class="data-row std-grid-row std-grid-row--full">
                    <div class="std-cell"><strong>No Paint components with paint_list flag</strong></div>
                </div>
            @endif

            @foreach($paintPageRows as $paintEntry)
                @if(($paintEntry['kind'] ?? '') === 'manual')
                    <div class="data-row manual-row std-grid-row std-grid-row--manual std-grid-row--full" data-row-index="{{ $paintGlobalRowIndex }}">
                        <div class="std-cell"><strong>{{ $paintEntry['text'] ?? '' }}</strong></div>
                    </div>
                    @php $paintGlobalRowIndex++; @endphp
                @elseif(($paintEntry['kind'] ?? '') === 'data')
                    @php
                        $component = $paintEntry['component'];
                        $rowHeight = max(34, (int) ($component->row_height ?? 32));
                    @endphp
                    <div class="data-row std-grid-row" data-row-index="{{ $paintGlobalRowIndex }}" style="--std-row-min-height: {{ $rowHeight }}px;">
                        <div class="std-cell">
                            <span class="std-cell--multiline">{{ $component->item_display ?? $component->ipl_num }}</span>
                        </div>
                        <div class="std-cell">{{ $component->part_number }}</div>
                        <div class="std-cell">
                            <span @if(strlen($component->name) > 15) class="std-description-long" @endif>{{ $component->name }}</span>
                        </div>
                        <div class="std-cell">
                            <span @if(strlen($component->process_name) > 30) class="std-description-long" @endif>{{ $component->process_name }}</span>
                        </div>
                        <div class="std-cell">{{ $component->qty }}</div>
                        <div class="std-cell">{{ $manualNumber }}</div>
                    </div>
                    @php $paintGlobalRowIndex++; @endphp
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
            {{ __('Total QTY:') }} <strong>{{ $paintSum['total_qty'] ?? 0 }}</strong>
        </div>
        <footer class="std-footer">
            <div class="std-footer-grid">
                <div class="std-footer-left">{{ __('Form # 014') }}</div>
                <div class="std-footer-center">
                    {{ __('Page') }} <span class="page-number" data-tdr-footer-page>{{ $paintPageNum }}</span>
                    {{ __('of') }} <span class="total-pages" data-tdr-footer-total>{{ $paint_total_pages }}</span>
                </div>
                <div class="std-footer-right">
                    {{ __('Rev#0, 15/Dec/2012') }}
                </div>
            </div>
        </footer>
    </div>
</div>
@endforeach

@include('shared.tdr-forms._print-settings-modal', ['formType' => 'paintFormStd', 'formConfig' => $tdrFormConfig])

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
@include('shared.tdr-forms._scripts', ['formType' => 'paintFormStd', 'formConfig' => $tdrFormConfig])
</body>
</html>
