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

        #componentsTable {
            background: transparent !important;
            color: #e6e6e6;
        }

        #componentsTable tbody tr,
        #componentsTable tbody td{
            background: transparent !important;
            border-color: rgba(255,255,255,.08) !important;
        }

        /* Лёгкая подложка на строках, чтобы читалось */
        #componentsTable tbody tr{
            box-shadow: inset 0 0 0 9999px rgba(255,255,255,0.03);
        }
        #componentsTable tbody tr:hover{
            box-shadow: inset 0 0 0 9999px rgba(255,255,255,0.06);
        }

        /* Заголовок чуть светлее */
        #componentsTable thead th{
            background: linear-gradient(180deg, #1a1d21 0%, #14161a 100%) !important;
            color: #f2f2f2 !important;
            border-color: rgba(255,255,255,.10) !important;
        }

        /* Границы мягкие */
        #componentsTable.table-bordered > :not(caption) > *{
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
            min-width: 80px;
            max-width: 120px;
        }

        .table th:nth-child(5), .table td:nth-child(5) {
            min-width: 100px;
            max-width: 150px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 100px;
            max-width: 150px;
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
            .table th:nth-child(4), .table td:nth-child(4),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
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

        /* ===== Select2 Dark (ChatGPT-like) ===== */
        .select2-container .select2-selection--single{
            height: 40px !important;
            background: #0f1114 !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 10px !important;
            color: #e6e6e6 !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,.25);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered{
            color: #e6e6e6 !important;
            line-height: 38px !important;
            padding-left: 12px !important;
            padding-right: 38px !important; /* место под крестик/стрелку */
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder{
            color: rgba(230,230,230,.55) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow{
            height: 38px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b{
            border-color: rgba(230,230,230,.7) transparent transparent transparent !important;
        }

        /* dropdown */
        .select2-container--default .select2-dropdown{
            background: #0f1114 !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            border-radius: 12px !important;
            overflow: hidden;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field{
            background: #0b0c0e !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 10px !important;
            color: #e6e6e6 !important;
            padding: 8px 10px !important;
            outline: none !important;
        }

        /* options */
        .select2-container--default .select2-results__option{
            color: #e6e6e6 !important;
            padding: 8px 12px !important;
            line-height: 1.35;

        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
            background: rgba(255,255,255,.08) !important;
            color: #fff !important;
        }
        .select2-container--default .select2-results__option--selected{
            background: rgba(255,255,255,.06) !important;
            color: #fff !important;
        }

        /* focus */
        .select2-container--default.select2-container--focus .select2-selection--single{
            border-color: rgba(255,255,255,.18) !important;
            box-shadow: 0 0 0 3px rgba(255,255,255,.06) !important;
        }

        /* ===== Clear (крестик) — сдвинуть чуть левее ===== */
        .select2-container--default .select2-selection--single .select2-selection__clear{
            color: rgba(230,230,230,.75) !important;
            font-size: 18px;
            line-height: 1;
            position: absolute;
            right: 30px;            /* <-- было ближе к стрелке, делаем левее */
            top: 50%;
            transform: translateY(-50%);
            padding: 0 6px;
        }
        .select2-container--default .select2-selection--single .select2-selection__clear:hover{
            color: #fff !important;
        }

        /* ===== Select2 dropdown height ===== */
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

        th.sortable.sorted-asc  i { transform: rotate(180deg); opacity: 1; }
        th.sortable.sorted-desc i { transform: rotate(0deg);   opacity: 1; }
        th.sortable i { transition: transform .15s ease, opacity .15s ease; opacity: .6; }
        #manualFilter + .select2 { width: 460px !important; }

    </style>

@endsection

@section('content')


    <div class="card dir-page">
        <div class="card-header my-0 ">
            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <h5 class="text-primary manage-header">{{__('Replaceable Parts')}}( <span class="text-success"
                                                                          id="componentsCount">{{$components->count()}}</span>)</h5>
                <span id="manualIndicator" class="text-muted"></span>
                <div class="d-flex my-2 gap-2 flex-wrap">
                    <!-- Filter by Manual -->
                    <div>
                        <select id="manualFilter" class="form-select" style="height:40px;width:460px;">
                            <option value="">{{ __('All Manuals') }}</option>

                            @foreach($manuals as $manual)
                                <option
                                    value="{{ $manual->id }}"
                                    data-number="{{ $manual->number }}"
                                    data-lib="{{ $manual->lib }}"
                                    data-title="{{ $manual->title }}"
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

                    <!-- CSV Components -->
                    <a href="{{ route('components.csv-components') }}" class="btn btn-outline-info" style="height: 40px">
                        <i class="bi bi-file-earmark-spreadsheet"></i> {{__('CSV Parts')}}
                    </a>

                    <!-- Add Component -->
                    <a href="{{ route('components.create') }}" class="btn btn-outline-primary" style="height: 40px">
                        {{__('Add Part')}}
                    </a>
                </div>
            </div>

            @if(count($components))

                <div class="table-wrapper me-3 p-2 pt-0 dir-panel" id="componentsTableWrapper" style="visibility:hidden">
                    <table id="componentsTable" class="table table-sm table-hover bg-gradient align-middle table-bordered dir-table">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-center sortable">{{__('IPL Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center sortable">{{__('Part Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center sortable">{{__('Name')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                            <th class="text-center">{{__('Image')}}</th>
                            <th class="text-center">{{__('Manual')}}</th>
                            <th class="text-center">{{__('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($components as $component)
                            <tr data-manual-id="{{ $component->manual_id ?? '' }}">
                                <td class="text-center">{{$component->ipl_num}}</td>
                                <td class="text-center">{{$component->part_number}}</td>
                                <td class="text-center">{{$component->name}}</td>
                                <td class="text-center" style="width: 120px;">
                                    @if($component->getMedia('components')->isNotEmpty())
                                        <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                                            <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('components') }}" width="40" height="40" alt="IMG"/>
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($component->manual)
                                        <a href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#manualModal{{ $component->manual->id }}">
                                            {{$component->manual->number}}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('components.edit',['component' => $component->id]) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('components.destroy', $component->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                <H5 CLASS="text-center">{{__('PARTS NOT FOUND')}}</H5>
            @endif

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
                                            <li><strong>repair</strong> - {{__('Repair flag (0 or 1, optional)')}}</li>
                                            <li><strong>is_bush</strong> - {{__('Is bushing (0 or 1, optional)')}}</li>
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

                    function initComponentsIndex() {
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

                        const table = document.getElementById('componentsTable');
                        const tbody = table?.querySelector('tbody');
                        const wrapper = document.getElementById('componentsTableWrapper');

                        const searchInput = document.getElementById('searchInput');
                        const searchClearBtn = document.getElementById('searchClearBtn');
                        const manualFilter = document.getElementById('manualFilter');

                        const componentsCount = document.getElementById('componentsCount');
                        const manualIndicator = document.getElementById('manualIndicator');

                        wrapper && (wrapper.style.visibility = 'hidden');
                        safeShowSpinner();

                        if (!table || !tbody || !searchInput || !manualFilter) return;

                        // --- helper to show/hide table even when coming from bfcache
                        const showUI = () => {
                            wrapper?.classList.remove('is-preloading');
                            if (typeof safeHideSpinner === 'function') safeHideSpinner();
                        };

                        try {
                            // Always start from "preloading" state, then always remove it in finally
                            wrapper?.classList.add('is-preloading');
                            if (typeof safeShowSpinner === 'function') safeShowSpinner();

                            // Cache rows (rebuild each init for Back/Forward safety)
                            const rows = Array.from(tbody.querySelectorAll('tr'));
                            const cache = rows.map((row, idx) => ({
                                el: row,
                                idx,
                                manualId: row.getAttribute('data-manual-id') || '',
                                searchText: (row.textContent || '').toLowerCase(),
                                cells: Array.from(row.querySelectorAll('td')).map(td => (td.textContent || '').trim()),
                            }));

                            // Select2 init once

                            const hasSelect2 = !!(window.jQuery && typeof window.jQuery.fn?.select2 === 'function');
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

                            function formatManual(state) {
                                if (!state.id) return state.text;

                                const el = state.element;
                                const number = el.dataset.number;
                                const lib    = el.dataset.lib;
                                const title  = el.dataset.title;

                                return `
                                            <div>
                                                <strong>${number}</strong>
                                                ${lib ? ` <span style="color: #4c8bf5 ">&nbsp;&nbsp; (${lib}) </span>` : ''}
                                                <span style="color:#adb5bd"> — ${title}</span>
                                            </div>
                                        `;
                            }

                            function formatManualSelected(state) {
                                if (!state.id) return state.text;

                                const el = state.element;
                                return el.dataset.number;
                            }
                            function updateManualIndicator() {
                                if (!manualIndicator) return;

                                if (!manualFilter.value) {
                                    manualIndicator.textContent = '';
                                    return;
                                }

                                const optText = manualFilter.options[manualFilter.selectedIndex]?.text || '';
                                const manualNumber = optText.split(' - ')[0].replace(/\(.+?\)/g, '').trim();
                                manualIndicator.textContent = `Manual: ${manualNumber}`;
                            }

                            function applyFilter(persist = true) {
                                const s = (searchInput.value || '').toLowerCase().trim();
                                const m = (manualFilter.value || '').trim();

                                if (persist) {
                                    localStorage.setItem(LS.search, s);
                                    localStorage.setItem(LS.manual, m);
                                }

                                let visible = 0;
                                for (const r of cache) {
                                    const okSearch = !s || r.searchText.includes(s);
                                    const okManual = !m || r.manualId === m;
                                    const ok = okSearch && okManual;

                                    r.el.style.display = ok ? '' : 'none';
                                    if (ok) visible++;
                                }

                                if (componentsCount) componentsCount.textContent = visible;
                                updateManualIndicator();
                            }

                            const sortableHeaders = Array.from(table.querySelectorAll('th.sortable'));

                            function smartCompare(a, b, dir, colIndex) {
                                const A = (a.cells[colIndex] || '').trim();
                                const B = (b.cells[colIndex] || '').trim();

                                const nA = Number(A), nB = Number(B);
                                const aNum = A !== '' && !Number.isNaN(nA);
                                const bNum = B !== '' && !Number.isNaN(nB);

                                if (aNum && bNum) return dir === 'asc' ? (nA - nB) : (nB - nA);

                                const c = A.localeCompare(B, undefined, { numeric: true, sensitivity: 'base' });
                                return dir === 'asc' ? c : -c;
                            }

                            function applySort(colIndex, dir, persist = true) {
                                if (persist) {
                                    localStorage.setItem(LS.sortCol, String(colIndex));
                                    localStorage.setItem(LS.sortDir, dir);
                                }

                                const ordered = [...cache].sort((a, b) => {
                                    const c = smartCompare(a, b, dir, colIndex);
                                    return c !== 0 ? c : (a.idx - b.idx);
                                });

                                const frag = document.createDocumentFragment();
                                ordered.forEach(r => frag.appendChild(r.el));
                                tbody.appendChild(frag);

                                sortableHeaders.forEach(th => {
                                    th.classList.remove('sorted-asc', 'sorted-desc');
                                    th.dataset.direction = '';
                                });

                                const active = sortableHeaders.find(th => th.cellIndex === colIndex);
                                if (active) {
                                    active.dataset.direction = dir;
                                    active.classList.add(dir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                                }
                            }

                            // Bind events only once per page lifetime
                            if (!table.dataset.bound) {
                                table.dataset.bound = '1';

                                // Search typing
                                searchInput.addEventListener('input', debounce(() => {
                                    safeShowSpinner();
                                    applyFilter(true);
                                    requestAnimationFrame(safeHideSpinner);
                                }, 250));

                                // Clear search
                                if (searchClearBtn) {
                                    searchClearBtn.addEventListener('click', () => {
                                        searchInput.value = '';
                                        localStorage.removeItem(LS.search);

                                        safeShowSpinner();
                                        applyFilter(true);
                                        requestAnimationFrame(safeHideSpinner);
                                    });
                                }

                                // Manual change
                                manualFilter.addEventListener('change', () => {
                                    safeShowSpinner();
                                    applyFilter(true);
                                    requestAnimationFrame(safeHideSpinner);
                                });

                                // Select2 events (fixes "manual works only after click X")
                                if (hasSelect2) {
                                    window.jQuery(manualFilter).on('select2:select select2:clear', () => {
                                        safeShowSpinner();
                                        applyFilter(true);
                                        requestAnimationFrame(safeHideSpinner);
                                    });
                                }

                                // Sorting
                                sortableHeaders.forEach(th => {
                                    th.style.cursor = 'pointer';
                                    th.addEventListener('click', () => {
                                        const colIndex = th.cellIndex;
                                        const cur = th.dataset.direction === 'asc'
                                            ? 'asc'
                                            : (th.dataset.direction === 'desc' ? 'desc' : '');
                                        const next = cur === 'asc' ? 'desc' : 'asc';

                                        safeShowSpinner();
                                        applySort(colIndex, next, true);
                                        requestAnimationFrame(safeHideSpinner);
                                    });
                                });

                                // Scroll save + restore flag on Edit click
                                const saveScroll = debounce(() => {
                                    localStorage.setItem(LS.scrollY, String(window.scrollY || 0));
                                }, 150);

                                window.addEventListener('scroll', saveScroll, { passive: true });

                                tbody.addEventListener('click', (e) => {
                                    const a = e.target.closest('a');
                                    if (!a) return;
                                    if (!a.querySelector('.bi-pencil-square')) return;

                                    localStorage.setItem(LS.scrollRestore, '1');
                                    localStorage.setItem(LS.scrollY, String(window.scrollY || 0));
                                });
                            }

                            // Restore UI state (search/manual/sort)
                            searchInput.value = localStorage.getItem(LS.search) || '';

                            const savedManual = localStorage.getItem(LS.manual) || '';
                            manualFilter.value = Array.from(manualFilter.options).some(o => o.value === savedManual)
                                ? savedManual
                                : '';

                            if (hasSelect2) {
                                window.jQuery(manualFilter).val(manualFilter.value).trigger('change.select2');
                            }

                            const sortCol = parseInt(localStorage.getItem(LS.sortCol) || '0', 10);
                            const sortDir = (localStorage.getItem(LS.sortDir) === 'desc') ? 'desc' : 'asc';
                            applySort(sortCol, sortDir, false);

                            applyFilter(false);

                            // Restore scroll if needed (Edit -> Back)
                            if (localStorage.getItem(LS.scrollRestore) === '1') {
                                localStorage.removeItem(LS.scrollRestore);
                                const y = parseInt(localStorage.getItem(LS.scrollY) || '0', 10);
                                requestAnimationFrame(() => {
                                    window.scrollTo({ top: Number.isFinite(y) ? y : 0, left: 0, behavior: 'instant' });
                                });
                            }

                        } catch (err) {
                            console.error(err);
                            if (typeof showErrorMessage === 'function') {
                                showErrorMessage('JS error on Components page');
                            } else if (typeof showNotification === 'function') {
                                showNotification('JS error on Components page', 'error');
                            }
                        } finally {
                            // ALWAYS show UI again (critical for bfcache back)
                            if (wrapper) wrapper.style.visibility = 'visible';
                            safeHideSpinner();
                        }
                    }


                    // обычная загрузка
                    document.addEventListener('DOMContentLoaded', initComponentsIndex);

                    // Back/Forward cache: DOMContentLoaded не гарантирован — поэтому pageshow
                    window.addEventListener('pageshow', initComponentsIndex);




                })();
            </script>


@endsection
