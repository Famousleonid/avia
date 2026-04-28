@extends('admin.master')

@section('style')
    <style>
        .content {
            overflow-y: auto !important;
        }

        .content:has(.vendor-tracking-page) {
            padding-top: 0 !important;
        }

        .content-inner {
            display: block !important;
            height: auto !important;
            min-height: 100%;
        }

        .vendor-tracking-page .card,
        .vendor-tracking-page .btn,
        .vendor-tracking-page .form-control,
        .vendor-tracking-page .form-select {
            border-radius: 8px;
        }

        .vendor-tracking-page {
            color: #1f2937;
        }

        .vendor-tracking-page .card {
            background: #ffffff !important;
            border: 1px solid #d7e0ea !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .vendor-tracking-page .card-body {
            background: transparent;
        }

        .vendor-tracking-filters {
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
        }

        .vendor-tracking-filter-vendor {
            flex: 0 0 150px;
        }

        .vendor-tracking-filter-status {
            flex: 0 0 105px;
        }

        .vendor-tracking-filter-types {
            flex: 0 0 220px;
            margin-left: 1.25rem;
        }

        .vendor-tracking-filter-text {
            flex: 0 0 145px;
        }

        .vendor-tracking-filter-vnull {
            flex: 0 0 125px;
            margin-left: 2.75rem;
        }

        .vendor-tracking-type-grid {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .vendor-tracking-check {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin: 0 !important;
            padding-left: 0 !important;
            min-width: 0;
        }

        .vendor-tracking-check .form-check-input {
            margin: 0 !important;
            float: none !important;
        }

        .vendor-tracking-check .form-check-label {
            margin: 0;
        }

        .vendor-tracking-wo-link {
            text-decoration: none;
        }

        .vendor-tracking-wo-link:hover,
        .vendor-tracking-wo-link:focus {
            text-decoration: none;
        }

        .vendor-tracking-headline {
            display: flex;
            align-items: baseline;
            gap: .85rem 1rem;
            flex-wrap: wrap;
        }

        .vendor-tracking-sticky-shell {
            position: sticky;
            top: 0;
            z-index: 30;
            background: #ffffff;
            padding-top: .15rem;
            padding-bottom: .35rem;
            margin-bottom: .35rem;
        }

        .vendor-tracking-sticky-shell::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: #d7e0ea;
        }

        .vendor-tracking-counts {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .vendor-tracking-count-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: #f3f6f9;
            border: 1px solid #d7e0ea;
            color: #5f6b7a;
            font-size: .78rem;
            font-weight: 500;
            letter-spacing: .02em;
        }

        .vendor-tracking-count-number {
            color: #1f2937;
            font-size: 18px;
            font-weight: 400;
            line-height: 1;
        }

        .vendor-tracking-loadmore {
            display: flex;
            justify-content: center;
            padding: 1rem 0 .25rem;
            color: #adb5bd;
            font-size: .9rem;
        }

        .vendor-tracking-loadmore.is-hidden {
            display: none;
        }

        .vendor-tracking-inline-select,
        .vendor-tracking-inline-input {
            min-width: 120px;
            background-color: #ffffff;
            color: #1f2937;
            border: 1px solid #cfd8e3;
        }

        .vendor-tracking-inline-select,
        .vendor-tracking-inline-select:hover {
            cursor: pointer;
        }

        .vendor-tracking-inline-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23606f7b' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 5.5 8 12l6.5-6.5-.9-.9L8 10.2 2.4 4.6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .4rem center;
            background-size: .85rem .85rem;
            padding-right: 1.45rem;
            text-overflow: clip;
        }

        .vendor-tracking-inline-select:focus,
        .vendor-tracking-inline-input:focus {
            background-color: #ffffff;
            color: #111827;
            border-color: rgba(13, 110, 253, 0.45);
            box-shadow: 0 0 0 .16rem rgba(13, 110, 253, 0.18);
        }

        .vendor-tracking-save-cell {
            transition: background-color .18s ease, box-shadow .18s ease;
        }

        .vendor-tracking-save-cell.is-saving {
            background: rgba(13, 110, 253, 0.12);
        }

        .vendor-tracking-save-cell.is-saved {
            background: rgba(25, 135, 84, 0.14);
        }

        .vendor-tracking-save-cell.is-error {
            background: rgba(220, 53, 69, 0.16);
        }

        .vendor-tracking-icon-btn {
            width: 26px;
            height: 26px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            font-size: .9rem;
        }

        .vendor-tracking-icon-btn:disabled {
            opacity: .45;
        }
        /*--------------------------------------------------------------*/

        .vendor-tracking-utility-col {
            width: 36px;
            min-width: 36px;
            max-width: 36px;
            text-align: center;
            padding-left: .2rem !important;
            padding-right: .2rem !important;
        }

        .vendor-tracking-type-col {
            width: 56px;
            min-width: 56px;
            max-width: 56px;
        }

        .vendor-tracking-vendor-col {
            width: 16%;
            min-width: 150px;
        }

        .vendor-tracking-vendor-select-wrap {
            position: relative;
            min-height: calc(1.8125rem + 2px);
        }

        .vendor-tracking-vendor-select-wrap .vendor-tracking-inline-select {
            width: 100%;
            min-width: 0;
        }

        .vendor-tracking-vendor-select-wrap .vendor-tracking-inline-select.is-expanded {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 40;
            width: var(--vendor-select-open-width, 100%);
            min-width: max(100%, var(--vendor-select-open-width, 100%));
            max-width: none;
        }

        .vendor-tracking-repair-col {
            width: 74px;
            min-width: 74px;
            max-width: 74px;
        }

        .vendor-tracking-wo-col {
            width: 92px;
            min-width: 92px;
            max-width: 92px;
        }

        td.vendor-tracking-wo-col {
            text-align: center;
        }

        .vendor-tracking-customer-col {
            width: 11%;
            min-width: 110px;
        }

        .vendor-tracking-ipl-col {
            width: 8%;
        }

        .vendor-tracking-part-col {
            width: 12%;
        }

        .vendor-tracking-serial-col {
            width: 9%;
        }

        .vendor-tracking-process-col {
            width: 16%;
        }

        .vendor-tracking-date-col {
            width: 114px;
            min-width: 114px;
            max-width: 114px;
        }

        .tasks-table .fp-alt,
        .table.table-dark .fp-alt {
            height: calc(1.8125rem + 2px) !important;
            padding: .25rem .5rem !important;
            line-height: 1.2 !important;
        }

        .vendor-tracking-date-col .finish-input,
        td .finish-input.js-vendor-tracking-date,
        td .fp-alt.finish-input {
            width: 100% !important;
            min-width: 0 !important;
        }

        .vendor-tracking-repair-col .vendor-tracking-inline-input {
            width: 100%;
            min-width: 0;
        }

        .finish-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .2rem center;
            background-size: 1rem 1rem;
            padding-right: 2rem;
        }

        .finish-input.has-finish {
            background-color: rgba(25, 135, 84, .1);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23198754' viewBox='0 0 16 16'%3E%3Cpath d='M13.485 1.929a.75.75 0 010 1.06L6.818 9.657a.75.75 0 01-1.06 0L2.515 6.414a.75.75 0 111.06-1.06L6 7.778l6.425-6.425a.75.75 0 011.06 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .7rem center;
            background-size: 1rem 1rem;
            padding-right: 1.6rem;
        }

        .fp-alt-wrap:has(.finish-input.has-finish) .fp-cal-btn {
            display: none !important;
        }

        .fp-alt-wrap {
            position: relative;
        }

        .fp-cal-btn {
            position: absolute;
            top: 50%;
            right: .35rem;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6c757d;
            padding: 0;
            line-height: 1;
        }

        .vendor-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: .75rem;
        }

        .vendor-media-item {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: .5rem;
            padding: .65rem;
            background: rgba(255, 255, 255, 0.03);
        }

        .vendor-media-thumb {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: .4rem;
            margin-bottom: .5rem;
        }

        .vendor-media-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .vendor-media-link:hover {
            color: inherit;
        }

        .vendor-profile-status {
            min-width: 110px;
        }

        .vendor-tracking-info-btn {
            width: 28px;
            height: 28px;
        }

        .vendor-tracking-vendor-cell .vendor-tracking-inline-select {
            width: 100%;
            min-width: 0;
        }

        .vendor-tracking-sort-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            color: inherit;
            text-decoration: none;
        }

        .vendor-tracking-sort-link:hover,
        .vendor-tracking-sort-link:focus {
            color: #fff;
            text-decoration: none;
        }

        .vendor-tracking-sort-icon {
            font-size: .72rem;
            opacity: .65;
        }

        .vendor-tracking-sort-link.is-active {
            color: #fff;
        }

        .vendor-tracking-sort-link.is-active .vendor-tracking-sort-icon {
            opacity: 1;
        }

        .vendor-tracking-info-stack {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            width: 100%;
        }

        .vendor-tracking-toolbar-btn {
            font-weight: 600;
            border-width: 1px;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
        }

        .vendor-tracking-toolbar-btn.btn-outline-secondary {
            color: #334155;
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .vendor-tracking-toolbar-btn.btn-outline-secondary:hover,
        .vendor-tracking-toolbar-btn.btn-outline-secondary:focus {
            color: #0f172a;
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        .vendor-tracking-toolbar-btn.btn-outline-success {
            color: #166534;
            border-color: #22c55e;
            background: #ecfdf3;
        }

        .vendor-tracking-toolbar-btn.btn-outline-success:hover,
        .vendor-tracking-toolbar-btn.btn-outline-success:focus {
            color: #0f2f1f;
            background: #bbf7d0;
            border-color: #22c55e;
        }

        .vendor-tracking-page .table-responsive {
            background: #ffffff;
            border-radius: 10px;
        }

        .vendor-tracking-table {
            --bs-table-bg: #ffffff;
            --bs-table-color: #1f2937;
            --bs-table-border-color: #d7e0ea;
            --bs-table-hover-bg: #f8fbff;
            --bs-table-hover-color: #111827;
            margin-bottom: 0;
        }

        .vendor-tracking-table > thead > tr > th {
            background: #f4f7fb;
            color: #334155;
            border-color: #d7e0ea;
            vertical-align: middle;
        }

        .vendor-tracking-table > tbody > tr > td {
            background: #ffffff;
            color: #1f2937;
            border-color: #d7e0ea;
            vertical-align: middle;
        }

        .vendor-tracking-table a {
            color: #0ea5e9;
        }

        .vendor-tracking-table a:hover,
        .vendor-tracking-table a:focus {
            color: #0284c7;
        }

        .vendor-tracking-page .text-muted,
        .vendor-tracking-page .small.text-muted {
            color: #6b7280 !important;
        }

        #vendorTrackingSettingsModal .modal-content,
        #vendorInfoModal .modal-content {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d7e0ea;
        }

        #vendorTrackingSettingsModal .modal-header,
        #vendorTrackingSettingsModal .modal-footer,
        #vendorInfoModal .modal-header,
        #vendorInfoModal .modal-footer {
            border-color: #d7e0ea;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page {
            color: #f8f9fa;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .card {
            background: #232525 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: none;
        }

        html[data-bs-theme="dark"] .vendor-tracking-sticky-shell {
            background: #232525;
        }

        html[data-bs-theme="dark"] .vendor-tracking-sticky-shell::after {
            background: rgba(255, 255, 255, 0.07);
        }

        html[data-bs-theme="dark"] .vendor-tracking-count-badge {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.08);
            color: #adb5bd;
        }

        html[data-bs-theme="dark"] .vendor-tracking-count-number {
            color: #ffffff;
        }

        html[data-bs-theme="dark"] .vendor-tracking-inline-select,
        html[data-bs-theme="dark"] .vendor-tracking-inline-input {
            background-color: #171b22;
            color: #f8f9fa;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        html[data-bs-theme="dark"] .vendor-tracking-inline-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23adb5bd' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 5.5 8 12l6.5-6.5-.9-.9L8 10.2 2.4 4.6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .4rem center;
            background-size: .85rem .85rem;
        }

        html[data-bs-theme="dark"] .vendor-tracking-inline-select:focus,
        html[data-bs-theme="dark"] .vendor-tracking-inline-input:focus {
            background-color: #1d2330;
            color: #ffffff;
            border-color: rgba(13, 110, 253, 0.6);
        }

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-secondary {
            color: #f8f9fa;
            border-color: #adb5bd;
            background: rgba(173, 181, 189, 0.14);
        }

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-secondary:hover,
        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-secondary:focus {
            color: #111418;
            background: #dee2e6;
            border-color: #dee2e6;
        }

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-success {
            color: #d1ffe3;
            border-color: #39d98a;
            background: rgba(57, 217, 138, 0.18);
        }

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-success:hover,
        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-success:focus {
            color: #08140d;
            background: #39d98a;
            border-color: #39d98a;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .table-responsive {
            background: transparent;
        }

        html[data-bs-theme="dark"] .vendor-tracking-table {
            --bs-table-bg: #232525;
            --bs-table-color: #f8f9fa;
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.03);
            --bs-table-hover-color: #ffffff;
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > thead > tr > th {
            background: #232525;
            color: #adb5bd;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > tbody > tr > td {
            background: #232525;
            color: #f8f9fa;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table a {
            color: #22c7ff;
        }

        html[data-bs-theme="dark"] .vendor-tracking-table a:hover,
        html[data-bs-theme="dark"] .vendor-tracking-table a:focus {
            color: #68d8ff;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .text-muted,
        html[data-bs-theme="dark"] .vendor-tracking-page .small.text-muted {
            color: #adb5bd !important;
        }

        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-content,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-content {
            background: #232525;
            color: #f8f9fa;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-header,
        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-footer,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-header,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-footer {
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-column-item {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
        }

        .vendor-tracking-column-list {
            display: grid;
            gap: .5rem;
        }

        .vendor-tracking-column-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .55rem .7rem;
            border: 1px solid #d7e0ea;
            border-radius: .6rem;
            background: #f8fafc;
            cursor: grab;
            user-select: none;
        }

        .vendor-tracking-column-item.is-dragging {
            opacity: .55;
            border-color: rgba(13, 110, 253, 0.55);
        }

        .vendor-tracking-column-item.is-drop-target {
            border-color: rgba(25, 135, 84, 0.75);
            background: rgba(25, 135, 84, 0.12);
        }

        .vendor-tracking-column-drag {
            color: #adb5bd;
            letter-spacing: .08em;
            font-size: .85rem;
        }

        .vendor-tracking-column-item .form-check-input {
            margin: 0;
        }

        .vendor-tracking-column-help {
            font-size: .8rem;
            color: #adb5bd;
        }
    </style>
@endsection

@section('content')
    <script>
        (function () {
            try {
                const stateKey = 'vendorTrackingFilters';
                const params = new URLSearchParams(window.location.search);
                const hasExplicitQuery = params.toString() !== '';
                const stored = JSON.parse(localStorage.getItem(stateKey) || 'null');

                if (!hasExplicitQuery && stored && typeof stored === 'object') {
                    if (stored.vendor_id && stored.vendor_id !== '0') {
                        params.set('vendor_id', stored.vendor_id);
                    }

                    if (stored.status && stored.status !== 'all') {
                        params.set('status', stored.status);
                    }

                    if (Array.isArray(stored.sources) && stored.sources.length && stored.sources.length < 3) {
                        stored.sources.forEach(source => params.append('sources[]', source));
                    }

                    if (stored.include_vendor_null) {
                        params.set('include_vendor_null', '1');
                    }

                    ['workorder', 'part_number', 'repair_order'].forEach(function (field) {
                        const value = typeof stored[field] === 'string' ? stored[field].trim() : '';
                        if (value !== '') {
                            params.set(field, value);
                        }
                    });
                }

                if (!hasExplicitQuery && params.toString() !== '') {
                    window.location.replace(window.location.pathname + '?' + params.toString());
                }
            } catch (error) {
                console.warn('Vendor tracking filter restore failed.', error);
            }
        })();
    </script>

    <div class="container-fluid vendor-tracking-page my-1">
        @php
            $currentSort = $filters['sort'] ?? 'wo';
            $currentDirection = $filters['direction'] ?? 'desc';
            $sortUrl = function (string $column) use ($currentSort, $currentDirection) {
                $direction = $currentSort === $column && $currentDirection === 'asc' ? 'desc' : 'asc';

                return route('vendor-tracking.index', array_merge(request()->query(), [
                    'sort' => $column,
                    'direction' => $direction,
                ]));
            };
            $sortIcon = function (string $column) use ($currentSort, $currentDirection) {
                if ($currentSort !== $column) {
                    return 'bi-arrow-down-up';
                }

                return $currentDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
            };
        @endphp
        <div class="vendor-tracking-sticky-shell">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="vendor-tracking-headline">
                    <h5 class="mb-0">Vendor Tracking</h5>
                    <div class="text-muted small">STD, part, and bushing processes sent to vendors by repair order and dates.</div>
                    <div class="vendor-tracking-counts">
                        <span class="vendor-tracking-count-badge">Selected: &nbsp; <span class="vendor-tracking-count-number">{{ number_format($summary['filtered_total'] ?? 0) }}</span></span>
                        <span class="vendor-tracking-count-badge">Total: &nbsp; <span class="vendor-tracking-count-number">{{ number_format($summary['total_rows'] ?? 0) }}</span></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm vendor-tracking-toolbar-btn" id="vendorTrackingSettingsBtn" title="Table settings">
                        <i class="bi bi-gear me-1"></i> Settings
                    </button>
                    <a href="{{ route('vendor-tracking.export', request()->query()) }}" class="btn btn-outline-success btn-sm vendor-tracking-toolbar-btn" id="vendorTrackingExportBtn" title="Export to Excel">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                </div>
            </div>

            <div class="card bg-gradient mb-2">
                <div class="card-body">
                    <form method="GET" action="{{ route('vendor-tracking.index') }}" class="vendor-tracking-filters d-flex gap-2 align-items-end">
                        <div class="vendor-tracking-filter-vendor">
                            <label class="form-label small text-muted">Vendor</label>
                            <select name="vendor_id" class="form-select form-select-sm">
                                <option value="0">All vendors</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((int) $filters['vendor_id'] === (int) $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="vendor-tracking-filter-status">
                            <label class="form-label small text-muted">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="all" @selected($filters['status'] === 'all')>All</option>
                                <option value="open" @selected($filters['status'] === 'open')>At vendor</option>
                                <option value="returned" @selected($filters['status'] === 'returned')>Returned</option>
                            </select>
                        </div>

                        <div class="vendor-tracking-filter-text">
                            <label class="form-label small text-muted">Workorder</label>
                            <input name="workorder" class="form-control form-control-sm" value="{{ $filters['workorder'] }}" placeholder="WO no">
                        </div>

                        <div class="vendor-tracking-filter-text">
                            <label class="form-label small text-muted">Part number</label>
                            <input name="part_number" class="form-control form-control-sm" value="{{ $filters['part_number'] }}" placeholder="P/N">
                        </div>

                        <div class="vendor-tracking-filter-text">
                            <label class="form-label small text-muted">Repair order</label>
                            <input name="repair_order" class="form-control form-control-sm" value="{{ $filters['repair_order'] }}" placeholder="RO">
                        </div>

                        <div class="vendor-tracking-filter-vnull">
                            <label class="form-label small text-muted d-block">Vendor</label>
                            <label class="form-check vendor-tracking-check mb-0">
                                <input class="form-check-input" type="checkbox" id="vendorTrackingIncludeNull" name="include_vendor_null" value="1" @checked($filters['include_vendor_null'] ?? false)>
                                <span class="form-check-label small">null</span>
                            </label>
                        </div>

                        <div class="vendor-tracking-filter-types">
                            <label class="form-label small text-muted d-block">Type</label>
                            <div class="vendor-tracking-type-grid">
                                <label class="form-check vendor-tracking-check mb-0">
                                    <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="part" @checked(in_array('part', $filters['sources'], true))>
                                    <span class="form-check-label small">Part</span>
                                </label>
                                <label class="form-check vendor-tracking-check mb-0">
                                    <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="std" @checked(in_array('std', $filters['sources'], true))>
                                    <span class="form-check-label small">STD</span>
                                </label>
                                <label class="form-check vendor-tracking-check mb-0">
                                    <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="bushing" @checked(in_array('bushing', $filters['sources'], true))>
                                    <span class="form-check-label small">Bushing</span>
                                </label>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="card bg-gradient">
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="vendorTrackingTable" class="table table-sm table-bordered table-hover align-middle mb-0 vendor-tracking-table">
                        <thead>
                            <tr class="text-muted small">
                                <th class="vendor-tracking-repair-col" data-col="repair_order">RO</th>
                                <th class="vendor-tracking-type-col" data-col="type">
                                    <a href="{{ $sortUrl('type') }}" class="vendor-tracking-sort-link {{ $currentSort === 'type' ? 'is-active' : '' }}">
                                        <span>Type</span>
                                        <i class="bi {{ $sortIcon('type') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="vendor-tracking-utility-col text-center" title="Info: vendor files and vendor info" data-col="info">
                                    <i class="bi bi-info-circle"></i>
                                </th>
                                <th class="vendor-tracking-vendor-col" data-col="vendor">
                                    <a href="{{ $sortUrl('vendor') }}" class="vendor-tracking-sort-link {{ $currentSort === 'vendor' ? 'is-active' : '' }}">
                                        <span>Vendor</span>
                                        <i class="bi {{ $sortIcon('vendor') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="vendor-tracking-wo-col" data-col="wo">
                                    <a href="{{ $sortUrl('wo') }}" class="vendor-tracking-sort-link {{ $currentSort === 'wo' ? 'is-active' : '' }}">
                                        <span>WO</span>
                                        <i class="bi {{ $sortIcon('wo') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="vendor-tracking-customer-col" data-col="customer">Customer</th>
                                <th class="vendor-tracking-ipl-col" data-col="ipl">
                                    <a href="{{ $sortUrl('ipl') }}" class="vendor-tracking-sort-link {{ $currentSort === 'ipl' ? 'is-active' : '' }}">
                                        <span>IPL</span>
                                        <i class="bi {{ $sortIcon('ipl') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="vendor-tracking-part-col" data-col="part_number">Part number</th>
                                <th class="vendor-tracking-serial-col" data-col="serial">Serial</th>
                                <th class="vendor-tracking-process-col" data-col="process">
                                    <a href="{{ $sortUrl('process') }}" class="vendor-tracking-sort-link {{ $currentSort === 'process' ? 'is-active' : '' }}">
                                        <span>Process</span>
                                        <i class="bi {{ $sortIcon('process') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="text-center vendor-tracking-date-col" data-col="sent">Sent (edit)</th>
                                <th class="text-center vendor-tracking-date-col" data-col="returned">Returned (edit)</th>
                                <th class="text-center" data-col="days">Days</th>
                                <th class="text-center" data-col="status">Status</th>
                            </tr>
                        </thead>
                        <tbody id="vendorTrackingBody">
                            @forelse($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $sent = $row->date_start;
                                    $returned = $row->date_finish;
                                    $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
                                    $woNumber = (string) ($wo?->number ?? '');
                                    $woDisplay = trim('w ' . preg_replace('/(\d{3})(?=\d)/', '$1 ', $woNumber));
                                    $vendorId = (int) ($row->vendor?->id ?? 0);
                                    $typeTextClass = match ($row->source) {
                                        'STD' => 'text-success',
                                        'Part' => 'text-primary',
                                        'Bush' => 'text-light',
                                        default => 'text-secondary',
                                    };
                                @endphp
                                <tr data-row-id="{{ $row->id }}" data-source-key="{{ $row->source_key }}">
                                    <td class="vendor-tracking-save-cell vendor-tracking-repair-col" data-col="repair_order">
                                        <input type="text" class="form-control form-control-sm vendor-tracking-inline-input js-vendor-tracking-repair-order" value="{{ $row->repair_order ?? '' }}">
                                    </td>
                                    <td class="vendor-tracking-type-col" data-col="type"><span class="fw-semibold {{ $typeTextClass }}">{{ $row->source }}</span></td>
                                    <td class="vendor-tracking-save-cell text-center" data-col="info">
                                        <div class="vendor-tracking-info-stack">
                                            <button type="button" class="btn btn-sm {{ ($row->vendor?->is_trusted ?? false) ? 'btn-outline-success' : 'btn-outline-info' }} vendor-tracking-icon-btn vendor-tracking-info-btn js-vendor-info-open" data-vendor-id="{{ $vendorId ?: '' }}" data-trusted="{{ ($row->vendor?->is_trusted ?? false) ? '1' : '0' }}" @disabled(!$vendorId) title="Vendor info">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="vendor-tracking-save-cell vendor-tracking-vendor-col vendor-tracking-vendor-cell" data-col="vendor">
                                        <div class="vendor-tracking-vendor-select-wrap">
                                            <select class="form-select form-select-sm vendor-tracking-inline-select js-vendor-tracking-vendor">
                                                <option value="">--</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" @selected($vendorId === (int) $vendor->id)>{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td class="vendor-tracking-wo-col" data-col="wo">
                                        @if($wo)
                                            <a href="{{ route('mains.show', $wo->id) }}" class="text-info vendor-tracking-wo-link">{{ $woDisplay }}</a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td class="vendor-tracking-customer-col" data-col="customer">{{ $row->customer?->name ?? '--' }}</td>
                                    <td class="vendor-tracking-ipl-col" data-col="ipl">{{ $row->ipl_num ?? '--' }}</td>
                                    <td class="vendor-tracking-part-col" data-col="part_number">{{ $row->part_number ?? '--' }}</td>
                                    <td class="vendor-tracking-serial-col" data-col="serial">{{ $row->serial ?: '--' }}</td>
                                    <td class="vendor-tracking-process-col" data-col="process">{{ $row->process_name ?? '--' }}</td>
                                    <td class="vendor-tracking-save-cell" data-col="sent">
                                        <input
                                            type="text"
                                            data-fp
                                            data-date-url="{{ $row->date_update_url }}"
                                            name="date_start"
                                            class="form-control form-control-sm finish-input js-vendor-tracking-date"
                                            value="{{ optional($sent)->format('Y-m-d') }}"
                                            data-original="{{ optional($sent)->format('Y-m-d') ?? '' }}"
                                        >
                                    </td>
                                    <td class="vendor-tracking-save-cell" data-col="returned">
                                        <input
                                            type="text"
                                            data-fp
                                            data-date-url="{{ $row->date_update_url }}"
                                            name="date_finish"
                                            class="form-control form-control-sm finish-input js-vendor-tracking-date"
                                            value="{{ optional($returned)->format('Y-m-d') }}"
                                            data-original="{{ optional($returned)->format('Y-m-d') ?? '' }}"
                                        >
                                    </td>
                                    <td class="text-center js-vendor-tracking-days" data-col="days">{{ $days ?? '--' }}</td>
                                    <td class="text-center js-vendor-tracking-status" data-col="status">
                                        @if($sent && ! $returned)
                                            <span class="badge text-bg-warning">At vendor</span>
                                        @elseif($returned)
                                            <span class="badge text-bg-success">Returned</span>
                                        @else
                                            <span class="badge text-bg-secondary">Planned</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-muted text-center py-4">No vendor process records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-none" id="vendorTrackingPagination">
                    {{ $rows->links() }}
                </div>

                <div class="vendor-tracking-loadmore {{ $rows->hasMorePages() ? '' : 'is-hidden' }}" id="vendorTrackingLoadMore">
                    Loading more records...
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vendorTrackingSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Table Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="vendorTrackingExcelTitle" class="form-label small text-muted">Excel title</label>
                        <input type="text" id="vendorTrackingExcelTitle" class="form-control form-control-sm" placeholder="Vendor Tracking">
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="small text-muted mb-2">Screen columns</div>
                            <div class="vendor-tracking-column-help mb-2">Drag to change order. Checkbox controls visibility on screen.</div>
                            <div id="vendorTrackingScreenColumns" class="vendor-tracking-column-list"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted mb-2">Excel columns</div>
                            <div class="vendor-tracking-column-help mb-2">Drag to change order. Checkbox controls export columns.</div>
                            <div id="vendorTrackingExcelColumns" class="vendor-tracking-column-list"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light btn-sm" id="vendorTrackingSettingsResetBtn">Reset</button>
                    <button type="button" class="btn btn-primary btn-sm" id="vendorTrackingSettingsSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vendorInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Vendor Info</h5>
                        <div class="small text-muted" id="vendorInfoModalName">Vendor</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="small text-muted">Upload one or many image or PDF files to the vendor media collection.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="file" id="vendorMediaInput" class="d-none" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
                            <button type="button" class="btn btn-outline-info btn-sm" id="vendorMediaUploadBtn">
                                <i class="bi bi-upload me-1"></i> Add files
                            </button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="vendorTrustedInput">
                            <label class="form-check-label" for="vendorTrustedInput">Trust this vendor</label>
                        </div>
                        <span class="badge text-bg-secondary vendor-profile-status" id="vendorTrustStateBadge">Not trusted</span>
                    </div>
                    <label class="form-label small text-muted" for="vendorDescriptionInput">Description</label>
                    <textarea id="vendorDescriptionInput" class="form-control form-control-sm" rows="7" placeholder="Write notes for this vendor..."></textarea>
                    <div id="vendorInfoStatus" class="small text-muted mt-3"></div>
                    <hr class="border-secondary my-3">
                    <div id="vendorMediaList" class="vendor-media-grid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="vendorProfileSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const updateUrl = @json(route('vendor-tracking.row.update'));
            const csrfToken = @json(csrf_token());
            const key = 'vendorTrackingSources';
            const vendorNullKey = 'vendorTrackingIncludeVendorNull';
            const filtersStateKey = 'vendorTrackingFilters';
            const screenColumnsKey = 'vendorTrackingScreenColumns';
            const excelColumnsKey = 'vendorTrackingExcelColumns';
            const screenColumnOrderKey = 'vendorTrackingScreenColumnOrder';
            const excelColumnOrderKey = 'vendorTrackingExcelColumnOrder';
            const excelTitleKey = 'vendorTrackingExcelTitle';
            const defaultScreenColumns = ['repair_order', 'type', 'info', 'vendor', 'wo', 'customer', 'ipl', 'part_number', 'serial', 'process', 'sent', 'returned', 'days', 'status'];
            const defaultExcelColumns = ['repair_order', 'type', 'vendor', 'wo', 'customer', 'ipl', 'part_number', 'serial', 'process', 'sent', 'returned', 'days'];
            const screenColumnDefs = [
                { key: 'repair_order', label: 'RO' },
                { key: 'type', label: 'Type' },
                { key: 'info', label: 'Info' },
                { key: 'vendor', label: 'Vendor' },
                { key: 'wo', label: 'WO' },
                { key: 'customer', label: 'Customer' },
                { key: 'ipl', label: 'IPL' },
                { key: 'part_number', label: 'Part Number' },
                { key: 'serial', label: 'Serial' },
                { key: 'process', label: 'Process' },
                { key: 'sent', label: 'Sent' },
                { key: 'returned', label: 'Returned' },
                { key: 'days', label: 'Days' },
                { key: 'status', label: 'Status' },
            ];
            const excelColumnDefs = screenColumnDefs.filter(def => !['info', 'status'].includes(def.key));
            const form = document.querySelector('.vendor-tracking-page form');
            const boxes = Array.from(document.querySelectorAll('.vendor-source-checkbox'));
            const vendorNullBox = document.getElementById('vendorTrackingIncludeNull');
            const autoSubmitFields = Array.from(form?.querySelectorAll('select[name="vendor_id"], select[name="status"]') || []);
            const textFields = Array.from(form?.querySelectorAll('input[name="workorder"], input[name="part_number"], input[name="repair_order"]') || []);
            const tbody = document.getElementById('vendorTrackingBody');
            const paginationWrap = document.getElementById('vendorTrackingPagination');
            const loadMoreIndicator = document.getElementById('vendorTrackingLoadMore');
            const exportBtn = document.getElementById('vendorTrackingExportBtn');
            const settingsBtn = document.getElementById('vendorTrackingSettingsBtn');
            const settingsModalEl = document.getElementById('vendorTrackingSettingsModal');
            const settingsModal = settingsModalEl ? bootstrap.Modal.getOrCreateInstance(settingsModalEl) : null;
            const settingsSaveBtn = document.getElementById('vendorTrackingSettingsSaveBtn');
            const settingsResetBtn = document.getElementById('vendorTrackingSettingsResetBtn');
            const excelTitleInput = document.getElementById('vendorTrackingExcelTitle');
            const screenColumnsWrap = document.getElementById('vendorTrackingScreenColumns');
            const excelColumnsWrap = document.getElementById('vendorTrackingExcelColumns');
            const vendorShowUrlTemplate = @json(route('vendors.show', ['vendor' => '__VENDOR__']));
            const vendorMetaUrlTemplate = @json(route('vendors.meta.update', ['vendor' => '__VENDOR__']));
            const vendorMediaUploadUrlTemplate = @json(route('vendors.media.upload', ['vendor' => '__VENDOR__']));
            const vendorInfoModalEl = document.getElementById('vendorInfoModal');
            const vendorInfoModal = vendorInfoModalEl ? bootstrap.Modal.getOrCreateInstance(vendorInfoModalEl) : null;
            const vendorInfoModalName = document.getElementById('vendorInfoModalName');
            const vendorMediaList = document.getElementById('vendorMediaList');
            const vendorMediaInput = document.getElementById('vendorMediaInput');
            const vendorMediaUploadBtn = document.getElementById('vendorMediaUploadBtn');
            const vendorTrustedInput = document.getElementById('vendorTrustedInput');
            const vendorDescriptionInput = document.getElementById('vendorDescriptionInput');
            const vendorInfoStatus = document.getElementById('vendorInfoStatus');
            const vendorProfileSaveBtn = document.getElementById('vendorProfileSaveBtn');
            const vendorTrustStateBadge = document.getElementById('vendorTrustStateBadge');

            if (!form || !boxes.length) {
                return;
            }

            function selectedSources() {
                const selected = boxes.filter(box => box.checked).map(box => box.value);
                return selected.length ? selected : boxes.map(box => box.value);
            }

            function collectFilterState() {
                return {
                    vendor_id: form?.querySelector('select[name="vendor_id"]')?.value || '0',
                    status: form?.querySelector('select[name="status"]')?.value || 'all',
                    sources: selectedSources(),
                    include_vendor_null: Boolean(vendorNullBox?.checked),
                    workorder: form?.querySelector('input[name="workorder"]')?.value || '',
                    part_number: form?.querySelector('input[name="part_number"]')?.value || '',
                    repair_order: form?.querySelector('input[name="repair_order"]')?.value || '',
                };
            }

            function persistSources() {
                localStorage.setItem(key, JSON.stringify(selectedSources()));
                if (vendorNullBox) {
                    localStorage.setItem(vendorNullKey, vendorNullBox.checked ? 'true' : 'false');
                }
                localStorage.setItem(filtersStateKey, JSON.stringify(collectFilterState()));
            }

            function sanitizeColumns(selected, allowed, fallback) {
                const values = Array.isArray(selected) ? selected : [];
                const normalized = values.filter(value => allowed.includes(value));
                return normalized.length ? normalized : [...fallback];
            }

            function sanitizeColumnOrder(order, defs, fallback) {
                const allowed = defs.map(def => def.key);
                const values = Array.isArray(order) ? order.filter(value => allowed.includes(value)) : [];
                const base = values.length ? values : [...fallback];

                allowed.forEach(function (keyValue) {
                    if (!base.includes(keyValue)) {
                        base.push(keyValue);
                    }
                });

                return base;
            }

            function getStoredScreenColumns() {
                try {
                    return sanitizeColumns(JSON.parse(localStorage.getItem(screenColumnsKey) || 'null'), screenColumnDefs.map(def => def.key), defaultScreenColumns);
                } catch (error) {
                    return [...defaultScreenColumns];
                }
            }

            function getStoredExcelColumns() {
                try {
                    return sanitizeColumns(JSON.parse(localStorage.getItem(excelColumnsKey) || 'null'), excelColumnDefs.map(def => def.key), defaultExcelColumns);
                } catch (error) {
                    return [...defaultExcelColumns];
                }
            }

            function getStoredScreenColumnOrder() {
                try {
                    return sanitizeColumnOrder(JSON.parse(localStorage.getItem(screenColumnOrderKey) || 'null'), screenColumnDefs, defaultScreenColumns);
                } catch (error) {
                    return sanitizeColumnOrder(null, screenColumnDefs, defaultScreenColumns);
                }
            }

            function getStoredExcelColumnOrder() {
                try {
                    return sanitizeColumnOrder(JSON.parse(localStorage.getItem(excelColumnOrderKey) || 'null'), excelColumnDefs, defaultExcelColumns);
                } catch (error) {
                    return sanitizeColumnOrder(null, excelColumnDefs, defaultExcelColumns);
                }
            }

            function getStoredExcelTitle() {
                return (localStorage.getItem(excelTitleKey) || 'Vendor Tracking').trim() || 'Vendor Tracking';
            }

            function renderColumnOptions(container, defs, values, order, inputName) {
                if (!container) {
                    return;
                }

                const labelByKey = defs.reduce(function (carry, def) {
                    carry[def.key] = def.label;
                    return carry;
                }, {});

                container.innerHTML = order.map(function (keyValue) {
                    return `
                        <label class="vendor-tracking-column-item" draggable="true" data-column-key="${keyValue}">
                            <span class="vendor-tracking-column-drag"><i class="bi bi-grip-vertical"></i></span>
                            <input class="form-check-input" type="checkbox" name="${inputName}" value="${keyValue}" ${values.includes(keyValue) ? 'checked' : ''}>
                            <span class="form-check-label">${labelByKey[keyValue] || keyValue}</span>
                        </label>
                    `;
                }).join('');

                bindColumnDrag(container);
            }

            function bindColumnDrag(container) {
                if (!container || container.dataset.dragReady === '1') {
                    return;
                }

                container.dataset.dragReady = '1';

                container.addEventListener('dragstart', function (event) {
                    const item = event.target.closest('.vendor-tracking-column-item');
                    if (!item) {
                        return;
                    }

                    item.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.dataset.columnKey || '');
                });

                container.addEventListener('dragend', function () {
                    container.querySelectorAll('.vendor-tracking-column-item').forEach(function (item) {
                        item.classList.remove('is-dragging', 'is-drop-target');
                    });
                });

                container.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    const dragging = container.querySelector('.vendor-tracking-column-item.is-dragging');
                    const target = event.target.closest('.vendor-tracking-column-item');
                    if (!dragging || !target || dragging === target) {
                        return;
                    }

                    container.querySelectorAll('.vendor-tracking-column-item').forEach(function (item) {
                        item.classList.remove('is-drop-target');
                    });
                    target.classList.add('is-drop-target');

                    const rect = target.getBoundingClientRect();
                    const shouldInsertBefore = event.clientY < rect.top + rect.height / 2;
                    container.insertBefore(dragging, shouldInsertBefore ? target : target.nextSibling);
                });

                container.addEventListener('drop', function (event) {
                    event.preventDefault();
                    container.querySelectorAll('.vendor-tracking-column-item').forEach(function (item) {
                        item.classList.remove('is-drop-target');
                    });
                });
            }

            function updateEmptyStateColspan() {
                const emptyCell = tbody?.querySelector('td[colspan]');
                if (!emptyCell) {
                    return;
                }

                const visibleCount = document.querySelectorAll('thead [data-col]').length
                    ? Array.from(document.querySelectorAll('thead [data-col]')).filter(cell => cell.style.display !== 'none').length
                    : 14;

                emptyCell.colSpan = Math.max(1, visibleCount);
            }

            function getOrderedSelectedColumns(columns, order) {
                const visible = Array.isArray(columns) ? columns : [];
                return sanitizeColumnOrder(order, screenColumnDefs, defaultScreenColumns).filter(function (keyValue) {
                    return visible.includes(keyValue);
                });
            }

            function reorderTableColumns(order) {
                const normalizedOrder = sanitizeColumnOrder(order, screenColumnDefs, defaultScreenColumns);
                document.querySelectorAll('#vendorTrackingTable tr').forEach(function (row) {
                    const cellsByKey = {};
                    row.querySelectorAll('[data-col]').forEach(function (cell) {
                        cellsByKey[cell.dataset.col] = cell;
                    });

                    normalizedOrder.forEach(function (keyValue) {
                        if (cellsByKey[keyValue]) {
                            row.appendChild(cellsByKey[keyValue]);
                        }
                    });
                });
            }

            function applyScreenColumns(columns, order) {
                reorderTableColumns(order);
                screenColumnDefs.forEach(function (def) {
                    const isVisible = columns.includes(def.key);
                    document.querySelectorAll(`[data-col="${def.key}"]`).forEach(function (cell) {
                        cell.style.display = isVisible ? '' : 'none';
                    });
                });

                updateEmptyStateColspan();
            }

            function openSettingsModal() {
                const screenColumns = getStoredScreenColumns();
                const excelColumns = getStoredExcelColumns();
                const screenOrder = getStoredScreenColumnOrder();
                const excelOrder = getStoredExcelColumnOrder();

                renderColumnOptions(screenColumnsWrap, screenColumnDefs, screenColumns, screenOrder, 'screen_columns');
                renderColumnOptions(excelColumnsWrap, excelColumnDefs, excelColumns, excelOrder, 'excel_columns');
                if (excelTitleInput) {
                    excelTitleInput.value = getStoredExcelTitle();
                }
                settingsModal?.show();
            }

            function readColumnOrder(container, defs, fallback) {
                return sanitizeColumnOrder(
                    Array.from(container?.querySelectorAll('.vendor-tracking-column-item') || []).map(item => item.dataset.columnKey),
                    defs,
                    fallback
                );
            }

            function saveSettings() {
                const screenOrder = readColumnOrder(screenColumnsWrap, screenColumnDefs, defaultScreenColumns);
                const excelOrder = readColumnOrder(excelColumnsWrap, excelColumnDefs, defaultExcelColumns);
                const screenColumns = sanitizeColumns(
                    Array.from(screenColumnsWrap?.querySelectorAll('input:checked') || []).map(input => input.value),
                    screenColumnDefs.map(def => def.key),
                    defaultScreenColumns
                );
                const excelColumns = sanitizeColumns(
                    Array.from(excelColumnsWrap?.querySelectorAll('input:checked') || []).map(input => input.value),
                    excelColumnDefs.map(def => def.key),
                    defaultExcelColumns
                );

                localStorage.setItem(screenColumnsKey, JSON.stringify(screenColumns));
                localStorage.setItem(excelColumnsKey, JSON.stringify(excelColumns));
                localStorage.setItem(screenColumnOrderKey, JSON.stringify(screenOrder));
                localStorage.setItem(excelColumnOrderKey, JSON.stringify(excelOrder));
                localStorage.setItem(excelTitleKey, (excelTitleInput?.value || 'Vendor Tracking').trim() || 'Vendor Tracking');
                applyScreenColumns(screenColumns, screenOrder);
                settingsModal?.hide();
            }

            function resetSettings() {
                localStorage.removeItem(screenColumnsKey);
                localStorage.removeItem(excelColumnsKey);
                localStorage.removeItem(screenColumnOrderKey);
                localStorage.removeItem(excelColumnOrderKey);
                localStorage.removeItem(excelTitleKey);
                openSettingsModal();
                applyScreenColumns(defaultScreenColumns, defaultScreenColumns);
            }

            function buildExportUrl() {
                const url = new URL(exportBtn?.getAttribute('href') || window.location.href, window.location.origin);
                url.searchParams.delete('columns[]');
                url.searchParams.delete('columns');
                url.searchParams.delete('excel_title');

                getOrderedSelectedColumns(getStoredExcelColumns(), getStoredExcelColumnOrder()).forEach(function (column) {
                    url.searchParams.append('columns[]', column);
                });
                url.searchParams.set('excel_title', getStoredExcelTitle());

                return url.toString();
            }

            boxes.forEach(box => {
                box.addEventListener('change', function () {
                    if (!boxes.some(item => item.checked)) {
                        box.checked = true;
                    }

                    persistSources();
                    form.submit();
                });
            });

            form.addEventListener('submit', persistSources);

            autoSubmitFields.forEach(field => {
                field.addEventListener('change', function () {
                    persistSources();
                    form.submit();
                });
            });

            textFields.forEach(field => {
                field.addEventListener('input', persistSources);
                field.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    persistSources();
                    form.requestSubmit();
                });
            });

            vendorNullBox?.addEventListener('change', function () {
                persistSources();
                form.submit();
            });

            persistSources();

            settingsBtn?.addEventListener('click', openSettingsModal);
            settingsSaveBtn?.addEventListener('click', saveSettings);
            settingsResetBtn?.addEventListener('click', resetSettings);
            exportBtn?.addEventListener('click', function (event) {
                event.preventDefault();
                window.location.href = buildExportUrl();
            });

            applyScreenColumns(getStoredScreenColumns(), getStoredScreenColumnOrder());

            if (!tbody || !paginationWrap || !loadMoreIndicator) {
                return;
            }

            const repairOrderTimers = new WeakMap();
            let currentInfoVendorId = null;

            const setCellState = function (cell, state) {
                if (!cell) {
                    return;
                }

                cell.classList.remove('is-saving', 'is-saved', 'is-error');
                if (state) {
                    cell.classList.add(state);
                }
            };

            const markSaved = function (cell) {
                setCellState(cell, 'is-saved');
                window.clearTimeout(cell._vendorTrackingSavedStateTimer);
                cell._vendorTrackingSavedStateTimer = window.setTimeout(function () {
                    cell.classList.remove('is-saved');
                }, 900);
            };

            const flashError = function (cell) {
                setCellState(cell, 'is-error');
                window.setTimeout(function () {
                    cell.classList.remove('is-error');
                }, 1600);
            };

            const buildVendorUrl = function (template, vendorId) {
                return template.replace('__VENDOR__', String(vendorId));
            };

            const setVisibleInvalid = function (input, isInvalid) {
                if (!input) {
                    return;
                }

                const visible = input._flatpickr?.altInput || input;
                visible.classList.toggle('is-invalid', Boolean(isInvalid));
            };

            const refreshFinishInputState = function (input) {
                if (!input) {
                    return;
                }

                const hasValue = String(input.value ?? '').trim() !== '';
                input.classList.toggle('has-finish', hasValue);
                if (input._flatpickr?.altInput) {
                    input._flatpickr.altInput.classList.toggle('has-finish', hasValue);
                }
            };

            const updateRowVendorButtons = function (row, vendorId) {
                row.querySelectorAll('.js-vendor-info-open').forEach(function (button) {
                    const hasVendor = Boolean(vendorId);
                    button.disabled = !hasVendor;
                    button.dataset.vendorId = hasVendor ? String(vendorId) : '';
                });

                if (!vendorId) {
                    row.querySelectorAll('.js-vendor-info-open').forEach(function (button) {
                        setInfoButtonTrusted(button, false);
                    });
                }
            };

            const setInfoButtonTrusted = function (button, isTrusted) {
                if (!button) {
                    return;
                }

                button.classList.toggle('btn-outline-info', !isTrusted);
                button.classList.toggle('btn-outline-success', Boolean(isTrusted));
                button.dataset.trusted = isTrusted ? '1' : '0';
            };

            const applyVendorTrustState = function (vendorId, isTrusted) {
                if (!vendorId) {
                    return;
                }

                tbody.querySelectorAll(`.js-vendor-info-open[data-vendor-id="${vendorId}"]`).forEach(function (button) {
                    setInfoButtonTrusted(button, isTrusted);
                });
            };

            const updateStatusCell = function (row, dateStart, dateFinish) {
                const daysCell = row.querySelector('.js-vendor-tracking-days');
                const statusCell = row.querySelector('.js-vendor-tracking-status');

                if (daysCell) {
                    if (!dateStart) {
                        daysCell.textContent = '--';
                    } else {
                        const start = new Date(`${dateStart}T00:00:00`);
                        const finish = dateFinish ? new Date(`${dateFinish}T00:00:00`) : new Date();
                        const diffMs = finish.getTime() - start.getTime();
                        const diffDays = Number.isNaN(diffMs) ? '--' : Math.max(0, Math.floor(diffMs / 86400000));
                        daysCell.textContent = String(diffDays);
                    }
                }

                if (statusCell) {
                    if (dateStart && !dateFinish) {
                        statusCell.innerHTML = '<span class="badge text-bg-warning">At vendor</span>';
                    } else if (dateFinish) {
                        statusCell.innerHTML = '<span class="badge text-bg-success">Returned</span>';
                    } else {
                        statusCell.innerHTML = '<span class="badge text-bg-secondary">Planned</span>';
                    }
                }
            };

            const setInfoStatus = function (message, isError) {
                if (!vendorInfoStatus) {
                    return;
                }

                vendorInfoStatus.textContent = message || '';
                vendorInfoStatus.classList.toggle('text-danger', Boolean(isError));
                vendorInfoStatus.classList.toggle('text-muted', !isError);
            };

            const setTrustBadgeState = function (isTrusted) {
                if (!vendorTrustStateBadge) {
                    return;
                }

                vendorTrustStateBadge.textContent = isTrusted ? 'Trusted' : 'Not trusted';
                vendorTrustStateBadge.classList.toggle('text-bg-success', Boolean(isTrusted));
                vendorTrustStateBadge.classList.toggle('text-bg-secondary', !isTrusted);
            };

            const bytesToLabel = function (bytes) {
                if (!bytes) {
                    return '0 KB';
                }

                if (bytes >= 1024 * 1024) {
                    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
                }

                return `${Math.max(1, Math.round(bytes / 1024))} KB`;
            };

            const formatMediaDate = function (value) {
                if (!value) {
                    return '';
                }

                const date = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                const day = String(date.getDate()).padStart(2, '0');
                const month = months[date.getMonth()] || '';
                const year = date.getFullYear();

                return `${day}.${month}.${year}`;
            };

            const rememberSavedValues = function (row) {
                row.querySelectorAll('.js-vendor-tracking-vendor').forEach(function (select) {
                    select.dataset.lastSavedValue = select.value;
                });

                row.querySelectorAll('.js-vendor-tracking-repair-order').forEach(function (input) {
                    input.dataset.lastSavedValue = input.value;
                });

                row.querySelectorAll('.js-vendor-tracking-date').forEach(function (input) {
                    input.dataset.lastSavedValue = input.value || '';
                    input.dataset.original = input.value || '';
                    refreshFinishInputState(input);
                });
            };

            const saveDateField = async function (input) {
                if (!input) {
                    return;
                }

                const row = input.closest('tr');
                if (!row) {
                    return;
                }

                const previousValue = input.dataset.lastSavedValue ?? '';
                const nextValue = input.value || '';
                if (nextValue === previousValue) {
                    refreshFinishInputState(input);
                    return;
                }

                const dateUrl = input.dataset.dateUrl;
                const cell = input.closest('td');
                const startInput = row.querySelector('.js-vendor-tracking-date[name="date_start"]');
                const finishInput = row.querySelector('.js-vendor-tracking-date[name="date_finish"]');

                setVisibleInvalid(startInput, false);
                setVisibleInvalid(finishInput, false);
                setCellState(cell, 'is-saving');

                try {
                    const payload = {};
                    payload[input.name] = nextValue;

                    const response = await fetch(dateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw data;
                    }

                    if (startInput) {
                        const startValue = data.date_start ?? '';
                        startInput.value = startValue;
                        startInput.dataset.lastSavedValue = startValue;
                        startInput.dataset.original = startValue;
                        if (startInput._flatpickr) {
                            if (startValue) {
                                startInput._flatpickr.setDate(startValue, false, 'Y-m-d');
                            } else {
                                startInput._flatpickr.clear(false);
                            }
                        }
                        refreshFinishInputState(startInput);
                    }

                    if (finishInput) {
                        const finishValue = data.date_finish ?? '';
                        finishInput.value = finishValue;
                        finishInput.dataset.lastSavedValue = finishValue;
                        finishInput.dataset.original = finishValue;
                        if (finishInput._flatpickr) {
                            if (finishValue) {
                                finishInput._flatpickr.setDate(finishValue, false, 'Y-m-d');
                            } else {
                                finishInput._flatpickr.clear(false);
                            }
                        }
                        refreshFinishInputState(finishInput);
                    }

                    updateStatusCell(row, data.date_start ?? '', data.date_finish ?? '');
                    markSaved(cell);
                } catch (error) {
                    const errors = error?.errors || {};
                    const errorFields = Object.keys(errors);

                    if (errorFields.length) {
                        errorFields.forEach(function (fieldName) {
                            const field = row.querySelector(`.js-vendor-tracking-date[name="${fieldName}"]`);
                            setVisibleInvalid(field, true);
                            if (!field) {
                                return;
                            }

                            const original = field.dataset.lastSavedValue ?? field.dataset.original ?? '';
                            field.value = original;
                            if (field._flatpickr) {
                                if (original) {
                                    field._flatpickr.setDate(original, false, 'Y-m-d');
                                } else {
                                    field._flatpickr.clear(false);
                                }
                            }
                            refreshFinishInputState(field);
                        });
                    } else {
                        setVisibleInvalid(input, true);
                        input.value = previousValue;
                        if (input._flatpickr) {
                            if (previousValue) {
                                input._flatpickr.setDate(previousValue, false, 'Y-m-d');
                            } else {
                                input._flatpickr.clear(false);
                            }
                        }
                        refreshFinishInputState(input);
                    }

                    flashError(cell);
                } finally {
                    cell?.classList.remove('is-saving');
                }
            };

            const initDateInput = function (input) {
                if (!input || input.dataset.fpReady === '1' || typeof flatpickr !== 'function') {
                    refreshFinishInputState(input);
                    return;
                }

                input.dataset.fpReady = '1';
                refreshFinishInputState(input);

                flatpickr(input, {
                    allowInput: true,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd.M.y',
                    clickOpens: true,
                    onReady: function (_, __, instance) {
                        instance.altInput.classList.add('finish-input');
                        refreshFinishInputState(input);

                        if (!instance.altInput.parentElement.querySelector('.fp-cal-btn')) {
                            const wrapper = instance.altInput.parentElement;
                            wrapper.classList.add('fp-alt-wrap');
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'fp-cal-btn';
                            button.innerHTML = '<i class="bi bi-calendar3"></i>';
                            button.addEventListener('click', function () {
                                instance.open();
                            });
                            wrapper.appendChild(button);
                        }
                    },
                    onValueUpdate: function (_, dateStr) {
                        input.value = dateStr || '';
                        refreshFinishInputState(input);
                    },
                    onChange: function (_, dateStr) {
                        input.value = dateStr || '';
                        refreshFinishInputState(input);
                        saveDateField(input);
                    },
                    onClose: function (_, __, instance) {
                        const selected = instance.selectedDates[0];
                        const typed = instance.input.value || (selected ? instance.formatDate(selected, 'Y-m-d') : '');
                        instance.input.value = typed;
                        refreshFinishInputState(instance.input);
                        saveDateField(instance.input);
                    },
                });
            };

            const measureVendorSelectWidth = function (select) {
                const wrap = select.closest('.vendor-tracking-vendor-select-wrap');
                const baseWidth = wrap ? Math.ceil(wrap.getBoundingClientRect().width) : Math.ceil(select.getBoundingClientRect().width);
                const probe = document.createElement('span');
                const style = window.getComputedStyle(select);

                probe.style.position = 'absolute';
                probe.style.visibility = 'hidden';
                probe.style.whiteSpace = 'nowrap';
                probe.style.font = style.font;
                probe.style.fontSize = style.fontSize;
                probe.style.fontFamily = style.fontFamily;
                probe.style.fontWeight = style.fontWeight;
                probe.style.letterSpacing = style.letterSpacing;
                document.body.appendChild(probe);

                let widest = baseWidth;
                Array.from(select.options).forEach(function (option) {
                    probe.textContent = option.textContent || '';
                    widest = Math.max(widest, Math.ceil(probe.getBoundingClientRect().width) + 24);
                });

                probe.remove();

                return widest;
            };

            const expandVendorSelect = function (select) {
                const wrap = select.closest('.vendor-tracking-vendor-select-wrap');
                if (!wrap) {
                    return;
                }

                wrap.style.setProperty('--vendor-select-open-width', `${measureVendorSelectWidth(select)}px`);
                select.classList.add('is-expanded');
            };

            const collapseVendorSelect = function (select) {
                const wrap = select.closest('.vendor-tracking-vendor-select-wrap');
                if (!wrap) {
                    return;
                }

                select.classList.remove('is-expanded');
                wrap.style.removeProperty('--vendor-select-open-width');
            };

            const initRow = function (row) {
                if (!row || row.dataset.vendorTrackingReady === '1') {
                    return;
                }

                row.dataset.vendorTrackingReady = '1';
                rememberSavedValues(row);
                row.querySelectorAll('.js-vendor-tracking-date').forEach(initDateInput);

                const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');
                if (vendorSelect && vendorSelect.dataset.expandReady !== '1') {
                    vendorSelect.dataset.expandReady = '1';
                    vendorSelect.addEventListener('mousedown', function () {
                        expandVendorSelect(vendorSelect);
                    });
                    vendorSelect.addEventListener('focus', function () {
                        expandVendorSelect(vendorSelect);
                    });
                    vendorSelect.addEventListener('blur', function () {
                        collapseVendorSelect(vendorSelect);
                    });
                    vendorSelect.addEventListener('change', function () {
                        collapseVendorSelect(vendorSelect);
                    });
                }
                updateRowVendorButtons(row, vendorSelect && vendorSelect.value !== '' ? Number(vendorSelect.value) : null);
            };

            const saveTrackingRow = async function (row, payload) {
                if (!row) {
                    return;
                }

                const vendorCell = row.querySelector('.js-vendor-tracking-vendor')?.closest('td');
                const repairOrderCell = row.querySelector('.js-vendor-tracking-repair-order')?.closest('td');
                const cells = [vendorCell, repairOrderCell].filter(Boolean);

                cells.forEach(function (cell) {
                    setCellState(cell, 'is-saving');
                });

                try {
                    const response = await fetch(updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            id: Number(row.dataset.rowId),
                            source_key: row.dataset.sourceKey,
                            vendor_id: payload.vendor_id,
                            repair_order: payload.repair_order,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save row');
                    }

                    const data = await response.json();
                    const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');
                    const repairInput = row.querySelector('.js-vendor-tracking-repair-order');
                    if (vendorSelect) {
                        vendorSelect.value = payload.vendor_id ? String(payload.vendor_id) : '';
                    }
                    if (repairInput) {
                        repairInput.value = data.repair_order ?? '';
                    }

                    updateRowVendorButtons(row, payload.vendor_id ? Number(payload.vendor_id) : null);
                    rememberSavedValues(row);
                    cells.forEach(markSaved);
                } catch (error) {
                    const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');
                    const repairInput = row.querySelector('.js-vendor-tracking-repair-order');

                    if (vendorSelect) {
                        vendorSelect.value = vendorSelect.dataset.lastSavedValue ?? '';
                    }
                    if (repairInput) {
                        repairInput.value = repairInput.dataset.lastSavedValue ?? '';
                    }

                    updateRowVendorButtons(row, vendorSelect && vendorSelect.value !== '' ? Number(vendorSelect.value) : null);
                    cells.forEach(flashError);
                } finally {
                    cells.forEach(function (cell) {
                        cell.classList.remove('is-saving');
                    });
                }
            };

            tbody.querySelectorAll('tr').forEach(initRow);

            tbody.addEventListener('change', function (event) {
                const vendorSelect = event.target.closest('.js-vendor-tracking-vendor');
                if (!vendorSelect) {
                    return;
                }

                const row = vendorSelect.closest('tr');
                const vendorId = vendorSelect.value === '' ? null : Number(vendorSelect.value);
                updateRowVendorButtons(row, vendorId);
                saveTrackingRow(row, {
                    vendor_id: vendorId,
                    repair_order: row.querySelector('.js-vendor-tracking-repair-order')?.value ?? '',
                });
            });

            const flushRepairOrderSave = function (input) {
                if (!input) {
                    return;
                }

                const previousValue = input.dataset.lastSavedValue ?? '';
                if (input.value === previousValue) {
                    return;
                }

                const row = input.closest('tr');
                const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');

                saveTrackingRow(row, {
                    vendor_id: vendorSelect && vendorSelect.value !== '' ? Number(vendorSelect.value) : null,
                    repair_order: input.value,
                });
            };

            tbody.addEventListener('input', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input) {
                    return;
                }

                window.clearTimeout(repairOrderTimers.get(input));
                const timer = window.setTimeout(function () {
                    flushRepairOrderSave(input);
                }, 450);
                repairOrderTimers.set(input, timer);
            });

            tbody.addEventListener('blur', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input) {
                    return;
                }

                window.clearTimeout(repairOrderTimers.get(input));
                flushRepairOrderSave(input);
            }, true);

            tbody.addEventListener('keydown', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input || event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(repairOrderTimers.get(input));
                flushRepairOrderSave(input);
                input.blur();
            });

            const loadVendorData = async function (vendorId) {
                const response = await fetch(buildVendorUrl(vendorShowUrlTemplate, vendorId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error('Failed to load vendor');
                }

                return data.vendor;
            };

            const renderMediaItems = function (media) {
                if (!vendorMediaList) {
                    return;
                }

                if (!Array.isArray(media) || !media.length) {
                    vendorMediaList.innerHTML = '<div class="text-muted small">No files uploaded yet.</div>';
                    return;
                }

                vendorMediaList.innerHTML = media.map(function (item) {
                    const preview = item.is_image && item.thumb_url
                        ? `<img src="${item.thumb_url}" alt="${item.file_name}" class="vendor-media-thumb">`
                        : `<div class="vendor-media-thumb d-flex align-items-center justify-content-center bg-secondary-subtle text-dark fw-semibold">${item.mime_type === 'application/pdf' ? 'PDF' : 'FILE'}</div>`;
                    const savedAt = formatMediaDate(item.created_at);

                    return `
                        <div class="vendor-media-item">
                            <a href="${item.view_url}" data-fancybox="vendor-media-${currentInfoVendorId}" data-caption="${item.file_name}" class="vendor-media-link">
                                ${preview}
                            </a>
                            <div class="small fw-semibold text-break">${item.file_name}</div>
                            <div class="small text-muted mb-2">${savedAt || '&nbsp;'}</div>
                            <a href="${item.view_url}" data-fancybox="vendor-media-${currentInfoVendorId}" data-caption="${item.file_name}" class="btn btn-outline-light btn-sm w-100">Open</a>
                        </div>
                    `;
                }).join('');
            };

            const openVendorInfoModal = async function (vendorId) {
                if (!vendorInfoModal || !vendorId) {
                    return;
                }

                currentInfoVendorId = Number(vendorId);
                const triggerButton = tbody.querySelector(`.js-vendor-info-open[data-vendor-id="${currentInfoVendorId}"]`);
                const initialTrusted = triggerButton?.dataset.trusted === '1';

                vendorInfoModal.show();
                if (vendorInfoModalName) {
                    vendorInfoModalName.textContent = 'Loading...';
                }
                if (vendorTrustedInput) {
                    vendorTrustedInput.checked = initialTrusted;
                }
                if (vendorDescriptionInput) {
                    vendorDescriptionInput.value = '';
                }
                setTrustBadgeState(initialTrusted);
                setInfoStatus('Loading vendor info...', false);
                if (vendorMediaList) {
                    vendorMediaList.innerHTML = '';
                }

                try {
                    const vendor = await loadVendorData(currentInfoVendorId);
                    if (vendorInfoModalName) {
                        vendorInfoModalName.textContent = vendor.name;
                    }
                    if (vendorTrustedInput) {
                        vendorTrustedInput.checked = Boolean(vendor.is_trusted);
                    }
                    if (vendorDescriptionInput) {
                        vendorDescriptionInput.value = vendor.description || '';
                    }
                    applyVendorTrustState(currentInfoVendorId, Boolean(vendor.is_trusted));
                    setTrustBadgeState(Boolean(vendor.is_trusted));
                    renderMediaItems(vendor.media || []);
                    setInfoStatus(`${Array.isArray(vendor.media) ? vendor.media.length : 0} file(s) in collection.`, false);
                } catch (error) {
                    setInfoStatus('Failed to load vendor info.', true);
                    if (vendorMediaList) {
                        vendorMediaList.innerHTML = '';
                    }
                }
            };

            tbody.addEventListener('click', function (event) {
                const infoButton = event.target.closest('.js-vendor-info-open');
                if (infoButton) {
                    openVendorInfoModal(infoButton.dataset.vendorId);
                }
            });

            vendorTrustedInput?.addEventListener('change', function () {
                setTrustBadgeState(vendorTrustedInput.checked);
            });

            vendorMediaUploadBtn?.addEventListener('click', function () {
                if (!currentInfoVendorId) {
                    return;
                }

                vendorMediaInput?.click();
            });

            vendorMediaInput?.addEventListener('change', async function () {
                if (!currentInfoVendorId || !vendorMediaInput.files?.length) {
                    return;
                }

                const payload = new FormData();
                Array.from(vendorMediaInput.files).forEach(function (file) {
                    payload.append('files[]', file);
                });
                payload.append('_token', csrfToken);

                setInfoStatus('Uploading files...', false);
                vendorMediaUploadBtn?.setAttribute('disabled', 'disabled');

                try {
                    const response = await fetch(buildVendorUrl(vendorMediaUploadUrlTemplate, currentInfoVendorId), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: payload,
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error('Upload failed');
                    }

                    renderMediaItems(data.media || []);
                    setInfoStatus('Files uploaded successfully.', false);
                } catch (error) {
                    setInfoStatus('Failed to upload files.', true);
                } finally {
                    vendorMediaInput.value = '';
                    vendorMediaUploadBtn?.removeAttribute('disabled');
                }
            });

            vendorProfileSaveBtn?.addEventListener('click', async function () {
                if (!currentInfoVendorId) {
                    return;
                }

                vendorProfileSaveBtn.setAttribute('disabled', 'disabled');
                setInfoStatus('Saving vendor info...', false);

                try {
                    const response = await fetch(buildVendorUrl(vendorMetaUrlTemplate, currentInfoVendorId), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            is_trusted: vendorTrustedInput?.checked ? 1 : 0,
                            description: vendorDescriptionInput?.value ?? '',
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error('Save failed');
                    }

                    const isTrusted = Boolean(data.vendor?.is_trusted);
                    applyVendorTrustState(currentInfoVendorId, isTrusted);
                    setTrustBadgeState(isTrusted);
                    setInfoStatus('Vendor info saved.', false);
                    vendorInfoModal?.hide();
                } catch (error) {
                    setInfoStatus('Failed to save vendor info.', true);
                } finally {
                    vendorProfileSaveBtn.removeAttribute('disabled');
                }
            });

            let nextPageUrl = paginationWrap.querySelector('.pagination .page-item.active + .page-item a, .pagination .page-item:first-child a[rel="next"], .pagination a[rel="next"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination .page-item:last-child a[rel="next"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination a[aria-label="Next »"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination a[rel="next"]')?.getAttribute('href')
                || '';
            let isLoadingNextPage = false;

            const updateNextPageUrl = function (doc) {
                nextPageUrl = doc.querySelector('#vendorTrackingPagination .pagination .page-item.active + .page-item a')?.getAttribute('href')
                    || doc.querySelector('#vendorTrackingPagination .pagination a[rel="next"]')?.getAttribute('href')
                    || '';

                loadMoreIndicator.classList.toggle('is-hidden', !nextPageUrl);
            };

            const appendNextPage = async function () {
                if (!nextPageUrl || isLoadingNextPage) {
                    return;
                }

                isLoadingNextPage = true;
                loadMoreIndicator.textContent = 'Loading more records...';
                loadMoreIndicator.classList.remove('is-hidden');

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load next page');
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newRows = Array.from(doc.querySelectorAll('#vendorTrackingBody tr'));

                    newRows.forEach(function (row) {
                        initRow(row);
                        tbody.appendChild(row);
                    });

                    applyScreenColumns(getStoredScreenColumns(), getStoredScreenColumnOrder());
                    updateNextPageUrl(doc);
                } catch (error) {
                    loadMoreIndicator.textContent = 'Failed to load more records.';
                    return;
                } finally {
                    isLoadingNextPage = false;
                }
            };

            updateNextPageUrl(document);

            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        appendNextPage();
                    }
                });
            }, {
                root: null,
                rootMargin: '250px 0px',
                threshold: 0.01,
            });

            observer.observe(loadMoreIndicator);
        });
    </script>
@endsection
