{{-- Document column cell: generate a concrete PDF from the process' document template(s). --}}
{{-- Requires: $tdrProcessRow, $docsByRp (rule_process_id => [ProcessDocument]), $current_wo --}}
{{-- The part dimensions sheet ($ecDoc) is ONE drawing shared by repair (Machining) and --}}
{{-- EC (Machining (EC)) — rendered filtered to THIS row's place → per-place PDF. --}}
{{-- Колонка скрыта целиком, если нет ни шаблонов процессов, ни страниц-мест. --}}
@if(!empty($docsByRp) || !empty($ecPageParams ?? []))
@php
    // Machining-type row (repair OR EC) → the part dimensions sheet for this row's place.
    $_isMachiningRow = isset($machiningNameIds) && in_array((int) $tdrProcessRow->process_names_id, $machiningNameIds, true);
    $_placeParam = null;
    if ($_isMachiningRow) {
        foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
            if (!empty($rpToParam[$rid] ?? null)) { $_placeParam = (int) $rpToParam[$rid]; break; }
        }
    }

    // Unified generate targets: ['id'=>docId, 'param'=>?int place, 'label'=>str, 'dim'=>bool]
    $items = [];
    if ($_isMachiningRow && !empty($ecDoc) && $_placeParam && in_array($_placeParam, $ecPageParams ?? [], true)) {
        $items['ec'] = ['id' => $ecDoc->id, 'param' => $_placeParam, 'label' => ($ecDoc->title ?: 'Dimensions'), 'dim' => true];
    }
    foreach ((array) ($tdrProcessRow->rule_process_ids ?? []) as $rid) {
        foreach ($docsByRp[$rid] ?? [] as $d) {
            $items['d' . $d->id] = ['id' => $d->id, 'param' => null, 'label' => ($d->title ?: $d->doc_type ?: ('Doc #' . $d->id)), 'dim' => false];
        }
    }
    $items = array_values($items);
@endphp
<td class="text-center align-middle process-doc-cell">
    @if(empty($items) || !isset($current_wo))
        <span class="text-muted small">—</span>
    @elseif(count($items) === 1)
        @php $it = $items[0]; @endphp
        <button type="button"
                class="btn btn-sm {{ $it['dim'] ? 'btn-outline-warning' : 'btn-outline-success' }} gen-doc-btn" style="width: 60px"
                data-doc-id="{{ $it['id'] }}"
                @if($it['param'])data-parameter-id="{{ $it['param'] }}"@endif
                data-wo-id="{{ $current_wo->id }}"
                title="{{ __('Generate') }}: {{ $it['label'] }}">
            <i class="bi {{ $it['dim'] ? 'bi-rulers' : 'bi-file-earmark-pdf' }}"></i>
        </button>
    @else
        <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-pdf"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($items as $it)
                    <li>
                        <button type="button" class="dropdown-item gen-doc-btn"
                                data-doc-id="{{ $it['id'] }}"
                                @if($it['param'])data-parameter-id="{{ $it['param'] }}"@endif
                                data-wo-id="{{ $current_wo->id }}">
                            <i class="bi {{ $it['dim'] ? 'bi-rulers' : 'bi-file-earmark-pdf' }} me-1"></i>{{ $it['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</td>
@endif
