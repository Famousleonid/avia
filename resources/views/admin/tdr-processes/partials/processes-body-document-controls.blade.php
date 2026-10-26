@php
    $attachmentRows = isset($attachmentGroup)
        ? $tdrProcesses->filter(fn ($row) => (int) $row->tdrs_id === (int) $current_tdr->id && $row->in_traveler && !$row->ignore_row && (int) $row->traveler_group === (int) $attachmentGroup)
        : collect([$tdrProcessRow]);
    $hasAttachments = false;
    foreach ($attachmentRows as $attachmentRow) {
        foreach ((array) ($attachmentRow->rule_process_ids ?? []) as $rid) {
            $hasAttachments = $hasAttachments || !empty(($docsByRp ?? [])[$rid]);
        }
        foreach ((array) ($attachmentRow->phase_rule_process_ids ?? []) as $rid) {
            $hasAttachments = $hasAttachments || !empty(($phaseDocsByRp ?? [])[$rid]);
        }
    }
    if (isset($attachmentGroup)) {
        $hasAttachments = $hasAttachments || \App\Services\TravelerTemplateImages::forProcesses($current_tdr, $attachmentRows)->isNotEmpty();
    }
    $attachmentParams = ['tdr' => $current_tdr->id];
    if (isset($attachmentGroup)) $attachmentParams['traveler_group'] = $attachmentGroup;
    else $attachmentParams['row'] = $tdrProcessRow->id;
@endphp
@if($hasAttachments)
    <a class="btn btn-sm btn-outline-info process-picture-button" href="{{ route('tdr-processes.attachments', $attachmentParams) }}"
       target="_blank" rel="noopener" title="{{ __('View form attachments') }}" aria-label="{{ __('View form attachments') }}"><i class="bi bi-image" aria-hidden="true"></i></a>
@else
    <button type="button" class="btn btn-sm btn-outline-secondary process-picture-button" disabled
            title="{{ __('No form attachments') }}" aria-label="{{ __('No form attachments') }}"><i class="bi bi-image" aria-hidden="true"></i></button>
@endif