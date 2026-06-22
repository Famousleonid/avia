@extends('admin.master')

@section('content')
    <style>
        .text-center {
            text-align: center;
            align-content: center;
        }

        .fs-8 {
            font-size: 0.8rem;
        }

        .fs-7 {
            font-size: 0.7rem;
        }

        .fs-75 {
            font-size: 0.75rem;
        }

        #tdr_inspect_Table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #tdr_inspect_Table thead th {
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        #tdr__Table thead th {
            background-color: #030334 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .order-modal .modal-dialog {
            max-height: 85vh;
        }

        .order-modal .modal-content {
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }

        .order-modal .modal-header {
            flex-shrink: 0;
        }

        .order-modal .order-modal-table-wrapper {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }

        .order-modal .order-modal-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .order-modal .order-modal-table thead th {
            background-color: #030334 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .img-icon:hover {
            cursor: pointer;
        }

        .tdr-show-back-btn {
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

        .tdr-show-back-arrow {
            font-size: 1.35rem;
            line-height: 1;
            font-weight: 900;
            color: #fff;
        }

        .tdr-show-back-text {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.02;
            text-align: left;
        }

        .tdr-show-back-text .t1 {
            font-size: 1rem;
            opacity: .95;
        }

        .tdr-show-back-text .t2 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .tdr-show-paper-strip {
            align-items: flex-start !important;
        }

        .tdr-pdf-paper-wrap {
            display: flex;
            align-items: flex-start;
            line-height: 0;
        }

        .tdr-pdf-paper-wrap .paper-btn {
            display: block;
        }

        .tdr-show-paper-main,
        .tdr-show-paper-std,
        .tdr-show-paper-extra {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .tdr-show-paper-divider {
            align-self: stretch;
            width: 1px;
            min-height: 76px;
            margin: 2px .55rem;
            background: rgba(255, 255, 255, .22);
        }

        .tdr-show-paper-std {
            gap: .15rem;
        }

        @media (max-width: 1479.98px) {
            .tdr-show-paper-strip {
                column-gap: .35rem;
                margin-left: .35rem !important;
                padding-left: .25rem !important;
            }

            .tdr-pdf-paper-wrap {
                margin-right: .5rem !important;
            }

            .tdr-show-paper-main,
            .tdr-show-paper-std,
            .tdr-show-paper-extra {
                gap: .25rem;
            }

            .tdr-show-paper-divider {
                min-height: 56px;
                margin-left: .25rem;
                margin-right: .25rem;
            }

            .tdr-show-paper-main .me-3,
            .tdr-show-paper-std,
            .tdr-show-paper-extra {
                margin-right: .35rem !important;
            }

            .tdr-show-paper-strip .paper-btn {
                display: inline-block;
                zoom: .7;
            }

            .tdr-show-paper-strip .badge {
                min-width: 14px !important;
                height: 14px !important;
                font-size: .49rem !important;
                padding: 0 3px !important;
                top: -4px !important;
                left: 1px !important;
            }
        }

        @media (max-width: 1280px) {
            .tdr-show-paper-wo {
                display: none !important;
            }
        }

        #pdfCountBadge {
            align-items: center;
            color: #000;
            display: flex;
            font-size: .7rem;
            height: 20px;
            justify-content: center;
            left: 2px;
            min-width: 20px;
            padding: 0 5px;
            right: auto;
            top: -5px;
        }

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
            margin-bottom: 0;
            z-index: 3;
        }

        #tdrShowTabList .nav-link.active::after {
            background: var(--tdr-tabs-bg);
            bottom: 0;
            content: "";
            height: 1px;
            left: 1px;
            position: absolute;
            right: 1px;
        }

        /* Select2 in modals - ensure dropdown appears above modal (Bootstrap modal z-index: 1055) */
        #componentInspectionModal .select2-container--open,
        .select2-container--open {
            z-index: 1065 !important;
        }

        .select2-dropdown {
            z-index: 1065 !important;
        }

        #componentInspectionModal .modal-body {
            overflow-x: visible;
            overflow-y: auto;
        }

        /* Select2 selection — как инпуты (form-control) в модалке */
        #componentInspectionModal .select2-container .select2-selection--single,
        #componentInspectionModal .select2-container .select2-selection--multiple {
            background-color: rgba(33, 37, 41, .85) !important;
            border: 1px solid rgba(255, 255, 255, .15) !important;
            border-radius: 0.375rem !important;
            min-height: 38px !important;
            padding: 0.375rem 0.75rem !important;
        }

        #componentInspectionModal .select2-container .select2-selection__rendered {
            color: rgba(248, 249, 250, .95) !important;
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

        /* Edit Part Process and related modals (iframe) - ensure on top */
        #editTdrProcessModal, #editExtraProcessModal, #addExtraProcessModal, #addExtraPartModal, #editBushingModal, #addProcessesModal, #addPartModal, #changeSnModal {
            z-index: 1080 !important;
        }

        #addProcessesModal.modal.show, #addPartModal.modal.show {
            z-index: 1090 !important;
        }

        #editTdrProcessModal ~ .modal-backdrop, #editExtraProcessModal ~ .modal-backdrop, #addExtraProcessModal ~ .modal-backdrop, #addExtraPartModal ~ .modal-backdrop, #editBushingModal ~ .modal-backdrop, #addProcessesModal ~ .modal-backdrop, #addPartModal ~ .modal-backdrop, #changeSnModal ~ .modal-backdrop {
            z-index: 1075 !important;
        }

        /* Part / Extra processes (AJAX block) — заметнее подсветка строки при наведении */
        .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th,
        .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th {
            --bs-table-hover-color: var(--dir-text);
            --bs-table-hover-bg: var(--dir-row-hover);
            background-color: var(--dir-row-hover) !important;
            color: var(--dir-text) !important;
        }

        html[data-bs-theme="dark"] .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th,
        html[data-bs-theme="dark"] .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > td,
        html[data-bs-theme="dark"] .extra-processes-modal-body .sortable-table.table-hover > tbody > tr:hover > th {
            --bs-table-hover-bg: var(--dir-row-hover);
            background-color: var(--dir-row-hover) !important;
            color: var(--dir-text) !important;
        }

        /* Traveler-блок: фон без table-secondary, чтобы hover совпадал с остальными строками */
        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td,
        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th {
            background-color: rgba(108, 117, 125, 0.12);
            box-shadow: none;
        }

        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td:first-child,
        .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th:first-child {
            box-shadow: inset 3px 0 0 rgba(108, 117, 125, 0.45);
        }

        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td,
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th {
            background-color: rgba(255, 255, 255, 0.06);
            box-shadow: none;
        }

        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > td:first-child,
        html[data-bs-theme="dark"] .processes-modal-body .sortable-table > tbody > tr.traveler-block-row > th:first-child {
            box-shadow: inset 3px 0 0 rgba(173, 181, 189, 0.5);
        }

        .processes-modal-body .processes-toolbar {
            display: flex;
            justify-content: flex-end;
            margin: 0 1rem .35rem 0;
        }

        .processes-modal-body .process-action-col {
            width: 78px;
            min-width: 78px;
            max-width: 78px;
            white-space: nowrap;
        }

        .processes-modal-body .process-action-cell {
            white-space: nowrap;
            width: 78px;
            min-width: 78px;
            max-width: 78px;
            padding-left: .15rem !important;
            padding-right: .15rem !important;
        }

        .processes-modal-body .process-action-cell .btn {
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: .05rem .2rem !important;
            margin: 0 !important;
            line-height: 1;
        }

        .processes-modal-body .form-link,
        .processes-modal-body .travel-form-link {
            padding: .05rem .35rem !important;
            line-height: 1.15;
        }

        #content-part-processes .card {
            height: calc(100dvh - 216px);
            min-height: 0;
            overflow: hidden;
        }

        #componentProcessesTabBody {
            height: calc(100dvh - 275px) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }

        #componentProcessesTabBody .processes-modal-body {
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            height: 100%;
            min-height: 0;
        }

        #componentProcessesTabBody .processes-modal-body > .table-wrapper {
            height: auto !important;
            min-height: 0;
            max-height: none !important;
            margin-right: 0 !important;
            overflow: auto !important;
            padding-bottom: 0;
            box-sizing: border-box;
        }
    </style>

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
                    <div class="ps-2 d-flex align-items-start ms-3 tdr-show-paper-strip">
                        <div class="me-4 position-relative tdr-pdf-paper-wrap">
                            <x-paper-button text="PDF"
                                            color="outline-warning"
                                            class="open-pdf-modal"
                                            title="{{ __('PDF Library') }}"
                                            ariaLabel="{{ __('PDF Library') }}"
                                            data-id="{{ $current_wo->id }}"
                                            data-number="{{ $current_wo->number }}"/>
                            <span id="pdfCountBadge"
                                  class="badge bg-warning rounded-pill position-absolute d-none"
                                  style="top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; color: black; font-size: 0.7rem; padding: 0 5px;"></span>
                        </div>
                        {{-- x-paper buttons --}}
                        @php
                            $paperCountBadgeStyle = 'position: absolute; top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;';
                        @endphp

                        <div class="tdr-show-paper-main ms-1">

                            <div class="me-3 d-flex flex-wrap tdr-show-paper-wo">

                                <x-paper-button text="WO Box Title"
                                                href="{{ route('tdrs.wo_BoxTitle', ['id'=> $current_wo->id]) }}"
                                                target="_blank" color="outline-info"/>
                                <x-paper-button text="WO Process Sheet"
                                                href="{{ route('tdrs.woProcessForm', ['id'=> $current_wo->id]) }}"
                                                target="_blank" color="outline-info"/>
                            </div>

                            <div class="me-3 d-flex flex-wrap">

                                <div class="position-relative d-inline-block">
                                    <x-paper-button text="TDR Form"
                                                    href="{{ route('tdrs.tdrForm', ['id'=> $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    @if(($tdrFormRowsCount ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $tdrFormRowsCount }}</span>
                                    @endif
                                </div>

                                <div class="position-relative d-inline-block">
                                    <x-paper-button text="R&M Form"
                                                    href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    @if(($rmFormRowsCount ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $rmFormRowsCount }}</span>
                                    @endif
                                </div>

                                @if(!$hasProcessFormTdrs)
                                    <div class="position-relative d-inline-block">
                                        <x-paper-button text="SP Form"
                                                        href="{{ route('tdrs.specProcessFormEmp', ['id'=> $current_wo->id]) }}"
                                                        target="_blank" color="outline-primary"/>
                                        @if(($spFormColumnsCount ?? 0) > 0)
                                            <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $spFormColumnsCount }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="position-relative d-inline-block">
                                        <x-paper-button text="SP Form"
                                                        href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                                                        target="_blank" color="outline-primary"/>
                                        @if(($spFormColumnsCount ?? 0) > 0)
                                            <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $spFormColumnsCount }}</span>
                                        @endif
                                    </div>
                                @endif
                                <span id="bushingSpFormHeaderBtn">
                                    @if($woBushing && (($bushingSpFormColumnsCount ?? 0) > 0))
                                        <span class="position-relative d-inline-block">
                                            <x-paper-button text="Bushing Form"
                                                            href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}"
                                                            target="_blank" color="outline-primary"/>
                                            @if(($bushingSpFormColumnsCount ?? 0) > 0)
                                                <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $bushingSpFormColumnsCount }}</span>
                                            @endif
                                        </span>
                                    @endif
                                </span>

                                <span id="logCardFormPaperWrap" class="{{ $log_card ? '' : 'd-none' }}">
                                    <x-paper-button text="Log Card"
                                                    href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                </span>
                                <span id="serviceBulletinLogPaperWrap">
                                    <x-paper-button text="SB Form"
                                                    href="{{ route('tdrs.serviceBulletinLog', ['workorder' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                </span>
                            </div>
                        </div>
                        <div id="extraGroupFormsHeaderBtn" class="d-none pt-1 me-3">
                            <x-paper-button-multy text="Group Process Forms" color="outline-primary"
                                                  size="landscape" width="100"
                                                  ariaLabel="Group Process Forms" data-bs-toggle="modal"
                                                  data-bs-target="#extraGroupFormsModal"/>
                        </div>
                        <div class="tdr-show-paper-divider" aria-hidden="true"></div>
                        <div class="tdr-show-paper-std ms-2" id="tdr-std-paper-group">
                                <span class="tdr-std-paper-ndt-wrap d-inline-block position-relative">
                                    <x-paper-button text="NDT STD"
                                                    href="{{ route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['ndt'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['ndt'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-cad-wrap d-inline-block position-relative">
                                    <x-paper-button text="CAD STD"
                                                    href="{{ route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['cad'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['cad'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-stress-wrap d-inline-block position-relative">
                                    <x-paper-button text="Stress STD"
                                                    href="{{ route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['stress'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['stress'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-paint-wrap d-inline-block position-relative">
                                    <x-paper-button text="Paint STD"
                                                    href="{{ route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['paint'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['paint'] }}</span>
                                    @endif
                                </span>
                        </div>
                        <div class="tdr-show-paper-divider" aria-hidden="true"></div>
                        <div class="tdr-show-paper-extra ms-2">
                            @if(($kitPrlCount ?? 0) > 0)
                                <div class="position-relative d-inline-block ">
                                    <x-paper-button text="KIT"
                                                    href="{{ route('tdrs.kitForm', ['id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    <span class="badge bg-success rounded-pill"
                                          style="{{ $paperCountBadgeStyle }}">{{ $kitPrlCount }}</span>
                                </div>
                            @endif
                            @if(count($prl_parts) > 0)
                                <div class="position-relative d-inline-block ">
                                    <x-paper-button text="PRL"
                                                    href="{{ route('tdrs.prlForm', ['id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    <span class="badge bg-success rounded-pill"
                                          style="{{ $paperCountBadgeStyle }}">{{ count($prl_parts) }}</span>
                                </div>
                            @endif
                            @if(($bushingPrlCount ?? 0) > 0)
                                <div class="position-relative d-inline-block ">
                                    <x-paper-button text="Bush PRL"
                                                    href="{{ route('tdrs.bushPrlForm', ['id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    <span class="badge bg-success rounded-pill"
                                          style="{{ $paperCountBadgeStyle }}">{{ $bushingPrlCount }}</span>
                                </div>
                            @endif
                        </div>

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

                    .tdr-tabs-loading-dot:nth-child(2) {
                        animation-delay: .12s;
                    }

                    .tdr-tabs-loading-dot:nth-child(3) {
                        animation-delay: .24s;
                    }

                    @keyframes tdrTabsDotWave {
                        0%, 80%, 100% {
                            transform: translateY(0);
                            opacity: .45;
                        }
                        40% {
                            transform: translateY(-4px);
                            opacity: 1;
                        }
                    }
                </style>
                <div id="tdrShowTabsLoading" class="tdr-tabs-loading">
                    <span class="tdr-tabs-loading-dots" aria-label="Loading tabs">
                        <span class="tdr-tabs-loading-dot"></span>
                        <span class="tdr-tabs-loading-dot"></span>
                        <span class="tdr-tabs-loading-dot"></span>
                    </span>
                </div>
                <div id="tdrShowTabsHeader"
                     class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"
                     style="visibility:hidden;">
                    <ul class="nav nav-tabs mb-0" role="tablist" id="tdrShowTabList">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-tdr" data-bs-toggle="tab"
                                    data-bs-target="#content-tdr" type="button" role="tab">{{ __('TDR') }}</button>
                        </li>
                        {{-- Temporary Inspect tab (opened by the 📏 button on a TDR row; reuses the measurements pane in single-part mode) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-ms-inspect-li">
                            <button class="nav-link" id="tab-ms-inspect" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button" role="tab">
                                <i class="bi bi-rulers"></i> {{ __('Inspect') }}
                            </button>
                        </li>
                        {{-- Temporary Part Processes tab (shown when user clicks Component Processes, hidden when switching to another tab) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-part-processes-li">
                            <button class="nav-link" id="tab-part-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-part-processes" type="button" role="tab">
                                {{ __('Part Processes') }}
                            </button>
                        </li>
                        {{-- Temporary Extra Processes tab (shown when user clicks Processes in Extra Part Processes table) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-extra-processes-li">
                            <button class="nav-link" id="tab-extra-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-extra-processes" type="button" role="tab">
                                {{ __('Extra Processes') }}
                            </button>
                        </li>
                        @if($showLogCardTab ?? false)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-log-card" data-bs-toggle="tab"
                                        data-bs-target="#content-log-card" type="button"
                                        role="tab">{{ __('Log Card') }}</button>
                            </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-bushing" data-bs-toggle="tab"
                                    data-bs-target="#content-bushing" type="button"
                                    role="tab">{{ __('Bushing Processes') }}</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-rm-reports" data-bs-toggle="tab"
                                    data-bs-target="#content-rm-reports" type="button"
                                    role="tab">{{ __('Repair & Modification') }}</button>
                        </li>
                        @if($hasTransfers)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-transfers" data-bs-toggle="tab"
                                        data-bs-target="#content-transfers" type="button"
                                        role="tab">{{ __('Transfers') }}</button>
                            </li>
                        @endif
                        @unless(auth()->user()?->roleIs(['Shipping', 'Paint', 'Machining']))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-measurements" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button"
                                    role="tab">{{ __('Measurements') }}</button>
                        </li>
                        @endunless
                        {{-- Temporary Ordered-parts tab (opened by the Ordered button in Measurements; reuses the measurements pane) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-ms-new-li">
                            <button class="nav-link" id="tab-ms-new" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button" role="tab">
                                <i class="bi bi-box-seam"></i> {{ __('Ordered Parts') }}
                            </button>
                        </li>
                        {{-- dynamic tab: shown by the Req. Bushings button, hidden on leaving --}}
                        <li class="nav-item d-none" role="presentation" id="tab-req-bushings-li">
                            <button class="nav-link" id="tab-req-bushings" data-bs-toggle="tab"
                                    data-bs-target="#content-req-bushings" type="button"
                                    role="tab"><i class="bi bi-nut"></i> {{ __('Required Bushings') }}</button>
                        </li>
                        {{-- dynamic tab: Final Dimensional Report (bushing fits) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-final-report-li">
                            <button class="nav-link" id="tab-final-report" data-bs-toggle="tab"
                                    data-bs-target="#content-final-report" type="button"
                                    role="tab"><i class="bi bi-clipboard-data"></i> {{ __('Final Report') }}</button>
                        </li>
                        {{-- dynamic tab: F&C Table --}}
                        <li class="nav-item d-none" role="presentation" id="tab-fc-table-li">
                            <button class="nav-link" id="tab-fc-table" data-bs-toggle="tab"
                                    data-bs-target="#content-fc-table" type="button"
                                    role="tab">&#128438; {{ __('F&C Table') }}</button>
                        </li>
                    </ul>
                    <div id="ms-fc-btn-wrap" class="d-none align-items-center gap-1 ms-auto" style="margin-right:50px">
                        <button type="button" id="ms-fc-table-btn" class="btn btn-outline-secondary btn-sm" style="font-size:11px"
                                data-url="{{ route('workorders.measurements.fc-table', $current_wo->id) }}">
                            &#128438; F&amp;C Table
                        </button>
                        <button type="button" id="ms-req-bush-btn" class="btn btn-outline-secondary btn-sm" style="font-size:11px"
                                title="Required bushings — P/N per position from bore measurements">
                            <i class="bi bi-nut"></i> Req. Bushings
                        </button>
                        <button type="button" id="ms-final-report-btn" class="btn btn-outline-secondary btn-sm" style="font-size:11px"
                                title="Final dimensional report — bore / bushing OD finals and resulting fit">
                            <i class="bi bi-clipboard-data"></i> Final Report
                        </button>
                    </div>
                    <div id="partProcessesShortcutActions" class="d-none d-flex gap-2 align-items-center ms-auto"
                         style="margin-right: 50px;">
                        <button type="button"
                                class="btn btn-outline-primary btn-sm"
                                id="tab-extra-parts-processes"
                                data-process-shortcut-target="#content-extra-parts-processes"
                                data-base-text="{{ __('Fix fuckup') }}">
                            {{ __('Fix fuckup') }}{{ ($hasExtraProcessRecords ?? false) ? ' *' : '' }}
                        </button>
                    </div>
                    @if($showLogCardTab ?? false)
                        <div id="logCardTabActions" class="d-none d-flex gap-2 align-items-center flex-wrap"
                             style="margin-right: 100px;">
                            <button type="button"
                                    id="logCardEnterDataBtn"
                                    class="btn {{ $log_card ? 'btn-danger' : 'btn-success' }} btn-sm"
                                    data-has-log="{{ $log_card ? '1' : '0' }}"
                                    data-log-card-id="{{ $log_card->id ?? '' }}"
                                    data-readonly="{{ ($logCardTdrAccess['read_only'] ?? false) ? '1' : '0' }}"
                                    data-readonly-message="{{ $logCardTdrAccess['message'] ?? '' }}"
                                    @disabled($logCardTdrAccess['read_only'] ?? false)
                                    title="{{ $logCardTdrAccess['message'] ?? '' }}">
                                <i class="fas fa-{{ $log_card ? 'undo' : 'keyboard' }}"></i> {{ $log_card ? __('Reset Log Card') : __('Create Log Card') }}
                            </button>
                            <button type="button" id="logCardSaveBtn" class="btn btn-primary btn-sm d-none">
                                <i class="fas fa-save"></i> {{ __('Save') }}
                            </button>
                            <button type="button" id="logCardCancelBtn"
                                    class="btn btn-outline-secondary btn-sm d-none">{{ __('Cancel') }}</button>
                        </div>
                    @endif
                    <div id="bushingTabActions" class="d-none d-flex gap-2 align-items-center">
                        @if($woBushing ?? null)
                            <button type="button" class="btn btn-outline-primary btn-sm open-edit-bushing-modal"
                                    data-wo-bushing-id="{{ $woBushing->id }}">
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
                                <div class="d-flex flex-wrap align-items-baseline gap-2">
                                    <h6 class="mb-0">{{ __('Part Processes') }}, {{ __('Work Order') }}: <span
                                            id="compProcessesWoNumber" class="text-primary">-</span></h6>
                                    <small class="text-muted">
                                        ITEM: <span id="compProcessesName" class="text-white">-</span> | IPL: <span
                                            id="compProcessesIpl">-</span> | PN: <span id="compProcessesPn">-</span> |
                                        SN: <span id="compProcessesSn">-</span>
                                    </small>
                                </div>
                            </div>
                            <div class="card-body p-2" id="componentProcessesTabBody">
                                <div
                                    class="text-center py-5 text-muted">{{ __('Click a component processes button to load.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="content-extra-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="extraProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div
                                    class="text-center py-5 text-muted">{{ __('Click Processes in Extra Part Processes table to load.') }}</div>
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
                                <div class="card-body p-2" id="logCardTabBody"
                                     style="height: calc(100vh - 218px); min-height: 400px; overflow: hidden; display: flex; flex-direction: column;">
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
                            <div class="card-body p-2" id="rmReportsTabBody"
                                 style="height: calc(100dvh - 280px); min-height: 0; overflow: hidden;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
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
                    @unless(auth()->user()?->roleIs(['Shipping', 'Paint', 'Machining']))
                    {{-- Measurements tab — доступно всем ролям, кроме Shipping/Paint/Machining --}}
                    <div class="tab-pane fade" id="content-measurements" role="tabpanel">
                        @include('admin.measurements._tab', ['wo' => $current_wo])
                    </div>
                    {{-- Required Bushings (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade" id="content-req-bushings" role="tabpanel">
                        <iframe id="req-bushings-frame" src="about:blank"
                                style="width:100%;height:calc(100vh - 220px);border:0;background:transparent"></iframe>
                    </div>
                    {{-- Final Dimensional Report (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade" id="content-final-report" role="tabpanel">
                        <iframe id="final-report-frame" src="about:blank"
                                style="width:100%;height:calc(100vh - 220px);border:0;background:transparent"></iframe>
                    </div>
                    {{-- F&C Table (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade" id="content-fc-table" role="tabpanel">
                        <iframe id="fc-table-frame" src="about:blank"
                                style="width:100%;height:calc(100vh - 220px);border:0;background:transparent"></iframe>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // leaving a dynamic tab hides it again
                            var dynTabs = {
                                'tab-req-bushings': 'tab-req-bushings-li',
                                'tab-final-report': 'tab-final-report-li',
                                'tab-fc-table':     'tab-fc-table-li',
                                'tab-ms-inspect':   'tab-ms-inspect-li',
                                'tab-ms-new':       'tab-ms-new-li'
                            };
                            document.querySelectorAll('#tdrShowTabList .nav-link').forEach(function (btn) {
                                btn.addEventListener('shown.bs.tab', function (e) {
                                    Object.keys(dynTabs).forEach(function (tabId) {
                                        if (e.target.id !== tabId) {
                                            document.getElementById(dynTabs[tabId])?.classList.add('d-none');
                                        }
                                    });
                                });
                            });

                            // F&C Table opens as a dynamic tab (same pattern as the report tabs)
                            document.getElementById('ms-fc-table-btn')?.addEventListener('click', function () {
                                var li    = document.getElementById('tab-fc-table-li');
                                var btn   = document.getElementById('tab-fc-table');
                                var frame = document.getElementById('fc-table-frame');
                                if (!li || !btn || !frame) { window.open(this.dataset.url, '_blank'); return; }
                                frame.src = this.dataset.url;
                                li.classList.remove('d-none');
                                bootstrap.Tab.getOrCreateInstance(btn).show();
                            });
                        });
                    </script>
                    @endunless
                </div>
            </div>
        </div>
    @else
        <div>
            <H5 class="m-3">{{__('MANUAL ')}} {{$current_wo->unit->manuals->number}} {{__('NOT COMPLETE')}}</H5>
            <div class="d-flex border" style="width: 500px">
                <div class="m-3">
                    <img class="" src="{{ $current_wo->unit->manuals->getFirstMediaBigUrl('manuals') }}" width="200"
                         alt="Image"/>
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
    @include('admin.tdrs.partials.group-process-modal')
    @include('admin.tdrs.partials.show-scripts')

@endsection
