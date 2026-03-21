<style>
    .all-parts-table-wrapper { height: calc(100vh - 300px); overflow-y: auto; overflow-x: hidden; max-width: 960px; margin-left: auto; margin-right: auto; }
    .all-parts-table-wrapper .table th, .all-parts-table-wrapper .table td { white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; min-width: 80px; max-width: 500px;  justify-content: center}
    .all-parts-table-wrapper .table thead th { position: sticky; top: -1px; z-index: 10; background: inherit; }
    .all-parts-processes .vendor-select { transition: all 0.3s ease; }
    .all-parts-processes .vendor-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
    .all-parts-processes .component-checkboxes { max-height: 200px; overflow-y: auto; padding: 0.5rem; color: inherit; }
    .all-parts-processes .component-checkbox:checked + .form-check-label { font-weight: 500; color: inherit; }
</style>

<div class="all-parts-processes ">
    <div class="table-wrapper all-parts-table-wrapper ">
        <table class="table table-hover align-middle dir-table table-bordered bg-gradient">
            <thead>
                <tr>
                    <th class="text-primary text-center"  style="width: 10%">IPL</th>
                    <th class="text-primary text-center" style="width: 10%">Name</th>
                    <th class="text-primary text-center" style="width: 65%">Processes</th>
                    <th class="text-primary text-center" style="width: 15%">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tdrs as $tdr)
                    @if($tdr->use_process_forms)
                        <tr>
                            <td class="text-center">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#allPartsComponentModal{{ $tdr->component->id }}">
                                    {{ $tdr->component->ipl_num }}
                                </a>
                            </td>
                            <td class="text-center">{{ $tdr->component->name }}</td>
                            <td class="ms-1">
                                @php $componentProcesses = $tdrProcesses->where('tdrs_id', $tdr->id)->sortBy('sort_order'); @endphp
                                @foreach($componentProcesses as $processes)
                                    @php
                                        $processData = json_decode($processes->processes, true) ?: [];
                                        $processName = $processes->processName ? $processes->processName->name : 'N/A';
                                    @endphp
                                    @if(!$processes->processName) @continue @endif
                                    @if(is_array($processData) && !empty($processData))
                                        @foreach($processData as $processId)
                                            {{ $processName }} :
                                            @if(isset($proces[$processId]))
                                                {{ $proces[$processId]->process }}@if($processes->ec) ( EC )@endif<br>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-outline-success btn-sm open-add-process-modal"
                                        data-tdr-id="{{ $tdr->id }}">{{ __('Add') }}</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm open-part-processes-tab"
                                        data-tdr-id="{{ $tdr->id }}">{{ __('Processes') }}</button>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="allPartsComponentModal{{ $tdr->component->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-gradient">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('Work Order: ') }}{{ $current_wo->number }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <img src="{{ $tdr->component->getFirstMediaBigUrl('component') }}" width="200" alt="Image"/>
                                            </div>
                                            <div>
                                                <p><strong>{{ __('Part PN: ') }}</strong>{{ $tdr->component->part_number }}</p>
                                                <p><strong>{{ __('Part Name: ') }}</strong>{{ $tdr->component->name }}</p>
                                                <p><strong>{{ __('Part IPL: ') }}</strong>{{ $tdr->component->ipl_num }}</p>
                                                <p><strong>{{ __('Part SN: ') }}</strong>{{ $tdr->serial_number }}</p>
                                                @if($tdr->assy_serial_number)
                                                    <p><strong>{{ __('Part Assy SN: ') }}</strong>{{ $tdr->assy_serial_number }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    @if(isset($processGroups) && count($processGroups) > 0)
        <div class="modal fade" id="groupFormsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-print"></i> {{ __('Group Process Forms') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle"></i>
                            Select a process type to generate a grouped form with all components that have the same process.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered bg-gradient shadow dir-table">
                                <thead>
                                    <tr>
                                        <th class="text-primary text-center" style="width: 25%;">Process</th>
                                        <th class="text-primary text-center" style="width: 25%;">Parts</th>
                                        <th class="text-primary text-center" style="width: 25%;">Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($processGroups as $groupKey => $group)
                                        @php
                                            $actualProcessNameId = ($groupKey == 'NDT_GROUP') ? ($group['process_name']->id ?? $groupKey) : $groupKey;
                                            $displayName = ($groupKey == 'NDT_GROUP') ? 'NDT' : ($group['process_name'] ? $group['process_name']->name : 'N/A');
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <div class="position-relative d-inline-block ms-5">
                                                    <x-paper-button text="{{ $displayName }}" size="landscape" width="120px"
                                                        href="{{ route('tdrs.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $actualProcessNameId]) }}"
                                                        target="_blank" class="group-form-button"
                                                        data-process-name-id="{{ $actualProcessNameId }}" />
                                                    <span class="badge bg-success mt-1 ms-1 process-qty-badge"
                                                        data-process-name-id="{{ $actualProcessNameId }}"
                                                        style="position: absolute; top: -5px; left: 5px; min-width: 20px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                                                        {{ $group['qty'] }} pcs</span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="component-checkboxes" data-process-name-id="{{ $actualProcessNameId }}">
                                                    @foreach($group['components'] as $componentKey => $component)
                                                        <div class="form-check">
                                                            <input class="form-check-input component-checkbox" type="checkbox"
                                                                value="{{ ($component['ipl_num'] ?? '') . '_' . ($component['part_number'] ?? '') . '_' . ($component['serial_number'] ?? '') }}"
                                                                data-component-id="{{ $component['id'] }}"
                                                                data-ipl-num="{{ $component['ipl_num'] ?? '' }}"
                                                                data-part-number="{{ $component['part_number'] ?? '' }}"
                                                                data-serial-number="{{ $component['serial_number'] ?? '' }}"
                                                                id="allParts_component_{{ $actualProcessNameId }}_{{ $componentKey }}"
                                                                data-process-name-id="{{ $actualProcessNameId }}"
                                                                data-qty="{{ $component['qty'] }}"
                                                                {{ $group['count'] <= 1 ? 'disabled' : 'checked' }}>
                                                            <label class="form-check-label" for="allParts_component_{{ $actualProcessNameId }}_{{ $componentKey }}">
                                                                <strong>{{ $component['ipl_num'] }}</strong> - {{ Str::limit($component['name'], 40) }}
                                                                @if(isset($component['serial_number']) && $component['serial_number'])
                                                                    <span class="text-muted">(SN: {{ $component['serial_number'] }})</span>
                                                                @endif
                                                                <span>Qty: {{ $component['qty'] }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <select class="form-select vendor-select" data-process-name-id="{{ $actualProcessNameId }}" style="font-size: 0.9rem;">
                                                    <option value="">No vendor</option>
                                                    @foreach($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
