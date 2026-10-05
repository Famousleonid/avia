@if($rows->isEmpty())
    <div class="ws-empty">No pending processes recorded.</div>
@else
<table class="dir-table ws-table ws-process-table" style="--ws-ipl-width: {{ max(5, $rows->max(fn ($part) => mb_strlen((string) $part['ipl'])) + 2) }}ch">
    <colgroup><col style="width:35%"><col style="width:19%"><col style="width:7%"><col style="width:31%"><col style="width:8%"></colgroup>
    <thead><tr><th>(IPL) Part number</th><th>Description</th><th class="num">Qty</th><th>Current process</th><th class="num" title="Unfinished processes after the current process">Next</th></tr></thead>
    <tbody>
    @foreach($rows as $part)
        @php
            $process = $part['current'];
            $remainingId = 'ws-'.$card['workorder']->id.'-remaining-'.$loop->index;
        @endphp
        <tr><td title="{{ $part['ipl'] }} · {{ $part['pn'] }} · {{ $part['kind'] }}"><span class="ws-part-reference"><span class="ws-part-ipl">({{ $part['ipl'] ?: '—' }})</span><span class="ws-part-pn">{{ $part['pn'] ?: '—' }}</span></span></td><td title="{{ $part['name'] }}">{{ $part['name'] }}</td><td class="num">{{ $part['qty'] }}</td><td class="{{ $process['sent'] ? 'ws-sent' : '' }}" title="{{ $process['name'] }} · {{ $process['sent'] ? 'Awaiting return' : 'Not sent' }} · {{ $process['vendor'] ?: 'Vendor not specified' }} · RO {{ $process['ro'] ?: '—' }}@if($process['since']) · Sent @projectDate($process['since'])@endif{{ $part['earlier_pending'] ? ' · '.$part['earlier_pending'].' earlier operations also have no return date' : '' }}">{{ $process['name'] }}{{ $process['sent'] ? '' : ' · Not sent' }}</td><td class="num" title="{{ $part['ahead'] }} processes ahead{{ $part['earlier_pending'] ? '; '.$part['earlier_pending'].' earlier operations also unfinished' : '' }}"><button type="button" class="ws-remaining-toggle" data-ws-remaining="{{ $remainingId }}" aria-haspopup="dialog" aria-expanded="false" aria-controls="{{ $remainingId }}" aria-label="Show remaining processes for {{ $part['pn'] }}">{{ $part['ahead'] }}{{ $part['earlier_pending'] ? '*' : '' }}</button>
            <template id="{{ $remainingId }}-template">
                <div class="ws-remaining-heading"><span>Remaining processes · {{ $part['pn'] }} · {{ $part['kind'] }}</span><button type="button" data-ws-remaining-close aria-label="Close remaining processes">×</button></div>
                <table class="ws-remaining-table"><colgroup><col style="width:8%"><col style="width:44%"><col style="width:34%"><col style="width:14%"></colgroup>
                    <thead><tr><th>#</th><th>Process</th><th>Status</th><th>Days</th></tr></thead>
                    <tbody>@foreach($part['pending'] as $pendingProcess)
                        <tr><td>{{ $loop->iteration }}</td><td>{{ $pendingProcess['name'] }}</td><td>{{ $loop->index === $part['earlier_pending'] ? 'Current · ' : ($loop->index < $part['earlier_pending'] ? 'Earlier · ' : '') }}{{ $pendingProcess['sent'] ? 'Awaiting return' : 'Not sent' }}</td><td>{{ $pendingProcess['days'] }}</td></tr>
                    @endforeach</tbody>
                </table>
            </template>
        </td></tr>
    @endforeach
    </tbody>
</table>
@endif
