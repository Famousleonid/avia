@extends('admin.master')

@section('content')
    <style>
        .text-center { text-align: center; align-content: center; }
        .fs-8 { font-size: 0.8rem; }
        .fs-7 { font-size: 0.7rem; }
        .fs-75 { font-size: 0.75rem; }
        #tdr_inspect_Table thead { position: sticky; top: 0; z-index: 10; }
        #tdr_inspect_Table thead th { background-color: #030334 !important; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        #tdr__Table thead th { background-color: #030334 !important; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        .order-modal .modal-dialog { max-height: 70vh; }
        .order-modal .modal-content { max-height: 70vh; display: flex; flex-direction: column; }
        .order-modal .modal-header { flex-shrink: 0; }
        .order-modal .order-modal-table-wrapper { overflow-y: auto; flex: 1; min-height: 0; }
        .order-modal .order-modal-table thead { position: sticky; top: 0; z-index: 10; }
        .order-modal .order-modal-table thead th { background-color: #030334 !important; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); }
        .img-icon:hover { cursor: pointer; }
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
        #addPartProcessesModal, #editTdrProcessModal { z-index: 1080 !important; }
        #addPartProcessesModal ~ .modal-backdrop, #editTdrProcessModal ~ .modal-backdrop { z-index: 1075 !important; }
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
            {{-- Header show2: link to main, PDF Library, x-paper buttons --}}
            <div class="card-header m-1 shadow">
                <div class="d-flex text-center align-items-center">
                    <div style="width: 100px;">
                        <h5 class="text-success-emphasis ps-1">{{__('WO')}}
                            <a class="text-success-emphasis" href="{{ route('mains.show', $current_wo->id) }}">{{$current_wo->number}}</a>
                        </h5>
                    </div>
                    <div class="ps-2 d-flex align-items-center">
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
                        {{-- x-paper buttons (same conditions as tdrs.show) --}}
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
                                    <div class="position-relative d-inline-block">
                                        <x-paper-button text="PRL" href="{{ route('tdrs.prlForm', ['id' => $current_wo->id]) }}" target="_blank" />
                                        <span class="badge bg-success rounded-pill" style="position: absolute; top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">{{ count($prl_parts) }}</span>
                                    </div>
                                @endif
                                </div>
                                @if(count($processParts) > 1)
                                    <div id="groupProcessFormsHeaderBtn" class="d-none pt-1">
                                        <x-paper-button-multy text="Group Process Forms" color="outline-primary" size="landscape" width="100"
                                                              ariaLabel="Group Process Forms" data-bs-toggle="modal" data-bs-target="#groupFormsModal" />
                                    </div>
                                @endif

                            </div>
                            <div class="d-flex flex-wrap gap-2 ms-2">
                                @if($current_wo->instruction_id == 1 && $hasNdtComponents)
                                    <x-paper-button text="NDT STD" href="{{ route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                @endif
                                @if($current_wo->instruction_id == 1 && $hasCadComponents)
                                    <x-paper-button text="CAD STD" href="{{ route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                @endif
                                @if($current_wo->instruction_id == 1 && $hasStressComponents)
                                    <x-paper-button text="Stress STD" href="{{ route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                @endif
                                @if($hasPaintComponents)
                                    <x-paper-button text="Paint STD" href="{{ route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                @endif
                                @if($log_card)
                                    <x-paper-button text="Log Card" href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}" target="_blank" color="outline-primary" />
                                @endif
                                @if($woBushing)
                                    <x-paper-button text="Bushing SP Form" href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}" target="_blank" color="outline-primary" />
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Body: tabs (header links as tabs), TDR tab = main content --}}
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist" id="show2TabList">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-tdr" data-bs-toggle="tab" data-bs-target="#content-tdr" type="button" role="tab">{{ __('TDR') }}</button>
                    </li>
                    {{-- Temporary Part Processes tab (shown when user clicks Component Processes, hidden when switching to another tab) --}}
                    <li class="nav-item d-none" role="presentation" id="tab-part-processes-li">
                        <button class="nav-link" id="tab-part-processes" data-bs-toggle="tab" data-bs-target="#content-part-processes" type="button" role="tab">
                            {{ __('Part Processes') }}
                        </button>
                    </li>
                    @if(count($processParts))
                        <li class="nav-item" role="presentation" id="tab-all-parts-processes-li">
                            <button class="nav-link" id="tab-all-parts-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-all-parts-processes" type="button" role="tab">
                                {{ __('All Parts Processes') }}
                            </button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ route('extra_processes.show_all', ['id'=>$current_wo->id]) }}">{{ __('Extra Parts Processes') }}</a>
                    </li>
                    @foreach($manuals as $manual)
                        @if($manual->id == $manual_id)
                            @foreach($planes as $plane)
                                @if($plane->id == $manual->planes_id)
                                    @if(!str_contains($plane->type ?? '', 'ATR'))
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" href="{{ route('log_card.show', ['id' => $current_wo->id]) }}">{{ __('Log Card') }}</a>
                                        </li>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ route('wo_bushings.show', ['wo_bushing' => $current_wo->id]) }}">{{ __('Bushing Processes') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ route('rm_reports.show', ['rm_report' => $current_wo->id]) }}">{{ __('Repair & Modification Record') }}</a>
                    </li>
                    @if($current_wo->instruction_id == 1)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" href="{{ route('ndt-cad-csv.index', $current_wo->id) }}">STD Processes</a>
                        </li>
                    @endif
                    @if($hasTransfers)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" href="{{ route('transfers.show', $current_wo->id) }}">Transfers</a>
                        </li>
                    @endif
                </ul>

                <div class="tab-content">
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
                                <div class="d-flex gap-2 ms-md-auto">
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

                    <div class="tab-pane fade" id="content-all-parts-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="allPartsProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
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
    @include('admin.tdrs.partials.show2-modals')
    @include('admin.tdrs.partials.show2-scripts')
@endsection
