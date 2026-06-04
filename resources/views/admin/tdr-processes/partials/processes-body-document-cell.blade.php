{{-- Document column cell (2c.1): generate a concrete PDF from the process' document template(s). --}}
{{-- Requires: $tdrProcessRow, $docsByRp (rule_process_id => [ProcessDocument]), $current_wo --}}
{{-- EC row uses $ecDocs (part-level EC dimensions sheet). --}}
{{-- Колонка скрыта целиком, если нет ни шаблонов процессов, ни EC-листа — тогда <td> не выводим. --}}
@if(!empty($docsByRp) || !empty($ecDocs ?? []))
@php
    $_isEcRow = isset($ecProcessNameId) && (int) $tdrProcessRow->process_names_id === (int) $ecProcessNameId;
    $rowDocs = [];
    if ($_isEcRow) {
        foreach (($ecDocs ?? []) as $d) {
            $rowDocs[$d->id] = $d;
        }
    } else {
        foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
            foreach ($docsByRp[$rid] ?? [] as $d) {
                $rowDocs[$d->id] = $d;
            }
        }
    }
    $rowDocs = array_values($rowDocs);
@endphp
<td class="text-center align-middle process-doc-cell">
    @if(empty($rowDocs) || !isset($current_wo))
        <span class="text-muted small">—</span>
    @elseif(count($rowDocs) === 1)
        @php $d = $rowDocs[0]; @endphp
        <button type="button"
                class="btn btn-sm btn-outline-success gen-doc-btn" style="width: 60px"
                data-doc-id="{{ $d->id }}"
                data-wo-id="{{ $current_wo->id }}"
                title="{{ __('Generate document') }}: {{ $d->title ?: $d->doc_type }}">
            <i class="bi bi-file-earmark-pdf"></i>
        </button>
    @else
        <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-pdf"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($rowDocs as $d)
                    <li>
                        <button type="button" class="dropdown-item gen-doc-btn"
                                data-doc-id="{{ $d->id }}" data-wo-id="{{ $current_wo->id }}">
                            <i class="bi bi-file-earmark-pdf me-1"></i>{{ $d->title ?: $d->doc_type ?: ('Doc #'.$d->id) }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</td>
@endif
