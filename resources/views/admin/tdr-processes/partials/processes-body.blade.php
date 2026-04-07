{{-- Partial: Part Processes body (table + modals) for modal --}}
{{-- Requires: $current_tdr, $current_wo, $tdrProcesses, $proces, $vendors, $ecEligibleProcessNameIds --}}
@php
    $ecProcessNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
    $comp = $current_tdr->component;
    $tdrScopeProcesses = $tdrProcesses->where('tdrs_id', $current_tdr->id);
    $hasTravelerBlock = $tdrScopeProcesses->contains(fn ($p) => (bool) $p->in_traveler);
    $travelerAnchorProcessId = null;
    if ($hasTravelerBlock) {
        $travelerAnchorProcessId = $tdrScopeProcesses->filter(fn ($p) => (bool) $p->in_traveler)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->first()
            ?->id;
    }
@endphp

<div class="processes-modal-body" data-tdr-id="{{ $current_tdr->id }}"
     data-wo-number="{{ $current_tdr->workorder->number ?? '' }}"
     data-component-name="{{ $comp->name ?? 'N/A' }}"
     data-component-ipl="{{ $comp->ipl_num ?? 'N/A' }}"
     data-component-pn="{{ $comp->part_number ?? 'N/A' }}"
     data-serial-number="{{ $current_tdr->serial_number ?? 'N/A' }}"
     data-traveler-block="{{ $hasTravelerBlock ? '1' : '0' }}"
     data-traveler-group-url="{{ route('tdr-processes.traveler-group', ['tdrId' => $current_tdr->id]) }}"
     data-traveler-ungroup-url="{{ route('tdr-processes.traveler-ungroup', ['tdrId' => $current_tdr->id]) }}">
    @if($hasTravelerBlock)
        <div class="traveler-toolbar mb-2">
            <button type="button" class="btn btn-sm btn-outline-warning" id="btnUngroupTraveler">{{ __('UnGroup') }}</button>
        </div>
    @else
        <div class="traveler-toolbar mb-2 d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCreateTraveler" disabled>{{ __('Create Traveler') }}</button>
            <small class="text-muted">{{ __('Select one or more processes using the checkboxes, then create a Traveler group.') }}</small>
        </div>
    @endif
    <div class="table-wrapper me-3" style="max-height: 55vh; overflow-y: auto; overflow-x: auto;">
        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient sortable-table dir-table">
            <thead>
            <tr>
                <th class="text-primary text-center" style="width: 10%">Process Name</th>
                <th class="text-primary text-center" style="width: 30%;">Process</th>
                <th class="text-primary text-center" style="width: 22%">Description</th>
                <th class="text-primary text-center" style="width: 8%">Notes</th>
                @if($hasTravelerBlock)
                    <th class="text-primary text-center" style="width: 7%"
                        title="{{ __('AT rows: include on Part Traveler print. Traveler block rows are always included.') }}">{{ __('PT print') }}</th>
                @endif
                <th class="text-primary text-center" style="width: 8%">{{ __('Traveler') }}</th>
                <th class="text-primary text-center" style="width: 11%">Action</th>
                <th class="text-primary text-center" style="width: 11%">Form</th>
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
                        $inTr = (bool) $processes->in_traveler;
                        $trClass = $inTr ? ' table-secondary traveler-locked' : '';
                    @endphp

                    @if(!$processes->processName) @continue @endif

                    @if($isEc)
                        <tr data-id="{{ $processes->id }}" class="{{ trim($trClass) }}">
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
                            @if($hasTravelerBlock)
                                @include('admin.tdr-processes.partials.processes-body-omit-travel-form-cell', ['processes' => $processes, 'inTr' => $inTr])
                            @endif
                            <td class="text-center align-middle">
                                @if(!$hasTravelerBlock && !$inTr)
                                    <input type="checkbox" class="form-check-input traveler-select-cb" data-tdr-process-id="{{ $processes->id }}">
                                @endif
                            </td>
                            <td class="text-center">
                                @if($inTr)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled title="{{ __('UnGroup Traveler to edit') }}"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled title="{{ __('UnGroup Traveler to delete') }}"><i class="bi bi-trash"></i></button>
                                @elseif($isEcEditable)
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
                            <td class="text-center">
                                @if($inTr && (int) $processes->id === (int) $travelerAnchorProcessId)
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                        <input type="text" class="form-control form-control-sm travel-repair-num" style="width:108px" placeholder="{{ __('Rep.#') }}" maxlength="64">
                                        <select class="form-select form-select-sm travel-vendor-select" style="max-width:100px">
                                            <option value="">{{ __('Vendor') }}</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Traveler') }}</a>
                                    </div>
                                @elseif($inTr)
                                    <span class="text-muted">—</span>
                                @else
                                    <div class="d-flex gap-2 justify-content-center"></div>
                                @endif
                            </td>
                        </tr>
                    @elseif($isNdtWithPlus)
                        <tr data-id="{{ $processes->id }}" class="{{ trim($trClass) }}">
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
                            @if($hasTravelerBlock)
                                @include('admin.tdr-processes.partials.processes-body-omit-travel-form-cell', ['processes' => $processes, 'inTr' => $inTr])
                            @endif
                            <td class="text-center align-middle">
                                @if(!$hasTravelerBlock && !$inTr)
                                    <input type="checkbox" class="form-check-input traveler-select-cb" data-tdr-process-id="{{ $processes->id }}">
                                @endif
                            </td>
                            <td class="text-center">
                                @if($inTr)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($inTr && (int) $processes->id === (int) $travelerAnchorProcessId)
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                        <input type="text" class="form-control form-control-sm travel-repair-num" style="width:108px" placeholder="{{ __('Rep.#') }}" maxlength="64">
                                        <select class="form-select form-select-sm travel-vendor-select" style="max-width:100px">
                                            <option value="">{{ __('Vendor') }}</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Traveler') }}</a>
                                    </div>
                                @elseif($inTr)
                                    <span class="text-muted">—</span>
                                @else
                                    <div class="d-flex gap-2 justify-content-center">
                                        <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $processes->id }}">
                                            <option value="">Select Vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id]) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $processes->id }}" target="_blank">{{ __('Form') }}</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @else
                        @if(is_array($processData) && !empty($processData))
                            @foreach($processData as $process)
                                <tr data-id="{{ $processes->id }}" class="{{ trim($trClass) }}">
                                    <td class="text-center">{{ $processName }}</td>
                                    <td class="ps-2">
                                        @php $proc = $proces->firstWhere('id', $process); @endphp
                                        @if($proc){{ $proc->process }}@if($processes->ec) ( EC ) @endif @endif
                                    </td>
                                    <td class="text-center">{{ $processes->description ?? '' }}</td>
                                    <td class="text-center">{{ $processes->notes ?? '' }}</td>
                                    @if($hasTravelerBlock)
                                        @include('admin.tdr-processes.partials.processes-body-omit-travel-form-cell', ['processes' => $processes, 'inTr' => $inTr, 'showOmitCheckbox' => $loop->first])
                                    @endif
                                    <td class="text-center align-middle">
                                        @if(!$hasTravelerBlock && !$inTr && $loop->first)
                                            <input type="checkbox" class="form-check-input traveler-select-cb" data-tdr-process-id="{{ $processes->id }}">
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inTr)
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                        @else
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}" data-process="{{ $process }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inTr && (int) $processes->id === (int) $travelerAnchorProcessId && $loop->first)
                                            <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                                <input type="text" class="form-control form-control-sm travel-repair-num" style="width:108px" placeholder="{{ __('Rep.#') }}" maxlength="64">
                                                <select class="form-select form-select-sm travel-vendor-select" style="max-width:100px">
                                                    <option value="">{{ __('Vendor') }}</option>
                                                    @foreach($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                                <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Traveler') }}</a>
                                            </div>
                                        @elseif($inTr)
                                            <span class="text-muted">—</span>
                                        @else
                                            <div class="d-flex gap-2 justify-content-center">
                                                <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $processes->id }}" data-process="{{ $process }}">
                                                    <option value="">Select Vendor</option>
                                                    @foreach($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                                <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id, 'process_id' => $process]) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $processes->id }}" data-process="{{ $process }}" target="_blank">{{ __('Form') }}</a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr data-id="{{ $processes->id }}" class="{{ trim($trClass) }}">
                                <td class="text-center">{{ $processName }}</td>
                                <td class="ps-2 text-muted">—</td>
                                <td class="text-center">{{ $processes->description ?? '' }}</td>
                                <td class="text-center">{{ $processes->notes ?? '' }}</td>
                                @if($hasTravelerBlock)
                                    @include('admin.tdr-processes.partials.processes-body-omit-travel-form-cell', ['processes' => $processes, 'inTr' => $inTr])
                                @endif
                                <td class="text-center align-middle">
                                    @if(!$hasTravelerBlock && !$inTr)
                                        <input type="checkbox" class="form-check-input traveler-select-cb" data-tdr-process-id="{{ $processes->id }}">
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($inTr)
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($inTr && (int) $processes->id === (int) $travelerAnchorProcessId)
                                        <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                            <input type="text" class="form-control form-control-sm travel-repair-num" style="width:108px" placeholder="{{ __('Rep.#') }}" maxlength="64">
                                            <select class="form-select form-select-sm travel-vendor-select" style="max-width:100px">
                                                <option value="">{{ __('Vendor') }}</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Traveler') }}</a>
                                        </div>
                                    @elseif($inTr)
                                        <span class="text-muted">—</span>
                                    @else
                                        <div class="d-flex gap-2 justify-content-center">
                                            <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $processes->id }}">
                                                <option value="">Select Vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('tdr-processes.show', ['tdr_process' => $processes->id]) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $processes->id }}" target="_blank">{{ __('Form') }}</a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
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
