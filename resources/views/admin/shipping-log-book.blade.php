@extends('admin.master')

@section('style')
    <style>
        .shipping-log-card {
            height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .shipping-log-card .card-header {
            flex: 0 0 auto;
        }

        .shipping-log-card .card-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .shipping-log-table-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .shipping-log-table {
            table-layout: auto;
            width: 100%;
            min-width: 1180px;
            border-collapse: separate;
            border-spacing: 0;
            --shipping-log-border: rgba(255, 255, 255, .18);
            --shipping-log-head-bg: linear-gradient(180deg, #151719 0%, #2e3338 100%);
            --shipping-log-head-cover: #151719;
            --shipping-log-row-bg: var(--avia-panel);
        }

        html[data-bs-theme="light"] .shipping-log-table {
            --shipping-log-border: rgba(0, 0, 0, .18);
            --shipping-log-head-bg: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            --shipping-log-head-cover: #f8f9fa;
            --shipping-log-row-bg: #ffffff;
        }

        .shipping-log-table th,
        .shipping-log-table td {
            border: 0 !important;
            border-right: 1px solid var(--shipping-log-border) !important;
            border-bottom: 1px solid var(--shipping-log-border) !important;
            vertical-align: middle;
            padding: .16rem .40rem;
            line-height: 1.12;
        }

        .shipping-log-table th:first-child,
        .shipping-log-table td:first-child {
            border-left: 1px solid var(--shipping-log-border) !important;
        }

        .shipping-log-table th,
        .shipping-log-table td {
            white-space: nowrap;
        }

        .shipping-log-table thead th {
            position: sticky;
            top: -1px;
            z-index: 5;
            background: var(--shipping-log-head-bg) !important;
            background-clip: border-box;
            border-top: 1px solid var(--shipping-log-border) !important;
            box-shadow:
                0 -2px 0 var(--shipping-log-head-cover),
                0 1px 0 var(--shipping-log-border),
                0 2px 4px rgba(0, 0, 0, .22);
            height: 30px;
            font-size: .82rem;
        }

        .shipping-log-table th.sortable {
            cursor: pointer;
            user-select: none;
        }

        .shipping-log-table tbody td {
            background: var(--shipping-log-row-bg) !important;
        }

        .shipping-log-input,
        .shipping-log-notes {
            min-height: 30px;
            height: 30px;
            padding-top: .10rem;
            padding-bottom: .10rem;
            font-size: .90rem;
            line-height: 1.12;
        }

        .shipping-log-col-shipment {
            width: 15ch;
        }

        .shipping-log-col-forwarder {
            width: 26ch;
        }

        .shipping-log-col-awb {
            width: 18ch;
        }

        .shipping-log-col-action {
            width: 52px;
        }

        .shipping-log-col-notes {
            width: 100%;
            min-width: 260px;
            white-space: normal !important;
        }

        .shipping-log-col-shipment .shipping-log-input {
            width: 15ch;
            min-width: 15ch;
        }

        .shipping-log-col-forwarder .shipping-log-input {
            width: 26ch;
            min-width: 26ch;
        }

        .shipping-log-col-awb .shipping-log-input {
            width: 18ch;
            min-width: 18ch;
        }

        .shipping-log-notes {
            display: block;
            width: 100%;
            min-width: 220px;
            resize: vertical;
            max-height: 80px;
            white-space: normal;
        }

        .shipping-log-date {
            text-align: center;
        }

        .shipping-log-row.is-dirty .shipping-log-save {
            border-color: var(--bs-warning);
            color: var(--bs-warning);
        }

        .shipping-log-row.is-saving .shipping-log-save {
            pointer-events: none;
            opacity: .65;
        }

        .shipping-log-status {
            display: inline-block;
            min-height: 0;
            margin-left: .25rem;
            font-size: .68rem;
            line-height: 1;
            vertical-align: middle;
        }

        .shipping-log-save {
            --bs-btn-padding-y: .05rem;
            --bs-btn-padding-x: .28rem;
            --bs-btn-font-size: .78rem;
            line-height: 1.05;
        }

        .shipping-log-search {
            width: min(320px, 58vw);
        }

        .shipping-log-load-status {
            flex: 0 0 auto;
            min-height: 28px;
            font-size: .82rem;
        }

        @media print {
            .no-print,
            .sidebar,
            #sidebarColumn,
            .shipping-log-card .card-header {
                display: none !important;
            }

            .content,
            .content-inner,
            .shipping-log-card,
            .shipping-log-card .card-body,
            .shipping-log-table-wrap {
                height: auto !important;
                overflow: visible !important;
            }

            .shipping-log-table {
                min-width: 0;
                width: 100%;
                color: #000;
            }

            .shipping-log-table th,
            .shipping-log-table td {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: #fff !important;
            }

            .shipping-log-input,
            .shipping-log-notes {
                border: 0;
                padding: 0;
                color: #000;
                background: transparent;
            }
        }
    </style>
@endsection

@section('content')
    <div class="card shadow shipping-log-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="text-primary mb-0">Shipping Log Book</h5>
                <span class="text-secondary small">
                    <span id="shippingLogLoaded">{{ count($workorders) }}</span>
                    of
                    <span id="shippingLogTotal">{{ $totalCount }}</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2 no-print">
                <form method="GET" action="{{ route('shipping-log-book.index') }}" class="d-flex align-items-center gap-1">
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        class="form-control form-control-sm shipping-log-search"
                        placeholder="Search"
                    >
                    @if($q !== '')
                        <a href="{{ route('shipping-log-book.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary" title="Print" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-2">
            <div
                class="shipping-log-table-wrap"
                id="shippingLogTableWrap"
                data-endpoint="{{ route('shipping-log-book.index') }}"
                data-q="{{ $q }}"
                data-next-page="{{ $nextPage }}"
                data-has-more="{{ $hasMore ? '1' : '0' }}"
                data-total-count="{{ $totalCount }}"
                data-loaded-count="{{ count($workorders) }}"
                data-sort="{{ $sort }}"
                data-direction="{{ $direction }}"
            >
                <table class="table table-sm table-bordered table-hover shipping-log-table mb-0">
                    <thead>
                    <tr>
                        <th class="text-center text-primary sortable" data-sort-key="wo">WO No. <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary">Part No.</th>
                        <th class="text-center text-primary">Customer name</th>
                        <th class="text-center text-primary">Cust PO No.</th>
                        <th class="text-center text-primary sortable" data-sort-key="completed">Completed <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary sortable shipping-log-col-shipment" data-sort-key="shipment">Shipment <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary shipping-log-col-forwarder">Freight Forwarder</th>
                        <th class="text-center text-primary shipping-log-col-awb">AWB No.</th>
                        <th class="text-center text-primary shipping-log-col-notes">NOTES</th>
                        <th class="text-center text-primary shipping-log-col-action no-print"></th>
                    </tr>
                    </thead>
                    <tbody id="shippingLogRows">
                    @if(count($workorders) > 0)
                        @include('admin.shipping-log-book-rows', ['workorders' => $workorders])
                    @else
                        <tr class="shipping-log-empty-row">
                            <td colspan="10" class="text-center text-muted py-4">No workorders found</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
            <div class="shipping-log-load-status text-secondary pt-2 no-print" id="shippingLogLoadStatus"></div>
        </div>
    </div>

    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const tableWrap = document.getElementById('shippingLogTableWrap');
            const tbody = document.getElementById('shippingLogRows');
            const loadStatus = document.getElementById('shippingLogLoadStatus');
            const loadedEl = document.getElementById('shippingLogLoaded');
            const totalEl = document.getElementById('shippingLogTotal');
            const sortableHeaders = document.querySelectorAll('.shipping-log-table th.sortable[data-sort-key]');

            const state = {
                endpoint: tableWrap?.dataset.endpoint || '',
                q: tableWrap?.dataset.q || '',
                nextPage: Number(tableWrap?.dataset.nextPage || 0),
                hasMore: tableWrap?.dataset.hasMore === '1',
                loading: false,
                loadedCount: Number(tableWrap?.dataset.loadedCount || 0),
                totalCount: Number(tableWrap?.dataset.totalCount || 0),
                sort: tableWrap?.dataset.sort || 'wo',
                direction: tableWrap?.dataset.direction || 'desc',
            };

            function collectRow(row) {
                const data = {};
                row.querySelectorAll('.js-shipping-field').forEach((field) => {
                    data[field.name] = field.value;
                });
                return data;
            }

            function setStatus(row, text, className) {
                const status = row.querySelector('.shipping-log-status');
                if (!status) return;

                status.className = 'shipping-log-status ' + (className || 'text-secondary');
                status.textContent = text || '';
            }

            function markClean(row) {
                row.classList.remove('is-dirty');
                row.querySelectorAll('.js-shipping-field').forEach((field) => {
                    field.dataset.savedValue = field.value;
                });
            }

            function isDirty(row) {
                return Array.from(row.querySelectorAll('.js-shipping-field')).some((field) => {
                    return field.value !== (field.dataset.savedValue || '');
                });
            }

            async function saveRow(row) {
                if (!row || !row.dataset.updateUrl) return;
                if (!isDirty(row)) {
                    setStatus(row, '', 'text-secondary');
                    return;
                }

                row.classList.add('is-saving');
                setStatus(row, 'Saving', 'text-info');

                try {
                    const response = await fetch(row.dataset.updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(collectRow(row)),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || payload.success === false) {
                        const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                        throw new Error(errors || payload.message || 'Save failed');
                    }

                    if (payload.workorder && Object.prototype.hasOwnProperty.call(payload.workorder, 'shipping_shipment_at_display')) {
                        const shipmentInput = row.querySelector('[name="shipping_shipment_at"]');
                        if (shipmentInput) {
                            shipmentInput.value = payload.workorder.shipping_shipment_at_display || '';
                            if (shipmentInput._flatpickr || shipmentInput._projectDatePicker) {
                                try {
                                    shipmentInput._flatpickr?.setDate(shipmentInput.value || null, false, 'd/M/Y');
                                    shipmentInput._projectDatePicker?.setDate(shipmentInput.value || null, false, 'd/M/Y');
                                } catch (_) {}
                            }
                        }
                    }

                    markClean(row);
                    setStatus(row, 'Saved', 'text-success');
                    window.setTimeout(() => {
                        if (!row.classList.contains('is-dirty')) {
                            setStatus(row, '', 'text-secondary');
                        }
                    }, 1600);
                } catch (error) {
                    setStatus(row, error.message || 'Error', 'text-danger');
                } finally {
                    row.classList.remove('is-saving');
                }
            }

            function initializeRows(root = document) {
                if (typeof window.initProjectDatePickers === 'function') {
                    window.initProjectDatePickers(root);
                }

                root.querySelectorAll('.shipping-log-row').forEach((row) => {
                    if (row.dataset.shippingLogBound === '1') return;
                    row.dataset.shippingLogBound = '1';

                    markClean(row);

                    row.querySelectorAll('.js-shipping-field').forEach((field) => {
                        field.addEventListener('input', () => {
                            row.classList.toggle('is-dirty', isDirty(row));
                            setStatus(row, row.classList.contains('is-dirty') ? 'Changed' : '', 'text-warning');
                        });

                        field.addEventListener('change', () => {
                            row.classList.toggle('is-dirty', isDirty(row));
                            setStatus(row, row.classList.contains('is-dirty') ? 'Changed' : '', 'text-warning');
                        });

                        field.addEventListener('keydown', (event) => {
                            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                                event.preventDefault();
                                saveRow(row);
                            }
                        });
                    });

                    row.querySelector('.shipping-log-save')?.addEventListener('click', () => saveRow(row));
                });
            }

            function updateLoadStatus(text, className = 'text-secondary') {
                if (!loadStatus) return;
                loadStatus.className = 'shipping-log-load-status pt-2 no-print ' + className;
                loadStatus.textContent = text || '';
            }

            function updateCounters() {
                if (loadedEl) loadedEl.textContent = String(state.loadedCount);
                if (totalEl) totalEl.textContent = String(state.totalCount);

                if (state.totalCount === 0) {
                    updateLoadStatus('No workorders found.');
                    return;
                }

                if (state.hasMore) {
                    updateLoadStatus(`Loaded ${state.loadedCount} of ${state.totalCount}. Scroll down to load more...`);
                } else {
                    updateLoadStatus(`All ${state.totalCount} matching workorders are loaded.`);
                }
            }

            function updateSortHeaders() {
                sortableHeaders.forEach((header) => {
                    const icon = header.querySelector('i.bi');
                    const active = header.dataset.sortKey === state.sort;

                    header.classList.toggle('text-info', active);
                    header.classList.toggle('text-primary', !active);

                    if (!icon) return;
                    icon.className = active
                        ? (state.direction === 'asc' ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1')
                        : 'bi bi-chevron-expand ms-1';
                });
            }

            function syncUrl() {
                const url = new URL(window.location.href);

                if (state.q) url.searchParams.set('q', state.q);
                else url.searchParams.delete('q');

                if (state.sort !== 'wo') url.searchParams.set('sort', state.sort);
                else url.searchParams.delete('sort');

                if (state.direction !== 'desc') url.searchParams.set('direction', state.direction);
                else url.searchParams.delete('direction');

                url.searchParams.delete('page');
                window.history.replaceState({}, '', url.toString());
            }

            async function fetchMoreRows() {
                if (!state.hasMore || state.loading || !state.endpoint || !tbody) return;

                state.loading = true;
                updateLoadStatus('Loading...', 'text-info');

                const params = new URLSearchParams();
                params.set('fragment', '1');
                params.set('per_page', '100');
                params.set('page', String(state.nextPage || 1));
                params.set('sort', state.sort);
                params.set('direction', state.direction);
                if (state.q) params.set('q', state.q);

                try {
                    const response = await fetch(`${state.endpoint}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to load rows');
                    }

                    tbody.querySelector('.shipping-log-empty-row')?.remove();

                    const template = document.createElement('template');
                    template.innerHTML = data.html || '';
                    const fragment = template.content;
                    tbody.appendChild(fragment);
                    initializeRows(tbody);

                    state.nextPage = Number(data.next_page || 0);
                    state.hasMore = Boolean(data.has_more);
                    state.loadedCount += Number(data.loaded_count || 0);
                    state.totalCount = Number(data.total_count || state.totalCount);
                    updateCounters();
                } catch (error) {
                    updateLoadStatus(error.message || 'Failed to load rows', 'text-danger');
                } finally {
                    state.loading = false;
                }
            }

            async function reloadRowsForSort() {
                if (!tbody) return;

                state.loading = false;
                state.loadedCount = 0;
                state.nextPage = 1;
                state.hasMore = true;
                tbody.innerHTML = '';
                tableWrap.scrollTop = 0;
                updateSortHeaders();
                syncUrl();
                await fetchMoreRows();

                if (state.loadedCount === 0) {
                    tbody.innerHTML = '<tr class="shipping-log-empty-row"><td colspan="10" class="text-center text-muted py-4">No workorders found</td></tr>';
                    updateCounters();
                }
            }

            async function maybeLoadMoreOnScroll() {
                if (!tableWrap || !state.hasMore || state.loading) return;

                const threshold = 180;
                const remaining = tableWrap.scrollHeight - tableWrap.scrollTop - tableWrap.clientHeight;
                if (remaining <= threshold) {
                    await fetchMoreRows();
                }
            }

            initializeRows(document);
            updateSortHeaders();
            updateCounters();
            tableWrap?.addEventListener('scroll', maybeLoadMoreOnScroll);
            sortableHeaders.forEach((header) => {
                header.addEventListener('click', () => {
                    const sortKey = header.dataset.sortKey;
                    if (!sortKey) return;

                    if (state.sort === sortKey) {
                        state.direction = state.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        state.sort = sortKey;
                        state.direction = 'desc';
                    }

                    reloadRowsForSort();
                });
            });

            if (tableWrap && tableWrap.scrollHeight <= tableWrap.clientHeight + 8 && state.hasMore) {
                fetchMoreRows();
            }
        })();
    </script>
@endsection
