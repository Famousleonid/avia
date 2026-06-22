@extends('admin.master')

@section('style')
    <style>
        .marketing-page {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #eef1f4;
        }

        html[data-bs-theme="dark"] .marketing-page {
            background: #232525;
        }

        .marketing-toolbar {
            flex: 0 0 auto;
            display: flex;
            align-items: end;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(52, 58, 64, .14);
            background: #fff;
        }

        html[data-bs-theme="dark"] .marketing-toolbar,
        html[data-bs-theme="dark"] .marketing-table-panel,
        html[data-bs-theme="dark"] .marketing-detail {
            background: #2d3030;
            border-color: rgba(255, 255, 255, .12);
        }

        .marketing-title-block {
            min-width: 150px;
            margin-right: 2px;
        }

        .marketing-title {
            margin: 0;
            color: #1f252b;
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: 0;
        }

        .marketing-count {
            margin-top: 2px;
            color: #68717a;
            font-size: .78rem;
            font-weight: 700;
        }

        html[data-bs-theme="dark"] .marketing-title {
            color: #f4f6f8;
        }

        html[data-bs-theme="dark"] .marketing-count,
        html[data-bs-theme="dark"] .marketing-muted {
            color: #aeb6bd;
        }

        .marketing-filter {
            min-width: 150px;
        }

        .marketing-filter--search {
            min-width: 230px;
            flex: 1 1 280px;
        }

        .marketing-filter label {
            margin-bottom: 3px;
            color: #66707a;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        html[data-bs-theme="dark"] .marketing-filter label {
            color: #b9c1c8;
        }

        .marketing-filter.is-active label {
            color: #0d6efd;
        }

        .marketing-filter.is-active .form-control,
        .marketing-filter.is-active .form-select {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .12rem rgba(13, 110, 253, .18);
        }

        html[data-bs-theme="dark"] .marketing-filter.is-active label {
            color: #8bd3f7;
        }

        html[data-bs-theme="dark"] .marketing-filter.is-active .form-control,
        html[data-bs-theme="dark"] .marketing-filter.is-active .form-select {
            border-color: #8bd3f7;
            box-shadow: 0 0 0 .12rem rgba(139, 211, 247, .16);
        }

        .marketing-shell {
            --marketing-left-width: minmax(0, 1fr);
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: var(--marketing-left-width) 14px minmax(390px, 1fr);
            column-gap: 8px;
            padding: 12px;
            overflow: hidden;
        }

        .marketing-shell:not(.is-layout-ready) {
            visibility: hidden;
        }

        .marketing-splitter {
            position: relative;
            align-self: stretch;
            width: 14px;
            min-height: 0;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: transparent;
            cursor: col-resize;
            touch-action: none;
        }

        .marketing-splitter::before,
        .marketing-splitter::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 2px;
            height: min(180px, 42%);
            border-radius: 999px;
            background: rgba(108, 117, 125, .42);
            transform: translateY(-50%);
        }

        .marketing-splitter::before {
            left: 4px;
        }

        .marketing-splitter::after {
            right: 4px;
        }

        .marketing-splitter:hover::before,
        .marketing-splitter:hover::after,
        .marketing-splitter:focus-visible::before,
        .marketing-splitter:focus-visible::after,
        .marketing-shell.is-resizing .marketing-splitter::before,
        .marketing-shell.is-resizing .marketing-splitter::after {
            background: #0d6efd;
        }

        .marketing-shell.is-resizing,
        .marketing-shell.is-resizing * {
            user-select: none;
        }

        html[data-bs-theme="dark"] .marketing-splitter::before,
        html[data-bs-theme="dark"] .marketing-splitter::after {
            background: rgba(222, 226, 230, .42);
        }

        html[data-bs-theme="dark"] .marketing-splitter:hover::before,
        html[data-bs-theme="dark"] .marketing-splitter:hover::after,
        html[data-bs-theme="dark"] .marketing-splitter:focus-visible::before,
        html[data-bs-theme="dark"] .marketing-splitter:focus-visible::after,
        html[data-bs-theme="dark"] .marketing-shell.is-resizing .marketing-splitter::before,
        html[data-bs-theme="dark"] .marketing-shell.is-resizing .marketing-splitter::after {
            background: #8bd3f7;
        }

        .marketing-table-panel,
        .marketing-detail {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(52, 58, 64, .14);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .marketing-table-head,
        .marketing-detail-head {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(52, 58, 64, .12);
        }

        html[data-bs-theme="dark"] .marketing-table-head,
        html[data-bs-theme="dark"] .marketing-detail-head,
        html[data-bs-theme="dark"] .marketing-detail-tabs {
            border-color: rgba(255, 255, 255, .10);
        }

        .marketing-table-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .marketing-table {
            min-width: 1230px;
            margin: 0;
        }

        .marketing-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            height: 38px;
            border-bottom: 1px solid rgba(52, 58, 64, .14);
            background: #f8f9fa;
            color: #4a535c;
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
            white-space: nowrap;
        }

        html[data-bs-theme="dark"] .marketing-table th {
            background: #242728;
            color: #c7cdd3;
            border-color: rgba(255, 255, 255, .12);
        }

        .marketing-table td {
            max-width: 240px;
            height: 48px;
            color: #27313a;
            font-size: .86rem;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        html[data-bs-theme="dark"] .marketing-table td {
            color: #d8dee3;
        }

        .marketing-table tbody tr {
            cursor: pointer;
        }

        .marketing-table tbody tr.is-active td {
            background: rgba(13, 110, 253, .10);
        }

        .marketing-name {
            color: #0b5ed7;
            font-weight: 700;
        }

        html[data-bs-theme="dark"] .marketing-name {
            color: #7ad7ea;
        }

        .marketing-chip-list {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
            overflow: hidden;
        }

        .marketing-chip {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            max-width: 94px;
            height: 22px;
            padding: 0 7px;
            border: 1px solid rgba(13, 110, 253, .22);
            border-radius: 999px;
            color: #0b5ed7;
            background: rgba(13, 110, 253, .07);
            font-size: .74rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        html[data-bs-theme="dark"] .marketing-chip {
            color: #8bd3f7;
            background: rgba(13, 202, 240, .10);
            border-color: rgba(13, 202, 240, .22);
        }

        .marketing-badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 800;
        }

        .marketing-badge--existing { background: rgba(25, 135, 84, .13); color: #198754; }
        .marketing-badge--potential { background: rgba(13, 110, 253, .13); color: #0d6efd; }
        .marketing-badge--inactive { background: rgba(108, 117, 125, .16); color: #6c757d; }
        .marketing-badge--due { background: rgba(220, 53, 69, .14); color: #dc3545; }
        .marketing-badge--upcoming { background: rgba(255, 193, 7, .18); color: #8a6500; }
        .marketing-badge--none { background: rgba(108, 117, 125, .14); color: #6c757d; }

        .marketing-detail {
            position: relative;
        }

        .marketing-detail-title {
            min-width: 0;
        }

        .marketing-detail-title h2 {
            margin: 0;
            min-height: 1.25rem;
            color: var(--bs-info);
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        html[data-bs-theme="dark"] .marketing-detail-title h2 {
            color: #7ad7ea;
        }

        .marketing-detail-title h2.is-loading {
            color: #6c757d;
        }

        html[data-bs-theme="dark"] .marketing-detail-title h2.is-loading {
            color: #8b949e;
        }

        .marketing-detail-title div {
            min-height: 1rem;
            color: #68717a;
            font-size: .78rem;
            line-height: 1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .marketing-detail-tabs {
            flex: 0 0 auto;
            display: flex;
            gap: 4px;
            padding: 8px 10px;
            border-bottom: 1px solid rgba(52, 58, 64, .12);
            overflow-x: auto;
        }

        .marketing-tab {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 10px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: #4d5963;
            font-size: .82rem;
            font-weight: 800;
        }

        .marketing-tab.is-active {
            border-color: rgba(13, 110, 253, .22);
            color: #0d6efd;
            background: rgba(13, 110, 253, .08);
        }

        html[data-bs-theme="dark"] .marketing-tab {
            color: #c5ccd2;
        }

        html[data-bs-theme="dark"] .marketing-tab.is-active {
            color: #8bd3f7;
            background: rgba(13, 202, 240, .10);
            border-color: rgba(13, 202, 240, .22);
        }

        .marketing-detail-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 12px;
        }

        .marketing-pane {
            display: none;
        }

        .marketing-pane.is-active {
            display: block;
        }

        .marketing-section {
            margin-bottom: 14px;
        }

        .marketing-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 0 0 8px;
            color: #27313a;
            font-size: .86rem;
            font-weight: 900;
            letter-spacing: 0;
        }

        html[data-bs-theme="dark"] .marketing-section-title {
            color: #eef2f5;
        }

        .marketing-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;
        }

        .marketing-form-grid .span-2 {
            grid-column: 1 / -1;
        }

        .marketing-field label {
            display: block;
            margin-bottom: 3px;
            color: #68717a;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        html[data-bs-theme="dark"] .marketing-field label {
            color: #b9c1c8;
        }

        .marketing-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }

        .marketing-save-button {
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .marketing-save-button.is-dirty {
            color: #1f252b !important;
            background: #ffc107 !important;
            border-color: #ffc107 !important;
            box-shadow: 0 0 0 .12rem rgba(255, 193, 7, .25);
        }

        .marketing-save-button.is-saving {
            pointer-events: none;
            opacity: .82;
        }

        .marketing-save-button [data-save-icon] {
            width: 1em;
            height: 1em;
        }

        .marketing-empty {
            padding: 28px 16px;
            color: #68717a;
            text-align: center;
        }

        .marketing-note {
            padding: 10px 0;
            border-bottom: 1px solid rgba(52, 58, 64, .12);
        }

        html[data-bs-theme="dark"] .marketing-note {
            border-color: rgba(255, 255, 255, .10);
        }

        .marketing-note:last-child {
            border-bottom: 0;
        }

        .marketing-note-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
            color: #68717a;
            font-size: .76rem;
            font-weight: 700;
        }

        .marketing-note-text {
            color: #27313a;
            font-size: .86rem;
            line-height: 1.35;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        html[data-bs-theme="dark"] .marketing-note-text {
            color: #e7ecef;
        }

        .marketing-load {
            flex: 0 0 auto;
            padding: 10px;
            border-top: 1px solid rgba(52, 58, 64, .12);
            text-align: center;
        }

        html[data-bs-theme="dark"] .marketing-load {
            border-color: rgba(255, 255, 255, .10);
        }

        .marketing-contact-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid rgba(52, 58, 64, .12);
        }

        html[data-bs-theme="dark"] .marketing-contact-row {
            border-color: rgba(255, 255, 255, .10);
        }

        .marketing-contact-primary-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            align-self: center;
            min-width: 0;
        }

        .marketing-contact-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
        }

        .marketing-aircraft-select {
            min-height: 178px;
        }

        #aiAssistantWidget,
        .ai-widget {
            display: none !important;
        }

        .marketing-workorders-wrap {
            max-height: min(62vh, 620px);
            overflow: auto;
        }

        .marketing-workorders-table {
            min-width: 980px;
            margin: 0;
        }

        .marketing-workorders-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .marketing-workorders-table td {
            font-size: .82rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .marketing-loading-dots {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-left: 3px;
            vertical-align: middle;
        }

        .marketing-loading-dots span {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            animation: marketingLoadingDot 1s infinite ease-in-out;
        }

        .marketing-loading-dots span:nth-child(2) {
            animation-delay: .15s;
        }

        .marketing-loading-dots span:nth-child(3) {
            animation-delay: .3s;
        }

        @keyframes marketingLoadingDot {
            0%, 80%, 100% { transform: translateY(0); opacity: .35; }
            40% { transform: translateY(-4px); opacity: 1; }
        }

        .marketing-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .marketing-media-group + .marketing-media-group {
            margin-top: 18px;
        }

        .marketing-media-group-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 10px;
            color: #68717a;
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .marketing-media-group-title::after {
            content: "";
            flex: 1 1 auto;
            min-width: 24px;
            border-top: 1px solid rgba(52, 58, 64, .22);
        }

        html[data-bs-theme="dark"] .marketing-media-group-title {
            color: #aeb6bd;
        }

        html[data-bs-theme="dark"] .marketing-media-group-title::after {
            border-color: rgba(255, 255, 255, .16);
        }

        .marketing-media-thumb {
            display: block;
            border: 1px solid rgba(52, 58, 64, .14);
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .marketing-media-thumb img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
        }

        .marketing-media-pdf-layout {
            display: grid;
            grid-template-columns: minmax(180px, 260px) minmax(0, 1fr);
            gap: 12px;
            min-height: 70vh;
        }

        .marketing-media-pdf-list {
            min-height: 0;
            overflow: auto;
        }

        .marketing-media-pdf-frame {
            width: 100%;
            height: 70vh;
            border: 1px solid rgba(52, 58, 64, .16);
            border-radius: 8px;
            background: #fff;
        }

        @media (max-width: 767.98px) {
            .marketing-media-pdf-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1399.98px) {
            .marketing-toolbar {
                flex-wrap: wrap;
                align-items: end;
            }

            .marketing-title-block {
                width: 100%;
            }
        }

        @media (max-width: 991.98px) {
            .marketing-shell {
                grid-template-columns: 1fr;
                column-gap: 0;
            }

            .marketing-splitter {
                display: none;
            }

            .marketing-detail {
                position: fixed;
                inset: 56px 8px 8px 8px;
                z-index: 2050;
                display: none;
                box-shadow: 0 18px 48px rgba(0, 0, 0, .32);
            }

            .marketing-detail.is-open {
                display: flex;
            }

            .marketing-filter,
            .marketing-filter--search {
                flex: 1 1 180px;
                min-width: 170px;
            }
        }

        @media (max-width: 575.98px) {
            .marketing-shell {
                padding: 8px;
            }

            .marketing-toolbar {
                padding: 10px;
            }

            .marketing-form-grid,
            .marketing-contact-row {
                grid-template-columns: 1fr;
            }

            .marketing-contact-actions {
                grid-row: auto;
            }

            .marketing-contact-primary-actions {
                grid-column: auto;
            }
        }
    </style>
@endsection

@section('content')
    <div class="marketing-page" data-marketing-page>
        <div class="marketing-toolbar">
            <div class="marketing-title-block">
                <h1 class="marketing-title">Marketing</h1>
                <div class="marketing-count" id="marketingResultCount">0 companies</div>
            </div>

            <div class="marketing-filter marketing-filter--search">
                <label for="marketingSearch">Search</label>
                <input id="marketingSearch" class="form-control form-control-sm" type="search" autocomplete="off" placeholder="Company, contact, country, A/C">
            </div>

            <div class="marketing-filter">
                <label for="marketingLifecycle">Status</label>
                <select id="marketingLifecycle" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($lifecycleOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="marketing-filter">
                <label for="marketingCompanyType">Type</label>
                <select id="marketingCompanyType" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($companyTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="marketing-filter">
                <label for="marketingSegment">Segment</label>
                <select id="marketingSegment" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($segments as $segment)
                        <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="marketing-filter">
                <label for="marketingAircraft">A/C Type</label>
                <select id="marketingAircraft" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($planes as $plane)
                        <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="marketing-filter">
                <label for="marketingFollowUp">Follow-up</label>
                <select id="marketingFollowUp" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="due">Due</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="none">None</option>
                </select>
            </div>

            <div class="d-flex align-items-end gap-2">
                <button id="marketingResetFilters" class="btn btn-sm btn-outline-secondary" type="button" title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#marketingCreateModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Company</span>
                </button>
            </div>
        </div>

        <div class="marketing-shell" id="marketingShell">
            <section class="marketing-table-panel">
                <div class="marketing-table-head">
                    <div class="fw-bold">Companies</div>
                    <div class="marketing-muted small" id="marketingLoadState"></div>
                </div>

                <div class="marketing-table-scroll" id="marketingTableScroll">
                    <table class="table table-sm table-hover align-middle marketing-table">
                        <thead>
                        <tr>
                            <th style="width: 220px;">Company</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 120px;">Country</th>
                            <th style="width: 150px;">Type</th>
                            <th style="width: 150px;">Segment</th>
                            <th style="width: 260px;">A/C Type</th>
                            <th style="width: 120px;">Contacts</th>
                            <th style="width: 120px;">WO</th>
                            <th style="width: 150px;">Follow-up</th>
                        </tr>
                        </thead>
                        <tbody id="marketingRows">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Loading<span class="marketing-loading-dots"><span></span><span></span><span></span></span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="marketing-load">
                    <div id="marketingLoadMore" class="marketing-muted small" hidden></div>
                </div>
            </section>

            <button class="marketing-splitter"
                    id="marketingSplitter"
                    type="button"
                    aria-label="Resize marketing panels"
                    aria-orientation="vertical"></button>

            <aside class="marketing-detail" id="marketingDetail">
                <div class="marketing-detail-head">
                    <div class="marketing-detail-title">
                        <h2 id="detailTitle">Select company</h2>
                        <div id="detailMeta">Marketing profile</div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="detailClose" type="button" title="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="marketing-detail-tabs">
                    <button class="marketing-tab is-active" type="button" data-tab="overview"><i class="bi bi-building"></i>Overview</button>
                    <button class="marketing-tab" type="button" data-tab="contacts"><i class="bi bi-person-lines-fill"></i>Contacts</button>
                    <button class="marketing-tab" type="button" data-tab="notes"><i class="bi bi-journal-text"></i>Notes</button>
                    <button class="marketing-tab" type="button" data-tab="workorders"><i class="bi bi-wrench-adjustable"></i>WO</button>
                </div>

                <div class="marketing-detail-body">
                    <div class="marketing-pane is-active" data-pane="overview">
                        <form id="marketingProfileForm" data-no-spinner autocomplete="off">
                            <div class="marketing-section">
                                <h3 class="marketing-section-title">Company</h3>
                                <div class="marketing-form-grid">
                                    <div class="marketing-field span-2">
                                        <label for="detailName">Name</label>
                                        <input id="detailName" name="name" class="form-control form-control-sm" type="text" maxlength="250" autocomplete="off">
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailLifecycle">Status</label>
                                        <select id="detailLifecycle" name="lifecycle_status" class="form-select form-select-sm" autocomplete="off">
                                            @foreach($lifecycleOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailSegment">Segment</label>
                                        <select id="detailSegment" name="segment_id" class="form-select form-select-sm" autocomplete="off">
                                            <option value=""></option>
                                            @foreach($segments as $segment)
                                                <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailCompanyType">Type</label>
                                        <select id="detailCompanyType" name="company_type_id" class="form-select form-select-sm" autocomplete="off">
                                            <option value=""></option>
                                            @foreach($companyTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailCountry">Country</label>
                                        <input id="detailCountry" name="country" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailAddress">Address</label>
                                        <textarea id="detailAddress" name="address" class="form-control form-control-sm" rows="2" autocomplete="off"></textarea>
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailTerms">Terms</label>
                                        <input id="detailTerms" name="terms_label" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailAircraft">A/C Type</label>
                                        <select id="detailAircraft" name="aircraft_ids[]" class="form-select form-select-sm marketing-aircraft-select" multiple size="8" autocomplete="off">
                                            @foreach($planes as $plane)
                                                <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="marketing-actions">
                                    <button class="btn btn-sm btn-primary marketing-save-button" type="submit" data-save-button title="Save">
                                        <i class="bi bi-check-lg" data-save-icon></i>
                                        <span>Save</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="marketing-pane" data-pane="contacts">
                        <form id="marketingContactForm" class="marketing-section" data-no-spinner autocomplete="off">
                            <h3 class="marketing-section-title">New Contact</h3>
                            <div class="marketing-form-grid">
                                <div class="marketing-field">
                                    <label>First Name</label>
                                    <input name="first_name" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Last Name</label>
                                    <input name="last_name" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Position</label>
                                    <input name="position" class="form-control form-control-sm" type="text" maxlength="160" autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Email</label>
                                    <input name="email" class="form-control form-control-sm" type="email" maxlength="190" autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Phone</label>
                                    <input name="phone" class="form-control form-control-sm" type="text" maxlength="80" autocomplete="off">
                                </div>
                                <label class="d-flex align-items-center gap-2 mt-4 small fw-bold">
                                    <input name="is_primary" class="form-check-input mt-0" type="checkbox" value="1">
                                    Primary
                                </label>
                            </div>
                            <div class="marketing-actions">
                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Add</span>
                                </button>
                            </div>
                        </form>

                        <div class="marketing-section">
                            <h3 class="marketing-section-title">Contacts</h3>
                            <div id="marketingContactsList"></div>
                        </div>
                    </div>

                    <div class="marketing-pane" data-pane="notes">
                        <form id="marketingNoteForm" class="marketing-section" data-no-spinner autocomplete="off">
                            <h3 class="marketing-section-title">New Note</h3>
                            <div class="marketing-form-grid">
                                <div class="marketing-field">
                                    <label>Contact</label>
                                    <select name="contact_id" class="form-select form-select-sm" id="noteContact" autocomplete="off"></select>
                                </div>
                                <div class="marketing-field">
                                    <label>Date</label>
                                    <input name="interaction_at" class="form-control form-control-sm" type="text" maxlength="11" placeholder=".... /.... /......" data-project-date data-project-date-capital autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Status</label>
                                    <select name="follow_up_status" class="form-select form-select-sm" autocomplete="off">
                                        <option value="open">Open</option>
                                        <option value="done">Done</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="marketing-field">
                                    <label>Follow-up</label>
                                    <input name="follow_up_at" class="form-control form-control-sm" type="text" maxlength="11" placeholder=".... /.... /......" data-project-date data-project-date-capital autocomplete="off">
                                </div>
                                <div class="marketing-field span-2">
                                    <label>Notes</label>
                                    <textarea name="note" class="form-control form-control-sm" rows="4" required autocomplete="off"></textarea>
                                </div>
                            </div>
                            <div class="marketing-actions">
                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Add</span>
                                </button>
                            </div>
                        </form>

                        <div class="marketing-section">
                            <h3 class="marketing-section-title">Timeline</h3>
                            <div id="marketingNotesList"></div>
                        </div>
                    </div>

                    <div class="marketing-pane" data-pane="workorders">
                        <div class="marketing-section">
                            <h3 class="marketing-section-title">Workorders</h3>
                            <div class="marketing-workorders-wrap" id="marketingWorkordersScroll">
                                <table class="table table-sm table-bordered marketing-workorders-table">
                                    <thead>
                                    <tr>
                                        <th>WO #</th>
                                        <th>Status</th>
                                        <th>RO#</th>
                                        <th>Part Number</th>
                                        <th>Description</th>
                                        <th>Serial Number</th>
                                        <th>Task</th>
                                        <th>Terms</th>
                                        <th>WO Estimate</th>
                                        <th>WO Estimate Date</th>
                                        <th>Approval Date</th>
                                        <th>Files</th>
                                    </tr>
                                    </thead>
                                    <tbody id="marketingWorkordersRows">
                                    <tr><td colspan="12" class="text-center text-muted py-4">Select company</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="marketing-actions">
                                <div id="marketingWorkordersMore" class="marketing-muted small" hidden></div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div class="modal fade" id="marketingCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="marketingCreateForm" data-no-spinner autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Add Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="marketing-form-grid">
                        <div class="marketing-field span-2">
                            <label>Name</label>
                            <input name="name" class="form-control form-control-sm" type="text" required maxlength="250" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>Status</label>
                            <select name="lifecycle_status" class="form-select form-select-sm" autocomplete="off">
                                @foreach($lifecycleOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="marketing-field">
                            <label>Country</label>
                            <input name="country" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>Type</label>
                            <select name="company_type_id" class="form-select form-select-sm" autocomplete="off">
                                <option value=""></option>
                                @foreach($companyTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="marketing-field">
                            <label>Segment</label>
                            <select name="segment_id" class="form-select form-select-sm" autocomplete="off">
                                <option value=""></option>
                                @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="marketing-field span-2">
                            <label>Address</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2" autocomplete="off"></textarea>
                        </div>
                        <div class="marketing-field span-2">
                            <label>A/C Type</label>
                            <select name="aircraft_ids[]" class="form-select form-select-sm marketing-aircraft-select" multiple size="8" autocomplete="off">
                                @foreach($planes as $plane)
                                    <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg"></i>
                        <span>Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="marketingMediaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="marketingMediaTitle">Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="marketingMediaBody">
                    <div class="text-center text-muted py-4">Loading<span class="marketing-loading-dots"><span></span><span></span><span></span></span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const routes = {
                customers: @json(route('marketing.customers.index')),
                storeCustomer: @json(route('marketing.customers.store')),
                showCustomer: @json(route('marketing.customers.show', ['customer' => '__ID__'])),
                updateProfile: @json(route('marketing.customers.profile.update', ['customer' => '__ID__'])),
                workorders: @json(route('marketing.customers.workorders', ['customer' => '__ID__'])),
                storeContact: @json(route('marketing.contacts.store', ['customer' => '__ID__'])),
                updateContact: @json(route('marketing.contacts.update', ['contact' => '__ID__'])),
                destroyContact: @json(route('marketing.contacts.destroy', ['contact' => '__ID__'])),
                storeNote: @json(route('marketing.notes.store', ['customer' => '__ID__'])),
                updateNote: @json(route('marketing.notes.update', ['note' => '__ID__'])),
                destroyNote: @json(route('marketing.notes.destroy', ['note' => '__ID__'])),
            };

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const scope = 'marketing.index';
            const filtersKey = 'filters';
            const layoutKey = 'layout';
            const tabKey = 'active_tab';
            const allowedTabs = ['overview', 'contacts', 'notes', 'workorders'];

            const shell = document.getElementById('marketingShell');
            const splitter = document.getElementById('marketingSplitter');
            const rowsEl = document.getElementById('marketingRows');
            const tableScroll = document.getElementById('marketingTableScroll');
            const loadMoreBtn = document.getElementById('marketingLoadMore');
            const loadState = document.getElementById('marketingLoadState');
            const resultCount = document.getElementById('marketingResultCount');
            const detail = document.getElementById('marketingDetail');
            const detailTitle = document.getElementById('detailTitle');
            const detailMeta = document.getElementById('detailMeta');
            const profileForm = document.getElementById('marketingProfileForm');
            const contactForm = document.getElementById('marketingContactForm');
            const noteForm = document.getElementById('marketingNoteForm');
            const createForm = document.getElementById('marketingCreateForm');
            const contactsList = document.getElementById('marketingContactsList');
            const notesList = document.getElementById('marketingNotesList');
            const noteContact = document.getElementById('noteContact');
            const workordersRows = document.getElementById('marketingWorkordersRows');
            const workordersScroll = document.getElementById('marketingWorkordersScroll');
            const workordersMore = document.getElementById('marketingWorkordersMore');
            const mediaModalEl = document.getElementById('marketingMediaModal');
            const mediaTitle = document.getElementById('marketingMediaTitle');
            const mediaBody = document.getElementById('marketingMediaBody');

            const filterEls = {
                q: document.getElementById('marketingSearch'),
                lifecycle_status: document.getElementById('marketingLifecycle'),
                company_type_id: document.getElementById('marketingCompanyType'),
                segment_id: document.getElementById('marketingSegment'),
                plane_id: document.getElementById('marketingAircraft'),
                follow_up: document.getElementById('marketingFollowUp'),
            };

            let state = {
                page: 1,
                hasMore: false,
                loading: false,
                rows: [],
                selectedId: null,
                selectedCustomer: null,
                activeTab: 'overview',
                workordersPage: 1,
                workordersHasMore: false,
                workordersLoaded: false,
                workordersLoading: false,
            };

            const loadingDotsHtml = '<span class="marketing-loading-dots"><span></span><span></span><span></span></span>';

            function loadingHtml(label = 'Loading') {
                return `${escapeHtml(label)}${loadingDotsHtml}`;
            }

            function setDetailTitleLoading() {
                detailTitle.classList.add('is-loading');
                detailTitle.innerHTML = loadingHtml('Loading');
            }

            function setDetailTitleText(value) {
                detailTitle.classList.remove('is-loading');
                detailTitle.textContent = value || 'Select company';
            }

            function urlFor(template, id) {
                return template.replace('__ID__', encodeURIComponent(String(id)));
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function slugForAttribute(value) {
                return String(value ?? '')
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-z0-9_-]+/g, '-')
                    .replace(/^-+|-+$/g, '') || 'group';
            }

            function notify(message, type = 'success') {
                if (type === 'error' && typeof window.notifyError === 'function') return window.notifyError(message);
                if (type === 'success' && typeof window.notifySuccess === 'function') return window.notifySuccess(message);
                if (typeof window.showNotification === 'function') return window.showNotification(message, type);
                console[type === 'error' ? 'error' : 'log'](message);
            }

            function disableAutocomplete(root = document) {
                root.querySelectorAll('form, input, select, textarea').forEach((el) => {
                    el.setAttribute('autocomplete', 'off');
                });

                root.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea').forEach((el) => {
                    el.setAttribute('autocomplete', 'new-password');
                    el.setAttribute('autocorrect', 'off');
                    el.setAttribute('autocapitalize', 'off');
                    el.setAttribute('spellcheck', 'false');

                    if (!el.matches('[readonly]') && !el.matches('[type="date"], [type="search"], [data-project-date]')) {
                        el.setAttribute('readonly', 'readonly');
                        el.dataset.autofillReadonly = '1';
                    }
                });
            }

            function unlockAutofillBlockedField(target) {
                const el = target?.closest?.('input[data-autofill-readonly="1"], textarea[data-autofill-readonly="1"]');
                if (!el) return;

                el.removeAttribute('readonly');
                delete el.dataset.autofillReadonly;
            }

            function isMarketingAutofillScope(target) {
                return !!target?.closest?.('[data-marketing-page], #marketingCreateModal');
            }

            async function requestJson(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(options.headers || {}),
                    },
                    spinner: false,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message || Object.values(data.errors || {}).flat().join(' ') || `HTTP ${response.status}`;
                    throw new Error(message);
                }

                return data;
            }

            function formDataObject(form) {
                const fd = new FormData(form);
                const data = {};

                fd.forEach((value, key) => {
                    if (key.endsWith('[]')) {
                        const cleanKey = key.slice(0, -2);
                        data[cleanKey] = data[cleanKey] || [];
                        if (String(value) !== '') data[cleanKey].push(value);
                        return;
                    }

                    data[key] = value === '' ? null : value;
                });

                form.querySelectorAll('input[type="checkbox"][name]').forEach((input) => {
                    data[input.name] = input.checked ? 1 : 0;
                });

                form.querySelectorAll('select[multiple][name]').forEach((select) => {
                    const key = select.name.replace(/\[\]$/, '');
                    data[key] = Array.from(select.selectedOptions).map((option) => option.value);
                });

                return data;
            }

            function formStateSignature(form) {
                return JSON.stringify(formDataObject(form));
            }

            function setSaveButtonDirty(form, dirty) {
                const button = form?.querySelector?.('[data-save-button]');
                if (!button) return;
                if (button.classList.contains('is-saving')) return;

                button.classList.toggle('is-dirty', dirty);
                button.title = dirty ? 'Unsaved changes' : 'Save';
                const icon = button.querySelector('[data-save-icon]');
                if (icon) icon.className = dirty ? 'bi bi-exclamation-lg' : 'bi bi-check-lg';
            }

            function setSaveButtonSaving(form, saving) {
                const button = form?.querySelector?.('[data-save-button]');
                if (!button) return;

                button.classList.toggle('is-saving', saving);
                button.disabled = saving;
                button.setAttribute('aria-busy', saving ? 'true' : 'false');

                const icon = button.querySelector('[data-save-icon]');
                if (icon && saving) {
                    icon.className = 'spinner-border spinner-border-sm';
                }

                if (!saving) refreshFormDirtyState(form);
            }

            function markFormClean(form) {
                if (!form?.querySelector?.('[data-save-button]')) return;

                form.dataset.cleanState = formStateSignature(form);
                setSaveButtonDirty(form, false);
            }

            function refreshFormDirtyState(form) {
                if (!form?.querySelector?.('[data-save-button]')) return;

                const cleanState = form.dataset.cleanState;
                setSaveButtonDirty(form, !!cleanState && formStateSignature(form) !== cleanState);
            }

            function handleDirtyFieldChange(event) {
                const form = event.target.closest('#marketingProfileForm, .js-contact-row');
                if (form) refreshFormDirtyState(form);
            }

            function currentFilters() {
                return Object.fromEntries(Object.entries(filterEls).map(([key, el]) => [key, el.value || '']).filter(([, value]) => value !== ''));
            }

            function setFilters(filters) {
                Object.entries(filterEls).forEach(([key, el]) => {
                    el.value = filters?.[key] || '';
                });
                updateFilterStates();
            }

            function updateFilterStates() {
                Object.values(filterEls).forEach((el) => {
                    el.closest('.marketing-filter')?.classList.toggle('is-active', String(el.value || '') !== '');
                });
            }

            function clampPanelWidth(width) {
                if (!shell) return null;

                const shellWidth = shell.getBoundingClientRect().width;
                if (!Number.isFinite(shellWidth) || shellWidth < 700) return null;

                const minLeft = 300;
                const minRight = 390;
                const handleWidth = splitter?.getBoundingClientRect().width || 14;
                const gapWidth = 16;
                const maxLeft = shellWidth - minRight - handleWidth - gapWidth;

                if (maxLeft <= minLeft) return null;

                return Math.min(Math.max(Math.round(width), minLeft), Math.round(maxLeft));
            }

            function setPanelWidth(width, persist = false) {
                const clamped = clampPanelWidth(width);
                if (!clamped) return;

                shell.style.setProperty('--marketing-left-width', `${clamped}px`);

                if (persist) {
                    const persistPromise = window.UserUiSettings?.set(scope, layoutKey, { leftWidth: clamped });
                    persistPromise?.catch(() => {});
                }
            }

            async function restorePanelLayout() {
                try {
                    const saved = await window.UserUiSettings?.get(scope, layoutKey, {});
                    if (saved?.leftWidth) setPanelWidth(Number(saved.leftWidth), false);
                } catch (_) {}
            }

            async function restoreActiveTab() {
                try {
                    const saved = await window.UserUiSettings?.get(scope, tabKey, 'overview');
                    switchTab(saved || 'overview', false);
                } catch (_) {
                    switchTab('overview', false);
                }
            }

            function startResize(event) {
                if (!shell || !splitter || window.matchMedia('(max-width: 991.98px)').matches) return;

                event.preventDefault();
                splitter.setPointerCapture?.(event.pointerId);
                shell.classList.add('is-resizing');

                const shellRect = shell.getBoundingClientRect();

                function onMove(moveEvent) {
                    setPanelWidth(moveEvent.clientX - shellRect.left, false);
                }

                function onUp(upEvent) {
                    splitter.releasePointerCapture?.(upEvent.pointerId);
                    shell.classList.remove('is-resizing');
                    window.removeEventListener('pointermove', onMove);
                    window.removeEventListener('pointerup', onUp);

                    const leftPanel = shell.querySelector('.marketing-table-panel');
                    if (leftPanel) setPanelWidth(leftPanel.getBoundingClientRect().width, true);
                }

                window.addEventListener('pointermove', onMove);
                window.addEventListener('pointerup', onUp);
            }

            function resizeWithKeyboard(event) {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();

                const current = shell.querySelector('.marketing-table-panel')?.getBoundingClientRect().width || 0;
                const step = event.shiftKey ? 80 : 32;
                const shellWidth = shell.getBoundingClientRect().width;

                if (event.key === 'Home') return setPanelWidth(300, true);
                if (event.key === 'End') return setPanelWidth(shellWidth - 390, true);
                setPanelWidth(current + (event.key === 'ArrowRight' ? step : -step), true);
            }

            function queryString(params) {
                const qs = new URLSearchParams();
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && String(value) !== '') qs.set(key, value);
                });
                return qs.toString();
            }

            function followUpBadge(customer) {
                const stateName = customer.follow_up_state || 'none';
                const label = customer.next_follow_up_at?.display || 'None';
                return `<span class="marketing-badge marketing-badge--${escapeHtml(stateName)}">${escapeHtml(label)}</span>`;
            }

            function lifecycleBadge(customer) {
                return `<span class="marketing-badge marketing-badge--${escapeHtml(customer.lifecycle_status || 'existing')}">${escapeHtml(customer.lifecycle_label || 'Existing')}</span>`;
            }

            function aircraftChips(customer) {
                const items = customer.aircraft || [];
                if (!items.length) return '<span class="text-muted">-</span>';

                const chips = items.slice(0, 4).map((item) => `<span class="marketing-chip" title="${escapeHtml(item.type)}">${escapeHtml(item.type)}</span>`).join('');
                const more = items.length > 4 ? `<span class="marketing-chip">+${items.length - 4}</span>` : '';

                return `<span class="marketing-chip-list">${chips}${more}</span>`;
            }

            function renderRows(append = false) {
                if (!append) rowsEl.innerHTML = '';

                if (!state.rows.length) {
                    rowsEl.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No companies found</td></tr>';
                    return;
                }

                const html = state.rows.map((customer) => {
                    const primary = customer.primary_contact;
                    const contactLabel = primary
                        ? [primary.full_name, primary.position].filter(Boolean).join(' / ')
                        : `${customer.contacts_count || 0} contacts`;

                    return `
<tr data-customer-id="${customer.id}" class="${Number(state.selectedId) === Number(customer.id) ? 'is-active' : ''}">
  <td><span class="marketing-name">${escapeHtml(customer.name)}</span></td>
  <td>${lifecycleBadge(customer)}</td>
  <td title="${escapeHtml(customer.country)}">${escapeHtml(customer.country || '-')}</td>
  <td title="${escapeHtml(customer.company_type || '')}">${escapeHtml(customer.company_type || '-')}</td>
  <td title="${escapeHtml(customer.segment || '')}">${escapeHtml(customer.segment || '-')}</td>
  <td title="${escapeHtml(customer.aircraft_text || '')}">${aircraftChips(customer)}</td>
  <td title="${escapeHtml(contactLabel)}">${escapeHtml(contactLabel || '-')}</td>
  <td>${Number(customer.workorders_count || 0)}</td>
  <td>${followUpBadge(customer)}</td>
</tr>`;
                }).join('');

                rowsEl.innerHTML = html;
            }

            async function loadCustomers(reset = false) {
                if (state.loading) return;

                state.loading = true;
                loadState.innerHTML = loadingHtml('Loading');

                if (reset) {
                    loadMoreBtn.hidden = true;
                    state.page = 1;
                    state.rows = [];
                    rowsEl.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">${loadingHtml('Loading')}</td></tr>`;
                } else {
                    loadMoreBtn.hidden = false;
                    loadMoreBtn.innerHTML = loadingHtml('Loading');
                }

                try {
                    const params = {
                        ...currentFilters(),
                        page: state.page,
                        per_page: 40,
                    };
                    const data = await requestJson(`${routes.customers}?${queryString(params)}`, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    const items = data.items || [];

                    state.rows = reset ? items : state.rows.concat(items);
                    state.hasMore = !!data.pagination?.has_more;
                    state.page = data.pagination?.next_page || state.page + 1;

                    resultCount.textContent = `${Number(data.pagination?.total || 0)} companies`;
                    loadMoreBtn.hidden = !state.hasMore;
                    loadMoreBtn.textContent = state.hasMore ? 'Scroll for more' : '';
                    renderRows(false);
                } catch (error) {
                    rowsEl.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
                } finally {
                    state.loading = false;
                    loadState.textContent = '';
                }
            }

            async function saveFilters() {
                try {
                    await window.UserUiSettings?.set(scope, filtersKey, currentFilters());
                } catch (_) {}
            }

            function debounce(fn, wait) {
                let timer = null;
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            async function openCustomer(id) {
                state.selectedId = id;
                state.workordersLoaded = false;
                state.workordersPage = 1;
                state.workordersHasMore = false;
                state.workordersLoading = false;
                renderRows(false);

                detail.classList.add('is-open');
                setDetailTitleLoading();
                detailMeta.textContent = 'Marketing profile';

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('customer', id);
                window.history.replaceState({}, '', nextUrl.toString());

                try {
                    const data = await requestJson(urlFor(routes.showCustomer, id), { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    state.selectedCustomer = data.customer;
                    renderDetail();

                    if (state.activeTab === 'workorders') {
                        await loadWorkorders(true);
                    }
                } catch (error) {
                    notify(error.message, 'error');
                }
            }

            function selectedAircraftIds(customer) {
                return (customer.aircraft || []).map((item) => String(item.id));
            }

            function setSelectMultiple(select, values) {
                const set = new Set((values || []).map(String));
                Array.from(select.options).forEach((option) => {
                    option.selected = set.has(String(option.value));
                });
            }

            function renderDetail() {
                const customer = state.selectedCustomer;
                if (!customer) return;

                setDetailTitleText(customer.name);
                detailMeta.textContent = [customer.country, customer.company_type, customer.segment].filter(Boolean).join(' / ') || 'Marketing profile';

                profileForm.name.value = customer.name || '';
                profileForm.lifecycle_status.value = customer.profile?.lifecycle_status || 'existing';
                profileForm.country.value = customer.profile?.country || '';
                profileForm.address.value = customer.profile?.address || '';
                profileForm.company_type_id.value = customer.profile?.company_type_id || '';
                profileForm.segment_id.value = customer.profile?.segment_id || '';
                profileForm.terms_label.value = customer.profile?.terms_label || '';
                setSelectMultiple(document.getElementById('detailAircraft'), selectedAircraftIds(customer));
                markFormClean(profileForm);

                renderContacts(customer.contacts || []);
                renderNoteContactOptions(customer.contacts || []);
                renderNotes(customer.notes || []);
                disableAutocomplete(detail);
                window.initProjectDatePickers?.(detail);
            }

            function renderContacts(contacts) {
                if (!contacts.length) {
                    contactsList.innerHTML = '<div class="marketing-empty">No contacts</div>';
                    return;
                }

                contactsList.innerHTML = contacts.map((contact) => `
<form class="marketing-contact-row js-contact-row" data-contact-id="${contact.id}" data-no-spinner autocomplete="off">
  <input name="first_name" class="form-control form-control-sm" value="${escapeHtml(contact.first_name)}" placeholder="First Name" autocomplete="off">
  <input name="last_name" class="form-control form-control-sm" value="${escapeHtml(contact.last_name)}" placeholder="Last Name" autocomplete="off">
  <input name="position" class="form-control form-control-sm" value="${escapeHtml(contact.position)}" placeholder="Position" autocomplete="off">
  <input name="email" class="form-control form-control-sm" value="${escapeHtml(contact.email)}" placeholder="Email" autocomplete="off">
  <input name="phone" class="form-control form-control-sm" value="${escapeHtml(contact.phone)}" placeholder="Phone" autocomplete="off">
  <div class="marketing-contact-primary-actions">
    <label class="d-flex align-items-center gap-2 small fw-bold mb-0">
      <input name="is_primary" class="form-check-input mt-0" type="checkbox" value="1" ${contact.is_primary ? 'checked' : ''}>
      Primary
    </label>
    <div class="marketing-contact-actions">
      <button class="btn btn-sm btn-outline-primary marketing-save-button" type="submit" data-save-button title="Save"><i class="bi bi-check-lg" data-save-icon></i></button>
      <button class="btn btn-sm btn-outline-danger js-contact-delete" type="button" title="Delete"><i class="bi bi-trash"></i></button>
    </div>
  </div>
</form>`).join('');
                disableAutocomplete(contactsList);
                contactsList.querySelectorAll('.js-contact-row').forEach(markFormClean);
            }

            function renderNoteContactOptions(contacts) {
                noteContact.innerHTML = '<option value=""></option>' + contacts.map((contact) => {
                    const label = contact.full_name || contact.email || contact.phone || `Contact ${contact.id}`;
                    return `<option value="${contact.id}">${escapeHtml(label)}</option>`;
                }).join('');
            }

            function renderNotes(notes) {
                if (!notes.length) {
                    notesList.innerHTML = '<div class="marketing-empty">No notes</div>';
                    return;
                }

                notesList.innerHTML = notes.map((note) => {
                    const follow = note.follow_up_at?.display
                        ? `<span class="marketing-badge marketing-badge--${note.follow_up_status === 'open' ? 'upcoming' : 'none'}">${escapeHtml(note.follow_up_at.display)}</span>`
                        : '';

                    return `
<div class="marketing-note" data-note-id="${note.id}">
  <div class="marketing-note-meta">
    <span>${escapeHtml(note.interaction_at?.display || '')} ${note.contact_name ? '/ ' + escapeHtml(note.contact_name) : ''}</span>
    <span>${follow}</span>
  </div>
  <div class="marketing-note-text">${escapeHtml(note.note)}</div>
  <div class="marketing-actions">
    ${note.follow_up_status === 'open' ? `<button class="btn btn-sm btn-outline-success js-note-done" type="button" title="Mark follow-up as done"><i class="bi bi-check2-circle"></i><span>Follow-up done</span></button>` : ''}
    ${note.follow_up_status !== 'cancelled' ? `<button class="btn btn-sm btn-outline-secondary js-note-cancel" type="button" title="Cancel follow-up reminder"><i class="bi bi-slash-circle"></i><span>Cancel follow-up</span></button>` : ''}
    <button class="btn btn-sm btn-outline-danger js-note-delete" type="button" title="Delete note"><i class="bi bi-trash"></i><span>Delete</span></button>
  </div>
</div>`;
                }).join('');
            }

            async function saveProfile(event) {
                event.preventDefault();
                if (!state.selectedCustomer) return;

                setSaveButtonSaving(profileForm, true);

                try {
                    const data = await requestJson(urlFor(routes.updateProfile, state.selectedCustomer.id), {
                        method: 'PATCH',
                        body: JSON.stringify(formDataObject(profileForm)),
                    });
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Saved');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    setSaveButtonSaving(profileForm, false);
                    window.safeHideSpinner?.();
                }
            }

            function updateRowCache(customer) {
                const index = state.rows.findIndex((row) => Number(row.id) === Number(customer.id));
                if (index >= 0) state.rows[index] = customer;
            }

            async function addContact(event) {
                event.preventDefault();
                if (!state.selectedCustomer) return;

                try {
                    const data = await requestJson(urlFor(routes.storeContact, state.selectedCustomer.id), {
                        method: 'POST',
                        body: JSON.stringify(formDataObject(contactForm)),
                    });
                    contactForm.reset();
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Contact added');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    window.safeHideSpinner?.();
                }
            }

            async function saveContact(form) {
                const id = form.dataset.contactId;
                setSaveButtonSaving(form, true);

                try {
                    const data = await requestJson(urlFor(routes.updateContact, id), {
                        method: 'PATCH',
                        body: JSON.stringify(formDataObject(form)),
                    });
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Contact saved');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    setSaveButtonSaving(form, false);
                    window.safeHideSpinner?.();
                }
            }

            async function deleteContact(button) {
                const form = button.closest('.js-contact-row');
                const id = form?.dataset.contactId;
                if (!id || !confirm('Delete contact?')) return;

                try {
                    const data = await requestJson(urlFor(routes.destroyContact, id), { method: 'DELETE' });
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Contact deleted');
                } catch (error) {
                    notify(error.message, 'error');
                }
            }

            async function addNote(event) {
                event.preventDefault();
                if (!state.selectedCustomer) return;

                try {
                    const data = await requestJson(urlFor(routes.storeNote, state.selectedCustomer.id), {
                        method: 'POST',
                        body: JSON.stringify(formDataObject(noteForm)),
                    });
                    noteForm.reset();
                    noteForm.follow_up_status.value = 'open';
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Note added');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    window.safeHideSpinner?.();
                }
            }

            async function updateNoteStatus(button, status) {
                const noteEl = button.closest('[data-note-id]');
                const id = noteEl?.dataset.noteId;
                if (!id) return;

                try {
                    const data = await requestJson(urlFor(routes.updateNote, id), {
                        method: 'PATCH',
                        body: JSON.stringify({ follow_up_status: status }),
                    });
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Note updated');
                } catch (error) {
                    notify(error.message, 'error');
                }
            }

            async function deleteNote(button) {
                const noteEl = button.closest('[data-note-id]');
                const id = noteEl?.dataset.noteId;
                if (!id || !confirm('Delete note?')) return;

                try {
                    const data = await requestJson(urlFor(routes.destroyNote, id), { method: 'DELETE' });
                    state.selectedCustomer = data.customer;
                    updateRowCache(data.customer);
                    renderDetail();
                    renderRows(false);
                    notify('Note deleted');
                } catch (error) {
                    notify(error.message, 'error');
                }
            }

            async function addCompany(event) {
                event.preventDefault();

                try {
                    const data = await requestJson(routes.storeCustomer, {
                        method: 'POST',
                        body: JSON.stringify(formDataObject(createForm)),
                    });
                    createForm.reset();
                    bootstrap.Modal.getInstance(document.getElementById('marketingCreateModal'))?.hide();
                    await loadCustomers(true);
                    await openCustomer(data.customer.id);
                    notify('Company added');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    window.safeHideSpinner?.();
                }
            }

            function renderWorkorders(items, append = false) {
                if (!append) workordersRows.innerHTML = '';

                if (!items.length && !append) {
                    workordersRows.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">No workorders</td></tr>';
                    return;
                }

                const html = items.map((wo) => `
<tr>
  <td><a href="${escapeHtml(wo.urls.open)}">${escapeHtml(wo.number_label)}</a></td>
  <td>${escapeHtml(wo.status || '-')}</td>
  <td>${escapeHtml(wo.ro_number || '-')}</td>
  <td>${escapeHtml(wo.part_number || '-')}</td>
  <td title="${escapeHtml(wo.description || '')}">${escapeHtml(wo.description || '-')}</td>
  <td>${escapeHtml(wo.serial_number || '-')}</td>
  <td>${escapeHtml(wo.task || '-')}</td>
  <td>${escapeHtml(wo.terms || '-')}</td>
  <td>${wo.estimate_amount ? escapeHtml(wo.estimate_amount) : '-'}</td>
  <td>${escapeHtml(wo.estimate_date?.display || '-')}</td>
  <td>${escapeHtml(wo.approval_date?.display || '-')}</td>
  <td>
    <button class="btn btn-sm btn-outline-secondary js-marketing-media" type="button" data-media-kind="photos" data-media-count="${Number(wo.image_count || 0)}" data-media-url="${escapeHtml(wo.urls.photos)}" data-wo-label="${escapeHtml(wo.number_label)}" title="Images"><i class="bi bi-images"></i> ${Number(wo.image_count || 0)}</button>
    <button class="btn btn-sm btn-outline-secondary js-marketing-media" type="button" data-media-kind="pdfs" data-media-count="${Number(wo.pdf_count || 0)}" data-media-url="${escapeHtml(wo.urls.pdfs)}" data-wo-label="${escapeHtml(wo.number_label)}" title="PDF"><i class="bi bi-file-earmark-pdf"></i> ${Number(wo.pdf_count || 0)}</button>
  </td>
</tr>`).join('');

                workordersRows.insertAdjacentHTML('beforeend', html);
            }

            async function loadWorkorders(reset = false) {
                if (!state.selectedCustomer) return;
                if (state.workordersLoading) return;
                if (reset) {
                    state.workordersPage = 1;
                    state.workordersLoaded = false;
                    state.workordersHasMore = false;
                    workordersRows.innerHTML = `<tr><td colspan="12" class="text-center text-muted py-4">${loadingHtml('Loading')}</td></tr>`;
                }

                state.workordersLoading = true;
                if (reset) {
                    workordersMore.hidden = true;
                } else {
                    workordersMore.hidden = false;
                    workordersMore.innerHTML = loadingHtml('Loading');
                }

                try {
                    const url = `${urlFor(routes.workorders, state.selectedCustomer.id)}?${queryString({ page: state.workordersPage, per_page: 20 })}`;
                    const data = await requestJson(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    state.workordersHasMore = !!data.pagination?.has_more;
                    state.workordersPage = data.pagination?.next_page || state.workordersPage + 1;
                    state.workordersLoaded = true;
                    workordersMore.hidden = !state.workordersHasMore;
                    workordersMore.textContent = state.workordersHasMore ? 'Scroll for more' : '';
                    renderWorkorders(data.items || [], !reset);
                } catch (error) {
                    workordersRows.innerHTML = `<tr><td colspan="12" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
                } finally {
                    state.workordersLoading = false;
                }
            }

            function normalizePhotoPayload(data) {
                if (Array.isArray(data.photos)) {
                    const items = data.photos.map((item) => ({
                        thumb: item.thumb_url || item.thumb || item.big_url || item.big || '',
                        big: item.big_url || item.big || item.thumb_url || item.thumb || '',
                        label: item.alt || item.name || 'Photo',
                    })).filter((item) => item.big || item.thumb);
                    return items.length ? [{ key: 'photos', label: 'Photos', items }] : [];
                }

                const groups = data.groups || {};
                const media = data.media || {};
                return Object.entries(groups).map(([group, label]) => {
                    const items = (media[group] || []).map((item, index) => ({
                        thumb: item.thumb || item.thumb_url || item.big || item.big_url || '',
                        big: item.big || item.big_url || item.thumb || item.thumb_url || '',
                        label: item.name || item.file_name || `${label || group} ${index + 1}`,
                    })).filter((item) => item.big || item.thumb);

                    return { key: group, label: label || group, items };
                }).filter((group) => group.items.length);
            }

            function normalizePdfPayload(data) {
                return (data.pdfs || []).map((item) => ({
                    name: item.name || item.file_name || 'PDF',
                    url: item.url || '',
                    downloadUrl: item.download_url || item.url || '',
                    kind: item.kind_label || '',
                })).filter((item) => item.url);
            }

            function renderPhotoModal(groups, groupName = 'marketing-media') {
                if (!groups.length) {
                    mediaBody.innerHTML = '<div class="marketing-empty">No images</div>';
                    return;
                }

                mediaBody.innerHTML = groups.map((group) => `
<section class="marketing-media-group">
  <h6 class="marketing-media-group-title">${escapeHtml(group.label)}</h6>
  <div class="marketing-media-grid">${group.items.map((item) => `
    <a class="marketing-media-thumb" href="${escapeHtml(item.big)}" data-fancybox="${escapeHtml(slugForAttribute(groupName))}-${escapeHtml(slugForAttribute(group.key || group.label))}" data-caption="${escapeHtml(item.label)}" title="${escapeHtml(item.label)}">
      <img src="${escapeHtml(item.thumb || item.big)}" alt="${escapeHtml(item.label)}">
    </a>`).join('')}</div>
</section>`).join('');
            }

            function renderPdfModal(items) {
                if (!items.length) {
                    mediaBody.innerHTML = '<div class="marketing-empty">No PDF files</div>';
                    return;
                }

                const first = items[0];
                mediaBody.innerHTML = `
<div class="marketing-media-pdf-layout">
  <div class="marketing-media-pdf-list list-group">
    ${items.map((item, index) => `
      <button class="list-group-item list-group-item-action js-marketing-pdf-select ${index === 0 ? 'active' : ''}" type="button" data-pdf-url="${escapeHtml(item.url)}">
        <div class="fw-bold text-truncate">${escapeHtml(item.name)}</div>
        <div class="small ${index === 0 ? 'text-white-50' : 'text-muted'}">${escapeHtml(item.kind || 'PDF')}</div>
      </button>
    `).join('')}
  </div>
  <iframe class="marketing-media-pdf-frame" src="${escapeHtml(first.url)}" title="${escapeHtml(first.name)}"></iframe>
</div>`;
            }

            async function openMediaModal(button) {
                const kind = button.dataset.mediaKind;
                const url = button.dataset.mediaUrl;
                const woLabel = button.dataset.woLabel || 'WO';
                const count = Number(button.dataset.mediaCount || 0);
                if (!kind || !url) return;

                mediaTitle.textContent = `${woLabel} ${kind === 'pdfs' ? 'PDF' : 'Images'}`;
                mediaBody.innerHTML = `<div class="text-center text-muted py-4">${loadingHtml('Loading')}</div>`;
                bootstrap.Modal.getOrCreateInstance(mediaModalEl).show();

                if (count <= 0) {
                    if (kind === 'pdfs') {
                        renderPdfModal([]);
                    } else {
                        renderPhotoModal([]);
                    }
                    return;
                }

                try {
                    const data = await requestJson(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    if (kind === 'pdfs') {
                        renderPdfModal(normalizePdfPayload(data));
                    } else {
                        renderPhotoModal(normalizePhotoPayload(data), `marketing-${button.dataset.woLabel || 'wo'}-photos`);
                    }
                } catch (error) {
                    mediaBody.innerHTML = `<div class="text-danger py-4">${escapeHtml(error.message)}</div>`;
                }
            }

            function switchTab(tab, persist = true) {
                if (!allowedTabs.includes(tab)) tab = 'overview';

                state.activeTab = tab;
                document.querySelectorAll('.marketing-tab').forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === tab));
                document.querySelectorAll('.marketing-pane').forEach((pane) => pane.classList.toggle('is-active', pane.dataset.pane === tab));

                if (persist) {
                    window.UserUiSettings?.set(scope, tabKey, tab)?.catch(() => {});
                }

                if (tab === 'workorders' && state.selectedCustomer && !state.workordersLoaded) {
                    loadWorkorders(true);
                }
            }

            rowsEl.addEventListener('click', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row) openCustomer(row.dataset.customerId);
            });

            Object.values(filterEls).forEach((el) => {
                const handler = debounce(async () => {
                    updateFilterStates();
                    await saveFilters();
                    await loadCustomers(true);
                }, el.type === 'search' ? 260 : 80);
                el.addEventListener(el.type === 'search' ? 'input' : 'change', handler);
            });

            document.getElementById('marketingResetFilters').addEventListener('click', async () => {
                setFilters({});
                await saveFilters();
                await loadCustomers(true);
            });

            tableScroll.addEventListener('scroll', () => {
                const remaining = tableScroll.scrollHeight - tableScroll.scrollTop - tableScroll.clientHeight;
                if (remaining < 120 && state.hasMore) loadCustomers(false);
            });

            workordersScroll?.addEventListener('scroll', () => {
                const remaining = workordersScroll.scrollHeight - workordersScroll.scrollTop - workordersScroll.clientHeight;
                if (remaining < 140 && state.workordersHasMore) loadWorkorders(false);
            });

            workordersRows.addEventListener('click', (event) => {
                const mediaBtn = event.target.closest('.js-marketing-media');
                if (mediaBtn) openMediaModal(mediaBtn);
            });

            mediaBody.addEventListener('click', (event) => {
                const pdfBtn = event.target.closest('.js-marketing-pdf-select');
                const frame = mediaBody.querySelector('.marketing-media-pdf-frame');
                if (!pdfBtn || !frame) return;

                mediaBody.querySelectorAll('.js-marketing-pdf-select').forEach((btn) => {
                    btn.classList.toggle('active', btn === pdfBtn);
                    btn.querySelector('.small')?.classList.toggle('text-white-50', btn === pdfBtn);
                    btn.querySelector('.small')?.classList.toggle('text-muted', btn !== pdfBtn);
                });
                frame.src = pdfBtn.dataset.pdfUrl || '';
            });

            document.addEventListener('pointerdown', (event) => {
                if (isMarketingAutofillScope(event.target)) unlockAutofillBlockedField(event.target);
            }, true);

            document.addEventListener('focusin', (event) => {
                if (isMarketingAutofillScope(event.target)) unlockAutofillBlockedField(event.target);
            }, true);

            document.getElementById('marketingCreateModal')?.addEventListener('shown.bs.modal', (event) => {
                disableAutocomplete(event.currentTarget);
            });

            profileForm.addEventListener('submit', saveProfile);
            profileForm.addEventListener('input', handleDirtyFieldChange);
            profileForm.addEventListener('change', handleDirtyFieldChange);
            contactForm.addEventListener('submit', addContact);
            noteForm.addEventListener('submit', addNote);
            createForm.addEventListener('submit', addCompany);

            contactsList.addEventListener('input', handleDirtyFieldChange);
            contactsList.addEventListener('change', handleDirtyFieldChange);

            contactsList.addEventListener('submit', (event) => {
                const form = event.target.closest('.js-contact-row');
                if (!form) return;
                event.preventDefault();
                saveContact(form);
            });

            contactsList.addEventListener('click', (event) => {
                const deleteBtn = event.target.closest('.js-contact-delete');
                if (deleteBtn) deleteContact(deleteBtn);
            });

            notesList.addEventListener('click', (event) => {
                const doneBtn = event.target.closest('.js-note-done');
                const cancelBtn = event.target.closest('.js-note-cancel');
                const deleteBtn = event.target.closest('.js-note-delete');
                if (doneBtn) updateNoteStatus(doneBtn, 'done');
                if (cancelBtn) updateNoteStatus(cancelBtn, 'cancelled');
                if (deleteBtn) deleteNote(deleteBtn);
            });

            document.querySelectorAll('.marketing-tab').forEach((btn) => {
                btn.addEventListener('click', () => switchTab(btn.dataset.tab));
            });

            splitter?.addEventListener('pointerdown', startResize);
            splitter?.addEventListener('keydown', resizeWithKeyboard);
            window.addEventListener('resize', () => {
                const leftPanel = shell?.querySelector('.marketing-table-panel');
                if (leftPanel) setPanelWidth(leftPanel.getBoundingClientRect().width, false);
            });

            document.getElementById('detailClose').addEventListener('click', () => {
                detail.classList.remove('is-open');
            });

            (async function init() {
                disableAutocomplete(document.querySelector('[data-marketing-page]') || document);
                disableAutocomplete(document.getElementById('marketingCreateModal') || document.createElement('div'));

                try {
                    await restorePanelLayout();
                    await restoreActiveTab();
                } finally {
                    shell?.classList.add('is-layout-ready');
                }

                try {
                    const saved = await window.UserUiSettings?.get(scope, filtersKey, {});
                    setFilters(saved || {});
                } catch (_) {}
                await loadCustomers(true);

                const initialCustomerId = new URLSearchParams(window.location.search).get('customer');
                if (initialCustomerId) {
                    await openCustomer(initialCustomerId);
                }
            })();
        })();
    </script>
@endsection
