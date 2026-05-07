@php
    $hasRows = $groupedComponents->isNotEmpty() || $separateComponents->isNotEmpty();
    $componentData = isset($componentData) && is_array($componentData) ? $componentData : [];
    $hasSavedLogCard = $log_card && !empty($componentData);
@endphp

<div id="log-card-partial-shell" class="log-card-partial"
     data-workorder-id="{{ $current_wo->id }}"
     data-log-card-id="{{ $log_card->id ?? '' }}"
     data-state="{{ $hasSavedLogCard ? 'saved' : 'draft' }}">
    <script type="application/json" id="log-card-tab-meta">@json($tabMeta)</script>

    <style>
        .log-card-partial .table-scroll-container {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
            overflow-y: auto;
        }
        .log-card-partial {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
        }
        .log-card-partial .table-scroll-container thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--bs-table-bg, #031e3a);
        }
        .log-card-partial .lc-option-line {
            display: flex;
            gap: .45rem;
            align-items: flex-start;
        }
        .log-card-partial .lc-option-line .form-check-input {
            margin-top: .15rem;
        }
        .log-card-partial .lc-assy-choice {
            margin-top: 0;
            padding-top: 0;
        }
        .log-card-partial .lc-assy-choice .form-check {
            margin-bottom: .15rem;
        }
        .log-card-partial .lc-assy-choice label {
            cursor: pointer;
        }
        .log-card-partial .lc-inline-input {
            min-width: 130px;
        }
    </style>

    @if(!$hasRows && !$hasSavedLogCard)
        <p class="text-center text-muted mt-3">{{ __('No components with log_card=1 for this manual.') }}</p>
    @elseif(!$hasSavedLogCard)
        <div class="table-responsive table-scroll-container">
            <table class="table table-bordered table-hover dir-table align-middle bg-gradient">
                <thead class="table-dark">
                    <tr>
                        <th class="text-primary text-center" style="width: 55%;">{{ __('Description') }} / {{ __('Part Number') }}</th>
                        <th class="text-primary text-center">{{ __('Assy') }} ({{ __('IPL') }} / {{ __('Part Number') }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedComponents as $groupIndex => $group)
                        @php
                            $compList = $group['components'];
                            $onlyOne = $compList->count() === 1;
                        @endphp
                        @foreach($compList as $i => $componentDataRow)
                            @php
                                $component = $componentDataRow['component'];
                                $assemblyRows = ($component->relationLoaded('assemblies') ? $component->assemblies : collect())
                                    ->filter(function ($assembly) {
                                        return filled($assembly->assy_part_number ?? null)
                                            || filled($assembly->assy_ipl_num ?? null);
                                    })
                                    ->values();
                                $defaultAssemblyId = $assemblyRows->first()->id ?? null;
                                $componentInputName = 'lc_selected_component['.$groupIndex.']';
                                $assemblyInputName = 'lc_selected_assembly['.$groupIndex.']';
                            @endphp
                            <tr>
                                <td>
                                    @if($onlyOne)
                                        <input type="hidden"
                                               name="{{ $componentInputName }}"
                                               value="{{ $component->id }}"
                                               data-ipl-group="{{ $group['ipl_group'] }}">
                                        {{ $component->name }} ({{ $component->ipl_num }}) / {{ $component->part_number }}
                                    @else
                                        <label class="lc-option-line mb-0">
                                            <input type="radio"
                                                   class="form-check-input lc-comp-radio"
                                                   name="{{ $componentInputName }}"
                                                   value="{{ $component->id }}"
                                                   data-ipl-group="{{ $group['ipl_group'] }}"
                                                   @checked($i === 0)>
                                            <span>{{ $component->name }} ({{ $component->ipl_num }}) / {{ $component->part_number }}</span>
                                        </label>
                                    @endif
                                </td>
                                <td class="text-start ps-3">
                                    @if($assemblyRows->isNotEmpty())
                                        <div class="lc-assy-choice" data-component-id="{{ $component->id }}">
                                            @if($assemblyRows->count() > 1)
                                                @foreach($assemblyRows as $assembly)
                                                    @php $assyId = 'lc-assy-'.$groupIndex.'-'.$assembly->id; @endphp
                                                    <div class="form-check">
                                                       <input type="radio"
                                                              id="{{ $assyId }}"
                                                               class="form-check-input lc-assy-radio {{ (!$onlyOne && $i !== 0) ? 'd-none' : '' }}"
                                                               name="{{ $assemblyInputName }}"
                                                               value="{{ $assembly->id }}"
                                                               data-component-id="{{ $component->id }}"
                                                               data-assy-part-number="{{ $assembly->assy_part_number }}"
                                                               data-assy-ipl-num="{{ $assembly->assy_ipl_num }}"
                                                               data-units-assy="{{ $assembly->units_assy }}"
                                                               @checked(($onlyOne || $i === 0) && (int) $defaultAssemblyId === (int) $assembly->id)>
                                                        <label class="form-check-label small" for="{{ $assyId }}">
                                                            {{ $assembly->assy_ipl_num ?: '-' }} / {{ $assembly->assy_part_number ?: '-' }}
                                                        </label>
                                                    </div>
                                                @endforeach
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
                            $assemblyRows = ($component->relationLoaded('assemblies') ? $component->assemblies : collect())
                                ->filter(function ($assembly) {
                                    return filled($assembly->assy_part_number ?? null)
                                        || filled($assembly->assy_ipl_num ?? null);
                                })
                                ->values();
                            $assemblyInputName = 'lc_selected_assembly[separate_'.$index.']';
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden"
                                       name="lc_selected_component[separate_{{ $index }}]"
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
                                            @foreach($assemblyRows as $assembly)
                                                @php $assyId = 'lc-assy-separate-'.$index.'-'.$assembly->id; @endphp
                                                <div class="form-check">
                                                    <input type="radio"
                                                           id="{{ $assyId }}"
                                                           class="form-check-input lc-assy-radio"
                                                           name="{{ $assemblyInputName }}"
                                                           value="{{ $assembly->id }}"
                                                           data-component-id="{{ $component->id }}"
                                                           data-assy-part-number="{{ $assembly->assy_part_number }}"
                                                           data-assy-ipl-num="{{ $assembly->assy_ipl_num }}"
                                                           data-units-assy="{{ $assembly->units_assy }}"
                                                           @checked($loop->first)>
                                                    <label class="form-check-label small" for="{{ $assyId }}">
                                                        {{ $assembly->assy_ipl_num ?: '-' }} / {{ $assembly->assy_part_number ?: '-' }}
                                                    </label>
                                                </div>
                                            @endforeach
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
                </tbody>
            </table>
        </div>
    @else
        <div class="table-responsive table-scroll-container">
            <table class="table table-bordered table-hover dir-table align-middle bg-gradient">
                <thead class="table-dark">
                    <tr>
                        <th class="text-primary text-center">{{ __('Description') }} / {{ __('Part Number') }}</th>
                        <th class="text-primary text-center">{{ __('Serial Number') }}</th>
                        <th class="text-primary text-center">{{ __('Part Number Assy') }}</th>
                        <th class="text-primary text-center">{{ __('ASSY Serial Number') }}</th>
                        <th class="text-primary text-center">{{ __('Reason to Removed') }}</th>
                        <th class="text-primary text-center">{{ __('New Serial Number') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($componentData as $index => $item)
                        @php
                            $component = $components->firstWhere('id', (int) ($item['component_id'] ?? 0));
                            if (!$component) {
                                continue;
                            }
                            $assemblyId = $item['component_assembly_id'] ?? '';
                            $assyPartNumber = $item['assy_part_number'] ?? '';
                            $assyIplNum = $item['assy_ipl_num'] ?? '';
                            if (!$assyPartNumber && $assemblyId && $component->relationLoaded('assemblies')) {
                                $assembly = $component->assemblies->firstWhere('id', (int) $assemblyId);
                                $assyPartNumber = $assembly->assy_part_number ?? '';
                                $assyIplNum = $assembly->assy_ipl_num ?? $assyIplNum;
                            }
                        @endphp
                        <tr class="lc-saved-row"
                            data-component-id="{{ $component->id }}"
                            data-ipl-group="{{ $item['ipl_group'] ?? '' }}"
                            data-component-assembly-id="{{ $assemblyId }}"
                            data-assy-part-number="{{ $assyPartNumber }}"
                            data-assy-ipl-num="{{ $assyIplNum }}"
                            data-units-assy="{{ $item['units_assy'] ?? '' }}">
                            <td>
                                {{ $item['name'] ?? $item['description'] ?? $component->name }} ({{ $component->ipl_num }}) / {{ $item['part_number'] ?? $component->part_number }}
                                @if(!empty($item['unit_index']) && !empty($item['units_assy']))
                                    <br><small class="text-muted">{{ __('Unit') }} {{ $item['unit_index'] }} {{ __('of') }} {{ $item['units_assy'] }}</small>
                                @endif
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm lc-inline-input lc-saved-field"
                                       name="serial_number"
                                       value="{{ $item['serial_number'] ?? '' }}"
                                       placeholder="{{ __('Serial Number') }}">
                            </td>
                            <td class="text-start ps-3">
                                @if($assyPartNumber || $assyIplNum)
                                    {{ $assyPartNumber ?: $assyIplNum }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm lc-inline-input lc-saved-field"
                                       name="assy_serial_number"
                                       value="{{ $item['assy_serial_number'] ?? '' }}"
                                       placeholder="{{ __('ASSY Serial Number') }}">
                            </td>
                            <td>
                                <select class="form-control form-control-sm lc-inline-input lc-saved-field" name="reason">
                                    <option value="">{{ __('Reason') }}</option>
                                    @foreach($codes as $c)
                                        <option value="{{ $c->id }}" @selected((string) ($item['reason'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm lc-inline-input lc-saved-field"
                                       name="new_serial_number"
                                       value="{{ $item['new_serial_number'] ?? '' }}"
                                       placeholder="{{ __('New Serial Number') }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
