{{--
    Шапка формы процесса.
    Переменные: $process_name, $current_wo, $selectedVendor
    Опционально: $header_title (из config по умолчанию)
--}}
<div class="header-page">
    <div class="row">
        <div class="col-3">
            <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                 style="width: 120px; margin: 6px 10px 0;">
        </div>
        <div class="col-9">
            <h4 class="mt-4 text-black text-"><strong>{{ $process_name->process_sheet_name ?? $process_name->name ?? ($header_title ?? 'PROCESS') }} PROCESS SHEET</strong></h4>
        </div>
    </div>
    <div class="row">
        <div class="col-7">
            <div class="row" style="height: 32px">
                <div class="col-6 pt-2 text-end"><strong>COMPONENT NAME</strong> :</div>
                <div class="col-6 pt-2 border-b"><strong>
                    <span @if(strlen($current_wo->description) > 30) class="description-text-long" @endif>{{ $current_wo->description }}</span>
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
                <div class="col-8 pt-2 border-b"></div>
            </div>
            <div class="row" style="height: 32px">
                <div class="col-4 pt-2 text-end"><strong>RO No:</strong></div>
                <div class="col-8 pt-2 border-b"></div>
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
