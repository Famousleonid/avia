{{-- Partial: Part Processes body (table + modals) for modal --}}
{{-- Requires: $current_tdr, $current_wo, $tdrProcesses, $proces, $vendors, $ecEligibleProcessNameIds --}}
@php
    $ecProcessNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
    $comp = $current_tdr->component;
    $tdrScopeProcesses = $tdrProcesses->where('tdrs_id', $current_tdr->id);
    $hasTravelerBlock = $tdrScopeProcesses->contains(fn ($p) => (bool) $p->in_traveler);

    $travelerVisualRowCount = 0;
    foreach ($tdrProcesses as $_tp) {
        if ((int) $_tp->tdrs_id !== (int) $current_tdr->id || ! $_tp->processName) {
            continue;
        }
        $_processData = json_decode($_tp->processes, true) ?: [];
        $_processName = $_tp->processName->name;
        $_isEc = ($ecProcessNameId !== null && (int) $_tp->process_names_id === (int) $ecProcessNameId);
        $_isNdtWithPlus = strpos($_processName, 'NDT-') === 0 && ! empty($_tp->plus_process);
        if ($_isEc) {
            if ((bool) $_tp->in_traveler) {
                $travelerVisualRowCount++;
            }
            continue;
        }
        if ($_isNdtWithPlus) {
            if ((bool) $_tp->in_traveler) {
                $travelerVisualRowCount++;
            }
            continue;
        }
        if (is_array($_processData) && $_processData !== []) {
            foreach ($_processData as $_) {
                if ((bool) $_tp->in_traveler) {
                    $travelerVisualRowCount++;
                }
            }
            continue;
        }
        if ((bool) $_tp->in_traveler) {
            $travelerVisualRowCount++;
        }
    }
    $travelerFormMergedDone = false;
    $hasGroupProcessForms = collect($processGroups ?? [])->contains(function ($g) {
        return (int) ($g['count'] ?? 0) > 1;
    });
@endphp

<div class="processes-modal-body" data-tdr-id="{{ $current_tdr->id }}"
     data-wo-number="{{ $current_tdr->workorder->number ?? '' }}"
     data-component-name="{{ $comp->name ?? 'N/A' }}"
     data-component-ipl="{{ $comp->ipl_num ?? 'N/A' }}"
     data-component-pn="{{ $comp->part_number ?? 'N/A' }}"
     data-serial-number="{{ $current_tdr->serial_number ?? 'N/A' }}"
     data-traveler-block="{{ $hasTravelerBlock ? '1' : '0' }}"
     data-group-process-forms="{{ $hasGroupProcessForms ? '1' : '0' }}"
     data-traveler-group-url="{{ route('tdr-processes.traveler-group', ['tdrId' => $current_tdr->id]) }}"
     data-traveler-ungroup-url="{{ route('tdr-processes.traveler-ungroup', ['tdrId' => $current_tdr->id]) }}">
    <div class="table-wrapper me-3" style="max-height: 55vh; overflow-y: auto; overflow-x: auto;">
        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient dir-table">
            <thead>
            <tr>
                <th class="text-primary text-center" style="width: 10%">Process Name</th>
                <th class="text-primary text-center" style="width: 30%;">Process</th>
                <th class="text-primary text-center" style="width: 22%">Description</th>
                <th class="text-primary text-center" style="width: 8%">Traveler Notes</th>
                @if($hasTravelerBlock)
                    <th class="text-primary text-center" style="width: 7%"
                        title="{{ __('AT rows: include on Part Traveler print. Traveler block rows are always included.') }}">{{ __('PT print') }}</th>
                @endif
                <th class="text-primary text-center align-middle" style="width: 10%">
                    <div class="d-flex flex-column align-items-center gap-1 py-1">
                        <span class="d-block">{{ __('Traveler') }}</span>
                        @if($hasTravelerBlock)
                            <button type="button" class="btn btn-sm btn-outline-warning text-nowrap" id="btnUngroupTraveler">{{ __('UnGroup') }}</button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="btnCreateTraveler" disabled>{{ __('Create') }}</button>
                        @endif
                    </div>
                </th>
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
                        /* без table-secondary — иначе Bootstrap даёт свой бледный hover */
                        $trClass = $inTr ? ' traveler-block-row traveler-locked' : '';
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
                            @if($inTr)
                                @if(! $travelerFormMergedDone)
                                    @php $travelerFormMergedDone = true; @endphp
                                    <td rowspan="{{ max(1, $travelerVisualRowCount) }}" class="text-center align-middle p-2 traveler-merged-form-cell">
                                        @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                    </td>
                                @endif
                            @else
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center"></div>
                            </td>
                            @endif
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
                            @if($inTr)
                                @if(! $travelerFormMergedDone)
                                    @php $travelerFormMergedDone = true; @endphp
                                    <td rowspan="{{ max(1, $travelerVisualRowCount) }}" class="text-center align-middle p-2 traveler-merged-form-cell">
                                        @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                    </td>
                                @endif
                            @else
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
                            @endif
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
                                    @if($inTr)
                                        @if(! $travelerFormMergedDone)
                                            @php $travelerFormMergedDone = true; @endphp
                                            <td rowspan="{{ max(1, $travelerVisualRowCount) }}" class="text-center align-middle p-2 traveler-merged-form-cell">
                                                @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                            </td>
                                        @endif
                                    @else
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
                                    @endif
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
                                @if($inTr)
                                    @if(! $travelerFormMergedDone)
                                        @php $travelerFormMergedDone = true; @endphp
                                        <td rowspan="{{ max(1, $travelerVisualRowCount) }}" class="text-center align-middle p-2 traveler-merged-form-cell">
                                            @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                        </td>
                                    @endif
                                @else
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
                                @endif
                            </tr>
                        @endif
                    @endif
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('admin.tdr-processes.partials.part-processes-group-forms-modal')

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
