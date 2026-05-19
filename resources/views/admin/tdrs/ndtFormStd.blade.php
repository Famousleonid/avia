<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $tdrFormConfig = config('tdr_forms.ndtFormStd');
        $componentName = $current_wo->unit->name
            ?: ($current_wo->unit->manuals->title ?? $current_wo->description);
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
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDT Form</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('admin.tdrs.partials.std-sheet-styles', ['tdrFormConfig' => $tdrFormConfig])
</head>
<body>
@include('admin.tdrs.partials.std-sheet-toolbar')

@foreach($ndt_table_pages as $ndtPageIndex => $ndtPageRows)
    @php $ndtPageNum = $ndtPageIndex + 1; @endphp
    <div class="container-fluid std-sheet-container {{ $ndtPageNum === 1 ? 'tdr-primary-sheet' : 'dynamic-page-wrapper' }}">
        <div class="std-page page data-page {{ $ndtPageNum === 1 ? 'tdr-primary-logical-page' : '' }}" data-page-index="{{ $ndtPageNum }}">
            <div class="std-header header-page">
                <div class="std-header-top">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="std-header-logo">
                    <h2 class="std-header-title">NDT PROCESS SHEET</h2>
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

                <div class="std-ndt-grid">
                    <div class="std-ndt-column">
                        <h3 class="std-ndt-title">MAGNETIC PARTICLE AS PER:</h3>
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#1</div>
                            <div class="std-ndt-value">
                                <span @if(strlen($ndtProcessMap[1]) > 25) class="std-process-long" @endif>{{ $ndtProcessMap[1] }}</span>
                            </div>
                        </div>

                        <h3 class="std-ndt-title">LIQUID/FLUID PENETRANT AS PER:</h3>
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#4</div>
                            <div class="std-ndt-value">
                                <span @if(strlen($ndtProcessMap[4]) > 25) class="std-process-long" @endif>{{ $ndtProcessMap[4] }}</span>
                            </div>
                        </div>

                        <h3 class="std-ndt-title">ULTRASOUND AS PER:</h3>
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#7</div>
                            <div class="std-ndt-value"></div>
                        </div>
                    </div>

                    <div class="std-ndt-column" style="padding-top: 34px;">
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#2</div>
                            <div class="std-ndt-value"></div>
                        </div>
                        <div class="std-ndt-line" style="margin-top: 24px;">
                            <div class="std-ndt-index">#5</div>
                            <div class="std-ndt-value">{{ $ndtProcessMap[5] }}</div>
                        </div>
                        <div class="std-meta-label" style="margin-top: 30px;">CMM No:</div>
                    </div>

                    <div class="std-ndt-column" style="padding-top: 34px;">
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#3</div>
                            <div class="std-ndt-value"></div>
                        </div>
                        <h3 class="std-ndt-title">EDDY CURRENT AS PER:</h3>
                        <div class="std-ndt-line">
                            <div class="std-ndt-index">#6</div>
                            <div class="std-ndt-value">{{ $ndtProcessMap[6] }}</div>
                        </div>
                        <div class="std-manual-ref-box">{{ $manualNumber }}</div>
                    </div>
                </div>
            </div>

            <div class="std-table table-header" style="--std-table-columns: 1fr 3fr 3fr 2fr 1fr 1fr 1fr;">
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

            <div class="all-rows-container page-rows-container" style="--std-table-columns: 1fr 3fr 3fr 2fr 1fr 1fr 1fr;">
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
                            $rowHeight = max(32, (int) ($component->row_height ?? 32));
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

            <footer class="std-footer">
                <div class="std-footer-grid">
                    <div class="std-footer-left">{{ __('Form #016') }}</div>
                    <div class="std-footer-center">
                        {{ __('Page') }} <span class="page-number" data-tdr-footer-page>{{ $ndtPageNum }}</span>
                        {{ __('of') }} <span class="total-pages" data-tdr-footer-total>{{ $ndt_total_pages }}</span>
                    </div>
                    <div class="std-footer-right">
                        {{ __('Rev#0, 15/Dec/2012') }}
                        <br>
                        {{ __('Total QTY:') }} <strong>{{ $ndtSums['total'] ?? 0 }}</strong>
                        ( {{ __('MPI:') }} <strong>{{ $ndtSums['mpi'] ?? 0 }}</strong> {{ __(' ; ') }}
                        {{ __('FPI:') }} <strong>{{ $ndtSums['fpi'] ?? 0 }}</strong> )
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
