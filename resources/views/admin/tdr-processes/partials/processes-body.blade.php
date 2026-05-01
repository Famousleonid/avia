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
        $_processData = \App\Models\TdrProcess::normalizeStoredProcessIds($_tp->processes);
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
    $travelerFormRendered = [];
    $travelerCheckboxRendered = [];
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
    <div class="processes-toolbar"></div>
    <div class="table-wrapper me-3" style="max-height: 55vh; overflow-y: auto; overflow-x: auto;">
        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient dir-table sortable-table">
            <thead>
            <tr>
                <th class="text-primary text-center" style="width: 12%">Process Name</th>
                <th class="text-primary text-center" style="width: 34%;">Process</th>
                <th class="text-primary text-center" style="width: 26%">Description</th>
                <th class="text-primary text-center" style="width: 10%">
                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" id="btnCreateTraveler">{{ __('Traveler') }}</button>
                </th>
                <th class="text-primary text-center process-action-col">{{ __('Action') }}</th>
                <th class="text-primary text-center" style="width: 14%">{{ __('Form') }}</th>
            </tr>
            </thead>
            <tbody id="sortable-tbody">
            @foreach($tdrProcesses as $processes)
                @if($processes->tdrs_id == $current_tdr->id)
                    @php
                        $processData = \App\Models\TdrProcess::normalizeStoredProcessIds($processes->processes);
                        $processName = $processes->processName ? $processes->processName->name : 'N/A';
                        $isEc = ($ecProcessNameId !== null && (int)$processes->process_names_id === (int)$ecProcessNameId);
                        $hasMachiningOrRil = $tdrProcesses->contains(fn($p) => in_array((int)$p->process_names_id, $ecEligibleProcessNameIds ?? []));
                        $isStandaloneEc = $isEc && (
                            $processes->standalone_ec_only === true
                            || $processes->standalone_ec_only === 1
                            || $processes->standalone_ec_only === '1'
                        );
                        $isEcEditable = $isEc && (
                            $isStandaloneEc
                            || $tdrProcesses->count() === 1
                            || ! $hasMachiningOrRil
                        );
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
                            @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$processes->id]); $travelerCheckboxRendered[$processes->id] = true; @endphp
                            @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                            <td class="text-center process-action-cell">
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
                                @php $travelerGroup = (int) ($processes->traveler_group ?: 1); @endphp
                                @if(empty($travelerFormRendered[$travelerGroup]))
                                    @php $travelerFormRendered[$travelerGroup] = true; @endphp
                                    <td class="text-center align-middle p-2 traveler-merged-form-cell">
                                        @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                    </td>
                                @else
                                    <td class="text-center align-middle text-muted small">—</td>
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
                            @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$processes->id]); $travelerCheckboxRendered[$processes->id] = true; @endphp
                            @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                            <td class="text-center process-action-cell">
                                @if($inTr)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                @endif
                            </td>
                            @if($inTr)
                                @php $travelerGroup = (int) ($processes->traveler_group ?: 1); @endphp
                                @if(empty($travelerFormRendered[$travelerGroup]))
                                    @php $travelerFormRendered[$travelerGroup] = true; @endphp
                                    <td class="text-center align-middle p-2 traveler-merged-form-cell">
                                        @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                    </td>
                                @else
                                    <td class="text-center align-middle text-muted small">—</td>
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
                                    @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$processes->id]); $travelerCheckboxRendered[$processes->id] = true; @endphp
                                    @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                                    <td class="text-center process-action-cell">
                                        @if($inTr)
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                        @else
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}" data-process="{{ $process }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </td>
                                    @if($inTr)
                                        @php $travelerGroup = (int) ($processes->traveler_group ?: 1); @endphp
                                        @if(empty($travelerFormRendered[$travelerGroup]))
                                            @php $travelerFormRendered[$travelerGroup] = true; @endphp
                                            <td class="text-center align-middle p-2 traveler-merged-form-cell">
                                                @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                            </td>
                                        @else
                                            <td class="text-center align-middle text-muted small">—</td>
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
                                @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$processes->id]); $travelerCheckboxRendered[$processes->id] = true; @endphp
                                @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                                <td class="text-center process-action-cell">
                                    @if($inTr)
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $processes->id }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $processes->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                                @if($inTr)
                                    @php $travelerGroup = (int) ($processes->traveler_group ?: 1); @endphp
                                    @if(empty($travelerFormRendered[$travelerGroup]))
                                        @php $travelerFormRendered[$travelerGroup] = true; @endphp
                                        <td class="text-center align-middle p-2 traveler-merged-form-cell">
                                            @include('admin.tdr-processes.partials.processes-body-traveler-form-controls')
                                        </td>
                                    @else
                                        <td class="text-center align-middle text-muted small">—</td>
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
