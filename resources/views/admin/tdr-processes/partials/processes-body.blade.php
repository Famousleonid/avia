{{-- Partial: Part Processes body (table + modals) for modal --}}
{{-- Requires: $current_tdr, $current_wo, $tdrProcesses, $proces, $vendors, $ecEligibleProcessNameIds --}}
@php
    $ecProcessNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
    $comp = $current_tdr->component;
@endphp

<div class="processes-modal-body" data-tdr-id="{{ $current_tdr->id }}"
     data-wo-number="{{ $current_tdr->workorder->number ?? '' }}"
     data-component-name="{{ $comp->name ?? 'N/A' }}"
     data-component-ipl="{{ $comp->ipl_num ?? 'N/A' }}"
     data-component-pn="{{ $comp->part_number ?? 'N/A' }}"
     data-serial-number="{{ $current_tdr->serial_number ?? 'N/A' }}">
    <div class="table-wrapper me-3" style="max-height: 55vh; overflow-y: auto; overflow-x: auto;">
        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient sortable-table dir-table">
            <thead>
            <tr>
                <th class="text-primary text-center" style="width: 10%">Process Name</th>
                <th class="text-primary text-center" style="width: 35%;">Process</th>
                <th class="text-primary text-center" style="width: 25%">Description</th>
                <th class="text-primary text-center" style="width: 10%">Notes</th>
                <th class="text-primary text-center" style="width: 10%">Action</th>
                <th class="text-primary text-center" style="width: 10%">Form</th>
            </tr>
            </thead>
            <tbody id="sortable-tbody">
            @foreach($tdrProcesses as $processes)
                @if($processes->tdrs_id == $current_tdr->id)
                    @php
                        $processData = json_decode($processes->processes, true) ?: [];
                        $processName = $processes->processName ? $processes->processName->name : 'N/A';
                        $isEc = ($ecProcessNameId !== null && (int)$processes->process_names_id === (int)$ecProcessNameId);
                        $hasMachiningOrRil = $tdrProcesses->contains(fn($p) => in_array((int)$p->process_names_id, $ecEligibleProcessNameIds ?? []));
                        $isEcEditable = $isEc && ($tdrProcesses->count() === 1 || !$hasMachiningOrRil);
                        $isNdtWithPlus = false;
                        $combinedProcessNames = [];
                        if (strpos($processName, 'NDT-') === 0 && !empty($processes->plus_process)) {
                            $isNdtWithPlus = true;
                            $combinedProcessNames[] = $processName;
                            foreach (explode(',', $processes->plus_process) as $plusProcessId) {
                                $plusProcessName = \App\Models\ProcessName::find(trim($plusProcessId));
                                if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                                    $combinedProcessNames[] = $plusProcessName->name;
                                }
                            }
                        }
                    @endphp

                    @if(!$processes->processName) @continue @endif

                    @if($isEc)
                        <tr data-id="{{ $processes->id }}">
                            <td class="text-center">{{ $processName }}</td>
                            <td class="ps-2">
                                @php
                                    $processNames = [];
                                    if (is_array($processData) && !empty($processData)) {
                                        foreach($processData as $processId) {
                                            $proc = $proces->firstWhere('id', $processId);
                                            if ($proc) $processNames[] = $proc->process;
                                        }
                                    }
                                @endphp
                                {{ !empty($processNames) ? implode(', ', $processNames) : 'No processes' }}
                            </td>
                            <td class="text-center">{{ $processes->description ?? '' }}</td>
                            <td class="text-center">{{ $processes->notes ?? '' }}</td>
                            <td class="text-center">
                                @if($isEcEditable)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}" title="{{ __('Process Edit') }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}" title="{{ __('Process Delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled"><i class="bi bi-trash"></i></button>
                                @endif
                            </td>
                            <td class="text-center"><div class="d-flex gap-2 justify-content-center"></div></td>
                        </tr>
                    @elseif($isNdtWithPlus)
                        <tr data-id="{{ $processes->id }}">
                            <td class="text-center">{{ implode(' / ', $combinedProcessNames) }}</td>
                            <td class="ps-2">
                                @php
                                    $allProcesses = [];
                                    if (is_array($processData) && !empty($processData)) {
                                        foreach($processData as $processId) {
                                            $proc = $proces->firstWhere('id', $processId);
                                            if ($proc) $allProcesses[] = $proc->process;
                                        }
                                    }
                                @endphp
                                {{ !empty($allProcesses) ? implode(' / ', $allProcesses) : 'No processes' }}@if($processes->ec) ( EC ) @endif
                            </td>
                            <td class="text-center">{{ $processes->description ?? '' }}</td>
                            <td class="text-center">{{ $processes->notes ?? '' }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $processes->id }}">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id]) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $processes->id }}" target="_blank">{{ __('Form') }}</a>
                                </div>
                            </td>
                        </tr>
                    @else
                        @if(is_array($processData) && !empty($processData))
                            @foreach($processData as $process)
                                <tr data-id="{{ $processes->id }}">
                                    <td class="text-center">{{ $processName }}</td>
                                    <td class="ps-2">
                                        @php $proc = $proces->firstWhere('id', $process); @endphp
                                        @if($proc){{ $proc->process }}@if($processes->ec) ( EC ) @endif @endif
                                    </td>
                                    <td class="text-center">{{ $processes->description ?? '' }}</td>
                                    <td class="text-center">{{ $processes->notes ?? '' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}" data-process="{{ $process }}"><i class="bi bi-trash"></i></button>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $processes->id }}" data-process="{{ $process }}">
                                                <option value="">Select Vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id, 'process_id' => $process]) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $processes->id }}" data-process="{{ $process }}" target="_blank">{{ __('Form') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endif
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Add Vendor Modal --}}
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVendorModalLabel">{{ __('Add New Vendor') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addVendorForm">
                    @csrf
                    <div class="mb-3">
                        <label for="vendorName" class="form-label">{{ __('Vendor Name') }}</label>
                        <input type="text" class="form-control" id="vendorName" name="name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveVendorButton">{{ __('Save Vendor') }}</button>
            </div>
        </div>
    </div>
</div>
