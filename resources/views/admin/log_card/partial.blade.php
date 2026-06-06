@php
    $hasRows = $groupedComponents->isNotEmpty() || $separateComponents->isNotEmpty();
    $componentData = isset($componentData) && is_array($componentData) ? $componentData : [];
    $hasSavedLogCard = $log_card && !empty($componentData);
    $logCardTdrReadOnly = (bool) ($logCardTdrAccess['read_only'] ?? false);
    $logCardTdrReadOnlyMessage = $logCardTdrAccess['message'] ?? '';
@endphp

<div id="log-card-partial-shell" class="log-card-partial"
     data-workorder-id="{{ $current_wo->id }}"
     data-log-card-id="{{ $log_card->id ?? '' }}"
     data-readonly="{{ $logCardTdrReadOnly ? '1' : '0' }}"
     data-readonly-message="{{ $logCardTdrReadOnlyMessage }}"
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

    @if($logCardTdrReadOnly)
        <div class="alert alert-warning py-2 px-3 mt-2 mb-3">
            {{ $logCardTdrReadOnlyMessage }}
        </div>
    @endif

    @if(!$hasRows && !$hasSavedLogCard && $availableLogCardManuals->isEmpty())
        <p class="text-center text-muted mt-3">{{ __('No components with log_card=1 for this manual.') }}</p>
    @elseif(!$hasSavedLogCard)
        <div class="table-responsive table-scroll-container">
            <table class="table table-bordered table-hover dir-table align-middle bg-gradient">
                <thead class="table-dark">
                    <tr>
                        <th class="text-primary text-center" style="width: 86px;">
                            <label class="d-inline-flex align-items-center gap-1 mb-0">
                                <input type="checkbox"
                                       class="form-check-input lc-include-toggle-all"
                                       @disabled($logCardTdrReadOnly)
                                       checked>
                                <span>{{ __('Include') }}</span>
                            </label>
                        </th>
                        <th class="text-primary text-center" style="width: 55%;">{{ __('Description') }} / {{ __('Part Number') }}</th>
                        <th class="text-primary text-center">{{ __('Assy') }} ({{ __('IPL') }} / {{ __('Part Number') }})</th>
                    </tr>
                </thead>
                <tbody id="log-card-draft-body">
                    @if($hasRows)
                        @include('admin.log_card.partials.draft-manual-rows', [
                            'manual' => $manual,
                            'sectionKey' => '',
                        ])
                    @endif
                </tbody>
            </table>
        </div>
        <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
            <select class="form-select form-select-sm"
                    id="logCardExtraManualSelect"
                    style="max-width: 420px;"
                    @disabled($logCardTdrReadOnly)>
                <option value="">{{ __('Select manual') }}</option>
                @foreach($availableLogCardManuals as $availableManual)
                    <option value="{{ $availableManual->id }}">
                        {{ trim(($availableManual->number ?? '').' '.($availableManual->title ?? '')) }}
                    </option>
                @endforeach
            </select>
            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    id="logCardAddManualBtn"
                    @disabled($logCardTdrReadOnly || $availableLogCardManuals->isEmpty())>
                <i class="fas fa-plus"></i> {{ __('Add another manual') }}
            </button>
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
                            if (($item['row_type'] ?? '') === 'manual') {
                                $manualLabel = $item['manual_label'] ?? trim((string) (($item['manual_number'] ?? '').' '.($item['manual_title'] ?? '')));
                            } else {
                                $manualLabel = null;
                            }
                        @endphp
                        @if(($item['row_type'] ?? '') === 'manual')
                            <tr class="table-secondary lc-manual-saved-row"
                                data-row-index="{{ $index }}"
                                data-row-type="manual"
                                data-manual-id="{{ $item['manual_id'] ?? '' }}"
                                data-manual-label="{{ $manualLabel }}">
                                <td colspan="6" class="fw-semibold text-dark">
                                    {{ __('Manual') }}: {{ $manualLabel ?: __('Manual') }}
                                </td>
                            </tr>
                            @continue
                        @endif
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
                            if (!$assyPartNumber && $component->relationLoaded('assemblies') && $component->assemblies->count() === 1) {
                                $assembly = $component->assemblies->first();
                                $assyPartNumber = $assembly->assy_part_number ?? '';
                                $assyIplNum = $assembly->assy_ipl_num ?? $assyIplNum;
                                $assemblyId = $assembly->id ?? $assemblyId;
                            }
                            if (!$assyPartNumber && !empty($component->assy_part_number)) {
                                $assyPartNumber = $component->assy_part_number;
                            }
                        @endphp
                        <tr class="lc-saved-row"
                            data-row-index="{{ $index }}"
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
                                       @readonly($logCardTdrReadOnly)
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
                                       @readonly($logCardTdrReadOnly)
                                       placeholder="{{ __('ASSY Serial Number') }}">
                            </td>
                            <td>
                                <select class="form-control form-control-sm lc-inline-input lc-saved-field" name="reason" @disabled($logCardTdrReadOnly)>
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
                                       @readonly($logCardTdrReadOnly)
                                       placeholder="{{ __('New Serial Number') }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
