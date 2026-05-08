@php
    $tdrProcessRow = $tdrProcessRow ?? $processes ?? null;
    $travelerGroup = $inTr && $tdrProcessRow ? (int) ($tdrProcessRow->traveler_group ?: 1) : 0;
    $showTravelerCheckbox = $showTravelerCheckbox ?? true;
@endphp
<td class="text-center align-middle traveler-select-cell">
    @if($showTravelerCheckbox && $tdrProcessRow)
        <div class="d-inline-flex align-items-center justify-content-center gap-1">
            <input type="checkbox"
                   class="form-check-input traveler-row-checkbox"
                   value="{{ $tdrProcessRow->id }}"
                   data-in-traveler="{{ $inTr ? '1' : '0' }}"
                   data-traveler-group="{{ $travelerGroup }}">
            @if($inTr)
                <span class="small text-info text-nowrap">{{ __('Traveler') }} {{ $travelerGroup ?: 1 }}</span>
            @endif
        </div>
    @else
        <span class="text-muted small">—</span>
    @endif
</td>
