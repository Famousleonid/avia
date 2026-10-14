@extends('admin.master')
@section('style')
<style>
.wo-status.ws-restoring{visibility:hidden}
.wo-status{height:100%;min-height:0;display:flex;flex-direction:column;gap:16px;padding:20px;color:var(--dir-text);background:var(--dir-bg)}
.ws-header{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;flex-shrink:0}.ws-header h1{font-size:18px;font-weight:400;letter-spacing:0;margin:0;color:var(--bs-info,#0dcaf0)}.ws-sub{color:var(--dir-muted);font-size:12px;margin-top:5px}.ws-total{font-size:14px;font-weight:400;color:var(--bs-info,#0dcaf0)}.ws-total strong{font-size:16px;font-weight:400;color:inherit;margin-right:5px}
.ws-filters{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1.3fr) minmax(310px,1fr);gap:12px;align-items:end;padding:8px 12px;background:var(--dir-panel-bg);border:1px solid var(--dir-border);border-radius:12px;flex-shrink:0}.ws-filters label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--dir-muted);display:block;line-height:1.15;margin-bottom:1px}.ws-filters .form-select,.ws-filters .form-control{background-color:var(--dir-input-bg);color:var(--dir-input-text);border-color:var(--dir-input-border);font-size:13px;min-height:34px;height:34px}.ws-filters option{background:var(--dir-input-bg);color:var(--dir-input-text)}
.ws-scroll{flex:1;min-height:0;min-width:0;overflow:auto;padding-right:4px}
.ws-card{margin-bottom:20px;padding:0;overflow:hidden;background:transparent;min-width:0;border:1px solid color-mix(in srgb,var(--dir-border-2) 45%,var(--dir-muted) 55%);border-radius:5px}
.ws-identity{display:flex;align-items:center;gap:18px;white-space:nowrap;overflow:auto;font-size:14px;padding:7px 10px;background:var(--dir-panel-bg);border:0;border-bottom:1px solid var(--dir-border-2)}.ws-identity>*{flex-shrink:0}.ws-number{color:#6edff6;font-weight:600;text-decoration:none;font-size:inherit}
.ws-halves{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr) minmax(0,2fr);align-items:start}.ws-half{min-width:0;overflow:hidden;background:var(--dir-table-bg)}.ws-half+.ws-half{border-left:1px solid var(--dir-border-2)}
.ws-half-toggle{display:flex;align-items:center;gap:16px;width:100%;border:0;border-bottom:1px solid var(--dir-border);padding:7px 10px;background:var(--dir-thead-bg);color:var(--dir-muted);font-size:12px;white-space:nowrap;overflow-x:auto;text-align:left}.ws-half-toggle>*{flex-shrink:0}.ws-half-label{font-weight:600;color:var(--dir-text);margin-right:auto}.ws-half-toggle strong{font-variant-numeric:tabular-nums;color:var(--dir-text);margin-left:4px}.ws-half-toggle i{font-size:10px}.ws-half-toggle[aria-expanded=true] i{transform:rotate(180deg)}.ws-half-toggle:hover,.ws-half-toggle:focus-visible{background:var(--dir-row-active)}
.ws-half-toggle[aria-expanded=false]{border-bottom:0}
.ws-half:not(.ws-ec) .ws-half-label,.ws-secondary-data{color:#9ca3af}
html[data-bs-theme=light] .ws-half:not(.ws-ec) .ws-half-label,html[data-bs-theme=light] .ws-secondary-data{color:#6b7280}
.ws-estimate{margin-left:0}.ws-detail{height:330px;max-height:330px;overflow:auto;width:100%;min-width:0}.ws-detail[hidden]{display:none!important}
.ws-table{table-layout:fixed;width:100%;min-width:480px;border-collapse:collapse;font-size:12px;color:var(--dir-text);background:var(--dir-table-bg)}.ws-table th{position:sticky;top:0;z-index:2;background:var(--dir-thead-bg);font-size:11px;color:var(--dir-muted);text-align:left}.ws-table th,.ws-table td{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:5px 6px;border:1px solid var(--dir-border-2);vertical-align:middle;line-height:20px}.ws-table .num{text-align:center;font-variant-numeric:tabular-nums}
.ws-half-toggle strong.ws-pending{color:#f4bd64}.ws-half-toggle strong.ws-clear{color:#46c997}
.ws-empty{padding:15px!important;color:var(--dir-muted);font-size:12px;text-align:center}.ws-missing{font-weight:600}.ws-sent{color:#60cbee}
.ws-process-remaining{display:block;font-size:11px;line-height:16px;font-variant-numeric:tabular-nums}.ws-process-overdue{color:#ff737b}html[data-bs-theme=light] .ws-process-overdue{color:#c82333}
.ws-filters .select2-selection__clear{font-size:18px;font-weight:400;color:var(--dir-muted);margin-right:22px}
.ws-filters>div{min-width:0}.ws-filters .select2-container{width:100%!important}.ws-filters .select2-selection--single{height:34px;background:var(--dir-input-bg);border:1px solid var(--dir-input-border);border-radius:6px}.ws-filters .select2-selection__rendered{line-height:32px!important;color:var(--dir-input-text)!important;font-size:13px}.ws-filters .select2-selection__arrow{height:32px!important}.ws-customer-dropdown{background:var(--dir-input-bg)!important;color:var(--dir-input-text)!important;border-color:var(--dir-input-border)!important}.ws-customer-dropdown .select2-search__field{background:var(--dir-input-bg);color:var(--dir-input-text);border:1px solid var(--dir-input-border)!important}.ws-customer-dropdown .select2-results__option{font-size:13px}.ws-customer-dropdown .select2-results__option--highlighted{background:#245d82!important;color:#fff!important}
html[data-bs-theme=light] .ws-number{color:#173f73}html[data-bs-theme=light] .ws-half-toggle strong.ws-pending{color:#976015}html[data-bs-theme=light] .ws-half-toggle strong.ws-clear{color:#157347}html[data-bs-theme=light] .ws-sent{color:#176681}
@media(max-width:1150px){.wo-status{padding:12px}.ws-filters{grid-template-columns:1fr 1fr}}
.ws-awaiting-approval{color:var(--dir-muted)}.ws-active,.ws-esd-future{color:#46c997}.ws-esd-soon{color:#ffdc65}.ws-esd-overdue{color:#ff737b}
html[data-bs-theme=light] .ws-active,html[data-bs-theme=light] .ws-esd-future{color:#157347}html[data-bs-theme=light] .ws-esd-soon{color:#946c00}html[data-bs-theme=light] .ws-esd-overdue{color:#c82333}
.ws-loading-dots{display:inline-flex;gap:3px;margin-left:4px;font-size:18px;line-height:1}.ws-loading-dots span{animation:ws-loading-dot 1s infinite}.ws-loading-dots span:nth-child(2){animation-delay:.15s}.ws-loading-dots span:nth-child(3){animation-delay:.3s}
@keyframes ws-loading-dot{0%,80%,100%{opacity:.25;transform:translateY(0)}40%{opacity:1;transform:translateY(-3px)}}
@media(prefers-reduced-motion:reduce){.ws-loading-dots span{animation:none;opacity:1}}
.ws-status-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--dir-muted);display:block;line-height:1.15;margin-bottom:1px}
.ws-status-options{display:flex;align-items:center;gap:12px;min-height:34px;flex-wrap:wrap}.ws-filters .ws-status-options label{display:inline-flex;align-items:center;gap:5px;font-size:12px;text-transform:none;letter-spacing:0;margin:0;color:var(--dir-text);white-space:nowrap;cursor:pointer}.ws-status-options input{margin:0;flex-shrink:0}
@media(max-width:1150px){.ws-filters>div:last-child{grid-column:1/-1}}
.ws-remaining-toggle{border:0;background:transparent;color:var(--dir-link,#60cbee);text-decoration:underline;cursor:pointer;padding:0 5px;font:inherit;min-width:24px}
.ws-remaining-popover{--ws-popup-bg:#3b3f44;--ws-popup-head:#4b5056;--ws-popup-border:#858b92;--ws-popup-text:#f4f4f4;position:fixed;z-index:1080;width:min(380px,calc(100vw - 16px));max-height:min(360px,calc(100vh - 16px));overflow:auto;padding:10px;background:var(--ws-popup-bg);color:var(--ws-popup-text);border:1px solid var(--ws-popup-border);border-radius:7px;box-shadow:0 10px 32px #0009;font-size:12px}.ws-forecast-popover{width:min(700px,calc(100vw - 16px));max-height:min(560px,calc(100vh - 16px))}.ws-forecast-route{margin-top:12px}.ws-forecast-critical{color:#ff9292}.ws-forecast-next{color:#ffd166}html[data-bs-theme=light] .ws-forecast-critical{color:#b42318}html[data-bs-theme=light] .ws-forecast-next{color:#856000}.ws-forecast-note{margin:6px 0;line-height:1.5}.ws-remaining-heading{display:flex;align-items:flex-start;gap:8px;font-weight:600;margin-bottom:7px;overflow-wrap:anywhere}.ws-remaining-heading span{flex:1;min-width:0}.ws-remaining-heading button{border:0;background:transparent;color:var(--ws-popup-text);font-size:22px;line-height:20px;padding:0 3px;cursor:pointer}.ws-remaining-table{width:100%;table-layout:fixed;border-collapse:collapse}.ws-remaining-table th,.ws-remaining-table td{padding:5px 4px;border:1px solid var(--ws-popup-border);white-space:normal;overflow-wrap:anywhere;line-height:17px;text-align:left}.ws-remaining-table th{background:var(--ws-popup-head);color:var(--ws-popup-text)}.ws-remaining-table td:last-child,.ws-remaining-table th:last-child{text-align:center}

.ws-ecd-form{display:inline-flex;align-items:center;gap:5px;margin:0}.ws-ecd-input{width:124px;min-width:0;padding:3px 6px;font:inherit;background:var(--dir-input-bg);color:var(--dir-input-text);border:1px solid var(--dir-input-border);border-radius:4px}.ws-ecd-input.ws-esd-future{color:#46c997}.ws-ecd-input.ws-esd-soon{color:#ffdc65}.ws-ecd-input.ws-esd-overdue{color:#ff737b}.ws-ecd-save{border:1px solid var(--dir-input-border);border-radius:4px;background:var(--dir-input-bg);color:var(--dir-link,#60cbee);padding:3px 6px}.ws-ecd-message{font-size:11px;color:var(--dir-muted)}.ws-ecd-message:empty{display:none}.ws-ec-table{min-width:240px}.ws-ec-table td{font-size:11px;padding-inline:3px}.ws-ec-table th{padding-inline:3px}
html[data-bs-theme=light] .ws-ecd-input.ws-esd-future{color:#157347}html[data-bs-theme=light] .ws-ecd-input.ws-esd-soon{color:#946c00}html[data-bs-theme=light] .ws-ecd-input.ws-esd-overdue{color:#c82333}
.ws-part-reference{display:grid;grid-template-columns:var(--ws-ipl-width,9ch) minmax(0,1fr);align-items:baseline;column-gap:7px;min-width:0;font-variant-numeric:tabular-nums}.ws-part-ipl{text-align:right;font-family:ui-monospace,Consolas,monospace;font-size:11px;white-space:nowrap}.ws-part-pn{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ws-ec-sent,.ws-ec-returned,.ws-ec-open{color:#198754;line-height:1;display:inline-block;vertical-align:middle}.ws-ec-sent{font-size:20px}.ws-ec-returned{font-size:22px}.ws-ec-open{font-size:12px;font-weight:600}
html[data-bs-theme=light] .ws-remaining-popover{--ws-popup-bg:#eeeeee;--ws-popup-head:#dedede;--ws-popup-border:#999999;--ws-popup-text:#292929;box-shadow:0 8px 26px #29292940}
.ws-identity{cursor:pointer}.ws-identity:focus-visible{outline:2px solid var(--dir-link,#60cbee);outline-offset:-2px}.ws-ecd-form{cursor:default}.ws-ecd-calendar-button{cursor:pointer}
.ws-ecd-calendar{--ws-cal-bg:#243444;--ws-cal-text:#edf4fb;--ws-cal-border:#526a80;--ws-cal-hover:#36546b;--ws-cal-muted:#91a4b8;background:var(--ws-cal-bg);border:1px solid var(--ws-cal-border);box-shadow:0 8px 24px #0005}.ws-ecd-calendar .flatpickr-months .flatpickr-month,.ws-ecd-calendar .flatpickr-current-month,.ws-ecd-calendar .flatpickr-weekday,.ws-ecd-calendar .flatpickr-day{color:var(--ws-cal-text);fill:var(--ws-cal-text)}.ws-ecd-calendar .flatpickr-prev-month svg,.ws-ecd-calendar .flatpickr-next-month svg{fill:var(--ws-cal-text)}.ws-ecd-calendar .flatpickr-monthDropdown-months,.ws-ecd-calendar .flatpickr-monthDropdown-month{background:var(--ws-cal-bg);color:var(--ws-cal-text)}.ws-ecd-calendar .flatpickr-day:hover{background:var(--ws-cal-hover);border-color:var(--ws-cal-border)}.ws-ecd-calendar .flatpickr-day.selected{background:#2676a8;border-color:#2676a8;color:#fff}.ws-ecd-calendar .flatpickr-day.prevMonthDay,.ws-ecd-calendar .flatpickr-day.nextMonthDay{color:var(--ws-cal-muted)}
html[data-bs-theme=light] .ws-ecd-calendar{--ws-cal-bg:#fff;--ws-cal-text:#17324d;--ws-cal-border:#a8b8c8;--ws-cal-hover:#e5edf5;--ws-cal-muted:#788a9d}
</style>
@endsection
@section('content')
<div class="wo-status ws-restoring">
    @include('admin.workorders.partials.status-tabs')
    <header class="ws-header"><div><h1>WO Status</h1></div><div class="ws-total"><strong id="wsTotal">{{ $workorders->total() }}</strong> workorders</div></header>
    <form method="get" action="{{ route('workorders.status') }}" class="ws-filters" id="woStatusFilters" data-no-spinner>
        <div><label for="wsCustomer">Customer</label><select id="wsCustomer" name="customer_id" class="form-select"><option value="">All customers</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>@endforeach</select></div>
        <div><label for="wsWorkorder">Workorder number</label><input id="wsWorkorder" name="number" class="form-control" type="search" inputmode="numeric" autocomplete="off" placeholder="Search WO number…" value="{{ $selectedNumber }}" maxlength="20"></div>
        <div><span class="ws-status-label" id="wsStatusLabel">Status</span>
            <input type="hidden" id="wsState" name="state" value="{{ $uiState['filters']['state'] }}">
            <div class="ws-status-options" role="group" aria-labelledby="wsStatusLabel">
                @foreach(['active'=>'Active','approved'=>'Approved'] as $key=>$label)
                    <label><input type="checkbox" class="form-check-input" data-ws-status="{{ $key }}" @checked(in_array($key, explode(',', $uiState['filters']['state']), true))> {{ $label }}</label>
                @endforeach
            </div>
        </div>

    </form>
    <div class="ws-scroll">
        <div id="wsCards">@include('admin.workorders.partials.status-cards')</div>
        <div id="wsSentinel" class="ws-empty" role="status" aria-live="polite" data-next="{{ $nextUrl }}"></div>
        <button id="wsRetry" class="btn btn-outline-info d-none" type="button">Retry</button>
    </div>

</div>
@endsection
@section('scripts')
<script id="wsInitialState" type="application/json">@json($uiState)</script>
<script src="{{ asset('js/wo-status.js') }}?v={{ filemtime(public_path('js/wo-status.js')) }}"></script>
@endsection



