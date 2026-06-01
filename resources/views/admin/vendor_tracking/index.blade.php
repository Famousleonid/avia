@extends('admin.master')

@section('style')
    <style>
        .content {
            overflow-y: auto !important;
        }

        .content:has(.vendor-tracking-page) {
            padding-top: 0 !important;
        }

        html[data-bs-theme="light"] .content:has(.vendor-tracking-page),
        html[data-bs-theme="light"] .content:has(.vendor-tracking-page) .content-inner {
            background: #ffffff !important;
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
            min-height: 100%;
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

        .vendor-tracking-filter-customer {
            flex: 0 0 170px;
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

        .vendor-tracking-filter-clear-wrap {
            position: relative;
        }

        .vendor-tracking-filter-clear-wrap .form-control {
            padding-right: 1.85rem;
        }

        .vendor-tracking-filter-clear {
            align-items: center;
            background: transparent;
            border: 0;
            color: #6c757d;
            display: none;
            height: 100%;
            justify-content: center;
            padding: 0 .45rem;
            position: absolute;
            right: 0;
            top: 0;
        }

        .vendor-tracking-filter-clear-wrap.has-value .vendor-tracking-filter-clear {
            display: inline-flex;
        }

        .vendor-tracking-filter-clear:hover,
        .vendor-tracking-filter-clear:focus {
            color: var(--bs-info);
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

        .vendor-tracking-page .vendor-tracking-filter-active {
            border-color: var(--bs-info) !important;
            box-shadow: 0 0 0 .14rem rgba(13, 202, 240, .18) !important;
        }

        .vendor-tracking-check.is-filter-active .form-check-input {
            border-color: var(--bs-info) !important;
            box-shadow: 0 0 0 .14rem rgba(13, 202, 240, .18) !important;
        }

        .vendor-tracking-check.is-filter-active .form-check-label {
            color: var(--bs-info);
            font-weight: 600;
        }

        .vendor-tracking-wo-link {
            text-decoration: none;
        }

        .vendor-tracking-ro-filter,
        .vendor-tracking-readonly-date {
            display: flex;
            align-items: center;
            width: 100%;
            min-width: 0;
            min-height: calc(1.8125rem + 2px);
            box-sizing: border-box;
            border: 1px solid rgba(108, 117, 125, .55);
            border-radius: .25rem;
            background: #ffffff;
            color: #1f2937;
            padding: .25rem .35rem;
            font-size: .78rem;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .vendor-tracking-ro-filter {
            justify-content: flex-start;
            text-align: left;
        }

        .vendor-tracking-ro-filter.has-value {
            cursor: pointer;
        }

        .vendor-tracking-ro-filter.has-value:hover,
        .vendor-tracking-ro-filter.has-value:focus {
            border-color: rgba(13, 202, 240, .65);
            color: var(--bs-info);
            outline: none;
        }

        .vendor-tracking-readonly-date {
            justify-content: center;
        }

        .vendor-tracking-readonly-date.has-value {
            border-color: rgba(25, 135, 84, .55);
            background-color: rgba(25, 135, 84, .1);
            color: #146c43;
        }

        .vendor-tracking-ro-filter.is-empty,
        .vendor-tracking-readonly-date.is-empty {
            color: transparent;
            pointer-events: none;
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

        .vendor-tracking-results-card {
            min-height: var(--vendor-tracking-results-min-height, 420px);
            position: relative;
        }

        .vendor-tracking-results-card.is-filtering {
            opacity: .74;
            transition: opacity .12s ease;
        }

        .vendor-tracking-results-card.is-filtering .table-responsive {
            min-height: var(--vendor-tracking-table-min-height, 360px);
        }

        .vendor-tracking-loading-dots {
            align-items: center;
            background: rgba(255, 255, 255, .82);
            bottom: 0;
            display: none;
            justify-content: center;
            left: 0;
            pointer-events: none;
            position: absolute;
            right: 0;
            top: 0;
            z-index: 8;
        }

        .vendor-tracking-results-card.is-filtering .vendor-tracking-loading-dots {
            display: flex;
        }

        .vendor-tracking-loading-dots span {
            animation: vendorTrackingDots .68s infinite ease-in-out alternate;
            background: var(--bs-info);
            border-radius: 50%;
            display: block;
            height: 9px;
            margin: 0 4px;
            width: 9px;
        }

        .vendor-tracking-loading-dots span:nth-child(2) {
            animation-delay: .14s;
        }

        .vendor-tracking-loading-dots span:nth-child(3) {
            animation-delay: .28s;
        }

        @keyframes vendorTrackingDots {
            from {
                opacity: .35;
                transform: translateY(0);
            }

            to {
                opacity: 1;
                transform: translateY(-8px);
            }
        }

        .vendor-tracking-table.is-column-settings-pending {
            opacity: 0;
        }

        .vendor-tracking-table.is-column-settings-ready {
            opacity: 1;
            transition: opacity .08s ease;
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
            width: 72px;
            min-width: 72px;
            max-width: 72px;
            white-space: nowrap;
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
            font-size: 14px;
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

        .vendor-tracking-part-name-col {
            width: 12%;
            min-width: 120px;
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

        .vendor-tracking-toolbar-btn.btn-outline-warning {
            color: #713f12;
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .vendor-tracking-toolbar-btn.btn-outline-warning:hover,
        .vendor-tracking-toolbar-btn.btn-outline-warning:focus {
            color: #451a03;
            background: #fde68a;
            border-color: #f59e0b;
        }

        .quantum-buffer-modal .modal-dialog {
            max-width: min(1560px, calc(100vw - 2rem));
        }

        .quantum-buffer-modal .modal-body {
            max-height: calc(100vh - 190px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        .quantum-buffer-rules {
            display: grid;
            grid-template-columns: repeat(4, minmax(170px, 1fr));
            gap: .65rem;
        }

        .quantum-buffer-rule {
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            background: #f8fafc;
            padding: .65rem .75rem;
            min-height: 84px;
        }

        .quantum-buffer-rule-title {
            font-size: .82rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: .25rem;
        }

        .quantum-buffer-rule-text {
            font-size: .78rem;
            color: #64748b;
            line-height: 1.35;
        }

        .quantum-buffer-table-wrap {
            flex: 1 1 auto;
            min-height: 280px;
            overflow: auto;
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            background: #ffffff;
        }

        .quantum-buffer-table {
            min-width: 1320px;
            margin-bottom: 0;
        }

        .quantum-buffer-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f1f5f9;
            color: #334155;
            white-space: nowrap;
        }

        .quantum-buffer-table td {
            vertical-align: top;
        }

        .quantum-buffer-message {
            max-width: 340px;
            white-space: normal;
        }

        .vendor-tracking-page .table-responsive {
            background: #2B3035;
            border-radius: 10px;
        }

        .vendor-tracking-table {
            --bs-table-bg: #2B3035;
            --bs-table-color: #f8f9fa;
            --bs-table-border-color: #d7e0ea;
            margin-bottom: 0;
            background: #2B3035;
        }

        .vendor-tracking-table > thead > tr > th {
            background: #2B3035;
            color: #f8f9fa;
            border-color: #d7e0ea;
            vertical-align: middle;
        }

        .vendor-tracking-table > tbody > tr > td {
            background: #2B3035;
            color: #f8f9fa;
            border-color: #d7e0ea;
            vertical-align: middle;
        }

        .vendor-tracking-traveler-row > td {
            background: #2B3035 !important;
        }

        .vendor-tracking-traveler-toggle {
            width: 24px;
            height: 24px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .vendor-tracking-traveler-toggle .bi {
            transition: transform .16s ease;
        }

        .vendor-tracking-traveler-toggle[aria-expanded="true"] .bi {
            transform: rotate(90deg);
        }

        .vendor-tracking-detail-cell {
            background: #2B3035 !important;
            padding: .45rem .65rem !important;
            text-align: left;
        }

        .vendor-tracking-detail-panel {
            display: inline-block;
            width: max-content;
            max-width: 100%;
            min-width: 300px;
            margin-left: 0;
            margin-right: auto;
            --dir-table-bg: #2D2D2D;
            background: #2D2D2D;
        }

        .vendor-tracking-detail-table {
            margin: 0;
            color: inherit;
            font-size: .82rem;
            width: auto;
            min-width: 300px;
        }

        .vendor-tracking-detail-layout {
            display: inline-grid;
            grid-template-columns: auto max-content max-content;
            align-items: stretch;
            gap: 0;
            --dir-table-bg: #2D2D2D;
            background: #2D2D2D;
        }

        .vendor-tracking-detail-table th,
        .vendor-tracking-detail-table td {
            padding: .25rem .45rem;
            white-space: nowrap;
        }

        .vendor-tracking-detail-form-col {
            width: 96px;
            min-width: 96px;
            text-align: center;
        }

        .vendor-tracking-detail-traveler-actions {
            display: contents;
        }

        .vendor-tracking-detail-action-col {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: .25rem .45rem;
            border: 1px solid var(--dir-border-2, #495057);
            border-left: 0;
            background: #2D2D2D;
            white-space: nowrap;
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
            background: #232525 !important;
        }

        html[data-bs-theme="dark"] .content:has(.vendor-tracking-page),
        html[data-bs-theme="dark"] .content:has(.vendor-tracking-page) .content-inner {
            background: #232525 !important;
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

        html[data-bs-theme="dark"] .vendor-tracking-ro-filter,
        html[data-bs-theme="dark"] .vendor-tracking-readonly-date {
            background-color: #171b22;
            color: #f8f9fa;
            border-color: rgba(255, 255, 255, 0.14);
        }

        html[data-bs-theme="dark"] .vendor-tracking-readonly-date.has-value {
            border-color: rgba(25, 135, 84, .55);
            background-color: rgba(25, 135, 84, .18);
            color: #d1e7dd;
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

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-warning {
            color: #fff2c2;
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.16);
        }

        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-warning:hover,
        html[data-bs-theme="dark"] .vendor-tracking-toolbar-btn.btn-outline-warning:focus {
            color: #211407;
            background: #fbbf24;
            border-color: #fbbf24;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .table-responsive {
            background: #2B3035;
        }

        html[data-bs-theme="dark"] .vendor-tracking-loading-dots {
            background: rgba(43, 48, 53, .78);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table {
            --bs-table-bg: #2B3035;
            --bs-table-color: #f8f9fa;
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
            background: #2B3035;
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > thead > tr > th {
            background: #2B3035;
            color: #adb5bd;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > tbody > tr > td {
            background: #2B3035;
            color: #f8f9fa;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-traveler-row > td {
            background: #2B3035 !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-cell {
            background: #2B3035 !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-panel {
            --dir-table-bg: #2D2D2D;
            background: #2D2D2D;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-table th,
        html[data-bs-theme="dark"] .vendor-tracking-detail-table td {
            color: #f8f9fa;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-layout,
        html[data-bs-theme="dark"] .vendor-tracking-detail-action-col {
            --dir-table-bg: #2D2D2D;
            background: #2D2D2D;
            border-color: var(--dir-border-2, #495057);
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

        html[data-bs-theme="dark"] .vendor-tracking-page .vendor-tracking-filter-active {
            border-color: #0dcaf0 !important;
            box-shadow: 0 0 0 .14rem rgba(13, 202, 240, .24) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-filter-clear {
            color: #adb5bd;
        }

        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-content,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-content,
        html[data-bs-theme="dark"] #quantumRoBufferModal .modal-content {
            background: #232525;
            color: #f8f9fa;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-header,
        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-footer,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-header,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-footer,
        html[data-bs-theme="dark"] #quantumRoBufferModal .modal-header,
        html[data-bs-theme="dark"] #quantumRoBufferModal .modal-footer {
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .quantum-buffer-rule {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
        }

        html[data-bs-theme="dark"] .quantum-buffer-rule-title {
            color: #e5e7eb;
        }

        html[data-bs-theme="dark"] .quantum-buffer-rule-text {
            color: #adb5bd;
        }

        html[data-bs-theme="dark"] .quantum-buffer-table-wrap {
            border-color: rgba(255, 255, 255, 0.12);
            background: #2B3035;
        }

        html[data-bs-theme="dark"] .quantum-buffer-table {
            --bs-table-bg: #2B3035;
            --bs-table-color: #f8f9fa;
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .quantum-buffer-table th {
            background: #1f2329;
            color: #adb5bd;
        }

        @media (max-width: 1100px) {
            .quantum-buffer-rules {
                grid-template-columns: repeat(2, minmax(170px, 1fr));
            }
        }

        @media (max-width: 700px) {
            .quantum-buffer-rules {
                grid-template-columns: 1fr;
            }
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
    <div class="container-fluid vendor-tracking-page my-1">
        @php
            $currentSort = $filters['sort'] ?? 'sent_date';
            $currentDirection = $filters['direction'] ?? 'asc';
            $sortUrl = function (string $column) use ($currentSort, $currentDirection) {
                $direction = $currentSort === $column
                    ? ($currentDirection === 'asc' ? 'desc' : 'asc')
                    : ($column === 'changed_at' ? 'desc' : 'asc');

                return route('vendor-tracking.index', array_merge(request()->query(), [
                    'sort' => $column,
                    'direction' => $direction,
                    'sort_user' => 1,
                ]));
            };
            $sortIcon = function (string $column) use ($currentSort, $currentDirection) {
                if ($currentSort !== $column) {
                    return 'bi-arrow-down-up';
                }

                return $currentDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
            };
            $vendorTrackingReadonlyDate = static fn ($date): string => format_project_date($date) ?? '';
            $vendorTrackingReadonlyText = static fn ($value): string => trim((string) ($value ?? ''));
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
                    <button type="button" class="btn btn-outline-warning btn-sm vendor-tracking-toolbar-btn" data-bs-toggle="modal" data-bs-target="#quantumRoBufferModal" title="Quantum buffer unresolved rows">
                        <i class="bi bi-database-exclamation me-1"></i> Quantum
                        @if(($quantumUnparsedTotal ?? 0) > 0)
                            <span class="badge text-bg-warning ms-1">{{ number_format($quantumUnparsedTotal) }}</span>
                        @endif
                    </button>
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
                    <form method="GET" action="{{ route('vendor-tracking.index') }}" class="vendor-tracking-filters d-flex gap-2 align-items-end" data-no-spinner>
                        <div class="vendor-tracking-filter-vendor">
                            <label class="form-label small text-muted">Vendor</label>
                            <select name="vendor_id" class="form-select form-select-sm">
                                <option value="0">All vendors</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((int) $filters['vendor_id'] === (int) $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="vendor-tracking-filter-customer">
                            <label class="form-label small text-muted">Customer</label>
                            <select name="customer_id" class="form-select form-select-sm">
                                <option value="0">All customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((int) ($filters['customer_id'] ?? 0) === (int) $customer->id)>{{ $customer->name }}</option>
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
                            <div class="vendor-tracking-filter-clear-wrap">
                                <input name="workorder" class="form-control form-control-sm" value="{{ $filters['workorder'] }}" placeholder="WO no">
                                <button type="button" class="vendor-tracking-filter-clear" data-clear-filter="workorder" title="Clear workorder" aria-label="Clear workorder">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="vendor-tracking-filter-text">
                            <label class="form-label small text-muted">Part number</label>
                            <div class="vendor-tracking-filter-clear-wrap">
                                <input name="part_number" class="form-control form-control-sm" value="{{ $filters['part_number'] }}" placeholder="P/N">
                                <button type="button" class="vendor-tracking-filter-clear" data-clear-filter="part_number" title="Clear part number" aria-label="Clear part number">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="vendor-tracking-filter-text">
                            <label class="form-label small text-muted">Repair order</label>
                            <div class="vendor-tracking-filter-clear-wrap">
                                <input name="repair_order" class="form-control form-control-sm" value="{{ $filters['repair_order'] }}" placeholder="RO">
                                <button type="button" class="vendor-tracking-filter-clear" data-clear-filter="repair_order" title="Clear repair order" aria-label="Clear repair order">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
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

        <div class="card bg-gradient vendor-tracking-results-card" id="vendorTrackingResultsCard">
            <div class="vendor-tracking-loading-dots" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="vendorTrackingTable" class="table table-sm table-bordered align-middle mb-0 vendor-tracking-table is-column-settings-pending">
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
                                <th class="vendor-tracking-part-name-col" data-col="part_name">Name</th>
                                <th class="vendor-tracking-serial-col" data-col="serial">Serial</th>
                                <th class="vendor-tracking-process-col" data-col="process">
                                    <a href="{{ $sortUrl('process') }}" class="vendor-tracking-sort-link {{ $currentSort === 'process' ? 'is-active' : '' }}">
                                        <span>Process</span>
                                        <i class="bi {{ $sortIcon('process') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="text-center vendor-tracking-date-col" data-col="sent">
                                    <a href="{{ $sortUrl('sent_date') }}" class="vendor-tracking-sort-link {{ $currentSort === 'sent_date' ? 'is-active' : '' }}">
                                        <span>Sent</span>
                                        <i class="bi {{ $sortIcon('sent_date') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                                <th class="text-center vendor-tracking-date-col" data-col="returned">Returned</th>
                                <th class="text-center vendor-tracking-date-col" data-col="ecd">ECD</th>
                                <th class="text-center" data-col="days">Days</th>
                                <th class="text-center" data-col="status">Status</th>
                                <th class="text-center vendor-tracking-date-col" data-col="changed_at">
                                    <a href="{{ $sortUrl('changed_at') }}" class="vendor-tracking-sort-link {{ $currentSort === 'changed_at' ? 'is-active' : '' }}">
                                        <span>Changed</span>
                                        <i class="bi {{ $sortIcon('changed_at') }} vendor-tracking-sort-icon"></i>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="vendorTrackingBody">
                            @forelse($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $sent = $row->date_start;
                                    $returned = $row->date_finish;
                                    $ecd = $row->date_promise;
                                    $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
                                    $woNumber = (string) ($wo?->number ?? '');
                                    $woDisplay = trim('w ' . preg_replace('/(\d{3})(?=\d)/', '$1 ', $woNumber));
                                    $vendorId = (int) ($row->vendor?->id ?? 0);
                                    $typeTextClass = match ($row->source) {
                                        'STD' => 'text-success',
                                        'Part' => 'text-primary',
                                        'Bush' => 'text-light',
                                        'Traveler' => 'text-info',
                                        default => 'text-secondary',
                                    };
                                    $isTravelerGroup = (bool) ($row->is_traveler_group ?? false);
                                    $travelerChildren = collect($row->traveler_children ?? []);
                                    $rowKey = (string) ($row->row_key ?? $row->id);
                                    $travelerGroup = (int) ($row->traveler_group ?? 0);
                                    $changedAt = $row->changed_at ? \Carbon\Carbon::parse($row->changed_at) : null;
                                    $changedDisplay = $changedAt ? format_project_date($changedAt) . ' ' . $changedAt->format('H:i') : '--';
                                    $repairOrderDisplay = $vendorTrackingReadonlyText($row->repair_order ?? '');
                                    $sentDisplay = $vendorTrackingReadonlyDate($sent);
                                    $returnedDisplay = $vendorTrackingReadonlyDate($returned);
                                @endphp
                                <tr data-row-id="{{ $row->id }}"
                                    data-row-key="{{ $rowKey }}"
                                    data-source-key="{{ $row->source_key }}"
                                    @if($travelerGroup > 0) data-traveler-group="{{ $travelerGroup }}" @endif
                                    @class(['vendor-tracking-traveler-row' => $isTravelerGroup])>
                                    <td class="vendor-tracking-repair-col" data-col="repair_order">
                                        @if($repairOrderDisplay !== '')
                                            <button type="button"
                                                    class="btn btn-sm vendor-tracking-ro-filter js-vendor-tracking-ro-filter has-value"
                                                    data-repair-order="{{ $repairOrderDisplay }}"
                                                    title="Filter by RO {{ $repairOrderDisplay }}">
                                                {{ $repairOrderDisplay }}
                                            </button>
                                        @else
                                            <span class="vendor-tracking-ro-filter is-empty"></span>
                                        @endif
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
                                    <td class="vendor-tracking-part-name-col" data-col="part_name">{{ $row->part_name ?? '--' }}</td>
                                    <td class="vendor-tracking-serial-col" data-col="serial">{{ $row->serial ?: '--' }}</td>
                                    <td class="vendor-tracking-process-col" data-col="process">
                                        @if($isTravelerGroup)
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-info vendor-tracking-traveler-toggle js-vendor-traveler-toggle" aria-expanded="false" title="Show traveler processes">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                                <span>{{ $row->process_name ?? '--' }}</span>
                                            </div>
                                        @else
                                            {{ $row->process_name ?? '--' }}
                                        @endif
                                    </td>
                                    <td class="text-center" data-col="sent">
                                        <span class="vendor-tracking-readonly-date {{ $sentDisplay !== '' ? 'has-value' : 'is-empty' }}">{{ $sentDisplay }}</span>
                                    </td>
                                    <td class="text-center" data-col="returned">
                                        <span class="vendor-tracking-readonly-date {{ $returnedDisplay !== '' ? 'has-value' : 'is-empty' }}">{{ $returnedDisplay }}</span>
                                    </td>
                                    <td class="vendor-tracking-save-cell" data-col="ecd">
                                        <input
                                            type="text"
                                            data-fp
                                            data-date-url="{{ $row->date_update_url }}"
                                            name="date_promise"
                                            class="form-control form-control-sm finish-input js-vendor-tracking-date"
                                            value="{{ optional($ecd)->format('Y-m-d') }}"
                                            data-original="{{ optional($ecd)->format('Y-m-d') ?? '' }}"
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
                                    <td class="text-center text-muted small" data-col="changed_at">{{ $changedDisplay }}</td>
                                </tr>
                                @if($isTravelerGroup)
                                    <tr class="vendor-tracking-detail-row d-none"
                                        data-traveler-detail-for="{{ $rowKey }}"
                                        data-row-id="{{ $row->id }}"
                                        data-row-key="{{ $rowKey }}"
                                        data-source-key="{{ $row->source_key }}"
                                        @if($travelerGroup > 0) data-traveler-group="{{ $travelerGroup }}" @endif>
                                        <td class="vendor-tracking-detail-cell" colspan="17">
                                            <div class="vendor-tracking-detail-panel">
                                                <div class="vendor-tracking-detail-layout">
                                                    <table class="table table-sm table-bordered align-middle dir-table vendor-tracking-detail-table">
                                                        <tbody>
                                                            @foreach($travelerChildren as $child)
                                                                <tr data-row-id="{{ $child->id }}" data-source-key="{{ $child->source_key }}">
                                                                    <td>{{ $child->process_name ?? '--' }}</td>
                                                                    <td>{{ $child->process_label ?? '--' }}</td>
                                                                    <td class="vendor-tracking-detail-form-col">
                                                                        <a href="{{ $child->form_url }}" class="btn btn-sm btn-outline-primary js-vendor-tracking-form-link" target="_blank">Form</a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                    <div class="vendor-tracking-detail-traveler-actions">
                                                        <div class="vendor-tracking-detail-action-col">
                                                            <a href="{{ route('tdr-processes.travelForm', ['id' => $row->id, 'traveler_group' => $travelerGroup]) }}" class="btn btn-sm btn-outline-primary js-vendor-tracking-form-link" target="_blank">
                                                                Form traveler
                                                            </a>
                                                        </div>
                                                        <div class="vendor-tracking-detail-action-col">
                                                            <button type="button" class="btn btn-sm btn-outline-warning js-vendor-traveler-ungroup" data-ungroup-url="{{ route('tdr-processes.traveler-ungroup', ['tdrId' => $row->id]) }}" data-traveler-group="{{ $travelerGroup }}">
                                                                Ungroup
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="17" class="text-muted text-center py-4 vendor-tracking-empty-cell">No vendor process records found.</td>
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

    <div class="modal fade quantum-buffer-modal" id="quantumRoBufferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Quantum RO Buffer</h5>
                        <div class="small text-muted">
                            Unparsed rows: {{ number_format($quantumUnparsedTotal ?? 0) }}
                            @if(($quantumUnparsedRows ?? collect())->count() < ($quantumUnparsedTotal ?? 0))
                                · showing latest {{ number_format(($quantumUnparsedRows ?? collect())->count()) }}
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="small text-muted">
                            Fix incorrect Ref values in Quantum. Laravel process codes are treated as a fixed parser dictionary.
                        </div>
                    </div>

                    <div class="quantum-buffer-rules">
                        <div class="quantum-buffer-rule">
                            <div class="quantum-buffer-rule-title">1. Ref code</div>
                            <div class="quantum-buffer-rule-text">Quantum `WO_BOM.REF` must match exactly one `process_names.code`. Spaces and case are ignored.</div>
                        </div>
                        <div class="quantum-buffer-rule">
                            <div class="quantum-buffer-rule-title">2. Target</div>
                            <div class="quantum-buffer-rule-text">Parser finds the WO, then one matching TDR process or one STD process for that process name.</div>
                        </div>
                        <div class="quantum-buffer-rule">
                            <div class="quantum-buffer-rule-title">3. Part rows</div>
                            <div class="quantum-buffer-rule-text">For `DETAIL_PART`, the Quantum PN must match the avia component PN on that WO.</div>
                        </div>
                        <div class="quantum-buffer-rule">
                            <div class="quantum-buffer-rule-title">4. Apply</div>
                            <div class="quantum-buffer-rule-text">When target is unique, parser writes RO, vendor, sent date and first returned date; ambiguous rows stay here.</div>
                        </div>
                    </div>

                    <div class="quantum-buffer-table-wrap">
                        <table class="table table-sm table-bordered align-middle quantum-buffer-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>RO</th>
                                    <th>WO</th>
                                    <th>Vendor</th>
                                    <th>PN</th>
                                    <th>Description</th>
                                    <th>Class</th>
                                    <th>Ref</th>
                                    <th>Sent</th>
                                    <th>Returned</th>
                                    <th>Qty</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quantumUnparsedRows as $line)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $line->apply_status === 'error' ? 'text-bg-danger' : 'text-bg-warning' }}">
                                                {{ $line->apply_status ?: 'pending' }}
                                            </span>
                                        </td>
                                        <td class="quantum-buffer-message">{{ $line->apply_message ?: 'Not parsed yet.' }}</td>
                                        <td>{{ $line->ro_number ?: '--' }}</td>
                                        <td>{{ $line->wo_number ?: '--' }}</td>
                                        <td>{{ $line->vendor_name ?: '--' }}</td>
                                        <td>{{ $line->pn ?: '--' }}</td>
                                        <td>{{ $line->description ?: '--' }}</td>
                                        <td>{{ $line->class ?: '--' }}</td>
                                        <td><code>{{ $line->bom_ref ?: '--' }}</code></td>
                                        <td>{{ format_project_date($line->out_date) ?? '--' }}</td>
                                        <td>{{ format_project_date($line->returned_date) ?? '--' }}</td>
                                        <td>
                                            <span class="d-block">To Repair: {{ $line->qty_repair ?? '--' }}</span>
                                            <span class="d-block">Reserved: {{ $line->qty_reserved ?? '--' }}</span>
                                            <span class="d-block">Repaired: {{ $line->qty_repaired ?? '--' }}</span>
                                        </td>
                                        <td>
                                            <span class="d-block">{{ $line->source_uid ?: '--' }}</span>
                                            <span class="d-block small text-muted">
                                                {{ $line->source_last_modified ? ((format_project_date($line->source_last_modified) ?? '--') . ' ' . $line->source_last_modified->format('H:i')) : '--' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted py-4">No unparsed Quantum RO rows.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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
        document.addEventListener('DOMContentLoaded', async function () {
            const updateUrl = @json(route('vendor-tracking.row.update'));
            const heartbeatUrl = @json(route('session.heartbeat'));
            let csrfToken = @json(csrf_token());
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const settingsScope = 'vendor-tracking.index';
            const vendorSettings = await window.UserUiSettings.loadScope(settingsScope);
            const key = 'vendorTrackingSources';
            const vendorNullKey = 'vendorTrackingIncludeVendorNull';
            const filtersStateKey = 'filters';
            const screenColumnsKey = 'vendorTrackingScreenColumns';
            const excelColumnsKey = 'vendorTrackingExcelColumns';
            const screenColumnOrderKey = 'vendorTrackingScreenColumnOrder';
            const excelColumnOrderKey = 'vendorTrackingExcelColumnOrder';
            const excelTitleKey = 'vendorTrackingExcelTitle';
            const settingsVersionKey = 'vendorTrackingSettingsVersion';
            const currentSettingsVersion = '3';
            const defaultScreenColumns = ['repair_order', 'type', 'info', 'vendor', 'wo', 'customer', 'ipl', 'part_number', 'part_name', 'serial', 'process', 'sent', 'returned', 'ecd', 'days', 'status', 'changed_at'];
            const defaultExcelColumns = ['repair_order', 'type', 'vendor', 'wo', 'customer', 'ipl', 'part_number', 'part_name', 'serial', 'process', 'sent', 'returned', 'ecd', 'days', 'changed_at'];
            const screenColumnDefs = [
                { key: 'repair_order', label: 'RO' },
                { key: 'type', label: 'Type' },
                { key: 'info', label: 'Info' },
                { key: 'vendor', label: 'Vendor' },
                { key: 'wo', label: 'WO' },
                { key: 'customer', label: 'Customer' },
                { key: 'ipl', label: 'IPL' },
                { key: 'part_number', label: 'Part Number' },
                { key: 'part_name', label: 'Part Name' },
                { key: 'serial', label: 'Serial' },
                { key: 'process', label: 'Process' },
                { key: 'sent', label: 'Sent' },
                { key: 'returned', label: 'Returned' },
                { key: 'ecd', label: 'ECD' },
                { key: 'days', label: 'Days' },
                { key: 'status', label: 'Status' },
                { key: 'changed_at', label: 'Changed' },
            ];
            const excelColumnDefs = screenColumnDefs.filter(def => !['info', 'status'].includes(def.key));
            const form = document.querySelector('.vendor-tracking-page form');
            const boxes = Array.from(document.querySelectorAll('.vendor-source-checkbox'));
            const vendorNullBox = document.getElementById('vendorTrackingIncludeNull');
            const autoSubmitFields = Array.from(form?.querySelectorAll('select[name="vendor_id"], select[name="customer_id"], select[name="status"]') || []);
            const textFields = Array.from(form?.querySelectorAll('input[name="workorder"], input[name="part_number"], input[name="repair_order"]') || []);
            const clearFilterButtons = Array.from(form?.querySelectorAll('.vendor-tracking-filter-clear') || []);
            const vendorTrackingTable = document.getElementById('vendorTrackingTable');
            const tbody = document.getElementById('vendorTrackingBody');
            const resultsCard = document.getElementById('vendorTrackingResultsCard');
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

            function settingValue(settingKey, fallback = null) {
                return Object.prototype.hasOwnProperty.call(vendorSettings, settingKey)
                    ? vendorSettings[settingKey]
                    : fallback;
            }

            function selectedSources() {
                const selected = boxes.filter(box => box.checked).map(box => box.value);
                return selected.length ? selected : boxes.map(box => box.value);
            }

            function collectFilterState() {
                return {
                    vendor_id: form?.querySelector('select[name="vendor_id"]')?.value || '0',
                    customer_id: form?.querySelector('select[name="customer_id"]')?.value || '0',
                    status: form?.querySelector('select[name="status"]')?.value || 'all',
                    sources: selectedSources(),
                    include_vendor_null: Boolean(vendorNullBox?.checked),
                    workorder: form?.querySelector('input[name="workorder"]')?.value || '',
                    part_number: form?.querySelector('input[name="part_number"]')?.value || '',
                    repair_order: form?.querySelector('input[name="repair_order"]')?.value || '',
                };
            }

            function setActiveFilterClass(element, active) {
                element?.classList.toggle('vendor-tracking-filter-active', Boolean(active));
            }

            function updateActiveFilterStyles() {
                const vendorSelect = form?.querySelector('select[name="vendor_id"]');
                const customerSelect = form?.querySelector('select[name="customer_id"]');
                const statusSelect = form?.querySelector('select[name="status"]');

                setActiveFilterClass(vendorSelect, vendorSelect && vendorSelect.value !== '0');
                setActiveFilterClass(customerSelect, customerSelect && customerSelect.value !== '0');
                setActiveFilterClass(statusSelect, statusSelect && statusSelect.value !== 'all');

                textFields.forEach(function (field) {
                    setActiveFilterClass(field, field.value.trim() !== '');
                    field.closest('.vendor-tracking-filter-clear-wrap')
                        ?.classList.toggle('has-value', field.value.trim() !== '');
                });

                vendorNullBox
                    ?.closest('.vendor-tracking-check')
                    ?.classList.toggle('is-filter-active', Boolean(vendorNullBox.checked));

                const allSourcesSelected = boxes.length > 0 && boxes.every(function (box) {
                    return box.checked;
                });

                boxes.forEach(function (box) {
                    box.closest('.vendor-tracking-check')
                        ?.classList.toggle('is-filter-active', !allSourcesSelected && box.checked);
                });
            }

            function persistSources() {
                const filters = collectFilterState();
                window.UserUiSettings.set(settingsScope, key, filters.sources);
                if (vendorNullBox) {
                    window.UserUiSettings.set(settingsScope, vendorNullKey, filters.include_vendor_null);
                }
                window.UserUiSettings.set(settingsScope, filtersStateKey, filters);
                updateActiveFilterStyles();
            }

            function markFilteringBeforeSubmit() {
                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                }

                if (!resultsCard) {
                    return;
                }

                const tableWrap = resultsCard.querySelector('.table-responsive');
                resultsCard.style.setProperty('--vendor-tracking-results-min-height', Math.ceil(resultsCard.getBoundingClientRect().height) + 'px');
                if (tableWrap) {
                    resultsCard.style.setProperty('--vendor-tracking-table-min-height', Math.ceil(tableWrap.getBoundingClientRect().height) + 'px');
                }
                resultsCard.classList.add('is-filtering');
            }

            function submitFilters() {
                markFilteringBeforeSubmit();
                form.requestSubmit();
            }

            function sanitizeColumns(selected, allowed, fallback, required) {
                const values = Array.isArray(selected) ? selected : [];
                const normalized = values.filter(value => allowed.includes(value));
                const result = normalized.length ? normalized : [];
                (required || []).forEach(function (keyValue) {
                    if (allowed.includes(keyValue) && !result.includes(keyValue)) {
                        result.push(keyValue);
                    }
                });
                return result.length ? result : [...fallback];
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
                const columns = sanitizeColumns(settingValue(screenColumnsKey, null), screenColumnDefs.map(def => def.key), defaultScreenColumns, ['process']);
                if (settingValue(settingsVersionKey, null) !== currentSettingsVersion && !columns.includes('part_name')) {
                    columns.splice(Math.max(0, columns.indexOf('part_number') + 1), 0, 'part_name');
                }
                if (settingValue(settingsVersionKey, null) !== currentSettingsVersion && !columns.includes('changed_at')) {
                    columns.push('changed_at');
                }
                return columns;
            }

            function getStoredExcelColumns() {
                const columns = sanitizeColumns(settingValue(excelColumnsKey, null), excelColumnDefs.map(def => def.key), defaultExcelColumns, ['process']);
                if (settingValue(settingsVersionKey, null) !== currentSettingsVersion && !columns.includes('part_name')) {
                    columns.splice(Math.max(0, columns.indexOf('part_number') + 1), 0, 'part_name');
                }
                if (settingValue(settingsVersionKey, null) !== currentSettingsVersion && !columns.includes('changed_at')) {
                    columns.push('changed_at');
                }
                return columns;
            }

            function getStoredScreenColumnOrder() {
                return sanitizeColumnOrder(settingValue(screenColumnOrderKey, null), screenColumnDefs, defaultScreenColumns);
            }

            function getStoredExcelColumnOrder() {
                return sanitizeColumnOrder(settingValue(excelColumnOrderKey, null), excelColumnDefs, defaultExcelColumns);
            }

            function getStoredExcelTitle() {
                return String(settingValue(excelTitleKey, 'Vendor Tracking') || 'Vendor Tracking').trim() || 'Vendor Tracking';
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
                    const isRequired = keyValue === 'process';
                    return `
                        <label class="vendor-tracking-column-item" draggable="true" data-column-key="${keyValue}">
                            <span class="vendor-tracking-column-drag"><i class="bi bi-grip-vertical"></i></span>
                            <input class="form-check-input" type="checkbox" name="${inputName}" value="${keyValue}" ${values.includes(keyValue) || isRequired ? 'checked' : ''} ${isRequired ? 'disabled' : ''}>
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
                const visibleCount = document.querySelectorAll('thead [data-col]').length
                    ? Array.from(document.querySelectorAll('thead [data-col]')).filter(cell => cell.style.display !== 'none').length
                    : 17;
                const fullCount = document.querySelectorAll('#vendorTrackingTable > thead [data-col]').length || 17;

                tbody?.querySelectorAll('.vendor-tracking-empty-cell').forEach(function (cell) {
                    cell.colSpan = Math.max(1, visibleCount);
                });
                tbody?.querySelectorAll('.vendor-tracking-detail-cell').forEach(function (cell) {
                    cell.colSpan = Math.max(1, fullCount);
                });
            }

            function getOrderedSelectedColumns(columns, order) {
                const visible = Array.isArray(columns) ? columns : [];
                return sanitizeColumnOrder(order, screenColumnDefs, defaultScreenColumns).filter(function (keyValue) {
                    return visible.includes(keyValue);
                });
            }

            function reorderTableColumns(order) {
                const normalizedOrder = sanitizeColumnOrder(order, screenColumnDefs, defaultScreenColumns);
                document.querySelectorAll('#vendorTrackingTable > thead > tr, #vendorTrackingBody > tr:not(.vendor-tracking-detail-row)').forEach(function (row) {
                    const cellsByKey = {};
                    Array.from(row.children).forEach(function (cell) {
                        if (!cell.dataset.col) {
                            return;
                        }
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
                    document.querySelectorAll(`#vendorTrackingTable > thead > tr > [data-col="${def.key}"], #vendorTrackingBody > tr:not(.vendor-tracking-detail-row) > [data-col="${def.key}"]`).forEach(function (cell) {
                        cell.style.display = isVisible ? '' : 'none';
                    });
                });

                updateEmptyStateColspan();
                vendorTrackingTable?.classList.remove('is-column-settings-pending');
                vendorTrackingTable?.classList.add('is-column-settings-ready');
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
                    defaultScreenColumns,
                    ['process']
                );
                const excelColumns = sanitizeColumns(
                    Array.from(excelColumnsWrap?.querySelectorAll('input:checked') || []).map(input => input.value),
                    excelColumnDefs.map(def => def.key),
                    defaultExcelColumns,
                    ['process']
                );

                vendorSettings[screenColumnsKey] = screenColumns;
                vendorSettings[excelColumnsKey] = excelColumns;
                vendorSettings[screenColumnOrderKey] = screenOrder;
                vendorSettings[excelColumnOrderKey] = excelOrder;
                vendorSettings[excelTitleKey] = (excelTitleInput?.value || 'Vendor Tracking').trim() || 'Vendor Tracking';
                vendorSettings[settingsVersionKey] = currentSettingsVersion;
                window.UserUiSettings.set(settingsScope, screenColumnsKey, screenColumns);
                window.UserUiSettings.set(settingsScope, excelColumnsKey, excelColumns);
                window.UserUiSettings.set(settingsScope, screenColumnOrderKey, screenOrder);
                window.UserUiSettings.set(settingsScope, excelColumnOrderKey, excelOrder);
                window.UserUiSettings.set(settingsScope, excelTitleKey, vendorSettings[excelTitleKey]);
                window.UserUiSettings.set(settingsScope, settingsVersionKey, currentSettingsVersion);
                applyScreenColumns(screenColumns, screenOrder);
                settingsModal?.hide();
            }

            function resetSettings() {
                vendorSettings[screenColumnsKey] = defaultScreenColumns;
                vendorSettings[excelColumnsKey] = defaultExcelColumns;
                vendorSettings[screenColumnOrderKey] = defaultScreenColumns;
                vendorSettings[excelColumnOrderKey] = defaultExcelColumns;
                vendorSettings[excelTitleKey] = 'Vendor Tracking';
                vendorSettings[settingsVersionKey] = currentSettingsVersion;
                window.UserUiSettings.set(settingsScope, screenColumnsKey, defaultScreenColumns);
                window.UserUiSettings.set(settingsScope, excelColumnsKey, defaultExcelColumns);
                window.UserUiSettings.set(settingsScope, screenColumnOrderKey, defaultScreenColumns);
                window.UserUiSettings.set(settingsScope, excelColumnOrderKey, defaultExcelColumns);
                window.UserUiSettings.set(settingsScope, excelTitleKey, 'Vendor Tracking');
                window.UserUiSettings.set(settingsScope, settingsVersionKey, currentSettingsVersion);
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
                    submitFilters();
                });
            });

            form.addEventListener('submit', function () {
                persistSources();
                markFilteringBeforeSubmit();
            });

            autoSubmitFields.forEach(field => {
                field.addEventListener('change', function () {
                    persistSources();
                    submitFilters();
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
                    submitFilters();
                });
            });

            clearFilterButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const fieldName = button.dataset.clearFilter || '';
                    const field = fieldName ? form.querySelector(`input[name="${fieldName}"]`) : null;
                    if (!field || field.value === '') {
                        return;
                    }

                    field.value = '';
                    persistSources();
                    submitFilters();
                });
            });

            vendorNullBox?.addEventListener('change', function () {
                persistSources();
                submitFilters();
            });

            updateActiveFilterStyles();
            persistSources();

            settingsBtn?.addEventListener('click', openSettingsModal);
            settingsSaveBtn?.addEventListener('click', saveSettings);
            settingsResetBtn?.addEventListener('click', resetSettings);
            exportBtn?.addEventListener('click', function (event) {
                event.preventDefault();
                window.location.href = buildExportUrl();
            });
            document.querySelectorAll('.vendor-tracking-sort-link').forEach(function (link) {
                link.addEventListener('click', markFilteringBeforeSubmit);
            });

            applyScreenColumns(getStoredScreenColumns(), getStoredScreenColumnOrder());

            if (!tbody || !paginationWrap || !loadMoreIndicator) {
                return;
            }

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

            const currentCsrfToken = function () {
                return csrfMeta?.getAttribute('content') || csrfToken;
            };

            const setCsrfToken = function (token) {
                if (!token) {
                    return;
                }

                csrfToken = token;
                csrfMeta?.setAttribute('content', token);
            };

            const refreshCsrfToken = async function () {
                try {
                    const response = await fetch(heartbeatUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                        ignoreSessionExpiry: true,
                    });

                    if (!response.ok) {
                        return false;
                    }

                    const data = await response.json();
                    if (!data.csrf_token) {
                        return false;
                    }

                    setCsrfToken(data.csrf_token);
                    return true;
                } catch (error) {
                    return false;
                }
            };

            const fetchTrackingRowUpdate = async function (row, payload) {
                const request = function () {
                    return fetch(updateUrl, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        ignoreSessionExpiry: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': currentCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            id: Number(row.dataset.rowId),
                            source_key: row.dataset.sourceKey,
                            traveler_group: Number(row.dataset.travelerGroup || 0) || null,
                            vendor_id: payload.vendor_id,
                        }),
                    });
                };

                let response = await request();
                if (response.status === 419 && await refreshCsrfToken()) {
                    response = await request();
                }

                return response;
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
                const promiseInput = row.querySelector('.js-vendor-tracking-date[name="date_promise"]');

                setVisibleInvalid(promiseInput, false);
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

                    if (promiseInput) {
                        const promiseValue = data.date_promise ?? '';
                        promiseInput.value = promiseValue;
                        promiseInput.dataset.lastSavedValue = promiseValue;
                        promiseInput.dataset.original = promiseValue;
                        if (promiseInput._flatpickr) {
                            if (promiseValue) {
                                promiseInput._flatpickr.setDate(promiseValue, false, 'Y-m-d');
                            } else {
                                promiseInput._flatpickr.clear(false);
                            }
                        }
                        refreshFinishInputState(promiseInput);
                    }

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

                row._vendorTrackingSaveSeq = (row._vendorTrackingSaveSeq || 0) + 1;
                const saveSeq = row._vendorTrackingSaveSeq;
                const vendorCell = row.querySelector('.js-vendor-tracking-vendor')?.closest('td');
                const cells = [vendorCell].filter(Boolean);

                cells.forEach(function (cell) {
                    setCellState(cell, 'is-saving');
                });

                try {
                    const response = await fetchTrackingRowUpdate(row, payload);
                    const contentType = response.headers.get('Content-Type') || '';
                    const data = contentType.indexOf('application/json') !== -1
                        ? await response.json()
                        : {};

                    if (!response.ok || data.success === false) {
                        throw data;
                    }

                    if (saveSeq !== row._vendorTrackingSaveSeq) {
                        return;
                    }

                    const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');
                    if (vendorSelect) {
                        vendorSelect.value = payload.vendor_id ? String(payload.vendor_id) : '';
                        vendorSelect.dataset.lastSavedValue = vendorSelect.value;
                    }

                    updateRowVendorButtons(row, payload.vendor_id ? Number(payload.vendor_id) : null);
                    cells.forEach(markSaved);
                } catch (error) {
                    if (saveSeq !== row._vendorTrackingSaveSeq) {
                        return;
                    }

                    const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');

                    if (vendorSelect) {
                        vendorSelect.value = vendorSelect.dataset.lastSavedValue ?? '';
                    }

                    updateRowVendorButtons(row, vendorSelect && vendorSelect.value !== '' ? Number(vendorSelect.value) : null);
                    cells.forEach(flashError);
                } finally {
                    if (saveSeq !== row._vendorTrackingSaveSeq) {
                        return;
                    }

                    cells.forEach(function (cell) {
                        cell.classList.remove('is-saving');
                    });
                }
            };

            tbody.querySelectorAll('tr').forEach(initRow);

            tbody.addEventListener('click', function (event) {
                const roFilterButton = event.target.closest('.js-vendor-tracking-ro-filter');
                if (roFilterButton) {
                    const repairOrder = roFilterButton.dataset.repairOrder || '';
                    const repairOrderFilter = form?.querySelector('input[name="repair_order"]');
                    if (!repairOrder || !repairOrderFilter) {
                        return;
                    }

                    repairOrderFilter.value = repairOrder;
                    persistSources();
                    submitFilters();
                    return;
                }

                const ungroupButton = event.target.closest('.js-vendor-traveler-ungroup');
                if (ungroupButton) {
                    const url = ungroupButton.dataset.ungroupUrl;
                    if (!url) {
                        return;
                    }

                    ungroupButton.disabled = true;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            traveler_group: Number(ungroupButton.dataset.travelerGroup || 0),
                        }),
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Failed to ungroup traveler');
                            }
                            return response.json();
                        })
                        .then(function () {
                            window.location.reload();
                        })
                        .catch(function () {
                            ungroupButton.disabled = false;
                            flashError(ungroupButton.closest('th'));
                        });
                    return;
                }

                const formLink = event.target.closest('.js-vendor-tracking-form-link');
                if (formLink) {
                    const detailRow = formLink.closest('.vendor-tracking-detail-row');
                    let row = formLink.closest('tr[data-row-id]');
                    if (detailRow) {
                        const rowKey = detailRow.dataset.travelerDetailFor;
                        row = tbody.querySelector(`tr[data-row-key="${CSS.escape(rowKey)}"]`) || detailRow;
                    }
                    const vendorSelect = row?.querySelector('.js-vendor-tracking-vendor');
                    const url = new URL(formLink.getAttribute('href'), window.location.origin);
                    if (vendorSelect && vendorSelect.value) {
                        url.searchParams.set('vendor_id', vendorSelect.value);
                    } else {
                        url.searchParams.delete('vendor_id');
                    }
                    formLink.setAttribute('href', url.toString());
                    return;
                }

                const toggle = event.target.closest('.js-vendor-traveler-toggle');
                if (!toggle) {
                    return;
                }

                const row = toggle.closest('tr');
                const rowKey = row?.dataset.rowKey || row?.dataset.rowId;
                if (!rowKey) {
                    return;
                }

                const detailRow = tbody.querySelector(`.vendor-tracking-detail-row[data-traveler-detail-for="${CSS.escape(rowKey)}"]`);
                if (!detailRow) {
                    return;
                }

                const isOpen = !detailRow.classList.contains('d-none');
                detailRow.classList.toggle('d-none', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                toggle.title = isOpen ? 'Show traveler processes' : 'Hide traveler processes';
            });

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
                });
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
