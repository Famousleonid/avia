@php
    $manual = $manual ?? null;
    $sectionKey = $sectionKey ?? '';
    $manualLabel = trim((string) (($manual->number ?? '').' '.($manual->title ?? '')));
    $manualLabel = $manualLabel !== '' ? $manualLabel : __('Manual').' #'.($manual->id ?? '');
    $savedComponentRows = collect(isset($componentData) && is_array($componentData) ? $componentData : [])
        ->filter(fn ($row) => is_array($row)
            && ($row['row_type'] ?? '') !== 'manual'
            && !empty($row['component_id']));
    $orderedComponents = collect($orderedComponents ?? []);
@endphp

<tr class="table-secondary lc-manual-heading"
    data-manual-id="{{ $manual->id ?? '' }}"
    data-manual-label="{{ $manualLabel }}">
    <td colspan="3" class="fw-semibold text-dark">
        {{ __('Manual') }}: {{ $manualLabel }}
    </td>
</tr>

@foreach($orderedComponents as $orderedIndex => $orderedRow)
    @php
        $isMultipleUnit = ($orderedRow['row_type'] ?? '') === 'unit';
        $component = $orderedRow['component'];

        if ($isMultipleUnit) {
            $unitRow = $orderedRow['unit_row'];
            $unitIndex = (int) ($unitRow['unit_index'] ?? 0);
            $unitsAssy = (int) ($unitRow['units_assy'] ?? 0);
            $separateIndex = (int) ($orderedRow['separate_index'] ?? $orderedIndex);
            $rowGroupKey = $sectionKey !== ''
                ? $sectionKey.'_separate_'.$separateIndex
                : 'separate_'.$separateIndex;
            $iplGroup = '';
            $savedRow = $savedComponentRows->first(function ($saved) use ($component, $unitIndex) {
                return (int) ($saved['component_id'] ?? 0) === (int) $component->id
                    && (int) ($saved['unit_index'] ?? 0) === $unitIndex;
            });
        } else {
            $componentDataRow = $orderedRow['component_data_row'];
            $group = $orderedRow['group'];
            $groupIndex = (string) ($orderedRow['group_index'] ?? '');
            $rowGroupKey = $sectionKey !== '' ? $sectionKey.'_'.$groupIndex : $groupIndex;
            $iplGroup = (string) ($group['ipl_group'] ?? '');
            $unitIndex = 0;
            $unitsAssy = 0;
            $savedRow = $savedComponentRows->first(function ($row) use ($component) {
                return (int) ($row['component_id'] ?? 0) === (int) $component->id
                    && empty($row['unit_index']);
            });
        }

        $assemblyRows = ($component->relationLoaded('assemblies') ? $component->assemblies : collect())
            ->filter(function ($assembly) {
                return filled($assembly->assy_part_number ?? null)
                    || filled($assembly->assy_ipl_num ?? null);
            })
            ->values();
        $savedAssemblyId = (int) ($savedRow['component_assembly_id'] ?? 0);
        $defaultAssemblyId = $assemblyRows->contains('id', $savedAssemblyId)
            ? $savedAssemblyId
            : ($assemblyRows->first()->id ?? null);
        $componentInputName = 'lc_selected_component['.$rowGroupKey.']';
        $assemblyInputName = $isMultipleUnit
            ? 'lc_selected_assembly['.$rowGroupKey.']'
            : 'lc_selected_assembly['.$rowGroupKey.'_'.$component->id.']';
    @endphp

    <tr data-manual-id="{{ $manual->id ?? '' }}" data-manual-label="{{ $manualLabel }}">
        <td class="text-center">
            <input type="checkbox"
                   class="form-check-input lc-include-checkbox"
                   name="lc_include[{{ $rowGroupKey }}]"
                   value="1"
                   data-component-id="{{ $component->id }}"
                   data-group-key="{{ $rowGroupKey }}"
                   @if(!$isMultipleUnit) data-ipl-group="{{ $iplGroup }}" @endif
                   @checked((bool) $savedRow)
                   @disabled($logCardTdrReadOnly)>
        </td>
        <td>
            <input type="hidden"
                   name="{{ $componentInputName }}"
                   value="{{ $component->id }}"
                   @if($isMultipleUnit)
                       data-unit-index="{{ $unitIndex }}"
                       data-units-assy="{{ $unitsAssy }}"
                   @else
                       data-ipl-group="{{ $iplGroup }}"
                   @endif>
            {{ $component->part_number }} <span class="lc-ipl text-secondary">({{ $component->ipl_num }})</span> {{ $component->name }}
            @if($isMultipleUnit)
                <small class="text-muted text-nowrap ms-2">{{ __('Unit') }} {{ $unitIndex }} {{ __('of') }} {{ $unitsAssy }}</small>
            @endif
        </td>
        <td class="text-start ps-3">
            @if($assemblyRows->isNotEmpty())
                <div class="lc-assy-choice" data-component-id="{{ $component->id }}">
                    @if($assemblyRows->count() > 1)
                        <select class="form-control form-control-sm lc-inline-input lc-assy-select"
                                name="{{ $assemblyInputName }}"
                                data-component-id="{{ $component->id }}"
                                @disabled($logCardTdrReadOnly)>
                            @foreach($assemblyRows as $assembly)
                                <option value="{{ $assembly->id }}"
                                        data-component-id="{{ $component->id }}"
                                        data-assy-part-number="{{ $assembly->assy_part_number }}"
                                        data-assy-ipl-num="{{ $assembly->assy_ipl_num }}"
                                        data-units-assy="{{ $assembly->units_assy }}"
                                        @selected((int) $defaultAssemblyId === (int) $assembly->id)>
                                    {{ $assembly->assy_ipl_num ?: '-' }} / {{ $assembly->assy_part_number ?: '-' }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        @php $assembly = $assemblyRows->first(); @endphp
                        <input type="hidden"
                               name="{{ $assemblyInputName }}"
                               value="{{ $assembly->id }}"
                               data-component-id="{{ $component->id }}"
                               data-assy-part-number="{{ $assembly->assy_part_number }}"
                               data-assy-ipl-num="{{ $assembly->assy_ipl_num }}"
                               data-units-assy="{{ $assembly->units_assy }}">
                        <div class="small text-muted">{{ $assembly->assy_ipl_num ?: '-' }} / {{ $assembly->assy_part_number ?: '-' }}</div>
                    @endif
                </div>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
    </tr>
@endforeach
