@extends('admin.master')

@section('style')
<link rel="stylesheet" href="{{ asset('css/tdrs/show.css') }}?v={{ filemtime(public_path('css/tdrs/show.css')) }}">
@endsection

@section('content')


    @if($current_wo->unit->manuals->builder)
        <div class="card bg-gradient tdr-show-card">
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
                                            title="{{ __('Documents') }}"
                                            ariaLabel="{{ __('Documents') }}"
                                            data-id="{{ $current_wo->id }}"
                                            data-number="{{ $current_wo->number }}"/>
                            <span id="pdfCountBadge" data-count-loaded="1"
                                  class="badge bg-warning rounded-pill position-absolute {{ $documentCount ? '' : 'd-none' }}"
                                  style="top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; color: black; font-size: 0.7rem; padding: 0 5px;">{{ $documentCount ?: '' }}</span>
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
                                    <x-paper-button text="In-Process Check Sheet"
                                                    href="{{ route('tdrs.inProcessCheckSheet', ['workorder'=> $current_wo->id]) }}"
                                                    title="IN-PROCESS CHECK SHEET"
                                                    target="_blank" color="outline-primary"/>
                                </div>
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
                        @php
                            $stdFormsEnabled = $current_wo->isOverhaul();
                        @endphp
                        <div class="tdr-show-paper-std ms-2 {{ $stdFormsEnabled ? '' : 'is-unavailable' }}" id="tdr-std-paper-group"
                             @if(!$stdFormsEnabled) title="STD forms are available for Overhaul only" @endif>
                                <span class="tdr-std-paper-ndt-wrap d-inline-block position-relative">
                                    <x-paper-button text="NDT STD"
                                                    :href="$stdFormsEnabled ? route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) : null"
                                                    :disabled="!$stdFormsEnabled" type="button"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['ndt'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['ndt'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-cad-wrap d-inline-block position-relative">
                                    <x-paper-button text="CAD STD"
                                                    :href="$stdFormsEnabled ? route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) : null"
                                                    :disabled="!$stdFormsEnabled" type="button"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['cad'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['cad'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-stress-wrap d-inline-block position-relative">
                                    <x-paper-button text="Stress STD"
                                                    :href="$stdFormsEnabled ? route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) : null"
                                                    :disabled="!$stdFormsEnabled" type="button"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['stress'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['stress'] }}</span>
                                    @endif
                                </span>
                            <span class="tdr-std-paper-paint-wrap d-inline-block position-relative">
                                    <x-paper-button text="Paint STD"
                                                    :href="$stdFormsEnabled ? route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) : null"
                                                    :disabled="!$stdFormsEnabled" type="button"
                                                    target="_blank" color="outline-success"
                                                    :custom-colors="['fold' => '#2fbf78', 'stroke' => '#2fbf78', 'text' => '#146c43', 'paper' => '#d5d5d5']"/>
                                    @if(($stdFormCounts['paint'] ?? 0) > 0)
                                        <span class="badge bg-success rounded-pill" style="{{ $paperCountBadgeStyle }}">{{ $stdFormCounts['paint'] }}</span>
                                    @endif
                                </span>
                        </div>
                        <div class="tdr-show-paper-divider" aria-hidden="true"></div>
                        @include('admin.tdrs.partials.prl-paper-group')

                    </div>
                </div>
            </div>

            {{-- Body: tabs (header links as tabs), TDR tab = main content --}}
            <div class="card-body p-1">


                <div id="tdrShowTabsHeader"
                     class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <ul class="nav nav-tabs mb-0" role="tablist" id="tdrShowTabList">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $initialTdrTab === 'tab-tdr' ? 'active' : '' }}" id="tab-tdr" data-bs-toggle="tab"
                                    data-bs-target="#content-tdr" type="button" role="tab">{{ __('TDR') }}</button>
                        </li>
                        {{-- Temporary Inspect tab (opened by the 📏 button on a TDR row; reuses the measurements pane in single-part mode) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-ms-inspect-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-ms-inspect' ? 'active' : '' }}" id="tab-ms-inspect" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button" role="tab">
                                <i class="bi bi-rulers"></i> {{ __('Inspect') }}
                            </button>
                        </li>
                        {{-- Temporary Part Processes tab (shown when user clicks Component Processes, hidden when switching to another tab) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-part-processes-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-part-processes' ? 'active' : '' }}" id="tab-part-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-part-processes" type="button" role="tab">
                                {{ __('Part Processes') }}
                            </button>
                        </li>
                        {{-- Temporary Extra Processes tab (shown when user clicks Processes in Extra Part Processes table) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-extra-processes-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-extra-processes' ? 'active' : '' }}" id="tab-extra-processes" data-bs-toggle="tab"
                                    data-bs-target="#content-extra-processes" type="button" role="tab">
                                {{ __('Extra Processes') }}
                            </button>
                        </li>
                        @if($showLogCardTab ?? false)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $initialTdrTab === 'tab-log-card' ? 'active' : '' }}" id="tab-log-card" data-bs-toggle="tab"
                                        data-bs-target="#content-log-card" type="button"
                                        role="tab">{{ __('Log Card') }}</button>
                            </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $initialTdrTab === 'tab-bushing' ? 'active' : '' }}" id="tab-bushing" data-bs-toggle="tab"
                                    data-bs-target="#content-bushing" type="button"
                                    role="tab">{{ __('Bushing Processes') }}</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $initialTdrTab === 'tab-rm-reports' ? 'active' : '' }}" id="tab-rm-reports" data-bs-toggle="tab"
                                    data-bs-target="#content-rm-reports" type="button"
                                    role="tab">{{ __('Repair & Modification') }}</button>
                        </li>
                        @if($hasTransfers)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $initialTdrTab === 'tab-transfers' ? 'active' : '' }}" id="tab-transfers" data-bs-toggle="tab"
                                        data-bs-target="#content-transfers" type="button"
                                        role="tab">{{ __('Transfers') }}</button>
                            </li>
                        @endif
                        @unless(auth()->user()?->roleIs(['Shipping', 'Paint', 'Machining', 'Technician', 'Team Leader']))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $initialTdrTab === 'tab-measurements' ? 'active' : '' }}" id="tab-measurements" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button"
                                    role="tab">{{ __('Measurements') }}</button>
                        </li>
                        @endunless
                        {{-- Temporary Ordered-parts tab (opened by the Ordered button in Measurements; reuses the measurements pane) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-ms-new-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-ms-new' ? 'active' : '' }}" id="tab-ms-new" data-bs-toggle="tab"
                                    data-bs-target="#content-measurements" type="button" role="tab">
                                <i class="bi bi-box-seam"></i> {{ __('Ordered Parts') }}
                            </button>
                        </li>
                        {{-- dynamic tab: shown by the Req. Bushings button, hidden on leaving --}}
                        <li class="nav-item d-none" role="presentation" id="tab-req-bushings-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-req-bushings' ? 'active' : '' }}" id="tab-req-bushings" data-bs-toggle="tab"
                                    data-bs-target="#content-req-bushings" type="button"
                                    role="tab"><i class="bi bi-nut"></i> {{ __('Required Bushings') }}</button>
                        </li>
                        {{-- dynamic tab: Final Dimensional Report (bushing fits) --}}
                        <li class="nav-item d-none" role="presentation" id="tab-final-report-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-final-report' ? 'active' : '' }}" id="tab-final-report" data-bs-toggle="tab"
                                    data-bs-target="#content-final-report" type="button"
                                    role="tab"><i class="bi bi-clipboard-data"></i> {{ __('Final Report') }}</button>
                        </li>
                        {{-- dynamic tab: F&C Table --}}
                        <li class="nav-item d-none" role="presentation" id="tab-fc-table-li">
                            <button class="nav-link {{ $initialTdrTab === 'tab-fc-table' ? 'active' : '' }}" id="tab-fc-table" data-bs-toggle="tab"
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
                                    class="btn {{ $log_card ? 'btn-primary' : 'btn-success' }} btn-sm"
                                    data-has-log="{{ $log_card ? '1' : '0' }}"
                                    data-log-card-id="{{ $log_card->id ?? '' }}"
                                    data-readonly="{{ ($logCardTdrAccess['read_only'] ?? false) ? '1' : '0' }}"
                                    data-readonly-message="{{ $logCardTdrAccess['message'] ?? '' }}"
                                    @disabled($logCardTdrAccess['read_only'] ?? false)
                                    title="{{ $logCardTdrAccess['message'] ?? '' }}">
                                <i class="fas fa-{{ $log_card ? 'sync-alt' : 'keyboard' }}"></i> {{ $log_card ? __('Update Log Card') : __('Create Log Card') }}
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

                <div class="tab-content" id="tdrShowTabContent">
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-tdr' ? 'show active' : '' }}" id="content-tdr" role="tabpanel">
                        @include('admin.tdrs.partials.tdr-body')
                    </div>
                    {{-- Temporary Part Processes tab content --}}
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-part-processes' ? 'show active' : '' }}" id="content-part-processes" role="tabpanel">
                        <div class="card bg-gradient">
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
                                <div id="partProcessesHeaderActions">
                                    <div data-process-header-toolbar></div>
                                    <button type="button" class="btn btn-outline-primary btn-sm text-nowrap"
                                            onclick="document.getElementById('tab-extra-parts-processes')?.click()">{{ __('Fix fuckup') }}{{ ($hasExtraProcessRecords ?? false) ? ' *' : '' }}</button>
                                </div>
                            </div>
                            <div class="card-body p-2" id="componentProcessesTabBody">
                                <div
                                    class="text-center py-5 text-muted">{{ __('Click a component processes button to load.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-extra-processes' ? 'show active' : '' }}" id="content-extra-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="extraProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div
                                    class="text-center py-5 text-muted">{{ __('Click Processes in Extra Part Processes table to load.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-extra-parts-processes' ? 'show active' : '' }}" id="content-extra-parts-processes" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2 overflow-auto" id="extraPartsProcessesTabBody"
                                 style="height: calc(100vh - 280px); min-height: 400px;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @if($showLogCardTab ?? false)
                        <div class="tab-pane fade {{ $initialTdrTab === 'tab-log-card' ? 'show active' : '' }}" id="content-log-card" role="tabpanel">
                            <div class="card bg-gradient h-100">
                                <div class="card-body p-2" id="logCardTabBody"
                                     style="height: calc(100vh - 218px); min-height: 400px; overflow: hidden; display: flex; flex-direction: column;">
                                    <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-bushing' ? 'show active' : '' }}" id="content-bushing" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-0 w-100" id="bushingTabBody"
                                 style="padding: 0 !important; max-width: none;">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-rm-reports' ? 'show active' : '' }}" id="content-rm-reports" role="tabpanel">
                        <div class="card bg-gradient h-100">
                            <div class="card-body p-2" id="rmReportsTabBody">
                                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                    @if($hasTransfers)
                        <div class="tab-pane fade {{ $initialTdrTab === 'tab-transfers' ? 'show active' : '' }}" id="content-transfers" role="tabpanel">
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
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-measurements' ? 'show active' : '' }}" id="content-measurements" role="tabpanel">
                        @include('admin.measurements._tab', ['wo' => $current_wo])
                    </div>
                    {{-- Required Bushings (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-req-bushings' ? 'show active' : '' }}" id="content-req-bushings" role="tabpanel">
                        <iframe id="req-bushings-frame" src="about:blank"
                                style="width:100%;height:calc(100vh - 220px);border:0;background:transparent"></iframe>
                    </div>
                    {{-- Final Dimensional Report (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-final-report' ? 'show active' : '' }}" id="content-final-report" role="tabpanel">
                        <iframe id="final-report-frame" src="about:blank"
                                style="width:100%;height:calc(100vh - 220px);border:0;background:transparent"></iframe>
                    </div>
                    {{-- F&C Table (dynamic, opened from Measurements) --}}
                    <div class="tab-pane fade {{ $initialTdrTab === 'tab-fc-table' ? 'show active' : '' }}" id="content-fc-table" role="tabpanel">
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
    @include('admin.tdrs.partials.assy-configuration-modal')
    @include('admin.tdrs.partials.group-process-modal')
    @include('admin.tdrs.partials.show-scripts')
    <script src="{{ asset('js/traveler-process-templates.js') }}?v={{ filemtime(public_path('js/traveler-process-templates.js')) }}" defer></script>

@endsection
