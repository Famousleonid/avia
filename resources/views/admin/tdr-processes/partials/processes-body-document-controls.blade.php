{{-- Doc button(s) inside the Form column: open the process document in a new tab
     (HTML view with Print and an explicit "Save PDF to WO library" action). --}}
{{-- Requires: $tdrProcessRow, $docsByRp (rule_process_id => [ProcessDocument]), $current_wo --}}
{{-- Документы привязаны к процессу (rule-process); Machining (EC) сохраняет связь → тот же чертёж. --}}
@php
    $rowDocs = [];
    foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
        foreach (($docsByRp ?? [])[$rid] ?? [] as $d) {
            $rowDocs[$d->id] = $d;
        }
    }
    // Start/Finish process documents (separate id space).
    foreach ((array) ($tdrProcessRow->phase_rule_process_ids ?? []) as $rid) {
        foreach (($phaseDocsByRp ?? [])[$rid] ?? [] as $d) {
            $rowDocs['p' . $d->id] = $d;
        }
    }
    $rowDocs = array_values($rowDocs);
@endphp
@if(!empty($rowDocs) && isset($current_wo))
    @if(count($rowDocs) === 1)
        @php $d = $rowDocs[0]; @endphp
        <a href="{{ route('process-documents.view', ['workorder' => $current_wo->id, 'processDocument' => $d->id]) }}"
           target="_blank"
           class="btn btn-sm btn-outline-success"
           title="{{ __('Open document') }}: {{ $d->title ?: $d->doc_type }}">Fig.</a>
    @else
        <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Fig.</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($rowDocs as $d)
                    <li>
                        <a class="dropdown-item"
                           target="_blank"
                           href="{{ route('process-documents.view', ['workorder' => $current_wo->id, 'processDocument' => $d->id]) }}">
                            <i class="bi {{ $d->doc_type === 'manual_page' ? 'bi-printer' : 'bi-file-earmark-pdf' }} me-1"></i>{{ $d->title ?: $d->doc_type ?: ('Doc #'.$d->id) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
