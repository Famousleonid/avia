<div class="ws-remaining-heading"><span>Process days · w{{ $wo->number }}</span><button type="button" data-ws-remaining-close aria-label="Close process days breakdown">×</button></div>
<p class="ws-forecast-note">Approval: @projectDate($wo->approve_at) · Process days: {{ $card['duration_days'] }} · Calculated ECD: @projectDate($card['isd'])</p>
<p class="ws-forecast-note">Routes run in parallel: the longest route determines the total, not the sum. Operations within each route run sequentially. Each Traveler group counts as one step using its longest included process duration. Actual returns replace planned dates; the next operation follows after {{ config('workorder_completion.between_process_days', 2) }} days.</p>
@php
    $nextLongestDays = collect($card['forecast_routes'])->pluck('days')
        ->filter(fn ($days) => $days < $card['duration_days'])->max();
@endphp
@forelse($card['forecast_routes'] as $route)
    @php
        $determinesEcd = $route['days'] === $card['duration_days'] && $card['duration_days'] > 0;
        $nextLongest = !$determinesEcd && $nextLongestDays !== null && $route['days'] === $nextLongestDays;
    @endphp
    <div class="ws-forecast-route"><strong>{{ $route['label'] }}</strong> · <span @class(['ws-forecast-critical' => $determinesEcd, 'ws-forecast-next' => $nextLongest])>{{ $route['days'] }} days · @projectDate($route['end'])@if($determinesEcd) · Determines ECD @endif</span></div>
    <table class="ws-remaining-table"><colgroup><col style="width:34%"><col style="width:12%"><col style="width:12%"><col style="width:21%"><col style="width:21%"></colgroup><thead><tr><th>Process</th><th>Std days</th><th>Gap days</th><th>Actual return</th><th>Calculated end</th></tr></thead><tbody>
    @foreach($route['steps'] as $step)
        <tr><td>{{ $step['name'] }}</td><td>{{ $step['days'] }}</td><td>{{ $step['gap'] }}</td><td>{{ $step['finish'] ? format_project_date($step['finish']) : '—' }}</td><td>@projectDate($step['end'])</td></tr>
    @endforeach
    </tbody></table>
@empty
    <p class="ws-forecast-note">No applicable processes.</p>
@endforelse
@if($card['duration_days'] === 0)<p class="ws-forecast-note">Zero process days: Calculated ECD is today.</p>@endif
