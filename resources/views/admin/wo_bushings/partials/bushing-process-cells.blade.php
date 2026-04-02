@php
    $a = $assignment ?? null;
    $inBatch = !empty($a['batch_id'] ?? null);
    $batchId = (int) ($a['batch_id'] ?? 0);
    $locked = !empty($a['locked'] ?? false);
    $woPid = $a['wo_process_id'] ?? null;
    $componentId = (int) ($component->id ?? 0);
    $hasNdts = isset($ndtNames) && is_array($ndtNames) && count($ndtNames) > 0;
    $hasProcess = !empty($process) || $hasNdts;
    $batchLabel = $batchLabel ?? 'Grp';
    $titleText = $detailTitle ?? '';
    if ($titleText === '' && !empty($process)) {
        $titleText = trim((string) ($process->process ?? ''));
    }
    if ($titleText === '' && $hasNdts) {
        $titleText = implode(' / ', $ndtNames);
    }
@endphp
<td class="bushing-subcol-batch align-middle text-center" @if($titleText !== '') title="{{ $titleText }}" @endif>
    @if($hasProcess)
        @if(!$inBatch && !empty($woPid))
            <div class="bushing-batch-inner d-flex align-items-center justify-content-center gap-1 flex-wrap">
                <input type="checkbox" class="form-check-input bushing-batch-group-checkbox mt-0"
                       data-process-key="{{ $processKey }}" data-wo-process-id="{{ $woPid }}"
                       data-component-id="{{ $componentId }}"
                       title="{{ __('Select to add to a batch') }}" autocomplete="off">
            </div>
        @elseif($inBatch && !$locked)
            <div class="bushing-batch-inner d-flex align-items-center justify-content-center gap-1 flex-wrap">
                <button type="button"
                        class="btn btn-sm btn-secondary py-0 px-1 js-bushing-batch-label align-self-center"
                        style="font-size:0.65rem;"
                        data-process-key="{{ $processKey }}"
                        data-batch-id="{{ $batchId }}"
                        title="{{ __('Toggle all checkboxes in this group') }}">{{ $batchLabel }}</button>
                <input type="checkbox" class="form-check-input bushing-batch-ungroup-checkbox mt-0"
                       data-process-key="{{ $processKey }}" data-wo-process-id="{{ $woPid ?? '' }}"
                       data-batch-id="{{ $batchId }}"
                       data-component-id="{{ $componentId }}"
                       title="{{ __('Select to remove from batch') }}" autocomplete="off">
            </div>
        @elseif($locked)
            <div class="bushing-batch-inner d-flex align-items-center justify-content-center gap-1 flex-wrap">
                <span class="badge bg-warning text-dark align-self-center" style="font-size:0.65rem;" title="{{ __('Sent — batch locked') }}">{{ $batchLabel }} / Sent</span>
            </div>
        @else
            {{-- Процесс есть в строке, но нет wo_bushing_process id в карте — группировать не с чем; как «нет партии» --}}
            <span class="text-muted" title="{{ __('No batch action for this cell') }}">—</span>
        @endif
    @else
        <span class="text-muted">—</span>
    @endif
    @if(!$hasProcess)
        <span class="text-muted">—</span>
    @endif
</td>
