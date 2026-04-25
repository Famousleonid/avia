{{-- Тело таблицы модалки Group Process Forms (All Parts Processes). Колонка Parts — только детали с чекбоксами. --}}
@foreach($processGroups as $group)
    @php
        $rowUid = $group['row_uid'];
        $actualProcessNameId = $group['representative_process_name_id'];
        $displayName = $group['display_name'] ?? ($group['process_name']->name ?? 'N/A');
        $rowKind = $group['row_kind'] ?? 'standard';
    @endphp
    <tr class="all-parts-group-form-row"
        data-group-form-row="{{ $rowUid }}"
        data-process-name-id="{{ $actualProcessNameId }}"
        data-row-kind="{{ $rowKind }}">
        <td class="align-middle">
            <div class="position-relative d-inline-block ms-5">
                <x-paper-button text="{{ $displayName }}" size="landscape" width="120px"
                    href="{{ route('tdrs.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $actualProcessNameId]) }}"
                    target="_blank" class="group-form-button"
                    data-process-name-id="{{ $actualProcessNameId }}"
                    data-group-form-row="{{ $rowUid }}" />
                <span class="badge bg-success mt-1 ms-1 process-qty-badge"
                    data-group-form-row="{{ $rowUid }}"
                    data-position-unit="{{ __('pos.') }}"
                    style="position: absolute; top: -5px; left: 5px; min-width: 20px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                    {{ $group['position_count'] ?? $group['qty'] }} {{ __('pos.') }}</span>
            </div>
        </td>
        <td class="align-middle">
            <div class="component-checkboxes" data-group-form-row="{{ $rowUid }}">
                @foreach($group['components'] ?? [] as $componentKey => $component)
                    <div class="form-check">
                        <input class="form-check-input component-checkbox" type="checkbox"
                            value="{{ ($component['ipl_num'] ?? '') . '_' . ($component['part_number'] ?? '') . '_' . ($component['serial_number'] ?? '') }}"
                            data-component-id="{{ $component['id'] }}"
                            data-ipl-num="{{ $component['ipl_num'] ?? '' }}"
                            data-part-number="{{ $component['part_number'] ?? '' }}"
                            data-serial-number="{{ $component['serial_number'] ?? '' }}"
                            id="allParts_c_{{ $rowUid }}_{{ $componentKey }}"
                            data-process-name-id="{{ $actualProcessNameId }}"
                            data-group-form-row="{{ $rowUid }}"
                            data-order-qty="{{ (int) ($component['qty'] ?? 1) }}"
                            checked>
                        <label class="form-check-label" for="allParts_c_{{ $rowUid }}_{{ $componentKey }}">
                            <strong>{{ $component['ipl_num'] ?? '' }}</strong> — {{ Str::limit($component['name'] ?? '', 40) }}
                            @if(!empty($component['serial_number']))
                                <span class="text-muted">(SN: {{ $component['serial_number'] }})</span>
                            @endif
                            <span>{{ __('Qty') }}: {{ (int) ($component['qty'] ?? 1) }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </td>
        <td class="align-middle">
            <select class="form-select vendor-select" data-group-form-row="{{ $rowUid }}"
                data-process-name-id="{{ $actualProcessNameId }}" style="font-size: 0.9rem;">
                <option value="">{{ __('No vendor') }}</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </td>
    </tr>
@endforeach
