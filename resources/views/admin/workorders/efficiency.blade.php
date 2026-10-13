@extends('admin.master')
@section('style')
<style>
.efficiency-page{height:100%;min-height:0;min-width:0;display:flex;flex-direction:column;gap:16px;padding:16px;overflow:hidden}
.efficiency-filter{flex-shrink:0;margin:0;max-width:480px}.efficiency-filter label{font-size:12px;color:var(--bs-secondary-color,#94a3b8)}
.efficiency-scroll{flex:1;min-height:0;min-width:0;overflow:auto;isolation:isolate;border:1px solid #425568}
.efficiency-table{border-collapse:separate;border-spacing:0;width:100%;min-width:600px;table-layout:fixed;font-size:13px}
.efficiency-table th,.efficiency-table td{padding:10px 12px;text-align:center;border-bottom:1px solid #425568;border-right:1px solid #425568;background:var(--avia-bg,#1e2c39)}
.efficiency-table thead th{position:sticky;top:0;z-index:2;background:var(--avia-card-bg,#263746)}
.efficiency-table .eff-tech{position:sticky;left:0;width:180px;text-align:left;overflow-wrap:anywhere;z-index:3;box-shadow:2px 0 3px #0003}
.efficiency-table .eff-average{position:sticky;right:0;width:115px;z-index:3;box-shadow:-2px 0 3px #0003}
.efficiency-table .eff-count{width:95px}
.eff-workorders{display:flex;flex-wrap:wrap;gap:8px;min-width:0}
.eff-workorder{display:flex;flex-direction:column;justify-content:center;gap:5px;padding:8px 10px;min-width:108px;max-width:100%;background:#263746;border:1px solid #425568;border-radius:4px}
.eff-workorder-number{color:#fff;font-size:15px;white-space:nowrap}
.eff-workorder-status{font-size:11px;color:#a8b3bf}
.efficiency-table thead .eff-tech,.efficiency-table thead .eff-average{z-index:4}
.efficiency-table th,.efficiency-table td{font-weight:400}.efficiency-note{font-size:12px;color:var(--bs-secondary-color,#94a3b8);margin:0}
.efficiency-table .eff-number{font-size:16px;color:var(--bs-info,#0dcaf0);font-variant-numeric:tabular-nums}
.efficiency-table .eff-empty{color:var(--bs-secondary-color,#94a3b8)}
html[data-bs-theme=light] .efficiency-table td{background:#fff}html[data-bs-theme=light] .efficiency-table th{background:#e9eef3}
</style>
@endsection
@section('content')
<div class="efficiency-page">
    @include('admin.workorders.partials.status-tabs')
    <form class="efficiency-filter" method="get" action="{{ route('workorders.status.efficiency') }}">
        <label for="efficiencyPart">Part number</label>
        <select id="efficiencyPart" name="part_number" class="form-select">
            <option value="">Select part number…</option>
            @foreach($partNumbers as $number)<option value="{{ $number }}" @selected($partNumber === $number)>{{ $number }}</option>@endforeach
        </select>
    </form>
    @if($partNumber)
        <p class="efficiency-note">2. Disassembly · Working days, Ontario (after Start through Finish). Weekends and public holidays excluded; weekend holidays observed on the next available weekday. Missing or skipped dates are excluded from averages.</p>
        @if($workorders->isEmpty())
            <p>No workorders found.</p>
        @else
            <div class="efficiency-scroll" tabindex="0" aria-label="Disassembly efficiency">
                <table class="efficiency-table">
                    <thead><tr><th class="eff-tech" scope="col">Technician</th><th scope="col">Workorders · working days</th><th class="eff-count" scope="col">Completed</th><th class="eff-average" scope="col">Average, days</th></tr></thead>
                    <tbody>@foreach($rows as $row)<tr>
                        <th class="eff-tech" scope="row">{{ $row['name'] }}</th>
                        <td><div class="eff-workorders">
                            @foreach($workorders as $wo)
                                @if(isset($row['cells'][$wo->id]))
                                    @php($cell = $row['cells'][$wo->id])
                                    <div class="eff-workorder" title="{{ $cell['note'] }}">
                                        <span class="eff-workorder-number">w{{ $wo->number }}</span>
                                        @if($cell['days'] !== null)
                                            <span class="eff-number">{{ $cell['days'] }} <small>{{ $cell['days'] === 1 ? 'day' : 'days' }}</small></span>
                                        @else
                                            <span class="eff-workorder-status">{{ $cell['note'] === 'Skipped' ? 'Skipped' : ($cell['note'] === 'Dates not completed' ? 'Not completed' : 'Invalid dates') }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div></td>
                        <td class="eff-count eff-number">{{ count($row['days']) }}</td>
                        <td class="eff-average {{ $row['average'] !== null ? 'eff-number' : 'eff-empty' }}">{{ $row['average'] !== null ? number_format($row['average'], 1) : '—' }}</td>
                    </tr>@endforeach</tbody>
                    <tfoot><tr><td class="eff-tech">All workorders</td><td></td><td class="eff-count eff-number">{{ $rows->sum(fn ($row) => count($row['days'])) }}</td><td class="eff-average {{ $average !== null ? 'eff-number' : 'eff-empty' }}">{{ $average !== null ? number_format($average, 1) : '—' }}</td></tr></tfoot>
                </table>
            </div>
        @endif
    @endif
</div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('efficiencyPart');
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(select).select2({width:'100%', placeholder:'Select part number…', allowClear:true});
        jQuery(select).on('change', function () { select.form.requestSubmit(); });
    } else {
        select.addEventListener('change', function () { select.form.requestSubmit(); });
    }
});
</script>
@endsection
