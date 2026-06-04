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
    $formRouteExtraParams = !empty($omitFormHeaderDate) ? ['omit_form_header_date' => 1] : [];
    $hasGroupProcessForms = collect($processGroups ?? [])->contains(function ($g) {
        return (int) ($g['count'] ?? 0) > 1;
    });

    // 2c.1 — document templates linked to each TdrProcess (via rule_process_ids).
    $allRpIds = [];
    foreach ($tdrProcesses as $_tp) {
        foreach ((array) ($_tp->rule_process_ids ?? []) as $_rid) {
            $allRpIds[] = $_rid;
        }
    }
    $docsByRp = [];
    if (!empty($allRpIds)) {
        $_docs = \App\Models\ProcessDocument::where('documentable_type', \App\Models\ManualParameterRuleProcess::class)
            ->whereIn('documentable_id', array_values(array_unique($allRpIds)))
            ->orderBy('sort_order')
            ->get(['id', 'documentable_id', 'title', 'doc_type']);
        foreach ($_docs as $_d) {
            $docsByRp[$_d->documentable_id][] = $_d;
        }
    }
    // EC dimensions sheet — part-level document(s) on the inspection component (shown on the EC row).
    $ecDocs = [];
    $_icId = \App\Models\ManualInspectionComponentVariant::where('component_id', $comp->id ?? 0)->value('inspection_component_id');
    if ($_icId) {
        $ecDocs = \App\Models\ProcessDocument::where('documentable_type', \App\Models\ManualInspectionComponent::class)
            ->where('documentable_id', $_icId)
            ->orderBy('sort_order')
            ->get(['id', 'documentable_id', 'title', 'doc_type'])
            ->all();
    }
    // Колонку Document показываем, если есть шаблон у процесса детали ИЛИ EC-лист детали.
    $showDocColumn = !empty($docsByRp) || !empty($ecDocs);
@endphp

<style>
    /* Document column (2c.1): ширину задаёт <colgroup>; nowrap обязателен — иначе
       одиночное слово "Document" рвётся по буквам (глобально overflow-wrap:anywhere)
       и раздувает высоту шапки. */
    .process-doc-col,
    .process-doc-cell {
        white-space: nowrap;
    }
    .tdr-process-inline-create-row > td {
        background: rgba(13, 202, 240, .07);
        border-top: 2px solid rgba(13, 202, 240, .45);
        vertical-align: middle;
    }
    .tdr-processes-table.table-hover > tbody > .tdr-process-inline-create-row:hover > * {
        --bs-table-accent-bg: rgba(13, 202, 240, .07);
        background-color: rgba(13, 202, 240, .07) !important;
        color: inherit;
    }
    .dir-table.table-hover > tbody > .tdr-process-inline-create-row:hover > td,
    .dir-table.table-hover > tbody > .tdr-process-inline-create-row:hover > th,
    .dir-table > tbody > .tdr-process-inline-create-row:focus-within > td,
    .dir-table > tbody > .tdr-process-inline-create-row:focus-within > th {
        background-color: rgba(13, 202, 240, .07) !important;
        color: var(--dir-text) !important;
    }
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table.table-hover > tbody > tr.tdr-process-inline-create-row:hover > td,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table.table-hover > tbody > tr.tdr-process-inline-create-row:hover > th,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table > tbody > tr.tdr-process-inline-create-row:focus-within > td,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table > tbody > tr.tdr-process-inline-create-row:focus-within > th {
        --bs-table-hover-bg: rgba(13, 202, 240, .07);
        --bs-table-hover-color: var(--dir-text);
        --bs-table-active-bg: rgba(13, 202, 240, .07);
        --bs-table-active-color: var(--dir-text);
        background-color: rgba(13, 202, 240, .07) !important;
        color: var(--dir-text) !important;
        box-shadow: none !important;
    }
    .dir-table.table-hover > tbody > .tdr-process-inline-create-row:hover > td:first-child::before,
    .dir-table.table-hover > tbody > .tdr-process-inline-create-row:hover > th:first-child::before,
    .dir-table > tbody > .tdr-process-inline-create-row:focus-within > td:first-child::before,
    .dir-table > tbody > .tdr-process-inline-create-row:focus-within > th:first-child::before {
        background: transparent !important;
    }
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table.table-hover > tbody > tr.tdr-process-inline-create-row:hover > td:first-child::before,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table.table-hover > tbody > tr.tdr-process-inline-create-row:hover > th:first-child::before,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table > tbody > tr.tdr-process-inline-create-row:focus-within > td:first-child::before,
    .processes-modal-body .tdr-processes-table.dir-table.sortable-table > tbody > tr.tdr-process-inline-create-row:focus-within > th:first-child::before {
        background: transparent !important;
    }
    .tdr-process-inline-create-row .form-select,
    .tdr-process-inline-create-row .form-control,
    .tdr-process-inline-process-text {
        border: 1px dotted var(--bs-info);
        min-height: 32px;
    }
    .tdr-process-inline-process-text {
        align-items: center;
        border-radius: .35rem;
        display: flex;
        justify-content: center;
        line-height: 1.15;
        padding: .35rem .45rem;
        white-space: normal;
    }
    .tdr-processes-table {
        table-layout: fixed;
        min-width: 980px;
    }
    .tdr-processes-table th,
    .tdr-processes-table td {
        overflow-wrap: anywhere;
    }
    .tdr-processes-table .process-action-col,
    .tdr-processes-table .process-action-cell,
    .tdr-processes-table .process-form-col {
        white-space: nowrap;
    }
    .tdr-processes-table .traveler-vendor-select {
        min-width: 112px;
        width: 100%;
    }
    .tdr-process-inline-options {
        background: var(--dir-table-bg);
        border: 1px dotted var(--bs-info);
        border-radius: .35rem;
        box-shadow: 0 10px 26px rgba(0, 0, 0, .35);
        margin-top: .35rem;
        max-height: min(240px, 45vh);
        min-height: 32px;
        overflow-y: auto;
        padding: .25rem .4rem;
        position: static;
        width: 100%;
    }
    .tdr-process-inline-process-cell {
        position: relative;
    }
    .tdr-process-inline-option {
        align-items: center;
        display: flex;
        gap: .4rem;
        line-height: 1.2;
        margin: 0;
        min-height: 28px;
        padding: .18rem 0;
    }
    .tdr-process-inline-option input {
        flex: 0 0 auto;
        margin-top: 0;
    }
    .tdr-process-inline-option span {
        min-width: 0;
    }
    .tdr-process-inline-option-comment {
        color: #ffc107;
    }
</style>

<div class="processes-modal-body" data-tdr-id="{{ $current_tdr->id }}"
     data-manual-id="{{ $manual_id ?? '' }}"
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
    <div class="table-wrapper me-3">
        <table class="display table table-sm table-hover align-middle bg-gradient dir-table sortable-table tdr-processes-table">
            <colgroup>
                <col style="width: 11%">{{-- Process Name --}}
                <col style="width: {{ $showDocColumn ? 37 : 42 }}%">{{-- Process (поглощает остаток) --}}
                <col style="width: 17%">{{-- Description --}}
                <col style="width: 9%">{{-- Traveler --}}
                <col style="width: 8%">{{-- Action --}}
                <col style="width: 13%">{{-- Form --}}
                @if($showDocColumn)<col style="width: 5%">{{-- Document --}}@endif
            </colgroup>
            <thead>
            <tr>
                <th class="text-primary text-center">Process Name</th>
                <th class="text-primary text-center">Process</th>
                <th class="text-primary text-center">Description</th>
                <th class="text-primary text-center">
                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" id="btnCreateTraveler">{{ __('Traveler') }}</button>
                </th>
                <th class="text-primary text-center process-action-col">{{ __('Action') }}</th>
                <th class="text-primary text-center process-form-col">{{ __('Form') }}</th>
                @if($showDocColumn)<th class="text-primary text-center process-doc-col">{{ __('Document') }}</th>@endif
            </tr>
            </thead>
            <tbody id="sortable-tbody">
            @foreach($tdrProcesses as $tdrProcessRow)
                @if($tdrProcessRow->tdrs_id == $current_tdr->id)
                    @php
                        $processData = \App\Models\TdrProcess::normalizeStoredProcessIds($tdrProcessRow->processes);
                        $processName = $tdrProcessRow->processName ? $tdrProcessRow->processName->name : 'N/A';
                        $isEc = ($ecProcessNameId !== null && (int)$tdrProcessRow->process_names_id === (int)$ecProcessNameId);
                        $hasMachiningOrRil = $tdrProcesses->contains(fn($p) => in_array((int)$p->process_names_id, $ecEligibleProcessNameIds ?? []));
                        $isStandaloneEc = $isEc && (
                            $tdrProcessRow->standalone_ec_only === true
                            || $tdrProcessRow->standalone_ec_only === 1
                            || $tdrProcessRow->standalone_ec_only === '1'
                        );
                        $isEcEditable = $isEc && (
                            $isStandaloneEc
                            || $tdrProcesses->count() === 1
                            || ! $hasMachiningOrRil
                        );
                        $isNdtWithPlus = false;
                        $combinedProcessNames = [];
                        if (strpos($processName, 'NDT-') === 0 && !empty($tdrProcessRow->plus_process)) {
                            $isNdtWithPlus = true;
                            $combinedProcessNames[] = $processName;
                            foreach (explode(',', $tdrProcessRow->plus_process) as $plusProcessId) {
                                $plusProcessName = \App\Models\ProcessName::find(trim($plusProcessId));
                                if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                                    $combinedProcessNames[] = $plusProcessName->name;
                                }
                            }
                        }
                        $inTr = (bool) $tdrProcessRow->in_traveler;
                        /* без table-secondary — иначе Bootstrap даёт свой бледный hover */
                        $trClass = $inTr ? ' traveler-block-row traveler-locked' : '';
                    @endphp

                    @if(!$tdrProcessRow->processName) @continue @endif

                    @if($isEc)
                        <tr data-id="{{ $tdrProcessRow->id }}" class="{{ trim($trClass) }}">
                            <td class="text-center">{{ $processName }}</td>
                            <td class="ps-2">
                                @php
                                    $ecProcessLabels = [];
                                    if (is_array($processData) && !empty($processData)) {
                                        foreach($processData as $processId) {
                                            $proc = $proces->firstWhere('id', $processId);
                                            if ($proc) $ecProcessLabels[] = $proc->process;
                                        }
                                    }
                                @endphp
                                {{ !empty($ecProcessLabels) ? implode(', ', $ecProcessLabels) : 'No processes' }}
                            </td>
                            <td class="text-center">{{ $tdrProcessRow->description ?? '' }}</td>
                            @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$tdrProcessRow->id]); $travelerCheckboxRendered[$tdrProcessRow->id] = true; @endphp
                            @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                            <td class="text-center process-action-cell">
                                @if($inTr)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled title="{{ __('UnGroup Traveler to edit') }}"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled title="{{ __('UnGroup Traveler to delete') }}"><i class="bi bi-trash"></i></button>
                                @elseif($isEcEditable)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $tdrProcessRow->id }}" title="{{ __('Process Edit') }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-tdr-id="{{ $current_tdr->id }}" title="{{ __('Process Delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled"><i class="bi bi-trash"></i></button>
                                @endif
                            </td>
                            @if($inTr)
                                @php $travelerGroup = (int) ($tdrProcessRow->traveler_group ?: 1); @endphp
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
                            @include('admin.tdr-processes.partials.processes-body-document-cell')
                        </tr>
                    @elseif($isNdtWithPlus)
                        <tr data-id="{{ $tdrProcessRow->id }}" class="{{ trim($trClass) }}">
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
                                {{ !empty($allProcesses) ? implode(' / ', $allProcesses) : 'No processes' }}@if($tdrProcessRow->ec) ( EC ) @endif
                            </td>
                            <td class="text-center">{{ $tdrProcessRow->description ?? '' }}</td>
                            @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$tdrProcessRow->id]); $travelerCheckboxRendered[$tdrProcessRow->id] = true; @endphp
                            @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                            <td class="text-center process-action-cell">
                                @if($inTr)
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $tdrProcessRow->id }}"><i class="bi bi-pencil-square"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                @endif
                            </td>
                            @if($inTr)
                                @php $travelerGroup = (int) ($tdrProcessRow->traveler_group ?: 1); @endphp
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
                                    <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $tdrProcessRow->id }}">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('tdr-processes.show', array_merge(['tdr_process' => $tdrProcessRow->id], $formRouteExtraParams)) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $tdrProcessRow->id }}" target="_blank">{{ __('Form') }}</a>
                                </div>
                            </td>
                            @endif
                            @include('admin.tdr-processes.partials.processes-body-document-cell')
                        </tr>
                    @else
                        @if(is_array($processData) && !empty($processData))
                            @foreach($processData as $process)
                                <tr data-id="{{ $tdrProcessRow->id }}" class="{{ trim($trClass) }}">
                                    <td class="text-center">{{ $processName }}</td>
                                    <td class="ps-2">
                                        @php $proc = $proces->firstWhere('id', $process); @endphp
                                        @if($proc){{ $proc->process }}@if($tdrProcessRow->ec) ( EC ) @endif @endif
                                    </td>
                                    <td class="text-center">{{ $tdrProcessRow->description ?? '' }}</td>
                                    @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$tdrProcessRow->id]); $travelerCheckboxRendered[$tdrProcessRow->id] = true; @endphp
                                    @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                                    <td class="text-center process-action-cell">
                                        @if($inTr)
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                        @else
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $tdrProcessRow->id }}"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-tdr-id="{{ $current_tdr->id }}" data-process="{{ $process }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </td>
                                    @if($inTr)
                                        @php $travelerGroup = (int) ($tdrProcessRow->traveler_group ?: 1); @endphp
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
                                            <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-process="{{ $process }}">
                                                <option value="">Select Vendor</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('tdr-processes.show', array_merge(['tdr_process' => $tdrProcessRow->id, 'process_id' => $process], $formRouteExtraParams)) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-process="{{ $process }}" target="_blank">{{ __('Form') }}</a>
                                        </div>
                                    </td>
                                    @endif
                                    @include('admin.tdr-processes.partials.processes-body-document-cell')
                                </tr>
                            @endforeach
                        @else
                            <tr data-id="{{ $tdrProcessRow->id }}" class="{{ trim($trClass) }}">
                                <td class="text-center">{{ $processName }}</td>
                                <td class="ps-2 text-muted">—</td>
                                <td class="text-center">{{ $tdrProcessRow->description ?? '' }}</td>
                                @php $showTravelerCheckbox = empty($travelerCheckboxRendered[$tdrProcessRow->id]); $travelerCheckboxRendered[$tdrProcessRow->id] = true; @endphp
                                @include('admin.tdr-processes.partials.processes-body-traveler-checkbox', ['showTravelerCheckbox' => $showTravelerCheckbox])
                                <td class="text-center process-action-cell">
                                    @if($inTr)
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 disabled" disabled><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm disabled" disabled><i class="bi bi-trash"></i></button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2 load-edit-process" data-tdr-process-id="{{ $tdrProcessRow->id }}"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ajax-delete-process" data-tdr-process-id="{{ $tdrProcessRow->id }}" data-tdr-id="{{ $current_tdr->id }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                                @if($inTr)
                                    @php $travelerGroup = (int) ($tdrProcessRow->traveler_group ?: 1); @endphp
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
                                        <select class="form-select form-select-sm vendor-select" style="width: 85px" data-tdr-process-id="{{ $tdrProcessRow->id }}">
                                            <option value="">Select Vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('tdr-processes.show', array_merge(['tdr_process' => $tdrProcessRow->id], $formRouteExtraParams)) }}" class="btn btn-sm btn-outline-primary form-link" style="width: 60px" data-tdr-process-id="{{ $tdrProcessRow->id }}" target="_blank">{{ __('Form') }}</a>
                                    </div>
                                </td>
                                @endif
                                @include('admin.tdr-processes.partials.processes-body-document-cell')
                            </tr>
                        @endif
                    @endif
                @endif
            @endforeach
            <tr class="tdr-process-inline-create-row d-none" data-inline-process-row>
                <td>
                    <select class="form-select form-select-sm" data-inline-process-name>
                        <option value="">---</option>
                        @foreach(($processNames ?? collect()) as $processName)
                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="tdr-process-inline-process-cell">
                    <div class="tdr-process-inline-options d-none" data-inline-process-options></div>
                    <div class="tdr-process-inline-process-text text-center text-muted" data-inline-process-text>{{ __('Select process name') }}</div>
                    <button type="button"
                            class="btn btn-link btn-sm p-0 mt-1 d-none"
                            data-inline-process-create>
                        <i class="fas fa-plus"></i> {{ __('Add Process') }}
                    </button>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" data-inline-process-description placeholder="{{ __('Page & Fig') }}">
                </td>
                <td></td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button type="button" class="btn btn-info btn-sm" data-inline-process-save>{{ __('Save') }}</button>
                    </div>
                </td>
                <td></td>
                @if($showDocColumn)<td></td>@endif
            </tr>
            <tr class="tdr-process-inline-add-row">
                <td class="text-start">
                    <button type="button"
                            class="btn btn-outline-info btn-sm"
                            data-inline-process-add
                            data-tdr-id="{{ $current_tdr->id }}">
                        {{ __('Add') }}
                    </button>
                </td>
                <td colspan="{{ $showDocColumn ? 6 : 5 }}"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

@include('admin.tdr-processes.partials.part-processes-group-forms-modal')
