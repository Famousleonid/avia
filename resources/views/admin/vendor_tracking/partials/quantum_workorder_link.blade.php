@php
    $workorderUrl = $quantumWorkorderUrls[(int) preg_replace('/\D+/', '', (string) $line->wo_number)] ?? null;
@endphp
@if($workorderUrl)
    <a href="{{ $workorderUrl }}" title="Open workorder Main">{{ $line->wo_number }}</a>
@else
    {{ $line->wo_number ?: '--' }}
@endif
