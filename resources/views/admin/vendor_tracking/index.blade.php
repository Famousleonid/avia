@extends('admin.master')

@section('style')
    <style>
        .content:has(.vendor-tracking-page) {
            overflow: hidden !important;
            padding-top: 0 !important;
        }

        html[data-bs-theme="light"] .content:has(.vendor-tracking-page),
        html[data-bs-theme="light"] .content:has(.vendor-tracking-page) .content-inner {
            background: #ffffff !important;
        }

        .content:has(.vendor-tracking-page) .content-inner {
            display: flex !important;
            flex-direction: column;
            height: 100% !important;
            min-height: 0;
        }

        .vendor-tracking-page .card,
        .vendor-tracking-page .btn,
        .vendor-tracking-page .form-control,
        .vendor-tracking-page .form-select {
            border-radius: 8px;
        }

        .vendor-tracking-page {
            color: #1f2937;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
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
            flex: 0 0 auto;
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
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            position: relative;
        }

        .vendor-tracking-results-card > .card-body {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .vendor-tracking-results-card > .card-body > .table-responsive {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
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

        .vendor-tracking-quantum-sync-badge {
            gap: .35rem;
        }

        .vendor-tracking-quantum-sync-badge.is-ok {
            border-color: #86efac;
            color: #14532d;
            background: #f0fdf4;
        }

        .vendor-tracking-quantum-sync-badge.is-warning {
            border-color: #f59e0b;
            color: #713f12;
            background: #fffbeb;
        }

        .vendor-tracking-quantum-sync-badge.is-stale {
            border-color: #fca5a5;
            color: #7f1d1d;
            background: #fef2f2;
        }

        .vendor-tracking-quantum-sync-badge.is-never,
        .vendor-tracking-quantum-sync-badge.is-unknown,
        .vendor-tracking-quantum-sync-badge.is-unavailable {
            border-color: #cbd5e1;
            color: #475569;
            background: #f8fafc;
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

        .vendor-tracking-toolbar-btn.btn-outline-danger {
            color: #7f1d1d;
            border-color: #ef4444;
            background: #fef2f2;
        }

        .vendor-tracking-toolbar-btn.btn-outline-danger:hover,
        .vendor-tracking-toolbar-btn.btn-outline-danger:focus {
            color: #450a0a;
            background: #fee2e2;
            border-color: #dc2626;
        }

        .quantum-buffer-modal {
            --quantum-buffer-modal-margin: 3rem;
            --quantum-buffer-splitter-size: 18px;
            --quantum-unparsed-height: 42%;
        }

        .quantum-buffer-modal .modal-dialog {
            height: calc(100vh - (var(--quantum-buffer-modal-margin) * 2));
            margin: var(--quantum-buffer-modal-margin) auto;
            max-width: calc(100vw - (var(--quantum-buffer-modal-margin) * 2));
            width: calc(100vw - (var(--quantum-buffer-modal-margin) * 2));
        }

        @supports (height: 100dvh) {
            .quantum-buffer-modal .modal-dialog {
                height: calc(100dvh - (var(--quantum-buffer-modal-margin) * 2));
            }
        }

        .quantum-buffer-modal .modal-content {
            height: 100%;
            max-height: 100%;
        }

        .quantum-buffer-modal .modal-header,
        .quantum-buffer-modal .modal-footer {
            flex: 0 0 auto;
            padding: .35rem .75rem;
        }

        .quantum-buffer-modal .modal-header {
            gap: .75rem;
        }

        .quantum-buffer-modal .modal-title {
            font-size: 1rem;
            line-height: 1.2;
        }

        .quantum-buffer-modal .modal-body {
            display: grid;
            flex: 1 1 auto;
            gap: .45rem;
            grid-template-rows: minmax(150px, var(--quantum-unparsed-height, 42%)) var(--quantum-buffer-splitter-size) minmax(170px, 1fr);
            max-height: none;
            min-height: 0;
            overflow: hidden;
            padding: .55rem .75rem;
        }

        .quantum-buffer-title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: .35rem .8rem;
        }

        .quantum-buffer-title-meta {
            font-size: .74rem;
            color: #64748b;
        }

        .quantum-buffer-sync-meta {
            flex-basis: 100%;
            font-size: .74rem;
            color: #64748b;
        }

        .quantum-buffer-sync-meta .badge {
            font-weight: 600;
        }

        .quantum-buffer-header-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            flex: 1 1 520px;
            gap: .45rem .75rem;
            justify-content: flex-end;
            margin-left: auto;
            min-width: 420px;
        }

        .quantum-buffer-search-group {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .3rem;
            justify-content: flex-start;
            margin-right: auto;
            position: relative;
        }

        .quantum-buffer-search-form {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            white-space: nowrap;
        }

        .quantum-buffer-status-counts {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .25rem .35rem;
            margin-left: .35rem;
            vertical-align: middle;
        }

        .quantum-buffer-status-count {
            align-items: center;
            border: 1px solid #d7e0ea;
            border-radius: 999px;
            display: inline-flex;
            gap: .25rem;
            line-height: 1.1;
            padding: .12rem .38rem;
            white-space: nowrap;
        }

        .quantum-buffer-status-count strong {
            color: #334155;
            font-weight: 700;
        }

        .quantum-buffer-action-col {
            width: 1%;
            white-space: nowrap;
        }

        .quantum-buffer-table-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            background: #ffffff;
        }

        .quantum-buffer-section {
            display: flex;
            min-height: 0;
            overflow: hidden;
            flex-direction: column;
            gap: .45rem;
        }

        .quantum-buffer-unparsed-section {
            min-height: 0;
        }

        .quantum-buffer-recent-section {
            min-height: 0;
        }

        .quantum-buffer-recent-section.is-collapsed {
            display: none;
        }

        .quantum-buffer-recent-section.is-collapsed .quantum-buffer-recent-wrap,
        .quantum-buffer-recent-section.is-collapsed #quantumRecentLoadState {
            display: none !important;
        }

        .quantum-buffer-modal.is-recent-hidden .modal-body {
            grid-template-rows: minmax(0, 1fr);
        }

        .quantum-buffer-modal.is-recent-hidden .quantum-buffer-unparsed-section {
            min-height: 0;
        }

        .quantum-buffer-modal.is-recent-hidden .quantum-buffer-splitter {
            display: none;
        }

        .quantum-buffer-modal.is-recent-hidden .quantum-buffer-unparsed-wrap {
            flex: 1 1 auto;
            max-height: none;
        }

        .quantum-buffer-section-toggle {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .quantum-buffer-header-toggle {
            font-size: .78rem;
            color: #334155;
            font-weight: 600;
        }

        .quantum-buffer-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
        }

        .quantum-buffer-section-title {
            font-size: .9rem;
            font-weight: 700;
            color: #334155;
        }

        .quantum-buffer-recent-tools {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
        }

        .quantum-buffer-wo-search,
        .quantum-buffer-ro-search {
            width: 112px;
            min-height: 28px;
        }

        .quantum-buffer-search-status {
            font-size: .72rem;
            min-width: 0;
            max-width: 230px;
            line-height: 1.15;
            overflow: hidden;
            pointer-events: none;
            position: absolute;
            right: calc(100% + .45rem);
            text-align: right;
            text-overflow: ellipsis;
            top: 50%;
            transform: translateY(-50%);
            white-space: nowrap;
        }

        .quantum-buffer-search-status:empty {
            display: none;
        }

        .quantum-buffer-recent-wrap {
            flex: 1 1 auto;
            min-height: 0;
        }

        .quantum-buffer-unparsed-wrap {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
        }

        .quantum-buffer-splitter {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            cursor: row-resize;
            display: flex;
            justify-content: center;
            min-height: var(--quantum-buffer-splitter-size);
            outline: none;
            touch-action: none;
            user-select: none;
        }

        .quantum-buffer-splitter:hover,
        .quantum-buffer-splitter:focus {
            border-color: rgba(13, 202, 240, .65);
            background: #eef8fb;
        }

        .quantum-buffer-splitter-lines {
            display: grid;
            gap: 3px;
            width: 46px;
        }

        .quantum-buffer-splitter-lines span {
            background: #94a3b8;
            border-radius: 999px;
            display: block;
            height: 2px;
        }

        .quantum-buffer-splitter.is-dragging .quantum-buffer-splitter-lines span {
            background: #0dcaf0;
        }

        body.quantum-buffer-is-resizing {
            cursor: row-resize;
            user-select: none;
        }

        .quantum-buffer-table {
            min-width: 1320px;
            margin-bottom: 0;
        }

        .quantum-buffer-table.is-compact {
            min-width: 1240px;
            font-size: .78rem;
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
            color: #64748b;
            max-width: 340px;
            white-space: normal;
        }

        .quantum-buffer-message .quantum-message-muted {
            color: #64748b;
        }

        .quantum-buffer-message .quantum-message-info {
            color: #0dcaf0;
            font-weight: 400;
        }

        .quantum-buffer-message .quantum-message-wo {
            color: #ffffff;
            font-weight: 400;
        }

        .vendor-tracking-page .table-responsive {
            background: var(--avia-panel);
            border-radius: 10px;
        }

        .vendor-tracking-table {
            --bs-table-bg: var(--avia-panel);
            --bs-table-color: var(--avia-text);
            --bs-table-border-color: #d7e0ea;
            margin-bottom: 0;
            background: var(--avia-panel);
        }

        .vendor-tracking-table > thead > tr > th {
            background: var(--avia-surface-raised);
            color: var(--avia-text);
            border-color: #d7e0ea;
            position: sticky;
            top: 0;
            vertical-align: middle;
            z-index: 4;
        }

        .vendor-tracking-table > tbody > tr > td {
            background: var(--avia-panel);
            color: var(--avia-text);
            border-color: #d7e0ea;
            vertical-align: middle;
        }

        .vendor-tracking-traveler-row > td {
            background: var(--avia-panel) !important;
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
            background: var(--avia-panel) !important;
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
            --dir-table-bg: var(--avia-surface-raised);
            background: var(--avia-surface-raised);
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
            --dir-table-bg: var(--avia-surface-raised);
            background: var(--avia-surface-raised);
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
            background: var(--avia-surface-raised);
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
            color: var(--avia-text);
            background: var(--avia-bg) !important;
        }

        html[data-bs-theme="dark"] .content:has(.vendor-tracking-page),
        html[data-bs-theme="dark"] .content:has(.vendor-tracking-page) .content-inner {
            background: var(--avia-bg) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .card {
            background: var(--avia-surface) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: none;
        }

        html[data-bs-theme="dark"] .vendor-tracking-sticky-shell {
            background: var(--avia-surface);
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
            background: var(--avia-panel);
        }

        html[data-bs-theme="dark"] .vendor-tracking-loading-dots {
            background: rgba(var(--bs-dark-rgb), .78);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table {
            --bs-table-bg: var(--avia-panel);
            --bs-table-color: var(--avia-text);
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
            background: var(--avia-panel);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > thead > tr > th {
            background: var(--avia-surface-raised);
            color: var(--avia-text-secondary);
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-table > tbody > tr > td {
            background: var(--avia-panel);
            color: var(--avia-text);
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .vendor-tracking-traveler-row > td {
            background: var(--avia-panel) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-cell {
            background: var(--avia-panel) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-panel {
            --dir-table-bg: var(--avia-surface-raised);
            background: var(--avia-surface-raised);
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-table th,
        html[data-bs-theme="dark"] .vendor-tracking-detail-table td {
            color: var(--avia-text);
        }

        html[data-bs-theme="dark"] .vendor-tracking-detail-layout,
        html[data-bs-theme="dark"] .vendor-tracking-detail-action-col {
            --dir-table-bg: var(--avia-surface-raised);
            background: var(--avia-surface-raised);
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
            color: var(--avia-text-secondary) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-page .vendor-tracking-filter-active {
            border-color: #0dcaf0 !important;
            box-shadow: 0 0 0 .14rem rgba(13, 202, 240, .24) !important;
        }

        html[data-bs-theme="dark"] .vendor-tracking-filter-clear {
            color: var(--avia-text-secondary);
        }

        html[data-bs-theme="dark"] #vendorTrackingSettingsModal .modal-content,
        html[data-bs-theme="dark"] #vendorInfoModal .modal-content,
        html[data-bs-theme="dark"] #quantumRoBufferModal .modal-content {
            background: var(--avia-modal);
            color: var(--avia-text);
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

        html[data-bs-theme="dark"] .quantum-buffer-section-title {
            color: #e5e7eb;
        }

        html[data-bs-theme="dark"] .quantum-buffer-title-meta {
            color: var(--avia-text-secondary);
        }

        html[data-bs-theme="dark"] .quantum-buffer-header-toggle {
            color: var(--avia-text);
        }

        html[data-bs-theme="dark"] .quantum-buffer-status-count {
            border-color: rgba(255, 255, 255, 0.14);
        }

        html[data-bs-theme="dark"] .quantum-buffer-status-count strong {
            color: #e5e7eb;
        }

        html[data-bs-theme="dark"] .quantum-buffer-table-wrap {
            border-color: rgba(255, 255, 255, 0.12);
            background: var(--avia-panel);
        }

        html[data-bs-theme="dark"] .quantum-buffer-table {
            --bs-table-bg: var(--avia-panel);
            --bs-table-color: var(--avia-text);
            --bs-table-border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .quantum-buffer-table th {
            background: var(--avia-input);
            color: var(--avia-text-secondary);
        }

        html[data-bs-theme="dark"] .quantum-buffer-message,
        html[data-bs-theme="dark"] .quantum-buffer-message .quantum-message-muted {
            color: var(--avia-text-muted);
        }

        html[data-bs-theme="dark"] .quantum-buffer-splitter {
            background: var(--avia-input);
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-bs-theme="dark"] .quantum-buffer-splitter:hover,
        html[data-bs-theme="dark"] .quantum-buffer-splitter:focus {
            background: #26313a;
            border-color: rgba(13, 202, 240, .55);
        }

        html[data-bs-theme="dark"] .quantum-buffer-splitter-lines span {
            background: #adb5bd;
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
    <div class="container-fluid vendor-tracking-page mt-1 mb-0">
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
            $quantumRoQty = static function ($value): string {
                if ($value === null || $value === '') {
                    return '--';
                }

                return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
            };
            $quantumStatusCounts = $quantumStatusCounts ?? [];
            $quantumStatusCountItems = [
                ['key' => 'unresolved', 'label' => 'unresolved'],
                ['key' => 'pending', 'label' => 'pending'],
                ['key' => 'wo_not_found', 'label' => 'WO not found'],
                ['key' => 'wo_not_found_old', 'label' => 'WO not found: old'],
                ['key' => 'applied', 'label' => 'applied'],
                ['key' => 'eco_fee', 'label' => 'ECO FEE'],
                ['key' => 'not_applicable', 'label' => 'N/A'],
                ['key' => 'dismissed', 'label' => 'dismissed'],
                ['key' => 'error', 'label' => 'error'],
            ];
            $quantumSyncHealth = $quantumSyncHealth ?? [];
            $quantumSyncStatus = $quantumSyncHealth['status'] ?? 'unavailable';
            $quantumSyncDateTime = static function ($value): string {
                if (! $value) {
                    return '--';
                }

                try {
                    $date = $value instanceof \Carbon\CarbonInterface
                        ? $value
                        : \Carbon\Carbon::parse($value);

                    return (format_project_date($date) ?? '--') . ' ' . $date->format('H:i');
                } catch (\Throwable) {
                    return '--';
                }
            };
            $quantumSyncLastRunLabel = $quantumSyncDateTime($quantumSyncHealth['last_run_at'] ?? null);
            $quantumSyncLastLineLabel = $quantumSyncDateTime($quantumSyncHealth['last_line_seen_at'] ?? null);
            $quantumSyncRowsReceived = number_format((int) ($quantumSyncHealth['rows_received'] ?? 0));
            $quantumSyncRowsChanged = number_format(
                (int) ($quantumSyncHealth['rows_inserted'] ?? 0)
                + (int) ($quantumSyncHealth['rows_updated'] ?? 0)
            );
        @endphp
        <div class="vendor-tracking-sticky-shell">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="vendor-tracking-headline">
                    <h5 class="mb-0">Vendor Tracking</h5>
                    <div class="text-muted small">STD, part, and bushing processes sent to vendors by repair order and dates.</div>
                    <div class="vendor-tracking-counts">
                        <span class="vendor-tracking-count-badge">Selected: &nbsp; <span class="vendor-tracking-count-number">{{ number_format($summary['filtered_total'] ?? 0) }}</span></span>
                        <span class="vendor-tracking-count-badge">Total: &nbsp; <span class="vendor-tracking-count-number">{{ number_format($summary['total_rows'] ?? 0) }}</span></span>
                        <span
                            class="vendor-tracking-count-badge vendor-tracking-quantum-sync-badge is-{{ $quantumSyncStatus }}"
                            title="{{ $quantumSyncHealth['message'] ?? 'Quantum sync status' }}"
                        >
                            <i class="bi {{ $quantumSyncHealth['icon'] ?? 'bi-database-x' }}"></i>
                            Quantum sync:
                            <strong>{{ $quantumSyncHealth['label'] ?? 'Unavailable' }}</strong>
                            <span>{{ $quantumSyncHealth['age_label'] ?? '--' }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn {{ $quantumSyncHealth['button_class'] ?? 'btn-outline-warning' }} btn-sm vendor-tracking-toolbar-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#quantumRoBufferModal"
                        title="{{ $quantumSyncHealth['message'] ?? 'Quantum buffer unresolved rows' }} Last run: {{ $quantumSyncLastRunLabel }}"
                    >
                        <i class="bi {{ $quantumSyncHealth['icon'] ?? 'bi-database-exclamation' }} me-1"></i> Quantum
                        <span
                            class="badge text-bg-warning ms-1 {{ ($quantumUnparsedTotal ?? 0) > 0 ? '' : 'd-none' }}"
                            id="quantumToolbarUnparsedCount"
                        >{{ number_format($quantumUnparsedTotal ?? 0) }}</span>
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
                                    <td colspan="17" class="text-muted text-center py-4 vendor-tracking-empty-cell">
                                        @if($completedWorkorderSearch)
                                            Workorder W{{ $completedWorkorderSearch->number }} is already completed
                                            @if($completedWorkorderSearch->done_at)
                                                ({{ format_project_date($completedWorkorderSearch->done_at) }})
                                            @endif
                                            and is not shown in Vendor Tracking.
                                        @else
                                            No vendor process records found.
                                        @endif
                                    </td>
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
                    <div class="quantum-buffer-title-row">
                        <h5 class="modal-title mb-0">Quantum RO Buffer</h5>
                        <div class="quantum-buffer-title-meta">
                            Latest received:
                            <span id="quantumRecentTotalCount">{{ number_format($quantumRecentTotal ?? 0) }}</span>
                            total rows
                            &middot; unresolved:
                            <span id="quantumUnparsedTotalCount">{{ number_format($quantumUnparsedTotal ?? 0) }}</span>
                            &middot; sync:
                            <span class="badge {{ $quantumSyncHealth['badge_class'] ?? 'text-bg-secondary' }}">{{ $quantumSyncHealth['label'] ?? 'Unavailable' }}</span>
                            {{ $quantumSyncHealth['age_label'] ?? '--' }}
                        </div>
                        <div class="quantum-buffer-sync-meta">
                            Last run: {{ $quantumSyncLastRunLabel }}
                            @if(! empty($quantumSyncHealth['last_run_id']))
                                &middot; run #{{ $quantumSyncHealth['last_run_id'] }}
                            @endif
                            @if(! empty($quantumSyncHealth['last_run_status']))
                                &middot; status: {{ $quantumSyncHealth['last_run_status'] }}
                            @endif
                            &middot; rows: {{ $quantumSyncRowsReceived }} received / {{ $quantumSyncRowsChanged }} changed
                            &middot; last row seen: {{ $quantumSyncLastLineLabel }}
                        </div>
                    </div>
                    <div class="quantum-buffer-header-actions">
                        <div class="quantum-buffer-search-group">
                            <span id="quantumWorkorderSearchStatus" class="quantum-buffer-search-status"></span>
                            <form
                                id="quantumWorkorderSearchForm"
                                class="quantum-buffer-search-form"
                                data-find-url="{{ route('vendor-tracking.quantum-lines.find-workorder') }}"
                                data-search-mode="wo"
                                data-no-spinner
                            >
                                <input
                                    type="search"
                                    id="quantumWorkorderSearch"
                                    class="form-control form-control-sm quantum-buffer-wo-search"
                                    placeholder="WO in Reason"
                                    inputmode="numeric"
                                    autocomplete="off"
                                >
                                <button type="submit" class="btn btn-outline-primary btn-sm" title="Filter Reason by WO">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                            <form
                                id="quantumRepairOrderSearchForm"
                                class="quantum-buffer-search-form"
                                data-find-url="{{ route('vendor-tracking.quantum-lines.find-workorder') }}"
                                data-search-mode="ro"
                                data-no-spinner
                            >
                                <input
                                    type="search"
                                    id="quantumRepairOrderSearch"
                                    class="form-control form-control-sm quantum-buffer-ro-search"
                                    placeholder="RO filter"
                                    inputmode="numeric"
                                    autocomplete="off"
                                >
                                <button type="submit" class="btn btn-outline-primary btn-sm" title="Search RO">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                        </div>
                        <label class="quantum-buffer-section-toggle quantum-buffer-header-toggle mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input mt-0"
                                id="quantumRecentVisibleToggle"
                                checked
                            >
                            <span>Show latest</span>
                        </label>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="quantum-buffer-section quantum-buffer-unparsed-section">
                        <div class="quantum-buffer-section-head">
                            <div class="quantum-buffer-section-title">Needs attention</div>
                            <div class="quantum-buffer-recent-tools small text-muted">Unresolved rows only &middot; completed WO excluded</div>
                        </div>
                        <div class="quantum-buffer-table-wrap quantum-buffer-unparsed-wrap">
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
                                        <th class="quantum-buffer-action-col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="quantumUnparsedRowsBody">
                                    @include('admin.vendor_tracking.partials.quantum_unparsed_rows', ['quantumUnparsedRows' => $quantumUnparsedRows])
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="quantum-buffer-splitter"
                        id="quantumBufferSplitter"
                        role="separator"
                        aria-orientation="horizontal"
                        aria-label="Resize Quantum buffer tables"
                        tabindex="0"
                        title="Resize"
                    >
                        <span class="quantum-buffer-splitter-lines" aria-hidden="true">
                            <span></span>
                            <span></span>
                        </span>
                    </div>

                    <div class="quantum-buffer-section quantum-buffer-recent-section">
                        <div class="quantum-buffer-section-head">
                            <div class="quantum-buffer-section-title">
                                Latest received from Quantum
                                <span class="quantum-buffer-status-counts">
                                    @foreach($quantumStatusCountItems as $countItem)
                                        @php($countValue = (int) ($quantumStatusCounts[$countItem['key']] ?? 0))
                                        <span class="quantum-buffer-status-count {{ $countValue > 0 ? '' : 'd-none' }}" data-quantum-status-count="{{ $countItem['key'] }}">
                                            {{ $countItem['label'] }}
                                            <strong data-quantum-status-value>{{ number_format($countValue) }}</strong>
                                        </span>
                                    @endforeach
                                </span>
                            </div>
                            <div class="quantum-buffer-recent-tools small text-muted">
                                <span title="Local audit buffer of the latest rows received from Quantum, including their apply result and target">Audit log &middot; all statuses &middot; completed WO excluded</span>
                                @if(($quantumRecentRows ?? collect())->count() < ($quantumRecentTotal ?? 0))
                                    <span>&middot; scroll to load more</span>
                                @endif
                            </div>
                        </div>
                        <div
                            class="quantum-buffer-table-wrap quantum-buffer-recent-wrap js-quantum-recent-scroll"
                            data-fetch-url="{{ route('vendor-tracking.quantum-lines.recent') }}"
                            data-next-page="{{ ($quantumRecentRows ?? collect())->count() < ($quantumRecentTotal ?? 0) ? 2 : '' }}"
                            data-has-more="{{ ($quantumRecentRows ?? collect())->count() < ($quantumRecentTotal ?? 0) ? '1' : '0' }}"
                        >
                            <table class="table table-sm table-bordered align-middle quantum-buffer-table is-compact">
                                <thead>
                                    <tr>
                                        <th>Seen</th>
                                        <th>Status</th>
                                        <th>RO</th>
                                        <th>WO</th>
                                        <th>Vendor</th>
                                        <th>PN</th>
                                        <th>Class</th>
                                        <th>Ref</th>
                                        <th>Sent</th>
                                        <th>Returned</th>
                                        <th>Target</th>
                                        <th>Message</th>
                                        <th class="quantum-buffer-action-col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="quantumRecentRowsBody">
                                    @if(($quantumRecentRows ?? collect())->isNotEmpty())
                                        @include('admin.vendor_tracking.partials.quantum_recent_rows', ['quantumRecentRows' => $quantumRecentRows])
                                    @else
                                        <tr class="js-quantum-recent-empty">
                                            <td colspan="13" class="text-center text-muted py-4">No Quantum RO rows received yet.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="quantum-buffer-load-more small text-muted text-center py-2 d-none" id="quantumRecentLoadState">Loading more rows...</div>
                        </div>
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
            const quantumRecentVisibleKey = 'quantumRecentVisible';
            const quantumSplitRatioKey = 'quantumRoBufferSplitRatio';
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

            const quantumRecentScroll = document.querySelector('.js-quantum-recent-scroll');
            const quantumRecentBody = document.getElementById('quantumRecentRowsBody');
            const quantumUnparsedBody = document.getElementById('quantumUnparsedRowsBody');
            const quantumRecentLoadState = document.getElementById('quantumRecentLoadState');
            const quantumWorkorderSearchForm = document.getElementById('quantumWorkorderSearchForm');
            const quantumWorkorderSearchInput = document.getElementById('quantumWorkorderSearch');
            const quantumRepairOrderSearchForm = document.getElementById('quantumRepairOrderSearchForm');
            const quantumRepairOrderSearchInput = document.getElementById('quantumRepairOrderSearch');
            const quantumWorkorderSearchStatus = document.getElementById('quantumWorkorderSearchStatus');
            const quantumBufferModalEl = document.getElementById('quantumRoBufferModal');
            const quantumBufferModalBody = quantumBufferModalEl?.querySelector('.modal-body') || null;
            const quantumUnparsedSection = document.querySelector('.quantum-buffer-unparsed-section');
            const quantumRecentSection = document.querySelector('.quantum-buffer-recent-section');
            const quantumBufferSplitter = document.getElementById('quantumBufferSplitter');
            const quantumRecentVisibleToggle = document.getElementById('quantumRecentVisibleToggle');
            let isLoadingQuantumRecent = false;
            let activeQuantumFilter = { mode: null, search: '' };
            let quantumSplitRatio = Number(settingValue(quantumSplitRatioKey, 0.42));

            const quantumBodyGridGap = function () {
                if (!quantumBufferModalBody) {
                    return 0;
                }

                const styles = window.getComputedStyle(quantumBufferModalBody);
                const rowGap = parseFloat(styles.rowGap || styles.gap || '0');

                return Number.isFinite(rowGap) ? rowGap : 0;
            };

            const setQuantumRecentVisible = function (visible, persist = false) {
                quantumRecentSection?.classList.toggle('is-collapsed', !visible);
                quantumBufferModalEl?.classList.toggle('is-recent-hidden', !visible);
                if (quantumRecentVisibleToggle) {
                    quantumRecentVisibleToggle.checked = visible;
                }
                if (persist) {
                    window.UserUiSettings.set(settingsScope, quantumRecentVisibleKey, visible);
                }
                requestAnimationFrame(function () {
                    applyQuantumSplitRatio(quantumSplitRatio, false);
                });
            };

            const clampQuantumSplitHeight = function (height) {
                if (!quantumBufferModalBody) {
                    return height;
                }

                const bodyHeight = quantumBufferModalBody.clientHeight || 0;
                const splitterHeight = quantumBufferSplitter?.offsetHeight || 0;
                const minTop = 150;
                const minBottom = 170;
                const rowGaps = quantumBodyGridGap() * 2;
                const availableHeight = Math.max(0, bodyHeight - splitterHeight - rowGaps);
                const maxTop = Math.max(minTop, availableHeight - minBottom);

                return Math.max(minTop, Math.min(maxTop, Math.round(height)));
            };

            const setQuantumSplitterAria = function (height) {
                if (!quantumBufferSplitter || !quantumBufferModalBody) {
                    return;
                }

                const bodyHeight = quantumBufferModalBody.clientHeight || 1;
                quantumBufferSplitter.setAttribute('aria-valuemin', '0');
                quantumBufferSplitter.setAttribute('aria-valuemax', '100');
                quantumBufferSplitter.setAttribute('aria-valuenow', String(Math.round((height / bodyHeight) * 100)));
            };

            const applyQuantumSplitRatio = function (ratio, persist = false) {
                if (!quantumBufferModalBody || !quantumUnparsedSection || !quantumBufferModalEl || quantumBufferModalEl.classList.contains('is-recent-hidden')) {
                    return;
                }

                const bodyHeight = quantumBufferModalBody.clientHeight || 0;
                if (bodyHeight <= 0) {
                    return;
                }

                const safeRatio = Math.max(0.25, Math.min(0.75, Number(ratio) || 0.42));
                const height = clampQuantumSplitHeight(bodyHeight * safeRatio);
                quantumSplitRatio = height / bodyHeight;
                quantumBufferModalEl.style.setProperty('--quantum-unparsed-height', `${height}px`);
                setQuantumSplitterAria(height);

                if (persist) {
                    window.UserUiSettings.set(settingsScope, quantumSplitRatioKey, Number(quantumSplitRatio.toFixed(4)));
                }
            };

            setQuantumRecentVisible(settingValue(quantumRecentVisibleKey, true) !== false);

            quantumRecentVisibleToggle?.addEventListener('change', function () {
                setQuantumRecentVisible(Boolean(quantumRecentVisibleToggle.checked), true);
            });

            const resizeQuantumSplitBy = function (delta, persist = true) {
                if (!quantumBufferModalBody || !quantumUnparsedSection) {
                    return;
                }

                const bodyHeight = quantumBufferModalBody.clientHeight || 0;
                if (bodyHeight <= 0) {
                    return;
                }

                const currentHeight = quantumUnparsedSection.getBoundingClientRect().height;
                const nextHeight = clampQuantumSplitHeight(currentHeight + delta);
                applyQuantumSplitRatio(nextHeight / bodyHeight, persist);
            };

            if (quantumBufferSplitter && quantumBufferModalBody && quantumUnparsedSection) {
                let splitterDragStartY = 0;
                let splitterDragStartHeight = 0;
                let splitterDragging = false;

                const stopQuantumSplitterDrag = function () {
                    if (!splitterDragging) {
                        return;
                    }

                    splitterDragging = false;
                    quantumBufferSplitter.classList.remove('is-dragging');
                    document.body.classList.remove('quantum-buffer-is-resizing');
                    document.removeEventListener('pointermove', onQuantumSplitterPointerMove);
                    document.removeEventListener('pointerup', stopQuantumSplitterDrag);
                    document.removeEventListener('pointercancel', stopQuantumSplitterDrag);
                    window.UserUiSettings.set(settingsScope, quantumSplitRatioKey, Number(quantumSplitRatio.toFixed(4)));
                };

                var onQuantumSplitterPointerMove = function (event) {
                    if (!splitterDragging) {
                        return;
                    }

                    const bodyHeight = quantumBufferModalBody.clientHeight || 0;
                    if (bodyHeight <= 0) {
                        return;
                    }

                    const nextHeight = clampQuantumSplitHeight(splitterDragStartHeight + (event.clientY - splitterDragStartY));
                    applyQuantumSplitRatio(nextHeight / bodyHeight, false);
                    event.preventDefault();
                };

                quantumBufferSplitter.addEventListener('pointerdown', function (event) {
                    if (event.button !== undefined && event.button !== 0) {
                        return;
                    }

                    splitterDragging = true;
                    splitterDragStartY = event.clientY;
                    splitterDragStartHeight = quantumUnparsedSection.getBoundingClientRect().height;
                    quantumBufferSplitter.classList.add('is-dragging');
                    document.body.classList.add('quantum-buffer-is-resizing');
                    document.addEventListener('pointermove', onQuantumSplitterPointerMove);
                    document.addEventListener('pointerup', stopQuantumSplitterDrag);
                    document.addEventListener('pointercancel', stopQuantumSplitterDrag);
                    event.preventDefault();
                });

                quantumBufferSplitter.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowUp') {
                        resizeQuantumSplitBy(-24);
                        event.preventDefault();
                    } else if (event.key === 'ArrowDown') {
                        resizeQuantumSplitBy(24);
                        event.preventDefault();
                    } else if (event.key === 'Home') {
                        applyQuantumSplitRatio(0.25, true);
                        event.preventDefault();
                    } else if (event.key === 'End') {
                        applyQuantumSplitRatio(0.75, true);
                        event.preventDefault();
                    }
                });

                quantumBufferModalEl?.addEventListener('shown.bs.modal', function () {
                    requestAnimationFrame(function () {
                        applyQuantumSplitRatio(quantumSplitRatio, false);
                    });
                });

                window.addEventListener('resize', function () {
                    requestAnimationFrame(function () {
                        applyQuantumSplitRatio(quantumSplitRatio, false);
                    });
                });
            }

            const setQuantumCountText = function (id, value) {
                const element = document.getElementById(id);
                if (!element || value === undefined || value === null) {
                    return;
                }

                element.textContent = Number(value).toLocaleString('en-US');
            };

            const setQuantumToolbarCount = function (value) {
                const element = document.getElementById('quantumToolbarUnparsedCount');
                if (!element || value === undefined || value === null) {
                    return;
                }

                const count = Number(value) || 0;
                element.textContent = count.toLocaleString('en-US');
                element.classList.toggle('d-none', count <= 0);
            };

            const setQuantumStatusCounts = function (counts) {
                if (!counts || typeof counts !== 'object') {
                    return;
                }

                document.querySelectorAll('[data-quantum-status-count]').forEach(function (element) {
                    const key = element.dataset.quantumStatusCount || '';
                    const value = Number(counts[key] || 0);
                    const valueElement = element.querySelector('[data-quantum-status-value]');

                    if (valueElement) {
                        valueElement.textContent = value.toLocaleString('en-US');
                    }

                    element.classList.toggle('d-none', value <= 0);
                });
            };

            const setQuantumLoadState = function (message, visible, isError = false) {
                if (!quantumRecentLoadState) {
                    return;
                }

                quantumRecentLoadState.textContent = message || '';
                quantumRecentLoadState.classList.toggle('d-none', !visible);
                quantumRecentLoadState.classList.toggle('text-danger', Boolean(isError));
                quantumRecentLoadState.classList.toggle('text-muted', !isError);
            };

            const setQuantumSearchStatus = function (message, className = 'text-muted') {
                if (!quantumWorkorderSearchStatus) {
                    return;
                }

                quantumWorkorderSearchStatus.className = 'quantum-buffer-search-status ' + className;
                quantumWorkorderSearchStatus.textContent = message || '';
            };

            const escapeQuantumMessageHtml = function (value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    }[char];
                });
            };

            const renderQuantumMessageHtml = function (message) {
                const text = String(message || 'Not parsed yet.');
                const importantPhrases = [
                    'WO not found: old',
                    'Workorder not found',
                    'WO not found',
                    'Unsupported Quantum PN',
                    'Missing REF',
                    'No STD process target',
                    'Multiple STD process targets',
                    'Vendor not found',
                    'Bushing REF must be batch',
                    'No bushing batches',
                    'Bushing batch not found',
                    'No process_names.code matched REF',
                    'Multiple process_names.code matched REF',
                    'No TDR process target',
                    'Multiple TDR process targets',
                    'No target process',
                    'Applied',
                    'Already current',
                    'Dismissed by user',
                    'Restored by user',
                ];
                const escapeRegExp = value => String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const phrasePattern = importantPhrases.map(escapeRegExp).join('|');
                const woPattern = '\\bW\\d{5,}\\b';
                const combinedPattern = new RegExp('(' + phrasePattern + '|' + woPattern + ')', 'gi');
                const woOnlyPattern = new RegExp('^(?:' + woPattern + ')$', 'i');
                let html = '';
                let lastIndex = 0;

                text.replace(combinedPattern, function (match, _captured, offset) {
                    if (offset > lastIndex) {
                        html += '<span class="quantum-message-muted">' + escapeQuantumMessageHtml(text.slice(lastIndex, offset)) + '</span>';
                    }

                    const className = woOnlyPattern.test(match) ? 'quantum-message-wo' : 'quantum-message-info';
                    html += '<span class="' + className + '">' + escapeQuantumMessageHtml(match) + '</span>';
                    lastIndex = offset + match.length;

                    return match;
                });

                if (lastIndex < text.length) {
                    html += '<span class="quantum-message-muted">' + escapeQuantumMessageHtml(text.slice(lastIndex)) + '</span>';
                }

                return html || '<span class="quantum-message-muted">Not parsed yet.</span>';
            };

            const quantumRowByLineId = function (lineId) {
                if (!quantumRecentBody || !lineId) {
                    return null;
                }

                const escapedLineId = window.CSS && typeof window.CSS.escape === 'function'
                    ? window.CSS.escape(String(lineId))
                    : String(lineId).replace(/"/g, '\\"');

                return quantumRecentBody.querySelector(`[data-quantum-line-id="${escapedLineId}"]`);
            };

            const cssEscapeValue = function (value) {
                return window.CSS && typeof window.CSS.escape === 'function'
                    ? window.CSS.escape(String(value))
                    : String(value).replace(/"/g, '\\"');
            };

            const appendQuantumRecentRows = function (html) {
                if (!quantumRecentBody || !html) {
                    return;
                }

                quantumRecentBody.querySelector('.js-quantum-recent-empty')?.remove();

                const template = document.createElement('template');
                template.innerHTML = html.trim();
                Array.from(template.content.children).forEach(function (row) {
                    quantumRecentBody.appendChild(row);
                });
            };

            const replaceQuantumBufferRows = function (data) {
                if (quantumRecentBody) {
                    const recentHtml = String(data?.html || '').trim();
                    quantumRecentBody.innerHTML = recentHtml || '<tr class="js-quantum-recent-empty"><td colspan="13" class="text-center text-muted py-4">No matching Quantum Reason rows.</td></tr>';
                }

                if (quantumUnparsedBody) {
                    const unparsedHtml = String(data?.unparsed_html || '').trim();
                    quantumUnparsedBody.innerHTML = unparsedHtml || '<tr class="js-quantum-unparsed-empty"><td colspan="13" class="text-center text-muted py-4">No matching unresolved Quantum rows.</td></tr>';
                }

                if (quantumRecentScroll) {
                    quantumRecentScroll.dataset.hasMore = data?.has_more ? '1' : '0';
                    quantumRecentScroll.dataset.nextPage = data?.next_page ? String(data.next_page) : '';
                    quantumRecentScroll.scrollTop = 0;
                }

                setQuantumCountText('quantumRecentTotalCount', data?.total ?? 0);
                setQuantumCountText('quantumUnparsedTotalCount', data?.unparsed_total ?? 0);
                document.querySelector('.quantum-buffer-unparsed-wrap')?.scrollTo({ top: 0 });
            };

            const setQuantumDismissButtonsDisabled = function (disabled) {
                quantumUnparsedBody?.querySelectorAll('.js-quantum-dismiss-row').forEach(function (button) {
                    button.toggleAttribute('disabled', Boolean(disabled));
                });
            };

            const ensureQuantumUnparsedEmptyRow = function () {
                if (!quantumUnparsedBody || quantumUnparsedBody.querySelector('tr[data-quantum-line-id]')) {
                    return;
                }

                if (!quantumUnparsedBody.querySelector('.js-quantum-unparsed-empty')) {
                    const row = document.createElement('tr');
                    row.className = 'js-quantum-unparsed-empty';
                    row.innerHTML = '<td colspan="13" class="text-center text-muted py-4">No unresolved Quantum RO rows.</td>';
                    quantumUnparsedBody.appendChild(row);
                }
            };

            const applyQuantumDismissResult = function (data) {
                const dismissedIds = Array.isArray(data?.dismissed_ids) ? data.dismissed_ids.map(String) : [];
                const linesById = new Map((Array.isArray(data?.lines) ? data.lines : []).map(line => [String(line.id), line]));

                dismissedIds.forEach(function (lineId) {
                    quantumUnparsedBody?.querySelector(`tr[data-quantum-line-id="${cssEscapeValue(lineId)}"]`)?.remove();

                    const recentRow = quantumRowByLineId(lineId);
                    if (recentRow) {
                        const line = linesById.get(lineId) || {};
                        const statusCell = recentRow.querySelector('.js-quantum-status-cell');
                        const messageCell = recentRow.querySelector('.js-quantum-message-cell');

                        if (statusCell) {
                            statusCell.innerHTML = '<span class="badge text-bg-secondary">dismissed</span>';
                        }

                        if (messageCell && line.message) {
                            messageCell.innerHTML = renderQuantumMessageHtml(line.message);
                        }

                        const actionCell = recentRow.querySelector('.js-quantum-action-cell');
                        if (actionCell && line.restore_url) {
                            actionCell.innerHTML = `
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm js-quantum-restore-row"
                                    data-restore-url="${line.restore_url}"
                                    title="Restore this row to pending"
                                >
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            `;
                        }
                    }
                });

                ensureQuantumUnparsedEmptyRow();
                setQuantumToolbarCount(data?.unparsed_total);
                setQuantumCountText(
                    'quantumUnparsedTotalCount',
                    activeQuantumFilter.search !== ''
                        ? quantumUnparsedBody?.querySelectorAll('tr[data-quantum-line-id]').length ?? 0
                        : data?.unparsed_total
                );
                setQuantumStatusCounts(data?.status_counts);
            };

            const dismissQuantumRows = async function (url, payload = {}) {
                if (!url) {
                    return;
                }

                setQuantumDismissButtonsDisabled(true);

                const request = function () {
                    return fetch(url, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        spinner: false,
                        ignoreSessionExpiry: true,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });
                };

                try {
                    let response = await request();
                    if (response.status === 419 && await refreshCsrfToken()) {
                        response = await request();
                    }

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Dismiss failed');
                    }

                    applyQuantumDismissResult(data);
                    setQuantumSearchStatus(data.dismissed ? `Dismissed ${data.dismissed}` : 'Nothing dismissed', data.dismissed ? 'text-success' : 'text-muted');
                } catch (error) {
                    setQuantumSearchStatus(error.message || 'Dismiss failed', 'text-danger');
                } finally {
                    setQuantumDismissButtonsDisabled(false);
                }
            };

            const applyQuantumRestoreResult = function (data) {
                const line = data?.line || {};
                const lineId = line.id || data?.restored_id;
                const recentRow = quantumRowByLineId(lineId);

                if (recentRow) {
                    const statusCell = recentRow.querySelector('.js-quantum-status-cell');
                    const messageCell = recentRow.querySelector('.js-quantum-message-cell');
                    const actionCell = recentRow.querySelector('.js-quantum-action-cell');

                    if (statusCell) {
                        statusCell.innerHTML = '<span class="badge text-bg-secondary">pending</span>';
                    }

                    if (messageCell && line.message) {
                        messageCell.innerHTML = renderQuantumMessageHtml(line.message);
                    }

                    if (actionCell) {
                        actionCell.innerHTML = '';
                    }
                }

                setQuantumToolbarCount(data?.unparsed_total);
                setQuantumCountText(
                    'quantumUnparsedTotalCount',
                    activeQuantumFilter.search !== ''
                        ? quantumUnparsedBody?.querySelectorAll('tr[data-quantum-line-id]').length ?? 0
                        : data?.unparsed_total
                );
                setQuantumStatusCounts(data?.status_counts);
            };

            const restoreQuantumRow = async function (url) {
                if (!url) {
                    return;
                }

                const request = function () {
                    return fetch(url, {
                        method: 'PATCH',
                        credentials: 'same-origin',
                        spinner: false,
                        ignoreSessionExpiry: true,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                };

                try {
                    let response = await request();
                    if (response.status === 419 && await refreshCsrfToken()) {
                        response = await request();
                    }

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Restore failed');
                    }

                    applyQuantumRestoreResult(data);
                    setQuantumSearchStatus(data.restored ? 'Restored to pending' : 'Nothing restored', data.restored ? 'text-success' : 'text-muted');
                } catch (error) {
                    setQuantumSearchStatus(error.message || 'Restore failed', 'text-danger');
                }
            };

            const loadMoreQuantumRecent = async function () {
                if (
                    !quantumRecentScroll
                    || !quantumRecentBody
                    || isLoadingQuantumRecent
                    || activeQuantumFilter.search !== ''
                    || quantumRecentScroll.dataset.hasMore !== '1'
                ) {
                    return false;
                }

                const page = Number(quantumRecentScroll.dataset.nextPage || 0);
                const fetchUrl = quantumRecentScroll.dataset.fetchUrl || '';
                if (!page || !fetchUrl) {
                    return false;
                }

                isLoadingQuantumRecent = true;
                setQuantumLoadState('Loading more rows...', true);

                const url = new URL(fetchUrl, window.location.origin);
                url.searchParams.set('page', String(page));

                const request = function () {
                    return fetch(url.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        spinner: false,
                        ignoreSessionExpiry: true,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                };

                try {
                    let response = await request();
                    if (response.status === 419 && await refreshCsrfToken()) {
                        response = await request();
                    }

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Failed to load Quantum rows');
                    }

                    appendQuantumRecentRows(data.html || '');
                    quantumRecentScroll.dataset.hasMore = data.has_more ? '1' : '0';
                    quantumRecentScroll.dataset.nextPage = data.next_page ? String(data.next_page) : '';
                    setQuantumCountText('quantumRecentTotalCount', data.total);
                    setQuantumLoadState('', false);
                    return true;
                } catch (error) {
                    setQuantumLoadState('Failed to load more rows.', true, true);
                    return false;
                } finally {
                    isLoadingQuantumRecent = false;
                }
            };

            const filterQuantumBufferRows = async function (event, form, input, mode) {
                event?.preventDefault();
                if (!input || !form) {
                    return;
                }

                const searchTerm = input.value.trim();
                const searchMode = mode === 'ro' ? 'ro' : 'wo';
                const searchLabel = searchMode === 'ro' ? 'RO' : 'WO';
                const findUrl = form.dataset.findUrl || '';

                if (!findUrl) {
                    setQuantumSearchStatus('Filter unavailable', 'text-danger');
                    return;
                }

                setQuantumRecentVisible(true, false);
                setQuantumSearchStatus(searchTerm ? 'Filtering...' : 'Resetting...', 'text-info');

                if (searchTerm !== '') {
                    if (searchMode === 'wo' && quantumRepairOrderSearchInput) {
                        quantumRepairOrderSearchInput.value = '';
                    }
                    if (searchMode === 'ro' && quantumWorkorderSearchInput) {
                        quantumWorkorderSearchInput.value = '';
                    }
                }

                const url = new URL(findUrl, window.location.origin);
                url.searchParams.set('mode', searchMode);
                url.searchParams.set(searchMode === 'ro' ? 'repair_order' : 'workorder', searchTerm);

                try {
                    const response = await fetch(url.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        spinner: false,
                        ignoreSessionExpiry: true,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Filter failed');
                    }

                    replaceQuantumBufferRows(data);
                    activeQuantumFilter = {
                        mode: searchTerm ? searchMode : null,
                        search: searchTerm,
                    };

                    if (!searchTerm) {
                        setQuantumSearchStatus('', 'text-muted');
                        return;
                    }

                    const matchedCount = Number(data.total || data.matched_count || 0);
                    if (!data.found || matchedCount <= 0) {
                        setQuantumSearchStatus(data.message || `${searchLabel} not found in Reason`, 'text-warning');
                        return;
                    }

                    const suffix = data.has_more_matches ? ' (first 500 shown)' : '';
                    const location = searchMode === 'wo' ? 'Reason' : 'RO';
                    setQuantumSearchStatus(`${matchedCount} rows where ${location} contains ${searchTerm}${suffix}`, 'text-success');
                } catch (error) {
                    setQuantumSearchStatus(error.message || 'Filter failed', 'text-danger');
                }
            };

            const bindQuantumLineSearch = function (form, input, mode) {
                form?.addEventListener('submit', function (event) {
                    filterQuantumBufferRows(event, form, input, mode);
                });
                input?.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    filterQuantumBufferRows(event, form, input, mode);
                });
                input?.addEventListener('search', function (event) {
                    if (input.value.trim() === '') {
                        filterQuantumBufferRows(event, form, input, mode);
                    }
                });
            };

            bindQuantumLineSearch(quantumWorkorderSearchForm, quantumWorkorderSearchInput, 'wo');
            bindQuantumLineSearch(quantumRepairOrderSearchForm, quantumRepairOrderSearchInput, 'ro');

            quantumUnparsedBody?.addEventListener('click', function (event) {
                const button = event.target.closest('.js-quantum-dismiss-row');
                if (!button) {
                    return;
                }

                dismissQuantumRows(button.dataset.dismissUrl || '');
            });

            quantumRecentBody?.addEventListener('click', function (event) {
                const button = event.target.closest('.js-quantum-restore-row');
                if (!button) {
                    return;
                }

                restoreQuantumRow(button.dataset.restoreUrl || '');
            });

            quantumRecentScroll?.addEventListener('scroll', function () {
                if (quantumRecentScroll.scrollTop + quantumRecentScroll.clientHeight >= quantumRecentScroll.scrollHeight - 90) {
                    loadMoreQuantumRecent();
                }
            });

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
                    altFormat: 'd/M/Y',
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
