@php
    $manual = $manual ?? null;
    $sectionKey = $sectionKey ?? '';
    $manualLabel = trim((string) (($manual->number ?? '').' '.($manual->title ?? '')));
    $manualLabel = $manualLabel !== '' ? $manualLabel : __('Manual').' #'.($manual->id ?? '');
@endphp

<tr class="table-secondary lc-manual-heading"
    data-manual-id="{{ $manual->id ?? '' }}"
    data-manual-label="{{ $manualLabel }}">
    <td colspan="3" class="fw-semibold text-dark">
        {{ __('Manual') }}: {{ $manualLabel }}
    </td>
</tr>

@foreach($groupedComponents as $groupIndex => $group)
    @php
        $compList = $group['components'];
        $rowGroupKey = $sectionKey !== '' ? $sectionKey.'_'.$groupIndex : (string) $groupIndex;
    @endphp
    @foreach($compList as $componentDataRow)
        @php
            $component = $componentDataRow['component'];
            $assemblyRows = ($component->relationLoaded('assemblies') ? $component->assemblies : collect())
                ->filter(function ($assembly) {
                    return filled($assembly->assy_part_number ?? null)
                        || filled($assembly->assy_ipl_num ?? null);
                })
                ->values();
            $defaultAssemblyId = $assemblyRows->first()->id ?? null;
            $componentInputName = 'lc_selected_component['.$rowGroupKey.']';
            $assemblyInputName = 'lc_selected_assembly['.$rowGroupKey.'_'.$component->id.']';
        @endphp
        <tr data-manual-id="{{ $manual->id ?? '' }}" data-manual-label="{{ $manualLabel }}">
            <td class="text-center">
                <input type="checkbox"
                       class="form-check-input lc-include-checkbox"
                       name="lc_include[{{ $rowGroupKey }}]"
                       value="1"
                       data-component-id="{{ $component->id }}"
                       data-group-key="{{ $rowGroupKey }}"
                       data-ipl-group="{{ $group['ipl_group'] }}"
                       @disabled($logCardTdrReadOnly)
                       checked>
            </td>
            <td>
                <input type="hidden"
                       name="{{ $componentInputName }}"
                       value="{{ $component->id }}"
                       data-ipl-group="{{ $group['ipl_group'] }}">
                {{ $component->name }} ({{ $component->ipl_num }}) / {{ $component->part_number }}
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
@endforeach

@foreach($separateComponents as $index => $row)
    @php
        $component = $row['component'];
        $rowGroupKey = $sectionKey !== '' ? $sectionKey.'_separate_'.$index : 'separate_'.$index;
        $assemblyRows = ($component->relationLoaded('assemblies') ? $component->assemblies : collect())
            ->filter(function ($assembly) {
                return filled($assembly->assy_part_number ?? null)
                    || filled($assembly->assy_ipl_num ?? null);
            })
            ->values();
        $assemblyInputName = 'lc_selected_assembly['.$rowGroupKey.']';
    @endphp
    <tr data-manual-id="{{ $manual->id ?? '' }}" data-manual-label="{{ $manualLabel }}">
        <td class="text-center">
            <input type="checkbox"
                   class="form-check-input lc-include-checkbox"
                   name="lc_include[{{ $rowGroupKey }}]"
                   value="1"
                   data-component-id="{{ $component->id }}"
                   data-group-key="{{ $rowGroupKey }}"
                   @disabled($logCardTdrReadOnly)
                   checked>
        </td>
        <td>
            <input type="hidden"
                   name="lc_selected_component[{{ $rowGroupKey }}]"
                   value="{{ $component->id }}"
                   data-unit-index="{{ $row['unit_index'] }}"
                   data-units-assy="{{ $row['units_assy'] }}">
            {{ $component->name }} ({{ $component->ipl_num }}) / {{ $component->part_number }}
            <br><small class="text-muted">{{ __('Unit') }} {{ $row['unit_index'] }} {{ __('of') }} {{ $row['units_assy'] }}</small>
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
                                        @selected($loop->first)>
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
