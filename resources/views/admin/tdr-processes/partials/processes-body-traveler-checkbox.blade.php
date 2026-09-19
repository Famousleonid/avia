@php
    $tdrProcessRow = $tdrProcessRow ?? $processes ?? null;
    $travelerGroup = $inTr && $tdrProcessRow ? (int) ($tdrProcessRow->traveler_group ?: 1) : 0;
    $showTravelerCheckbox = $showTravelerCheckbox ?? true;
    $travelerGroupLocked = $inTr
        && in_array($travelerGroup, $roLockedTravelerGroups ?? [], true);
    $processRoLocked = ($assignedRoStructureRestricted ?? false)
        && filled(trim((string) ($tdrProcessRow?->repair_order)));
    $travelerSelectionLocked = $travelerGroupLocked || $processRoLocked;
@endphp
<td class="text-center align-middle traveler-select-cell">
    @if($showTravelerCheckbox && $tdrProcessRow)
        <div class="d-inline-flex align-items-center justify-content-center gap-1"
             @if($travelerSelectionLocked) title="{{ __('Assigned RO: only Admin or Manager can change Traveler grouping for this process.') }}" @endif>
            <input type="checkbox"
                   class="form-check-input traveler-row-checkbox"
                   value="{{ $tdrProcessRow->id }}"
                   data-in-traveler="{{ $inTr ? '1' : '0' }}"
                   data-traveler-group="{{ $travelerGroup }}"
                   @if($travelerSelectionLocked) disabled aria-disabled="true" data-process-ro-locked="grouping" @endif
                   @if($travelerGroupLocked) data-traveler-ro-locked="1" @endif>
            @if($inTr)
                <span class="small text-info text-nowrap">{{ __('Traveler') }} {{ $travelerGroup ?: 1 }}</span>
                @if($travelerSelectionLocked)
                    <i class="bi bi-lock-fill small text-warning" aria-hidden="true"></i>
                @endif
            @endif
        </div>
    @else
        <span class="text-muted small">—</span>
    @endif
</td>
