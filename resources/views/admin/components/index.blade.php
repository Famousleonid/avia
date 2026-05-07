@extends('admin.master')

@section('style')

    <style>

        .table-wrapper.is-preloading{
            visibility: hidden;
        }

        .table-wrapper{
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        html[data-bs-theme="dark"] #componentsTable {
            background: transparent !important;
            color: #e6e6e6;
        }

        html[data-bs-theme="dark"] #componentsTable tbody tr,
        html[data-bs-theme="dark"] #componentsTable tbody td{
            background: transparent !important;
            border-color: rgba(255,255,255,.08) !important;
        }

        
        html[data-bs-theme="dark"] #componentsTable tbody tr{
            box-shadow: inset 0 0 0 9999px rgba(255,255,255,0.03);
        }
        html[data-bs-theme="dark"] #componentsTable tbody tr:hover{
            box-shadow: inset 0 0 0 9999px rgba(255,255,255,0.06);
        }

        
        html[data-bs-theme="dark"] #componentsTable thead th{
            background: linear-gradient(180deg, #1a1d21 0%, #14161a 100%) !important;
            color: #f2f2f2 !important;
            border-color: rgba(255,255,255,.10) !important;
        }

        
        html[data-bs-theme="dark"] #componentsTable.table-bordered > :not(caption) > *{
            border-color: rgba(255,255,255,.10) !important;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 100px;
            max-width: 150px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 100px;
            max-width: 150px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 150px;
            max-width: 250px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 190px;
            max-width: 260px;
        }

        .table th:nth-child(5), .table td:nth-child(5) {
            min-width: 80px;
            max-width: 120px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 100px;
            max-width: 150px;
        }

        .table th:nth-child(7), .table td:nth-child(7) {
            min-width: 110px;
            max-width: 150px;
        }

        #componentsTable .component-flag-head,
        #componentsTable .component-flag-cell {
            width: 42px;
            min-width: 42px;
            max-width: 42px;
            padding-left: 4px;
            padding-right: 4px;
            text-align: center;
        }

        #componentsTable .component-flag-head {
            color: #fff !important;
            font-size: .72rem;
            font-weight: 400;
            line-height: 1.1;
        }

        #componentsTable .component-flag-toggle {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }


        .table thead th {
            position: sticky;
            height: 32px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;

            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
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

        .assy-popover-button {
            max-width: 100%;
            min-width: 0;
            padding: .15rem .4rem;
            line-height: 1.25;
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

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 350px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }

        html[data-bs-theme="light"] #componentsTable {
            color: var(--bs-body-color);
        }

        html[data-bs-theme="light"] #componentsTable thead th {
            background: var(--bs-tertiary-bg) !important;
            color: var(--bs-body-color) !important;
        }

        html[data-bs-theme="light"] #manualFilter + .select2 .select2-selection--single {
            background: #fff !important;
            border: 1px solid #dee2e6 !important;
            color: #212529 !important;
            box-shadow: none !important;
        }

        html[data-bs-theme="light"] #manualFilter + .select2 .select2-selection__rendered {
            color: #212529 !important;
        }

        
        html[data-bs-theme="dark"] .select2-container .select2-selection--single{
            height: 40px !important;
            background: #0f1114 !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 10px !important;
            color: #e6e6e6 !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,.25);
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered{
            color: #e6e6e6 !important;
            line-height: 38px !important;
            padding-left: 12px !important;
            padding-right: 38px !important; 
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__placeholder{
            color: rgba(230,230,230,.55) !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__arrow{
            height: 38px !important;
            right: 8px !important;
        }
        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__arrow b{
            border-color: rgba(230,230,230,.7) transparent transparent transparent !important;
        }

        
        html[data-bs-theme="dark"] .select2-container--default .select2-dropdown{
            background: #0f1114 !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            border-radius: 12px !important;
            overflow: hidden;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-search--dropdown .select2-search__field{
            background: #0b0c0e !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 10px !important;
            color: #e6e6e6 !important;
            padding: 8px 10px !important;
            outline: none !important;
        }

        
        html[data-bs-theme="dark"] .select2-container--default .select2-results__option{
            color: #e6e6e6 !important;
            padding: 8px 12px !important;
            line-height: 1.35;

        }
        html[data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
            background: #0d6efd !important;
            color: #fff !important;
        }
        html[data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable *{
            color: #fff !important;
        }
        html[data-bs-theme="dark"] .select2-container--default .select2-results__option--selected{
            background: #20262d !important;
            color: #fff !important;
        }
        html[data-bs-theme="dark"] .select2-container--default .select2-results__option--selected *{
            color: #fff !important;
        }

        
        html[data-bs-theme="dark"] .select2-container--default.select2-container--focus .select2-selection--single{
            border-color: rgba(255,255,255,.18) !important;
            box-shadow: 0 0 0 3px rgba(255,255,255,.06) !important;
        }

        
        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__clear{
            color: rgba(230,230,230,.75) !important;
            font-size: 18px;
            line-height: 1;
            position: absolute;
            right: 30px;            
            top: 50%;
            transform: translateY(-50%);
            padding: 0 6px;
        }
        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__clear:hover{
            color: #fff !important;
        }

        
        .select2-container--default .select2-results__options{
            max-height: 60vh !important;
            overflow-y: auto !important;
        }
        .select2-container--open .select2-dropdown{
            margin-bottom: 8px;
        }
        .fs-8 {
            font-size: 0.8rem;
        }

        .manual-option-lib {
            color: #0d6efd;
        }

        .manual-option-title {
            color: #6c757d;
        }

        html[data-bs-theme="dark"] .manual-option-lib {
            color: #4c8bf5;
        }

        html[data-bs-theme="dark"] .manual-option-title {
            color: #adb5bd;
        }

        html[data-bs-theme="dark"] .select2-results__option--highlighted .manual-option-lib,
        html[data-bs-theme="dark"] .select2-results__option--highlighted .manual-option-title,
        html[data-bs-theme="dark"] .select2-results__option--selected .manual-option-lib,
        html[data-bs-theme="dark"] .select2-results__option--selected .manual-option-title {
            color: #fff !important;
        }

        .manual-selection-lib {
            color: #6c757d;
        }
        .manual-part-lock-icon {
            color: #f0ad4e;
            font-size: 13px;
            vertical-align: middle;
        }

        html[data-bs-theme="dark"] .manual-selection-lib {
            color: #adb5bd;
        }

        th.sortable.sorted-asc  i { transform: rotate(180deg); opacity: 1; }
        th.sortable.sorted-desc i { transform: rotate(0deg);   opacity: 1; }
        th.sortable i { transition: transform .15s ease, opacity .15s ease; opacity: .6; }
        #manualFilter + .select2 { width: 460px !important; }

        .component-avatar{
            width:40px;
            height:40px;
            min-width:40px;
            min-height:40px;
            max-width:40px;
            max-height:40px;
            border-radius:50%;
            object-fit:cover;
            display:block;
            margin:auto;
        }

        #createComponentOffcanvas,
        #editComponentOffcanvas {
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

        #createComponentOffcanvas .offcanvas-body,
        #editComponentOffcanvas .offcanvas-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-bottom: 0;
        }

        #createComponentOffcanvas form,
        #editComponentOffcanvas form {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        #createComponentOffcanvas .component-form-footer,
        #editComponentOffcanvas .component-form-footer {
            margin-top: auto !important;
        }

        body:has(#createComponentOffcanvas.show) #aiAssistantWidget,
        body:has(#editComponentOffcanvas.show) #aiAssistantWidget {
            display: none !important;
        }

        @media (max-height: 720px) {
            #createComponentOffcanvas,
            #editComponentOffcanvas {
                top: .5rem;
                bottom: 4vh;
                max-height: calc(100vh - .5rem - 4vh);
            }
        }

        .component-form-section {
            border-top: 1px solid rgba(255,255,255,.1);
            padding-top: 1rem;
        }

        .component-form-footer {
            position: sticky;
            bottom: 0;
            z-index: 2;
            background: #212529;
            border-top: 1px solid rgba(255,255,255,.1);
            margin-left: -1rem;
            margin-right: -1rem;
            padding: .75rem 1rem 1rem;
        }

        html[data-bs-theme="light"] .component-form-footer {
            background: #fff;
            border-color: #dee2e6;
        }

        .component-assembly-row {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: .5rem;
            padding: .6rem;
            background: rgba(255,255,255,.025);
        }

        .component-assembly-row + .component-assembly-row {
            margin-top: .5rem;
        }

        .component-assembly-row .form-label {
            font-size: .8rem;
            margin-bottom: .25rem;
        }

        .component-assembly-row .form-control {
            min-height: 36px;
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        .component-assembly-row input[type="file"].form-control {
            padding-top: .3rem;
            padding-bottom: .3rem;
        }

        html[data-bs-theme="light"] .component-assembly-row {
            border-color: #dee2e6;
            background: #f8f9fa;
        }

        .components-header-title {
            font-size: 1.1rem;
        }

        .components-filter-bar {
            margin-right: 3rem;
        }

        .components-manual-filter {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .components-manual-filter .form-label {
            margin-bottom: 0;
            white-space: nowrap;
        }

    </style>

@endsection

@section('content')


    <div class="card dir-page">
        <div class="card-header my-0 ">
            <div class="d-flex align-items-center flex-wrap gap-3">

                <h5 class="text-primary manage-header components-header-title mb-0 me-3">{{__('Replaceable Parts')}}( <span class="text-success"
                                                                          id="componentsCount">{{ $componentsTotal }}</span>)</h5>
                <div class="components-filter-bar d-flex my-2 gap-2 flex-wrap align-items-center">
                    <!-- Filter by Manual -->
                    <div class="components-manual-filter">
                        <label for="manualFilter" class="form-label text-muted">
                            {{ __('Manual') }}:
                            <i id="componentsManualLockIcon"
                               class="bi bi-lock-fill manual-part-lock-icon ms-1 d-none"
                               title="{{ __('Manual parts are locked') }}"></i>
                        </label>
                        <select id="manualFilter" class="form-select" style="height:40px;width:460px;">
                            <option value="">{{ __('All Manuals') }}</option>

                            @foreach($manuals as $manual)
                                <option
                                    value="{{ $manual->id }}"
                                    data-number="{{ $manual->number }}"
                                    data-lib="{{ $manual->lib }}"
                                    data-title="{{ $manual->title }}"
                                    data-parts-locked="{{ $manual->partLock ? '1' : '0' }}"
                                    data-locked-by="{{ $manual->partLock?->lockedBy?->name ?? '' }}"
                                >
                                    {{ $manual->number }} ({{ $manual->lib ?? '—' }}) - {{ $manual->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="clearable-input">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button type="button" class="btn-clear text-secondary" id="searchClearBtn" aria-label="Clear search">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>

                    <!-- Add Component -->
                    <button type="button"
                            id="addPartButton"
                            class="btn btn-outline-info"
                            style="height: 40px"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#createComponentOffcanvas"
                            aria-controls="createComponentOffcanvas">
                        {{__('Add Part')}}
                    </button>

                    <!-- CSV Components -->
                    <a href="{{ route('components.csv-components') }}" class="btn btn-outline-primary ms-5" style="height: 40px">
                        <i class="bi bi-file-earmark-spreadsheet"></i> {{__('CSV Parts')}}
                    </a>
                </div>
                <script>
                    (() => {
                        try {
                            const savedSearch = localStorage.getItem('components_search') || '';
                            const savedManual = localStorage.getItem('components_manual_id') || '';
                            const searchInput = document.getElementById('searchInput');
                            const manualFilter = document.getElementById('manualFilter');
                            if (searchInput) searchInput.value = savedSearch;
                            if (manualFilter && Array.from(manualFilter.options).some(option => option.value === savedManual)) {
                                manualFilter.value = savedManual;
                            }
                        } catch (e) {}
                    })();
                </script>
            </div>

                <div class="table-wrapper me-3 p-2 pt-0 dir-panel"
                     id="componentsTableWrapper"
                     style="visibility:hidden"
                     data-next-page="{{ $components->currentPage() + 1 }}"
                     data-has-more="{{ $components->hasMorePages() ? '1' : '0' }}"
                     data-per-page="{{ $components->perPage() }}">
                    <table id="componentsTable" class="table table-sm table-hover bg-gradient align-middle table-bordered dir-table">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-center sortable">{{__('IPL Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center sortable">{{__('Part Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center sortable">{{__('Name')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center">{{__('Assy')}}</th>
                            <th class="text-center">{{__('Image')}}</th>
                            <th class="text-center component-flag-head" title="Log Card">LC</th>
                            <th class="text-center component-flag-head" title="Bushing">Bush</th>
                            <th class="text-center component-flag-head" title="Kit">Kit</th>
                            <th class="text-center component-flag-head" title="NDT List">NDT</th>
                            <th class="text-center component-flag-head" title="CAD List">CAD</th>
                            <th class="text-center component-flag-head" title="Stress Relief List">Stress</th>
                            <th class="text-center component-flag-head" title="Paint List">Paint</th>
                            <th class="text-center">{{__('Manual')}}</th>
                            <th class="text-center">{{__('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @include('admin.components.partials.index-rows', ['components' => $components])
                        @if($componentsTotal === 0)
                            <tr class="components-empty-row">
                                <td colspan="14" class="text-center text-muted py-4">{{ __('PARTS NOT FOUND') }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                    <div id="componentsLoadStatus" class="text-center text-muted small py-2"></div>
                </div>

        </div>

        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="createComponentOffcanvas" aria-labelledby="createComponentOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title text-primary" id="createComponentOffcanvasLabel">{{ __('Add Replaceable Part') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="offcanvas-body">
                <form id="createComponentDrawerForm" action="{{ route('components.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div id="createComponentErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="drawer_manual_id" class="form-label">CMM</label>
                            <select name="manual_id" id="drawer_manual_id" class="form-select" required>
                                <option value="">{{ __('Select Manual') }}</option>
                                @foreach($manuals as $manual)
                                    <option value="{{ $manual->id }}">
                                        {{ $manual->number }} ({{ $manual->lib ?? '-' }}) - {{ $manual->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="drawer_ipl_num" class="form-label">{{ __('IPL Number') }}</label>
                            <input id="drawer_ipl_num"
                                   type="text"
                                   class="form-control"
                                   name="ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label for="drawer_part_number" class="form-label">{{ __('Part Number') }}</label>
                            <input id="drawer_part_number" type="text" class="form-control" name="part_number" required>
                        </div>

                        <div class="col-12">
                            <label for="drawer_name" class="form-label">{{ __('Name') }}</label>
                            <input id="drawer_name" type="text" class="form-control" name="name" required>
                        </div>

                        <div class="col-12">
                            <label for="drawer_img" class="form-label">{{ __('Image') }}</label>
                            <input id="drawer_img" type="file" name="img" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="component-form-section mt-4">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_log_card" name="log_card">
                                <label class="form-check-label" for="drawer_log_card">Log Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_is_bush" name="is_bush">
                                <label class="form-check-label" for="drawer_is_bush">Is Bush</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_kit" name="kit">
                                <label class="form-check-label" for="drawer_kit">Kit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_ndt_list" name="ndt_list">
                                <label class="form-check-label" for="drawer_ndt_list">NDT List</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_cad_list" name="cad_list">
                                <label class="form-check-label" for="drawer_cad_list">CAD List</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_stress_relief_list" name="stress_relief_list">
                                <label class="form-check-label" for="drawer_stress_relief_list">Stress Relief</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="drawer_paint_list" name="paint_list">
                                <label class="form-check-label" for="drawer_paint_list">Paint List</label>
                            </div>
                        </div>

                        <div class="mt-3 d-none" id="drawer_bush_ipl_container">
                            <label for="drawer_bush_ipl_num" class="form-label">{{ __('Initial Bushing IPL Number') }}</label>
                            <input id="drawer_bush_ipl_num"
                                   type="text"
                                   class="form-control"
                                   name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$">
                        </div>
                    </div>

                    <div class="component-form-section mt-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="mb-0">{{ __('Assemblies') }}</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addAssemblyRowBtn">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div id="assemblyRows"></div>
                    </div>

                    <div class="component-form-footer mt-4">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="createComponentSubmitBtn">
                                {{ __('Save Part') }}
                            </button>
                        </div>
                    </div>
                </form>
                <template id="assemblyRowTemplate">
                    <div class="component-assembly-row" data-assembly-row>
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
                                <input type="text"
                                       class="form-control"
                                       data-assembly-field="assy_ipl_num"
                                       pattern="^$|^\d+-\d+[A-Za-z]?$">
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
                                <input type="file"
                                       class="form-control"
                                       accept="image/*"
                                       aria-label="{{ __('Assy Image') }}"
                                       data-assembly-field="assy_img">
                            </div>
                            <div class="col-md-6">
                                <input type="text"
                                       class="form-control"
                                       placeholder="{{ __('Notes') }}"
                                       aria-label="{{ __('Notes') }}"
                                       data-assembly-field="notes">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="editComponentOffcanvas" aria-labelledby="editComponentOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title text-primary" id="editComponentOffcanvasLabel">{{ __('Edit Replaceable Part') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="offcanvas-body">
                <form id="editComponentDrawerForm" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    <div id="editComponentErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_drawer_manual_id" class="form-label">CMM</label>
                            <select name="manual_id" id="edit_drawer_manual_id" class="form-select" required>
                                <option value="">{{ __('Select Manual') }}</option>
                                @foreach($manuals as $manual)
                                    <option value="{{ $manual->id }}">
                                        {{ $manual->number }} ({{ $manual->lib ?? '-' }}) - {{ $manual->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_drawer_ipl_num" class="form-label">{{ __('IPL Number') }}</label>
                            <input id="edit_drawer_ipl_num"
                                   type="text"
                                   class="form-control"
                                   name="ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_drawer_part_number" class="form-label">{{ __('Part Number') }}</label>
                            <input id="edit_drawer_part_number" type="text" class="form-control" name="part_number" required>
                        </div>

                        <div class="col-12">
                            <label for="edit_drawer_name" class="form-label">{{ __('Name') }}</label>
                            <input id="edit_drawer_name" type="text" class="form-control" name="name" required>
                        </div>

                        <div class="col-12">
                            <label for="edit_drawer_img" class="form-label">{{ __('Image') }}</label>
                            <input id="edit_drawer_img" type="file" name="img" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="component-form-section mt-4">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_log_card" name="log_card">
                                <label class="form-check-label" for="edit_drawer_log_card">Log Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_is_bush" name="is_bush">
                                <label class="form-check-label" for="edit_drawer_is_bush">Is Bush</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_kit" name="kit">
                                <label class="form-check-label" for="edit_drawer_kit">Kit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_ndt_list" name="ndt_list">
                                <label class="form-check-label" for="edit_drawer_ndt_list">NDT List</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_cad_list" name="cad_list">
                                <label class="form-check-label" for="edit_drawer_cad_list">CAD List</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_stress_relief_list" name="stress_relief_list">
                                <label class="form-check-label" for="edit_drawer_stress_relief_list">Stress Relief</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_drawer_paint_list" name="paint_list">
                                <label class="form-check-label" for="edit_drawer_paint_list">Paint List</label>
                            </div>
                        </div>

                        <div class="mt-3 d-none" id="edit_drawer_bush_ipl_container">
                            <label for="edit_drawer_bush_ipl_num" class="form-label">{{ __('Initial Bushing IPL Number') }}</label>
                            <input id="edit_drawer_bush_ipl_num"
                                   type="text"
                                   class="form-control"
                                   name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$">
                        </div>
                    </div>

                    <div class="component-form-section mt-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="mb-0">{{ __('Assemblies') }}</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="editAddAssemblyRowBtn">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div id="editAssemblyRows"></div>
                    </div>

                    <div class="component-form-footer mt-4">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="editComponentSubmitBtn">
                                {{ __('Save Part') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- CSV Upload Modal -->
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

                                    <div class="mb-3">
                                        <label for="manual_id_csv" class="form-label">{{__('Select Manual')}}</label>
                                        <select name="manual_id" id="manual_id_csv" class="form-select" required>
                                            <option value="">{{__('Select Manual')}}</option>
                                            @foreach($manuals as $manual)
                                                <option value="{{ $manual->id }}">{{ $manual->number }} - {{ $manual->title }}
                                                    ({{ Str::limit($manual->unit_name_training, 10) }})
                                                </option>
                                            @endforeach
                                        </select>
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
                                            <li><strong>kit</strong>, <strong>ndt_list</strong>, <strong>cad_list</strong>, <strong>stress_relief_list</strong>, <strong>paint_list</strong> - {{__('Flags (0 or 1, optional)')}}</li>
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

        <!-- Manual Modals -->
        @foreach($manuals as $manual)
            @php
                $thumbUrl = $manual->getFirstMediaThumbnailUrl('manuals') ?: asset('img/no-image.png');
                $bigUrl = $manual->getFirstMediaBigUrl('manuals') ?: asset('img/no-image.png');
            @endphp
            <div class="modal fade" id="manualModal{{ $manual->id }}" tabindex="-1"
                 role="dialog" aria-labelledby="manualModalLabel{{ $manual->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content bg-gradient">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="manualModalLabel{{ $manual->id }}">
                                    {{ $manual->title }}{{ __(': ') }}
                                </h5>
                                <h6 style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $manual->unit_name_training ?? '' }}</h6>
                            </div>
                            <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="d-flex">
                                <div class="me-2">
                                    <img src="{{ $bigUrl }}" width="200" alt="Image"/>
                                </div>
                                <div>
                                    <p><strong>{{ __('CMM:') }}</strong> {{ $manual->number }}</p>
                                    <p><strong>{{ __('Description:') }}</strong> {{ $manual->title }}</p>
                                    <p><strong>{{ __('Revision Date:') }}</strong> {{ $manual->revision_date ?? 'N/A' }}</p>
                                    <p><strong>{{ __('AirCraft Type:') }}</strong> {{ $planes[$manual->planes_id] ?? 'N/A' }}</p>
                                    <p><strong>{{ __('MFR:') }}</strong> {{ $builders[$manual->builders_id] ?? 'N/A' }}</p>
                                    <p><strong>{{ __('Scope:') }}</strong> {{ $scopes[$manual->scopes_id] ?? 'N/A' }}</p>
                                    <p><strong>{{ __('Library:') }}</strong> {{ $manual->lib ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <noscript>
            <style>#componentsTableWrapper {visibility: visible !important;}</style>
        </noscript>

        @endsection

@section('scripts')

            <script>
                (() => {
                    const drawerHelpers = {
                        hasSelect2: () => !!(window.jQuery && typeof window.jQuery.fn?.select2 === 'function'),
                        setErrors(errorsBox, messages) {
                            if (!errorsBox) return;

                            if (!messages.length) {
                                errorsBox.classList.add('d-none');
                                errorsBox.innerHTML = '';
                                return;
                            }

                            errorsBox.innerHTML = messages.map(message => `<div>${message}</div>`).join('');
                            errorsBox.classList.remove('d-none');
                        },
                        responseErrors(data, fallback) {
                            return data.errors
                                ? Object.values(data.errors).flat()
                                : [data.message || fallback];
                        },
                        setSelectValue(select, value) {
                            if (!select) return;

                            select.value = value || '';
                            if (this.hasSelect2()) {
                                window.jQuery(select).val(value || '').trigger('change.select2');
                            }
                        },
                        setSubmitting(form, button, savingText, isSubmitting) {
                            if (!button) return;

                            button.dataset.originalHtml ||= button.innerHTML;
                            form.dataset.submitting = isSubmitting ? '1' : '0';
                            button.disabled = isSubmitting;
                            button.innerHTML = isSubmitting
                                ? `<span class="spinner-border spinner-border-sm me-1"></span>${savingText}`
                                : button.dataset.originalHtml;
                        },
                        safeHideSpinner() {
                            if (typeof safeHideSpinner === 'function') safeHideSpinner();
                        },
                        makeAssemblyManager(rows, template, idPrefix) {
                            const renumber = () => {
                                rows?.querySelectorAll('[data-assembly-row]').forEach((row, index) => {
                                    const title = row.querySelector('[data-assembly-title]');
                                    if (title) title.textContent = `Assembly ${index + 1}`;

                                    row.querySelectorAll('[data-assembly-field]').forEach((field) => {
                                        const key = field.dataset.assemblyField;
                                        field.name = `assemblies[${index}][${key}]`;
                                        field.id = `${idPrefix}_assembly_${index}_${key}`;
                                    });

                                    row.querySelectorAll('label').forEach((label) => {
                                        const field = label.parentElement?.querySelector('[data-assembly-field]');
                                        if (field) label.setAttribute('for', field.id);
                                    });

                                    const removeBtn = row.querySelector('[data-remove-assembly]');
                                    if (removeBtn) removeBtn.classList.toggle('d-none', rows.children.length <= 1);
                                });
                            };

                            const add = (values = {}) => {
                                if (!rows || !template) return null;

                                const fragment = template.content.cloneNode(true);
                                const row = fragment.querySelector('[data-assembly-row]');
                                row?.querySelectorAll('[data-assembly-field]').forEach((field) => {
                                    const key = field.dataset.assemblyField;
                                    if (field.type !== 'file') {
                                        field.value = values[key] ?? '';
                                    }
                                });

                                rows.appendChild(fragment);
                                renumber();

                                return rows.lastElementChild;
                            };

                            const reset = (items = [{}]) => {
                                if (!rows) return;

                                rows.innerHTML = '';
                                (items.length ? items : [{}]).forEach(item => add(item));
                            };

                            rows?.addEventListener('click', (event) => {
                                const removeBtn = event.target.closest('[data-remove-assembly]');
                                if (!removeBtn) return;

                                removeBtn.closest('[data-assembly-row]')?.remove();
                                if (!rows.children.length) {
                                    add();
                                } else {
                                    renumber();
                                }
                            });

                            return { add, reset, renumber };
                        },
                    };

                    function initCreateComponentDrawer() {
                        const offcanvasEl = document.getElementById('createComponentOffcanvas');
                        const form = document.getElementById('createComponentDrawerForm');
                        const errorsBox = document.getElementById('createComponentErrors');
                        const submitBtn = document.getElementById('createComponentSubmitBtn');
                        const manualSelect = document.getElementById('drawer_manual_id');
                        const isBush = document.getElementById('drawer_is_bush');
                        const bushContainer = document.getElementById('drawer_bush_ipl_container');
                        const bushInput = document.getElementById('drawer_bush_ipl_num');
                        const assemblyRows = document.getElementById('assemblyRows');
                        const assemblyTemplate = document.getElementById('assemblyRowTemplate');
                        const addAssemblyRowBtn = document.getElementById('addAssemblyRowBtn');
                        const indexManualFilter = document.getElementById('manualFilter');

                        if (!offcanvasEl || !form || form.dataset.bound) return;
                        form.dataset.bound = '1';

                        const hasSelect2 = drawerHelpers.hasSelect2();
                        if (hasSelect2 && manualSelect && !window.jQuery(manualSelect).data('select2')) {
                            window.jQuery(manualSelect).select2({
                                theme: 'bootstrap-5',
                                width: '100%',
                                dropdownParent: window.jQuery(offcanvasEl),
                                placeholder: '{{ __('Select Manual') }}',
                                allowClear: true,
                            });
                        }

                        const setErrors = (messages) => drawerHelpers.setErrors(errorsBox, messages);

                        const syncBush = () => {
                            const checked = !!isBush?.checked;
                            bushContainer?.classList.toggle('d-none', !checked);
                            if (bushInput) {
                                bushInput.required = checked;
                                if (!checked) bushInput.value = '';
                            }
                        };

                        isBush?.addEventListener('change', syncBush);
                        syncBush();

                        const assemblyManager = drawerHelpers.makeAssemblyManager(assemblyRows, assemblyTemplate, 'drawer');
                        addAssemblyRowBtn?.addEventListener('click', () => assemblyManager.add());
                        assemblyManager.reset();

                        offcanvasEl.addEventListener('show.bs.offcanvas', () => {
                            setErrors([]);
                            const selectedManual = indexManualFilter?.value || '';
                            if (manualSelect && selectedManual) {
                                drawerHelpers.setSelectValue(manualSelect, selectedManual);
                            }
                        });

                        form.addEventListener('submit', async (event) => {
                            event.preventDefault();
                            if (form.dataset.submitting === '1') return;
                            setErrors([]);

                            if (!form.checkValidity()) {
                                form.classList.add('was-validated');
                                return;
                            }

                            drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Saving...') }}', true);

                            try {
                                const response = await fetch(form.action, {
                                    method: 'POST',
                                    body: new FormData(form),
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                });

                                const data = await response.json().catch(() => ({}));

                                if (!response.ok || !data.success) {
                                    setErrors(drawerHelpers.responseErrors(data, '{{ __('Failed to submit.') }}'));
                                    return;
                                }

                                form.reset();
                                form.classList.remove('was-validated');
                                drawerHelpers.setSelectValue(manualSelect, '');
                                assemblyManager.reset();
                                syncBush();

                                const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
                                offcanvas.hide();

                                window.dispatchEvent(new CustomEvent('components:index-reload'));

                                if (typeof showNotification === 'function') {
                                    showNotification(data.message || '{{ __('Component created successfully.') }}', 'success');
                                }
                            } catch (err) {
                                console.error(err);
                                setErrors(['{{ __('Failed to submit.') }}']);
                            } finally {
                                drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Saving...') }}', false);
                                drawerHelpers.safeHideSpinner();
                            }
                        });
                    }

                    function initEditComponentDrawer() {
                        const offcanvasEl = document.getElementById('editComponentOffcanvas');
                        const form = document.getElementById('editComponentDrawerForm');
                        const errorsBox = document.getElementById('editComponentErrors');
                        const submitBtn = document.getElementById('editComponentSubmitBtn');
                        const manualSelect = document.getElementById('edit_drawer_manual_id');
                        const isBush = document.getElementById('edit_drawer_is_bush');
                        const bushContainer = document.getElementById('edit_drawer_bush_ipl_container');
                        const bushInput = document.getElementById('edit_drawer_bush_ipl_num');
                        const assemblyRows = document.getElementById('editAssemblyRows');
                        const assemblyTemplate = document.getElementById('assemblyRowTemplate');
                        const addAssemblyRowBtn = document.getElementById('editAddAssemblyRowBtn');

                        if (!offcanvasEl || !form || form.dataset.bound) return;
                        form.dataset.bound = '1';

                        const hasSelect2 = drawerHelpers.hasSelect2();
                        if (hasSelect2 && manualSelect && !window.jQuery(manualSelect).data('select2')) {
                            window.jQuery(manualSelect).select2({
                                theme: 'bootstrap-5',
                                width: '100%',
                                dropdownParent: window.jQuery(offcanvasEl),
                                placeholder: '{{ __('Select Manual') }}',
                                allowClear: true,
                            });
                        }

                        const setErrors = (messages) => drawerHelpers.setErrors(errorsBox, messages);
                        const assemblyManager = drawerHelpers.makeAssemblyManager(assemblyRows, assemblyTemplate, 'edit_drawer');

                        const syncBush = () => {
                            const checked = !!isBush?.checked;
                            bushContainer?.classList.toggle('d-none', !checked);
                            if (bushInput) {
                                bushInput.required = checked;
                                if (!checked) bushInput.value = '';
                            }
                        };

                        const setValue = (name, value) => {
                            const field = form.elements[name];
                            if (field) field.value = value ?? '';
                        };

                        const fillForm = (component) => {
                            form.reset();
                            form.classList.remove('was-validated');
                            setErrors([]);

                            drawerHelpers.setSelectValue(manualSelect, component.manual_id);
                            setValue('ipl_num', component.ipl_num);
                            setValue('part_number', component.part_number);
                            setValue('name', component.name);
                            setValue('bush_ipl_num', component.bush_ipl_num);
                            if (isBush) isBush.checked = !!component.is_bush;
                            const logCard = document.getElementById('edit_drawer_log_card');
                            if (logCard) logCard.checked = !!component.log_card;
                            ['kit', 'ndt_list', 'cad_list', 'stress_relief_list', 'paint_list'].forEach((field) => {
                                const checkbox = document.getElementById(`edit_drawer_${field}`);
                                if (checkbox) checkbox.checked = !!component[field];
                            });

                            let assemblies = Array.isArray(component.assemblies) ? component.assemblies : [];
                            if (!assemblies.length && (component.assy_ipl_num || component.assy_part_number || component.units_assy)) {
                                assemblies = [{
                                    assy_ipl_num: component.assy_ipl_num,
                                    assy_part_number: component.assy_part_number,
                                    units_assy: component.units_assy,
                                    notes: '',
                                }];
                            }
                            assemblyManager.reset(assemblies.length ? assemblies : [{}]);
                            syncBush();
                        };

                        addAssemblyRowBtn?.addEventListener('click', () => assemblyManager.add());
                        isBush?.addEventListener('change', syncBush);
                        assemblyManager.reset();
                        syncBush();

                        document.addEventListener('click', async (event) => {
                            const button = event.target.closest('.open-edit-component-drawer');
                            if (!button) return;

                            event.preventDefault();
                            setErrors([]);
                            drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Loading...') }}', true);
                            bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();

                            try {
                                const response = await fetch(button.dataset.componentUrl, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                });
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok || !data.success) {
                                    setErrors(drawerHelpers.responseErrors(data, '{{ __('Failed to load part.') }}'));
                                    return;
                                }

                                form.action = button.dataset.updateUrl;
                                fillForm(data.component);
                            } catch (err) {
                                console.error(err);
                                setErrors(['{{ __('Failed to load part.') }}']);
                            } finally {
                                drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Loading...') }}', false);
                                drawerHelpers.safeHideSpinner();
                            }
                        });

                        form.addEventListener('submit', async (event) => {
                            event.preventDefault();
                            if (form.dataset.submitting === '1') return;
                            setErrors([]);

                            if (!form.checkValidity()) {
                                form.classList.add('was-validated');
                                return;
                            }

                            drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Saving...') }}', true);

                            try {
                                const response = await fetch(form.action, {
                                    method: 'POST',
                                    body: new FormData(form),
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                });
                                const data = await response.json().catch(() => ({}));

                                if (!response.ok || !data.success) {
                                    setErrors(drawerHelpers.responseErrors(data, '{{ __('Failed to submit.') }}'));
                                    return;
                                }

                                form.classList.remove('was-validated');
                                bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
                                window.dispatchEvent(new CustomEvent('components:index-reload'));

                                if (typeof showNotification === 'function') {
                                    showNotification(data.message || '{{ __('Component updated successfully.') }}', 'success');
                                }
                            } catch (err) {
                                console.error(err);
                                setErrors(['{{ __('Failed to submit.') }}']);
                            } finally {
                                drawerHelpers.setSubmitting(form, submitBtn, '{{ __('Saving...') }}', false);
                                drawerHelpers.safeHideSpinner();
                            }
                        });
                    }

                    const initComponentDrawers = () => {
                        initCreateComponentDrawer();
                        initEditComponentDrawer();
                    };

                    document.addEventListener('DOMContentLoaded', initComponentDrawers);
                    window.addEventListener('pageshow', initComponentDrawers);
                })();
            </script>

            <script>
                (() => {
                    const LS = {
                        search: 'components_search',
                        manual: 'components_manual_id',
                        sortCol: 'components_sort_col',
                        sortDir: 'components_sort_dir',
                        scrollY: 'components_scroll_y',
                        scrollRestore: 'components_scroll_restore',
                    };

                    const debounce = (fn, wait = 250) => {
                        let t;
                        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
                    };

                    async function initComponentsInfiniteIndex() {
                        const table = document.getElementById('componentsTable');
                        const tbody = table?.querySelector('tbody');
                        const wrapper = document.getElementById('componentsTableWrapper');
                        const status = document.getElementById('componentsLoadStatus');
                        const searchInput = document.getElementById('searchInput');
                        const searchClearBtn = document.getElementById('searchClearBtn');
                        const manualFilter = document.getElementById('manualFilter');
                        const manualLockIcon = document.getElementById('componentsManualLockIcon');
                        const addPartButton = document.getElementById('addPartButton');
                        const componentsCount = document.getElementById('componentsCount');
                        const sortableHeaders = Array.from(table?.querySelectorAll('th.sortable') || []);

                        if (!table || !tbody || !wrapper || !searchInput || !manualFilter) return;

                        const state = {
                            page: Number(wrapper.dataset.nextPage || 2),
                            hasMore: wrapper.dataset.hasMore === '1',
                            loading: false,
                            perPage: Number(wrapper.dataset.perPage || 100),
                            sortCol: Number(localStorage.getItem(LS.sortCol) || 0),
                            sortDir: localStorage.getItem(LS.sortDir) === 'desc' ? 'desc' : 'asc',
                        };

                        const hasSelect2 = !!(window.jQuery && typeof window.jQuery.fn?.select2 === 'function');
                        const safeShow = () => { if (typeof safeShowSpinner === 'function') safeShowSpinner(); };
                        const safeHide = () => { if (typeof safeHideSpinner === 'function') safeHideSpinner(); };

                        function formatManual(state) {
                            if (!state.id) return state.text;

                            const el = state.element;
                            const number = el.dataset.number;
                            const lib = el.dataset.lib;
                            const title = el.dataset.title;
                            const locked = el.dataset.partsLocked === '1';

                            return `
                                <div>
                                    <strong>${number}</strong>
                                    ${lib ? ` <span class="manual-option-lib">&nbsp;&nbsp; (${lib}) </span>` : ''}
                                    <span class="manual-option-title"> - ${title}</span>
                                    ${locked ? '<i class="bi bi-lock-fill manual-part-lock-icon ms-2"></i>' : ''}
                                </div>
                            `;
                        }

                        function formatManualSelected(state) {
                            if (!state.id) return state.text;

                            const number = state.element.dataset.number || '';
                            const lib = state.element.dataset.lib || '';
                            const locked = state.element.dataset.partsLocked === '1';
                            const text = lib ? `${number} <span class="manual-selection-lib">(${lib})</span>` : number;
                            return text + (locked ? '<i class="bi bi-lock-fill manual-part-lock-icon ms-2"></i>' : '');
                        }

                        function updateManualPartLockUi() {
                            const option = manualFilter.selectedOptions?.[0] || null;
                            const locked = !!(option && option.value && option.dataset.partsLocked === '1');
                            const lockedBy = option?.dataset.lockedBy || '';
                            if (manualLockIcon) {
                                manualLockIcon.classList.toggle('d-none', !locked);
                                manualLockIcon.title = lockedBy ? `Locked by ${lockedBy}` : '{{ __('Manual parts are locked') }}';
                            }
                            if (addPartButton) {
                                addPartButton.disabled = locked && !@json(auth()->user()?->canManageLockedManualParts() ?? false);
                                addPartButton.title = addPartButton.disabled ? '{{ __('Manual parts are locked') }}' : '';
                            }
                        }

                        function updateSortHeaders() {
                            sortableHeaders.forEach(th => {
                                th.classList.remove('sorted-asc', 'sorted-desc');
                                th.dataset.direction = '';
                            });

                            const active = sortableHeaders.find(th => th.cellIndex === state.sortCol);
                            if (active) {
                                active.dataset.direction = state.sortDir;
                                active.classList.add(state.sortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                            }
                        }

                        function setStatus(text) {
                            if (status) status.textContent = text || '';
                        }

                        function initAssyPopovers(scope = tbody) {
                            if (!window.bootstrap?.Popover) return;

                            scope.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
                                const existing = bootstrap.Popover.getInstance(el);
                                if (existing) existing.dispose();

                                new bootstrap.Popover(el, {
                                    html: true,
                                    container: 'body',
                                    sanitize: false,
                                });
                            });
                        }

                        async function updateComponentFlag(input) {
                            const previous = !input.checked;
                            let bushIplNum = input.dataset.bushIplNum || '';

                            if (input.dataset.field === 'is_bush') {
                                if (input.checked) {
                                    const entered = typeof window.inputDialog === 'function'
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
                                        const confirmed = await window.confirmDialog({
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
                                const payload = {
                                    field: input.dataset.field,
                                    value: input.checked ? 1 : 0,
                                };

                                if (input.dataset.field === 'is_bush') {
                                    payload.bush_ipl_num = bushIplNum;
                                }

                                const response = await fetch(input.dataset.url, {
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

                                const data = await response.json().catch(() => ({}));
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

                        function emptyRow() {
                            return '<tr class="components-empty-row"><td colspan="14" class="text-center text-muted py-4">{{ __('PARTS NOT FOUND') }}</td></tr>';
                        }

                        function currentParams(page) {
                            const params = new URLSearchParams();
                            params.set('ajax', '1');
                            params.set('page', String(page));
                            params.set('per_page', String(state.perPage));
                            params.set('sort_col', String(state.sortCol));
                            params.set('sort_dir', state.sortDir);

                            const search = (searchInput.value || '').trim();
                            const manual = (manualFilter.value || '').trim();
                            if (search) params.set('search', search);
                            if (manual) params.set('manual_id', manual);

                            return params;
                        }

                        async function loadPage(page, replace = false) {
                            if (state.loading) return;
                            if (!replace && !state.hasMore) return;

                            state.loading = true;
                            setStatus(page === 1 ? '{{ __('Loading...') }}' : '{{ __('Loading more...') }}');
                            safeShow();

                            try {
                                const response = await fetch(`${window.location.pathname}?${currentParams(page).toString()}`, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                                const data = await response.json();
                                if (replace) tbody.innerHTML = '';

                                if ((data.rows_html || '').trim()) {
                                    tbody.insertAdjacentHTML('beforeend', data.rows_html);
                                    initAssyPopovers(tbody);
                                } else if (replace) {
                                    tbody.innerHTML = emptyRow();
                                }

                                state.page = Number(data.next_page || page + 1);
                                state.hasMore = !!data.has_more;
                                wrapper.dataset.nextPage = String(state.page);
                                wrapper.dataset.hasMore = state.hasMore ? '1' : '0';

                                if (componentsCount) componentsCount.textContent = Number(data.total || 0);
                                setStatus(state.hasMore ? '' : '{{ __('All parts loaded') }}');
                            } catch (err) {
                                console.error(err);
                                setStatus('{{ __('Failed to load parts') }}');
                            } finally {
                                state.loading = false;
                                safeHide();
                            }
                        }

                        function persistFilters() {
                            localStorage.setItem(LS.search, (searchInput.value || '').trim());
                            localStorage.setItem(LS.manual, (manualFilter.value || '').trim());
                            localStorage.setItem(LS.sortCol, String(state.sortCol));
                            localStorage.setItem(LS.sortDir, state.sortDir);
                        }

                        function reloadFromFirstPage() {
                            persistFilters();
                            wrapper.scrollTop = 0;
                            state.hasMore = true;
                            state.page = 1;
                            updateSortHeaders();
                            return loadPage(1, true);
                        }

                        async function restoreScrollPosition() {
                            if (localStorage.getItem(LS.scrollRestore) !== '1') return;
                            localStorage.removeItem(LS.scrollRestore);

                            const y = parseInt(localStorage.getItem(LS.scrollY) || '0', 10);
                            if (!Number.isFinite(y) || y <= 0) return;

                            while (state.hasMore && wrapper.scrollHeight < y + wrapper.clientHeight) {
                                await loadPage(state.page, false);
                            }

                            wrapper.scrollTop = y;
                        }

                        if (hasSelect2) {
                            const $mf = window.jQuery(manualFilter);
                            if (!$mf.data('select2')) {
                                $mf.select2({
                                    width: '520px',
                                    placeholder: 'All Manuals',
                                    allowClear: true,
                                    dropdownAutoWidth: true,
                                    templateResult: formatManual,
                                    templateSelection: formatManualSelected,
                                    escapeMarkup: m => m
                                });
                            }
                        }

                        searchInput.value = localStorage.getItem(LS.search) || '';
                        const savedManual = localStorage.getItem(LS.manual) || '';
                        manualFilter.value = Array.from(manualFilter.options).some(o => o.value === savedManual) ? savedManual : '';
                        if (hasSelect2) window.jQuery(manualFilter).val(manualFilter.value).trigger('change.select2');
                        updateManualPartLockUi();

                        updateSortHeaders();
                        initAssyPopovers();

                        if (!table.dataset.infiniteBound) {
                            table.dataset.infiniteBound = '1';

                            searchInput.addEventListener('input', debounce(reloadFromFirstPage, 250));

                            searchClearBtn?.addEventListener('click', () => {
                                searchInput.value = '';
                                localStorage.removeItem(LS.search);
                                reloadFromFirstPage();
                            });

                            manualFilter.addEventListener('change', () => {
                                updateManualPartLockUi();
                                reloadFromFirstPage();
                            });

                            if (hasSelect2) {
                                window.jQuery(manualFilter).on('select2:select select2:clear', () => {
                                    updateManualPartLockUi();
                                    reloadFromFirstPage();
                                });
                            }

                            sortableHeaders.forEach(th => {
                                th.style.cursor = 'pointer';
                                th.addEventListener('click', () => {
                                    state.sortCol = th.cellIndex;
                                    state.sortDir = th.dataset.direction === 'asc' ? 'desc' : 'asc';
                                    reloadFromFirstPage();
                                });
                            });

                            wrapper.addEventListener('scroll', debounce(() => {
                                localStorage.setItem(LS.scrollY, String(wrapper.scrollTop || 0));

                                const distanceToBottom = wrapper.scrollHeight - wrapper.scrollTop - wrapper.clientHeight;
                                if (distanceToBottom <= 180) loadPage(state.page, false);
                            }, 100), { passive: true });

                            tbody.addEventListener('click', (e) => {
                                const a = e.target.closest('a');
                                if (!a || !a.querySelector('.bi-pencil-square')) return;

                                localStorage.setItem(LS.scrollRestore, '1');
                                localStorage.setItem(LS.scrollY, String(wrapper.scrollTop || 0));
                            });

                            tbody.addEventListener('change', (e) => {
                                const input = e.target.closest('.component-flag-toggle');
                                if (!input) return;
                                updateComponentFlag(input);
                            });

                            window.addEventListener('components:index-reload', reloadFromFirstPage);
                        }

                        const hasSavedState = (searchInput.value || manualFilter.value || state.sortCol !== 0 || state.sortDir !== 'asc');
                        if (hasSavedState) {
                            await reloadFromFirstPage();
                            wrapper.style.visibility = 'visible';
                        } else {
                            wrapper.style.visibility = 'visible';
                            restoreScrollPosition();
                        }
                    }

                    document.addEventListener('DOMContentLoaded', initComponentsInfiniteIndex);
                    window.addEventListener('pageshow', initComponentsInfiniteIndex);
                })();
            </script>


@endsection
