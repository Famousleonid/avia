@extends('admin.master')
@section('style')
<style>
.unit-history{--unit-history-accent:#087990;color:var(--avia-text,#212529);height:100%;min-height:0;min-width:0;display:flex;flex-direction:column;padding:16px;gap:12px;overflow:hidden}
.unit-history-body{flex:1;min-height:0;min-width:0;display:flex;flex-direction:column;overflow:hidden}
.unit-history-body>*{flex-shrink:0}
.unit-history form{display:flex;flex-wrap:wrap;align-items:end;gap:12px;margin-bottom:0}
.unit-history-source-count{margin-left:auto;align-self:end;line-height:38px;white-space:nowrap}
.unit-history label{display:block;font-size:12px;margin-bottom:4px}.unit-history .group-field{flex:1 1 260px;max-width:440px}
.unit-history-summary{display:flex;flex-wrap:wrap;gap:12px;margin:8px 0}.unit-history-summary>div{flex:1 1 160px;background:var(--avia-surface-raised,#f8f9fa);border:1px solid var(--avia-border,#dee2e6);border-radius:6px;padding:6px 10px}
.unit-history-summary strong{display:block;font-size:20px;line-height:1.1;margin-bottom:3px;color:var(--unit-history-accent)}.unit-history-summary small{display:block;font-size:12px;line-height:1.2}
.unit-history-body>.unit-history-table-wrap{flex:1 1 0;overflow:auto;scrollbar-gutter:stable;border:1px solid var(--avia-border,#dee2e6);min-height:0;min-width:0}
.unit-history table{width:100%;min-width:900px;margin:0;border-collapse:separate;border-spacing:0;font-size:13px}
.unit-history th,.unit-history td{padding:9px 10px;border-bottom:1px solid var(--avia-border,#dee2e6);vertical-align:top}
.unit-history thead{position:sticky;top:0;background:var(--avia-surface-raised,#f8f9fa);z-index:1}
.unit-history th{background:var(--avia-surface-raised,#f8f9fa);white-space:nowrap}
html[data-bs-theme="dark"] .unit-history{--unit-history-accent:var(--avia-info)}
.unit-history-note{font-size:12px;color:var(--bs-secondary-color,#a8b3bf)}
@media(max-width:600px){.unit-history{padding:8px}.unit-history form>div{flex:1 1 130px}.unit-history .group-field{max-width:none}}
</style>
@endsection
@section('content')
<div class="unit-history">
    @include('admin.workorders.partials.status-tabs')
    <div class="unit-history-body">
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        <form method="get" action="{{ route('workorders.status.unit-history') }}">
            <div class="group-field"><label for="unitGroup">P/N group or full unit P/N</label>
                <input class="form-control" id="unitGroup" name="group" list="unitGroups" value="{{ $search }}" placeholder="190-70745 or 190-70745-403" maxlength="255" autocomplete="off" required>
                <datalist id="unitGroups">@foreach($suggestions as $item)<option value="{{ $item['value'] }}" label="{{ $item['label'] }}"></option>@endforeach</datalist>
            </div>
            <div><label for="historyFrom">Opened from</label><input id="historyFrom" class="form-control" type="text" data-project-date name="from" value="{{ format_project_date($from) }}" placeholder="dd/Mmm/yyyy" autocomplete="off"></div>
            <div><label for="historyTo">Opened through</label><input id="historyTo" class="form-control" type="text" data-project-date name="to" value="{{ format_project_date($to) }}" placeholder="dd/Mmm/yyyy" autocomplete="off"></div>
            <button class="btn btn-primary" type="submit">Search</button>
            <a href="{{ route('workorders.status.unit-history') }}" class="btn btn-outline-secondary">Reset</a>
            <span class="unit-history-source-count unit-history-note">{{ number_format($totalSource) }} WOs</span>
        </form>
        @if($search !== '')
            @if($rows->isEmpty())<p>No workorders found for this group and date range.</p>@else
                <div class="unit-history-summary">
                    <div><strong>{{ $quantity }}</strong><small>Units recorded in Quantum · selected period</small></div>
                    <div><strong>{{ $rows->count() }}</strong><small>Workorders · selected period</small></div>
                    <div><strong>{{ $last12 }}</strong><small>Units · last 12 months, all matching P/Ns</small></div>
                    <div><strong>{{ $interval === null ? '—' : $interval . ' days' }}</strong><small>Average interval between distinct arrival dates · selected period</small></div>
                </div>
                @if($undated)<p class="unit-history-note">{{ $undated }} WO(s) in this group have no open date and are excluded from date filters.</p>@endif
                <div class="unit-history-table-wrap" tabindex="0" aria-label="Unit workorder history">
                    <table><thead><tr><th>Opened</th><th>WO</th><th>Unit P/N</th><th>Description</th><th>S/N</th><th>Customer</th><th>Units</th><th>Closed</th></tr></thead>
                        <tbody>@foreach($rows as $row)
                            @php($woId = $workorderIds->get(preg_replace('/\D/', '', $row['wo_number'])))
                            <tr><td>{{ format_project_date($row['opened_at']) ?? '—' }}</td>
                                <td>@if($woId)<a href="{{ route('mains.show', ['workorder' => $woId]) }}">{{ $row['wo_number'] }}</a>@else{{ $row['wo_number'] }}@endif</td>
                                <td>{{ $row['part_number'] }}</td><td>{{ ($row['description'] ?? '') ?: '—' }}</td><td>{{ $row['serials'] ?: '—' }}</td><td>{{ $row['customer'] }}</td>
                                <td>{{ $row['quantity'] ?? '—' }}</td>
                                <td>{{ format_project_date($row['closed_at']) ?? '—' }}</td></tr>
                        @endforeach</tbody>
                    </table>
                </div>
            @endif
        @else
            <p class="unit-history-note">Select a group to see when units arrived and their customers. With dates empty, the full available history is shown.</p>
        @endif
    </div>
</div>
@endsection
@section('scripts')
<script>
(() => {
    const input = document.getElementById('unitGroup');
    const list = document.getElementById('unitGroups');
    const options = Array.from(list.options);
    const filterSuggestions = () => {
        const prefix = input.value.trim().toUpperCase();
        // Native datalists also match substrings and labels; only supply P/N prefixes.
        list.replaceChildren(...options.filter(option => option.value.toUpperCase().startsWith(prefix)));
    };
    input.addEventListener('input', filterSuggestions);
    input.addEventListener('focus', filterSuggestions);
    filterSuggestions();
})();
</script>
@endsection
