@php
    $additionalManuals = collect($additionalManuals ?? [])->values();
@endphp

@if($additionalManuals->isEmpty())
    -
@else
    @foreach($additionalManuals as $additionalManual)
        <span class="text-nowrap">{{ $additionalManual['number'] ?: '-' }}@if(filled($additionalManual['lib'] ?? null)) <span class="text-secondary">({{ $additionalManual['lib'] }})</span>@endif</span>@unless($loop->last), @endunless
    @endforeach
@endif
