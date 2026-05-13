@extends('admin.master')

@section('content')
    @php
        $manualTabKeys = ['components', 'parts', 'processes', 'std', 'sb'];
        $manualShowTab = in_array(request('tab'), $manualTabKeys, true) ? request('tab') : 'components';

        $manualScrollPartId = null;
        if (request()->filled('part_id') && ctype_digit((string) request('part_id'))) {
            $pid = (int) request('part_id');
            if ($parts->pluck('id')->contains($pid)) {
                $manualScrollPartId = $pid;
                $manualShowTab = 'parts';
            }
        }

        $manualUrlParts = route('manuals.show', ['manual' => $cmm, 'tab' => 'parts']);
        $manualUrlProcesses = route('manuals.show', ['manual' => $cmm, 'tab' => 'processes']);
        $manualUrlSb = route('manuals.show', ['manual' => $cmm, 'tab' => 'sb']);
        $sbRequirementOptions = [
            '' => 'None',
            \App\Models\ManualServiceBulletin::REQUIREMENT_OPTIONAL => 'Optional',
            \App\Models\ManualServiceBulletin::REQUIREMENT_RECOMMENDED => 'Recommended',
            \App\Models\ManualServiceBulletin::REQUIREMENT_MANDATORY => 'Mandatory',
        ];
    @endphp
    <style>
        /* ÐžÐ±Ñ‰Ð¸Ðµ Ð½Ð°ÑÑ‚Ñ€Ð¾Ð¹ÐºÐ¸ Ñ‚Ð°Ð±Ð»Ð¸Ñ† */
        .table{
            align-content: center;
        }
        /* Ð¤Ð¸ÐºÑÐ¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ð°Ñ Ñ€Ð°ÑÐºÐ»Ð°Ð´ÐºÐ° â€” ÑˆÐ¸Ñ€Ð¸Ð½Ñ‹ ÐºÐ¾Ð»Ð¾Ð½Ð¾Ðº Ð±ÐµÑ€ÑƒÑ‚ÑÑ Ð¸Ð· th/col/CSS */
        #nav-components .table,
        #nav-parts .table,
        #nav-processes .table {
            table-layout: fixed;
        }

        /* Ð¨Ð¸Ñ€Ð¸Ð½Ð° Ñ‚Ð°Ð±Ð»Ð¸Ñ†Ñ‹ Ð²Ð¾ Ð²ÐºÐ»Ð°Ð´ÐºÐµ Components */
        #nav-components .table {
            width: 50%;
        }

        /* ÐšÐ¾Ð»Ð¾Ð½ÐºÐ¸ Components: # | Components PN | EFF Code | Action */
        #nav-components .table th:nth-child(1),
        #nav-components .table td:nth-child(1) { width: 50px; }
        #nav-components .table th:nth-child(2),
        #nav-components .table td:nth-child(2) { width: 200px; }
        #nav-components .table th:nth-child(3),
        #nav-components .table td:nth-child(3) { width: 80px; }
        #nav-components .table th:nth-child(4),
        #nav-components .table td:nth-child(4) { width: 100px; }

        .component-table-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Ð¨Ð¸Ñ€Ð¸Ð½Ð° Ñ‚Ð°Ð±Ð»Ð¸Ñ†Ñ‹ Ð²Ð¾ Ð²ÐºÐ»Ð°Ð´ÐºÐµ Parts */
        #nav-parts .table {
            width: 100%;
        }
        #manualPartsTable th:nth-child(1),
        #manualPartsTable td:nth-child(1) { width: 9%; }
        #manualPartsTable th:nth-child(2),
        #manualPartsTable td:nth-child(2) { width: 13%; }
        #manualPartsTable th:nth-child(3),
        #manualPartsTable td:nth-child(3) { width: 26%; }
        #manualPartsTable th:nth-child(4),
        #manualPartsTable td:nth-child(4) { width: 14%; }
        #manualPartsTable th:nth-child(5),
        #manualPartsTable td:nth-child(5) { width: 7%; }
        #manualPartsTable th:nth-child(6),
        #manualPartsTable td:nth-child(6) { width: 5%; }
        #manualPartsTable th:nth-child(15),
        #manualPartsTable td:nth-child(15) { width: 7%; }

        #manualPartsTable {
            min-width: 1340px;
            width: max(100%, 1340px);
        }

        #nav-parts .table th,
        #nav-parts .table td {
            padding: 5px 7px;
            line-height: 1.25;
            vertical-align: middle;
        }
        #nav-parts .table .btn-sm {
            padding: 2px 6px;
            line-height: 1.2;
        }
        #nav-parts .table img {
            width: 32px;
            height: 32px;
        }

        #manualCreateComponentOffcanvas,
        #manualEditComponentOffcanvas {
            --bs-offcanvas-width: min(720px, 100vw);
            top: .75rem;
            bottom: 4vh;
            height: auto;
            max-height: calc(100vh - .75rem - 4vh);
            display: flex;
            flex-direction: column;
            border-top-left-radius: .75rem;
            border-bottom-left-radius: .75rem;
            overflow: hidden;
        }

        #manualCreateComponentOffcanvas .offcanvas-body,
        #manualEditComponentOffcanvas .offcanvas-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-bottom: 0;
        }

        #manualCreateComponentOffcanvas form,
        #manualEditComponentOffcanvas form {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        .manual-component-form-section {
            border-top: 1px solid rgba(255,255,255,.1);
            padding-top: 1rem;
        }

        .manual-component-form-footer {
            margin-top: auto !important;
            position: sticky;
            bottom: 0;
            z-index: 2;
            background: #212529;
            border-top: 1px solid rgba(255,255,255,.1);
            margin-left: -1rem;
            margin-right: -1rem;
            padding: .75rem 1rem 1rem;
        }

        html[data-bs-theme="light"] .manual-component-form-footer {
            background: #fff;
            border-color: #dee2e6;
        }

        .manual-component-assembly-row {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: .5rem;
            padding: .6rem;
            background: rgba(255,255,255,.025);
        }

        .manual-component-assembly-row + .manual-component-assembly-row {
            margin-top: .5rem;
        }

        .manual-component-assembly-row .form-label {
            font-size: .8rem;
            margin-bottom: .25rem;
        }

        .component-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            max-width: 40px;
            max-height: 40px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: auto;
        }

        .assy-popover-button {
            max-width: 100%;
            min-width: 0;
            padding: .15rem .4rem;
            line-height: 1.25;
        }

        .assy-summary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            max-width: 100%;
            min-width: 0;
        }

        .assy-summary-main {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }

        .component-assy-popover {
            --bs-popover-max-width: 520px;
        }

        .component-assy-popover .popover-body {
            padding: .5rem;
        }

        .assy-popover-list {
            display: grid;
            gap: .4rem;
            min-width: 320px;
        }

        .assy-popover-item {
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: .35rem;
        }

        .assy-popover-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .assy-popover-notes {
            max-width: 460px;
            white-space: normal;
        }

        #manualPartsTable th.sortable {
            cursor: pointer;
        }

        #manualPartsTable th.sortable.sorted-asc i {
            transform: rotate(180deg);
            opacity: 1;
        }

        #manualPartsTable th.sortable.sorted-desc i {
            transform: rotate(0deg);
            opacity: 1;
        }

        #manualPartsTable th.sortable i {
            transition: transform .15s ease, opacity .15s ease;
            opacity: .6;
        }

        #manualPartsTable .component-flag-head,
        #manualPartsTable .component-flag-cell {
            width: 46px !important;
            min-width: 46px !important;
            max-width: 46px !important;
            padding-left: 4px;
            padding-right: 4px;
            text-align: center;
        }

        #manualPartsTable .component-flag-head {
            color: #fff !important;
            font-size: .72rem;
            font-weight: 400;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        #manualPartsTable .component-flag-toggle {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        #manualPartsTable col.manual-part-flag-col {
            width: 46px !important;
            min-width: 46px !important;
            max-width: 46px !important;
        }

        #manualPartsTable col.manual-part-action-col {
            width: 86px !important;
            min-width: 86px !important;
            max-width: 86px !important;
        }

        #manualPartsTable th:nth-child(15),
        #manualPartsTable td:nth-child(15) {
            width: 86px !important;
            min-width: 86px !important;
            max-width: 86px !important;
        }

        body:has(#manualCreateComponentOffcanvas.show) #aiAssistantWidget,
        body:has(#manualEditComponentOffcanvas.show) #aiAssistantWidget {
            display: none !important;
        }

        #nav-processes .table {
            width: 100%;
            table-layout: fixed;
        }

        #nav-sb .manual-sb-table-wrap {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        #nav-sb .manual-sb-pane-body {
            flex: 1 1 auto;
            min-height: 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        #nav-sb .manual-sb-table {
            min-width: 1560px;
            table-layout: fixed;
        }

        #nav-sb .manual-sb-table th,
        #nav-sb .manual-sb-table td {
            vertical-align: middle;
        }

        #nav-sb .manual-sb-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #nav-sb .manual-sb-table textarea {
            min-height: 58px;
            resize: vertical;
        }

        #nav-sb .manual-sb-actions {
            white-space: nowrap;
        }
        #nav-processes .table th:nth-child(1),
        #nav-processes .table td:nth-child(1) { width: 7%; }
        #nav-processes .table th:nth-child(2),
        #nav-processes .table td:nth-child(2) { width: 22%; }
        #nav-processes .table th:nth-child(3),
        #nav-processes .table td:nth-child(3) { width: auto; }
        #nav-processes .table th:nth-child(4),
        #nav-processes .table td:nth-child(4) { width: 28%; }
        #nav-processes .table th:nth-child(5),
        #nav-processes .table td:nth-child(5) {
            width: 82px;
            min-width: 82px;
            max-width: 82px;
        }

        .card shadow {
            max-width: 1200px;
        }

        .card-header{
            display: flex;
        }
        .card-header h5{
            font-size: 12px;
        }
        .manual-show-cmm-number {
            font-size: 1.5em;
            line-height: 1;
        }
        .card-body{
            height: 80vh;
            /*overflow-y: auto;*/
            /*overflow-x: hidden;*/
            font-size: 14px;

        }
        .card .btn i.bi {
            font-size: 14px;
        }

        /* Parts tab table: fixed header + scrollable body */
        #nav-parts .parts-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;


        }

        #manualPartsTable thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;
        }
        #nav-components .component-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;



        }

        .badge{
            font-size: 14px;
        }

        #nav-components table:not(.dir-table) thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;

        }
        #nav-processes .process-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;



        }

        #nav-processes table:not(.dir-table) thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;

        }
        .manual-process-group-row td {
            background: rgba(13, 110, 253, 0.08);
            font-weight: 600;
            padding-top: 6px;
            padding-bottom: 6px;
        }
        .manual-process-child-row td:nth-child(1),
        .manual-process-child-row td:nth-child(2) {
            color: #6c757d;
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }
        .manual-process-child-row td {
            padding-top: 5px;
            padding-bottom: 5px;
            line-height: 1.2;
        }
        .manual-process-lock-cell {
            text-align: center;
            white-space: nowrap;
        }
        .manual-process-lock-button {
            display: inline-block;
        }
        .manual-process-state-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            margin-right: 6px;
            font-size: 13px;
            vertical-align: middle;
        }
        .manual-process-state-icon.is-locked {
            color: #f0ad4e;
        }
        .manual-part-lock-icon {
            color: #f0ad4e;
            font-size: 13px;
            vertical-align: middle;
        }
        .manual-process-name-spacer {
            display: block;
            min-height: 16px;
        }
        .manual-process-actions {
            width: 82px;
            min-width: 82px;
            max-width: 82px;
            white-space: nowrap;
            text-align: center;
        }
        .manual-process-inline-text {
            display: inline-flex;
            align-items: center;
            gap: 0;
            min-height: 18px;
        }
        .manual-process-comment {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .manual-process-lock-button .btn-sm {
            padding: 2px 8px;
            font-size: 12px;
            line-height: 1.15;
        }
        .manual-process-actions .btn-sm {
            width: 28px;
            height: 26px;
            padding: 2px 0;
            font-size: 12px;
            line-height: 1.15;
        }
        .manual-process-actions .bi {
            font-size: 12px;
        }
        #nav-std .std-inner-toolbar-right {
            min-width: 0;
        }
        #nav-std .std-table-container {
            max-height: 70vh;
            overflow: auto;
            font-size: 12px;
        }
        #nav-std table:not(.dir-table) thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;
        }
        /* ÐŸÑ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€ STD CSV Ð² Ð¼Ð¾Ð´Ð°Ð»ÐºÐµ: Ð¿Ñ€Ð¾ÐºÑ€ÑƒÑ‚ÐºÐ° + Ñ„Ð¸ÐºÑÐ¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ð¹ Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²Ð¾Ðº */
        #nav-tabContent .table,
        #nav-tabContent .table th,
        #nav-tabContent .table td,
        #nav-tabContent .table .small {
            font-size: 14px !important;
        }
        .std-csv-view-table-wrap {
            max-height: 70vh;
            overflow: auto;
        }
        .std-csv-view-thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            /*background: var(--bs-light) !important;*/
            background: #c2c2d9 !important;
            white-space: nowrap;
            font-size: 12px;
        }
        #nav-tab .nav-link:focus,
        #nav-tab .nav-link:focus-visible {
            outline: none;
            box-shadow: none;
        }
        #nav-tabContent .tab-pane:focus,
        #nav-tabContent .tab-pane:focus-visible,
        #nav-tabContent .component-table-container:focus,
        #nav-tabContent .component-table-container:focus-visible,
        #nav-tabContent .parts-table-container:focus,
        #nav-tabContent .parts-table-container:focus-visible,
        #nav-tabContent .process-table-container:focus,
        #nav-tabContent .process-table-container:focus-visible,
        #nav-tabContent .std-table-container:focus,
        #nav-tabContent .std-table-container:focus-visible,
        #nav-tabContent .manual-sb-table-wrap:focus,
        #nav-tabContent .manual-sb-table-wrap:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
        #editUnitModal .modal-dialog {
            max-height: 90vh;
        }

        #editUnitModal .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }
        #manual-show-tabs-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
        }
        html.manual-show-tabs-pending #manual-show-tabs-overlay {
            display: flex;
        }
        html.manual-show-tabs-pending #nav-tab,
        html.manual-show-tabs-pending #nav-tab-actions,
        html.manual-show-tabs-pending #nav-tabContent {
            visibility: hidden;
        }

        .manual-show-card {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .manual-show-card > .card-header {
            flex: 0 0 auto;
        }

        .manual-show-card > .card-body {
            flex: 1 1 auto;
            min-height: 0;
            height: auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .manual-show-card nav {
            flex: 0 0 auto;
        }

        .manual-show-card #nav-tabContent {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .manual-show-card #nav-tabContent > .tab-pane {
            min-height: 0;
            min-width: 0;
            flex: 1 1 auto;
        }

        .manual-show-card #nav-tabContent > .tab-pane.active {
            display: flex;
            flex-direction: column;
        }

        .manual-show-card #nav-components .component-table-container,
        .manual-show-card #nav-parts .parts-table-container,
        .manual-show-card #nav-processes .process-table-container,
        .manual-show-card #nav-std .std-table-container,
        .manual-show-card #nav-sb .manual-sb-table-wrap {
            flex: 1 1 auto;
            min-height: 0;
            min-width: 0;
            height: auto;
            max-height: none;
            overflow: auto;
        }
    </style>
    <div class="card shadow manual-show-card">
        <div class="card-header m-2 ">
            <div class="me-2 d-flex ">
                <a href="{{ $cmm->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                    <img class="rounded-circle" src="{{ $cmm->getFirstMediaThumbnailUrl('manuals') }}" width="40" height="40"
                         alt="Image"/>
                </a>

                <div class="ms-3">
                    <h5 class="ms-2 "><strong class="text-secondary">{{__('CMM:')}}</strong> <span class="manual-show-cmm-number text-info">{{ $cmm->number }}</span></h5>
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Description:')}}</strong> {{ $cmm->title }}</h5>
                </div>
            </div>
            <div class="ms-3">
                <h5 class="ms-2"><strong class="text-secondary">{{__('Component PNs:')}}</strong> {{ $cmm->unit_name_training }}</h5>
                <div class="d-flex">
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Revision Date:')}}</strong> {{ $cmm->revision_date }}</h5>
                        <h5 class="ms-4"><strong class="text-secondary">{{__('Lib:')}}</strong> {{ $cmm->lib }}</h5>
                </div>
            </div>
            <div class="ms-3 me-5">
                <h5 class="ms-2"><strong class="text-secondary">{{__('AirCraft Type:')}}</strong>
                        @foreach($planes as $plane)
                            @if($plane->id == $cmm->planes_id )
                                {{$plane->type}}
                            @endif
                        @endforeach
                </h5>
                <h5 class="ms-2"><strong class="text-secondary">{{__('MFR:')}}</strong>
                        @foreach($builders as $builder)
                            @if($builder->id == $cmm->builders_id )
                                {{$builder->name}}
                            @endif
                        @endforeach
                </h5>
            </div>
        </div>

        <div class="card-body">
            <div id="manual-show-tabs-overlay" aria-hidden="true">
                <div class="text-light">{{ __("Loading...") }}</div>
            </div>
            <script>
                (function () {
                    var allowedTabs = ['components', 'parts', 'processes', 'std', 'sb'];
                    var hashToTab = {'#nav-components': 'components', '#nav-parts': 'parts', '#nav-processes': 'processes', '#nav-std': 'std', '#nav-sb': 'sb'};
                    var storageKey = 'manual.show.activeTab.' + @json((int) $cmm->id);
                    var params = new URLSearchParams(location.search);
                    var q = params.get('tab');
                    var fromStorage = null;
                    try {
                        fromStorage = localStorage.getItem(storageKey);
                    } catch (e) {}
                    var desiredKey = (q && allowedTabs.indexOf(q) !== -1)
                        ? q
                        : (params.get('part_id') ? 'parts' : (params.get('std_inner') ? 'std' : (hashToTab[location.hash] || null)));
                    if (!desiredKey && fromStorage && allowedTabs.indexOf(fromStorage) !== -1) {
                        desiredKey = fromStorage;
                    }
                    var server = @json($manualShowTab);
                    if (desiredKey && desiredKey !== server) {
                        document.documentElement.classList.add('manual-show-tabs-pending');
                    }
                })();
            </script>
            <nav>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link @if($manualShowTab === 'components') active @endif" id="nav-components-tab" data-bs-toggle="tab" data-bs-target="#nav-components"
                                type="button" role="tab" aria-controls="nav-components" aria-selected="{{ $manualShowTab === 'components' ? 'true' : 'false' }}">Components</button>
                        <button class="nav-link @if($manualShowTab === 'parts') active @endif" id="nav-parts-tab" data-bs-toggle="tab" data-bs-target="#nav-parts"
                                type="button" role="tab" aria-controls="nav-parts" aria-selected="{{ $manualShowTab === 'parts' ? 'true' : 'false' }}">
                            Parts
                            @if($manualPartsLocked)
                                <i class="bi bi-lock-fill manual-part-lock-icon ms-1" title="Locked by {{ $manualPartLock->lockedBy?->name ?? 'Unknown user' }}"></i>
                            @endif
                        </button>
                        <button class="nav-link @if($manualShowTab === 'processes') active @endif" id="nav-processes-tab" data-bs-toggle="tab" data-bs-target="#nav-processes"
                                type="button" role="tab" aria-controls="nav-processes" aria-selected="{{ $manualShowTab === 'processes' ? 'true' : 'false' }}">Processes</button>
                        <button class="nav-link @if($manualShowTab === 'std') active @endif" id="nav-std-tab" data-bs-toggle="tab" data-bs-target="#nav-std"
                                type="button" role="tab" aria-controls="nav-std" aria-selected="{{ $manualShowTab === 'std' ? 'true' : 'false' }}">STD Processes</button>
                        <button class="nav-link @if($manualShowTab === 'sb') active @endif" id="nav-sb-tab" data-bs-toggle="tab" data-bs-target="#nav-sb"
                                type="button" role="tab" aria-controls="nav-sb" aria-selected="{{ $manualShowTab === 'sb' ? 'true' : 'false' }}">SB</button>
                    </div>
                    <div class="ms-3 d-flex align-items-center gap-2" id="nav-tab-actions">
                        <button type="button"
                                class="btn btn-outline-primary btn-sm btn-update-components"
                                data-tab-target="#nav-components"
                                data-id="{{ $units->first()?->id ?? '' }}"
                                data-manuals-id="{{ $cmm->id }}"
                                data-manual="{{ $cmm->title }}"
                                data-manual-number="{{ $cmm->number }}"
                                data-manual-image="{{ $cmm->getFirstMediaBigUrl('manuals') ?: asset('img/no-image.png') }}"
                                data-bs-toggle="modal"
                                data-bs-target="#editUnitModal">
                            {{ __('Update Components') }}
                        </button>
                        <div class="d-none" data-tab-target="#nav-parts">
                            <div class="input-group input-group-sm" style="width: 260px">
                                <input type="text"
                                       id="parts-search"
                                       class="form-control"
                                       placeholder="Search parts...">
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="parts-search-clear"
                                        aria-label="{{ __('Clear search') }}"
                                        title="{{ __('Clear search') }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button"
                           class="btn btn-outline-info btn-sm d-none"
                           data-tab-target="#nav-parts"
                           data-bs-toggle="offcanvas"
                           data-bs-target="#manualCreateComponentOffcanvas"
                           aria-controls="manualCreateComponentOffcanvas"
                           @disabled($manualPartsLocked && ! $userCanManageLockedManualParts)
                           @if($manualPartsLocked && ! $userCanManageLockedManualParts) title="{{ __('Manual parts are locked') }}" @endif>
                            {{ __('Add Parts') }}
                        </button>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm d-none"
                                data-tab-target="#nav-parts"
                                data-bs-toggle="modal"
                                data-bs-target="#uploadCsvModal"
                                @disabled($manualPartsLocked && ! $userCanManageLockedManualParts)
                                @if($manualPartsLocked && ! $userCanManageLockedManualParts) title="{{ __('Manual parts are locked') }}" @endif>
                            <i class="bi bi-upload"></i> {{__('Upload CSV')}}
                        </button>
                        <form action="{{ $manualPartsLocked ? route('manuals.part-lock.unlock', ['manual' => $cmm]) : route('manuals.part-lock.lock', ['manual' => $cmm]) }}"
                              method="POST"
                              class="d-none m-0"
                              data-tab-target="#nav-parts">
                            @csrf
                            @if($manualPartsLocked)
                                @method('DELETE')
                            @endif
                            <input type="hidden" name="return_to" value="{{ $manualUrlParts }}">
                            <button type="submit"
                                    class="btn btn-outline-secondary btn-sm"
                                    @disabled(! $userCanManageLockedManualParts)
                                    title="{{ $manualPartsLocked ? __('Unlock parts') : __('Lock parts') }}">
                                <i class="bi {{ $manualPartsLocked ? 'bi-unlock' : 'bi-lock-fill' }}"></i>
                                {{ $manualPartsLocked ? __('Unlock Parts') : __('Lock Parts') }}
                            </button>
                        </form>
                        <a href="{{ route('processes.create', ['manual_id' => $cmm->id, 'return_to' => $manualUrlProcesses]) }}"
                           class="btn btn-outline-primary btn-sm d-none"
                           data-tab-target="#nav-processes">
                            {{ __('Add Process') }}
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm d-none"
                                data-tab-target="#nav-std"
                                data-bs-toggle="modal"
                                data-bs-target="#stdCsvUploadModal">
                            <i class="fas fa-upload"></i> {{__('Add CSV Files') }} <span class="small text-muted">(â†’ STD)</span>
                        </button>
                    </div>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane justify-content-start fade @if($manualShowTab === 'components') show active @endif" id="nav-components" role="tabpanel"
                     aria-labelledby="nav-components-tab" tabindex="0">
                    <div class=" component-table-container m-2">
                        <table class="table table-hover table-bordered dir-table">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">#</th>
                                <th class="text-center bg-gradient" scope="col">Components PN</th>
                                <th class="text-center bg-gradient" scope="col">EFF Code</th>
                            </tr>
                            </thead>
                            <tbody class="text-center" id="components-table-body">
                            @php
                            $i=1
                            @endphp

                            @foreach($units as $u)
                            <tr>
                                <td class="align-content-center">{{$i++}}</td>
                                <td class="align-content-center @if(!$u->verified) text-danger fw-bold @endif">
                                    {{$u->part_number}}
                                </td>
                                <td class="align-content-center"> {{$u->eff_code}}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
                <div class="tab-pane fade @if($manualShowTab === 'parts') show active @endif" id="nav-parts" role="tabpanel" aria-labelledby="nav-parts-tab" tabindex="0">
                    <div class="parts-table-container m-2">
                        <table class="table table-sm table-hover table-bordered dir-table align-middle" id="manualPartsTable">
                            <colgroup>
                                <col style="width: 9%;">
                                <col style="width: 13%;">
                                <col style="width: 26%;">
                                <col style="width: 14%;">
                                <col style="width: 7%;">
                                <col style="width: 5%;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-flag-col" style="width: 46px;">
                                <col class="manual-part-action-col" style="width: 86px;">
                            </colgroup>
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient align-content-center sortable">IPL Number <i class="bi bi-chevron-expand ms-1"></i></th>
                                <th class="text-center bg-gradient align-content-center sortable">Part Number <i class="bi bi-chevron-expand ms-1"></i></th>
                                <th class="text-center bg-gradient align-content-center sortable">Name <i class="bi bi-chevron-expand ms-1"></i></th>
                                <th class="text-center bg-gradient align-content-center">Assy</th>
                                <th class="text-center bg-gradient align-content-center">Units per assy</th>
                                <th class="text-center bg-gradient align-content-center">Image</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Log Card">LC</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Bushing">Bush</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Kit">Kit</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Kit E">Kit_E</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="NDT List">NDT</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="CAD List">CAD</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Stress Relief List">Stress</th>
                                <th class="text-center bg-gradient align-content-center component-flag-head" title="Paint List">Paint</th>
                                <th class="text-center bg-gradient align-content-center">Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                                @include('admin.components.partials.index-rows', [
                                    'components' => $parts,
                                    'showManualColumn' => false,
                                    'editButtonClass' => 'open-manual-edit-component-drawer',
                                    'deleteRedirect' => $manualUrlParts,
                                ])
                                @if($parts->isEmpty())
                                    <tr class="components-empty-row">
                                        <td colspan="15" class="text-center text-muted py-4">{{ __('PARTS NOT FOUND') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        </div>

                </div>
                <div class="tab-pane fade @if($manualShowTab === 'processes') show active @endif" id="nav-processes" role="tabpanel" aria-labelledby="nav-processes-tab" tabindex="0">
                    <div class="process-table-container m-2">
                        <table class="table table-hover table-bordered dir-table">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">Lock</th>
                                <th class="text-center bg-gradient" scope="col">Process Name</th>
                                <th class="text-center bg-gradient" scope="col">Process / Specification</th>
                                <th class="text-center bg-gradient" scope="col">Comment</th>
                                <th class="text-center bg-gradient" scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                            @foreach($manualProcessGroups as $group)
                                @php
                                    $processName = $group['process_name'];
                                    $groupLock = $group['group_lock'];
                                @endphp
                                <tr class="manual-process-group-row">
                                    <td class="align-content-center manual-process-lock-cell">
                                        @if($processName)
                                            <form action="{{ $groupLock
                                                ? route('manuals.process-name-locks.unlock', ['manual' => $cmm, 'processName' => $processName])
                                                : route('manuals.process-name-locks.lock', ['manual' => $cmm, 'processName' => $processName]) }}"
                                                  method="POST"
                                                  class="manual-process-lock-button">
                                                @csrf
                                                @if($groupLock)
                                                    @method('DELETE')
                                                @endif
                                                <input type="hidden" name="return_to" value="{{ $manualUrlProcesses }}">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="{{ $groupLock ? 'Unlock group' : 'Lock group' }}" @disabled(! $userCanManageLockedManualProcesses)>
                                                    {{ $groupLock ? 'Unlock' : 'Lock' }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="align-content-center text-start">
                                        <span class="manual-process-inline-text">
                                            @if($groupLock)
                                                <span class="manual-process-state-icon is-locked"
                                                      title="Locked by {{ $groupLock->lockedBy?->name ?? 'Unknown user' }}">
                                                    <i class="bi bi-lock-fill"></i>
                                                </span>
                                            @endif
                                            <span>{{ $processName?->name ?? 'Unknown Process Name' }}</span>
                                        </span>
                                    </td>
                                    <td class="align-content-center text-start">
                                        &nbsp;
                                    </td>
                                    <td class="align-content-center text-start">
                                        &nbsp;
                                    </td>
                                    <td class="align-content-center manual-process-actions">
                                        &nbsp;
                                    </td>
                                </tr>
                                @foreach($group['items'] as $mp)
                                    @php
                                        $rowLocked = $mp->is_locked;
                                    @endphp
                                    <tr class="manual-process-child-row">
                                        <td class="align-content-center manual-process-lock-cell">
                                            <form action="{{ $rowLocked
                                                ? route('manuals.manual-process-locks.unlock', ['manual' => $cmm, 'manualProcess' => $mp])
                                                : route('manuals.manual-process-locks.lock', ['manual' => $cmm, 'manualProcess' => $mp]) }}"
                                                  method="POST"
                                                  class="manual-process-lock-button">
                                                @csrf
                                                @if($rowLocked)
                                                    @method('DELETE')
                                                @endif
                                                <input type="hidden" name="return_to" value="{{ $manualUrlProcesses }}">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="{{ $rowLocked ? 'Unlock process' : 'Lock process' }}" @disabled(! $userCanManageLockedManualProcesses)>
                                                    {{ $rowLocked ? 'Unlock' : 'Lock' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="align-content-center text-start ps-4">
                                            <span class="manual-process-name-spacer"></span>
                                        </td>
                                        <td class="align-content-center text-start ps-3">
                                            <span class="manual-process-inline-text">
                                                @if($rowLocked)
                                                    <span class="manual-process-state-icon is-locked"
                                                          title="Locked by {{ $mp->lockedBy?->name ?? 'Unknown user' }}">
                                                        <i class="bi bi-lock-fill"></i>
                                                    </span>
                                                @endif
                                                <span>{{ $mp->process?->process }}</span>
                                            </span>
                                        </td>
                                        <td class="align-content-center text-start ps-3 manual-process-comment">
                                            {{ $mp->process_comment ?: '-' }}
                                        </td>
                                        <td class="align-content-center manual-process-actions">
                                            <a href="{{ route('manual_processes.edit', $mp) }}?return_to={{ urlencode($manualUrlProcesses) }}"
                                               class="btn btn-outline-primary btn-sm @if($rowLocked && ! $userCanManageLockedManualProcesses) disabled @endif"
                                               title="{{ __('Edit') }}"
                                               @if($rowLocked && ! $userCanManageLockedManualProcesses) aria-disabled="true" tabindex="-1" @endif>
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('manual_processes.destroy', $mp) }}?return_to={{ urlencode($manualUrlProcesses) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this process?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_to" value="{{ $manualUrlProcesses }}">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Delete') }}" @disabled($rowLocked && ! $userCanManageLockedManualProcesses)>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
                <div class="tab-pane fade @if($manualShowTab === 'std') show active @endif" id="nav-std" role="tabpanel" aria-labelledby="nav-std-tab" tabindex="0">
                    <div class="std-table-container m-2">
                        @include('admin.manuals.partials.std-processes-tables', [
                            'cmm' => $cmm,
                            'stdProcessesByType' => $stdProcessesByType ?? collect(),
                            'stdExistingPartKeysByStd' => $stdExistingPartKeysByStd ?? [],
                            'stdAddSourceManuals' => $stdAddSourceManuals ?? collect(),
                            'stdProcessPicklists' => $stdProcessPicklists ?? ['cad' => [], 'stress' => [], 'paint' => []],
                        ])
                    </div>
                </div>
                <div class="tab-pane fade @if($manualShowTab === 'sb') show active @endif" id="nav-sb" role="tabpanel" aria-labelledby="nav-sb-tab" tabindex="0">
                    <div class="m-2 manual-sb-pane-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-0">{{ __('Service Bulletins') }}</h5>
                                <div class="text-muted small">{{ __('Rows are stored on this manual and reused by workorders for SB Log.') }}</div>
                            </div>
                            <span class="badge text-bg-secondary">{{ $serviceBulletins->count() }} {{ __('rows') }}</span>
                        </div>

                        <div class="manual-sb-table-wrap">
                            <table class="table table-sm table-hover table-bordered dir-table manual-sb-table">
                                <colgroup>
                                    <col style="width: 70px;">
                                    <col style="width: 130px;">
                                    <col style="width: 170px;">
                                    <col style="width: 170px;">
                                    <col style="width: 130px;">
                                    <col style="width: 190px;">
                                    <col style="width: 360px;">
                                    <col style="width: 150px;">
                                    <col style="width: 90px;">
                                    <col style="width: 120px;">
                                </colgroup>
                                <thead class="bg-gradient">
                                <tr>
                                    <th class="text-center bg-gradient">{{ __('Sort') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Year Introduced') }}</th>
                                    <th class="text-center bg-gradient">{{ __('A/C MFG SB No.') }}</th>
                                    <th class="text-center bg-gradient">{{ __('OEM SB No.') }}</th>
                                    <th class="text-center bg-gradient">{{ __('A.W.D. No.') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Identification Method') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Description') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Requirement') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Active') }}</th>
                                    <th class="text-center bg-gradient">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="table-secondary">
                                    <td><input form="create-sb-row" type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ ($serviceBulletins->max('sort_order') ?? 0) + 1 }}"></td>
                                    <td><input form="create-sb-row" type="text" name="year_introduced" class="form-control form-control-sm"></td>
                                    <td><input form="create-sb-row" type="text" name="ac_mfg_service_bulletin_no" class="form-control form-control-sm"></td>
                                    <td><input form="create-sb-row" type="text" name="oem_service_bulletin_no" class="form-control form-control-sm"></td>
                                    <td><input form="create-sb-row" type="text" name="awd_no" class="form-control form-control-sm"></td>
                                    <td><input form="create-sb-row" type="text" name="identification_method" class="form-control form-control-sm"></td>
                                    <td><textarea form="create-sb-row" name="description" class="form-control form-control-sm"></textarea></td>
                                    <td>
                                        <select form="create-sb-row" name="default_requirement" class="form-select form-select-sm">
                                            @foreach($sbRequirementOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input form="create-sb-row" type="hidden" name="is_active" value="0">
                                        <input form="create-sb-row" type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                                    </td>
                                    <td class="text-center manual-sb-actions">
                                        <button form="create-sb-row" type="submit" class="btn btn-outline-success btn-sm">{{ __('Add') }}</button>
                                    </td>
                                </tr>

                                @forelse($serviceBulletins as $bulletin)
                                    <tr>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ $bulletin->sort_order }}"></td>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="text" name="year_introduced" class="form-control form-control-sm" value="{{ $bulletin->year_introduced }}"></td>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="text" name="ac_mfg_service_bulletin_no" class="form-control form-control-sm" value="{{ $bulletin->ac_mfg_service_bulletin_no }}"></td>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="text" name="oem_service_bulletin_no" class="form-control form-control-sm" value="{{ $bulletin->oem_service_bulletin_no }}"></td>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="text" name="awd_no" class="form-control form-control-sm" value="{{ $bulletin->awd_no }}"></td>
                                        <td><input form="update-sb-{{ $bulletin->id }}" type="text" name="identification_method" class="form-control form-control-sm" value="{{ $bulletin->identification_method }}"></td>
                                        <td><textarea form="update-sb-{{ $bulletin->id }}" name="description" class="form-control form-control-sm">{{ $bulletin->description }}</textarea></td>
                                        <td>
                                            <select form="update-sb-{{ $bulletin->id }}" name="default_requirement" class="form-select form-select-sm">
                                                @foreach($sbRequirementOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected((string) $bulletin->default_requirement === (string) $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input form="update-sb-{{ $bulletin->id }}" type="hidden" name="is_active" value="0">
                                            <input form="update-sb-{{ $bulletin->id }}" type="checkbox" name="is_active" value="1" class="form-check-input" @checked($bulletin->is_active)>
                                        </td>
                                        <td class="text-center manual-sb-actions">
                                            <button form="update-sb-{{ $bulletin->id }}" type="submit" class="btn btn-outline-primary btn-sm" title="{{ __('Save') }}">
                                                <i class="bi bi-save"></i>
                                            </button>
                                            <button type="submit" form="delete-sb-{{ $bulletin->id }}" class="btn btn-outline-danger btn-sm" title="{{ __('Delete') }}" onclick="return confirm('{{ __('Delete this Service Bulletin row?') }}');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">{{ __('No Service Bulletin rows yet.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <form id="create-sb-row" method="post" action="{{ route('manuals.service-bulletins.store', ['manual' => $cmm]) }}" class="d-none">@csrf</form>
                        @foreach($serviceBulletins as $bulletin)
                            <form id="update-sb-{{ $bulletin->id }}" method="post" action="{{ route('manuals.service-bulletins.update', ['manual' => $cmm, 'serviceBulletin' => $bulletin]) }}" class="d-none">
                                @csrf
                                @method('PUT')
                            </form>
                            <form id="delete-sb-{{ $bulletin->id }}" method="post" action="{{ route('manuals.service-bulletins.destroy', ['manual' => $cmm, 'serviceBulletin' => $bulletin]) }}" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>


        </div>

    </div>

    <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="manualCreateComponentOffcanvas" aria-labelledby="manualCreateComponentOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-primary" id="manualCreateComponentOffcanvasLabel">{{ __('Add Replaceable Part') }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form id="manualCreateComponentDrawerForm" action="{{ route('components.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="manual_id" value="{{ $cmm->id }}">
                <input type="hidden" name="redirect" value="{{ $manualUrlParts }}">
                <div id="manualCreateComponentErrors" class="alert alert-danger d-none"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="manual_drawer_ipl_num" class="form-label">{{ __('IPL Number') }}</label>
                        <input id="manual_drawer_ipl_num" type="text" class="form-control" name="ipl_num" pattern="^\d+-\d+[A-Za-z]?$" required>
                    </div>
                    <div class="col-md-6">
                        <label for="manual_drawer_part_number" class="form-label">{{ __('Part Number') }}</label>
                        <input id="manual_drawer_part_number" type="text" class="form-control" name="part_number" required>
                    </div>
                    <div class="col-12">
                        <label for="manual_drawer_name" class="form-label">{{ __('Name') }}</label>
                        <input id="manual_drawer_name" type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="manual_drawer_units_assy" class="form-label">{{ __('Units per Assy') }}</label>
                        <input id="manual_drawer_units_assy" type="text" class="form-control" name="units_assy">
                    </div>
                    <div class="col-md-6">
                        <label for="manual_drawer_img" class="form-label">{{ __('Image') }}</label>
                        <input id="manual_drawer_img" type="file" name="img" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="manual-component-form-section mt-4">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_log_card" name="log_card">
                            <label class="form-check-label" for="manual_drawer_log_card">Log Card</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_is_bush" name="is_bush">
                            <label class="form-check-label" for="manual_drawer_is_bush">Is Bush</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_kit" name="kit">
                            <label class="form-check-label" for="manual_drawer_kit">Kit</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_kit_e" name="kit_e">
                            <label class="form-check-label" for="manual_drawer_kit_e">Kit_E</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_ndt_list" name="ndt_list">
                            <label class="form-check-label" for="manual_drawer_ndt_list">NDT List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_cad_list" name="cad_list">
                            <label class="form-check-label" for="manual_drawer_cad_list">CAD List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_stress_relief_list" name="stress_relief_list">
                            <label class="form-check-label" for="manual_drawer_stress_relief_list">Stress Relief</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_drawer_paint_list" name="paint_list">
                            <label class="form-check-label" for="manual_drawer_paint_list">Paint List</label>
                        </div>
                    </div>
                    <div class="mt-3 d-none" id="manual_drawer_bush_ipl_container">
                        <label for="manual_drawer_bush_ipl_num" class="form-label">{{ __('Initial Bushing IPL Number') }}</label>
                        <input id="manual_drawer_bush_ipl_num" type="text" class="form-control" name="bush_ipl_num" pattern="^\d+-\d+[A-Za-z]?$">
                    </div>
                </div>

                <div class="manual-component-form-section mt-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h6 class="mb-0">{{ __('Assemblies') }}</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="manualAddAssemblyRowBtn">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <div id="manualAssemblyRows"></div>
                </div>

                <div class="manual-component-form-footer mt-4">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="manualCreateComponentSubmitBtn">{{ __('Save Part') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="manualEditComponentOffcanvas" aria-labelledby="manualEditComponentOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-primary" id="manualEditComponentOffcanvasLabel">{{ __('Edit Replaceable Part') }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form id="manualEditComponentDrawerForm" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="manual_id" value="{{ $cmm->id }}">
                <input type="hidden" name="redirect" value="{{ $manualUrlParts }}">
                <div id="manualEditComponentErrors" class="alert alert-danger d-none"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="manual_edit_drawer_ipl_num" class="form-label">{{ __('IPL Number') }}</label>
                        <input id="manual_edit_drawer_ipl_num" type="text" class="form-control" name="ipl_num" pattern="^\d+-\d+[A-Za-z]?$" required>
                    </div>
                    <div class="col-md-6">
                        <label for="manual_edit_drawer_part_number" class="form-label">{{ __('Part Number') }}</label>
                        <input id="manual_edit_drawer_part_number" type="text" class="form-control" name="part_number" required>
                    </div>
                    <div class="col-12">
                        <label for="manual_edit_drawer_name" class="form-label">{{ __('Name') }}</label>
                        <input id="manual_edit_drawer_name" type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="manual_edit_drawer_units_assy" class="form-label">{{ __('Units per Assy') }}</label>
                        <input id="manual_edit_drawer_units_assy" type="text" class="form-control" name="units_assy">
                    </div>
                    <div class="col-md-6">
                        <label for="manual_edit_drawer_img" class="form-label">{{ __('Image') }}</label>
                        <input id="manual_edit_drawer_img" type="file" name="img" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="manual-component-form-section mt-4">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_log_card" name="log_card">
                            <label class="form-check-label" for="manual_edit_drawer_log_card">Log Card</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_is_bush" name="is_bush">
                            <label class="form-check-label" for="manual_edit_drawer_is_bush">Is Bush</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_kit" name="kit">
                            <label class="form-check-label" for="manual_edit_drawer_kit">Kit</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_kit_e" name="kit_e">
                            <label class="form-check-label" for="manual_edit_drawer_kit_e">Kit_E</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_ndt_list" name="ndt_list">
                            <label class="form-check-label" for="manual_edit_drawer_ndt_list">NDT List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_cad_list" name="cad_list">
                            <label class="form-check-label" for="manual_edit_drawer_cad_list">CAD List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_stress_relief_list" name="stress_relief_list">
                            <label class="form-check-label" for="manual_edit_drawer_stress_relief_list">Stress Relief</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="manual_edit_drawer_paint_list" name="paint_list">
                            <label class="form-check-label" for="manual_edit_drawer_paint_list">Paint List</label>
                        </div>
                    </div>
                    <div class="mt-3 d-none" id="manual_edit_drawer_bush_ipl_container">
                        <label for="manual_edit_drawer_bush_ipl_num" class="form-label">{{ __('Initial Bushing IPL Number') }}</label>
                        <input id="manual_edit_drawer_bush_ipl_num" type="text" class="form-control" name="bush_ipl_num" pattern="^\d+-\d+[A-Za-z]?$">
                    </div>
                </div>

                <div class="manual-component-form-section mt-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h6 class="mb-0">{{ __('Assemblies') }}</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="manualEditAddAssemblyRowBtn">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <div id="manualEditAssemblyRows"></div>
                </div>

                <div class="manual-component-form-footer mt-4">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="manualEditComponentSubmitBtn">{{ __('Save Part') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="manualAssemblyRowTemplate">
        <div class="manual-component-assembly-row" data-assembly-row>
            <input type="hidden" data-assembly-field="id">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted" data-assembly-title></span>
                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-assembly>
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Assembly IPL Number') }}</label>
                    <input type="text" class="form-control" data-assembly-field="assy_ipl_num" pattern="^$|^\d+-\d+[A-Za-z]?$">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Assembly Part Number') }}</label>
                    <input type="text" class="form-control" data-assembly-field="assy_part_number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Units per Assy') }}</label>
                    <input type="text" class="form-control" data-assembly-field="units_assy">
                </div>
                <div class="col-md-6">
                    <input type="file" class="form-control" accept="image/*" aria-label="{{ __('Assy Image') }}" data-assembly-field="assy_img">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="{{ __('Notes') }}" aria-label="{{ __('Notes') }}" data-assembly-field="notes">
                </div>
            </div>
        </div>
    </template>
{{-- ????????? ???? Edit Unit (Update Components) — CMM image, part numbers list, Add PN, Update --}}
    <div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-labelledby="editUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header ">
                    <div class="d-flex ">
                        <div class="ms-4 me-4">
                            <img id="cmmImage" src="" alt="Image CMM" style="width: 100px;">
                        </div>
                        <div class="ms-4">
                            <h6 class="modal-title" id="editUnitModalLabel"></h6>
                            <p id="editUnitModalNumber"></p>
                            <button type="button" class="btn btn-outline-primary" id="addUnitButton">{{ __('Add PN') }}</button>
                        </div>
                    </div>


                </div>
                <div class="modal-body ">
                    <div class="ms-4">
                            <div id="partNumbersList"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="updateUnitButton">{{ __('Update') }}</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="stdCsvUploadModal" tabindex="-1" aria-labelledby="stdCsvUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stdCsvUploadModalLabel">{{ __('Add STD Process CSV') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stdCsvUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="stdCsvProcessType" class="form-label">{{ __('Process Type') }}</label>
                            <select id="stdCsvProcessType" name="process_type" class="form-control" required>
                                <option value="">{{ __('Select Process Type') }}</option>
                                <option value="ndt">{{ __('NDT') }}</option>
                                <option value="cad">{{ __('CAD') }}</option>
                                <option value="stress">{{ __('Stress Relief') }}</option>
                                <option value="paint">{{ __('Paint') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stdCsvFileInput" class="form-label">{{ __('CSV File') }}</label>
                            <input type="file" id="stdCsvFileInput" name="csv_file" class="form-control" accept=".csv,.txt" required>
                            <small class="text-muted">{{ __('Select CSV or TXT file') }}</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-std-csv-upload">{{ __('Upload') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ÐœÐ¾Ð´Ð°Ð»ÑŒÐ½Ð¾Ðµ Ð¾ÐºÐ½Ð¾ Ð¿Ñ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€Ð° STD Process CSV (Ð² Ñ‚Ð¾Ð¼ Ð¶Ðµ Ð¾ÐºÐ½Ðµ, Ð¿Ñ€Ð¾ÐºÑ€ÑƒÑ‚ÐºÐ°, Ñ„Ð¸ÐºÑÐ¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ð¹ Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²Ð¾Ðº) --}}
    <div class="modal fade" id="stdCsvViewModal" tabindex="-1" aria-labelledby="stdCsvViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stdCsvViewModalLabel">{{ __('STD Process file') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="stdCsvViewLoading" class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                    <div id="stdCsvViewTableWrap" class="std-csv-view-table-wrap" style="display: none;">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead class="table-light std-csv-view-thead">
                                <tr id="stdCsvViewTheadRow"></tr>
                            </thead>
                            <tbody id="stdCsvViewTbody"></tbody>
                        </table>
                    </div>
                    <div id="stdCsvViewError" class="alert alert-danger m-3" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadCsvModalLabel">
                        {{__('Upload Parts CSV')}}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('components.upload-csv') }}" method="POST" enctype="multipart/form-data" id="csvUploadForm">
                                @csrf

                                {{-- Ñ„Ð¸ÐºÑÐ¸Ñ€ÑƒÐµÐ¼ manual --}}
                                <input type="hidden" name="manual_id" value="{{ $cmm->id }}">

                                {{-- ÐšÐ£Ð”Ð Ð²ÐµÑ€Ð½ÑƒÑ‚ÑŒÑÑ Ð¿Ð¾ÑÐ»Ðµ ÑƒÑÐ¿ÐµÑˆÐ½Ð¾Ð¹ Ð·Ð°Ð³Ñ€ÑƒÐ·ÐºÐ¸ --}}
                                <input type="hidden" name="redirect"
                                       value="{{ $manualUrlParts }}">

                                <div class="mb-3">
                                    <label class="form-label">{{__('CMM')}}</label>
                                    <input type="text" class="form-control"
                                           value="{{ $cmm->number }} - {{ $cmm->title }}"
                                           disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">{{__('Select CSV File')}}</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> {{__('Upload Parts')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">{{__('CSV Format Requirements')}}</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">{{__('Your CSV file should have the following columns:')}}</p>
                                    <ul class="small text-muted">
                                        <li><strong>part_number</strong> - {{__('Part number (required)')}}</li>
                                        <li><strong>assy_part_number</strong> - {{__('Assembly part number (optional)')}}</li>
                                        <li><strong>name</strong> - {{__('Part name (required)')}}</li>
                                        <li><strong>ipl_num</strong> - {{__('IPL number (required)')}}</li>
                                        <li><strong>assy_ipl_num</strong> - {{__('Assembly IPL number (optional)')}}</li>
                                        <li><strong>log_card</strong> - {{__('Log card (0 or 1, optional)')}}</li>
                                        <li><strong>is_bush</strong> - {{__('Is bushing (0 or 1, optional)')}}</li>
                                        <li><strong>kit</strong>, <strong>kit_e</strong>, <strong>ndt_list</strong>, <strong>cad_list</strong>, <strong>stress_relief_list</strong>, <strong>paint_list</strong> - {{__('Flags (0 or 1, optional)')}}</li>
                                        <li><strong>bush_ipl_num</strong> - {{__('Bushing IPL number (optional)')}}</li>
                                    </ul>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small><i class="bi bi-info-circle"></i> <strong>{{__('Note:')}}</strong> {{__
                                            ('Exact duplicate parts will be automatically skipped. Multiple components with the
                                            same part_number but different IPL numbers are allowed in the same manual. Uploaded CSV files will be saved and can be viewed later.')}}</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('components.download-csv-template') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i> {{__('Download Template')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // STD Processes CSV: Ð¿Ñ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€ Ð² Ð¼Ð¾Ð´Ð°Ð»ÐºÐµ (Ñ‚Ð¾ Ð¶Ðµ Ð¾ÐºÐ½Ð¾, Ð¿Ñ€Ð¾ÐºÑ€ÑƒÑ‚ÐºÐ°, Ñ„Ð¸ÐºÑÐ¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ð¹ Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²Ð¾Ðº)
        function openStdCsvView(fileId, fileName) {
            var modal = document.getElementById('stdCsvViewModal');
            var titleEl = document.getElementById('stdCsvViewModalLabel');
            var loadingEl = document.getElementById('stdCsvViewLoading');
            var tableWrap = document.getElementById('stdCsvViewTableWrap');
            var errorEl = document.getElementById('stdCsvViewError');
            var theadRow = document.getElementById('stdCsvViewTheadRow');
            var tbody = document.getElementById('stdCsvViewTbody');

            if (titleEl) titleEl.textContent = fileName || '{{ __("STD Process file") }}';
            if (loadingEl) { loadingEl.style.display = 'block'; }
            if (tableWrap) { tableWrap.style.display = 'none'; }
            if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
            if (theadRow) theadRow.innerHTML = '';
            if (tbody) tbody.innerHTML = '';

            var dataUrl = '{{ route("manuals.csv.data", ["manual" => $cmm->id, "file" => "__ID__"]) }}'.replace('__ID__', fileId);

            fetch(dataUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (!data.success) {
                        if (errorEl) { errorEl.textContent = data.error || '{{ __("Error loading file") }}'; errorEl.style.display = 'block'; }
                        return;
                    }
                    var headers = data.headers || [];
                    var records = data.records || [];
                    headers.forEach(function (h) {
                        var th = document.createElement('th');
                        th.textContent = h;
                        th.scope = 'col';
                        if (theadRow) theadRow.appendChild(th);
                    });
                    records.forEach(function (row) {
                        var tr = document.createElement('tr');
                        row.forEach(function (cell) {
                            var td = document.createElement('td');
                            td.textContent = cell;
                            tr.appendChild(td);
                        });
                        if (tbody) tbody.appendChild(tr);
                    });
                    if (tableWrap) tableWrap.style.display = 'block';
                })
                .catch(function (err) {
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (errorEl) { errorEl.textContent = err.message || '{{ __("Error loading file") }}'; errorEl.style.display = 'block'; }
                });

            (new bootstrap.Modal(modal)).show();
        }

        function updateStdCsvTabCountBadge() {
            var tbody = document.getElementById('std-csv-tbody');
            var tabBtn = document.getElementById('std-process-inner-tab-csv');
            if (!tbody || !tabBtn) return;
            var badge = tabBtn.querySelector('.badge');
            if (!badge) return;
            var n = tbody.querySelectorAll('tr:not(#std-csv-empty-row)').length;
            badge.textContent = n;
        }

        // STD Processes CSV: ÑƒÐ´Ð°Ð»ÐµÐ½Ð¸Ðµ Ñ„Ð°Ð¹Ð»Ð°
        function deleteStdCsvFile(url, buttonEl) {
            if (!confirm('{{ __("Are you sure you want to delete this file?") }}')) return;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        var tbody = document.getElementById('std-csv-tbody');
                        var tr = buttonEl.closest('tr');
                        if (tr) tr.remove();
                        if (tbody && tbody.querySelectorAll('tr').length === 0) {
                            var emptyRow = document.createElement('tr');
                            emptyRow.id = 'std-csv-empty-row';
                            emptyRow.innerHTML = '<td colspan="4" class="text-muted">{{ __("No STD process files. Use \"Add CSV Files\" to upload NDT, CAD, Stress Relief or Paint CSV.") }}</td>';
                            tbody.appendChild(emptyRow);
                        }
                        updateStdCsvTabCountBadge();
                    } else {
                        throw new Error(data.error || '{{ __("Error deleting file") }}');
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    showNotification(err.message || '{{ __("Error deleting file") }}', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // STD Processes CSV: Ð·Ð°Ð³Ñ€ÑƒÐ·ÐºÐ° Ñ„Ð°Ð¹Ð»Ð°
            // ÐŸÑ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€ STD CSV Ð¿Ð¾ ÐºÐ»Ð¸ÐºÑƒ (Ð² Ñ‚.Ñ‡. Ð´Ð»Ñ Ð´Ð¸Ð½Ð°Ð¼Ð¸Ñ‡ÐµÑÐºÐ¸ Ð´Ð¾Ð±Ð°Ð²Ð»ÐµÐ½Ð½Ñ‹Ñ… ÑÑ‚Ñ€Ð¾Ðº)
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.std-csv-view-btn');
                if (!btn) return;
                e.preventDefault();
                var fileId = btn.getAttribute('data-file-id');
                var fileName = btn.getAttribute('data-file-name') || '';
                if (fileId) openStdCsvView(fileId, fileName);
            });

            var btnStdCsvUpload = document.getElementById('btn-std-csv-upload');
            if (btnStdCsvUpload) {
                btnStdCsvUpload.addEventListener('click', function () {
                    var fileInput = document.getElementById('stdCsvFileInput');
                    var processTypeSelect = document.getElementById('stdCsvProcessType');
                    if (!fileInput || !fileInput.files.length) {
                        showNotification('{{ __("Please select a file") }}', 'warning');
                        return;
                    }
                    if (!processTypeSelect || !processTypeSelect.value) {
                        showNotification('{{ __("Please select a process type") }}', 'warning');
                        return;
                    }
                    var formData = new FormData();
                    formData.append('csv_file', fileInput.files[0]);
                    formData.append('process_type', processTypeSelect.value);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("manuals.csv.store", ["manual" => $cmm->id]) }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success && data.file) {
                                window.location.assign(@json(route('manuals.show', ['manual' => $cmm->id, 'tab' => 'std'])));
                                return;
                            }
                            throw new Error(data.error || '{{ __("Error uploading file") }}');
                        })
                        .catch(function (err) {
                            console.error(err);
                            showNotification(err.message || '{{ __("Error uploading file") }}', 'error');
                        });
                });
            }

            // ÐŸÐ¾Ð¸ÑÐº Ð¿Ð¾ Parts
            const input = document.getElementById('parts-search');
            const clearInput = document.getElementById('parts-search-clear');
            const table = document.getElementById('manualPartsTable');

            if (input && table) {
                const tbody = table.querySelector('tbody');
                const sortableHeaders = Array.from(table.querySelectorAll('th.sortable'));
                const partsSearchStorageKey = 'manual.show.partsSearch.' + @json((int) $cmm->id);
                let sortCol = 0;
                let sortDir = 'asc';
                let searchFrame = null;

                try {
                    input.value = localStorage.getItem(partsSearchStorageKey) || '';
                } catch (e) {}

                function visiblePartRows() {
                    return Array.from(tbody?.querySelectorAll('tr') || []).filter(function (row) {
                        return !row.classList.contains('components-empty-row');
                    });
                }

                function partRowCellText(row, columnIndex) {
                    return (row.children[columnIndex]?.textContent || '').trim();
                }

                function iplSortKey(value) {
                    const match = String(value || '').trim().match(/^(\d+)-(\d+)([A-Za-z]*)$/);
                    if (!match) {
                        return [1, 0, 0, String(value || '').trim().toUpperCase()];
                    }

                    return [
                        0,
                        Number(match[1]),
                        Number(match[2]),
                        match[3].toUpperCase(),
                    ];
                }

                function compareIplValues(a, b) {
                    const ak = iplSortKey(a);
                    const bk = iplSortKey(b);

                    for (let i = 0; i < ak.length; i++) {
                        if (typeof ak[i] === 'number' || typeof bk[i] === 'number') {
                            const diff = Number(ak[i]) - Number(bk[i]);
                            if (diff !== 0) return diff;
                        } else {
                            const diff = String(ak[i]).localeCompare(String(bk[i]));
                            if (diff !== 0) return diff;
                        }
                    }

                    return 0;
                }

                function partRowSearchText(row) {
                    if (!row.dataset.searchText) {
                        row.dataset.searchText = [
                            partRowCellText(row, 0),
                            partRowCellText(row, 1),
                            partRowCellText(row, 2),
                            partRowCellText(row, 3),
                        ].join(' ').toLowerCase();
                    }

                    return row.dataset.searchText;
                }

                function applyPartsSearch() {
                    const query = input.value.trim().toLowerCase();

                    visiblePartRows().forEach(function (row) {
                        const visible = !query || partRowSearchText(row).includes(query);
                        if (row.hidden === visible) {
                            row.hidden = !visible;
                        }
                    });
                }

                function queuePartsSearch() {
                    if (searchFrame) {
                        cancelAnimationFrame(searchFrame);
                    }
                    try {
                        if (input.value) {
                            localStorage.setItem(partsSearchStorageKey, input.value);
                        } else {
                            localStorage.removeItem(partsSearchStorageKey);
                        }
                    } catch (e) {}
                    searchFrame = requestAnimationFrame(function () {
                        searchFrame = null;
                        applyPartsSearch();
                    });
                }

                function updateSortHeaders() {
                    sortableHeaders.forEach(function (th) {
                        th.classList.remove('sorted-asc', 'sorted-desc');
                    });
                    const active = sortableHeaders.find(function (th) {
                        return th.cellIndex === sortCol;
                    });
                    if (active) active.classList.add(sortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                }

                function sortPartsTable(columnIndex) {
                    if (!tbody) return;
                    if (sortCol === columnIndex) {
                        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortCol = columnIndex;
                        sortDir = 'asc';
                    }

                    visiblePartRows()
                        .sort(function (a, b) {
                            const av = partRowCellText(a, columnIndex).toLowerCase();
                            const bv = partRowCellText(b, columnIndex).toLowerCase();
                            const result = columnIndex === 0
                                ? compareIplValues(av, bv)
                                : av.localeCompare(bv, undefined, { numeric: true });

                            return sortDir === 'asc' ? result : -result;
                        })
                        .forEach(function (row) {
                            tbody.appendChild(row);
                        });

                    updateSortHeaders();
                    applyPartsSearch();
                }

                input.addEventListener('input', queuePartsSearch);
                clearInput?.addEventListener('click', function () {
                    input.value = '';
                    try {
                        localStorage.removeItem(partsSearchStorageKey);
                    } catch (e) {}
                    applyPartsSearch();
                    input.focus();
                });
                sortableHeaders.forEach(function (th) {
                    th.addEventListener('click', function () {
                        sortPartsTable(th.cellIndex);
                    });
                });
                updateSortHeaders();
                applyPartsSearch();
            }

            // ÐŸÐµÑ€ÐµÐºÐ»ÑŽÑ‡ÐµÐ½Ð¸Ðµ ÐºÐ½Ð¾Ð¿Ð¾Ðº "Add ..." Ð² Ð½Ð°Ð²Ð¸Ð³Ð°Ñ†Ð¸Ð¸ Ð²ÐºÐ»Ð°Ð´Ð¾Ðº
            document.querySelectorAll('#nav-parts [data-bs-toggle="popover"]').forEach(function (el) {
                if (window.bootstrap && bootstrap.Popover) {
                    bootstrap.Popover.getOrCreateInstance(el, { sanitize: false });
                }
            });

            async function updateManualPartFlag(input) {
                var previous = !input.checked;
                var bushIplNum = input.dataset.bushIplNum || '';

                if (input.dataset.field === 'is_bush') {
                    if (input.checked) {
                        var entered = typeof window.inputDialog === 'function'
                            ? await window.inputDialog({
                                title: '{{ __('Initial Bushing IPL Number') }}',
                                message: '{{ __('Enter initial bushing IPL number.') }} {{ __('For example:') }} 1-230A',
                                value: bushIplNum,
                                okText: '{{ __('Save') }}',
                                cancelText: '{{ __('Cancel') }}',
                                pattern: '^\\d+-\\d+[A-Za-z]?$',
                                invalidMessage: '{{ __('Initial Bushing IPL Number format is invalid.') }}',
                            })
                            : null;
                        if (entered === null) {
                            input.checked = previous;
                            return;
                        }
                        bushIplNum = entered.trim();
                    } else {
                        if (bushIplNum && typeof window.confirmDialog === 'function') {
                            var confirmed = await window.confirmDialog({
                                title: '{{ __('Clear Bushing IPL?') }}',
                                message: '{{ __('The entered Initial Bushing IPL Number will be cleared.') }}',
                                okText: '{{ __('Clear') }}',
                                cancelText: '{{ __('Cancel') }}',
                                danger: true,
                            });
                            if (!confirmed) {
                                input.checked = previous;
                                return;
                            }
                        }
                        bushIplNum = '';
                    }
                }

                input.disabled = true;

                try {
                    var payload = {
                        field: input.dataset.field,
                        value: input.checked ? 1 : 0,
                    };

                    if (input.dataset.field === 'is_bush') {
                        payload.bush_ipl_num = bushIplNum;
                    }

                    var response = await fetch(input.dataset.url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    var data = await response.json().catch(function () { return {}; });
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || '{{ __('Failed to update flag') }}');
                    }
                    if (input.dataset.field === 'is_bush') {
                        input.dataset.bushIplNum = data.bush_ipl_num || '';
                        input.title = data.bush_ipl_num || 'Bush';
                    }
                } catch (err) {
                    console.error(err);
                    input.checked = previous;
                    if (typeof showNotification === 'function') {
                        showNotification(err.message || '{{ __('Failed to update flag') }}', 'error');
                    }
                } finally {
                    input.disabled = false;
                }
            }

            document.getElementById('manualPartsTable')?.addEventListener('change', function (event) {
                var input = event.target.closest('.component-flag-toggle');
                if (!input) return;
                updateManualPartFlag(input);
            });

            function manualDrawerSetErrors(box, messages) {
                if (!box) return;
                var list = Array.isArray(messages) ? messages.filter(Boolean) : [];
                box.classList.toggle('d-none', list.length === 0);
                box.innerHTML = list.map(function (message) {
                    return '<div>' + String(message) + '</div>';
                }).join('');
            }

            function manualDrawerResponseErrors(data, fallback) {
                if (data && data.errors) return Object.values(data.errors).flat();
                if (data && data.message) return [data.message];
                return [fallback];
            }

            function manualDrawerSetSubmitting(button, text, busy) {
                if (!button) return;
                if (busy) {
                    button.dataset.originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + text;
                } else {
                    button.disabled = false;
                    if (button.dataset.originalText) button.innerHTML = button.dataset.originalText;
                }
            }

            function manualSyncBush(isBush, container) {
                if (!isBush || !container) return;
                container.classList.toggle('d-none', !isBush.checked);
            }

            function makeManualAssemblyManager(rowsEl, templateEl) {
                function refreshNames() {
                    rowsEl.querySelectorAll('[data-assembly-row]').forEach(function (row, index) {
                        row.querySelectorAll('[data-assembly-field]').forEach(function (field) {
                            var name = field.getAttribute('data-assembly-field');
                            field.name = 'assemblies[' + index + '][' + name + ']';
                        });
                        var title = row.querySelector('[data-assembly-title]');
                        if (title) title.textContent = 'Assy #' + (index + 1);
                    });
                }

                function add(data) {
                    data = data || {};
                    var fragment = templateEl.content.cloneNode(true);
                    var row = fragment.querySelector('[data-assembly-row]');
                    row.querySelectorAll('[data-assembly-field]').forEach(function (field) {
                        var key = field.getAttribute('data-assembly-field');
                        if (field.type !== 'file') field.value = data[key] || '';
                    });
                    row.querySelector('[data-remove-assembly]')?.addEventListener('click', function () {
                        row.remove();
                        refreshNames();
                    });
                    rowsEl.appendChild(fragment);
                    refreshNames();
                }

                function reset(items) {
                    rowsEl.innerHTML = '';
                    var list = Array.isArray(items) && items.length ? items : [{}];
                    list.forEach(add);
                    refreshNames();
                }

                return { add: add, reset: reset, refreshNames: refreshNames };
            }

            function initManualComponentDrawer(config) {
                var offcanvasEl = document.getElementById(config.offcanvasId);
                var form = document.getElementById(config.formId);
                var errorsBox = document.getElementById(config.errorsId);
                var submitBtn = document.getElementById(config.submitId);
                var isBush = document.getElementById(config.isBushId);
                var bushContainer = document.getElementById(config.bushContainerId);
                var rowsEl = document.getElementById(config.rowsId);
                var addAssemblyBtn = document.getElementById(config.addAssemblyBtnId);
                var templateEl = document.getElementById('manualAssemblyRowTemplate');
                if (!offcanvasEl || !form || form.dataset.bound || !rowsEl || !templateEl) return null;
                form.dataset.bound = '1';

                var assemblies = makeManualAssemblyManager(rowsEl, templateEl);
                addAssemblyBtn?.addEventListener('click', function () { assemblies.add(); });
                isBush?.addEventListener('change', function () { manualSyncBush(isBush, bushContainer); });

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    manualDrawerSetErrors(errorsBox, []);
                    if (!form.checkValidity()) {
                        form.classList.add('was-validated');
                        return;
                    }
                    assemblies.refreshNames();
                    manualDrawerSetSubmitting(submitBtn, '{{ __("Saving...") }}', true);
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            }).catch(function () {
                                return { ok: response.ok, data: {} };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok || !result.data.success) {
                                manualDrawerSetErrors(errorsBox, manualDrawerResponseErrors(result.data, '{{ __("Failed to submit.") }}'));
                                return;
                            }
                            bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
                            window.location.href = @json($manualUrlParts) + '&part_id=' + encodeURIComponent(result.data.component_id || config.loadedId || '');
                        })
                        .catch(function () {
                            manualDrawerSetErrors(errorsBox, ['{{ __("Failed to submit.") }}']);
                        })
                        .finally(function () {
                            manualDrawerSetSubmitting(submitBtn, '{{ __("Saving...") }}', false);
                            if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                        });
                });

                return { form: form, offcanvasEl: offcanvasEl, assemblies: assemblies, isBush: isBush, bushContainer: bushContainer, errorsBox: errorsBox, config: config };
            }

            var manualCreateDrawer = initManualComponentDrawer({
                offcanvasId: 'manualCreateComponentOffcanvas',
                formId: 'manualCreateComponentDrawerForm',
                errorsId: 'manualCreateComponentErrors',
                submitId: 'manualCreateComponentSubmitBtn',
                isBushId: 'manual_drawer_is_bush',
                bushContainerId: 'manual_drawer_bush_ipl_container',
                rowsId: 'manualAssemblyRows',
                addAssemblyBtnId: 'manualAddAssemblyRowBtn'
            });

            if (manualCreateDrawer) {
                manualCreateDrawer.offcanvasEl.addEventListener('show.bs.offcanvas', function () {
                    manualCreateDrawer.form.reset();
                    manualCreateDrawer.form.classList.remove('was-validated');
                    manualCreateDrawer.assemblies.reset([{}]);
                    manualDrawerSetErrors(manualCreateDrawer.errorsBox, []);
                    manualSyncBush(manualCreateDrawer.isBush, manualCreateDrawer.bushContainer);
                });
            }

            var manualEditDrawer = initManualComponentDrawer({
                offcanvasId: 'manualEditComponentOffcanvas',
                formId: 'manualEditComponentDrawerForm',
                errorsId: 'manualEditComponentErrors',
                submitId: 'manualEditComponentSubmitBtn',
                isBushId: 'manual_edit_drawer_is_bush',
                bushContainerId: 'manual_edit_drawer_bush_ipl_container',
                rowsId: 'manualEditAssemblyRows',
                addAssemblyBtnId: 'manualEditAddAssemblyRowBtn'
            });

            function manualEditSetValue(name, value) {
                var field = manualEditDrawer?.form.querySelector('[name="' + name + '"]');
                if (field) field.value = value || '';
            }

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.open-manual-edit-component-drawer');
                if (!button || !manualEditDrawer) return;
                event.preventDefault();
                manualDrawerSetErrors(manualEditDrawer.errorsBox, []);
                manualEditDrawer.config.loadedId = '';
                manualEditDrawer.form.action = button.dataset.updateUrl || '';
                bootstrap.Offcanvas.getOrCreateInstance(manualEditDrawer.offcanvasEl).show();

                fetch(button.dataset.componentUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data.success) {
                            manualDrawerSetErrors(manualEditDrawer.errorsBox, manualDrawerResponseErrors(result.data, '{{ __("Failed to load part.") }}'));
                            return;
                        }
                        var component = result.data.component || {};
                        manualEditDrawer.config.loadedId = component.id || '';
                        manualEditSetValue('ipl_num', component.ipl_num);
                        manualEditSetValue('part_number', component.part_number);
                        manualEditSetValue('units_assy', component.units_assy);
                        manualEditSetValue('name', component.name);
                        manualEditSetValue('bush_ipl_num', component.bush_ipl_num);
                        if (manualEditDrawer.isBush) manualEditDrawer.isBush.checked = !!component.is_bush;
                        var logCard = document.getElementById('manual_edit_drawer_log_card');
                        if (logCard) logCard.checked = !!component.log_card;
                        ['kit', 'kit_e', 'ndt_list', 'cad_list', 'stress_relief_list', 'paint_list'].forEach(function (field) {
                            var checkbox = document.getElementById('manual_edit_drawer_' + field);
                            if (checkbox) checkbox.checked = !!component[field];
                        });
                        manualSyncBush(manualEditDrawer.isBush, manualEditDrawer.bushContainer);
                        manualEditDrawer.assemblies.reset(Array.isArray(component.assemblies) ? component.assemblies : []);
                    })
                    .catch(function () {
                        manualDrawerSetErrors(manualEditDrawer.errorsBox, ['{{ __("Failed to load part.") }}']);
                    })
                    .finally(function () {
                        if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                    });
            });

            const navTabs = document.querySelectorAll('#nav-tab .nav-link');
            const actions = document.querySelectorAll('#nav-tab-actions [data-tab-target]');

            function updateTabActions(activeTarget) {
                actions.forEach(function (btn) {
                    const target = btn.getAttribute('data-tab-target');
                    btn.classList.toggle('d-none', target !== activeTarget);
                });
            }

            const tabKeyToPane = {
                components: '#nav-components',
                parts: '#nav-parts',
                processes: '#nav-processes',
                std: '#nav-std',
                sb: '#nav-sb'
            };
            const initialParams = new URLSearchParams(window.location.search);
            const partIdToScroll = initialParams.get('part_id');
            const serverTab = @json($manualShowTab);
            const manualActiveTabStorageKey = 'manual.show.activeTab.' + @json((int) $cmm->id);
            let storedTab = null;
            try {
                storedTab = localStorage.getItem(manualActiveTabStorageKey);
            } catch (e) {}

            function rememberManualShowTab(target) {
                const key = Object.keys(tabKeyToPane).find(function (name) {
                    return tabKeyToPane[name] === target;
                });
                if (key) {
                    try {
                        localStorage.setItem(manualActiveTabStorageKey, key);
                    } catch (e) {}
                }
            }

            let hash = window.location.hash;
            const tabFromQuery = initialParams.get('tab');
            if (!hash && tabFromQuery && tabKeyToPane[tabFromQuery]) {
                hash = tabKeyToPane[tabFromQuery];
            }
            if (!hash && !tabFromQuery && partIdToScroll) {
                hash = tabKeyToPane.parts;
            }
            if (!hash && !tabFromQuery && !partIdToScroll && initialParams.get('std_inner')) {
                hash = tabKeyToPane.std;
            }
            let desiredPane = hash || '';
            const desiredFromLocalStorage = !desiredPane && !tabFromQuery && storedTab && tabKeyToPane[storedTab];
            if (desiredFromLocalStorage) {
                desiredPane = tabKeyToPane[storedTab];
            }
            if (!desiredPane && serverTab && tabKeyToPane[serverTab]) {
                desiredPane = tabKeyToPane[serverTab];
            }

            let activeTab = document.querySelector('#nav-tab .nav-link.active');
            const activePane = activeTab ? activeTab.getAttribute('data-bs-target') : null;
            const needsClientSwitch = !!(desiredPane && activePane !== desiredPane);

            function applyManualShowUrlCleanup() {
                if (!window.history || !window.history.replaceState) return;
                var removedQueryParams = initialParams.has('tab') || initialParams.has('part_id') || initialParams.has('std_inner');
                if (!removedQueryParams && !needsClientSwitch) return;
                var u = new URL(window.location.href);
                u.searchParams.delete('tab');
                u.searchParams.delete('part_id');
                u.searchParams.delete('std_inner');
                if (desiredPane && !desiredFromLocalStorage) {
                    u.hash = desiredPane;
                }
                window.history.replaceState(null, '', u.pathname + u.search + u.hash);
            }

            function scrollToEditedPartRow() {
                if (!partIdToScroll) return;
                requestAnimationFrame(function () {
                    var row = document.getElementById('manual-part-row-' + partIdToScroll);
                    if (row) {
                        row.scrollIntoView({ block: 'center', behavior: 'auto' });
                        row.classList.add('table-warning');
                        window.setTimeout(function () { row.classList.remove('table-warning'); }, 1400);
                    }
                });
            }

            function activateStdInnerTabIfRequested() {
                var inner = initialParams.get('std_inner');
                if (!inner) {
                    return;
                }
                var allowed = { ndt: 1, cad: 1, stress: 1, paint: 1, csv: 1 };
                if (!allowed[inner]) {
                    return;
                }
                var stdPane = document.getElementById('nav-std');
                if (!stdPane || !stdPane.classList.contains('active')) {
                    return;
                }
                var btnId = inner === 'csv' ? 'std-process-inner-tab-csv' : 'std-process-inner-tab-' + inner;
                requestAnimationFrame(function () {
                    var btn = document.getElementById(btnId);
                    if (btn && window.bootstrap && bootstrap.Tab) {
                        new bootstrap.Tab(btn).show();
                    }
                });
            }

            function finishManualShowTabsBoot() {
                document.documentElement.classList.remove('manual-show-tabs-pending');
                activeTab = document.querySelector('#nav-tab .nav-link.active');
                if (activeTab) {
                    var target = activeTab.getAttribute('data-bs-target');
                    updateTabActions(target);
                    rememberManualShowTab(target);
                }
                scrollToEditedPartRow();
                activateStdInnerTabIfRequested();
            }

            if (needsClientSwitch) {
                var targetTabEl = document.querySelector('#nav-tab .nav-link[data-bs-target="' + desiredPane + '"]');
                if (targetTabEl) {
                    function onTabShown() {
                        targetTabEl.removeEventListener('shown.bs.tab', onTabShown);
                        applyManualShowUrlCleanup();
                        finishManualShowTabsBoot();
                    }
                    targetTabEl.addEventListener('shown.bs.tab', onTabShown);
                    new bootstrap.Tab(targetTabEl).show();
                } else {
                    applyManualShowUrlCleanup();
                    finishManualShowTabsBoot();
                }
            } else {
                applyManualShowUrlCleanup();
                finishManualShowTabsBoot();
            }

            // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ñ€Ð¸ Ð¿ÐµÑ€ÐµÐºÐ»ÑŽÑ‡ÐµÐ½Ð¸Ð¸ Ð²ÐºÐ»Ð°Ð´Ð¾Ðº (Bootstrap event)
            navTabs.forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (event) {
                    const target = event.target.getAttribute('data-bs-target');
                    updateTabActions(target);
                    rememberManualShowTab(target);
                });
            });

            // ---- Edit Unit / Update Components modal (bulk edit: load units, populate partNumbersList, Add PN, Update) ----
            var editUnitModal = document.getElementById('editUnitModal');
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.btn-update-components');
                if (!button) return;

                const manualId     = button.getAttribute('data-manuals-id');
                const manualTitle  = button.getAttribute('data-manual');
                const manualImage  = button.getAttribute('data-manual-image');
                const manualNumber = button.getAttribute('data-manual-number');

                const editModal = document.getElementById('editUnitModal');
                if (!editModal || !manualId) return;

                editModal.setAttribute('data-manual-id', manualId);
                document.getElementById('editUnitModalLabel').innerText  = manualTitle || '{{ __("Edit Unit") }}';
                document.getElementById('editUnitModalNumber').innerText = manualNumber ? 'CMM: ' + manualNumber : '';
                document.getElementById('cmmImage').src                  = manualImage || '';

                const partNumbersList = document.getElementById('partNumbersList');
                partNumbersList.innerHTML = '';

                const unitsUrl = '{{ route("units.show", $cmm->id) }}';

                fetch(unitsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.units && data.units.length > 0) {
                            data.units.forEach(function (unit) {
                                addPartNumberRow(unit.part_number, unit.verified, unit.eff_code || '');
                            });
                        } else {
                            var noUnitsItem = document.createElement('div');
                            noUnitsItem.className = 'mb-2 text-muted';
                            noUnitsItem.innerText = '{{ __("No part numbers found for this manual.") }}';
                            partNumbersList.appendChild(noUnitsItem);
                        }
                    })
                    .catch(function (err) { console.error('Error loading units:', err); });
            });

            document.addEventListener('click', function (e) {
                if (e.target.id === 'addUnitButton' || e.target.closest('#addUnitButton')) {
                    addPartNumberRow('', true, '');
                }
            });

            function addPartNumberRow(partNumber, verified, effCode) {
                var partNumbersList = document.getElementById('partNumbersList');
                if (!partNumbersList) return;

                var noUnitsMsg = partNumbersList.querySelector('.text-muted');
                if (noUnitsMsg) noUnitsMsg.remove();

                var listItem = document.createElement('div');
                listItem.className = 'mb-2 d-flex align-items-center';

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input me-2';
                checkbox.checked = !!verified;

                var pnInput = document.createElement('input');
                pnInput.type = 'text';
                pnInput.className = 'form-control me-2';
                pnInput.style.width = '150px';
                pnInput.value = partNumber || '';
                pnInput.placeholder = 'Part Number';

                var effCodeInput = document.createElement('input');
                effCodeInput.type = 'text';
                effCodeInput.className = 'form-control me-2';
                effCodeInput.style.width = '120px';
                effCodeInput.value = effCode || '';
                effCodeInput.placeholder = 'EFF Code';

                var deleteButton = document.createElement('button');
                deleteButton.className = 'btn btn-danger btn-sm ms-1';
                deleteButton.innerText = 'Del';
                deleteButton.onclick = function () { listItem.remove(); };

                listItem.appendChild(checkbox);
                listItem.appendChild(pnInput);
                listItem.appendChild(effCodeInput);
                listItem.appendChild(deleteButton);
                partNumbersList.appendChild(listItem);
            }

            var updateUnitBtn = document.getElementById('updateUnitButton');
            if (updateUnitBtn) {
                updateUnitBtn.addEventListener('click', function () {
                var manualId = editUnitModal.getAttribute('data-manual-id');
                var listItems = document.querySelectorAll('#partNumbersList .d-flex.align-items-center');
                var partNumbers = Array.from(listItems).map(function (listItem) {
                    var inputs = listItem.querySelectorAll('.form-control');
                    var checkbox = listItem.querySelector('.form-check-input');
                    return {
                        part_number: inputs[0] ? inputs[0].value : '',
                        eff_code: inputs[1] ? inputs[1].value : '',
                        verified: !!(checkbox && checkbox.checked)
                    };
                });

                if (!manualId) { showNotification('{{ __("Error: Manual ID not found") }}', 'error'); return; }
                if (partNumbers.length === 0) { showNotification('{{ __("Error: No part numbers to update") }}', 'error'); return; }
                var invalidItems = partNumbers.filter(function (item) { return !item.part_number.trim(); });
                if (invalidItems.length > 0) { showNotification('{{ __("Error: All part numbers must be filled") }}', 'error'); return; }

                var unitsUpdateBase = @json(rtrim((string) url('/units'), '/'));
                var updateUrl = unitsUpdateBase + '/' + encodeURIComponent(manualId);

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ part_numbers: partNumbers })
                })
                    .then(function (r) {
                        return r.text().then(function (text) {
                            var data = {};
                            if (text) {
                                try { data = JSON.parse(text); } catch (e) { data = { _raw: text }; }
                            }
                            if (!r.ok) {
                                var errMsg = (data && (data.error || data.message)) || (r.status + ' ' + r.statusText);
                                throw new Error(errMsg);
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        if (data.success) {
                            showNotification('{{ __("Units updated successfully") }}', 'success');
                            var modalInstance = bootstrap.Modal.getInstance(editUnitModal);
                            if (modalInstance) modalInstance.hide();
                            // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‚Ð°Ð±Ð»Ð¸Ñ†Ñƒ Components Ð±ÐµÐ· Ð¿ÐµÑ€ÐµÐ·Ð°Ð³Ñ€ÑƒÐ·ÐºÐ¸
                            var unitsUrl = '{{ route("units.show", $cmm->id) }}';
                            fetch(unitsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(function (r) { return r.json(); })
                                .then(function (resp) {
                                    var tbody = document.getElementById('components-table-body');
                                    if (!tbody) return;
                                    tbody.innerHTML = '';
                                    if (resp.units && resp.units.length > 0) {
                                        resp.units.forEach(function (unit, idx) {
                                            var tr = document.createElement('tr');
                                            tr.innerHTML = '<td class="align-content-center">' + (idx + 1) + '</td>' +
                                                '<td class="align-content-center' + (unit.verified ? '' : ' text-danger fw-bold') + '">' + (unit.part_number || '') + '</td>' +
                                                '<td class="align-content-center">' + (unit.eff_code || '') + '</td>';
                                            tbody.appendChild(tr);
                                        });
                                    }
                                })
                                .catch(function (err) { console.error('Error refreshing components:', err); });
                        } else {
                            showNotification('Error: ' + (data.error || '{{ __("Unknown error") }}'), 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        showNotification('{{ __("Error updating units") }}: ' + err.message, 'error');
                    });
                });
            }

        });
    </script>

@endsection

@section('scripts')
    {{-- ÐšÐ¾Ð½Ñ‚ÐµÐºÑÑ‚ CMM Ð´Ð»Ñ AI: Ð½Ð¾Ð¼ÐµÑ€ Ð¸ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸Ðµ â€” Ð±ÐµÐ· manual_id Ð² Ð¾Ñ‚Ð²ÐµÑ‚Ð°Ñ… Ð¿Ð¾Ð»ÑŒÐ·Ð¾Ð²Ð°Ñ‚ÐµÐ»ÑŽ --}}
    <script>
        window.aiCurrentManual = @json([
            'number' => $cmm->number ?? '',
            'title' => $cmm->title ?? '',
        ]);
    </script>
@endsection
