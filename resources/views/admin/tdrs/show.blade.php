@extends('admin.master')

@section('content')
    <style>
        .text-center { text-align: center; align-content: center; }
        .fs-8 { font-size: 0.8rem; }
        .fs-7 { font-size: 0.7rem; }
        .fs-75 { font-size: 0.75rem; }
        #tdr_inspect_Table thead { position: sticky; top: 0; z-index: 10; }
        #tdr_inspect_Table thead th {  box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        #tdr__Table thead th { background-color: #030334 !important; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        .order-modal .modal-dialog { max-height: 85vh; }
        .order-modal .modal-content { max-height: 85vh; display: flex; flex-direction: column; }
        .order-modal .modal-header { flex-shrink: 0; }
        .order-modal .order-modal-table-wrapper { overflow-y: auto; flex: 1; min-height: 0; }
        .order-modal .order-modal-table thead { position: sticky; top: 0; z-index: 10; }
        .order-modal .order-modal-table thead th { background-color: #030334 !important; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        .img-icon:hover { cursor: pointer; }
        .tdr-show-back-btn{
            min-height: 55px;
            padding: .25rem .55rem !important;
            border-width: 1px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            font-weight: 700;
            color: #31d2f2 !important;
            background: rgba(13, 202, 240, .08);
            text-decoration: none !important;
        }
        .tdr-show-back-btn:hover,
        .tdr-show-back-btn:focus {
            color: #5ee3ff !important;
            border-color: #5ee3ff !important;
            background: rgba(13, 202, 240, .16) !important;
            box-shadow: 0 0 0 .12rem rgba(13, 202, 240, .22);
            text-decoration: none !important;
        }
        .tdr-show-back-arrow{
            font-size: 1.35rem;
            line-height: 1;
            font-weight: 900;
            color: #fff;
        }
        .tdr-show-back-text{
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.02;
            text-align: left;
        }
        .tdr-show-back-text .t1{ font-size: 1rem; opacity: .95; }
        .tdr-show-back-text .t2{ font-size: 1.3rem; font-weight: 700; }
        #tdrShowTabList {
            --tdr-tabs-bg: #212529;
            border-bottom: 0 !important;
            align-items: flex-end;
        }
        #tdrShowTabList .nav-link {
            border-radius: 6px 6px 0 0;
            border-color: transparent;
            margin-bottom: 0;
            padding-bottom: .55rem;
            padding-top: .55rem;
            position: relative;
        }
        #tdrShowTabList .nav-link:not(.active)::after {
            background: rgba(13, 202, 240, .55);
            bottom: 0;
            content: "";
            height: 1px;
            left: 0;
            position: absolute;
            right: 0;
        }
        #tdrShowTabList .nav-link.active {
            background-color: transparent;
            border-color: rgba(13, 202, 240, .8) rgba(13, 202, 240, .8) var(--tdr-tabs-bg);
            color: #5ee3ff;
            isolation: isolate;
            margin-bottom: -1px;
            z-index: 3;
        }
        /* Select2 in modals - ensure dropdown appears above modal (Bootstrap modal z-index: 1055) */
        #componentInspectionModal .select2-container--open,
        .select2-container--open { z-index: 1065 !important; }
        .select2-dropdown { z-index: 1065 !important; }
        #componentInspectionModal .modal-body { overflow-x: visible; overflow-y: auto; }
        /* Select2 selection — как инпуты (form-control) в модалке */
        #componentInspectionModal .select2-container .select2-selection--single,
        #componentInspectionModal .select2-container .select2-selection--multiple {
            background-color: rgba(33,37,41,.85) !important;
            border: 1px solid rgba(255,255,255,.15) !important;
            border-radius: 0.375rem !important;
            min-height: 38px !important;
            padding: 0.375rem 0.75rem !important;
        }
        #componentInspectionModal .select2-container .select2-selection__rendered {
            color: rgba(248,249,250,.95) !important;
            line-height: 1.5 !important;
        }
        /* Иконка 'x' (clear) — справа */
        #componentInspectionModal .select2-container .select2-selection {
            position: relative !important;
            padding-right: 2rem !important;
        }
        #componentInspectionModal .select2-selection__clear {
            position: absolute !important;
            right: 8px !important;
            left: auto !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 2 !important;
        }
        /* Select2 Dark Theme for dropdown (when appended to body) */
        html[data-bs-theme="dark"] .select2-dropdown,
        html[data-bs-theme="dark"] .select2-container--default .select2-results > .select2-results__options {
            background-color: #121212 !important;
            color: #e9ecef !important;
            border: 1px solid #495057 !important;
        }
        html[data-bs-theme="dark"] .select2-results__option {
            color: #e9ecef !important;
        }
        html[data-bs-theme="dark"] .select2-results__option--highlighted[aria-selected] {
            background-color: #6ea8fe !important;
            color: #000000 !important;
        }
        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
            color: #e9ecef !important;
            border: 1px solid #495057 !important;
        }
        /* Add Part Processes & Edit Part Process modals (iframe) - ensure on top */
        #addPartProcessesModal, #editTdrProcessModal, #editExtraProcessModal, #addExtraProcessModal, #addExtraPartModal, #editBushingModal, #addProcessesModal, #addPartModal, #changeSnModal, #partProcessesGroupFormsModal { z-index: 1080 !important; }
        #addProcessesModal.modal.show, #addPartModal.modal.show { z-index: 1090 !important; }
        #addPartProcessesModal ~ .modal-backdrop, #editTdrProcessModal ~ .modal-backdrop, #editExtraProcessModal ~ .modal-backdrop, #addExtraProcessModal ~ .modal-backdrop, #addExtraPartModal ~ .modal-backdrop, #editBushingModal ~ .modal-backdrop, #addProcessesModal ~ .modal-backdrop, #addPartModal ~ .modal-backdrop, #changeSnModal ~ .modal-backdrop, #partProcessesGroupFormsModal ~ .modal-backdrop { z-index: 1075 !important; }

        #partProcessesGroupFormsModal .modal-dialog {
            max-height: 80vh;
            margin: 1.75rem auto;
        }
        #partProcessesGroupFormsModal .modal-content {
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        #partProcessesGroupFormsModal .modal-header {
            flex-shrink: 0;
        }
        #partProcessesGroupFormsModal .modal-body {
            overflow-y: auto;
            min-height: 0;
        }

        /* Part / Extra processes (AJAX block) — заметнее подсветка строки при наведении */
        .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th,
        .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th {
            --bs-table-hover-color: var(--bs-body-color);
            --bs-table-hover-bg: rgba(13, 110, 253, 0.18);
            background-color: rgba(13, 110, 253, 0.16) !important;
        }
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th,
        html[data-bs-theme="dark"] .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        html[data-bs-theme="dark"] .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th {
            --bs-table-hover-bg: rgba(110, 168, 254, 0.28);
            background-color: rgba(110, 168, 254, 0.24) !important;
        }

        /* Traveler-блок: фон без table-secondary, чтобы hover совпадал с остальными строками */
        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td,
        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th {
            background-color: rgba(108, 117, 125, 0.12);
            box-shadow: inset 3px 0 0 rgba(108, 117, 125, 0.45);
        }
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td,
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th {
            background-color: rgba(255, 255, 255, 0.06);
            box-shadow: inset 3px 0 0 rgba(173, 181, 189, 0.5);
        }
    </style>

    @php
        $manual = null;
        $hasNdtComponents = false;
        $hasCadComponents = false;
        $hasStressComponents = false;
        $hasPaintComponents = false;
        if ($current_wo && $current_wo->ndtCadCsv) {
            $ndtComponents = $current_wo->ndtCadCsv->ndt_components ?? [];
            $hasNdtComponents = !empty($ndtComponents) && is_array($ndtComponents) && count($ndtComponents) > 0;
            $cadComponents = $current_wo->ndtCadCsv->cad_components ?? [];
            $hasCadComponents = !empty($cadComponents) && is_array($cadComponents) && count($cadComponents) > 0;
            $stressComponents = $current_wo->ndtCadCsv->stress_components ?? [];
            $hasStressComponents = !empty($stressComponents) && is_array($stressComponents) && count($stressComponents) > 0;
            $paintComponents = $current_wo->ndtCadCsv->paint_components ?? [];
            $hasPaintComponents = !empty($paintComponents) && is_array($paintComponents) && count($paintComponents) > 0;
        }
    @endphp

    @if($current_wo->unit->manuals->builder)
        <div class="card bg-gradient">
            {{-- TDR show: link to main, PDF Library, x-paper buttons --}}
            <div class="card-header m-1 shadow">
                <div class="d-flex text-center align-items-center">
                    <div style="width: 120px;">
                        <a href="{{ route('mains.show', $current_wo->id) }}"
                           class="btn btn-outline-info tdr-show-back-btn"
                           title="{{ __('Back to WO main') }}">
                            <i class="bi bi-arrow-left tdr-show-back-arrow" aria-hidden="true"></i>
                            <span class="tdr-show-back-text">
                                <span class="t1">wo</span>
                                <span class="t2">{{ $current_wo->number }}</span>
                            </span>
                        </a>
                    </div>
                    <div class="ps-2 d-flex align-items-center ms-3">
                        <div class="me-2 position-relative">
                            <button class="btn btn-outline-warning ms-2 open-pdf-modal text-center"
                                    title="{{ __('PDF Library') }}"
                                    style="height: 55px;width: 55px;align-content: center"
                                    data-id="{{ $current_wo->id }}"
                                    data-number="{{ $current_wo->number }}">
                                <i class="bi bi-file-earmark-pdf" style="font-size: 26px;"></i>
                            </button>
                            <span id="pdfCountBadge"
                                  class="badge bg-warning rounded-pill position-absolute d-none"
                                  style="top: -5px; right: -5px; min-width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; color: black; font-size: 0.7rem; padding: 0 5px;"></span>
                        </div>
                        {{-- x-paper buttons --}}
                        @if(count($tdrs))
                            <div class="d-flex flex-wrap gap-2 ms-2">
                                <div class="me-3 d-flex flex-wrap">
                                    <x-paper-button text="WO Process Sheet" href="{{ route('tdrs.woProcessForm', ['id'=> $current_wo->id]) }}" target="_blank" color="outline-info" />
                                    <x-paper-button text="WO Box Title" href="{{ route('tdrs.wo_BoxTitle', ['id'=> $current_wo->id]) }}" target="_blank" color="outline-info" />

                                </div>

                                <div class="me-3 d-flex flex-wrap">
                                <x-paper-button text="TDR Form" href="{{ route('tdrs.tdrForm', ['id'=> $current_wo->id]) }}" target="_blank" />

                                @if(!$hasProcessFormTdrs)
                                    <x-paper-button text="SP Form" href="{{ route('tdrs.specProcessFormEmp', ['id'=> $current_wo->id]) }}" target="_blank" />
                                @else
                                    <x-paper-button text="SP Form" href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}" target="_blank" />
                                @endif
                                <x-paper-button text="R&M Form" href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}" target="_blank" />

                                @if(count($prl_parts) > 0)
                                    <div class="position-relative d-inline-block ">
                                        <x-paper-button text="PRL" href="{{ route('tdrs.prlForm', ['id' => $current_wo->id]) }}" target="_blank" />
                                        <span class="badge bg-success rounded-pill" style="position: absolute; top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">{{ count($prl_parts) }}</span>
                                    </div>
                                @endif
                                </div>
                                    <div id="extraGroupFormsHeaderBtn" class="d-none pt-1 me-3">
                                        <x-paper-button-multy text="Group Process Forms" color="outline-primary" size="landscape" width="100"
                                                              ariaLabel="Group Process Forms" data-bs-toggle="modal" data-bs-target="#extraGroupFormsModal" />
                                    </div>

                            </div>
                            <div class="d-flex flex-wrap gap-2 ms-2" id="tdr-std-paper-group">
                                @if($current_wo->instruction_id == 1)
                                    <span class="tdr-std-paper-ndt-wrap d-inline-block @if(!$hasNdtComponents) d-none @endif">
                                        <x-paper-button text="NDT STD" href="{{ route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                    </span>
                                    <span class="tdr-std-paper-cad-wrap d-inline-block @if(!$hasCadComponents) d-none @endif">
                                        <x-paper-button text="CAD STD" href="{{ route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                    </span>
                                    <span class="tdr-std-paper-stress-wrap d-inline-block @if(!$hasStressComponents) d-none @endif">
                                        <x-paper-button text="Stress STD" href="{{ route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                    </span>
                                @endif
                                <span class="tdr-std-paper-paint-wrap d-inline-block @if(!$hasPaintComponents) d-none @endif">
                                    <x-paper-button text="Paint STD" href="{{ route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                </span>
                                <span id="logCardFormPaperWrap" class="{{ $log_card ? '' : 'd-none' }}">
                                    <x-paper-button text="Log Card" href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                </span>
                                @if(!empty($showDestructionCert))
                                    <span class="ms-1">
                                        <x-paper-button text="Cert. of Destruction" href="{{ route('log_card.sertDistrForm', ['id'=> $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                    </span>
                                @endif
                                <span id="bushingSpFormHeaderBtn">
                                    @if($woBushing)
                                        <x-paper-button text="Bushing SP Form" href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}" target="_blank" color="outline-primary" />
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Body: tabs (header links as tabs), TDR tab = main content --}}
            <div class="card-body p-1">
                <style>
                    .tdr-tabs-loading {
                        min-height: 34px;
                        display: flex;
                        align-items: center;
                        margin-bottom: .75rem;
                    }
                    .tdr-tabs-loading-dots {
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                    }
                    .tdr-tabs-loading-dot {
                        width: 7px;
                        height: 7px;
                        border-radius: 999px;
                        background: rgba(173, 181, 189, .9);
                        animation: tdrTabsDotWave 1s infinite ease-in-out;
                    }
                    .tdr-tabs-loading-dot:nth-child(2) { animation-delay: .12s; }
                    .tdr-tabs-loading-dot:nth-child(3) { animation-delay: .24s; }
                    @keyframes tdrTabsDotWave {
                        0%, 80%, 100% { transform: translateY(0); opacity: .45; }
                        40% { transform: translateY(-4px); opacity: 1; }
                    }
                </style>
                <div id="tdrShowTabsLoading" class="tdr-tabs-loading">
                    <span class="tdr-tabs-loading-dots" aria-label="Loading tabs">
                        <span class="tdr-tabs-loading-dot"></span>
                        <span class="tdr-tabs-loading-dot"></span>
                        <span class="tdr-tabs-loading-dot"></span>
                    </span>
                </div>
                <div id="tdrShowTabsHeader" class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3" style="visibility:hidden;">
                <ul class="nav nav-tabs mb-0" role="tablist" id="tdrShowTabList">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-tdr" data-bs-toggle="tab" data-bs-target="#content-tdr" type="button" role="tab">{{ __('TDR') }}</button>
                    </li>
                    {{-- Temporary Part Processes tab (shown when user clicks Component Processes, hidden when switching to another tab) --}}
                    <li class="nav-item d-none" role="presentation" id="tab-part-processes-li">
                        <button class="nav-link" id="tab-part-processes" data-bs-toggle="tab" data-bs-target="#content-part-processes" type="button" role="tab">
                            {{ __('Part Processes') }}
                        </button>
                    </li>
                    {{-- Temporary Extra Processes tab (shown when user clicks Processes in Extra Part Processes table) --}}
                    <li class="nav-item d-none" role="presentation" id="tab-extra-processes-li">
                        <button class="nav-link" id="tab-extra-processes" data-bs-toggle="tab" data-bs-target="#content-extra-processes" type="button" role="tab">
                            {{ __('Extra Processes') }}
                        </button>
                    </li>
                    @if(count($processParts))
                        <li class="nav-item " role="presentation" id="tab-all-parts-processes-li">
                            <button class="nav-link " id="tab-all-parts-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-all-parts-processes" type="button" role="tab">
                                {{ __('All Parts Processes') }}
                            </button>
                        </li>
                    @endif

                    <li class="nav-item" role="presentation" id="tab-extra-parts-processes-li">
                        <button class="nav-link" id="tab-extra-parts-processes" data-bs-toggle="tab"
                                data-bs-target="#content-extra-parts-processes" type="button" role="tab"
                                data-base-text="{{ __('Extra Parts Processes') }}">
                            {{ __('Extra Parts Processes') }}{{ ($hasExtraProcessRecords ?? false) ? ' *' : '' }}
                        </button>
                    </li>
                    @if($showLogCardTab ?? false)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-log-card" data-bs-toggle="tab" data-bs-target="#content-log-card" type="button" role="tab">{{ __('Log Card') }}</button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-bushing" data-bs-toggle="tab" data-bs-target="#content-bushing" type="button" role="tab">{{ __('Bushing Processes') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-rm-reports" data-bs-toggle="tab" data-bs-target="#content-rm-reports" type="button" role="tab">{{ __('Repair & Modification') }}</button>
                    </li>
                    @if($current_wo->instruction_id == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-std-processes" data-bs-toggle="tab" data-bs-target="#content-std-processes" type="button" role="tab">STD Processes</button>
                        </li>
                    @endif
                    @if($hasTransfers)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-transfers" data-bs-toggle="tab" data-bs-target="#content-transfers" type="button" role="tab">{{ __('Transfers') }}</button>
                        </li>
                    @endif
                </ul>
                <div id="extraPartsTabActions" class="d-none d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-success btn-sm" id="openAddExtraPartModalBtn" data-workorder-id="{{ $current_wo->id }}">
                        <i class="fas fa-plus"></i> {{ __('Add Extra Part') }}
                    </button>
                </div>
                @if(count($processParts))
                    <div id="allPartsGroupFormsTabActions" class="d-none d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm d-none" id="allPartsGroupFormsBtn"
                                data-bs-toggle="modal" data-bs-target="#groupFormsModal">
                            <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                        </button>
                    </div>
                @endif
                @if($showLogCardTab ?? false)
                <div id="logCardTabActions" class="d-none d-flex gap-2 align-items-center flex-wrap">
                    <button type="button" id="logCardEnterDataBtn" class="btn btn-success btn-sm" data-has-log="{{ $log_card ? '1' : '0' }}" data-log-card-id="{{ $log_card->id ?? '' }}">
                        <i class="fas fa-{{ $log_card ? 'edit' : 'keyboard' }}"></i> {{ $log_card ? __('Edit') : __('Enter Data') }}
                    </button>
                    <button type="button" id="logCardSaveBtn" class="btn btn-primary btn-sm d-none">
                        <i class="fas fa-save"></i> {{ __('Save') }}
                    </button>
                    <button type="button" id="logCardCancelBtn" class="btn btn-outline-secondary btn-sm d-none">{{ __('Cancel') }}</button>
                </div>
                @endif
                <div id="bushingTabActions" class="d-none d-flex gap-2 align-items-center">
                    @if($woBushing ?? null)
                        <button type="button" class="btn btn-outline-primary btn-sm open-edit-bushing-modal" data-wo-bushing-id="{{ $woBushing->id }}">
                            <i class="fas fa-edit"></i> {{ __('Update Bushings List') }}
                        </button>

                    @elseif($hasBushings ?? false)
                        <button type="submit" form="bushings-form" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> {{ __('Create Bushing List') }}
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm bushing-clear-btn">
                            <i class="fas fa-eraser"></i> {{ __('Clear All') }}
                        </button>
                    @endif
                </div>
                @if($hasTransfers)
                    <div id="transfersTabActions" class="d-none d-flex gap-2 align-items-center flex-wrap">
                        @if($transfersHasOutgoingGroup ?? false)
                            <a href="{{ route('transfers.transfersForm', $current_wo->id) }}"
                               class="btn btn-outline-info btn-sm"
                               target="_blank"
                               title="{{ __('Transfers Form for outgoing transfers from WO') }} W{{ $current_wo->number }}">
                                {{ __('Transfers Form') }} (Outgoing)
                            </a>
                        @endif
                        @foreach(($transfersIncomingGroupsWithMultiple ?? collect()) as $sourceWoId => $transfers)
                            @php
                                $sourceWo = $transfers->first()->workorderSource;
                            @endphp
                            @if($sourceWo)
                                <a href="{{ route('transfers.transfersForm', $sourceWoId) }}"
                                   class="btn btn-outline-info btn-sm"
                                   target="_blank"
                                   title="{{ __('Transfers Form for transfers from WO') }} W{{ $sourceWo->number }}">
                                    {{ __('Transfers Form') }} (From W{{ $sourceWo->number }})
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
                </div>

                <div class="tab-content" id="tdrShowTabContent" style="visibility:hidden;">
                    <div class="tab-pane fade show active" id="content-tdr" role="tabpanel">
                        @include('admin.tdrs.partials.tdr-body')
                    </div>
                    {{-- Temporary Part Processes tab content --}}
                    <div class="tab-pane fade" id="content-part-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                                <div>
                                    <h6 class="mb-0">{{ __('Part Processes') }}, {{ __('Work Order') }}: <span id="compProcessesWoNumber" class="text-primary">-</span></h6>
                                    <small class="text-muted">ITEM: <span id="compProcessesName">-</span> | IPL: <span id="compProcessesIpl">-</span> | PN: <span id="compProcessesPn">-</span> | SN: <span id="compProcessesSn">-</span></small>
                                </div>
                                <div class="d-flex gap-2 ms-md-auto flex-wrap align-items-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm d-none" id="compProcessesGroupFormsBtn"
                                            data-bs-toggle="modal" data-bs-target="#partProcessesGroupFormsModal">
                                        <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" id="compProcessesAddProcessBtn" data-tdr-id="">
                                        <i class="bi bi-plus-lg"></i> {{ __('Add Process') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="compProcessesAddVendorBtn" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                                        <i class="bi bi-plus-lg"></i> {{ __('Add Vendor') }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2 overflow-auto" id="componentProcessesTabBody" style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Click a component processes button to load.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="content-extra-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="extraProcessesTabBody" style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Click Processes in Extra Part Processes table to load.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="content-all-parts-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-1 overflow-auto" id="allPartsProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 600px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="content-extra-parts-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="extraPartsProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @if($showLogCardTab ?? false)
                    <div class="tab-pane fade" id="content-log-card" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="logCardTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="tab-pane fade" id="content-bushing" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-0 w-100" id="bushingTabBody"
                                 style="min-height: 400px; overflow: visible; padding: 0 !important; max-width: none;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="content-rm-reports" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="rmReportsTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @if($current_wo->instruction_id == 1)
                    <div class="tab-pane fade" id="content-std-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="stdProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($hasTransfers)
                    <div class="tab-pane fade" id="content-transfers" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="transfersTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div>
            <H5 class="m-3">{{__('MANUAL ')}} {{$current_wo->unit->manuals->number}} {{__('NOT COMPLETE')}}</H5>
            <div class="d-flex border" style="width: 500px">
                <div class="m-3">
                    <img class="" src="{{ $current_wo->unit->manuals->getFirstMediaBigUrl('manuals') }}" width="200" alt="Image"/>
                </div>
                <div class="text-center m-3" style="width: 250px">
                    <p><strong>{{ __('CMM:') }}</strong> {{ $current_wo->unit->manuals->number }}</p>
                    <p><strong>{{ __('Description:') }}</strong> {{ $current_wo->unit->manuals->title }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Modals and scripts: reuse from show via stack or include show's modals section --}}
    @include('admin.tdrs.partials.show-modals')
    @include('admin.tdrs.partials.show-scripts')
@endsection
