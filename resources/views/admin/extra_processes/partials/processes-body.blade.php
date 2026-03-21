{{-- Partial: Extra Processes body for tab (workorderId, componentId) --}}
@php
    $comp = $component ?? ($extra_process ? $extra_process->component : null);
@endphp
<div class="extra-processes-modal-body" data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $comp->id ?? '' }}"
     data-wo-number="{{ $current_wo->number ?? '' }}"
     data-component-name="{{ $comp->name ?? 'N/A' }}"
     data-component-ipl="{{ $comp->ipl_num ?? 'N/A' }}"
     data-component-pn="{{ $comp->part_number ?? 'N/A' }}">
    <div class="card-header d-flex flex-wrap align-items-center gap-2 mb-2">
        <div>
            <h6 class="mb-0">{{ __('Component Extra Processes') }}, {{ __('WO') }}: <span class="text-primary">{{ $current_wo->number }}</span></h6>
            @if($comp)
                <small class="text-muted">{{ __('Part') }}: {{ $comp->name }} | IPL: {{ $comp->ipl_num }} | PN: {{ $comp->part_number }}</small>
            @endif
        </div>
        <div class="d-flex gap-2 ms-md-auto">
            @if($comp)
            <a href="{{ route('extra_processes.create_processes', ['workorderId' => $current_wo->id, 'componentId' => $comp->id]) }}?modal=1"
               class="btn btn-outline-success btn-sm open-add-extra-process-modal" data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $comp->id }}">
                <i class="fas fa-plus"></i> {{ __('Add Process') }}
            </a>
            @endif
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVendorModalExtra">
                <i class="fas fa-plus"></i> {{ __('Add Vendor') }}
            </button>
        </div>
    </div>
    <div class="table-wrapper" style="max-height: 55vh; overflow-y: auto; overflow-x: auto;">
        @if($extra_process && $extra_process->processes)
            <table class="table table-sm table-hover align-middle table-bordered bg-gradient dir-table sortable-table">
                <thead>
                <tr>
                    <th class="text-primary text-center">Process Name</th>
                    <th class="text-primary text-center" style="width: 350px;">Process</th>
                    <th class="text-primary text-center">Description</th>
                    <th class="text-primary text-center">Notes</th>
                    <th class="text-primary text-center">Action</th>
                    <th class="text-primary text-center">Form</th>
                </tr>
                </thead>
                <tbody id="sortable-tbody">
                @if(is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1))
                    @foreach($extra_process->processes as $processNameId => $processId)
                        @php $processName = \App\Models\ProcessName::find($processNameId); $process = \App\Models\Process::find($processId); @endphp
                        @if($processName && $process)
                            <tr data-id="{{ $extra_process->id }}">
                                <td class="text-center">{{ $processName->name }}</td>
                                <td class="ps-2">{{ $process->process }}</td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-extra-process-process"
                                        data-extra-process-id="{{ $extra_process->id }}" data-process-name-id="{{ $processNameId }}"
                                        data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $extra_process->component_id }}"><i class="bi bi-pencil-square"></i></button>
                                    <form action="{{ route('extra_processes.destroy', ['extra_process' => $extra_process->id]) }}" method="POST" style="display:inline;" class="delete-process-form">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="process_name_id" value="{{ $processNameId }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <select class="form-select form-select-sm vendor-select" style="width: 95px" data-extra-process-id="{{ $extra_process->id }}" data-process-name-id="{{ $processNameId }}">
                                            <option value="">Vendor</option>
                                            @foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
                                        </select>
                                        <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processNameId]) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ __('Form') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    @foreach($extra_process->processes as $index => $processItem)
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
                                foreach ($processItem['plus_process_names'] as $pi => $pid) {
                                    $pn = \App\Models\ProcessName::find($pid);
                                    if ($pn) $combinedProcessNames[] = $pn->name;
                                    if (isset($processItem['plus_process_ids'][$pi])) {
                                        $pp = \App\Models\Process::find($processItem['plus_process_ids'][$pi]);
                                        if ($pp) $combinedProcessDescriptions[] = $pp->process;
                                    }
                                }
                                usort($combinedProcessNames, fn($a,$b) => ((int)substr($a,-1)) <=> ((int)substr($b,-1)));
                            }
                        @endphp
                        @if($processName && $process)
                            <tr data-id="{{ $extra_process->id }}" data-process-index="{{ $index }}">
                                <td class="text-center">@if($isCombinedNdt){{ implode(' / ', $combinedProcessNames) }}@else{{ $processName->name }}@endif</td>
                                <td class="ps-2">@if($isCombinedNdt){{ implode(' / ', $combinedProcessDescriptions) }}@else{{ $process->process }}@endif</td>
                                <td class="text-center">{{ $processItem['description'] ?? '' }}</td>
                                <td class="text-center">{{ $processItem['notes'] ?? '' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-extra-process-process"
                                        data-extra-process-id="{{ $extra_process->id }}" data-process-index="{{ $index }}"
                                        data-workorder-id="{{ $current_wo->id }}" data-component-id="{{ $extra_process->component_id }}"><i class="bi bi-pencil-square"></i></button>
                                    <form action="{{ route('extra_processes.destroy', ['extra_process' => $extra_process->id]) }}" method="POST" style="display:inline;" class="delete-process-form">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="process_index" value="{{ $index }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <select class="form-select form-select-sm vendor-select" style="width: 95px" data-extra-process-id="{{ $extra_process->id }}" data-process-name-id="{{ $processItem['process_name_id'] }}">
                                            <option value="">Vendor</option>
                                            @foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
                                        </select>
                                        <a href="{{ route('extra_processes.show_form', ['id' => $extra_process->id, 'processNameId' => $processItem['process_name_id']]) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ __('Form') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
                </tbody>
            </table>
        @else
            <div class="alert alert-info">
                <strong>{{ __('No processes defined for this component.') }}</strong><br>
                {{ __('Click "Add Process" to add processes to this component.') }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addVendorModalExtra" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add New Vendor') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addVendorFormExtra">
                    @csrf
                    <div class="mb-3">
                        <label for="vendorNameExtra" class="form-label">{{ __('Vendor Name') }}</label>
                        <input type="text" class="form-control" id="vendorNameExtra" name="name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveVendorButtonExtra">{{ __('Save Vendor') }}</button>
            </div>
        </div>
    </div>
</div>
