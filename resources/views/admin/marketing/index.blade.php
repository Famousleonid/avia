@extends('admin.master')

@section('style')
    <style>
        .marketing-page {
            --marketing-control-sm-height: 31px;
            --marketing-control-sm-inner-height: 29px;
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
            --marketing-filter-control-height: var(--marketing-control-sm-height);
            --marketing-filter-control-inner-height: var(--marketing-control-sm-inner-height);
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
            position: relative;
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
            color: var(--bs-info);
        }

        .marketing-toolbar .form-control-sm,
        .marketing-toolbar .form-select-sm,
        .marketing-toolbar .select2-container--default .select2-selection--single {
            height: var(--marketing-filter-control-height) !important;
            min-height: var(--marketing-filter-control-height) !important;
            max-height: var(--marketing-filter-control-height) !important;
            box-sizing: border-box;
        }

        .marketing-filter.is-active .form-control,
        .marketing-filter.is-active .form-select,
        .marketing-filter.is-active .select2-container--default .select2-selection--single {
            border-color: var(--bs-info);
            box-shadow: 0 0 0 .12rem rgba(var(--bs-info-rgb), .18);
        }

        html[data-bs-theme="dark"] .marketing-filter.is-active label {
            color: #8bd3f7;
        }

        html[data-bs-theme="dark"] .marketing-filter.is-active .form-control,
        html[data-bs-theme="dark"] .marketing-filter.is-active .form-select,
        html[data-bs-theme="dark"] .marketing-filter.is-active .select2-container--default .select2-selection--single {
            border-color: #8bd3f7;
            box-shadow: 0 0 0 .12rem rgba(139, 211, 247, .16);
        }

        .marketing-filter .select2-container {
            display: block;
            width: 100% !important;
            margin: 0;
            vertical-align: bottom;
        }

        .marketing-filter .select2-container--default .select2-selection--single {
            height: var(--marketing-filter-control-height) !important;
            min-height: var(--marketing-filter-control-height) !important;
            max-height: var(--marketing-filter-control-height) !important;
            box-sizing: border-box !important;
            padding: 0 !important;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius-sm);
            background: var(--bs-body-bg);
        }

        html[data-bs-theme="dark"] .marketing-toolbar .marketing-filter .select2-container--default .select2-selection--single {
            height: var(--marketing-filter-control-height) !important;
            min-height: var(--marketing-filter-control-height) !important;
            max-height: var(--marketing-filter-control-height) !important;
            padding: 0 !important;
            border-radius: var(--bs-border-radius-sm) !important;
        }

        .marketing-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
            height: var(--marketing-filter-control-inner-height) !important;
            min-height: var(--marketing-filter-control-inner-height) !important;
            padding-left: .5rem !important;
            padding-right: 2rem !important;
            color: var(--bs-body-color);
            font-size: .875rem;
            line-height: var(--marketing-filter-control-inner-height) !important;
        }

        html[data-bs-theme="dark"] .marketing-toolbar .marketing-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
            height: var(--marketing-filter-control-inner-height) !important;
            min-height: var(--marketing-filter-control-inner-height) !important;
            padding-left: .5rem !important;
            padding-right: 2rem !important;
            line-height: var(--marketing-filter-control-inner-height) !important;
        }

        .marketing-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: var(--marketing-filter-control-inner-height) !important;
            right: 4px;
        }

        html[data-bs-theme="dark"] .marketing-toolbar .marketing-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: var(--marketing-filter-control-inner-height) !important;
        }

        .marketing-filter.has-clear .form-select {
            padding-right: 2rem;
            background-image: none;
        }

        .marketing-filter.has-clear .select2-container--default .select2-selection--single .select2-selection__arrow,
        .marketing-filter.has-clear .select2-container--default .select2-selection--single .select2-selection__clear {
            display: none;
        }

        .marketing-filter-clear {
            position: absolute;
            right: 6px;
            bottom: 4px;
            z-index: 4;
            display: none;
            align-items: center;
            justify-content: center;
            width: 23px;
            height: 23px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-info);
            line-height: 1;
        }

        .marketing-filter.has-clear .marketing-filter-clear {
            display: inline-flex;
        }

        .marketing-filter-clear:hover,
        .marketing-filter-clear:focus-visible {
            color: #fff;
            background: var(--bs-info);
            outline: 0;
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

        .marketing-table th:first-child,
        .marketing-table td:first-child {
            padding-left: 12px;
        }

        .marketing-shell.is-company-only .marketing-table {
            min-width: 100%;
            table-layout: fixed;
        }

        .marketing-shell.is-company-only .marketing-table th:not(:first-child),
        .marketing-shell.is-company-only .marketing-table td:not(:first-child) {
            display: none;
        }

        .marketing-shell.is-company-only .marketing-table th:first-child,
        .marketing-shell.is-company-only .marketing-table td:first-child {
            width: 100% !important;
            max-width: none;
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
            color: var(--bs-warning);
        }

        html[data-bs-theme="dark"] .marketing-detail-title h2.is-loading {
            color: #ffda6a;
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
            position: relative;
            flex: 0 0 auto;
            display: flex;
            gap: 2px;
            padding: 8px 14px 0;
            overflow-x: auto;
        }

        .marketing-detail-tabs::after {
            content: "";
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: 0;
            z-index: 0;
            height: 1px;
            background: rgba(52, 58, 64, .14);
            pointer-events: none;
        }

        html[data-bs-theme="dark"] .marketing-detail-tabs::after {
            background: rgba(255, 255, 255, .14);
        }

        .marketing-tab {
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 34px;
            margin-bottom: -1px;
            padding: 0 11px;
            border: 2px solid transparent;
            border-bottom: 0;
            border-radius: 7px 7px 0 0;
            background: transparent;
            color: #4d5963;
            font-size: .82rem;
            font-weight: 800;
        }

        .marketing-tab.is-active {
            z-index: 3;
            border-color: var(--bs-info);
            border-bottom: 0;
            color: #0d6efd;
            background: #fff;
        }

        .marketing-tab.is-active::before,
        .marketing-tab.is-active::after {
            content: "";
            position: absolute;
            bottom: 0;
            z-index: 4;
            width: 12px;
            height: 2px;
            background: var(--bs-info);
            pointer-events: none;
        }

        .marketing-tab.is-active::before {
            left: -12px;
        }

        .marketing-tab.is-active::after {
            right: -12px;
        }

        html[data-bs-theme="dark"] .marketing-tab {
            color: #c5ccd2;
        }

        html[data-bs-theme="dark"] .marketing-tab.is-active {
            color: #8bd3f7;
            border-color: #8bd3f7;
            border-bottom: 0;
            background: #2d3030;
        }

        html[data-bs-theme="dark"] .marketing-tab.is-active::before,
        html[data-bs-theme="dark"] .marketing-tab.is-active::after {
            background: #8bd3f7;
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

        .marketing-field .form-control-sm:not(textarea),
        .marketing-field .form-select-sm,
        .marketing-field .select2-container--default .select2-selection--single {
            height: var(--marketing-control-sm-height) !important;
            min-height: var(--marketing-control-sm-height) !important;
            max-height: var(--marketing-control-sm-height) !important;
            box-sizing: border-box;
        }

        .marketing-field textarea.form-control-sm {
            min-height: var(--marketing-control-sm-height);
            max-height: none;
            overflow: auto;
            resize: vertical;
            line-height: 1.35;
        }

        .marketing-field textarea[name="company_notes"] {
            min-height: var(--marketing-control-sm-height);
        }

        .marketing-address-line {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 10% minmax(0, 1fr);
            gap: 9px;
            align-items: start;
            min-width: 0;
        }

        .marketing-post-code-field .form-control-sm {
            padding-right: .35rem;
            padding-left: .35rem;
            font-size: .8rem;
        }

        .marketing-page input::placeholder,
        .marketing-page textarea::placeholder,
        .marketing-page .form-control::placeholder,
        .marketing-page .form-control-sm::placeholder {
            color: rgba(108, 117, 125, .52);
            opacity: 1;
        }

        html[data-bs-theme="dark"] .marketing-page input::placeholder,
        html[data-bs-theme="dark"] .marketing-page textarea::placeholder,
        html[data-bs-theme="dark"] .marketing-page .form-control::placeholder,
        html[data-bs-theme="dark"] .marketing-page .form-control-sm::placeholder {
            color: rgba(222, 226, 230, .38);
            opacity: 1;
        }

        .marketing-page .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgba(108, 117, 125, .58);
        }

        html[data-bs-theme="dark"] .marketing-page .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgba(222, 226, 230, .42);
        }

        #marketingWorkorderSalesModal .form-control.is-marketing-empty-date {
            color: rgba(108, 117, 125, .62);
        }

        html[data-bs-theme="dark"] #marketingWorkorderSalesModal .form-control.is-marketing-empty-date {
            color: rgba(222, 226, 230, .46);
        }

        .marketing-field .marketing-country-select + .select2,
        .marketing-field .marketing-city-select + .select2,
        .marketing-field .marketing-aircraft-select + .select2 {
            width: 100% !important;
        }

        .marketing-field .marketing-country-select + .select2 .select2-selection--single,
        .marketing-field .marketing-city-select + .select2 .select2-selection--single {
            height: var(--marketing-control-sm-height) !important;
            min-height: var(--marketing-control-sm-height) !important;
            max-height: var(--marketing-control-sm-height) !important;
            padding: 0 !important;
            border-radius: 6px !important;
        }

        .marketing-field .marketing-country-select + .select2 .select2-selection__rendered,
        .marketing-field .marketing-city-select + .select2 .select2-selection__rendered {
            height: var(--marketing-control-sm-inner-height) !important;
            min-height: var(--marketing-control-sm-inner-height) !important;
            padding-left: .75rem !important;
            padding-right: 2rem !important;
            line-height: var(--marketing-control-sm-inner-height) !important;
        }

        html[data-bs-theme="dark"] .marketing-page .marketing-field .marketing-country-select + .select2 .select2-selection--single .select2-selection__rendered,
        html[data-bs-theme="dark"] .marketing-page .marketing-field .marketing-city-select + .select2 .select2-selection--single .select2-selection__rendered {
            padding-left: .75rem !important;
        }

        .marketing-field .marketing-country-select + .select2 .select2-selection__arrow,
        .marketing-field .marketing-city-select + .select2 .select2-selection__arrow {
            height: var(--marketing-control-sm-inner-height) !important;
        }

        .marketing-field .marketing-aircraft-select + .select2 .select2-selection--multiple {
            min-height: 31px;
            max-height: 86px;
            overflow-y: auto;
            border-radius: 6px;
        }

        .marketing-field .marketing-aircraft-select + .select2 .select2-selection__rendered {
            padding: 2px 6px;
        }

        .marketing-address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
        }

        .marketing-address-item {
            min-width: 0;
            padding: 9px;
            border: 1px solid rgba(52, 58, 64, .14);
            border-radius: 6px;
            background: rgba(248, 249, 250, .78);
        }

        html[data-bs-theme="dark"] .marketing-address-item {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .12);
        }

        .marketing-address-label {
            margin-bottom: 4px;
            color: #68717a;
            font-size: .7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .marketing-address-text {
            color: #27313a;
            font-size: .8rem;
            line-height: 1.32;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        html[data-bs-theme="dark"] .marketing-address-text {
            color: #e7ecef;
        }

        .marketing-address-category-switcher {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .marketing-address-category-button {
            flex: 0 0 auto;
            font-weight: 800;
        }

        .marketing-address-category-button.is-active {
            color: #fff;
            background: var(--bs-info);
            border-color: var(--bs-info);
        }

        .marketing-overview-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
            margin-top: 10px;
        }

        .marketing-overview-footer .marketing-section {
            min-width: 0;
            margin-bottom: 0;
        }

        .marketing-overview-footer .marketing-actions {
            align-self: end;
            margin-top: 0;
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

        .marketing-contact-copy-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 6px;
        }

        .marketing-contact-row:not(.is-editing) .marketing-contact-save,
        .marketing-contact-row:not(.is-editing) .marketing-contact-cancel,
        .marketing-contact-row:not(.is-editing) .js-contact-delete,
        .marketing-contact-row.is-editing .marketing-contact-edit {
            display: none;
        }

        .marketing-contact-row:not(.is-editing) input[readonly] {
            cursor: text;
        }

        #aiAssistantWidget,
        .ai-widget {
            display: none !important;
        }

        .marketing-workorders-wrap {
            max-height: min(62vh, 620px);
            overflow: auto;
            position: relative;
        }

        .marketing-workorders-table {
            min-width: 1320px;
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

        .marketing-workorders-table tbody tr[data-workorder-id] {
            cursor: pointer;
        }

        .marketing-workorders-table.dir-table tbody > tr.marketing-workorder-complete > td,
        .marketing-workorders-table.dir-table.table-hover tbody > tr.marketing-workorder-complete:hover > td {
            background: var(--dir-table-bg) !important;
            color: #6c757d !important;
        }

        html[data-bs-theme="dark"] .marketing-workorders-table.dir-table tbody > tr.marketing-workorder-complete > td,
        html[data-bs-theme="dark"] .marketing-workorders-table.dir-table.table-hover tbody > tr.marketing-workorder-complete:hover > td {
            background: var(--dir-table-bg) !important;
            color: #aeb6bd !important;
        }

        .marketing-workorders-table.dir-table tbody > tr.marketing-workorder-complete a {
            color: inherit;
        }

        .marketing-workorders-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .marketing-workorders-toolbar .marketing-section-title {
            margin-bottom: 0;
        }

        .marketing-workorders-search {
            position: relative;
            flex: 0 1 320px;
            min-width: min(100%, 220px);
        }

        .marketing-workorders-search .form-control-sm {
            height: var(--marketing-control-sm-height);
            padding-right: 2rem;
        }

        .marketing-workorders-search-clear {
            position: absolute;
            top: 50%;
            right: 4px;
            z-index: 2;
            display: none;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-info);
            line-height: 1;
            transform: translateY(-50%);
        }

        .marketing-workorders-search.has-clear .marketing-workorders-search-clear {
            display: inline-flex;
        }

        .marketing-workorders-search-clear:hover,
        .marketing-workorders-search-clear:focus-visible {
            color: #fff;
            background: var(--bs-info);
            outline: 0;
        }

        .marketing-workorders-filter-row th {
            top: 31px !important;
            z-index: 12 !important;
            padding: 4px 6px !important;
            box-shadow: 0 2px 6px rgba(0,0,0,.18) !important;
        }

        .marketing-workorders-filter-row input {
            width: 100%;
            min-width: 72px;
            height: 25px;
            padding: 2px 6px;
            border: 1px solid var(--bs-border-color);
            border-radius: 4px;
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-size: .74rem;
            line-height: 19px;
        }

        .marketing-workorders-filter-row input:focus {
            border-color: var(--bs-info);
            box-shadow: 0 0 0 .1rem rgba(var(--bs-info-rgb), .18);
            outline: 0;
        }

        .marketing-workorders-filter-row th.is-active input {
            border-color: var(--bs-info);
            color: var(--bs-info);
        }

        .marketing-sales-report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .marketing-sales-report-heading {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            min-width: 0;
        }

        .marketing-sales-report-company {
            min-width: 0;
            color: var(--bs-info);
            font-size: .86rem;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .marketing-sales-report-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .marketing-sales-report-mode {
            flex: 0 0 auto;
        }

        .marketing-sales-report-mode .btn {
            font-weight: 800;
        }

        .marketing-sales-report-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .marketing-sales-report-aircraft {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .marketing-sales-report-filters label {
            display: inline-block;
            margin: 0;
            color: #68717a;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .marketing-sales-report-date .form-control-sm {
            width: 140px;
        }

        .marketing-sales-report-aircraft .form-select-sm,
        .marketing-sales-report-aircraft .select2-container {
            width: 180px !important;
        }

        .marketing-sales-report-aircraft .select2-container--default .select2-selection--single {
            height: var(--marketing-control-sm-height) !important;
            min-height: var(--marketing-control-sm-height) !important;
            max-height: var(--marketing-control-sm-height) !important;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius-sm);
            background: var(--bs-body-bg);
        }

        .marketing-sales-report-aircraft .select2-container--default .select2-selection--single .select2-selection__rendered {
            height: var(--marketing-control-sm-inner-height) !important;
            padding-left: .5rem !important;
            padding-right: 1.6rem !important;
            color: var(--bs-body-color);
            font-size: .875rem;
            line-height: var(--marketing-control-sm-inner-height) !important;
        }

        .marketing-sales-report-aircraft .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: var(--marketing-control-sm-inner-height) !important;
        }

        .marketing-sales-report-aircraft.has-clear .select2-container--default .select2-selection--single .select2-selection__arrow {
            display: none;
        }

        .marketing-sales-report-aircraft.has-clear .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute;
            top: 0;
            right: 7px;
            z-index: 2;
            height: var(--marketing-control-sm-inner-height);
            margin-right: 0;
            color: var(--bs-info);
            font-weight: 800;
            line-height: var(--marketing-control-sm-inner-height);
        }

        html[data-bs-theme="dark"] .marketing-sales-report-filters label {
            color: #b9c1c8;
        }

        .marketing-sales-report-wrap {
            max-height: min(62vh, 620px);
            overflow: auto;
            position: relative;
        }

        .marketing-sales-report-table {
            min-width: 920px;
            margin: 0;
        }

        .marketing-sales-report-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .marketing-sales-report-table td {
            font-size: .82rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .marketing-sales-report-money {
            text-align: right;
        }

        .marketing-sales-report-total td {
            font-weight: 800;
        }

        .marketing-sales-report-warning {
            color: #ffc107;
            font-size: .84rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .marketing-sales-report-note {
            margin-top: 8px;
            color: #68717a;
            font-size: .8rem;
        }

        html[data-bs-theme="dark"] .marketing-sales-report-note {
            color: #b9c1c8;
        }

        html[data-bs-theme="dark"] .marketing-sales-report-warning {
            color: #ffda6a;
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
            .marketing-contact-row,
            .marketing-address-grid {
                grid-template-columns: 1fr;
            }

            .marketing-address-line {
                grid-template-columns: 1fr;
            }

            .marketing-overview-footer {
                grid-template-columns: 1fr;
            }

            .marketing-overview-footer .marketing-actions {
                justify-content: flex-start;
            }

            .marketing-contact-actions {
                grid-row: auto;
            }

            .marketing-contact-primary-actions {
                grid-column: auto;
            }
        }

        @media print {
            @page {
                size: letter landscape;
                margin: 8mm;
            }

            html,
            body.is-marketing-sales-report-print,
            body.is-marketing-sales-report-print .container-fluid,
            body.is-marketing-sales-report-print .page-layout,
            body.is-marketing-sales-report-print .content,
            body.is-marketing-sales-report-print .content-inner {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                background: #fff !important;
            }

            body.is-marketing-sales-report-print #sidebarColumn,
            body.is-marketing-sales-report-print .marketing-toolbar,
            body.is-marketing-sales-report-print .marketing-table-panel,
            body.is-marketing-sales-report-print .marketing-splitter,
            body.is-marketing-sales-report-print .marketing-detail-head,
            body.is-marketing-sales-report-print .marketing-detail-tabs,
            body.is-marketing-sales-report-print .marketing-sales-report-actions,
            body.is-marketing-sales-report-print .marketing-sales-report-mode,
            body.is-marketing-sales-report-print .marketing-sales-report-aircraft,
            body.is-marketing-sales-report-print footer,
            body.is-marketing-sales-report-print #spinner-load {
                display: none !important;
            }

            body.is-marketing-sales-report-print .row.page-layout,
            body.is-marketing-sales-report-print .marketing-page,
            body.is-marketing-sales-report-print .marketing-shell,
            body.is-marketing-sales-report-print .marketing-detail,
            body.is-marketing-sales-report-print .marketing-detail-body {
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                border: 0 !important;
                border-radius: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            body.is-marketing-sales-report-print .marketing-pane {
                display: none !important;
            }

            body.is-marketing-sales-report-print #marketingPaneSalesReport {
                display: block !important;
                color: #000 !important;
            }

            body.is-marketing-sales-report-print .marketing-sales-report-wrap {
                max-height: none !important;
                overflow: visible !important;
            }

            body.is-marketing-sales-report-print .marketing-sales-report-table {
                min-width: 0 !important;
            }

            body.is-marketing-sales-report-print .marketing-sales-report-warning {
                display: none !important;
            }

            body.is-marketing-sales-report-print #marketingPaneSalesReport .marketing-section-title {
                color: #000 !important;
                font-weight: 400 !important;
            }

            body.is-marketing-sales-report-print .marketing-sales-report-company,
            body.is-marketing-sales-report-print .marketing-sales-report-note,
            body.is-marketing-sales-report-print .marketing-sales-report-table,
            body.is-marketing-sales-report-print .marketing-sales-report-table th,
            body.is-marketing-sales-report-print .marketing-sales-report-table td,
            body.is-marketing-sales-report-print .marketing-sales-report-table a {
                color: #000 !important;
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
                <input id="marketingSearch" class="form-control form-control-sm" type="search" autocomplete="off" placeholder="Company, contact, country, city, A/C">
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
                <label for="marketingCountry">Country</label>
                <select id="marketingCountry" class="form-select form-select-sm marketing-country-select">
                    <option value="">All</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="marketing-filter">
                <label for="marketingCompanyType">Type of Business</label>
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
                            <th style="width: 150px;">Type of Business</th>
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

                <div class="marketing-detail-tabs" role="tablist" aria-label="Marketing detail tabs">
                    <button id="marketingTabOverview" class="marketing-tab is-active" type="button" data-tab="overview" role="tab" aria-selected="true" aria-controls="marketingPaneOverview"><i class="bi bi-building"></i>Overview</button>
                    <button id="marketingTabContacts" class="marketing-tab" type="button" data-tab="contacts" role="tab" aria-selected="false" aria-controls="marketingPaneContacts"><i class="bi bi-person-lines-fill"></i>Contacts</button>
                    <button id="marketingTabNotes" class="marketing-tab" type="button" data-tab="notes" role="tab" aria-selected="false" aria-controls="marketingPaneNotes"><i class="bi bi-journal-text"></i>Notes</button>
                    <button id="marketingTabWorkorders" class="marketing-tab" type="button" data-tab="workorders" role="tab" aria-selected="false" aria-controls="marketingPaneWorkorders"><i class="bi bi-wrench-adjustable"></i>WO</button>
                    <button id="marketingTabSalesReport" class="marketing-tab" type="button" data-tab="sales_report" role="tab" aria-selected="false" aria-controls="marketingPaneSalesReport"><i class="bi bi-graph-up-arrow"></i>Sales Report</button>
                </div>

                <div class="marketing-detail-body">
                    <div id="marketingPaneOverview" class="marketing-pane is-active" data-pane="overview" role="tabpanel" aria-labelledby="marketingTabOverview">
                        <form id="marketingProfileForm" data-no-spinner autocomplete="off">
                            <div class="marketing-section">
                                <h3 class="marketing-section-title">Company (Name)</h3>
                                <div class="marketing-form-grid">
                                    <div class="marketing-field span-2">
                                        <label class="visually-hidden" for="detailName">Company Name</label>
                                        <input id="detailName" name="name" class="form-control form-control-sm" type="text" maxlength="250" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
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
                                        <label for="detailCompanyType">Type of Business</label>
                                        <select id="detailCompanyType" name="company_type_id" class="form-select form-select-sm" autocomplete="off">
                                            <option value=""></option>
                                            @foreach($companyTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailCountryId">Country</label>
                                        <select id="detailCountryId" name="country_id" class="form-select form-select-sm marketing-country-select" autocomplete="off">
                                            <option value=""></option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailCity">City</label>
                                        <select id="detailCity" name="city" class="form-select form-select-sm marketing-city-select" data-country-select="#detailCountryId" autocomplete="off"></select>
                                    </div>
                                    <div class="marketing-field">
                                        <label for="detailStateProvince">State/Province</label>
                                        <input id="detailStateProvince" name="state_province" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                                    </div>
                                    <div class="marketing-address-line span-2">
                                        <div class="marketing-field marketing-post-code-field">
                                            <label for="detailPostCode">Post Code</label>
                                            <input id="detailPostCode" name="post_code" class="form-control form-control-sm" type="text" maxlength="40" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
                                        </div>
                                        <div class="marketing-field">
                                            <label for="detailStreetAddress">Street Address</label>
                                            <textarea id="detailStreetAddress" name="street_address" class="form-control form-control-sm" rows="1" autocomplete="off" autocorrect="on" autocapitalize="sentences" spellcheck="true"></textarea>
                                        </div>
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailCompanyNotes">Company Notes</label>
                                        <textarea id="detailCompanyNotes" name="company_notes" class="form-control form-control-sm" rows="1" autocomplete="off" autocorrect="on" autocapitalize="sentences" spellcheck="true"></textarea>
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailTerms">Terms</label>
                                        <input id="detailTerms" name="terms_label" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                                    </div>
                                    <div class="marketing-field span-2">
                                        <label for="detailAircraft">A/C Type</label>
                                        <select id="detailAircraft" name="aircraft_ids[]" class="form-select form-select-sm marketing-aircraft-select" multiple autocomplete="off">
                                            @foreach($planes as $plane)
                                                <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="marketing-overview-footer">
                                <div class="marketing-section">
                                    <h3 class="marketing-section-title">Address by Category</h3>
                                    <div id="marketingAddressCategories" class="marketing-address-category-switcher" role="group" aria-label="Address categories"></div>
                                </div>
                                <div class="marketing-actions">
                                    <button class="btn btn-sm btn-primary marketing-save-button" type="button" data-save-button data-profile-save-button title="Save">
                                        <i class="bi bi-check-lg" data-save-icon></i>
                                        <span>Save</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="marketingPaneContacts" class="marketing-pane" data-pane="contacts" role="tabpanel" aria-labelledby="marketingTabContacts">
                        <form id="marketingContactForm" class="marketing-section" data-no-spinner autocomplete="off" hidden>
                            <h3 class="marketing-section-title">New Contact</h3>
                            <div class="marketing-form-grid">
                                <div class="marketing-field">
                                    <label>First Name</label>
                                    <input name="first_name" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                                </div>
                                <div class="marketing-field">
                                    <label>Last Name</label>
                                    <input name="last_name" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                                </div>
                                <div class="marketing-field">
                                    <label>Position</label>
                                    <input name="position" class="form-control form-control-sm" type="text" maxlength="160" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                                </div>
                                <div class="marketing-field">
                                    <label>Email</label>
                                    <input name="email" class="form-control form-control-sm" type="email" maxlength="190" autocomplete="off">
                                </div>
                                <div class="marketing-field">
                                    <label>Phone</label>
                                    <input name="phone" class="form-control form-control-sm" type="text" maxlength="80" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
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
                            <h3 class="marketing-section-title">
                                <span>Contacts</span>
                                <span class="marketing-contact-copy-actions">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-contact-new-toggle aria-expanded="false" title="Add new contact">
                                        <i class="bi bi-plus-lg"></i>
                                        <span>New Contact</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-contact-copy="all" title="Copy all contact info">
                                        <i class="bi bi-clipboard"></i>
                                        <span>All copy</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-contact-copy="emails" title="Copy all emails">
                                        <i class="bi bi-envelope"></i>
                                        <span>Email copy</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-contact-copy="phones" title="Copy all phone numbers">
                                        <i class="bi bi-telephone"></i>
                                        <span>Phone copy</span>
                                    </button>
                                </span>
                            </h3>
                            <div id="marketingContactsList"></div>
                        </div>
                    </div>

                    <div id="marketingPaneNotes" class="marketing-pane" data-pane="notes" role="tabpanel" aria-labelledby="marketingTabNotes">
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
                                    <textarea name="note" class="form-control form-control-sm" rows="4" required autocomplete="off" autocorrect="on" autocapitalize="sentences" spellcheck="true"></textarea>
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

                    <div id="marketingPaneWorkorders" class="marketing-pane" data-pane="workorders" role="tabpanel" aria-labelledby="marketingTabWorkorders">
                        <div class="marketing-section">
                            <div class="marketing-workorders-toolbar">
                                <h3 class="marketing-section-title">Workorders</h3>
                                <div class="marketing-workorders-search" id="marketingWorkordersSearchWrap">
                                    <input id="marketingWorkordersSearch" class="form-control form-control-sm" type="search" placeholder="Search..." autocomplete="off">
                                    <button id="marketingWorkordersSearchClear" class="marketing-workorders-search-clear" type="button" title="Clear search" aria-label="Clear workorders search">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="marketing-workorders-wrap dir-table-wrap" id="marketingWorkordersScroll">
                                <table class="table table-sm table-hover align-middle mb-0 dir-table dir-table--ellipsis marketing-workorders-table">
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
                                        <th>Invoice</th>
                                        <th>Invoice Date</th>
                                        <th>Ship Date</th>
                                        <th>AWB #</th>
                                        <th>Files</th>
                                    </tr>
                                    <tr class="marketing-workorders-filter-row">
                                        <th><input type="search" data-workorder-filter="number" placeholder="WO #"></th>
                                        <th><input type="search" data-workorder-filter="status" placeholder="Status"></th>
                                        <th><input type="search" data-workorder-filter="ro" placeholder="RO#"></th>
                                        <th><input type="search" data-workorder-filter="part" placeholder="P/N"></th>
                                        <th><input type="search" data-workorder-filter="description" placeholder="Description"></th>
                                        <th><input type="search" data-workorder-filter="serial" placeholder="S/N"></th>
                                        <th><input type="search" data-workorder-filter="task" placeholder="Task"></th>
                                        <th><input type="search" data-workorder-filter="terms" placeholder="Terms"></th>
                                        <th><input type="search" data-workorder-filter="estimate" placeholder="Estimate"></th>
                                        <th><input type="search" data-workorder-filter="estimate_date" placeholder="Date"></th>
                                        <th><input type="search" data-workorder-filter="approval_date" placeholder="Date"></th>
                                        <th><input type="search" data-workorder-filter="invoice" placeholder="Invoice"></th>
                                        <th><input type="search" data-workorder-filter="invoice_date" placeholder="Date"></th>
                                        <th><input type="search" data-workorder-filter="ship_date" placeholder="Date"></th>
                                        <th><input type="search" data-workorder-filter="awb" placeholder="AWB #"></th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody id="marketingWorkordersRows">
                                    <tr><td colspan="16" class="text-center text-muted py-4">Select company</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="marketing-actions">
                                <div id="marketingWorkordersMore" class="marketing-muted small" hidden></div>
                            </div>
                        </div>
                    </div>

                    <div id="marketingPaneSalesReport" class="marketing-pane" data-pane="sales_report" role="tabpanel" aria-labelledby="marketingTabSalesReport">
                        <div class="marketing-section">
                            <div class="marketing-sales-report-toolbar">
                                <div class="marketing-sales-report-heading">
                                    <h3 class="marketing-section-title mb-0">Sales Report</h3>
                                    <span id="marketingSalesReportCompany" class="marketing-sales-report-company"></span>
                                    <span id="marketingSalesReportWarning" class="marketing-sales-report-warning" hidden></span>
                                </div>
                                <div class="marketing-sales-report-filters marketing-sales-report-actions">
                                    <div class="btn-group btn-group-sm marketing-sales-report-mode" role="group" aria-label="Sales report type">
                                        <input class="btn-check" type="radio" name="marketingSalesReportMode" id="marketingSalesReportModeCustomer" value="customer" autocomplete="off" checked>
                                        <label class="btn btn-outline-info" for="marketingSalesReportModeCustomer">Customer</label>
                                        <input class="btn-check" type="radio" name="marketingSalesReportMode" id="marketingSalesReportModeAircraft" value="aircraft" autocomplete="off">
                                        <label class="btn btn-outline-info" for="marketingSalesReportModeAircraft">A/C Type</label>
                                    </div>
                                    <div id="marketingSalesReportAircraftWrap" class="marketing-sales-report-aircraft" hidden>
                                        <label for="marketingSalesReportAircraft">A/C Type</label>
                                        <select id="marketingSalesReportAircraft" class="form-select form-select-sm marketing-report-aircraft-select" autocomplete="off">
                                            <option value=""></option>
                                            @foreach($planes as $plane)
                                                <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="marketing-sales-report-date">
                                        <label for="marketingSalesDateFrom">From</label>
                                        <input id="marketingSalesDateFrom" class="form-control form-control-sm" type="date" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                                    </div>
                                    <div class="marketing-sales-report-date">
                                        <label for="marketingSalesDateTo">To</label>
                                        <input id="marketingSalesDateTo" class="form-control form-control-sm" type="date" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                                    </div>
                                    <button id="marketingSalesReportRefresh" class="btn btn-sm btn-outline-primary" type="button">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        <span>Build</span>
                                    </button>
                                    <button id="marketingSalesReportPrint" class="btn btn-sm btn-outline-info" type="button">
                                        <i class="bi bi-printer"></i>
                                        <span>Print</span>
                                    </button>
                                </div>
                            </div>

                            <div class="marketing-sales-report-wrap dir-table-wrap">
                                <table class="table table-sm table-hover align-middle mb-0 dir-table dir-table--ellipsis marketing-sales-report-table">
                                    <thead>
                                    <tr>
                                        <th data-sales-report-customer-col hidden>Customer</th>
                                        <th>WO#</th>
                                        <th>P/N</th>
                                        <th>S/N</th>
                                        <th>Description</th>
                                        <th>Invoiced Amount</th>
                                        <th>Date</th>
                                    </tr>
                                    </thead>
                                    <tbody id="marketingSalesReportRows">
                                    <tr><td colspan="6" class="text-center text-muted py-4">Select company</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="marketing-sales-report-note" id="marketingSalesReportNote">*NOTE: Report based on one customer</div>
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
                            <input name="name" class="form-control form-control-sm" type="text" required maxlength="250" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
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
                            <select id="createCountryId" name="country_id" class="form-select form-select-sm marketing-country-select" autocomplete="off">
                                <option value=""></option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="marketing-field">
                            <label>Type of Business</label>
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
                        <div class="marketing-field">
                            <label>City</label>
                            <select id="createCity" name="city" class="form-select form-select-sm marketing-city-select" data-country-select="#createCountryId" autocomplete="off"></select>
                        </div>
                        <div class="marketing-field">
                            <label>State/Province</label>
                            <input name="state_province" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true">
                        </div>
                        <div class="marketing-address-line span-2">
                            <div class="marketing-field marketing-post-code-field">
                                <label>Post Code</label>
                                <input name="post_code" class="form-control form-control-sm" type="text" maxlength="40" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false">
                            </div>
                            <div class="marketing-field">
                                <label>Street Address</label>
                                <textarea name="street_address" class="form-control form-control-sm" rows="1" autocomplete="off" autocorrect="on" autocapitalize="sentences" spellcheck="true"></textarea>
                            </div>
                        </div>
                        <div class="marketing-field span-2">
                            <label>Company Notes</label>
                            <textarea name="company_notes" class="form-control form-control-sm" rows="1" autocomplete="off" autocorrect="on" autocapitalize="sentences" spellcheck="true"></textarea>
                        </div>
                        <div class="marketing-field span-2">
                            <label>A/C Type</label>
                            <select name="aircraft_ids[]" class="form-select form-select-sm marketing-aircraft-select" multiple autocomplete="off">
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

    <div class="modal fade" id="marketingWorkorderSalesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="marketingWorkorderSalesForm" data-no-spinner autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="marketingWorkorderSalesTitle">Edit WO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="marketing-form-grid">
                        <div class="marketing-field">
                            <label>Terms</label>
                            <input name="wo_terms" class="form-control form-control-sm" type="text" maxlength="120" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>WO Estimate</label>
                            <input name="wo_estimate_amount" class="form-control form-control-sm" type="text" maxlength="60" placeholder="$0.00" inputmode="decimal" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>WO Estimate Date</label>
                            <input name="estimate_date" class="form-control form-control-sm" type="date" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>Invoice</label>
                            <input name="sales_invoice_amount" class="form-control form-control-sm" type="text" maxlength="60" placeholder="$0.00" inputmode="decimal" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>Invoice Date</label>
                            <input name="sales_invoice_date" class="form-control form-control-sm" type="date" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>Ship Date</label>
                            <input name="shipping_shipment_at" class="form-control form-control-sm" type="date" autocomplete="off">
                        </div>
                        <div class="marketing-field">
                            <label>AWB #</label>
                            <input name="shipping_awb_no" class="form-control form-control-sm" type="text" maxlength="255" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
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

    <div class="modal fade" id="marketingUnsavedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Unsaved changes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You have unsaved changes. Leave without saving?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Stay</button>
                    <button type="button" class="btn btn-sm btn-warning" data-unsaved-confirm>Leave</button>
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
                cities: @json(route('marketing.cities')),
                storeCustomer: @json(route('marketing.customers.store')),
                showCustomer: @json(route('marketing.customers.show', ['customer' => '__ID__'])),
                updateProfile: @json(route('marketing.customers.profile.update', ['customer' => '__ID__'])),
                workorders: @json(route('marketing.customers.workorders', ['customer' => '__ID__'])),
                updateWorkorderSalesFields: @json(route('marketing.workorders.sales-fields.update', ['workorder' => '__ID__'])),
                salesReport: @json(route('marketing.customers.sales-report', ['customer' => '__ID__'])),
                aircraftSalesReport: @json(route('marketing.sales-report.aircraft')),
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
            const selectedCustomerKey = 'selected_customer_id';
            const overviewTextareaHeightsKey = 'overview_textarea_heights';
            const allowedTabs = ['overview', 'contacts', 'notes', 'workorders', 'sales_report'];
            const addressCategoryLabels = @json($addressCategoryLabels);
            const addressCategoryKeys = Object.keys(addressCategoryLabels);
            const profileAddressFieldNames = ['country_id', 'city', 'state_province', 'post_code', 'street_address'];

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
            const contactNewToggle = document.querySelector('[data-contact-new-toggle]');
            const noteForm = document.getElementById('marketingNoteForm');
            const createForm = document.getElementById('marketingCreateForm');
            const contactsList = document.getElementById('marketingContactsList');
            const notesList = document.getElementById('marketingNotesList');
            const addressCategories = document.getElementById('marketingAddressCategories');
            const overviewTextareaFields = {
                street_address: document.getElementById('detailStreetAddress'),
                company_notes: document.getElementById('detailCompanyNotes'),
            };
            const noteContact = document.getElementById('noteContact');
            const workordersRows = document.getElementById('marketingWorkordersRows');
            const workordersScroll = document.getElementById('marketingWorkordersScroll');
            const workordersMore = document.getElementById('marketingWorkordersMore');
            const workordersSearchWrap = document.getElementById('marketingWorkordersSearchWrap');
            const workordersSearch = document.getElementById('marketingWorkordersSearch');
            const workordersSearchClear = document.getElementById('marketingWorkordersSearchClear');
            const workorderFilterInputs = Array.from(document.querySelectorAll('[data-workorder-filter]'));
            const salesReportRows = document.getElementById('marketingSalesReportRows');
            const salesReportCompany = document.getElementById('marketingSalesReportCompany');
            const salesReportWarning = document.getElementById('marketingSalesReportWarning');
            const salesReportNote = document.getElementById('marketingSalesReportNote');
            const salesReportModeInputs = document.querySelectorAll('input[name="marketingSalesReportMode"]');
            const salesReportAircraftWrap = document.getElementById('marketingSalesReportAircraftWrap');
            const salesReportAircraft = document.getElementById('marketingSalesReportAircraft');
            const salesReportDateFrom = document.getElementById('marketingSalesDateFrom');
            const salesReportDateTo = document.getElementById('marketingSalesDateTo');
            const salesReportRefresh = document.getElementById('marketingSalesReportRefresh');
            const salesReportPrint = document.getElementById('marketingSalesReportPrint');
            const workorderSalesModalEl = document.getElementById('marketingWorkorderSalesModal');
            const workorderSalesForm = document.getElementById('marketingWorkorderSalesForm');
            const workorderSalesDateInputs = workorderSalesForm
                ? Array.from(workorderSalesForm.querySelectorAll('input[type="date"]'))
                : [];
            const workorderSalesTitle = document.getElementById('marketingWorkorderSalesTitle');
            const mediaModalEl = document.getElementById('marketingMediaModal');
            const mediaTitle = document.getElementById('marketingMediaTitle');
            const mediaBody = document.getElementById('marketingMediaBody');
            const unsavedModalEl = document.getElementById('marketingUnsavedModal');
            const unsavedConfirmButton = unsavedModalEl?.querySelector('[data-unsaved-confirm]');
            const workordersColumnCount = 16;

            const filterEls = {
                q: document.getElementById('marketingSearch'),
                lifecycle_status: document.getElementById('marketingLifecycle'),
                country_id: document.getElementById('marketingCountry'),
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
                workordersPendingReset: false,
                workordersRequestSeq: 0,
                workordersFilterRevision: 0,
                workordersById: new Map(),
                editingWorkorderId: null,
                salesReportLoaded: false,
                salesReportLoading: false,
                salesReportMode: 'customer',
                activeAddressCategory: addressCategoryKeys[0] || '',
                addressDrafts: [],
            };
            let overviewTextareaHeights = {};
            let overviewTextareaHeightsRestored = false;
            let overviewTextareaHeightsSaveTimer = null;
            let applyingOverviewTextareaHeights = false;
            let unsavedPromptPromise = null;
            const unsavedChangesMessage = 'You have unsaved changes. Continue without saving?';

            const loadingDotsHtml = '<span class="marketing-loading-dots"><span></span><span></span><span></span></span>';

            function loadingHtml(label = 'Loading') {
                return `${escapeHtml(label)}${loadingDotsHtml}`;
            }

            function setDetailTitleLoading() {
                detailTitle.classList.add('is-loading');
                detailTitle.innerHTML = loadingHtml('Loading');
                detailMeta.textContent = '';
                detailMeta.hidden = true;
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
                    const type = String(el.getAttribute('type') || (el.tagName === 'TEXTAREA' ? 'textarea' : 'text')).toLowerCase();
                    const fieldName = String(el.getAttribute('name') || el.id || '').toLowerCase();
                    const supportsWritingAssist = !['date', 'email', 'hidden', 'number', 'password', 'search', 'tel', 'url'].includes(type)
                        && !el.matches('[data-project-date]')
                        && !fieldName.includes('email')
                        && !fieldName.includes('phone')
                        && !fieldName.includes('alpha2')
                        && !fieldName.includes('amount')
                        && !fieldName.includes('awb');

                    el.setAttribute('autocomplete', 'new-password');
                    el.setAttribute('autocorrect', supportsWritingAssist ? 'on' : 'off');
                    el.setAttribute('autocapitalize', supportsWritingAssist ? (el.tagName === 'TEXTAREA' ? 'sentences' : 'words') : 'off');
                    el.setAttribute('spellcheck', supportsWritingAssist ? 'true' : 'false');

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
                return !!target?.closest?.('[data-marketing-page], #marketingCreateModal, #marketingWorkorderSalesModal');
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

                if (form === profileForm) {
                    data.address_categories = addressCategoriesForSubmit();
                } else if (form === createForm) {
                    data.address_categories = addressCategoriesFromForm(createForm);
                }

                return data;
            }

            function formStateSignature(form) {
                const data = formDataObject(form);

                if (form === profileForm) {
                    profileAddressFieldNames.forEach((key) => delete data[key]);
                }

                return JSON.stringify(data);
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

            function hasUnsavedChanges() {
                return Boolean(document.querySelector('[data-marketing-page] .marketing-save-button.is-dirty'));
            }

            function confirmDiscardUnsavedChanges() {
                if (!hasUnsavedChanges()) return Promise.resolve(true);
                if (!unsavedModalEl || !window.bootstrap?.Modal) {
                    return Promise.resolve(window.confirm(unsavedChangesMessage));
                }
                if (unsavedPromptPromise) return unsavedPromptPromise;

                unsavedPromptPromise = new Promise((resolve) => {
                    const modal = window.bootstrap.Modal.getOrCreateInstance(unsavedModalEl);
                    let resolved = false;

                    function finish(result, hideModal = true) {
                        if (resolved) return;
                        resolved = true;
                        unsavedConfirmButton?.removeEventListener('click', onConfirm);
                        unsavedModalEl.removeEventListener('hidden.bs.modal', onHidden);
                        unsavedPromptPromise = null;
                        resolve(result);
                        if (hideModal) modal.hide();
                    }

                    function onConfirm() {
                        finish(true);
                    }

                    function onHidden() {
                        finish(false, false);
                    }

                    unsavedConfirmButton?.addEventListener('click', onConfirm);
                    unsavedModalEl.addEventListener('hidden.bs.modal', onHidden);
                    modal.show();
                });

                return unsavedPromptPromise;
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
                    setSelectValue(el, filters?.[key] || '');
                });
                updateFilterStates();
            }

            function updateFilterStates() {
                Object.values(filterEls).forEach((el) => {
                    const isActive = String(el.value || '') !== '';
                    const filter = el.closest('.marketing-filter');

                    filter?.classList.toggle('is-active', isActive);
                    filter?.classList.toggle('has-clear', isActive && el.tagName === 'SELECT');
                });
            }

            function initFilterClearButtons() {
                Object.values(filterEls).forEach((el) => {
                    if (el.tagName !== 'SELECT') return;

                    const filter = el.closest('.marketing-filter');
                    if (!filter || filter.querySelector('.marketing-filter-clear')) return;

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'marketing-filter-clear';
                    button.innerHTML = '<i class="bi bi-x-lg"></i>';
                    button.title = 'Clear filter';
                    button.setAttribute('aria-label', 'Clear filter');

                    button.addEventListener('click', async (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        setSelectValue(el, '');
                        updateFilterStates();
                        await saveFilters();
                        await loadCustomers(true);
                    });

                    filter.appendChild(button);
                });

                updateFilterStates();
            }

            function currentWorkorderFilters() {
                const filters = {};
                const search = String(workordersSearch?.value || '').trim();

                if (search !== '') {
                    filters.wo_q = search;
                }

                workorderFilterInputs.forEach((input) => {
                    const key = input.dataset.workorderFilter;
                    const value = String(input.value || '').trim();
                    if (key && value !== '') {
                        filters[`wo_${key}`] = value;
                    }
                });

                return filters;
            }

            function updateWorkorderFilterStates() {
                const hasSearch = String(workordersSearch?.value || '').trim() !== '';
                workordersSearchWrap?.classList.toggle('has-clear', hasSearch);

                workorderFilterInputs.forEach((input) => {
                    input.closest('th')?.classList.toggle('is-active', String(input.value || '').trim() !== '');
                });
            }

            function clearWorkorderSearch() {
                if (!workordersSearch) return;

                workordersSearch.value = '';
                reloadWorkordersForFilterChange();
            }

            function reloadWorkordersForFilterChange() {
                state.workordersFilterRevision += 1;
                updateWorkorderFilterStates();
                state.workordersLoaded = false;

                if (state.activeTab === 'workorders' && state.selectedCustomer) {
                    loadWorkorders(true);
                }
            }

            function clampPanelWidth(width) {
                if (!shell) return null;

                const shellWidth = shell.getBoundingClientRect().width;
                if (!Number.isFinite(shellWidth) || shellWidth < 700) return null;

                const minLeft = 190;
                const minRight = 390;
                const handleWidth = splitter?.getBoundingClientRect().width || 14;
                const gapWidth = 16;
                const maxLeft = shellWidth - minRight - handleWidth - gapWidth;

                if (maxLeft <= minLeft) return null;

                return Math.min(Math.max(Math.round(width), minLeft), Math.round(maxLeft));
            }

            function updatePanelMode(width) {
                if (!shell) return;
                shell.classList.toggle('is-company-only', Number(width) <= 260);
            }

            function setPanelWidth(width, persist = false) {
                const clamped = clampPanelWidth(width);
                if (!clamped) return;

                shell.style.setProperty('--marketing-left-width', `${clamped}px`);
                updatePanelMode(clamped);

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
                    await switchTab(saved || 'overview', false, { skipUnsavedCheck: true });
                } catch (_) {
                    await switchTab('overview', false, { skipUnsavedCheck: true });
                }
            }

            function overviewTextareaEntries() {
                return Object.entries(overviewTextareaFields).filter(([, element]) => element instanceof HTMLElement);
            }

            function overviewTextareaMinHeight(element) {
                const minHeight = Number.parseFloat(window.getComputedStyle(element).minHeight);
                return Number.isFinite(minHeight) ? minHeight : 0;
            }

            function applyOverviewTextareaHeights() {
                applyingOverviewTextareaHeights = true;
                overviewTextareaEntries().forEach(([key, element]) => {
                    const savedHeight = Number(overviewTextareaHeights?.[key] || 0);

                    if (savedHeight > 0) {
                        const minHeight = Math.ceil(overviewTextareaMinHeight(element));
                        element.style.height = `${Math.max(Math.round(savedHeight), minHeight)}px`;
                    } else {
                        element.style.height = '';
                    }
                });

                window.requestAnimationFrame(() => {
                    applyingOverviewTextareaHeights = false;
                });
            }

            async function restoreOverviewTextareaHeights() {
                try {
                    const saved = await window.UserUiSettings?.get(scope, overviewTextareaHeightsKey, {});
                    overviewTextareaHeights = saved && typeof saved === 'object' ? saved : {};
                } catch (_) {
                    overviewTextareaHeights = {};
                } finally {
                    overviewTextareaHeightsRestored = true;
                    applyOverviewTextareaHeights();
                }
            }

            function currentOverviewTextareaHeights() {
                const nextHeights = { ...(overviewTextareaHeights || {}) };

                overviewTextareaEntries().forEach(([key, element]) => {
                    const renderedHeight = element.getBoundingClientRect().height;
                    if (renderedHeight <= 0) return;

                    nextHeights[key] = Math.round(Math.max(renderedHeight, overviewTextareaMinHeight(element)));
                });

                return nextHeights;
            }

            function scheduleOverviewTextareaHeightSave() {
                if (!overviewTextareaHeightsRestored || applyingOverviewTextareaHeights) return;

                window.clearTimeout(overviewTextareaHeightsSaveTimer);
                overviewTextareaHeightsSaveTimer = window.setTimeout(() => {
                    overviewTextareaHeights = currentOverviewTextareaHeights();
                    window.UserUiSettings?.set(scope, overviewTextareaHeightsKey, overviewTextareaHeights)?.catch(() => {});
                }, 220);
            }

            function initOverviewTextareaResizePersistence() {
                const entries = overviewTextareaEntries();
                if (!entries.length) return;

                if ('ResizeObserver' in window) {
                    const trackedElements = new Set(entries.map(([, element]) => element));
                    const observer = new ResizeObserver((resizeEntries) => {
                        if (resizeEntries.some((entry) => trackedElements.has(entry.target))) {
                            scheduleOverviewTextareaHeightSave();
                        }
                    });

                    entries.forEach(([, element]) => observer.observe(element));
                    return;
                }

                entries.forEach(([, element]) => {
                    element.addEventListener('mouseup', scheduleOverviewTextareaHeightSave);
                    element.addEventListener('keyup', scheduleOverviewTextareaHeightSave);
                });
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

                if (event.key === 'Home') return setPanelWidth(190, true);
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

            async function saveSelectedCustomer(id) {
                try {
                    await window.UserUiSettings?.set(scope, selectedCustomerKey, id ? String(id) : null);
                } catch (_) {}
            }

            async function restoreSelectedCustomerId() {
                const urlCustomerId = new URLSearchParams(window.location.search).get('customer');
                if (urlCustomerId) return urlCustomerId;

                try {
                    return await window.UserUiSettings?.get(scope, selectedCustomerKey, null);
                } catch (_) {
                    return null;
                }
            }

            function debounce(fn, wait) {
                let timer = null;
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            async function openCustomer(id, options = {}) {
                if (!options.skipUnsavedCheck && Number(id) !== Number(state.selectedId) && !(await confirmDiscardUnsavedChanges())) {
                    return false;
                }

                state.selectedId = id;
                state.workordersLoaded = false;
                state.workordersPage = 1;
                state.workordersHasMore = false;
                state.workordersLoading = false;
                state.workordersPendingReset = false;
                state.workordersRequestSeq += 1;
                state.workordersFilterRevision += 1;
                state.salesReportLoaded = false;
                state.salesReportLoading = false;
                if (salesReportWarning) {
                    salesReportWarning.hidden = true;
                    salesReportWarning.textContent = '';
                }
                if (salesReportCompany) {
                    salesReportCompany.textContent = state.salesReportMode === 'customer' ? '' : selectedSalesReportAircraftLabel();
                }
                if (salesReportRows && state.salesReportMode === 'customer') {
                    salesReportRows.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Select company</td></tr>';
                }
                renderRows(false);

                detail.classList.add('is-open');
                setDetailTitleLoading();

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('customer', id);
                window.history.replaceState({}, '', nextUrl.toString());

                try {
                    const data = await requestJson(urlFor(routes.showCustomer, id), { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    state.selectedCustomer = data.customer;
                    await saveSelectedCustomer(data.customer.id);
                    renderDetail();

                    if (state.activeTab === 'workorders') {
                        await loadWorkorders(true);
                    }

                    if (state.activeTab === 'sales_report') {
                        await loadSalesReport(true);
                    }
                } catch (error) {
                    notify(error.message, 'error');
                    return false;
                }

                return true;
            }

            function selectedAircraftIds(customer) {
                return (customer.aircraft || []).map((item) => String(item.id));
            }

            function setSelectMultiple(select, values) {
                const set = new Set((values || []).map(String));
                Array.from(select.options).forEach((option) => {
                    option.selected = set.has(String(option.value));
                });

                if (window.jQuery?.fn?.select2 && window.jQuery(select).data('select2')) {
                    window.jQuery(select).val(Array.from(set)).trigger('change.select2');
                }
            }

            function setSelectValue(select, value) {
                if (!select) return;

                select.value = value ? String(value) : '';

                if (window.jQuery?.fn?.select2 && window.jQuery(select).data('select2')) {
                    window.jQuery(select).val(select.value).trigger('change.select2');
                }
            }

            function resetEnhancedSelects(root) {
                root.querySelectorAll('select').forEach((select) => {
                    if (window.jQuery?.fn?.select2 && window.jQuery(select).data('select2')) {
                        window.jQuery(select).val(select.multiple ? [] : '').trigger('change.select2');
                    }
                });
            }

            function countrySelectForCity(select) {
                const selector = select?.dataset?.countrySelect || '';
                return selector ? document.querySelector(selector) : select?.closest('form')?.querySelector('[name="country_id"]');
            }

            function setCitySelectValue(select, value) {
                if (!select) return;

                const clean = value ? String(value) : '';
                if (clean && !Array.from(select.options).some((option) => option.value === clean)) {
                    select.add(new Option(clean, clean, true, true));
                }

                setSelectValue(select, clean);
            }

            function ownValue(object, key, fallback = '') {
                if (object && Object.prototype.hasOwnProperty.call(object, key)) {
                    return object[key] ?? '';
                }

                return fallback ?? '';
            }

            function addressCategoryLabel(key) {
                return addressCategoryLabels[key] || key;
            }

            function addressDraftValue(object, key, fallback = '') {
                const value = ownValue(object, key, fallback);
                return value === null || typeof value === 'undefined' ? '' : String(value);
            }

            function normalizeAddressDraft(item, key, fallback = {}) {
                return {
                    key,
                    label: addressCategoryLabel(key),
                    country_id: addressDraftValue(item, 'country_id', fallback.country_id),
                    city: addressDraftValue(item, 'city', fallback.city),
                    state_province: addressDraftValue(item, 'state_province', fallback.state_province),
                    post_code: addressDraftValue(item, 'post_code', fallback.post_code),
                    street_address: addressDraftValue(item, 'street_address', fallback.street_address),
                };
            }

            function findAddressCategory(categories, key) {
                return (categories || []).find((item) => item?.key === key) || null;
            }

            function baseAddressFromCustomer(customer) {
                const profile = customer?.profile || {};

                return {
                    country_id: profile.country_id || customer?.country_id || '',
                    city: profile.city || customer?.city || '',
                    state_province: profile.state_province || customer?.state_province || '',
                    post_code: profile.post_code || customer?.post_code || '',
                    street_address: profile.street_address || profile.address || customer?.street_address || customer?.address || '',
                };
            }

            function addressCategoriesFromCustomer(customer) {
                const profile = customer?.profile || {};
                const categories = Array.isArray(profile.address_categories)
                    ? profile.address_categories
                    : (Array.isArray(customer?.address_categories) ? customer.address_categories : []);
                const fallback = baseAddressFromCustomer(customer);

                return addressCategoryKeys.map((key) => normalizeAddressDraft(findAddressCategory(categories, key), key, fallback));
            }

            function baseAddressFromForm(form) {
                return {
                    country_id: form?.elements?.country_id?.value || '',
                    city: form?.elements?.city?.value || '',
                    state_province: form?.elements?.state_province?.value || '',
                    post_code: form?.elements?.post_code?.value || '',
                    street_address: form?.elements?.street_address?.value || '',
                };
            }

            function addressCategoriesFromForm(form) {
                const fallback = baseAddressFromForm(form);
                return addressCategoryKeys.map((key) => normalizeAddressDraft({}, key, fallback));
            }

            function collectCurrentAddressDraft() {
                return normalizeAddressDraft(baseAddressFromForm(profileForm), state.activeAddressCategory);
            }

            function stashActiveAddressDraft() {
                if (!state.activeAddressCategory || !profileForm) return;

                const current = collectCurrentAddressDraft();
                const byKey = new Map((state.addressDrafts || []).map((item) => [item.key, item]));
                byKey.set(state.activeAddressCategory, current);
                state.addressDrafts = addressCategoryKeys.map((key) => normalizeAddressDraft(byKey.get(key), key));
            }

            function addressCategoriesForSubmit() {
                stashActiveAddressDraft();
                const byKey = new Map((state.addressDrafts || []).map((item) => [item.key, item]));

                return addressCategoryKeys.map((key) => normalizeAddressDraft(byKey.get(key), key));
            }

            function renderAddressCategoryButtons() {
                if (!addressCategories) return;

                addressCategories.innerHTML = addressCategoryKeys.map((key) => {
                    const active = key === state.activeAddressCategory;
                    return `<button class="btn btn-sm btn-outline-info marketing-address-category-button ${active ? 'is-active' : ''}" type="button" data-address-category="${escapeHtml(key)}" aria-pressed="${active ? 'true' : 'false'}">${escapeHtml(addressCategoryLabel(key))}</button>`;
                }).join('');
            }

            function applyAddressDraftToForm(key) {
                const draft = (state.addressDrafts || []).find((item) => item.key === key) || normalizeAddressDraft({}, key);

                setSelectValue(profileForm.country_id, draft.country_id || '');
                setCitySelectValue(profileForm.city, draft.city || '');
                profileForm.state_province.value = draft.state_province || '';
                profileForm.post_code.value = draft.post_code || '';
                profileForm.street_address.value = draft.street_address || '';
            }

            function selectAddressCategory(key) {
                if (!addressCategoryKeys.includes(key) || key === state.activeAddressCategory) return;

                stashActiveAddressDraft();
                state.activeAddressCategory = key;
                applyAddressDraftToForm(key);
                renderAddressCategoryButtons();
                refreshFormDirtyState(profileForm);
            }

            function renderAddressCategories(customer) {
                const previousKey = state.activeAddressCategory;
                state.addressDrafts = addressCategoriesFromCustomer(customer);
                state.activeAddressCategory = addressCategoryKeys.includes(previousKey)
                    ? previousKey
                    : (state.addressDrafts[0]?.key || addressCategoryKeys[0] || '');
                renderAddressCategoryButtons();
                applyAddressDraftToForm(state.activeAddressCategory);
            }

            function initMarketingCountrySelects(root = document) {
                if (!window.jQuery?.fn?.select2) return;

                window.jQuery(root).find('.marketing-country-select').each(function () {
                    const $select = window.jQuery(this);
                    if ($select.data('select2')) return;

                    const modal = this.closest('.modal');
                    $select.select2({
                        width: '100%',
                        placeholder: this.id === 'marketingCountry' ? 'All' : 'Select country',
                        allowClear: true,
                        dropdownParent: modal ? window.jQuery(modal) : window.jQuery(document.body),
                    });
                });
            }

            function initMarketingCitySelects(root = document) {
                if (!window.jQuery?.fn?.select2) return;

                window.jQuery(root).find('.marketing-city-select').each(function () {
                    const $select = window.jQuery(this);
                    if ($select.data('select2')) return;

                    const select = this;
                    const modal = select.closest('.modal');
                    $select.select2({
                        width: '100%',
                        placeholder: 'Select city',
                        allowClear: true,
                        tags: true,
                        dropdownParent: modal ? window.jQuery(modal) : window.jQuery(document.body),
                        ajax: {
                            url: routes.cities,
                            dataType: 'json',
                            delay: 180,
                            data(params) {
                                return {
                                    q: params.term || '',
                                    country_id: countrySelectForCity(select)?.value || '',
                                };
                            },
                            processResults(data) {
                                return { results: data.results || [] };
                            },
                            cache: true,
                        },
                        createTag(params) {
                            const term = (params.term || '').trim();
                            return term ? { id: term, text: term, newTag: true } : null;
                        },
                    });
                });
            }

            function initMarketingAircraftSelects(root = document) {
                if (!window.jQuery?.fn?.select2) return;

                window.jQuery(root).find('.marketing-aircraft-select').each(function () {
                    const $select = window.jQuery(this);
                    if ($select.data('select2')) return;

                    const modal = this.closest('.modal');
                    $select.select2({
                        width: '100%',
                        placeholder: 'Select A/C Type',
                        allowClear: true,
                        closeOnSelect: false,
                        dropdownParent: modal ? window.jQuery(modal) : window.jQuery(document.body),
                    });
                });
            }

            function renderDetail() {
                const customer = state.selectedCustomer;
                if (!customer) return;

                setDetailTitleText(customer.name);
                detailMeta.textContent = '';
                detailMeta.hidden = true;

                profileForm.name.value = customer.name || '';
                profileForm.lifecycle_status.value = customer.profile?.lifecycle_status || 'existing';
                profileForm.company_notes.value = customer.profile?.company_notes || '';
                profileForm.company_type_id.value = customer.profile?.company_type_id || '';
                profileForm.segment_id.value = customer.profile?.segment_id || '';
                profileForm.terms_label.value = customer.profile?.terms_label || '';
                renderAddressCategories(customer);
                applyOverviewTextareaHeights();
                setSelectMultiple(document.getElementById('detailAircraft'), selectedAircraftIds(customer));
                markFormClean(profileForm);
                setNewContactFormVisible(false);

                renderContacts(customer.contacts || []);
                renderNoteContactOptions(customer.contacts || []);
                renderNotes(customer.notes || []);
                disableAutocomplete(detail);
                window.initProjectDatePickers?.(detail);
            }

            function setContactFormEditing(form, editing) {
                if (!form) return;

                form.classList.toggle('is-editing', editing);
                form.querySelectorAll('input[name="first_name"], input[name="last_name"], input[name="position"], input[name="email"], input[name="phone"]').forEach((input) => {
                    input.readOnly = !editing;
                });

                form.querySelectorAll('input[type="checkbox"][name="is_primary"]').forEach((input) => {
                    input.disabled = !editing;
                });

                refreshFormDirtyState(form);
            }

            function contactCopyText(contacts, mode) {
                if (mode === 'emails') {
                    return contacts.map((contact) => contact.email || '').filter(Boolean).join('\n');
                }

                if (mode === 'phones') {
                    return contacts.map((contact) => contact.phone || '').filter(Boolean).join('\n');
                }

                return contacts.map((contact) => {
                    const lines = [
                        contact.full_name || [contact.first_name, contact.last_name].filter(Boolean).join(' '),
                        contact.position,
                        contact.email,
                        contact.phone,
                        contact.is_primary ? 'Primary contact' : '',
                    ].filter(Boolean);

                    return lines.join('\n');
                }).filter(Boolean).join('\n\n');
            }

            async function copyTextToClipboard(text) {
                if (!text.trim()) {
                    notify('Nothing to copy', 'error');
                    return;
                }

                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.setAttribute('readonly', 'readonly');
                        textarea.style.position = 'fixed';
                        textarea.style.left = '-9999px';
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        textarea.remove();
                    }

                    notify('Copied');
                } catch (error) {
                    notify('Copy failed', 'error');
                }
            }

            function copyMarketingContacts(mode) {
                const contacts = state.selectedCustomer?.contacts || [];
                copyTextToClipboard(contactCopyText(contacts, mode));
            }

            function setNewContactFormVisible(visible) {
                contactForm.hidden = !visible;
                contactNewToggle?.classList.toggle('active', visible);
                contactNewToggle?.setAttribute('aria-expanded', visible ? 'true' : 'false');

                if (visible) {
                    contactForm.querySelector('input[name="first_name"]')?.focus();
                } else {
                    contactForm.reset();
                }
            }

            function renderContacts(contacts) {
                if (!contacts.length) {
                    contactsList.innerHTML = '<div class="marketing-empty">No contacts</div>';
                    return;
                }

                contactsList.innerHTML = contacts.map((contact) => `
<form class="marketing-contact-row js-contact-row" data-contact-id="${contact.id}" data-no-spinner autocomplete="off">
  <input name="first_name" class="form-control form-control-sm" value="${escapeHtml(contact.first_name)}" placeholder="First Name" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true" readonly>
  <input name="last_name" class="form-control form-control-sm" value="${escapeHtml(contact.last_name)}" placeholder="Last Name" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true" readonly>
  <input name="position" class="form-control form-control-sm" value="${escapeHtml(contact.position)}" placeholder="Position" autocomplete="off" autocorrect="on" autocapitalize="words" spellcheck="true" readonly>
  <input name="email" class="form-control form-control-sm" value="${escapeHtml(contact.email)}" placeholder="Email" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" readonly>
  <input name="phone" class="form-control form-control-sm" value="${escapeHtml(contact.phone)}" placeholder="Phone" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" readonly>
  <div class="marketing-contact-primary-actions">
    <label class="d-flex align-items-center gap-2 small fw-bold mb-0">
      <input name="is_primary" class="form-check-input mt-0" type="checkbox" value="1" ${contact.is_primary ? 'checked' : ''} disabled>
      Primary
    </label>
    <div class="marketing-contact-actions">
      <button class="btn btn-sm btn-outline-secondary marketing-contact-edit" type="button" title="Edit contact"><i class="bi bi-pencil"></i><span>Edit</span></button>
      <button class="btn btn-sm btn-outline-secondary marketing-contact-cancel" type="button" title="Cancel editing"><i class="bi bi-x-lg"></i></button>
      <button class="btn btn-sm btn-outline-primary marketing-save-button marketing-contact-save" type="submit" data-save-button title="Save"><i class="bi bi-check-lg" data-save-icon></i></button>
      <button class="btn btn-sm btn-outline-danger js-contact-delete" type="button" title="Delete"><i class="bi bi-trash"></i></button>
    </div>
  </div>
</form>`).join('');
                disableAutocomplete(contactsList);
                contactsList.querySelectorAll('.js-contact-row').forEach((form) => {
                    setContactFormEditing(form, false);
                    markFormClean(form);
                });
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

            async function saveProfile(event = null) {
                event?.preventDefault?.();
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
                    setNewContactFormVisible(false);
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
                    resetEnhancedSelects(createForm);
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

            function renderWorkorderRow(wo) {
                const isComplete = String(wo.status || '').trim().toLowerCase() === 'complete';

                return `
<tr class="${isComplete ? 'marketing-workorder-complete' : ''}" data-workorder-id="${Number(wo.id || 0)}">
  <td><a href="${escapeHtml(wo.urls.open)}">${escapeHtml(wo.number_label)}</a></td>
  <td>${escapeHtml(wo.status || '-')}</td>
  <td>${escapeHtml(wo.ro_number || '-')}</td>
  <td>${escapeHtml(wo.part_number || '-')}</td>
  <td title="${escapeHtml(wo.description || '')}">${escapeHtml(wo.description || '-')}</td>
  <td>${escapeHtml(wo.serial_number || '-')}</td>
  <td>${escapeHtml(wo.task || '-')}</td>
  <td>${escapeHtml(wo.terms || '-')}</td>
  <td>${escapeHtml(wo.estimate_amount?.display || '-')}</td>
  <td>${escapeHtml(wo.estimate_date?.display || '-')}</td>
  <td>${escapeHtml(wo.approval_date?.display || '-')}</td>
  <td>${escapeHtml(wo.sales_invoice_amount?.display || '-')}</td>
  <td>${escapeHtml(wo.sales_invoice_date?.display || '-')}</td>
  <td>${escapeHtml(wo.shipping_shipment_at?.display || '-')}</td>
  <td>${escapeHtml(wo.shipping_awb_no || '-')}</td>
  <td>
    <button class="btn btn-sm btn-outline-info js-marketing-media" type="button" data-media-kind="photos" data-media-count="${Number(wo.image_count || 0)}" data-media-url="${escapeHtml(wo.urls.photos)}" data-wo-label="${escapeHtml(wo.number_label)}" title="Images"><i class="bi bi-images"></i> ${Number(wo.image_count || 0)}</button>
    <button class="btn btn-sm btn-outline-info js-marketing-media" type="button" data-media-kind="pdfs" data-media-count="${Number(wo.pdf_count || 0)}" data-media-url="${escapeHtml(wo.urls.pdfs)}" data-wo-label="${escapeHtml(wo.number_label)}" title="PDF"><i class="bi bi-file-earmark-pdf"></i> ${Number(wo.pdf_count || 0)}</button>
  </td>
</tr>`;
            }

            function renderWorkorders(items, append = false) {
                if (!append) {
                    workordersRows.innerHTML = '';
                    state.workordersById.clear();
                }

                if (!items.length && !append) {
                    workordersRows.innerHTML = `<tr><td colspan="${workordersColumnCount}" class="text-center text-muted py-4">No workorders</td></tr>`;
                    return;
                }

                items.forEach((wo) => state.workordersById.set(Number(wo.id), wo));
                const html = items.map((wo) => renderWorkorderRow(wo)).join('');

                workordersRows.insertAdjacentHTML('beforeend', html);
            }

            function syncWorkorderSalesDatePlaceholderState() {
                workorderSalesDateInputs.forEach((input) => {
                    input.classList.toggle('is-marketing-empty-date', !input.value);
                });
            }

            function openWorkorderSalesModal(id) {
                const wo = state.workordersById.get(Number(id));
                if (!wo || !workorderSalesForm || !workorderSalesModalEl) return;

                state.editingWorkorderId = Number(id);
                workorderSalesTitle.textContent = `Edit ${wo.number_label || 'WO'}`;
                workorderSalesForm.reset();
                workorderSalesForm.elements.wo_terms.value = wo.terms || '';
                workorderSalesForm.elements.wo_estimate_amount.value = wo.estimate_amount?.value || '';
                workorderSalesForm.elements.estimate_date.value = wo.estimate_date?.iso || '';
                workorderSalesForm.elements.sales_invoice_amount.value = wo.sales_invoice_amount?.value || '';
                workorderSalesForm.elements.sales_invoice_date.value = wo.sales_invoice_date?.iso || '';
                workorderSalesForm.elements.shipping_shipment_at.value = wo.shipping_shipment_at?.iso || '';
                workorderSalesForm.elements.shipping_awb_no.value = wo.shipping_awb_no || '';
                syncWorkorderSalesDatePlaceholderState();
                bootstrap.Modal.getOrCreateInstance(workorderSalesModalEl).show();
            }

            function replaceRenderedWorkorderRow(wo) {
                state.workordersById.set(Number(wo.id), wo);
                const row = workordersRows.querySelector(`tr[data-workorder-id="${Number(wo.id)}"]`);
                if (row) {
                    row.outerHTML = renderWorkorderRow(wo);
                }
            }

            async function saveWorkorderSalesFields(event) {
                event.preventDefault();

                const id = state.editingWorkorderId;
                if (!id || !workorderSalesForm) return;

                try {
                    const data = await requestJson(urlFor(routes.updateWorkorderSalesFields, id), {
                        method: 'PATCH',
                        body: JSON.stringify(formDataObject(workorderSalesForm)),
                    });

                    if (data.workorder) {
                        replaceRenderedWorkorderRow(data.workorder);
                    }

                    bootstrap.Modal.getInstance(workorderSalesModalEl)?.hide();
                    notify('WO fields saved');
                } catch (error) {
                    notify(error.message, 'error');
                } finally {
                    window.safeHideSpinner?.();
                }
            }

            async function loadWorkorders(reset = false) {
                if (!state.selectedCustomer) return;
                if (state.workordersLoading) {
                    if (reset) state.workordersPendingReset = true;
                    return;
                }
                if (reset) {
                    state.workordersPage = 1;
                    state.workordersLoaded = false;
                    state.workordersHasMore = false;
                    state.workordersById.clear();
                    workordersRows.innerHTML = `<tr><td colspan="${workordersColumnCount}" class="text-center text-muted py-4">${loadingHtml('Loading')}</td></tr>`;
                    if (workordersScroll) workordersScroll.scrollTop = 0;
                }

                state.workordersLoading = true;
                const requestSeq = ++state.workordersRequestSeq;
                const filterRevision = state.workordersFilterRevision;
                const customerId = state.selectedCustomer.id;
                if (reset) {
                    workordersMore.hidden = true;
                } else {
                    workordersMore.hidden = false;
                    workordersMore.innerHTML = loadingHtml('Loading');
                }

                try {
                    const url = `${urlFor(routes.workorders, state.selectedCustomer.id)}?${queryString({
                        ...currentWorkorderFilters(),
                        page: state.workordersPage,
                        per_page: 20,
                    })}`;
                    const data = await requestJson(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    const isCurrentRequest = requestSeq === state.workordersRequestSeq
                        && filterRevision === state.workordersFilterRevision
                        && Number(customerId) === Number(state.selectedCustomer?.id);

                    if (!isCurrentRequest) {
                        return;
                    }

                    state.workordersHasMore = !!data.pagination?.has_more;
                    state.workordersPage = data.pagination?.next_page || state.workordersPage + 1;
                    state.workordersLoaded = true;
                    workordersMore.hidden = !state.workordersHasMore;
                    workordersMore.textContent = state.workordersHasMore ? 'Scroll for more' : '';
                    renderWorkorders(data.items || [], !reset);
                } catch (error) {
                    const isCurrentRequest = requestSeq === state.workordersRequestSeq
                        && filterRevision === state.workordersFilterRevision
                        && Number(customerId) === Number(state.selectedCustomer?.id);

                    if (isCurrentRequest) {
                        workordersRows.innerHTML = `<tr><td colspan="${workordersColumnCount}" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
                    }
                } finally {
                    if (requestSeq === state.workordersRequestSeq) {
                        state.workordersLoading = false;

                        if (state.workordersPendingReset) {
                            state.workordersPendingReset = false;
                            loadWorkorders(true);
                        }
                    }
                }
            }

            function formatSalesMoney(value) {
                if (value === null || value === undefined || value === '') return '-';

                const amount = Number(value);
                if (!Number.isFinite(amount)) return escapeHtml(value);

                const decimals = Math.abs(amount - Math.round(amount)) < 0.005 ? 0 : 2;
                return `$${amount.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                })}`;
            }

            function salesReportMode() {
                const checked = Array.from(salesReportModeInputs || []).find((input) => input.checked);
                return checked?.value === 'aircraft' ? 'aircraft' : 'customer';
            }

            function selectedSalesReportAircraftLabel() {
                const option = salesReportAircraft?.selectedOptions?.[0];
                return option?.value ? option.textContent.trim() : '';
            }

            function setSalesReportMessage(message) {
                const mode = salesReportMode();
                const colspan = mode === 'aircraft' ? 7 : 6;
                salesReportRows.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted py-4">${message}</td></tr>`;
            }

            function updateSalesReportModeUi() {
                state.salesReportMode = salesReportMode();
                const isAircraft = state.salesReportMode === 'aircraft';
                if (salesReportAircraftWrap) {
                    salesReportAircraftWrap.hidden = !isAircraft;
                    salesReportAircraftWrap.classList.toggle('has-clear', isAircraft && !!salesReportAircraft?.value);
                }
                document.querySelectorAll('[data-sales-report-customer-col]').forEach((el) => {
                    el.hidden = !isAircraft;
                });

                if (salesReportCompany) {
                    salesReportCompany.textContent = isAircraft ? selectedSalesReportAircraftLabel() : (state.selectedCustomer?.name || '');
                }
            }

            function initMarketingReportAircraftSelects(root = document) {
                if (!window.jQuery?.fn?.select2) return;

                window.jQuery(root).find('.marketing-report-aircraft-select').each(function () {
                    const $select = window.jQuery(this);
                    if ($select.data('select2')) return;

                    $select.select2({
                        width: '100%',
                        placeholder: 'Select A/C Type',
                        allowClear: true,
                        dropdownParent: window.jQuery(document.body),
                    });
                });
            }

            function renderSalesReport(report) {
                if (!salesReportRows) return;

                const rows = report?.rows || [];
                const isAircraft = report?.report_type === 'component' || salesReportMode() === 'aircraft';
                const colspan = isAircraft ? 7 : 6;
                const totalColspan = isAircraft ? 5 : 4;

                document.querySelectorAll('[data-sales-report-customer-col]').forEach((el) => {
                    el.hidden = !isAircraft;
                });

                if (salesReportCompany) {
                    salesReportCompany.textContent = isAircraft
                        ? (rows[0]?.aircraft_type || selectedSalesReportAircraftLabel())
                        : (state.selectedCustomer?.name || rows[0]?.company || '');
                }

                if (salesReportWarning) {
                    salesReportWarning.hidden = !report?.warning;
                    salesReportWarning.textContent = report?.warning || '';
                }

                if (salesReportNote) {
                    salesReportNote.textContent = `*NOTE: ${report?.note || 'Report based on one customer'}`;
                }

                if (!rows.length) {
                    salesReportRows.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted py-4">No sales report rows</td></tr>`;
                    return;
                }

                const html = rows.map((row) => {
                    return `
<tr>
  ${isAircraft ? `<td title="${escapeHtml(row.company || '')}">${escapeHtml(row.company || '-')}</td>` : ''}
  <td>${escapeHtml(row.wo_number || '-')}</td>
  <td title="${escapeHtml(row.part_number || '')}">${escapeHtml(row.part_number || '-')}</td>
  <td title="${escapeHtml(row.serial_number || '')}">${escapeHtml(row.serial_number || '-')}</td>
  <td title="${escapeHtml(row.description || '')}">${escapeHtml(row.description || '-')}</td>
  <td class="marketing-sales-report-money">${formatSalesMoney(row.invoiced_amount)}</td>
  <td>${escapeHtml(row.date_label || report?.period_label || '-')}</td>
</tr>`;
                }).join('');

                salesReportRows.innerHTML = `${html}
<tr class="marketing-sales-report-total">
  <td colspan="${totalColspan}" class="text-end">TOTAL</td>
  <td class="marketing-sales-report-money">${formatSalesMoney(report?.total ?? 0)}</td>
  <td></td>
</tr>`;
            }

            async function loadSalesReport(reset = false) {
                if (!salesReportRows) return;

                const mode = salesReportMode();
                state.salesReportMode = mode;
                updateSalesReportModeUi();

                if (salesReportWarning) {
                    salesReportWarning.hidden = true;
                    salesReportWarning.textContent = '';
                }

                if (mode === 'customer' && !state.selectedCustomer) {
                    if (salesReportCompany) salesReportCompany.textContent = '';
                    setSalesReportMessage('Select company');
                    return;
                }

                if (mode === 'aircraft' && !salesReportAircraft?.value) {
                    if (salesReportCompany) salesReportCompany.textContent = '';
                    setSalesReportMessage('Select A/C Type');
                    return;
                }

                if (state.salesReportLoading) return;
                state.salesReportLoading = true;

                if (reset) {
                    state.salesReportLoaded = false;
                    if (salesReportCompany) {
                        salesReportCompany.textContent = mode === 'aircraft'
                            ? selectedSalesReportAircraftLabel()
                            : (state.selectedCustomer?.name || '');
                    }
                    setSalesReportMessage(loadingHtml('Loading'));
                }

                salesReportRefresh && (salesReportRefresh.disabled = true);
                salesReportPrint && (salesReportPrint.disabled = true);

                try {
                    const params = {
                        date_from: salesReportDateFrom?.value || '',
                        date_to: salesReportDateTo?.value || '',
                    };
                    const url = mode === 'aircraft'
                        ? `${routes.aircraftSalesReport}?${queryString({ ...params, plane_id: salesReportAircraft?.value || '' })}`
                        : `${urlFor(routes.salesReport, state.selectedCustomer.id)}?${queryString(params)}`;
                    const report = await requestJson(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
                    renderSalesReport(report);
                    state.salesReportLoaded = true;
                } catch (error) {
                    const colspan = mode === 'aircraft' ? 7 : 6;
                    salesReportRows.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
                } finally {
                    state.salesReportLoading = false;
                    salesReportRefresh && (salesReportRefresh.disabled = false);
                    salesReportPrint && (salesReportPrint.disabled = false);
                }
            }

            async function printSalesReport() {
                if (!state.salesReportLoaded) {
                    await loadSalesReport(true);
                }

                if (!state.salesReportLoaded) return;

                document.body.classList.add('is-marketing-sales-report-print');
                window.print();
                window.setTimeout(() => document.body.classList.remove('is-marketing-sales-report-print'), 800);
            }

            window.addEventListener('afterprint', () => {
                document.body.classList.remove('is-marketing-sales-report-print');
            });

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

            async function switchTab(tab, persist = true, options = {}) {
                if (!allowedTabs.includes(tab)) tab = 'overview';
                if (tab === state.activeTab) return true;
                if (!options.skipUnsavedCheck && !(await confirmDiscardUnsavedChanges())) return false;

                state.activeTab = tab;
                document.querySelectorAll('.marketing-tab').forEach((btn) => {
                    const active = btn.dataset.tab === tab;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.querySelectorAll('.marketing-pane').forEach((pane) => pane.classList.toggle('is-active', pane.dataset.pane === tab));

                if (persist) {
                    window.UserUiSettings?.set(scope, tabKey, tab)?.catch(() => {});
                }

                if (tab === 'workorders' && state.selectedCustomer && !state.workordersLoaded) {
                    loadWorkorders(true);
                }

                if (tab === 'sales_report' && !state.salesReportLoaded) {
                    loadSalesReport(true);
                }

                return true;
            }

            rowsEl.addEventListener('click', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row) openCustomer(row.dataset.customerId);
            });

            addressCategories?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-address-category]');
                if (button) selectAddressCategory(button.dataset.addressCategory);
            });

            initFilterClearButtons();

            Object.entries(filterEls).forEach(([key, el]) => {
                const handler = debounce(async () => {
                    updateFilterStates();
                    await saveFilters();
                    await loadCustomers(true);
                }, el.type === 'search' ? 260 : 80);
                el.addEventListener(el.type === 'search' ? 'input' : 'change', handler);

                if (key === 'country_id' && window.jQuery) {
                    window.jQuery(el)
                        .off('change.marketingFilters select2:select.marketingFilters select2:clear.marketingFilters')
                        .on('change.marketingFilters select2:select.marketingFilters select2:clear.marketingFilters', handler);
                }
            });

            document.getElementById('marketingResetFilters').addEventListener('click', async () => {
                setFilters({});
                await saveFilters();
                await loadCustomers(true);
            });

            const debouncedWorkorderFilterReload = debounce(reloadWorkordersForFilterChange, 260);

            workordersSearch?.addEventListener('input', debouncedWorkorderFilterReload);
            workordersSearchClear?.addEventListener('click', clearWorkorderSearch);
            workorderFilterInputs.forEach((input) => {
                input.addEventListener('input', debouncedWorkorderFilterReload);
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
                if (mediaBtn) {
                    openMediaModal(mediaBtn);
                    return;
                }

                if (event.target.closest('a, button, input, select, textarea, label')) {
                    return;
                }

                const row = event.target.closest('tr[data-workorder-id]');
                if (row) {
                    openWorkorderSalesModal(row.dataset.workorderId);
                }
            });

            salesReportRefresh?.addEventListener('click', () => loadSalesReport(true));
            salesReportPrint?.addEventListener('click', async () => {
                try {
                    await printSalesReport();
                } catch (error) {
                    notify(error.message || 'Print failed', 'error');
                }
            });

            [salesReportDateFrom, salesReportDateTo].forEach((el) => {
                el?.addEventListener('change', () => {
                    state.salesReportLoaded = false;
                    if (state.activeTab === 'sales_report') {
                        loadSalesReport(true);
                    }
                });
            });

            salesReportModeInputs.forEach((input) => {
                input.addEventListener('change', () => {
                    state.salesReportLoaded = false;
                    updateSalesReportModeUi();
                    loadSalesReport(true);
                });
            });

            salesReportAircraft?.addEventListener('change', () => {
                state.salesReportLoaded = false;
                updateSalesReportModeUi();
                if (state.activeTab === 'sales_report' && salesReportMode() === 'aircraft') {
                    loadSalesReport(true);
                }
            });

            if (window.jQuery && salesReportAircraft) {
                window.jQuery(salesReportAircraft)
                    .off('change.marketingSalesReport select2:select.marketingSalesReport select2:clear.marketingSalesReport')
                    .on('change.marketingSalesReport select2:select.marketingSalesReport select2:clear.marketingSalesReport', () => {
                        state.salesReportLoaded = false;
                        updateSalesReportModeUi();
                        if (state.activeTab === 'sales_report' && salesReportMode() === 'aircraft') {
                            loadSalesReport(true);
                        }
                    });
            }

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
                initMarketingCountrySelects(event.currentTarget);
                initMarketingCitySelects(event.currentTarget);
                initMarketingAircraftSelects(event.currentTarget);
            });

            workorderSalesModalEl?.addEventListener('shown.bs.modal', (event) => {
                disableAutocomplete(event.currentTarget);
                syncWorkorderSalesDatePlaceholderState();
                workorderSalesForm?.elements?.sales_invoice_amount?.focus();
            });

            workorderSalesModalEl?.addEventListener('hidden.bs.modal', () => {
                state.editingWorkorderId = null;
            });

            profileForm.addEventListener('submit', saveProfile);
            profileForm.querySelector('[data-profile-save-button]')?.addEventListener('click', saveProfile);
            profileForm.addEventListener('input', handleDirtyFieldChange);
            profileForm.addEventListener('change', handleDirtyFieldChange);
            contactForm.addEventListener('submit', addContact);
            noteForm.addEventListener('submit', addNote);
            createForm.addEventListener('submit', addCompany);
            workorderSalesForm?.addEventListener('submit', saveWorkorderSalesFields);
            workorderSalesForm?.addEventListener('input', syncWorkorderSalesDatePlaceholderState);
            workorderSalesForm?.addEventListener('change', syncWorkorderSalesDatePlaceholderState);

            contactsList.addEventListener('input', handleDirtyFieldChange);
            contactsList.addEventListener('change', handleDirtyFieldChange);

            contactsList.addEventListener('submit', (event) => {
                const form = event.target.closest('.js-contact-row');
                if (!form) return;
                event.preventDefault();
                if (!form.classList.contains('is-editing')) return;
                saveContact(form);
            });

            contactsList.addEventListener('click', (event) => {
                const editBtn = event.target.closest('.marketing-contact-edit');
                if (editBtn) {
                    const form = editBtn.closest('.js-contact-row');
                    setContactFormEditing(form, true);
                    form?.querySelector('input[name="first_name"]')?.focus();
                    return;
                }

                const cancelBtn = event.target.closest('.marketing-contact-cancel');
                if (cancelBtn) {
                    const form = cancelBtn.closest('.js-contact-row');
                    form?.reset();
                    setContactFormEditing(form, false);
                    markFormClean(form);
                    return;
                }

                const deleteBtn = event.target.closest('.js-contact-delete');
                if (deleteBtn) deleteContact(deleteBtn);
            });

            document.querySelectorAll('[data-contact-copy]').forEach((button) => {
                button.addEventListener('click', () => copyMarketingContacts(button.dataset.contactCopy || 'all'));
            });

            contactNewToggle?.addEventListener('click', () => {
                setNewContactFormVisible(contactForm.hidden);
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

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                const link = event.target.closest('a[href]');
                if (!link || !document.contains(link)) return;
                if (link.target && link.target !== '_self') return;

                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.toLowerCase().startsWith('javascript:')) return;

                const url = new URL(link.href, window.location.href);
                if (url.href === window.location.href || !hasUnsavedChanges()) return;

                event.preventDefault();
                event.stopImmediatePropagation();
                confirmDiscardUnsavedChanges().then((confirmed) => {
                    if (confirmed) window.location.href = url.href;
                });
            }, true);

            window.addEventListener('beforeunload', (event) => {
                if (!hasUnsavedChanges()) return;

                event.preventDefault();
                event.returnValue = '';
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
                initMarketingCountrySelects(document);
                initMarketingCitySelects(document);
                initMarketingAircraftSelects(document);
                initMarketingReportAircraftSelects(document);
                updateSalesReportModeUi();
                updateWorkorderFilterStates();

                try {
                    await restorePanelLayout();
                    await restoreActiveTab();
                    await restoreOverviewTextareaHeights();
                    initOverviewTextareaResizePersistence();
                } finally {
                    shell?.classList.add('is-layout-ready');
                }

                try {
                    const saved = await window.UserUiSettings?.get(scope, filtersKey, {});
                    setFilters(saved || {});
                } catch (_) {}
                await loadCustomers(true);

                const initialCustomerId = await restoreSelectedCustomerId();
                if (initialCustomerId) {
                    await openCustomer(initialCustomerId);
                }
            })();
        })();
    </script>
@endsection
