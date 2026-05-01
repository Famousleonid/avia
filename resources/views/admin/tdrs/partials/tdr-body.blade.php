{{-- TDR tab content: Inspection Unit + Inspection Component tables --}}
<style>
    .tdr-inline-create-row > td {
        background: rgba(13, 202, 240, .07);
        border-top: 2px solid rgba(13, 202, 240, .45);
        padding-left: 1px;
        padding-right: 1px;
        vertical-align: top;
    }

    #tdr_process_Table {
        table-layout: fixed;
        width: 100%;
    }

    #tdr_process_Table th,
    #tdr_process_Table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #tdr_process_Table th:not(:last-child),
    #tdr_process_Table td:not(:last-child) {
        white-space: nowrap;
    }

    #tdr_inspect_Table thead th,
    #tdr_process_Table thead th {
        height: 32px;
        padding-top: .25rem;
        padding-bottom: .25rem;
        vertical-align: middle;
    }

    #tdr_process_Table thead th {
        font-size: 14px;
        font-weight: 400;
        line-height: 1.1;
        padding: .18rem .25rem;
        white-space: nowrap;
    }

    .tdr-show-tables-layout {
        height: 75vh;
        width: 100%;
        overflow: visible;
        gap: 1ch;
        justify-content: flex-start;
    }

    .tdr-show-left-pane {
        flex: 0 0 300px;
        width: 300px;
        min-width: 240px;
        max-height: 70vh;
        overflow-y: auto;

    }

    #tdr_inspect_Table {
        table-layout: fixed;
        width: 100%;
    }

    #tdr_inspect_Table tbody td:first-child {
        font-size: .725rem;
        line-height: 1.15;
    }

    #tdr_inspect_Table tbody tr,
    #tdr_inspect_Table tbody td {
        height: 55px;
    }

    #tdr_inspect_Table tbody td {
        vertical-align: middle;
    }

    #tdr_inspect_Table .tdr-unit-trash-btn {
        --bs-btn-color: var(--bs-danger);
        --bs-btn-bg: transparent;
        --bs-btn-border-color: transparent;
        --bs-btn-hover-color: #ff4d5f;
        --bs-btn-hover-bg: transparent;
        --bs-btn-hover-border-color: transparent;
        --bs-btn-active-color: #ff4d5f;
        --bs-btn-active-bg: transparent;
        --bs-btn-active-border-color: transparent;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0;
        line-height: 1;
    }

    #tdr_inspect_Table .tdr-unit-trash-btn:hover,
    #tdr_inspect_Table .tdr-unit-trash-btn:focus {
        background: transparent !important;
        box-shadow: none !important;
    }

    #tdr_inspect_Table .tdr-unit-trash-btn .bi-trash3 {
        color: var(--bs-danger);
        font-size: 1rem;
    }

    .tdr-show-right-pane {
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
        position: relative;
    }

    .tdr-show-right-table-wrapper {
        max-height: 60vh;
        min-width: 0;
        width: 100%;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .tdr-show-right-toolbar {
        position: absolute;
        top: -32px;
        left: 0;
        right: 0;
        min-height: 28px;
        z-index: 20;
        pointer-events: none;
    }

    .tdr-show-right-toolbar > * {
        pointer-events: auto;
    }

    #tdrInlineAddBtn {
        align-items: center;
        display: inline-flex;
        height: 24px;
        justify-content: center;
        min-width: 52px;
        padding-left: .35rem;
        padding-right: .35rem;
        width: 52px;
    }

    #tdrInlineAddRow > td {
        border-top: 0;
        padding-bottom: .15rem;
        padding-top: .15rem;
        vertical-align: middle;
    }

    .tdr-inline-manual-picker {
        width: min(320px, 36vw);
        min-width: 240px;
    }

    #tdrInlineManualPicker .select2-selection,
    #tdrInlineComponentPicker .select2-selection,
    #tdr_inline_manual_id,
    #tdr_inline_component_id,
    #tdrInlineCreateRow .tdr-inline-field .select2-selection,
    #tdrInlineCreateRow .tdr-inline-field .form-control,
    #tdrInlineCreateRow .tdr-inline-field .form-select {
        border: 1px dotted var(--bs-info) !important;
    }

    #tdr-inline-add-part-btn,
    #tdr-inline-edit-part-btn {
        font-size: 12px;
        line-height: 1.1;
    }

    .select2-dropdown.tdr-inline-select-dropdown {
        min-width: min(520px, 90vw) !important;
    }

    .select2-dropdown.tdr-inline-select-dropdown .select2-search--dropdown {
        display: block !important;
    }

    .select2-dropdown.tdr-inline-select-dropdown .select2-search__field {
        display: block !important;
        width: 100% !important;
    }

    #tdr_process_Table .tdr-action-cell {
        padding-left: 3px;
        padding-right: 3px;
    }

    #tdr_process_Table .tdr-action-cell .btn {
        --bs-btn-padding-x: .28rem;
        --bs-btn-padding-y: .16rem;
        font-size: .78rem;
        line-height: 1;
    }

    #tdr_process_Table .tdr-action-cell .btn.me-2 {
        margin-right: .2rem !important;
    }

    #tdr_process_Table .tdr-part-name-cell {
        position: relative;
    }

    #tdr_process_Table .tdr-description-marker {
        border-left: 8px solid transparent;
        border-top: 8px solid var(--bs-info);
        cursor: help;
        height: 0;
        position: absolute;
        right: 2px;
        top: 2px;
        width: 0;
    }

    .tdr-inline-cell {
        cursor: pointer;
        min-height: 42px;
        overflow: hidden;
    }

    .tdr-inline-cell-disabled {
        cursor: default;
    }

    .tdr-inline-placeholder {
        min-height: 32px;
        padding: .35rem .45rem;
        border: 1px dashed rgba(13, 202, 240, .45);
        border-radius: .35rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tdr-inline-field {
        min-width: 0;
        width: 100%;
        margin-left: 0;
        margin-right: 0;
    }

    .tdr-inline-field .form-control,
    .tdr-inline-field .form-select,
    .tdr-inline-field .select2-container {
        max-width: 100% !important;
        min-width: 0 !important;
        width: 100% !important;
    }

    #tdrInlineCreateRow .tdr-inline-field .form-control,
    #tdrInlineCreateRow .tdr-inline-field .form-select {
        font-size: 14px !important;
        height: 32px !important;
        line-height: 30px !important;
        padding-bottom: 0 !important;
        padding-top: 0 !important;
        text-align: center;
        text-align-last: center;
    }

    body.tdr-inline-select2-dropdown-open .select2-container--open .select2-search__field,
    body.tdr-inline-select2-dropdown-open .select2-container--open .select2-results__option {
        font-size: 14px !important;
        line-height: 1.25 !important;
    }

    body.tdr-inline-component-select2-open .select2-container--open {
        width: min(460px, 90vw) !important;
        min-width: min(460px, 90vw) !important;
    }

    body.tdr-inline-component-select2-open .select2-container--open .select2-dropdown {
        width: 100% !important;
        min-width: min(460px, 90vw) !important;
    }

    #tdrInlineCreateRow .tdr-inline-field .select2-selection {
        height: 32px !important;
        min-height: 32px !important;
    }

    #tdrInlineCreateRow .tdr-inline-field .select2-selection__rendered {
        align-items: center;
        display: flex !important;
        font-size: 14px !important;
        height: 100%;
        justify-content: center;
        line-height: 30px !important;
        padding-bottom: 0 !important;
        padding-top: 0 !important;
        text-align: center;
    }

    #tdrInlineCreateRow .tdr-inline-field .select2-selection__arrow {
        height: 30px !important;
    }

    #tdr_process_Table select option {
        font-size: 14px;
    }

    #tdrInlineComponentPicker .select2-selection__rendered[data-part-number]:not([data-part-number=""]) {
        color: transparent !important;
        position: relative;
    }

    #tdrInlineComponentPicker .select2-selection__rendered[data-part-number]:not([data-part-number=""])::after {
        color: var(--bs-body-color);
        content: attr(data-part-number);
        left: 0;
        line-height: 1.2;
        overflow: hidden;
        position: absolute;
        right: 0;
        text-align: center;
        text-overflow: ellipsis;
        top: 50%;
        transform: translateY(-50%);
        white-space: nowrap;
    }

    #tdr_inline_order_component_group {
        display: inline-block;
        max-width: 100%;
    }

    #tdr_inline_order_component_group .form-label {
        font-size: 10px;
        line-height: 1.1;
    }

    #tdr_inline_order_component_group .select2-container,
    #tdr_inline_order_component_id {
        min-width: 8ch !important;
        max-width: 100% !important;
        width: auto !important;
    }

    #tdr_inline_serial_number,
    #tdr_inline_assy_serial_number {
        max-width: 100%;
        min-width: 0;
    }

    .select2-container--open .select2-results > .select2-results__options {
        max-height: min(42vh, 320px) !important;
        overflow-y: auto !important;
    }
</style>
<div class="d-flex tdr-show-tables-layout">
    <div class="tdr-show-left-pane">
        <div class="table-wrapper me-1 p-1">
            <table id="tdr_inspect_Table" class="table table-sm table-hover align-middle table-bordered dir-table shadow-lg">
                <colgroup>
                    <col >
                    <col style="width: 55px;">
                </colgroup>
                <thead>
                <tr>
                    <th class="text-primary text-center" colspan="2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#unitInspectionModal">{{__('Teardown Inspection')}}</a>
                    </th>
                </tr>
                </thead>
                <tbody>
                @if($hasMissingParts && $missingCondition)
                    <tr>
                        <td class="text-center fs-8">{{ $missingCondition->name }}</td>
                        <td class="text-center img-icon p-0">
                            <img src="{{ asset('img/missing.gif') }}" alt="missing" class="d-block"
                                 style="width: 55px;"
                                 data-bs-toggle="modal" data-bs-target="#missingModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                @foreach($inspectsUnit->whereNull('component_id') as $tdr)
                    <tr>
                        <td class="text-center fs-8" >
                            @php
                                $conditionName = $tdr->conditions->name ?? null;
                                if (!$conditionName) {
                                    foreach($conditions as $condition) {
                                        if ($condition->id == $tdr->conditions_id) {
                                            $conditionName = $condition->name;
                                            break;
                                        }
                                    }
                                }

                                $isNoteCondition = $conditionName && preg_match('/^note\s+\d+$/i', $conditionName);
                            @endphp
                            @if(!$isNoteCondition)
                                @if($tdr->conditions)
                                    {{ empty($tdr->conditions->name) ? __('(No name)') : $tdr->conditions->name }}
                                @else
                                    @foreach($conditions as $condition)
                                        @if($condition->id == $tdr->conditions_id)
                                            {{ empty($condition->name) ? __('(No name)') : $condition->name }}
                                            @break
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            @if($tdr->description)
                                {{ $isNoteCondition ? $tdr->description : '(' . $tdr->description . ')' }}
                            @endif
                        </td>
                        <td class="p-0 text-center img-icon">
                            <form action="{{ route('tdrs.destroy', $tdr->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="show">
                                <button type="button"
                                        class="btn btn-link btn-sm tdr-unit-trash-btn"
                                        aria-label="{{ __('Delete') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#useConfirmDelete"
                                        data-title="{{ __('Delete Confirmation') }}">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @if($hasOrderedParts ?? false)
                    <tr>
                        <td class="text-center">
                            <span class="position-relative d-inline-block">
                                ORDERED PARTS
                                <sup class="badge bg-primary rounded-pill position-absolute" style="top: 0.1em; right: -3.0em; font-size: 0.65em;">{{ $orderedPartsCount ?? 0 }}</sup>
                            </span>
                        </td>
                        <td class="p-0 text-center img-icon" style="height: 55px; width: 55px; overflow: hidden;">
                            <img src="{{ asset('img/pay.gif')}}" alt="order" style="height: 55px; width: 55px; object-fit: cover; display: block;" data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="tdr-show-right-pane">
        <form id="tdrInlineCreateForm" class="d-none" method="POST" action="{{ route('tdrs.store') }}" data-no-spinner>
            @csrf
            <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
            <input type="hidden" name="return_to" value="show">
            <input type="hidden" name="use_tdr" id="tdr_inline_use_tdr" value="0">
            <input type="hidden" name="use_process_forms" id="tdr_inline_use_process_forms" value="0">
            <input type="hidden" name="conditions_id" id="tdr_inline_conditions_id" value="">
        </form>
        <div class="tdr-show-right-toolbar d-flex align-items-end justify-content-between">
            <div id="tdrInlineManualPicker" class="tdr-inline-manual-picker d-none">
                <select name="manual_id" id="tdr_inline_manual_id" class="form-control form-control-sm" form="tdrInlineCreateForm">
                    <option value="">---</option>
                    @foreach($manuals as $manual)
                        <option value="{{ $manual->id }}" {{ $manual->id == $manual_id ? 'selected' : '' }}>
                            {{ $manual->number }} : {{ $manual->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-wrapper p-1 tdr-show-right-table-wrapper">
            <table id="tdr_process_Table" class="table table-sm table-hover align-middle dir-table small shadow-lg">
                <colgroup>
                    <col style="width: 12ch;">
                    <col style="width: 22%;">
                    <col style="width: 12%;">
                    <col style="width: 10ch;">
                    <col style="width: 14%;">
                    <col style="width: 28%;">
                    <col style="width: 5.5ch;">
                    <col style="width: 96px;">
                </colgroup>
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center text-primary sortable">{{__('IPL')}}</th>
                    <th class="text-center text-primary sortable">{{__('P/N')}}</th>
                    <th class="text-center text-primary">{{__('Code')}}</th>
                    <th class="text-center text-primary">{{__('Necessary')}}</th>
                    <th class="text-center text-primary sortable">{{__('S/N')}}</th>
                    <th class="text-center text-primary sortable">{{__('Part name')}}</th>
                    <th class="text-center text-primary">{{__('EC')}}</th>
                    <th class="text-primary text-center">
                        <span class="text-center">{{__('Action')}}</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($tdrs as $tdr)
                    @if($tdr->use_tdr == true && $tdr->use_process_forms == true)
                        <tr>
                            <td class="text-center">{{ $tdr->component->ipl_num ?? '' }}</td>
                            <td class="text-center">{{ $tdr->component->part_number ?? '' }}</td>
                            <td class="text-center">
                                @foreach($codes as $c)
                                    @if($c->id == $tdr->codes_id) {{ $c->name }} @endif
                                @endforeach
                            </td>
                            <td class="text-center">
                                @foreach($necessaries as $nec)
                                    @if($nec->id == $tdr->necessaries_id) {{ $nec->name }} @endif
                                @endforeach
                            </td>
                            <td class="text-center">{{ $tdr->serial_number }}</td>
                            <td class="text-center tdr-part-name-cell">
                                {{ $tdr->component->name ?? '' }}
                                @if(filled($tdr->description))
                                    <span class="tdr-description-marker" title="{{ $tdr->description }}"></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php $found = false; @endphp
                                @foreach($tdr_proc as $tdr_ec)
                                    @if($tdr_ec->tdrs_id == $tdr->id)
                                        @php $found = true; @endphp
                                        <img src="{{ asset('img/ok.png') }}" alt="{{ __('EC') }}" width="20">
                                        @break
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-center tdr-action-cell">
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 open-part-processes-tab"
                                            title="{{ __('Part Processes') }}"
                                            data-tdr-id="{{ $tdr->id }}">
                                        <i class="bi bi-bar-chart-steps"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2" title="{{ __('Part
                                    Inspection Edit') }}"
                                            data-bs-toggle="modal" data-bs-target="#editTdrModal"
                                            data-tdr-id="{{ $tdr->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('tdrs.destroy', ['tdr' => $tdr->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="show">
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#useConfirmDelete"
                                                data-title="{{ __('Delete Confirmation') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr id="tdrInlineCreateRow" class="tdr-inline-create-row d-none">
                    <td class="text-center text-muted" id="tdr_inline_ipl_display"></td>
                    <td>
                        <div id="tdrInlineComponentPicker" class="tdr-inline-field">
                            <select name="component_id" id="tdr_inline_component_id" class="form-control form-control-sm" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($components as $component)
                                    <option value="{{ $component->id }}"
                                            data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                            data-title="{{ $component->name }}"
                                            data-ipl="{{ $component->ipl_num }}"
                                            data-part-number="{{ $component->part_number }}">
                                        {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="tdr-inline-parts-actions" @class([
                                'mt-1 d-flex',
                                'd-none' => !($canManageManualParts ?? false),
                            ])>
                                <button type="button"
                                        id="tdr-inline-add-part-btn"
                                        class="btn btn-link btn-sm p-0 me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addComponentModal">
                                    {{ __('Add Part') }}
                                </button>
                                <button type="button" id="tdr-inline-edit-part-btn" class="btn btn-link btn-sm p-0">
                                    {{ __('Edit Part') }}
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="code">
                        <div class="tdr-inline-placeholder text-info">{{ __('Click code') }}</div>
                        <div class="tdr-inline-field d-none">
                            <select name="codes_id" id="tdr_inline_codes_id" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($codes as $code)
                                    <option value="{{ $code->id }}" data-title="{{ $code->name }}">
                                        {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="qty" id="tdr_inline_qty" class="form-control form-control-sm d-none" form="tdrInlineCreateForm" value="1" min="1" style="max-width: 90px" placeholder="{{ __('QTY') }}">
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="necessary">
                        <div class="tdr-inline-placeholder text-info">{{ __('Click necessary') }}</div>
                        <div class="tdr-inline-field d-none">
                            <select name="necessaries_id" id="tdr_inline_necessaries_id" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($necessaries as $necessaryItem)
                                    <option value="{{ $necessaryItem->id }}" data-title="{{ $necessaryItem->name }}">
                                        {{ $necessaryItem->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="tdr_inline_order_component_group" class="d-none">
                                <label for="tdr_inline_order_component_id" class="form-label mb-1 small text-muted">{{ __('Order Component') }}</label>
                                <select name="order_component_id" id="tdr_inline_order_component_id" class="form-control form-control-sm" form="tdrInlineCreateForm">
                                    <option selected value="">---</option>
                                    @foreach($components as $component)
                                        <option value="{{ $component->id }}">
                                            {{ $component->assy_part_number ?: $component->part_number }} - {{ $component->name }} ({{ $component->ipl_num }})
                                        </option>
                                    @endforeach
                                </select>
                                <div id="tdr_inline_order_qty_mount" class="mt-1"></div>
                            </div>
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="serial">
                        <div class="tdr-inline-placeholder text-info"></div>
                        <div class="tdr-inline-field d-none">
                            <input type="text" name="serial_number" id="tdr_inline_serial_number" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm" placeholder="{{ __('Serial Number') }}">
                            <input type="text" name="assy_serial_number" id="tdr_inline_assy_serial_number" class="form-control form-control-sm d-none" form="tdrInlineCreateForm" placeholder="{{ __('Assy Serial Number') }}">
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="description">
                        <div class="tdr-inline-placeholder text-info"></div>
                        <div class="tdr-inline-field d-none">
                            <input type="text" name="description" id="tdr_inline_description" class="form-control form-control-sm d-none mb-1" form="tdrInlineCreateForm" placeholder="{{ __('Description') }}">
                        </div>
                    </td>
                    <td class="text-center text-muted"></td>
                    <td class="text-center tdr-action-cell">
                        <div class="d-flex justify-content-center">
                            <button type="submit" form="tdrInlineCreateForm" class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button>
                        </div>
                    </td>
                </tr>
                <tr id="tdrInlineAddRow">
                    <td class="text-start">
                        <button type="button" class="btn btn-outline-info btn-sm" id="tdrInlineAddBtn">{{ __('Add') }}</button>
                    </td>
                    <td colspan="7"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('components.delete')
