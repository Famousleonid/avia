@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            position: relative;
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
            min-width: 80px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 50px;
            max-width: 70px;
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
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
        }

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 400px;
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
            padding: 0;
            line-height: 1;
        }

        .clearable-input .form-control {
            padding-right: 2rem;
        }

        .js-table-hidden {
            visibility: hidden;
        }

        .dir-header .dir-title {
            margin: 0;
            line-height: 1.1;
        }

        .dir-header .form-control-sm {
            height: 32px;
        }

        .cmm-thumb-link {
            display: inline-block;
        }

        .cmm-thumb-wrap {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            overflow: hidden;
            flex: 0 0 40px;
        }

        .cmm-thumb-img {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            max-width: 40px !important;
            max-height: 40px !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block;
        }

        .table-loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.78);
            z-index: 30;
            transition: opacity .15s ease;
        }

        html[data-bs-theme="dark"] .table-loading-overlay {
            background: rgba(20, 20, 20, 0.68);
        }

        .table-loading-overlay.d-none {
            display: none !important;
        }
    </style>

    <div class="card dir-page">
        <div class="card-header dir-header shadow-sm">
            <div class="d-flex w-100 align-items-center justify-content-between gap-2 flex-wrap">

                <h5 class="text-primary dir-title">
                    {{ __('Manage CMMs') }} (
                    <span class="text-success">{{ $cmms->count() }}</span>
                    )
                </h5>

                <div class="clearable-input">
                    <input id="searchInput"
                           type="text"
                           class="form-control form-control-sm w-100"
                           placeholder="Search...">
                    <button class="btn-clear text-secondary"
                            id="clearSearchBtn"
                            type="button"
                            title="Clear">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>

                <a href="{{ route('manuals.create') }}" class="btn btn-outline-primary btn-sm me-5">
                    {{ __('Add CMM') }}
                </a>
            </div>
        </div>

        @if(count($cmms))
            <div class="table-wrapper me-3 p-0" id="tableWrapper">

                <div id="tableLoading" class="table-loading-overlay">
                    <div class="text-center">
                        <div class="spinner-border text-warning-emphasis" role="status"></div>
                    </div>
                </div>

                <table id="cmmTable" class="table table-sm table-hover align-middle table-bordered js-table-hidden dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient" data-direction="asc">
                            {{ __('Number') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{ __('Title') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{ __('Components PN') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">{{ __('Image') }}</th>
                        <th class="text-primary text-center bg-gradient">{{ __('Rev.Date') }}</th>
                        <th class="text-primary text-center sortable bg-gradient" data-direction="asc">
                            {{ __('Lib') }} <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">{{ __('STD Files') }}</th>
                        <th class="text-primary text-center bg-gradient">{{ __('Action') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($cmms as $cmm)
                        <tr>
                            <td>
                                <a href="{{ route('manuals.show', ['manual' => $cmm->id]) }}">
                                    {{ $cmm->number }}
                                </a>
                            </td>

                            <td title="{{ $cmm->title }}">{{ $cmm->title }}</td>
                            <td title="{{ $cmm->unit_name }}">{{ $cmm->unit_name }}</td>

                            <td class="text-center">
                                @php
                                    $manualThumb = $cmm->getFirstMediaThumbnailUrl('manuals') ?: asset('img/noimage.png');
                                    $manualBig = $cmm->getFirstMediaBigUrl('manuals') ?: $manualThumb;
                                @endphp

                                <a href="{{ $manualBig }}" data-fancybox="gallery" class="cmm-thumb-link">
                                    <span class="cmm-thumb-wrap">
                                        <img
                                            src="{{ $manualThumb }}"
                                            onerror="this.onerror=null;this.src='{{ asset('img/noimage.png') }}';if(this.closest('a')){this.closest('a').setAttribute('href','{{ asset('img/noimage.png') }}');}"
                                            class="cmm-thumb-img"
                                            alt="Image"
                                        >
                                    </span>
                                </a>
                            </td>

                            <td class="text-center">{{ $cmm->revision_date }}</td>
                            <td class="text-center">{{ $cmm->lib }}</td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    @foreach($cmm->getMedia('csv_files') as $file)
                                        <a href="{{ route('manuals.csv.view', ['manual' => $cmm->id, 'file' => $file->id]) }}"
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-file-csv"></i>
                                            @if($file->getCustomProperty('process_type'))
                                                {{ $file->getCustomProperty('process_type') }}
                                            @else
                                                {{ __('No Type') }}
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </td>

                            <td class="text-center">
                                <a href="{{ route('manuals.edit', ['manual' => $cmm->id]) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                @role('Admin')
                                <form id="deleteForm_{{ $cmm->id }}"
                                      action="{{ route('manuals.destroy', ['manual' => $cmm->id]) }}"
                                      method="POST"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger"
                                            type="button"
                                            name="btn_delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#useConfirmDelete"
                                            data-title="Delete Confirmation row {{ $cmm->number }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endrole
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3">
                <p class="mb-0">Manuals not created</p>
            </div>
        @endif
    </div>

    @include('components.delete')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn') ||
                document.querySelector('.clearable-input .btn-clear');
            const loading = document.getElementById('tableLoading');

            const STORAGE_KEY = 'cmm_search';
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const headers = document.querySelectorAll('.sortable');

            let inputTimer = null;

            function showGlobalSpinner() {
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
            }

            function hideGlobalSpinner() {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }

            function showTableLoader() {
                if (loading) loading.classList.remove('d-none');
                table.style.visibility = 'hidden';
            }

            function hideTableLoader() {
                if (loading) loading.classList.add('d-none');
                table.style.visibility = 'visible';
            }

            function cacheRowSearchText() {
                rows.forEach(row => {
                    row.dataset.searchText = (row.textContent || '').toLowerCase();
                });
            }

            function applySearch(raw) {
                const filter = (raw || '').trim().toLowerCase();

                rows.forEach(row => {
                    const text = row.dataset.searchText || '';
                    row.style.display = !filter || text.includes(filter) ? '' : 'none';
                });
            }

            function saveSearchValue(value) {
                const v = (value || '').trim();
                if (v) localStorage.setItem(STORAGE_KEY, v);
                else localStorage.removeItem(STORAGE_KEY);
            }

            function getActualSearchValue() {
                const fromStorage = (localStorage.getItem(STORAGE_KEY) || '').trim();
                const fromInput = (searchInput?.value || '').trim();
                return fromStorage || fromInput;
            }

            function restoreAndApplyAsync() {
                showTableLoader();

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const value = getActualSearchValue();

                        if (searchInput) {
                            searchInput.value = value;
                        }

                        saveSearchValue(value);
                        applySearch(value);
                        hideTableLoader();
                    });
                });
            }

            function sortTableByColumn(header) {
                showTableLoader();

                requestAnimationFrame(() => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    const sortedRows = rows.slice().sort((a, b) => {
                        const aText = (a.cells[columnIndex]?.innerText || '').trim();
                        const bText = (b.cells[columnIndex]?.innerText || '').trim();

                        return direction === 'asc'
                            ? aText.localeCompare(bText, undefined, { numeric: true, sensitivity: 'base' })
                            : bText.localeCompare(aText, undefined, { numeric: true, sensitivity: 'base' });
                    });

                    sortedRows.forEach(row => tbody.appendChild(row));
                    hideTableLoader();
                });
            }

            cacheRowSearchText();
            restoreAndApplyAsync();

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    restoreAndApplyAsync();
                }
            });

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    sortTableByColumn(header);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const value = searchInput.value.trim();
                    saveSearchValue(value);

                    clearTimeout(inputTimer);
                    showTableLoader();

                    inputTimer = setTimeout(() => {
                        applySearch(value);
                        hideTableLoader();
                    }, 50);
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();

                    if (searchInput) {
                        searchInput.value = '';
                    }

                    saveSearchValue('');
                    showTableLoader();

                    requestAnimationFrame(() => {
                        applySearch('');
                        hideTableLoader();
                    });
                });
            }

            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let deleteForm = null;

            if (modal && confirmDeleteBtn) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    deleteForm = button ? button.closest('form') : null;

                    const title = button ? button.getAttribute('data-title') : null;
                    const modalTitle = modal.querySelector('#confirmDeleteLabel');

                    if (modalTitle) {
                        modalTitle.textContent = title || 'Delete Confirmation';
                    }
                });

                confirmDeleteBtn.addEventListener('click', function () {
                    showGlobalSpinner();
                    if (deleteForm) deleteForm.submit();
                });
            }
        });
    </script>
@endsection
