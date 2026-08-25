@extends('admin.master')

@section('style')
    <style>
        .shipping-log-card {
            height: 100%;
            flex: 1 1 auto;
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
            scrollbar-gutter: stable;
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
            --shipping-log-filter-row-bg: #263847;
            --shipping-log-filter-accent: #36a4d1;
            --shipping-log-filter-control-bg: #142534;
            --shipping-log-filter-control-border: #4c7894;
            --shipping-log-filter-placeholder: #a8bdca;
            --shipping-log-filter-active: #f6b94a;
        }

        .shipping-log-table.is-column-width-locked {
            table-layout: fixed;
        }

        html[data-bs-theme="light"] .shipping-log-table {
            --shipping-log-border: rgba(0, 0, 0, .18);
            --shipping-log-head-bg: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            --shipping-log-head-cover: #f8f9fa;
            --shipping-log-row-bg: #ffffff;
            --shipping-log-filter-row-bg: #deedf6;
            --shipping-log-filter-accent: #1879ad;
            --shipping-log-filter-control-bg: #ffffff;
            --shipping-log-filter-control-border: #6f9fba;
            --shipping-log-filter-placeholder: #59717e;
            --shipping-log-filter-active: #b85f00;
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

        .shipping-log-heading-row th {
            top: -1px;
            z-index: 7;
        }

        .shipping-log-table .shipping-log-filter-row th {
            top: 29px;
            z-index: 6;
            height: 34px;
            padding: .18rem .28rem;
            background: var(--shipping-log-filter-row-bg) !important;
            border-top-color: var(--shipping-log-filter-accent) !important;
            box-shadow:
                inset 0 2px 0 color-mix(in srgb, var(--shipping-log-filter-accent) 75%, transparent),
                0 1px 0 var(--shipping-log-border),
                0 2px 4px rgba(0, 0, 0, .22);
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter {
            color: var(--bs-body-color);
            background-color: var(--shipping-log-filter-control-bg) !important;
            border-color: var(--shipping-log-filter-control-border) !important;
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter::placeholder {
            color: var(--shipping-log-filter-placeholder);
            opacity: 1;
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter:focus {
            border-color: var(--shipping-log-filter-accent) !important;
            box-shadow: 0 0 0 .08rem color-mix(in srgb, var(--shipping-log-filter-accent) 35%, transparent);
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter.is-filter-active {
            border-color: var(--shipping-log-filter-active) !important;
            box-shadow: inset 0 0 0 1px var(--shipping-log-filter-active);
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter.is-filter-active:focus {
            box-shadow:
                inset 0 0 0 1px var(--shipping-log-filter-active),
                0 0 0 .08rem color-mix(in srgb, var(--shipping-log-filter-active) 38%, transparent);
        }

        .shipping-log-filter {
            width: 100%;
            min-width: 0;
            height: 26px;
            min-height: 26px;
            padding: .08rem .32rem;
            font-size: .78rem;
            line-height: 1;
        }

        .shipping-log-date-range {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .20rem;
        }

        .shipping-log-date-filter-control {
            position: relative;
            min-width: 0;
        }

        .shipping-log-date-filter-control .shipping-log-filter {
            padding-right: 1.75rem;
            font-size: .70rem;
            letter-spacing: -.015em;
        }

        .shipping-log-date-open {
            position: absolute;
            top: 1px;
            right: 1px;
            z-index: 2;
            width: 24px;
            height: 24px;
            padding: 0;
            border: 0;
            border-left: 1px solid var(--bs-border-color);
            border-radius: 0 .2rem .2rem 0;
            background: transparent;
            line-height: 1;
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-date-open {
            color: var(--shipping-log-filter-placeholder);
            border-left-color: var(--shipping-log-filter-control-border);
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-filter.is-filter-active + .shipping-log-date-open {
            color: var(--shipping-log-filter-active);
            border-left-color: var(--shipping-log-filter-active);
        }

        .flatpickr-calendar {
            z-index: 1080 !important;
        }

        .shipping-log-clear-filters {
            width: 28px;
            height: 26px;
            padding: 0;
            line-height: 1;
        }

        .shipping-log-table .shipping-log-filter-row .shipping-log-clear-filters {
            color: var(--shipping-log-filter-placeholder);
            background-color: var(--shipping-log-filter-control-bg);
            border-color: var(--shipping-log-filter-control-border);
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

        .shipping-log-col-completed,
        .shipping-log-col-shipment {
            width: 23ch;
            min-width: 190px;
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
            min-width: 160px;
            white-space: normal !important;
        }

        .shipping-log-col-shipment .shipping-log-input {
            width: 100%;
            min-width: 0;
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
            min-width: 160px;
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

        .shipping-log-print-watermark {
            display: none;
        }

        @media print {
            .no-print,
            .sidebar,
            #sidebarColumn,
            .shipping-log-card .card-header {
                display: none !important;
            }

            .shipping-log-filter-row {
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
                table-layout: auto;
                color: #000;
            }

            .shipping-log-table col {
                width: auto !important;
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

            .shipping-log-print-watermark {
                display: block !important;
                position: fixed;
                top: 50%;
                left: 50%;
                z-index: 9998;
                width: max-content;
                color: rgba(0, 0, 0, .11) !important;
                font: 700 28pt/1 Arial, sans-serif;
                letter-spacing: .08em;
                white-space: nowrap;
                pointer-events: none;
                transform: translate(-50%, -50%) rotate(-32deg);
                transform-origin: center;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endsection

@section('content')
    <div class="shipping-log-print-watermark" aria-hidden="true">CONFIDENTIAL — FOR INTERNAL USE ONLY</div>

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
                    <colgroup id="shippingLogColumns">
                        <col data-column-key="wo">
                        <col data-column-key="part">
                        <col data-column-key="customer">
                        <col data-column-key="customer_po">
                        <col data-column-key="completed">
                        <col data-column-key="shipment">
                        <col data-column-key="forwarder">
                        <col data-column-key="awb">
                        <col data-column-key="notes">
                        <col data-column-key="action">
                    </colgroup>
                    <thead>
                    <tr class="shipping-log-heading-row">
                        <th class="text-center text-primary sortable" data-sort-key="wo">WO No. <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary">Part No.</th>
                        <th class="text-center text-primary">Customer name</th>
                        <th class="text-center text-primary">Cust PO No.</th>
                        <th class="text-center text-primary sortable shipping-log-col-completed" data-sort-key="completed">Completed <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary sortable shipping-log-col-shipment" data-sort-key="shipment">Shipment <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary shipping-log-col-forwarder">Freight Forwarder</th>
                        <th class="text-center text-primary shipping-log-col-awb">AWB No.</th>
                        <th class="text-center text-primary shipping-log-col-notes">NOTES</th>
                        <th class="text-center text-primary shipping-log-col-action no-print"></th>
                    </tr>
                    <tr class="shipping-log-filter-row no-print">
                        <th><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="wo" value="{{ $filters['wo'] }}" placeholder="Filter" aria-label="Filter WO No." autocomplete="off"></th>
                        <th><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="part" value="{{ $filters['part'] }}" placeholder="Filter" aria-label="Filter Part No." autocomplete="off"></th>
                        <th><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="customer" value="{{ $filters['customer'] }}" placeholder="Filter" aria-label="Filter Customer name" autocomplete="off"></th>
                        <th><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="customer_po" value="{{ $filters['customer_po'] }}" placeholder="Filter" aria-label="Filter Cust PO No." autocomplete="off"></th>
                        <th class="shipping-log-col-completed">
                            <div class="shipping-log-date-range">
                                <div class="shipping-log-date-filter-control">
                                    <input id="shippingLogCompletedFrom" type="text" maxlength="9" class="form-control form-control-sm shipping-log-filter" data-filter-key="completed_from" value="{{ $filters['completed_from'] }}" placeholder="From ≥" aria-label="Completed from, inclusive" data-project-date data-project-date-lower data-project-date-short-year autocomplete="off">
                                    <button type="button" class="btn btn-sm btn-outline-secondary shipping-log-date-open" data-date-target="shippingLogCompletedFrom" data-open-label="Open Completed from calendar" data-clear-label="Clear Completed from date" title="Open Completed from calendar" aria-label="Open Completed from calendar"><i class="bi bi-calendar3"></i></button>
                                </div>
                                <div class="shipping-log-date-filter-control">
                                    <input id="shippingLogCompletedTo" type="text" maxlength="9" class="form-control form-control-sm shipping-log-filter" data-filter-key="completed_to" value="{{ $filters['completed_to'] }}" placeholder="To ≤" aria-label="Completed to, inclusive" data-project-date data-project-date-lower data-project-date-short-year autocomplete="off">
                                    <button type="button" class="btn btn-sm btn-outline-secondary shipping-log-date-open" data-date-target="shippingLogCompletedTo" data-open-label="Open Completed to calendar" data-clear-label="Clear Completed to date" title="Open Completed to calendar" aria-label="Open Completed to calendar"><i class="bi bi-calendar3"></i></button>
                                </div>
                            </div>
                        </th>
                        <th class="shipping-log-col-shipment">
                            <div class="shipping-log-date-range">
                                <div class="shipping-log-date-filter-control">
                                    <input id="shippingLogShipmentFrom" type="text" maxlength="9" class="form-control form-control-sm shipping-log-filter" data-filter-key="shipment_from" value="{{ $filters['shipment_from'] }}" placeholder="From ≥" aria-label="Shipment from, inclusive" data-project-date data-project-date-lower data-project-date-short-year autocomplete="off">
                                    <button type="button" class="btn btn-sm btn-outline-secondary shipping-log-date-open" data-date-target="shippingLogShipmentFrom" data-open-label="Open Shipment from calendar" data-clear-label="Clear Shipment from date" title="Open Shipment from calendar" aria-label="Open Shipment from calendar"><i class="bi bi-calendar3"></i></button>
                                </div>
                                <div class="shipping-log-date-filter-control">
                                    <input id="shippingLogShipmentTo" type="text" maxlength="9" class="form-control form-control-sm shipping-log-filter" data-filter-key="shipment_to" value="{{ $filters['shipment_to'] }}" placeholder="To ≤" aria-label="Shipment to, inclusive" data-project-date data-project-date-lower data-project-date-short-year autocomplete="off">
                                    <button type="button" class="btn btn-sm btn-outline-secondary shipping-log-date-open" data-date-target="shippingLogShipmentTo" data-open-label="Open Shipment to calendar" data-clear-label="Clear Shipment to date" title="Open Shipment to calendar" aria-label="Open Shipment to calendar"><i class="bi bi-calendar3"></i></button>
                                </div>
                            </div>
                        </th>
                        <th class="shipping-log-col-forwarder"><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="forwarder" value="{{ $filters['forwarder'] }}" placeholder="Filter" aria-label="Filter Freight Forwarder" autocomplete="off"></th>
                        <th class="shipping-log-col-awb"><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="awb" value="{{ $filters['awb'] }}" placeholder="Filter" aria-label="Filter AWB No." autocomplete="off"></th>
                        <th class="shipping-log-col-notes"><input type="search" class="form-control form-control-sm shipping-log-filter" data-filter-key="notes" value="{{ $filters['notes'] }}" placeholder="Filter" aria-label="Filter Notes" autocomplete="off"></th>
                        <th class="text-center shipping-log-col-action">
                            <button type="button" class="btn btn-sm btn-outline-secondary shipping-log-clear-filters" id="shippingLogClearFilters" title="Clear column filters" aria-label="Clear column filters">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </th>
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
            const table = tableWrap?.querySelector('.shipping-log-table');
            const tbody = document.getElementById('shippingLogRows');
            const loadStatus = document.getElementById('shippingLogLoadStatus');
            const loadedEl = document.getElementById('shippingLogLoaded');
            const totalEl = document.getElementById('shippingLogTotal');
            const sortableHeaders = document.querySelectorAll('.shipping-log-table th.sortable[data-sort-key]');
            const filterInputs = Array.from(document.querySelectorAll('.shipping-log-filter[data-filter-key]'));
            const clearFiltersButton = document.getElementById('shippingLogClearFilters');
            const dateOpenButtons = document.querySelectorAll('.shipping-log-date-open[data-date-target]');
            let filterTimer = null;
            let activeRequestController = null;

            function lockColumnWidths() {
                if (!table || table.classList.contains('is-column-width-locked')) return;

                const headers = Array.from(table.querySelectorAll('.shipping-log-heading-row th'));
                const columns = Array.from(table.querySelectorAll('#shippingLogColumns col'));
                if (headers.length === 0 || headers.length !== columns.length) return;

                headers.forEach((header, index) => {
                    const width = header.getBoundingClientRect().width;
                    if (width > 0) {
                        columns[index].style.width = `${width}px`;
                    }
                });

                table.classList.add('is-column-width-locked');
            }

            lockColumnWidths();

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
                filters: Object.fromEntries(filterInputs.map((input) => [input.dataset.filterKey, input.value.trim()])),
                requestVersion: 0,
            };

            function readFilters() {
                return Object.fromEntries(filterInputs.map((input) => [input.dataset.filterKey, input.value.trim()]));
            }

            function appendFilters(params) {
                Object.entries(state.filters).forEach(([key, value]) => {
                    if (value) params.set(`filters[${key}]`, value);
                });
            }

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

                Array.from(url.searchParams.keys())
                    .filter((key) => key.startsWith('filters['))
                    .forEach((key) => url.searchParams.delete(key));
                appendFilters(url.searchParams);

                url.searchParams.delete('page');
                window.history.replaceState({}, '', url.toString());
            }

            async function fetchRows({ replace = false } = {}) {
                if ((!replace && !state.hasMore) || state.loading || !state.endpoint || !tbody) return;

                const requestVersion = state.requestVersion;
                activeRequestController = new AbortController();
                state.loading = true;
                updateLoadStatus(replace ? 'Filtering...' : 'Loading...', 'text-info');

                const params = new URLSearchParams();
                params.set('fragment', '1');
                params.set('per_page', '100');
                params.set('page', String(replace ? 1 : (state.nextPage || 1)));
                params.set('sort', state.sort);
                params.set('direction', state.direction);
                if (state.q) params.set('q', state.q);
                appendFilters(params);

                try {
                    const response = await fetch(`${state.endpoint}?${params.toString()}`, {
                        signal: activeRequestController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to load rows');
                    }

                    if (requestVersion !== state.requestVersion) return;

                    const template = document.createElement('template');
                    template.innerHTML = data.html || '';
                    const fragment = template.content;

                    if (replace) {
                        tbody.replaceChildren(fragment);
                        tableWrap.scrollTop = 0;
                    } else {
                        tbody.querySelector('.shipping-log-empty-row')?.remove();
                        tbody.appendChild(fragment);
                    }

                    if (Number(data.loaded_count || 0) === 0) {
                        tbody.innerHTML = '<tr class="shipping-log-empty-row"><td colspan="10" class="text-center text-muted py-4">No workorders found</td></tr>';
                    }

                    initializeRows(tbody);

                    state.nextPage = Number(data.next_page || 0);
                    state.hasMore = Boolean(data.has_more);
                    state.loadedCount = replace
                        ? Number(data.loaded_count || 0)
                        : state.loadedCount + Number(data.loaded_count || 0);
                    state.totalCount = Number(data.total_count ?? state.totalCount);
                    updateCounters();
                } catch (error) {
                    if (error.name === 'AbortError' || requestVersion !== state.requestVersion) return;
                    updateLoadStatus(error.message || 'Failed to load rows', 'text-danger');
                } finally {
                    if (requestVersion === state.requestVersion) {
                        state.loading = false;
                        activeRequestController = null;
                    }
                }
            }

            async function reloadRows() {
                if (!tbody) return;

                state.requestVersion += 1;
                activeRequestController?.abort();
                activeRequestController = null;
                state.loading = false;
                updateSortHeaders();
                syncUrl();
                await fetchRows({ replace: true });
            }

            async function maybeLoadMoreOnScroll() {
                if (!tableWrap || !state.hasMore || state.loading) return;

                const threshold = 180;
                const remaining = tableWrap.scrollHeight - tableWrap.scrollTop - tableWrap.clientHeight;
                if (remaining <= threshold) {
                    await fetchRows();
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

                    reloadRows();
                });
            });
            function scheduleFilterReload() {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(() => {
                    state.filters = readFilters();
                    reloadRows();
                }, 320);
            }

            function syncFilterActiveState(input) {
                const active = input.value.trim() !== '';
                input.classList.toggle('is-filter-active', active);

                if (!input.id) return;

                const button = Array.from(dateOpenButtons).find((candidate) => candidate.dataset.dateTarget === input.id);
                if (!button) return;

                const label = active ? button.dataset.clearLabel : button.dataset.openLabel;
                const icon = button.querySelector('i');
                button.classList.toggle('is-date-clear', active);
                button.title = label || '';
                button.setAttribute('aria-label', label || '');
                if (icon) {
                    icon.className = active ? 'bi bi-x-lg' : 'bi bi-calendar3';
                }
            }

            function handleFilterChange(event) {
                syncFilterActiveState(event.currentTarget);
                scheduleFilterReload();
            }

            filterInputs.forEach((input) => {
                syncFilterActiveState(input);
                input.addEventListener('input', handleFilterChange);
                input.addEventListener('change', handleFilterChange);
            });
            dateOpenButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.dateTarget || '');
                    const picker = input?._projectDatePicker || input?._flatpickr;

                    if (input?.value.trim()) {
                        window.clearTimeout(filterTimer);
                        if (picker?.clear) {
                            picker.clear(false);
                        } else {
                            input.value = '';
                        }
                        syncFilterActiveState(input);
                        state.filters = readFilters();
                        reloadRows();
                        return;
                    }

                    if (picker?.open) {
                        picker.open();
                    } else {
                        input?.focus();
                    }
                });
            });
            clearFiltersButton?.addEventListener('click', () => {
                window.clearTimeout(filterTimer);
                filterInputs.forEach((input) => {
                    const picker = input._projectDatePicker || input._flatpickr;
                    if (picker?.clear) {
                        picker.clear(false);
                    } else {
                        input.value = '';
                    }
                    syncFilterActiveState(input);
                });
                state.filters = readFilters();
                reloadRows();
            });

            if (tableWrap && tableWrap.scrollHeight <= tableWrap.clientHeight + 8 && state.hasMore) {
                fetchRows();
            }
        })();
    </script>
@endsection
