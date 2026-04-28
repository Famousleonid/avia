@php
    $hasRows = $groupedComponents->isNotEmpty() || $separateComponents->isNotEmpty();
@endphp

<div id="log-card-partial-shell" class="log-card-partial log-card-shell"
     data-workorder-id="{{ $current_wo->id }}"
     data-log-card-id="{{ $log_card->id ?? '' }}">
    <script type="application/json" id="log-card-tab-meta">@json($tabMeta)</script>

    <style>
        .log-card-shell.log-card-shell--editing .lc-when-view { display: none !important; }
        .log-card-shell:not(.log-card-shell--editing) .lc-when-edit { display: none !important; }
        /* Multi-variant IPL: in view mode one summary row only; detail rows visible in Edit */
        .log-card-shell:not(.log-card-shell--editing) tr.lc-variant-detail-collapsed-empty { display: none !important; }
        .log-card-shell.log-card-shell--editing tr.lc-variant-summary-empty-single,
        .log-card-shell.log-card-shell--editing tr.lc-variant-summary-selected { display: none !important; }
        .log-card-partial .table-scroll-container { max-height: calc(100vh - 320px); overflow-y: auto; }
        .log-card-partial .table-scroll-container thead th {
            position: sticky; top: 0; z-index: 5; background: var(--bs-table-bg, #031e3a);
        }
    </style>

    @if(!$hasRows)
        <p class="text-center text-muted mt-3">{{ __('No components with log_card=1 for this manual.') }}</p>
    @else
        <div class="table-responsive table-scroll-container">
            <table class="table table-bordered table-hover dir-table align-middle bg-gradient">
                <thead class="table-dark">
                    <tr>
                        <th class="text-primary text-center">{{ __('Description') }}</th>
                        <th class="text-primary text-center">{{ __('Part Number') }} / {{ __('Assy PN') }}</th>
                        <th class="text-primary text-center lc-when-edit">{{ __('Select') }}</th>
                        <th class="text-primary text-center">{{ __('Serial Number') }}</th>
                        <th class="text-primary text-center">{{ __('ASSY Serial Number') }}</th>
                        <th class="text-primary text-center">{{ __('Reason to Removed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedComponents as $groupIndex => $group)
                        @php
                            $iplKey = $group['ipl_group'];
                            $preset = $presetByIplGroup[$iplKey] ?? null;
                            $savedCid = isset($preset['component_id']) ? (int) $preset['component_id'] : null;
                            $compList = $group['components'];
                            $onlyOne = $compList->count() === 1;
                            $firstEntity = $compList->first();
                            $defaultCid = $firstEntity['component']->id ?? null;
                            $effectiveCid = $savedCid ?: ($onlyOne ? $defaultCid : null);
                            $sn = $preset['serial_number'] ?? '';
                            $asy = $preset['assy_serial_number'] ?? '';
                            $rid = $preset['reason'] ?? '';
                            $descComponent = $firstEntity['component'];
                            if ($effectiveCid) {
                                $hit = $compList->first(static function ($x) use ($effectiveCid) {
                                    return (int) $x['component']->id === (int) $effectiveCid;
                                });
                                if ($hit) {
                                    $descComponent = $hit['component'];
                                }
                            }
                            $compactVariantView = ! $log_card && ($group['has_multiple'] ?? false);
                            $collapseDetailInView = ($group['has_multiple'] ?? false)
                                && ((! $log_card) || ($log_card && $effectiveCid));
                            $summaryComponentPlaceholder = null;
                            if ($compactVariantView) {
                                $summaryComponentPlaceholder = $group['components']
                                    ->sortBy(static function ($x) {
                                        return $x['component']->ipl_num ?? '';
                                    })
                                    ->last()['component'];
                            }
                        @endphp
                        @if($compactVariantView && $summaryComponentPlaceholder)
                            <tr class="lc-variant-summary-empty-single lc-when-view">
                                <td class="align-middle">
                                    {{ $summaryComponentPlaceholder->name }} ({{ $summaryComponentPlaceholder->ipl_num }})
                                    <br><span class="text-muted small">{{ __(':count IPL variants — :hint', ['count' => $group['count'], 'hint' => __('Use Enter Data to choose the variant.')]) }}</span>
                                </td>
                                <td class="text-start ps-3">
                                    {{ $summaryComponentPlaceholder->part_number }}
                                    @if($summaryComponentPlaceholder->assy_part_number)
                                        / {{ $summaryComponentPlaceholder->assy_part_number }}
                                    @endif
                                </td>
                                {{-- Same as other rows: column must use lc-when-edit so hide in view matches thead "Select" --}}
                                <td class="lc-when-edit"></td>
                                <td>{{ $sn }}</td>
                                <td>{{ $asy }}</td>
                                <td>@php $codSv = $rid ? $codes->firstWhere('id', $rid) : null; @endphp {{ $codSv ? $codSv->name : '' }}</td>
                            </tr>
                        @elseif($collapseDetailInView && $log_card && $effectiveCid)
                            <tr class="lc-variant-summary-selected lc-when-view">
                                <td class="align-middle">{{ $descComponent->name }} ({{ $descComponent->ipl_num }})</td>
                                <td class="text-start ps-3">
                                    {{ $descComponent->part_number }}
                                    @if($descComponent->assy_part_number)
                                        / {{ $descComponent->assy_part_number }}
                                    @endif
                                </td>
                                <td class="lc-when-edit"></td>
                                <td>{{ $sn }}</td>
                                <td>{{ $asy }}</td>
                                <td>
                                    @php $codSv = $rid ? $codes->firstWhere('id', $rid) : null; @endphp
                                    {{ $codSv ? $codSv->name : '' }}
                                </td>
                            </tr>
                        @endif
                        @foreach($group['components'] as $i => $componentData)
                            @php
                                $component = $componentData['component'];
                                $reasonDef = $componentData['reason_for_remove'] ?? '';
                                $isChecked = $effectiveCid && (int) $component->id === (int) $effectiveCid;
                                if (!$savedCid && $onlyOne) {
                                    $isChecked = true;
                                }
                            @endphp
                            <tr @if(!empty($collapseDetailInView)) class="lc-variant-detail-collapsed-empty" @endif>
                                @if($i === 0)
                                    <td rowspan="{{ $group['count'] }}" class="align-middle">
                                        <span class="lc-when-view">{{ $descComponent->name }} ({{ $descComponent->ipl_num }})</span>
                                        <span class="lc-when-edit">{{ $descComponent->name }} ({{ $descComponent->ipl_num }})</span>
                                    </td>
                                @endif
                                <td class="text-start ps-3">
                                    {{ $component->part_number }}
                                    @if($component->assy_part_number)
                                        / {{ $component->assy_part_number }}
                                    @endif
                                </td>
                                @if($group['has_multiple'])
                                    <td class="text-center lc-when-edit">
                                        <input type="radio"
                                               class="lc-comp-radio component-radio"
                                               name="lc_selected_component[{{ $groupIndex }}]"
                                               value="{{ $component->id }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                    </td>
                                @elseif($i === 0)
                                    <td class="text-center lc-when-edit">
                                        @foreach($group['components'] as $solo)
                                            <input type="hidden" name="lc_selected_component[{{ $groupIndex }}]" value="{{ $solo['component']->id }}">
                                        @endforeach
                                        <span class="text-muted small">—</span>
                                    </td>
                                @endif
                                @if($i === 0)
                                    <td class="align-middle" rowspan="{{ $group['count'] }}">
                                        <span class="lc-when-view">{{ $sn }}</span>
                                        <span class="lc-when-edit">
                                            <input type="text"
                                                   class="form-control form-control-sm serial-number-input lc-sn"
                                                   data-group-index="{{ $groupIndex }}"
                                                   name="lc_serial_numbers[{{ $groupIndex }}]"
                                                   value="{{ $sn }}"
                                                   placeholder="{{ __('Serial Number') }}">
                                        </span>
                                    </td>
                                    <td class="align-middle" rowspan="{{ $group['count'] }}">
                                        <span class="lc-when-view">{{ $asy }}</span>
                                        <span class="lc-when-edit">
                                            @if($descComponent->assy_part_number)
                                                <input type="text"
                                                       class="form-control form-control-sm lc-assy-sn"
                                                       data-group-index="{{ $groupIndex }}"
                                                       name="lc_assy_serial_numbers[{{ $groupIndex }}]"
                                                       value="{{ $asy }}"
                                                       placeholder="{{ __('ASSY Serial Number') }}">
                                            @endif
                                        </span>
                                    </td>
                                    <td class="align-middle" rowspan="{{ $group['count'] }}">
                                        @php
                                            $codV = $rid ? $codes->firstWhere('id', $rid) : null;
                                            $reasonLabelView = $codV ? $codV->name : '';
                                        @endphp
                                        <span class="lc-when-view">{{ $reasonLabelView }}</span>
                                        <span class="lc-when-edit">
                                            <select class="form-control form-control-sm reason-select lc-reason"
                                                    name="lc_reasons[{{ $groupIndex }}]"
                                                    data-group-index="{{ $groupIndex }}">
                                                <option value="">{{ __('Reason (Optional)') }}</option>
                                                @foreach($codes as $c)
                                                    <option value="{{ $c->id }}" @selected((string)$rid === (string)$c->id)>{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach

                    @foreach($separateComponents as $index => $row)
                        @php
                            $component = $row['component'];
                            $preset = $separateQueue[$index] ?? null;
                            $sn = $preset['serial_number'] ?? '';
                            $asy = $preset['assy_serial_number'] ?? '';
                            $rid = $preset['reason'] ?? '';
                            $reasonDef = $row['reason_for_remove'] ?? '';
                            $unitIndex = $row['unit_index'];
                            $unitsAssy = $row['units_assy'];
                            $codS = $rid ? $codes->firstWhere('id', $rid) : null;
                            $reasonLabelView = $codS ? $codS->name : '';
                        @endphp
                        <tr>
                            <td>
                                <span class="lc-when-view">{{ $component->name }} ({{ $component->ipl_num }})<br>
                                    <small class="text-muted">{{ __('Unit') }} {{ $unitIndex }} {{ __('of') }} {{ $unitsAssy }}</small>
                                </span>
                                <span class="lc-when-edit">{{ $component->name }} ({{ $component->ipl_num }})<br>
                                    <small class="text-muted">{{ __('Unit') }} {{ $unitIndex }} {{ __('of') }} {{ $unitsAssy }}</small>
                                </span>
                            </td>
                            <td class="text-start ps-3">
                                {{ $component->part_number }}
                                @if($component->assy_part_number)
                                    / {{ $component->assy_part_number }}
                                @endif
                            </td>
                            <td class="text-center lc-when-edit">
                                <input type="hidden" name="lc_selected_component[separate_{{ $index }}]" value="{{ $component->id }}">
                                <span class="text-muted small">—</span>
                            </td>
                            <td>
                                <span class="lc-when-view">{{ $sn }}</span>
                                <span class="lc-when-edit">
                                    <input type="text"
                                           class="form-control form-control-sm lc-sn"
                                           name="lc_serial_numbers[separate_{{ $index }}]"
                                           value="{{ $sn }}"
                                           placeholder="{{ __('Serial Number') }}">
                                </span>
                            </td>
                            <td>
                                <span class="lc-when-view">{{ $asy }}</span>
                                <span class="lc-when-edit">
                                    @if($component->assy_part_number)
                                        <input type="text"
                                               class="form-control form-control-sm lc-assy-sn"
                                               name="lc_assy_serial_numbers[separate_{{ $index }}]"
                                               value="{{ $asy }}"
                                               placeholder="{{ __('ASSY Serial Number') }}">
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="lc-when-view">{{ $reasonLabelView }}</span>
                                <span class="lc-when-edit">
                                    <select class="form-control form-control-sm reason-select lc-reason" name="lc_reasons[separate_{{ $index }}]">
                                        <option value="">{{ __('Reason (Optional)') }}</option>
                                        @foreach($codes as $c)
                                            <option value="{{ $c->id }}" @selected((string)$rid === (string)$c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
