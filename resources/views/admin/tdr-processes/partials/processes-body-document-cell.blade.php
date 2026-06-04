{{-- Document column cell (2c.1): generate a concrete PDF from the process' document template(s). --}}
{{-- Requires: $tdrProcessRow, $docsByRp (rule_process_id => [ProcessDocument]), $current_wo --}}
{{-- Machining (EC) rows: the part EC sheet ($ecDoc) filtered to THIS row's place → per-place PDF. --}}
{{-- Колонка скрыта целиком, если нет ни шаблонов процессов, ни EC-страниц — тогда <td> не выводим. --}}
@if(!empty($docsByRp) || !empty($ecPageParams ?? []))
@php
    // Is this a Machining (EC) row, and does its place have EC drawing page(s)?
    $_isMecRow = isset($machiningEcNameId) && (int) $tdrProcessRow->process_names_id === (int) $machiningEcNameId;
    $_placeParam = null;
    if ($_isMecRow) {
        foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
            if (!empty($rpToParam[$rid] ?? null)) { $_placeParam = (int) $rpToParam[$rid]; break; }
        }
    }
    $_ecPlace = ($_isMecRow && !empty($ecDoc) && $_placeParam && in_array($_placeParam, $ecPageParams ?? [], true));

    $rowDocs = [];
    if (!$_ecPlace) {
        foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
            foreach ($docsByRp[$rid] ?? [] as $d) {
                $rowDocs[$d->id] = $d;
            }
        }
        $rowDocs = array_values($rowDocs);
    }
@endphp
@if($_ecPlace)
<td class="text-center align-middle process-doc-cell">
    @if(isset($current_wo))
        <button type="button"
                class="btn btn-sm btn-outline-warning gen-doc-btn" style="width: 60px"
                data-doc-id="{{ $ecDoc->id }}"
                data-parameter-id="{{ $_placeParam }}"
                data-wo-id="{{ $current_wo->id }}"
                title="{{ __('Generate EC sheet for this place') }}">
            <i class="bi bi-rulers"></i>
        </button>
    @else
        <span class="text-muted small">—</span>
    @endif
</td>
@else
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
@endif {{-- $_ecPlace --}}
@endif {{-- doc column visible --}}
