@extends('admin.master')

@section('style')

    <style>
        .card {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 70px);
        }

        .card-header {
            flex-shrink: 0;
        }

        .table-wrapper {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: auto;

            opacity: 0;
            transition: opacity .15s;

        }

        .table-wrapper.ready {
            opacity: 1;
        }

        .wo-table-loading {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .table-wrapper.ready + .wo-table-loading {
            display: none;
        }

        .wo-loading-dots {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .wo-loading-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(173, 181, 189, .95);
            animation: woLoadingDotWave 1s infinite ease-in-out;
        }

        .wo-loading-dot:nth-child(2) {
            animation-delay: .12s;
        }

        .wo-loading-dot:nth-child(3) {
            animation-delay: .24s;
        }

        @keyframes woLoadingDotWave {
            0%, 80%, 100% {
                transform: translateY(0);
                opacity: .45;
            }
            40% {
                transform: translateY(-5px);
                opacity: 1;
            }
        }

        #show-workorder {
            table-layout: fixed;
            width: 100%;
        }

        .table th,
        .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;
            vertical-align: middle;
        }

        .col-number {
            width: 90px;
            font-size: 0.8rem;
        }

        .col-approve {
            width: 50px;
            font-size: 0.55rem;
            font-weight: normal;
        }

        .col-ec {
            width: 50px;
            font-size: 0.9rem;
        }

        .ec-icon-img {
            width: 12px;
            height: auto;
            display: inline-block;
            font-weight: 800;
        }

        .ec-arrow {
            color: #198754;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1;
            transform: translateY(1px);
        }

        .ec-open-arrow {
            color: #198754;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1;
            transform: translateY(1px);
        }

        .ec-arrow-finish {
            color: #198754;
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1;
            transform: translateY(1px);
        }

        .col-SN {
            width: 100px;
            font-size: 0.8rem;
            font-weight: normal;
        }

        .col-edit {
            width: 50px;
            font-size: 0.8rem;
            font-weight: normal;
        }

        .col-delete {
            width: 50px;
            font-size: 0.8rem;
            font-weight: normal;
        }

        .col-date {
            width: 90px;
            font-size: 0.8rem;
            font-weight: normal;
        }
        .col-PO {
            width: 90px;
            font-size: 0.8rem;
            font-weight: normal;
        }
        .col-stages {
            width: 92px;
            font-size: 0.8rem;
            font-weight: normal;
        }

        .stage-strip {
            display: inline-flex;
            align-items: center;
            gap: 0;
            max-width: 100%;
        }

        .stage-strip-segments {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .stage-seg {
            width: 10px;
            height: 8px;
            border-radius: 2px;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, .25);
        }

        .stage-seg.done {
            background: #198754; /* green */
        }

        .stage-seg.todo {
            background: #f59e0b; /* amber (in progress) */
        }

        .stage-seg.empty {
            background: #6c757d; /* gray (not started) */
        }

        .table thead th {
            position: sticky;
            height: 40px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            /*z-index: 1009;*/
        }

        .table th.sortable {
            cursor: pointer;
        }

        /* Search такого же размера, как селекты */
        .clearable-input {
            position: relative;
            max-width: 260px;
            height: 32px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
            width: 100%;
            height: 32px;
            line-height: 32px;
            font-size: .8rem;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .clearable-input .btn-clear {
            position: absolute;
            top: 50%;
            right: 0.25rem;
            transform: translateY(-50%);
            height: 32px;
            width: 32px;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.15rem;
            color: #ccc;
            z-index: 10;
        }

        #currentUserCheckbox,
        #woDone,
        #approvedCheckbox {
            cursor: pointer;
        }

        [data-bs-theme="dark"] #show-workorder {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }

        [data-bs-theme="dark"] #show-workorder thead th {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }

        [data-bs-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Фильтры customer / technik */
        .filter-select-wrapper {
            min-width: 210px;
        }

        .filter-select-wrapper .form-label {
            font-size: .75rem;
            margin-bottom: .2rem;
        }

        .filter-select-wrapper .form-control {
            height: 32px;
            padding-top: 2px;
            padding-bottom: 2px;
            font-size: .8rem;
        }

        .btn-clear-select {
            height: 32px;
            width: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .filter-select-wrapper {
                min-width: 150px;
            }
        }

        .checkbox-group {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            position: relative;
            padding-bottom: 4px; /* чтобы линия не прилипала */
        }

        /* Чекбокс */
        .checkbox-group input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
        }

        /* Линия, соединяющая чекбокс и текст */
        .checkbox-group::after {
            content: "";
            position: absolute;

            left: 0; /* начинаем ровно от края чекбокса */
            right: 0; /* тянем до конца текста */
            bottom: 0; /* под всем блоком */
            height: 3px;

            background: #0d6efd; /* синий */
            border-radius: 2px;
            opacity: 0.45;
        }

        .table-panel td {
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
        }

        .approve-inline {
            position: fixed; /* привязываем к экрану по координатам клика */
            z-index: 3000;
            width: 155px;
            padding: 4px;
            background: rgba(33, 37, 41, 0.95);
            border: 1px solid rgba(13, 110, 253, 0.45);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,.35);
        }

        .approve-inline input[type="date"]{
            height: 32px;
            font-size: .8rem;
        }

        .load-status {
            font-size: .85rem;
            min-height: 38px;
        }

        .load-status.is-loading {
            color: var(--bs-info);
        }

        .load-status.is-finished {
            color: var(--bs-secondary);
        }

    </style>

@endsection

@section('content')

    <script>
        (function () {
            try {
                var el = document.getElementById('spinner-load');
                if (el) el.classList.remove('d-none');
            } catch (e) {}
        })();
    </script>

    <div class="card shadow">

        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">

            <div class="d-flex align-items-center gap-3">
                <h5 class="text-primary mb-0">
                    {{ __('Workorders') }}

                    <span class="text-info" id="woVisible">{{ count($workorders) }}</span>
                    <span class="text-muted" style="font-size: 16px;">of</span>
                    <span class="text-info" id="woTotal">{{ $totalCount }}</span>
                </h5>

                <a id="admin_new_firm_create" href="{{ route('workorders.create') }}">
                    <img src="{{ asset('img/plus.png') }}" width="30" alt="Add" data-bs-toggle="tooltip"
                         title="Add new workorder">
                </a>
                @role('Admin')
                <form method="POST" action="{{ route('workorders.recalcStages') }}" class="ms-2"
                      onsubmit="return confirm('Recalculate stages for ALL workorders?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm"
                            onclick="if (typeof showLoadingSpinner === 'function') showLoadingSpinner();">
                        <i class="bi bi-arrow-repeat me-1"></i>
                    </button>
                </form>
                @endrole

                @roles('Admin|Manager')
                <div class="d-flex gap-2 ms-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="printSection('printArea')">
                        Print
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openPdfVisible('landscape')">
                        PDF
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyHtmlToClipboard('printArea')">
                        Copy Table
                    </button>
                </div>
                @endrole
            </div>

            <div class="clearable-input">
                <input id="searchInput" type="text" class="form-control" placeholder="Search...">
                <button id="clearSearch" type="button" class="btn-clear">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-5">

                @roles("Admin|Manager")
                <div class="d-flex align-items-end filter-select-wrapper">
                    <div class="flex-grow-1">
                        <select id="customerFilter" class="form-control form-control-sm">
                            <option value="">- All customers -</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="clearCustomerFilter"
                            class="btn btn-outline-secondary btn-sm btn-clear-select"
                            title="Clear customer filter">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <div class="d-flex align-items-end filter-select-wrapper">
                    <div class="flex-grow-1">
                        <select id="technikFilter" class="form-control form-control-sm">
                            <option value="">- All technicians -</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="clearTechnikFilter"
                            class="btn btn-outline-secondary btn-sm btn-clear-select"
                            title="Clear technician filter">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                @endroles

                <label class="checkbox-group">
                    <input type="checkbox" id="woDone">
                    <span>WO active</span>
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" id="currentUserCheckbox">
                    <span>My workorders</span>
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" id="approvedCheckbox">
                    <span>Approved</span>
                </label>

                <label class="checkbox-group"
                       @if(!auth()->user()->hasAnyRole('Admin|Manager|Shipping')) hidden @endif>

                    <input type="checkbox"
                           id="draftCheckbox"
                           @if(!auth()->user()->hasAnyRole('Admin|Manager|Shipping')) disabled @endif>

                    <span>Draft</span>
                </label>

            </div>

        </div>

        <div
            class="table-wrapper p-2 pt-0"
            id="printArea"
            data-endpoint="{{ route('workorders.index') }}"
            data-next-cursor="{{ $nextCursor }}"
            data-has-more="{{ $hasMore ? '1' : '0' }}"
            data-total-count="{{ $totalCount }}"
            data-overall-total="{{ $overallTotal }}"
            data-initial-q="{{ request('q', '') }}"
            data-initial-customer="{{ request('customer_id', '') }}"
            data-initial-technik="{{ request('technik_id', '') }}"
            data-initial-only-my="{{ request()->boolean('only_my', false) ? '1' : '0' }}"
            data-initial-only-active="{{ request()->boolean('only_active', false) ? '1' : '0' }}"
            data-initial-only-approved="{{ request()->boolean('only_approved', false) ? '1' : '0' }}"
            data-initial-show-drafts="{{ request()->boolean('show_drafts', false) ? '1' : '0' }}"
            data-initial-sort="{{ $initialSort }}"
            data-initial-direction="{{ $initialDirection }}"
        >
            <table id="show-workorder" class="table table-sm table-bordered table-hover w-100 table-panel" style="font-size: 14px;">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center text-primary sortable col-number" data-sort-key="number">
                        Number <i class="bi bi-chevron-expand ms-1"></i>
                    </th>
                    <th class="text-center text-primary col-approve no-print">Approve</th>
                    <th class="text-center text-primary col-ec no-print sortable" data-sort-key="ec">
                        EC <i class="bi bi-chevron-expand ms-1"></i>
                    </th>
                    @hasanyrole('Admin|Manager')
                    <th class="text-center text-primary col-stages no-print">Stages</th>
                    @endhasanyrole
                    <th class="text-center text-primary">Component</th>
                    <th class="text-center text-primary">Description</th>
                    <th class="text-center text-primary col-SN">Serial No.</th>
                    <th class="text-center text-primary no-print">Manual</th>
                    <th class="text-center text-primary sortable" data-sort-key="customer">
                        Customer <i class="bi bi-chevron-expand ms-1"></i>
                    </th>
                    <th class="text-center text-primary sortable" data-sort-key="instruction">
                        Instruction <i class="bi bi-chevron-expand ms-1"></i>
                    </th>
                    <th class="text-center text-primary col-date">Open Date</th>
                    <th class="text-center text-primary col-PO">Customer PO</th>
                    <th class="text-center text-primary col-edit no-print">Edit</th>
                    <th class="text-center text-primary sortable no-print" data-sort-key="technik">
                        Technik <i class="bi bi-chevron-expand ms-1"></i>
                    </th>
                    @role('Admin')
                    <th class="text-center text-primary col-delete no-print">Del</th>
                    @endrole
                </tr>
                </thead>
                <tbody id="workordersTbody">
                    @include('admin.workorders.partials.rows', [
                        'workorders' => $workorders,
                        'ecStatuses' => $ecStatuses,
                        'generalTasks' => $generalTasks,
                        'tasksByGeneral' => $tasksByGeneral,
                    ])
                </tbody>
            </table>

            <div id="workordersLoadStatus" class="load-status d-flex align-items-center justify-content-center py-2 text-muted">
                @if($hasMore)
                    Scroll down to load more workorders...
                @elseif($totalCount > 0)
                    All matching workorders are loaded.
                @else
                    No workorders found.
                @endif
            </div>
        </div>
        <div class="wo-table-loading" aria-label="Loading workorders">
            <span class="wo-loading-dots">
                <span class="wo-loading-dot"></span>
                <span class="wo-loading-dot"></span>
                <span class="wo-loading-dot"></span>
            </span>
        </div>
    </div>

    @include('components.delete')

@endsection

@section('scripts')
    <script>
        const workordersIndexUrl = @json(route('workorders.index'));
        const workordersApproveBaseUrl = @json(url('/workorders'));
        const workordersPdfUrl = @json(route('reports.table.pdf'));
        const workordersCsrfToken = @json(csrf_token());

        document.addEventListener('DOMContentLoaded', function () {
            const tableWrapper = document.getElementById('printArea');
            const tbody = document.getElementById('workordersTbody');
            const loadStatus = document.getElementById('workordersLoadStatus');
            const visibleCounter = document.getElementById('woVisible');
            const totalCounter = document.getElementById('woTotal');

            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearch');

            const checkboxMy = document.getElementById('currentUserCheckbox');
            const checkboxDone = document.getElementById('woDone');
            const checkboxApproved = document.getElementById('approvedCheckbox');
            const checkboxDraft = document.getElementById('draftCheckbox');

            const customerFilter = document.getElementById('customerFilter');
            const technikFilter = document.getElementById('technikFilter');
            const clearCustomerBtn = document.getElementById('clearCustomerFilter');
            const clearTechnikBtn = document.getElementById('clearTechnikFilter');
            const headers = Array.from(document.querySelectorAll('.sortable[data-sort-key]'));

            const deleteModal = document.getElementById('useConfirmDelete');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            let currentFormId = null;
            let searchDebounce = null;
            let approvePopover = null;
            let approveInput = null;

            const state = {
                q: '',
                customerId: '',
                technikId: '',
                onlyMy: true,
                onlyActive: false,
                onlyApproved: false,
                showDrafts: false,
                sort: tableWrapper.dataset.initialSort || 'number',
                direction: tableWrapper.dataset.initialDirection || 'desc',
                cursor: tableWrapper.dataset.nextCursor || '',
                hasMore: tableWrapper.dataset.hasMore === '1',
                loading: false,
                loadedCount: tbody.querySelectorAll('tr[data-id]').length,
                totalCount: Number(tableWrapper.dataset.totalCount || 0),
                overallTotal: Number(tableWrapper.dataset.overallTotal || 0),
                activeRequest: 0,
            };

            const serverState = {
                q: tableWrapper.dataset.initialQ || '',
                customerId: tableWrapper.dataset.initialCustomer || '',
                technikId: tableWrapper.dataset.initialTechnik || '',
                onlyMy: tableWrapper.dataset.initialOnlyMy === '1',
                onlyActive: tableWrapper.dataset.initialOnlyActive === '1',
                onlyApproved: tableWrapper.dataset.initialOnlyApproved === '1',
                showDrafts: tableWrapper.dataset.initialShowDrafts === '1',
                sort: tableWrapper.dataset.initialSort || 'number',
                direction: tableWrapper.dataset.initialDirection || 'desc',
            };

            function initializeTooltips(root = document) {
                root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
            }

            function updateSelectClearButton(selectEl, buttonEl) {
                if (!selectEl || !buttonEl) return;

                if (selectEl.value) {
                    buttonEl.classList.remove('btn-outline-secondary');
                    buttonEl.classList.add('btn-primary', 'text-white');
                } else {
                    buttonEl.classList.add('btn-outline-secondary');
                    buttonEl.classList.remove('btn-primary', 'text-white');
                }
            }

            function updateSearchClearButton() {
                clearSearchBtn.style.display = searchInput.value ? 'block' : 'none';
            }

            function getUrlValue(params, key, fallback = '') {
                return params.has(key) ? (params.get(key) || '') : fallback;
            }

            function getUrlBool(params, key, fallback) {
                if (!params.has(key)) return fallback;
                const value = params.get(key);
                return value === '1' || value === 'true';
            }
            function readStoredState() {
                const params = new URLSearchParams(window.location.search);

                state.q = getUrlValue(params, 'q', localStorage.getItem('woSearchInput') || serverState.q);
                state.customerId = getUrlValue(params, 'customer_id', localStorage.getItem('woCustomerFilter') || serverState.customerId);
                state.technikId = getUrlValue(params, 'technik_id', localStorage.getItem('woTechnikFilter') || serverState.technikId);
                state.onlyMy = getUrlBool(params, 'only_my', localStorage.getItem('myWorkordersCheckbox') !== null ? localStorage.getItem('myWorkordersCheckbox') === 'true' : serverState.onlyMy);
                state.onlyActive = getUrlBool(params, 'only_active', localStorage.getItem('doneCheckbox') !== null ? localStorage.getItem('doneCheckbox') === 'true' : serverState.onlyActive);
                state.onlyApproved = getUrlBool(params, 'only_approved', localStorage.getItem('approvedCheckbox') !== null ? localStorage.getItem('approvedCheckbox') === 'true' : serverState.onlyApproved);
                state.showDrafts = getUrlBool(params, 'show_drafts', localStorage.getItem('draftCheckbox') !== null ? localStorage.getItem('draftCheckbox') === 'true' : serverState.showDrafts);
                state.sort = getUrlValue(params, 'sort', serverState.sort);
                state.direction = getUrlValue(params, 'direction', serverState.direction);
            }

            function applyStateToControls() {
                searchInput.value = state.q;
                checkboxMy.checked = state.onlyMy;
                checkboxDone.checked = state.onlyActive;
                checkboxApproved.checked = state.onlyApproved;

                if (checkboxDraft) {
                    checkboxDraft.checked = state.showDrafts;
                }

                if (customerFilter) {
                    customerFilter.value = state.customerId;
                }

                if (technikFilter) {
                    technikFilter.value = state.technikId;
                }

                updateSearchClearButton();
                updateSelectClearButton(customerFilter, clearCustomerBtn);
                updateSelectClearButton(technikFilter, clearTechnikBtn);
                updateSortIcons();
            }

            function persistState() {
                localStorage.setItem('woSearchInput', state.q);
                localStorage.setItem('woCustomerFilter', state.customerId);
                localStorage.setItem('woTechnikFilter', state.technikId);
                localStorage.setItem('myWorkordersCheckbox', String(state.onlyMy));
                localStorage.setItem('doneCheckbox', String(state.onlyActive));
                localStorage.setItem('approvedCheckbox', String(state.onlyApproved));
                localStorage.setItem('draftCheckbox', String(state.showDrafts));
            }

            function syncUrl() {
                const params = new URLSearchParams();

                if (state.q) params.set('q', state.q);
                if (state.customerId) params.set('customer_id', state.customerId);
                if (state.technikId) params.set('technik_id', state.technikId);
                params.set('only_my', state.onlyMy ? '1' : '0');
                if (state.onlyActive) params.set('only_active', '1');
                if (state.onlyApproved) params.set('only_approved', '1');
                if (state.showDrafts) params.set('show_drafts', '1');
                if (state.sort && state.sort !== 'number') params.set('sort', state.sort);
                if (state.direction && state.direction !== 'desc') params.set('direction', state.direction);

                const query = params.toString();
                const nextUrl = query ? `${workordersIndexUrl}?${query}` : workordersIndexUrl;
                window.history.replaceState({}, '', nextUrl);
            }

            function buildQuery(includeCursor = true) {
                const params = new URLSearchParams();
                params.set('fragment', '1');
                params.set('per_page', '50');

                if (state.q) params.set('q', state.q);
                if (state.customerId) params.set('customer_id', state.customerId);
                if (state.technikId) params.set('technik_id', state.technikId);
                params.set('only_my', state.onlyMy ? '1' : '0');
                if (state.onlyActive) params.set('only_active', '1');
                if (state.onlyApproved) params.set('only_approved', '1');
                if (state.showDrafts) params.set('show_drafts', '1');
                if (state.sort) params.set('sort', state.sort);
                if (state.direction) params.set('direction', state.direction);
                if (includeCursor && state.cursor) params.set('cursor', state.cursor);

                return params.toString();
            }

            function setLoadStatus(text, mode = '') {
                loadStatus.textContent = text;
                loadStatus.classList.toggle('is-loading', mode === 'loading');
                loadStatus.classList.toggle('is-finished', mode === 'finished');
            }

            function updateCounters() {
                state.loadedCount = tbody.querySelectorAll('tr[data-id]').length;
                visibleCounter.textContent = state.loadedCount;
                totalCounter.textContent = state.totalCount;
            }

            function updateSortIcons() {
                headers.forEach(header => {
                    const icon = header.querySelector('i');
                    const isCurrent = header.dataset.sortKey === state.sort;

                    if (!icon) return;

                    if (!isCurrent) {
                        icon.className = 'bi bi-chevron-expand ms-1';
                        return;
                    }

                    icon.className = state.direction === 'asc'
                        ? 'bi bi-arrow-up ms-1'
                        : 'bi bi-arrow-down ms-1';
                });
            }

            function closeApprovePopover() {
                if (approvePopover) {
                    approvePopover.remove();
                    approvePopover = null;
                    approveInput = null;
                }
            }

            function shouldReloadInitialData() {
                return state.q !== serverState.q
                    || state.customerId !== serverState.customerId
                    || state.technikId !== serverState.technikId
                    || state.onlyMy !== serverState.onlyMy
                    || state.onlyActive !== serverState.onlyActive
                    || state.onlyApproved !== serverState.onlyApproved
                    || state.showDrafts !== serverState.showDrafts
                    || state.sort !== serverState.sort
                    || state.direction !== serverState.direction;
            }

            async function fetchChunk({ reset = false } = {}) {
                if (state.loading) return;

                if (reset) {
                    state.cursor = '';
                    state.hasMore = true;
                    tbody.innerHTML = '';
                    updateCounters();
                } else if (!state.hasMore) {
                    return;
                }

                state.loading = true;
                state.activeRequest += 1;
                const requestId = state.activeRequest;
                setLoadStatus('Loading workorders...', 'loading');

                try {
                    const response = await fetch(`${workordersIndexUrl}?${buildQuery(!reset)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data?.message || 'Failed to load workorders');
                    }

                    if (requestId !== state.activeRequest) {
                        return;
                    }

                    if (reset) {
                        tbody.innerHTML = data.html || '';
                    } else if ((data.html || '').trim() !== '') {
                        tbody.insertAdjacentHTML('beforeend', data.html);
                    }

                    const emptyRow = tbody.querySelector('.wo-empty-row');
                    if (emptyRow && tbody.querySelectorAll('tr[data-id]').length > 0) {
                        emptyRow.remove();
                    }

                    state.cursor = data.next_cursor || '';
                    state.hasMore = Boolean(data.has_more);
                    state.totalCount = Number(data.total_count || 0);
                    state.overallTotal = Number(data.overall_total || 0);

                    initializeTooltips(tbody);
                    updateCounters();

                    if (state.hasMore) {
                        setLoadStatus(`Loaded ${state.loadedCount} of ${state.totalCount}. Scroll down to load more...`);
                    } else if (state.totalCount > 0) {
                        setLoadStatus(`All ${state.totalCount} matching workorders are loaded.`, 'finished');
                    } else {
                        setLoadStatus('No workorders found.', 'finished');
                    }
                } catch (error) {
                    console.error(error);
                    setLoadStatus(error.message || 'Failed to load workorders.');
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(error.message || 'Failed to load workorders.', 'error');
                    }
                } finally {
                    state.loading = false;
                    tableWrapper.classList.add('ready');
                    if (typeof window.safeHideSpinner === 'function') {
                        window.safeHideSpinner();
                    }
                }
            }
            async function resetAndReload() {
                persistState();
                syncUrl();
                closeApprovePopover();

                if (typeof window.showLoadingSpinner === 'function') {
                    window.showLoadingSpinner();
                }

                await fetchChunk({ reset: true });
            }

            async function maybeLoadMoreOnScroll() {
                if (!state.hasMore || state.loading) return;

                const threshold = 180;
                const remaining = tableWrapper.scrollHeight - tableWrapper.scrollTop - tableWrapper.clientHeight;
                if (remaining <= threshold) {
                    await fetchChunk();
                }
            }

            async function ensureAllLoaded() {
                while (state.hasMore && !state.loading) {
                    await fetchChunk();
                }
            }

            async function saveApprove(workorderId, approveDate) {
                const response = await fetch(`${workordersApproveBaseUrl}/${workorderId}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': workordersCsrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ approve_date: approveDate || null }),
                });

                const data = await response.json();
                if (!response.ok || !data.ok) {
                    throw new Error(data?.message || 'Approve save failed');
                }

                const link = tbody.querySelector(`.approve-btn[data-id="${workorderId}"]`);
                if (link) {
                    const img = link.querySelector('.approve-icon');

                    if (data.approved) {
                        link.dataset.approveAt = data.approve_at_iso || '';
                        link.dataset.approveTitle = `${data.approve_at_human || ''} ${data.approve_name || ''}`.trim();
                        if (img) {
                            img.src = @json(asset('img/ok.png'));
                            img.width = 20;
                            img.title = `${data.approve_at_human || ''} ${data.approve_name || ''}`.trim();
                        }
                    } else {
                        link.dataset.approveAt = '';
                        link.dataset.approveTitle = '';
                        if (img) {
                            img.src = @json(asset('img/icon_no.png'));
                            img.width = 12;
                            img.removeAttribute('title');
                        }
                    }

                    const row = link.closest('tr');
                    if (row) {
                        row.dataset.approved = data.approved ? '1' : '0';
                    }
                }

                if (state.onlyApproved && !data.approved) {
                    await resetAndReload();
                }

                return data;
            }

            function openApprovePopover(button, event) {
                event.preventDefault();

                if (approvePopover && approvePopover.dataset.ownerId === button.dataset.id) {
                    closeApprovePopover();
                    return;
                }

                closeApprovePopover();

                approvePopover = document.createElement('div');
                approvePopover.className = 'approve-inline';
                approvePopover.dataset.ownerId = button.dataset.id;
                approvePopover.innerHTML = `<input type="date" class="form-control form-control-sm" value="${button.dataset.approveAt || ''}">`;

                document.body.appendChild(approvePopover);
                approveInput = approvePopover.querySelector('input[type="date"]');

                const pad = 8;
                const rect = approvePopover.getBoundingClientRect();
                let left = event.clientX + pad;
                let top = event.clientY + pad;

                if (left + rect.width > window.innerWidth - 6) left = window.innerWidth - rect.width - 6;
                if (top + rect.height > window.innerHeight - 6) top = window.innerHeight - rect.height - 6;

                approvePopover.style.left = `${left}px`;
                approvePopover.style.top = `${top}px`;

                setTimeout(() => {
                    try { approveInput?.focus(); } catch (_) {}
                    try { approveInput?.showPicker?.(); } catch (_) {}
                }, 0);

                approveInput.addEventListener('change', async () => {
                    try {
                        if (typeof window.showLoadingSpinner === 'function') window.showLoadingSpinner();
                        await saveApprove(button.dataset.id, approveInput.value || null);
                        closeApprovePopover();
                    } catch (error) {
                        console.error(error);
                        if (typeof window.showNotification === 'function') {
                            window.showNotification(error.message || 'Approve save failed', 'error');
                        }
                    } finally {
                        if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
                    }
                });

                approveInput.addEventListener('keydown', async (evt) => {
                    if (evt.key !== 'Enter' || approveInput.value) return;

                    evt.preventDefault();

                    try {
                        if (typeof window.showLoadingSpinner === 'function') window.showLoadingSpinner();
                        await saveApprove(button.dataset.id, null);
                        closeApprovePopover();
                    } catch (error) {
                        console.error(error);
                        if (typeof window.showNotification === 'function') {
                            window.showNotification(error.message || 'Approve remove failed', 'error');
                        }
                    } finally {
                        if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
                    }
                });
            }

            function getLoadedWorkorderIds() {
                return Array.from(tbody.querySelectorAll('tr[data-id]'))
                    .map(row => row.dataset.id)
                    .filter(Boolean);
            }

            readStoredState();
            applyStateToControls();
            initializeTooltips(document);
            updateCounters();

            tableWrapper.addEventListener('scroll', maybeLoadMoreOnScroll);

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const key = header.dataset.sortKey;
                    if (!key) return;

                    if (state.sort === key) {
                        state.direction = state.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        state.sort = key;
                        state.direction = key === 'number' ? 'desc' : 'asc';
                    }

                    updateSortIcons();
                    resetAndReload();
                });
            });

            searchInput.addEventListener('input', () => {
                state.q = searchInput.value.trim();
                updateSearchClearButton();

                window.clearTimeout(searchDebounce);
                searchDebounce = window.setTimeout(() => {
                    resetAndReload();
                }, 250);
            });

            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                state.q = '';
                updateSearchClearButton();
                resetAndReload();
            });

            checkboxMy.addEventListener('change', () => {
                state.onlyMy = checkboxMy.checked;
                resetAndReload();
            });
            checkboxDone.addEventListener('change', () => {
                state.onlyActive = checkboxDone.checked;
                resetAndReload();
            });

            checkboxApproved.addEventListener('change', () => {
                state.onlyApproved = checkboxApproved.checked;
                resetAndReload();
            });

            checkboxDraft?.addEventListener('change', () => {
                state.showDrafts = checkboxDraft.checked;
                resetAndReload();
            });

            customerFilter?.addEventListener('change', () => {
                state.customerId = customerFilter.value;
                updateSelectClearButton(customerFilter, clearCustomerBtn);
                resetAndReload();
            });

            technikFilter?.addEventListener('change', () => {
                state.technikId = technikFilter.value;
                updateSelectClearButton(technikFilter, clearTechnikBtn);
                resetAndReload();
            });

            clearCustomerBtn?.addEventListener('click', () => {
                if (!customerFilter) return;
                customerFilter.value = '';
                state.customerId = '';
                updateSelectClearButton(customerFilter, clearCustomerBtn);
                resetAndReload();
            });

            clearTechnikBtn?.addEventListener('click', () => {
                if (!technikFilter) return;
                technikFilter.value = '';
                state.technikId = '';
                updateSelectClearButton(technikFilter, clearTechnikBtn);
                resetAndReload();
            });

            deleteModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                currentFormId = button?.getAttribute('data-form-id') || null;

                const title = button?.getAttribute('data-title') || 'Delete Confirmation';
                document.getElementById('confirmDeleteLabel').textContent = title;
            });

            confirmBtn.addEventListener('click', () => {
                if (!currentFormId) return;
                const form = document.getElementById(currentFormId);
                if (!form) return;
                if (typeof window.showLoadingSpinner === 'function') window.showLoadingSpinner();
                form.submit();
            });

            document.addEventListener('click', event => {
                const approveBtn = event.target.closest('.approve-btn');
                if (approveBtn) {
                    openApprovePopover(approveBtn, event);
                    return;
                }

                if (approvePopover && !approvePopover.contains(event.target)) {
                    closeApprovePopover();
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeApprovePopover();
                }
            });

            window.printSection = async function printSection(elementId) {
                await ensureAllLoaded();

                const element = document.getElementById(elementId);
                if (!element) return;

                const cssLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
                    .map(link => `<link rel="stylesheet" href="${link.href}">`)
                    .join('');

                const style = `
<style>
  body { font-family: Arial, sans-serif; padding: 16px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #000; padding: 6px; font-size: 12px; }
  .no-print, .no-print * { display: none !important; }
  th.sortable i, th.sortable .bi { display: none !important; }
</style>`;

                const win = window.open('', '', 'height=800,width=1100');
                if (!win) return;

                win.document.open();
                win.document.write(`
                    <html>
                    <head>
                        <title>Print</title>
                        ${cssLinks}
                        ${style}
                    </head>
                    <body>
                        ${element.outerHTML}
                    </body>
                    </html>
                `);
                win.document.close();
                win.focus();
                win.print();
            };

            window.copyHtmlToClipboard = async function copyHtmlToClipboard(elementId) {
                await ensureAllLoaded();

                const original = document.getElementById(elementId);
                if (!original) return;

                const keepCols = [1, 4, 6, 8, 9, 10];
                const keepSet = new Set(keepCols.map(Number));
                const clone = original.cloneNode(true);
                const table = clone.querySelector('table');
                if (!table) return;

                table.querySelectorAll('a').forEach(link => {
                    link.replaceWith(document.createTextNode(link.textContent ?? ''));
                });

                table.querySelectorAll('tr').forEach(tr => {
                    Array.from(tr.children).forEach((cell, idx) => {
                        if (!keepSet.has(idx + 1)) {
                            cell.remove();
                        }
                    });
                });

                clone.querySelectorAll('[class]').forEach(el => el.removeAttribute('class'));
                table.setAttribute('border', '1');
                table.style.borderCollapse = 'collapse';
                table.style.width = '100%';
                table.style.background = '#fff';
                table.style.color = '#000';
                table.style.fontFamily = 'Arial, sans-serif';
                table.style.fontSize = '12px';
                table.querySelectorAll('th,td').forEach(cell => {
                    cell.style.border = '1px solid #000';
                    cell.style.padding = '6px';
                    cell.style.background = '#fff';
                    cell.style.color = '#000';
                    cell.style.verticalAlign = 'top';
                    cell.style.whiteSpace = 'nowrap';
                });

                const html = table.outerHTML;
                const text = table.innerText;

                try {
                    if (navigator.clipboard && window.ClipboardItem) {
                        const item = new ClipboardItem({
                            'text/html': new Blob([html], { type: 'text/html' }),
                            'text/plain': new Blob([text], { type: 'text/plain' }),
                        });
                        await navigator.clipboard.write([item]);
                        if (typeof window.notifySuccess === 'function') window.notifySuccess('Copied', 3500);
                        return;
                    }
                } catch (_) {}

                const temp = document.createElement('div');
                temp.style.position = 'fixed';
                temp.style.left = '-9999px';
                temp.innerHTML = html;
                document.body.appendChild(temp);

                const range = document.createRange();
                range.selectNodeContents(temp);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);

                const ok = document.execCommand('copy');
                selection.removeAllRanges();
                document.body.removeChild(temp);

                if (typeof window.notifySuccess === 'function') {
                    window.notifySuccess(ok ? 'Copied!' : 'Copy blocked by browser', 3500);
                }
            };

            window.openPdfVisible = async function openPdfVisible(orientation = 'portrait') {
                await ensureAllLoaded();

                const ids = getLoadedWorkorderIds();
                if (!ids.length) {
                    window.alert('No visible rows to export.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = workordersPdfUrl;
                form.target = '_blank';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = workordersCsrfToken;
                form.appendChild(csrf);

                const ori = document.createElement('input');
                ori.type = 'hidden';
                ori.name = 'orientation';
                ori.value = orientation;
                form.appendChild(ori);

                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                form.remove();
            };

            if (shouldReloadInitialData()) {
                resetAndReload();
            } else {
                setLoadStatus(
                    state.hasMore
                        ? `Loaded ${state.loadedCount} of ${state.totalCount}. Scroll down to load more...`
                        : (state.totalCount > 0 ? `All ${state.totalCount} matching workorders are loaded.` : 'No workorders found.'),
                    state.hasMore ? '' : 'finished'
                );

                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                }

                tableWrapper.classList.add('ready');
            }
        });
    </script>
@endsection

