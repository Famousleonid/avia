@php
    $travelerGroup = $inTr ? (int) ($processes->traveler_group ?: 1) : 0;
    $showTravelerCheckbox = $showTravelerCheckbox ?? true;
@endphp
<td class="text-center align-middle traveler-select-cell">
    @if($showTravelerCheckbox)
        <div class="d-inline-flex align-items-center justify-content-center gap-1">
            <input type="checkbox"
                   class="form-check-input traveler-row-checkbox"
                   value="{{ $processes->id }}"
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
