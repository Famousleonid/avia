@extends('admin.master')

@section('style')
    <style>

        .table-wrapper.is-preloading{
            visibility: hidden;
        }

        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            background: linear-gradient(180deg, #111316 0%, #0b0c0e 100%);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.35);
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
            height: 50px;
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

    </style>
@endsection

@section('content')


    <div class="card shadow">
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <h5 class="text-primary manage-header">{{__('Components')}}( <span class="text-success" id="componentsCount">{{$components->count()}}</span>)</h5>
                <span id="manualIndicator" class="text-muted"></span>
                <div class="d-flex my-2 gap-2 flex-wrap">
                    <!-- Filter by Manual -->
                    <div>
                        <select id="manualFilter" class="form-select" style="height: 40px; width: 300px;">
                            <option value="">{{__('All Manuals')}}</option>
                            @foreach($manuals as $manual)
                                <option value="{{$manual->id}}">{{$manual->number}} - {{$manual->title}}
                                    @if($manual->unit_name_training)
                                        ({{ Str::limit($manual->unit_name_training, 10) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="clearable-input">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>

                    <!-- CSV Components -->
                    <a href="{{ route('components.csv-components') }}" class="btn btn-outline-info" style="height: 40px">
                        <i class="bi bi-file-earmark-spreadsheet"></i> {{__('CSV Components')}}
                    </a>

                {{--                    <!-- Upload CSV -->--}}
                {{--                    <button type="button" class="btn btn-outline-success" style="height: 40px" data-bs-toggle="modal" data-bs-target="#uploadCsvModal">--}}
                {{--                        <i class="bi bi-upload"></i> {{__('Upload CSV')}}--}}
                {{--                    </button>--}}

                <!-- Add Component -->
                    <a href="{{ route('components.create') }}" class="btn btn-outline-primary" style="height: 40px">
                        {{__('Add Component')}}
                    </a>
                </div>
            </div>

            @if(count($components))

                <div class="table-wrapper me-3 p-2 pt-0 is-preloading" id="componentsTableWrapper">
                    <table id="componentsTable" class="table table-sm table-hover bg-gradient align-middle table-bordered">
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
                            <tr data-manual-id="{{ $component->manual_id }}">
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
                                    @if($component->manuals)
                                        <a href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#manualModal{{ $component->manuals->id }}">
                                            {{$component->manuals->number}}
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
                <H5 CLASS="text-center">{{__('COMPONENTS NOT FOUND')}}</H5>
            @endif

        </div>

        <!-- CSV Upload Modal -->
        <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadCsvModalLabel">
                            {{__('Upload Components CSV')}}
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
                                            <i class="bi bi-upload"></i> {{__('Upload Components')}}
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
                                            <li><strong>name</strong> - {{__('Component name (required)')}}</li>
                                            <li><strong>ipl_num</strong> - {{__('IPL number (required)')}}</li>
                                            <li><strong>assy_ipl_num</strong> - {{__('Assembly IPL number (optional)')}}</li>
                                            <li><strong>log_card</strong> - {{__('Log card (0 or 1, optional)')}}</li>
                                            <li><strong>repair</strong> - {{__('Repair flag (0 or 1, optional)')}}</li>
                                            <li><strong>is_bush</strong> - {{__('Is bushing (0 or 1, optional)')}}</li>
                                            <li><strong>bush_ipl_num</strong> - {{__('Bushing IPL number (optional)')}}</li>
                                        </ul>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <small><i class="bi bi-info-circle"></i> <strong>{{__('Note:')}}</strong> {{__('Exact duplicate components will be automatically skipped. Multiple components with the same part_number but different IPL numbers are allowed in the same manual. Uploaded CSV files will be saved and can be viewed later.')}}</small>
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

        @endsection

        @section('scripts')

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    try {
                        const table = document.getElementById('componentsTable');
                        const searchInput = document.getElementById('searchInput');
                        const manualFilter = document.getElementById('manualFilter');
                        const manualClearBtn = document.getElementById('manualClearBtn'); // опционально
                        const componentsCount = document.getElementById('componentsCount');
                        const manualIndicator = document.getElementById('manualIndicator');
                        const tableWrapper = document.getElementById('componentsTableWrapper');

                        // ====== manual select search (Select2) ======
                        if (window.jQuery && manualFilter) {
                            $(manualFilter).select2({
                                width: '350px',
                                placeholder: 'All Manuals',
                                allowClear: true,
                                dropdownAutoWidth: true
                            });

                            $(manualFilter).on('change', function () {
                                manualFilter.dispatchEvent(new Event('change', { bubbles: true }));
                            });
                        }


                        function updateManualIndicator() {
                            if (!manualIndicator || !manualFilter) return;

                            const selectedOption = manualFilter.options[manualFilter.selectedIndex];

                            if (!manualFilter.value) {
                                manualIndicator.textContent = ''; // All Manuals
                                return;
                            }

                            const optText = manualFilter.options[manualFilter.selectedIndex]?.text || '';
                            const manualNumber = optText.split(' - ')[0].trim();

                            manualIndicator.textContent = ` · Manual: ${manualNumber}`;

                        }

                        // tbody
                        const tbody = table.querySelector('tbody');
                        if (!tbody) {
                            console.warn('Table tbody not found');
                            return;
                        }

                        // ====== SAFE SPINNER (глобальный) ======
                        const safeShowSpinner = () => {
                            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
                        };
                        const safeHideSpinner = () => {
                            if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                        };

                        // ====== localStorage keys ======
                        const LS_SEARCH = 'components_search';
                        const LS_MANUAL = 'components_manual_id';

                        // ====== ВАЖНО: чтобы не было "дерганий" ======
                        // Скрываем tbody ДО применения сохраненных фильтров
                        if (tableWrapper) tableWrapper.classList.add('is-preloading');
                        safeShowSpinner();

                        // Кэшируем данные строк для быстрого поиска
                        const rows = Array.from(tbody.querySelectorAll('tr'));
                        const rowDataCache = rows.map(row => ({
                            element: row,
                            searchText: row.innerText ? row.innerText.toLowerCase() : '',
                            manualId: row.getAttribute('data-manual-id') || ''
                        }));

                        // Debounce функция для оптимизации поиска
                        let searchTimeout;

                        function debounce(func, wait) {
                            return function (...args) {
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => func.apply(this, args), wait);
                            };
                        }

                        // ====== Sorting ======
                        const headers = table ? table.querySelectorAll('.sortable') : [];
                        if (headers.length === 0) {
                            console.warn('No sortable headers found');
                        }

                        headers.forEach(header => {
                            header.addEventListener('click', () => {
                                safeShowSpinner();

                                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                                const visibleRows = rowDataCache.filter(data => data.element.style.display !== 'none');
                                const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                                header.dataset.direction = direction;

                                // Update icon
                                const icon = header.querySelector('i');
                                if (icon) {
                                    icon.className = direction === 'asc' ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1';
                                }

                                // Определяем, является ли это столбцом IPL Number
                                const headerText = header.textContent.trim().toLowerCase();
                                const isIplNumberColumn = headerText.includes('ipl') && headerText.includes('number');

                                visibleRows.sort((a, b) => {
                                    const aText = a.element.cells[columnIndex].innerText.trim();
                                    const bText = b.element.cells[columnIndex].innerText.trim();

                                    // Special sorting for IPL numbers
                                    if (isIplNumberColumn) {
                                        return sortIplNumbers(aText, bText, direction);
                                    }

                                    return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                                });

                                // Переупорядочиваем только видимые строки
                                visibleRows.forEach(data => tbody.appendChild(data.element));

                                requestAnimationFrame(() => safeHideSpinner());
                            });
                        });

                        // Начальная сортировка по IPL Number при загрузке
                        function applyInitialSort() {
                            const iplHeader = Array.from(headers).find(h => {
                                const text = h.textContent.trim().toLowerCase();
                                return text.includes('ipl') && text.includes('number');
                            });

                            if (iplHeader) {
                                const columnIndex = Array.from(iplHeader.parentNode.children).indexOf(iplHeader);
                                const direction = 'asc';
                                iplHeader.dataset.direction = direction;

                                // Обновляем иконку
                                const icon = iplHeader.querySelector('i');
                                if (icon) {
                                    icon.className = 'bi bi-chevron-up ms-1';
                                }

                                // Сортируем ВСЕ строки, а не только видимые
                                rowDataCache.sort((a, b) => {
                                    const aText = a.element.cells[columnIndex].innerText.trim();
                                    const bText = b.element.cells[columnIndex].innerText.trim();
                                    return sortIplNumbers(aText, bText, direction);
                                });

                                // Переупорядочиваем все строки в таблице
                                rowDataCache.forEach(data => tbody.appendChild(data.element));
                            }
                        }

                        // Custom sorting function for IPL numbers
                        // Правильная сортировка: 1-10, 1-20, 1-20A, 1-20B, 1-100, 1-1000
                        function sortIplNumbers(a, b, direction) {
                            if (!a && !b) return 0;
                            if (!a || a.trim() === '') return direction === 'asc' ? 1 : -1;
                            if (!b || b.trim() === '') return direction === 'asc' ? -1 : 1;

                            a = a.trim();
                            b = b.trim();

                            const aMatch = a.match(/^(\d+)-(\d+)([A-Za-z]*)$/);
                            const bMatch = b.match(/^(\d+)-(\d+)([A-Za-z]*)$/);

                            if (!aMatch || !bMatch) {
                                return direction === 'asc' ? a.localeCompare(b) : b.localeCompare(a);
                            }

                            const aMajor = parseInt(aMatch[1], 10);
                            const bMajor = parseInt(bMatch[1], 10);
                            const aMinorNum = parseInt(aMatch[2], 10);
                            const bMinorNum = parseInt(bMatch[2], 10);
                            const aMinorLetter = aMatch[3] || '';
                            const bMinorLetter = bMatch[3] || '';

                            if (aMajor !== bMajor) {
                                const result = aMajor - bMajor;
                                return direction === 'asc' ? result : -result;
                            }

                            if (aMinorNum !== bMinorNum) {
                                const result = aMinorNum - bMinorNum;
                                return direction === 'asc' ? result : -result;
                            }

                            if (aMinorLetter === '' && bMinorLetter !== '') {
                                return direction === 'asc' ? -1 : 1;
                            }
                            if (aMinorLetter !== '' && bMinorLetter === '') {
                                return direction === 'asc' ? 1 : -1;
                            }

                            if (aMinorLetter && bMinorLetter) {
                                const letterCompare = aMinorLetter.localeCompare(bMinorLetter);
                                return direction === 'asc' ? letterCompare : -letterCompare;
                            }

                            return 0;
                        }

                        // ====== ФИЛЬТРАЦИЯ (manual + search) + localStorage ======
                        function filterTable({persist = true} = {}) {
                            const searchFilter = (searchInput?.value || '').toLowerCase();
                            const manualFilterValue = (manualFilter?.value || '');

                            if (persist) {
                                localStorage.setItem(LS_SEARCH, searchFilter);
                                localStorage.setItem(LS_MANUAL, manualFilterValue);
                            }

                            let visibleCount = 0;

                            rowDataCache.forEach(data => {
                                const matchesSearch = !searchFilter || data.searchText.includes(searchFilter);
                                const matchesManual = !manualFilterValue || data.manualId === manualFilterValue;

                                if (matchesSearch && matchesManual) {
                                    data.element.style.display = '';
                                    visibleCount++;
                                } else {
                                    data.element.style.display = 'none';
                                }
                            });

                            if (componentsCount) {
                                componentsCount.textContent = visibleCount;
                            }

                            updateManualIndicator();
                        }

                        // ====== Восстановление состояния ДО первого показа таблицы ======
                        function restoreStateAndApplyOnce() {
                            // restore search
                            if (searchInput) {
                                const savedSearch = localStorage.getItem(LS_SEARCH) || '';
                                searchInput.value = savedSearch;
                            }

                            // restore manual
                            if (manualFilter) {
                                const savedManual = localStorage.getItem(LS_MANUAL) || '';
                                const exists = Array.from(manualFilter.options).some(o => o.value === savedManual);
                                manualFilter.value = exists ? savedManual : '';
                                if (window.jQuery) $(manualFilter).trigger('change.select2');
                            }

                            // Сортировка 1 раз
                            applyInitialSort();

                            // Фильтр 1 раз без лишних дерганий
                            filterTable({persist: false});

                            // Показываем tbody только после применения фильтра
                            requestAnimationFrame(() => {
                                if (tableWrapper) tableWrapper.classList.remove('is-preloading');
                                safeHideSpinner();
                            });


                            updateManualIndicator();

                        }

                        // Search с debounce (250ms)
                        if (searchInput) {
                            searchInput.addEventListener('input', debounce(() => {
                                safeShowSpinner();
                                filterTable({persist: true});
                                requestAnimationFrame(() => safeHideSpinner());
                            }, 250));
                        }

                        // Manual filter
                        if (manualFilter) {
                            manualFilter.addEventListener('change', () => {
                                safeShowSpinner();
                                filterTable({persist: true});
                                updateManualIndicator();
                                requestAnimationFrame(() => safeHideSpinner());
                            });
                        }

                        // Крестик “сброс manual” (если кнопка есть)
                        if (manualClearBtn && manualFilter) {
                            manualClearBtn.addEventListener('click', () => {
                                safeShowSpinner();
                                manualFilter.value = '';
                                localStorage.setItem(LS_MANUAL, '');
                                filterTable({persist: true});
                                updateManualIndicator();
                                requestAnimationFrame(() => safeHideSpinner());
                            });
                        }

                        // ====== CSV Upload handling (как у тебя) ======
                        const csvUploadForm = document.getElementById('csvUploadForm');
                        if (csvUploadForm) {
                            csvUploadForm.addEventListener('submit', function (e) {
                                e.preventDefault();

                                safeShowSpinner();

                                const formData = new FormData(this);
                                const submitBtn = this.querySelector('button[type="submit"]');
                                const originalText = submitBtn.innerHTML;

                                // Show loading state
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading.';

                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            showAlert('success', data.message);

                                            const modal = bootstrap.Modal.getInstance(document.getElementById('uploadCsvModal'));
                                            if (modal) modal.hide();

                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 1200);
                                        } else {
                                            showAlert('danger', data.message || 'Upload failed');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showAlert('danger', 'An error occurred during upload');
                                    })
                                    .finally(() => {
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = originalText;
                                        safeHideSpinner();
                                    });
                            });
                        }

                        // Alert function (как у тебя)
                        function showAlert(type, message) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                            document.body.appendChild(alertDiv);

                            setTimeout(() => {
                                if (alertDiv.parentNode) {
                                    alertDiv.remove();
                                }
                            }, 5000);
                        }

                        // ====== старт ======
                        restoreStateAndApplyOnce();

                    } catch (error) {
                        console.error('Error in components index script:', error);
                        if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                    }
                });



            </script>

@endsection
