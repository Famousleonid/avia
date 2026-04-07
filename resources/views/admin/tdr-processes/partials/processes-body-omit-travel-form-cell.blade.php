{{--
    Part Processes: для печати Part Traveler — чекбокс только в интерфейсе (не в БД).
    Снять флажок = не передавать эту строку (id tdr_process) при открытии travelForm через exclude_process_ids в URL.
    $processes, $inTr; optional $showOmitCheckbox (default true).
--}}
@php
    $showOmit = $showOmitCheckbox ?? true;
@endphp
<td class="text-center align-middle">
    @if($inTr)
        <span class="text-muted small" title="{{ __('Always on Part Traveler (vendor block)') }}">—</span>
    @elseif(!$showOmit)
    @else
        <input type="checkbox"
               class="form-check-input omit-traveler-form-cb"
               data-tdr-process-id="{{ $processes->id }}"
               checked
               title="{{ __('Uncheck to hide this AT row on Part Traveler for this open only (not saved)') }}">
    @endif
</td>
