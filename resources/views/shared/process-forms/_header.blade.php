<div class="header-page">
    @php($isMachiningPrintedForm = isset($process_name) && \App\Models\ProcessName::isMachiningPrintedForm($process_name))
    <div class="row">
        <div class="col-3">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 120px; margin: 6px 10px 0;">
        </div>
        <div class="col-9">
            <div class="process-sheet-title-row {{ $isMachiningPrintedForm ? 'process-sheet-title-row--machining' : '' }} d-flex flex-wrap justify-content-between align-items-start gap-2 mt-4">
                <h4 class="mb-0 text-black">
                    <strong>{{ $process_name->process_sheet_name ?? $process_name->name ?? ($header_title ?? 'PROCESS') }} PROCESS SHEET</strong>
                </h4>
                @if($isMachiningPrintedForm)
                    @php($mhLibs = $machining_header_manual_libs ?? [])
                    @auth
                        <div class="machining-sheet-header-meta text-md-end ms-md-auto">
                            {{ auth()->user()->selection_name }}@if(count($mhLibs) > 0) (lib: {{ implode(', ', $mhLibs) }})@endif
                        </div>
                    @endauth
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-7">
            <div class="row" style="height: 32px">
                <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                <div class="col-6 pt-2 border-b component-name-cell"><strong>
                    @php($headerComponentName = $current_wo->displayDescription() ?? '')
                    <span class="component-name-value" @if(strlen($headerComponentName) > 30) data-long="1" @endif>{{ $headerComponentName }}</span>
                </strong></div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-6 pt-2 text-end"><strong>PART NUMBER:</strong></div>
                <div class="col-6 pt-2 border-b"><strong>{{ $current_wo->unit->part_number }}</strong></div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-6 pt-2 text-end"><strong>WORK ORDER No:</strong></div>
                <div class="col-6 pt-2 border-b"><strong>W{{ $current_wo->number }}</strong></div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-6 pt-2 text-end"><strong>SERIAL No:</strong></div>
                <div class="col-6 pt-2 border-b"><strong>{{ $current_wo->serial_number }}</strong></div>
            </div>
        </div>
        <div class="col-5">
            <div class="row" style="height: 32px">
                <div class="col-4 pt-2 text-end"><strong>DATE:</strong></div>
                <div class="col-8 pt-2 border-b"><strong>{{ $formHeaderDate ?? '' }}</strong></div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                <div class="col-8 pt-2 border-b process-ro-line">
                    @php($headerRepairOrder = trim((string) ($formHeaderRepairOrder ?? '')))
                    @if($headerRepairOrder !== '')
                        <strong class="process-ro-box">{{ $headerRepairOrder }}</strong>
                    @endif
                </div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-4 pt-2 text-end"><strong>VENDOR:</strong></div>
                <div class="col-8 pt-2 border-b">
                    <strong>{{ $selectedVendor ? $selectedVendor->name : '' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@once
    <style>
        .process-requirement-print-star { display: none; color: #000; }
        @media print {
            .process-requirement-print-star:not([aria-hidden="true"]) {
                display: inline-block;
                line-height: 1;
                margin-left: 1ch;
                position: relative;
                top: .12em;
            }
        }
    </style>
@endonce
