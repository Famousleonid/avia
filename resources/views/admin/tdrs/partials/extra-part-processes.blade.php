<style>
    .extra-parts-table-wrapper { height: calc(100vh - 300px); overflow-y: auto; overflow-x: hidden; max-width: 1080px; margin-left: auto; margin-right: auto; }
    .extra-parts-table-wrapper .table th, .extra-parts-table-wrapper .table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 80px; max-width: 500px; }
    .extra-parts-table-wrapper .table thead th { position: sticky; top: -1px; z-index: 10; background: inherit; }
    .extra-part-processes .vendor-select { transition: all 0.3s ease; }
    .extra-part-processes .vendor-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
    .extra-part-processes .component-checkboxes { max-height: 200px; overflow-y: auto; padding: 0.5rem; color: inherit; }
    .extra-part-processes .component-checkbox:checked + .form-check-label { font-weight: 500; color: inherit; }
    .process-text-long { font-size: 0.90rem; line-height: 0.9; letter-spacing: -0.3px; transform: scale(0.9); transform-origin: left; margin-top: 5px; }
</style>

<div class="extra-part-processes" data-has-records="{{ (isset($extra_components) && count($extra_components) > 0) ? '1' : '0' }}" data-extra-process-count="{{ isset($extra_components) ? count($extra_components) : 0 }}">
    <div class="table-wrapper extra-parts-table-wrapper">
        <table class="table table-hover align-middle table-bordered bg-gradient">
            <thead>
                <tr>
                    <th class="text-primary text-center" style="width: 8%">IPL</th>
                    <th class="text-primary text-center" style="width: 15%">Name</th>
                    <th class="text-primary text-center" style="width: 5%">QTY</th>
                    <th class="text-primary text-center" style="width: 50%">Processes</th>
                    <th class="text-primary text-center" style="width: 18%">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($extra_components as $extra_component)
                    <tr>
                        <td class="text-center align-content-center">{{ $extra_component->component ? $extra_component->component->ipl_num : 'N/A' }}</td>
                        <td class="text-center align-content-center">{{ $extra_component->component ? $extra_component->component->name : 'N/A' }}</td>
                        <td class="text-center align-content-center">{{ $extra_component->component ? $extra_component->qty : 'N/A' }}</td>
                        <td class="ps-2">
                            @if($extra_component->processes)
                                @if(is_array($extra_component->processes) && array_keys($extra_component->processes) !== range(0, count($extra_component->processes) - 1))
                                    @foreach($extra_component->processes as $processNameId => $processId)
                                        @php $processName = \App\Models\ProcessName::find($processNameId); $process = \App\Models\Process::find($processId); @endphp
                                        @if($processName && $process)
                                            <div class="mb-1"><strong>{{ $processName->name }}:</strong>
                                                <span class="me-1 @if(strlen($process->process) > 40) process-text-long @endif">{{ $process->process }}</span></div>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach($extra_component->processes as $processItem)
                                        @php
                                            $processName = \App\Models\ProcessName::find($processItem['process_name_id']);
                                            $process = \App\Models\Process::find($processItem['process_id']);
                                            $isCombinedNdt = false;
                                            $combinedProcessNames = [];
                                            $combinedProcessDescriptions = [];
                                            if ($processName && strpos($processName->name, 'NDT-') === 0 && isset($processItem['plus_process_names']) && !empty($processItem['plus_process_names'])) {
                                                $isCombinedNdt = true;
                                                $combinedProcessNames[] = $processName->name;
                                                $combinedProcessDescriptions[] = $process->process ?? '';
                                                foreach ($processItem['plus_process_names'] as $plusIndex => $plusProcessNameId) {
                                                    $plusProcessName = \App\Models\ProcessName::find($plusProcessNameId);
                                                    if ($plusProcessName) $combinedProcessNames[] = $plusProcessName->name;
                                                    if (isset($processItem['plus_process_ids'][$plusIndex])) {
                                                        $plusProcess = \App\Models\Process::find($processItem['plus_process_ids'][$plusIndex]);
                                                        if ($plusProcess) $combinedProcessDescriptions[] = $plusProcess->process;
                                                    }
                                                }
                                                usort($combinedProcessNames, function($a, $b) { return ((int)substr($a, -1)) <=> ((int)substr($b, -1)); });
                                            }
                                        @endphp
                                        @if($processName && $process)
                                            <div class="mb-2"><strong>@if($isCombinedNdt){{ implode(' / ', $combinedProcessNames) }}@else{{ $processName->name }}@endif:</strong>
                                                <span class="ms-2 @if(strlen($process->process) > 40) process-text-long @endif">@if($isCombinedNdt){{ implode(' / ', $combinedProcessDescriptions) }}@else{{ $process->process }}@endif</span></div>
                                        @endif
                                    @endforeach
                                @endif
                            @else
                                <span class="text-muted">{{ __('No processes defined') }}</span>
                            @endif
                        </td>
                        <td class="text-center align-content-center">
                            @if($extra_component->component)
                                <div class="d-flex gap-1 justify-content-center flex-wrap">
                                    <button type="button" class="btn btn-outline-warning btn-sm open-edit-extra-process-modal" data-extra-process-id="{{ $extra_component->id }}" title="{{ __('Edit Component') }}">{{ __('Edit') }}</button>
                                    <button type="button" class="btn btn-outline-success btn-sm open-add-extra-process-modal" data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $extra_component->component->id }}" title="{{ __('Add Processes') }}">{{ __('Add') }}</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm open-extra-processes-tab" data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $extra_component->component->id }}" title="{{ __('All Processes for this Components') }}">{{ __('Processes') }}</button>
                                </div>
                            @else
                                <span class="text-muted">{{ __('Component not found') }} (ID: {{ $extra_component->component_id }})</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No extra part processes.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($processGroups) && count($processGroups) > 0)
        <div class="modal fade" id="extraGroupFormsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-print"></i> {{ __('Group Process Forms') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3"><i class="fas fa-info-circle"></i> {{ __('Select a process type to generate a grouped form with all components that have the same process.') }}</p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered bg-gradient shadow">
                                <thead>
                                    <tr>
                                        <th class="text-primary text-center" style="width: 25%;">{{ __('Process') }}</th>
                                        <th class="text-primary text-center" style="width: 25%;">{{ __('Components') }}</th>
                                        <th class="text-primary text-center" style="width: 25%;">{{ __('Vendor') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($processGroups as $groupKey => $group)
                                        @php
                                            $actualProcessNameId = ($groupKey == 'NDT_GROUP') ? $group['process_name']->id : $groupKey;
                                            $displayName = ($groupKey == 'NDT_GROUP') ? 'NDT' : $group['process_name']->name;
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <div class="position-relative d-inline-block ms-5">
                                                    <x-paper-button text="{{ $displayName }}" size="landscape" width="120px"
                                                        href="{{ route('extra_processes.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $actualProcessNameId]) }}"
                                                        target="_blank" class="group-form-button" data-process-name-id="{{ $actualProcessNameId }}" />
                                                    <span class="badge bg-success mt-1 ms-1 process-qty-badge" data-process-name-id="{{ $actualProcessNameId }}"
                                                        style="position: absolute; top: -5px; left: 5px; min-width: 20px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">{{ $group['qty'] }} pcs</span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="component-checkboxes" data-process-name-id="{{ $actualProcessNameId }}">
                                                    @foreach($group['components'] as $component)
                                                        <div class="form-check">
                                                            <input class="form-check-input component-checkbox" type="checkbox" value="{{ $component['id'] }}"
                                                                id="extra_component_{{ $actualProcessNameId }}_{{ $component['id'] }}"
                                                                data-process-name-id="{{ $actualProcessNameId }}" data-qty="{{ $component['qty'] }}"
                                                                {{ $group['count'] <= 1 ? 'disabled' : '' }} {{ $group['count'] > 1 ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="extra_component_{{ $actualProcessNameId }}_{{ $component['id'] }}">
                                                                <strong>{{ $component['ipl_num'] }}</strong> - {{ Str::limit($component['name'], 40) }} <span>Qty: {{ $component['qty'] }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <select class="form-select vendor-select" data-process-name-id="{{ $actualProcessNameId }}" style="font-size: 0.9rem;">
                                                    <option value="">{{ __('No vendor') }}</option>
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
