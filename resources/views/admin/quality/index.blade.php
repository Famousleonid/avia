@extends('admin.master')

@section('content')
    <style>
        .qa-page {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            flex: 1 1 auto;
            overflow: hidden;
            background: #2B3035;
        }

        .content,
        .content-inner {
            background: #2B3035 !important;
        }

        .qa-header {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: max-content max-content minmax(220px, 320px) minmax(320px, 520px) 1fr;
            align-items: center;
            column-gap: clamp(1rem, 3vw, 3.5rem);
        }

        .qa-current-wo {
            min-width: 6.5rem;
            color: var(--bs-body-color);
        }

        .qa-current-wo a {
            color: var(--bs-body-color);
            text-decoration: none;
        }

        .qa-current-wo a:hover .text-info {
            text-decoration: underline;
        }

        .qa-search-row {
            margin-left: clamp(1rem, 4vw, 4.5rem);
        }

        #qaMessage {
            flex: 0 0 auto;
        }

        #qaResult {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .qa-workorder-layout {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .qa-workorder-layout > .qa-block {
            flex: 0 0 auto;
            margin-bottom: .65rem !important;
        }

        .qa-search-row {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: center;
            column-gap: .65rem;
        }

        .qa-search-wrap,
        .qa-serial-search-wrap {
            position: relative;
        }

        .qa-search-wrap .form-control,
        .qa-serial-search-wrap .form-control {
            padding-right: 2.3rem;
        }

        .qa-serial-search-row {
            position: relative;
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: center;
            column-gap: .55rem;
        }

        .qa-serial-search-row .form-label {
            color: #39ff14;
            font-weight: 700;
        }

        .qa-serial-search-wrap .form-control {
            border-color: var(--bs-border-color);
            box-shadow: none;
        }

        .qa-serial-search-wrap .form-control:hover,
        .qa-serial-search-wrap .form-control:focus {
            border-color: #39ff14;
            border-style: dotted;
            box-shadow: none;
        }

        .qa-serial-panel {
            position: absolute;
            top: calc(100% + .35rem);
            left: 2.25rem;
            z-index: 1050;
            display: none;
            width: min(34rem, calc(100vw - 2rem));
            max-height: 20rem;
            overflow: auto;
            border: 1px solid rgba(13, 202, 240, .35);
            border-radius: .45rem;
            background: #343A40;
            box-shadow: 0 .8rem 2rem rgba(0, 0, 0, .42);
        }

        .qa-serial-panel.is-visible {
            display: block;
        }

        .qa-serial-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .45rem .65rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .qa-serial-result {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr) max-content;
            gap: .55rem;
            align-items: center;
            padding: .45rem .65rem;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .qa-serial-result:last-child {
            border-bottom: 0;
        }

        .qa-serial-result-source,
        .qa-serial-result-component {
            color: var(--bs-secondary-color);
            font-size: .75rem;
        }

        .qa-serial-result-component {
            overflow-wrap: anywhere;
        }

        .qa-serial-close {
            border: 0;
            background: transparent;
            color: var(--bs-secondary-color);
        }

        .qa-search-clear,
        .qa-serial-clear {
            position: absolute;
            top: 50%;
            right: .45rem;
            display: none;
            width: 1.7rem;
            height: 1.7rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-secondary-color);
            transform: translateY(-50%);
        }

        .qa-serial-clear {
            color: #39ff14;
        }

        .qa-search-clear.is-visible,
        .qa-serial-clear.is-visible {
            display: inline-flex;
        }

        .qa-dot-spinner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
            min-height: 1.25rem;
        }

        .qa-dot-spinner span {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: var(--bs-secondary-color);
            opacity: .5;
            animation: qaDotJump .72s ease-in-out infinite;
        }

        .qa-dot-spinner span:nth-child(2) {
            animation-delay: .12s;
        }

        .qa-dot-spinner span:nth-child(3) {
            animation-delay: .24s;
        }

        .qa-page-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 1080;
            display: none;
            transform: translate(-50%, -50%);
        }

        .qa-page-loading.is-visible {
            display: flex;
        }

        @keyframes qaDotJump {
            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: .42;
            }

            40% {
                transform: translateY(-.35rem);
                opacity: .95;
            }
        }

        .qa-block {
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: .65rem;
            background: rgba(255, 255, 255, .025);
        }

        .qa-block-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .45rem .7rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .qa-top-row {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) max-content;
            align-items: stretch;
            gap: .75rem;
            margin-bottom: .65rem;
        }

        .qa-top-row > .qa-block {
            min-width: 0;
            margin-bottom: 0 !important;
        }

        .qa-forms-block {
            width: max-content;
            justify-self: end;
        }

        .qa-top-row > .qa-block {
            background: #343A40;
        }

        .qa-repair-block {
            flex: 1 1 auto !important;
            display: flex;
            flex-direction: column;
            min-height: 0;
            margin-bottom: 0 !important;
        }

        .qa-repair-block .qa-block-title h6 {
            font-size: .9rem;
            font-weight: 500;
        }

        .qa-repair-block .table {
            font-size: .78rem;
        }

        .qa-repair-block .table th,
        .qa-repair-block .table td {
            padding: .25rem .35rem;
        }

        .qa-table-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .qa-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #111;
        }

        .qa-submitted-block table > :not(caption) > * > *,
        .qa-repair-block table > :not(caption) > * > * {
            background-color: #1d2020 !important;
        }

        .qa-submitted-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            padding: .75rem;
        }

        .qa-submitted-card {
            min-width: 0;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: .55rem;
            background: #343A40;
            padding: .75rem .85rem;
        }

        .qa-submitted-card-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: center;
            gap: .75rem;
            margin-bottom: .6rem;
            color: var(--bs-info);
            font-size: .9rem;
            font-weight: 500;
        }

        .qa-submitted-card-header--std,
        .qa-submitted-std-line {
            grid-template-columns: minmax(0, 1fr) minmax(6.8rem, max-content) minmax(6.8rem, max-content);
            column-gap: .75rem;
        }

        .qa-submitted-card-header-date {
            color: var(--bs-secondary-color);
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .qa-submitted-card-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) max-content;
            align-items: baseline;
            gap: .75rem;
            min-width: 0;
        }

        .qa-submitted-card-line + .qa-submitted-card-line {
            margin-top: .55rem;
            padding-top: .55rem;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .qa-submitted-card-title {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .qa-submitted-std-table {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(6.8rem, max-content) minmax(6.8rem, max-content);
            gap: .7rem .75rem;
            align-items: baseline;
        }

        .qa-submitted-std-name {
            min-width: 0;
            font-weight: 700;
        }

        .qa-submitted-std-head {
            color: var(--bs-secondary-color);
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .qa-submitted-card-date {
            white-space: nowrap;
            font-weight: 600;
        }

        .qa-submitted-std-line > .qa-submitted-card-date,
        .qa-submitted-card-header--std > .qa-submitted-card-header-date,
        .qa-submitted-std-table > .qa-submitted-card-date,
        .qa-submitted-std-table > .qa-submitted-std-head {
            text-align: right;
        }

        .qa-submitted-card-date--wide {
            white-space: normal;
            text-align: right;
        }

        .qa-block.is-highlighted {
            outline: 2px solid var(--bs-info);
            outline-offset: 2px;
        }

        .qa-submitted-card.is-highlighted {
            box-shadow:
                inset 0 2px 0 var(--bs-info),
                inset 0 -2px 0 var(--bs-info);
        }

        .qa-info-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .28rem .85rem;
            padding: .45rem .7rem;
        }

        .qa-info-item {
            min-width: 0;
            display: flex;
            align-items: baseline;
            gap: .35rem;
            line-height: 1.25;
        }

        .qa-info-item-open-date {
            grid-column: 1;
            grid-row: 2;
        }

        .qa-info-item-technician {
            grid-column: 1;
            grid-row: 1;
        }

        .qa-info-item-customer {
            grid-column: 2;
            grid-row: 1;
        }

        .qa-info-item-instruction {
            grid-column: 3;
            grid-row: 1;
        }

        .qa-info-item-manual {
            grid-column: 3;
            grid-row: 2;
        }

        .qa-info-item-manual-revision {
            grid-column: 2;
            grid-row: 2;
        }

        .qa-info-item-modified,
        .qa-info-item-serial {
            justify-content: flex-end;
        }

        .qa-info-item-modified {
            grid-column: 5;
            grid-row: 1;
        }

        .qa-info-item-serial {
            grid-column: 4;
            grid-row: 1;
        }

        .qa-info-item-modified .qa-info-edit,
        .qa-info-item-serial .qa-info-edit {
            flex: 0 1 7.5rem;
            max-width: 7.5rem;
        }

        .qa-info-label {
            flex: 0 0 auto;
            font-size: .74rem;
            color: var(--bs-secondary-color);
            white-space: nowrap;
        }

        .qa-info-value {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .qa-info-edit {
            display: block;
            flex: 1 1 auto;
            width: auto;
            min-width: 0;
            border: 0;
            border-bottom: 1px dashed rgba(13, 202, 240, .6);
            border-radius: 0;
            background: transparent;
            color: var(--bs-body-color);
            padding: 0;
            line-height: 1.25;
        }

        .qa-info-edit:focus {
            outline: 0;
            border-bottom-color: var(--bs-info);
            box-shadow: 0 1px 0 var(--bs-info);
        }

        .qa-info-edit.is-saving {
            color: var(--bs-secondary-color);
        }

        .qa-info-edit.is-invalid {
            border-bottom-color: var(--bs-danger);
            box-shadow: 0 1px 0 var(--bs-danger);
        }

        .qa-info-unit-row {
            min-width: 0;
        }

        .qa-info-unit-label-row {
            display: flex;
            align-items: center;
            gap: .3rem;
            white-space: nowrap;
        }

        .qa-info-item-component-pn {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: center;
            column-gap: .45rem;
            grid-column: 4 / 6;
            grid-row: 2;
            justify-self: end;
            width: 100%;
        }

        .qa-info-item-component-pn .qa-info-label {
            font-size: .72rem;
        }

        .qa-info-unit-row .select2-container {
            width: 100% !important;
            min-width: 0;
            transform: none;
        }

        .qa-info-unit-row .select2-container--default .select2-selection--single {
            height: 1rem !important;
            min-height: 1rem !important;
            border: 0;
            border-bottom: 1px dashed rgba(13, 202, 240, .6);
            border-radius: 0;
            background: transparent;
            padding: 0 !important;
            width: 100%;
        }

        .qa-info-unit-row .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color);
            line-height: 1rem;
            padding: 0 1.2rem 0 0 !important;
            font-size: .82rem;
        }

        .qa-info-unit-row .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 1rem;
            top: 0;
            right: 0;
        }

        .qa-info-unit-row .select2-container--default.select2-container--focus .select2-selection--single {
            border-bottom-color: var(--bs-info);
            box-shadow: 0 1px 0 var(--bs-info);
        }

        .select2-dropdown {
            min-width: 290px;
            background-color: #212529;
            color: var(--bs-body-color);
            border-color: rgba(255, 255, 255, .18);
        }

        .select2-dropdown .select2-search__field {
            background-color: #111;
            color: var(--bs-body-color);
            border-color: rgba(255, 255, 255, .18);
        }

        .select2-results__option--highlighted[aria-selected] {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .qa-unit-select-dropdown {
            width: max-content !important;
            min-width: 290px;
            max-width: min(52rem, calc(100vw - 2rem));
        }

        .qa-unit-select-dropdown .select2-results__option {
            white-space: nowrap;
        }

        .qa-unit-manual-muted {
            color: var(--bs-secondary-color);
        }

        .qa-info-select {
            width: 100%;
            min-width: 0;
            height: 1.55rem;
            border: 0;
            border-bottom: 1px dashed rgba(13, 202, 240, .6);
            border-radius: 0;
            background-color: transparent;
            color: var(--bs-body-color);
            padding: 0 1.45rem 0 0;
            line-height: 1.25;
        }

        .qa-info-select option {
            background-color: #212529;
            color: var(--bs-body-color);
        }

        .qa-info-select:focus {
            outline: 0;
            border-bottom-color: var(--bs-info);
            box-shadow: 0 1px 0 var(--bs-info);
        }

        .qa-info-select.is-saving {
            color: var(--bs-secondary-color);
        }

        .qa-info-select.is-invalid {
            border-bottom-color: var(--bs-danger);
            box-shadow: 0 1px 0 var(--bs-danger);
        }

        .qa-unit-add-button {
            width: 1rem;
            height: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            padding: 0;
        }

        .qa-unit-add-button img {
            width: 16px;
            height: 16px;
        }

        .qa-checks-line {
            padding: 0 .7rem .55rem;
        }

        .qa-check-button {
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
            text-align: left;
        }

        .qa-check-button:hover,
        .qa-check-button:focus {
            text-decoration: underline;
        }

        .qa-check-separator {
            color: var(--bs-secondary-color);
            margin: 0 .45rem;
        }

        .qa-photo-row {
            padding: .85rem;
        }

        .qa-photo-groups {
            display: grid;
            grid-template-columns: repeat(var(--qa-photo-count, 10), minmax(0, 1fr));
            gap: .6rem;
            min-width: 0;
        }

        .qa-photo-group {
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: .45rem;
            background: rgba(0, 0, 0, .12);
            color: var(--bs-body-color);
            text-align: left;
            min-width: 0;
            padding: .6rem .55rem;
        }

        .qa-photo-group.is-empty {
            cursor: default;
        }

        .qa-photo-group-title,
        .qa-photo-group-key {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .qa-photo-group-title {
            font-size: .86rem;
            line-height: 1.15;
        }

        .qa-photo-group-key {
            font-size: .72rem;
            line-height: 1.05;
        }

        .qa-photo-thumb {
            width: 78px;
            height: 58px;
            object-fit: cover;
            border-radius: .35rem;
        }

        .qa-form-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: flex-end;
            gap: .3rem;
            padding: .45rem .6rem;
        }

        .qa-form-paper {
            width: 74px;
            height: 99px;
            flex: 0 0 auto;
            cursor: default;
            text-decoration: none;
        }

        .qa-form-paper svg {
            display: block;
            width: 74px;
            height: 99px;
            --paper: #d5d5d5;
            --fold: #0d6efd;
            --stroke: #0d6efd;
            --text: #084298;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, .18));
        }

        .qa-form-paper[href] {
            cursor: pointer;
        }

        .qa-form-paper[href]:hover svg {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .3));
        }

        .qa-form-paper.is-success svg {
            --fold: #198754;
            --stroke: #198754;
            --text: #0f5132;
        }

        .qa-form-paper .paper {
            fill: var(--paper);
            stroke: var(--stroke);
            stroke-width: 1;
        }

        .qa-form-paper .fold {
            fill: var(--fold);
        }

        .qa-form-paper .line {
            stroke: var(--stroke);
            stroke-width: 2;
            fill: none;
        }

        .qa-form-paper foreignObject div {
            color: var(--text);
            font-weight: 700;
            line-height: 1.02;
            text-align: center;
            overflow-wrap: anywhere;
            hyphens: auto;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qa-empty {
            padding: 1.5rem 1rem;
            color: var(--bs-secondary-color);
            text-align: center;
        }

        html[data-bs-theme="light"] .qa-page,
        html[data-bs-theme="light"] .content,
        html[data-bs-theme="light"] .content-inner {
            background: var(--bs-body-bg) !important;
        }

        html[data-bs-theme="light"] .qa-block,
        html[data-bs-theme="light"] .qa-photo-group,
        html[data-bs-theme="light"] .qa-submitted-card {
            background: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        html[data-bs-theme="light"] .qa-block-title,
        html[data-bs-theme="light"] .qa-submitted-card-line + .qa-submitted-card-line {
            border-bottom-color: var(--bs-border-color);
            border-top-color: var(--bs-border-color);
        }

        html[data-bs-theme="light"] .qa-table-scroll thead th,
        html[data-bs-theme="light"] .qa-submitted-block table > :not(caption) > * > *,
        html[data-bs-theme="light"] .qa-repair-block table > :not(caption) > * > * {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color);
        }

        html[data-bs-theme="light"] #qaPhotoModal .modal-content {
            background: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
        }

        html[data-bs-theme="light"] #qaPhotoModal .btn-close {
            filter: none;
        }

        @media (max-width: 1199.98px) {
            .qa-top-row {
                grid-template-columns: 1fr;
            }

            .qa-forms-block {
                width: 100%;
            }

            .qa-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .qa-info-item-technician,
            .qa-info-item-customer,
            .qa-info-item-instruction,
            .qa-info-item-manual,
            .qa-info-item-manual-revision,
            .qa-info-item-modified,
            .qa-info-item-serial,
            .qa-info-item-open-date {
                grid-column: auto;
                grid-row: auto;
            }

            .qa-info-item-component-pn {
                grid-column: 1 / -1;
                grid-row: auto;
            }
        }

        @media (max-width: 767.98px) {
            .qa-header {
                grid-template-columns: 1fr;
                row-gap: .65rem;
            }

            .qa-serial-panel {
                left: 0;
            }

            .qa-info-grid {
                grid-template-columns: 1fr;
            }

            .qa-info-item-component-pn {
                grid-column: 1;
                justify-self: stretch;
                width: 100%;
            }

            .qa-info-unit-row .select2-container--default .select2-selection--single {
                width: 100%;
            }

            .qa-submitted-cards {
                grid-template-columns: 1fr;
            }

            .qa-photo-groups {
                overflow-x: auto;
            }

            .qa-photo-group {
                min-width: 6.5rem;
            }
        }
    </style>

    <div id="qaPageLoading" class="qa-page-loading" aria-hidden="true">
        <span class="qa-dot-spinner"><span></span><span></span><span></span></span>
    </div>

    <div class="container-fluid px-3 qa-page pt-1">
        <div class="qa-header mb-2">
            <h4 class="mb-0 text-info">Quality Assurance</h4>
            <h5 id="qaCurrentWorkorder" class="qa-current-wo mb-0"></h5>
            <div class="qa-search-row">
                <label class="form-label small mb-0" for="qaWorkorderSearch">WO #</label>
                <div class="qa-search-wrap">
                    <input type="text" id="qaWorkorderSearch" class="form-control form-control-sm" placeholder="Enter full workorder number" autocomplete="off" inputmode="numeric">
                    <button type="button" id="qaWorkorderSearchClear" class="qa-search-clear" aria-label="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="qa-serial-search-row" id="qaSerialSearchRow">
                <label class="form-label small mb-0" for="qaSerialSearch">S/N</label>
                <div class="qa-serial-search-wrap">
                    <input type="text" id="qaSerialSearch" class="form-control form-control-sm" placeholder="Find serial number" autocomplete="off">
                    <button type="button" id="qaSerialSearchClear" class="qa-serial-clear" aria-label="Clear serial search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div id="qaSerialPanel" class="qa-serial-panel" aria-live="polite"></div>
            </div>
        </div>

        <div id="qaMessage" class="small text-secondary mb-2"></div>
        <div id="qaResult">
            <div class="qa-empty qa-block">Enter a full workorder number and press Enter.</div>
        </div>
    </div>

    <div class="modal fade" id="qaAddUnitModal" tabindex="-1" aria-labelledby="qaAddUnitLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaAddUnitLabel">Add Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qaAddUnitError" class="alert alert-danger py-2 d-none"></div>
                    <div class="mb-3">
                        <label for="qaUnitManual" class="form-label">CMM</label>
                        <select class="form-select" id="qaUnitManual">
                            <option value="">Select CMM</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="qaUnitPartNumber" class="form-label">PN</label>
                        <input type="text" class="form-control" id="qaUnitPartNumber" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="qaUnitName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="qaUnitName" autocomplete="off">
                    </div>
                    <div class="mb-0">
                        <label for="qaUnitDescription" class="form-label">Description</label>
                        <input type="text" class="form-control" id="qaUnitDescription" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="qaCreateUnitBtn" class="btn btn-outline-primary">Add Unit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="qaPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaPhotoModalTitle">Photos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qaPhotoModalBody" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const storageKey = 'qualityAssurance.singleWorkorderSearch';
            const serialStorageKey = 'qualityAssurance.serialSearch';
            const searchInput = document.getElementById('qaWorkorderSearch');
            const clearButton = document.getElementById('qaWorkorderSearchClear');
            const currentWorkorderLabel = document.getElementById('qaCurrentWorkorder');
            const serialSearchInput = document.getElementById('qaSerialSearch');
            const serialClearButton = document.getElementById('qaSerialSearchClear');
            const serialPanel = document.getElementById('qaSerialPanel');
            const serialSearchRow = document.getElementById('qaSerialSearchRow');
            const result = document.getElementById('qaResult');
            const message = document.getElementById('qaMessage');
            const pageLoading = document.getElementById('qaPageLoading');
            const addUnitModalEl = document.getElementById('qaAddUnitModal');
            const addUnitError = document.getElementById('qaAddUnitError');
            const unitManualSelect = document.getElementById('qaUnitManual');
            const unitPartNumberInput = document.getElementById('qaUnitPartNumber');
            const unitNameInput = document.getElementById('qaUnitName');
            const unitDescriptionInput = document.getElementById('qaUnitDescription');
            const createUnitButton = document.getElementById('qaCreateUnitBtn');
            const photoModalEl = document.getElementById('qaPhotoModal');
            const photoModalTitle = document.getElementById('qaPhotoModalTitle');
            const photoModalBody = document.getElementById('qaPhotoModalBody');
            const endpoint = @json(route('quality.workorder'));
            const serialSearchEndpoint = @json(route('quality.serial_search'));
            const storeUnitEndpoint = @json(route('quality.units.store'));
            const updateEndpointTemplate = @json(route('quality.workorder.top_fields.update', ['workorder' => '__WORKORDER_ID__']));
            const unitOptions = @json($unitOptions ?? []);
            const manualOptions = @json($manualOptions ?? []);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const spinnerHtml = '<span class="qa-dot-spinner" aria-label="Loading"><span></span><span></span><span></span></span>';
            let currentPhotoGroups = [];
            let currentWorkorder = null;
            let serialSearchController = null;

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const normalizeWorkorderSearch = (value) => {
                const query = String(value ?? '').trim();
                return /\d/.test(query) ? query.replace(/\D+/g, '') : query;
            };

            const showClear = () => {
                clearButton.classList.toggle('is-visible', searchInput.value.trim() !== '');
            };

            const setCurrentWorkorder = (number = '', url = '') => {
                currentWorkorderLabel.innerHTML = number
                    ? `<a href="${escapeHtml(url || '#')}">WO <span class="text-info">${escapeHtml(number)}</span></a>`
                    : '';
            };

            const setLoading = (loading) => {
                pageLoading.classList.toggle('is-visible', loading);
            };

            const saveSearch = (value) => {
                try {
                    localStorage.setItem(storageKey, value);
                } catch (error) {
                    // localStorage can be unavailable in private browser modes.
                }
            };

            const readSearch = () => {
                try {
                    return localStorage.getItem(storageKey) || '';
                } catch (error) {
                    return '';
                }
            };

            const saveSerialSearch = (value) => {
                try {
                    const text = String(value ?? '');
                    if (text.trim() === '') {
                        localStorage.removeItem(serialStorageKey);
                        return;
                    }
                    localStorage.setItem(serialStorageKey, text);
                } catch (error) {
                    // localStorage can be unavailable in private browser modes.
                }
            };

            const readSerialSearch = () => {
                try {
                    return localStorage.getItem(serialStorageKey) || '';
                } catch (error) {
                    return '';
                }
            };

            const showSerialClear = () => {
                serialClearButton.classList.toggle('is-visible', serialSearchInput.value.trim() !== '');
            };

            const fieldHtml = (label, value, html = false, className = '') => `
                <div class="qa-info-item ${escapeHtml(className)}">
                    <div class="qa-info-label">${escapeHtml(label)}</div>
                    <div class="qa-info-value">${html ? value : escapeHtml(value || '-')}</div>
                </div>
            `;

            const editableFieldHtml = (label, value, field, className = '') => {
                const text = value && value !== '-' ? value : '';

                return `
                    <div class="qa-info-item ${escapeHtml(className)}">
                        <label class="qa-info-label" for="qaTopField${escapeHtml(field)}">${escapeHtml(label)}</label>
                        <input id="qaTopField${escapeHtml(field)}"
                               class="qa-info-edit"
                               type="text"
                               value="${escapeHtml(text)}"
                               placeholder="-"
                               data-qa-top-field="${escapeHtml(field)}"
                               data-original-value="${escapeHtml(text)}"
                               autocomplete="off">
                    </div>
                `;
            };

            const unitLabel = (unit) => {
                const manual = unit.manual_number ? `(${unit.manual_number})` : '(Manual pending)';

                return `${unit.part_number || '-'} ${manual}`;
            };

            const unitSelectFieldHtml = (label, selectedUnitId) => {
                const selected = selectedUnitId ? String(selectedUnitId) : '';
                const options = [
                    '<option value="">---</option>',
                    ...unitOptions.map((unit) => {
                        const id = String(unit.id);
                        const manual = unit.manual_number ? `(${unit.manual_number})` : '(Manual pending)';

                        return `<option value="${escapeHtml(id)}"
                                        data-part-number="${escapeHtml(unit.part_number || '-')}"
                                        data-manual-label="${escapeHtml(manual)}"
                                        ${id === selected ? 'selected' : ''}>${escapeHtml(unitLabel(unit))}</option>`;
                    }),
                ].join('');

                return `
                    <div class="qa-info-item qa-info-item-component-pn">
                        <div class="qa-info-unit-label-row">
                            <label class="qa-info-label" for="qaTopFieldUnitId">${escapeHtml(label)}</label>
                            <button type="button"
                                    class="qa-unit-add-button"
                                    data-qa-add-unit
                                    title="Add new unit"
                                    aria-label="Add new unit">
                                <img src="{{ asset('img/plus.png') }}" alt="">
                            </button>
                        </div>
                        <div class="qa-info-unit-row">
                            <select id="qaTopFieldUnitId"
                                    class="qa-info-select qa-unit-select"
                                    data-qa-top-field="unit_id"
                                    data-original-value="${escapeHtml(selected)}">
                                ${options}
                            </select>
                        </div>
                    </div>
                `;
            };

            const manualHtml = (value) => {
                const text = String(value || '-');
                const match = text.match(/^(.*?)\s*(\([^)]*\))$/);

                if (!match) {
                    return escapeHtml(text);
                }

                return `${escapeHtml(match[1].trim())} <span class="text-secondary">${escapeHtml(match[2])}</span>`;
            };

            const checksHtml = (checks) => {
                if (!checks || checks.length === 0) {
                    return '';
                }

                return `
                    <div class="qa-checks-line small">
                        ${checks.map((check, index) => {
                            const className = `qa-check-button ${check.ok ? 'text-success' : 'text-danger'} fw-semibold`;
                            const content = escapeHtml(check.label);
                            const control = check.url && check.url !== '#'
                                ? `<a href="${escapeHtml(check.url)}" class="${className}" data-qa-scroll="${escapeHtml(check.target || '')}">${content}</a>`
                                : `<button type="button" class="${className}" data-qa-scroll="${escapeHtml(check.target || '')}">${content}</button>`;

                            return `${index ? '<span class="qa-check-separator">&middot;</span>' : ''}${control}`;
                        }).join('')}
                    </div>
                `;
            };

            const mainLinkHtml = (text, url, className = '') => {
                const safeText = escapeHtml(text);
                if (!url || url === '#') {
                    return safeText;
                }

                return `<a href="${escapeHtml(url)}" class="${escapeHtml(className)}" title="Open in Main">${safeText}</a>`;
            };

            const hideSerialPanel = () => {
                serialPanel.classList.remove('is-visible');
                serialPanel.innerHTML = '';
            };

            const renderSerialPanel = (html) => {
                serialPanel.innerHTML = html;
                serialPanel.classList.add('is-visible');
            };

            const renderSerialResults = (query, rows) => {
                const header = `
                    <div class="qa-serial-panel-header">
                        <span class="fw-semibold text-info">S/N ${escapeHtml(query)}</span>
                        <button type="button" class="qa-serial-close" data-qa-serial-close aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `;

                if (!rows || rows.length === 0) {
                    renderSerialPanel(`${header}<div class="qa-empty py-3">No workorders found.</div>`);
                    return;
                }

                const body = rows.map((row) => `
                    <div class="qa-serial-result">
                        <a href="${escapeHtml(row.workorder_url || '#')}" class="fw-semibold text-info">WO ${escapeHtml(row.workorder_number)}</a>
                        <div>
                            <div>${escapeHtml(row.serial || '-')}</div>
                            <div class="qa-serial-result-component">${escapeHtml(row.component || row.source || '')}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="qa-serial-result-source">${escapeHtml(row.source || '')}</span>
                            <a href="${escapeHtml(row.tdr_url || row.workorder_url || '#')}" class="btn btn-outline-info btn-sm py-0">Open</a>
                        </div>
                    </div>
                `).join('');

                renderSerialPanel(header + body);
            };

            const searchSerial = async () => {
                const query = serialSearchInput.value.trim();

                if (query.length < 2) {
                    hideSerialPanel();
                    return;
                }

                if (serialSearchController) {
                    serialSearchController.abort();
                }
                serialSearchController = new AbortController();

                renderSerialPanel(`
                    <div class="qa-serial-panel-header">
                        <span class="fw-semibold text-info">Searching S/N</span>
                        <button type="button" class="qa-serial-close" data-qa-serial-close aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="qa-empty py-3">${spinnerHtml}</div>
                `);

                try {
                    const url = new URL(serialSearchEndpoint, window.location.origin);
                    url.searchParams.set('q', query);
                    const response = await fetch(url, {
                        signal: serialSearchController.signal,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || !data.ok) {
                        throw new Error(data.message || 'Could not search serial number.');
                    }

                    renderSerialResults(data.query || query, data.results || []);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    renderSerialPanel(`
                        <div class="qa-serial-panel-header">
                            <span class="fw-semibold text-info">S/N search</span>
                            <button type="button" class="qa-serial-close" data-qa-serial-close aria-label="Close">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="qa-empty py-3 text-danger">${escapeHtml(error.message || 'Could not search serial number.')}</div>
                    `);
                }
            };

            const blockHtml = (title, body, titleMeta = '', className = '', id = '') => `
                <section ${id ? `id="${escapeHtml(id)}"` : ''} class="qa-block ${escapeHtml(className)} mb-3">
                    <div class="qa-block-title">
                        <h6 class="mb-0 text-info">${escapeHtml(title)}</h6>
                        ${titleMeta}
                    </div>
                    ${body}
                </section>
            `;

            const blockShellHtml = (body, className = '', id = '') => `
                <section ${id ? `id="${escapeHtml(id)}"` : ''} class="qa-block ${escapeHtml(className)} mb-3">
                    ${body}
                </section>
            `;

            const renderTop = (wo) => {
                const top = wo.top || {};
                const approvedDate = top.approved_at && top.approved_at !== '-' ? top.approved_at : '';
            const approvalMeta = `
                <span class="d-inline-flex align-items-center gap-2">
                    <span class="${top.approved ? 'text-info' : 'text-secondary'} fw-semibold">${top.approved ? 'Approved' : 'Not approved'}</span>
                    ${approvedDate ? `<span class="small text-light">${escapeHtml(approvedDate)}</span>` : ''}
                </span>
            `;
                const fields = [
                    ['Technician', top.technician, false, null, 'qa-info-item-technician'],
                    ['Customer', top.customer, false, null, 'qa-info-item-customer'],
                    ['Instruction', top.instruction, false, null, 'qa-info-item-instruction'],
                    ['Serial #', top.serial, false, 'serial', 'qa-info-item-serial'],
                    ['Modified', top.modified, false, 'modified', 'qa-info-item-modified'],
                    ['Open Date', top.open_date, false, null, 'qa-info-item-open-date'],
                    ['Manual Rev.', top.manual_revision, false, null, 'qa-info-item-manual-revision'],
                    ['Manual', manualHtml(top.manual), true, null, 'qa-info-item-manual'],
                    ['Component PN', top.unit_id, false, 'unit_id'],
                ].map(([label, value, html, editable, className]) => editable
                    ? (editable === 'unit_id' ? unitSelectFieldHtml(label, value) : editableFieldHtml(label, value, editable, className || ''))
                    : fieldHtml(label, value, html, className || '')
                ).join('');

                return blockHtml('Workorder', `<div class="qa-info-grid">${fields}</div>${checksHtml(wo.checks)}`, approvalMeta);
            };

            const renderPhotos = (groups) => {
                currentPhotoGroups = groups || [];

                if (currentPhotoGroups.length === 0) {
                    return blockShellHtml('<div class="qa-empty">No image photos found.</div>');
                }

                const cards = currentPhotoGroups.map((group, index) => `
                    <button type="button" class="qa-photo-group ${Number(group.count) === 0 ? 'is-empty' : ''}" data-photo-group="${index}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="qa-photo-group-title fw-semibold">${escapeHtml(group.label)}</div>
                                <div class="qa-photo-group-key text-secondary">${escapeHtml(group.collection)}</div>
                            </div>
                            <span class="${Number(group.count) === 0 ? 'text-warning' : 'text-info'} fw-semibold">${escapeHtml(group.count)}</span>
                        </div>
                    </button>
                `).join('');

                return blockShellHtml(`<div class="qa-photo-row"><div class="qa-photo-groups" style="--qa-photo-count: ${currentPhotoGroups.length || 1};">${cards}</div></div>`);
            };

            const renderSubmitted = (rows, stdRows) => {
                rows = rows || [];
                stdRows = stdRows || [];

                if (rows.length === 0 && stdRows.length === 0) {
                    return blockShellHtml('<div class="qa-empty">No submitted inspections waiting for QA.</div>', 'qa-submitted-block', 'qaSubmittedBlock');
                }

                const submittedCards = rows.map((row, index) => {
                    const submittedDateClass = row.submitted_date && row.submitted_date !== '-' ? 'text-success' : 'text-danger';
                    const inspectionDateClass = row.inspection_done ? 'text-success' : 'text-warning';
                    const submittedDateText = row.submitted_date && row.submitted_date !== '-' ? row.submitted_date : 'Missing';
                    const inspectionDateText = row.inspection_date && row.inspection_date !== '-' ? row.inspection_date : 'Missing';
                    const submittedDateHtml = submittedDateText === 'Missing'
                        ? mainLinkHtml(submittedDateText, row.submitted_url, submittedDateClass)
                        : escapeHtml(submittedDateText);
                    const inspectionDateHtml = mainLinkHtml(inspectionDateText, row.inspection_url, inspectionDateClass);
                    const cardTitle = String(row.missing_inspection || '').toLowerCase().includes('final')
                        ? 'Final inspection'
                        : (index === 1 ? 'Final inspection' : 'Disassembly inspection');

                    return `
                        <div class="qa-submitted-card" data-qa-submitted-inspection>
                            <div class="qa-submitted-card-header">${escapeHtml(cardTitle)}</div>
                            <div class="qa-submitted-card-line">
                                <div class="qa-submitted-card-title fw-semibold">${escapeHtml(row.submitted_step)}</div>
                                <div class="qa-submitted-card-date ${submittedDateClass}">${submittedDateHtml}</div>
                            </div>
                            <div class="qa-submitted-card-line">
                                <div class="qa-submitted-card-title fw-semibold">${escapeHtml(row.missing_inspection)}</div>
                                <div class="qa-submitted-card-date ${inspectionDateClass}">${inspectionDateHtml}</div>
                            </div>
                        </div>
                    `;
                }).join('');

                const stdCard = stdRows.length ? `
                    <div id="qaStdProcessBlock" class="qa-submitted-card">
                        <div class="qa-submitted-std-table">
                            <div class="qa-submitted-card-header mb-0">STD Process</div>
                            <div class="qa-submitted-std-head">Sent</div>
                            <div class="qa-submitted-std-head">Returned</div>
                        ${stdRows.map(row => {
                            const startMissing = !row.date_start || row.date_start === '-';
                            const finishMissing = !row.date_finish || row.date_finish === '-';
                            const finishClass = row.ignored ? 'text-secondary' : (finishMissing ? 'text-danger' : 'text-success');
                            const startClass = row.ignored ? 'text-secondary' : (startMissing ? 'text-warning' : 'text-success');
                            const startText = startMissing ? 'Missing' : row.date_start;
                            const finishText = finishMissing ? 'Missing' : row.date_finish;
                            const label = `${row.short_label || row.label || row.type}${row.ignored ? '/Ignored' : ''}`;

                            return `
                            <div class="qa-submitted-std-name ${row.ignored ? 'text-secondary' : ''}">${escapeHtml(label)}</div>
                            <div class="qa-submitted-card-date ${startClass}">${escapeHtml(startText)}</div>
                            <div class="qa-submitted-card-date ${finishClass}">${escapeHtml(finishText)}</div>
                            `;
                        }).join('')}
                        </div>
                    </div>
                ` : '';

                const body = `
                    <div class="qa-submitted-cards">
                        ${submittedCards}${stdCard}
                    </div>
                `;

                return blockShellHtml(body, 'qa-submitted-block', 'qaSubmittedBlock');
            };

            const renderRepairOrders = (rows) => {
                if (!rows || rows.length === 0) {
                    return blockHtml('Repair order', '<div class="qa-empty">No processes found.</div>');
                }

                const missing = rows.filter(row => !row.ok).length;
                const body = `
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Component</th>
                                <th>Process</th>
                                <th>RO</th>
                                <th>Date Send</th>
                                <th>Date Receive</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${rows.map(row => {
                                const repairOrderMissing = !row.repair_order || row.repair_order === '-';
                                const dateStartMissing = !row.date_start || row.date_start === '-';
                                const dateFinishMissing = !row.date_finish || row.date_finish === '-';

                                return `
                                <tr>
                                    <td>${escapeHtml(row.component)}</td>
                                    <td>${escapeHtml(row.process_name)}</td>
                                    <td>${repairOrderMissing ? '<span class="text-danger fw-semibold">Missing</span>' : escapeHtml(row.repair_order)}</td>
                                    <td>${dateStartMissing ? mainLinkHtml('Missing', row.date_start_url, 'text-danger fw-semibold') : escapeHtml(row.date_start)}</td>
                                    <td>${dateFinishMissing ? mainLinkHtml('Missing', row.date_finish_url, 'text-danger fw-semibold') : escapeHtml(row.date_finish)}</td>
                                    <td><span class="${row.ok ? 'text-success' : 'text-danger'} fw-semibold">${row.ok ? 'OK' : 'Missing'}</span></td>
                                </tr>
                            `;
                            }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

                return blockHtml('Repair order', body, `<span class="${missing ? 'text-danger' : 'text-success'} fw-semibold">${missing ? `${missing} missing` : 'OK'}</span>`, 'qa-repair-block', 'qaRepairBlock');
            };

            const fitPaperLabels = (root = document) => {
                root.querySelectorAll('.qa-form-paper-label').forEach((label) => {
                    label.style.fontSize = '24px';

                    for (let size = 24; size >= 9; size -= 1) {
                        label.style.fontSize = `${size}px`;

                        if (label.scrollWidth <= label.clientWidth && label.scrollHeight <= label.clientHeight) {
                            break;
                        }
                    }
                });
            };

            const paperButtonHtml = (form, index) => {
                const title = form.title || '';
                const colorClass = index < 2 ? 'is-success' : '';
                const url = form.url || '';
                const tag = url ? 'a' : 'div';
                const href = url ? ` href="${escapeHtml(url)}" target="_blank" rel="noopener"` : '';

                return `
                    <${tag} class="qa-form-paper ${colorClass}"${href} title="${escapeHtml(title)}" aria-label="${escapeHtml(title)}">
                        <svg viewBox="0 0 190 270" role="img" aria-hidden="true">
                            <path class="paper" d="M10 10 H140 L180 50 V240 H10 Z"></path>
                            <polygon class="fold" points="140,10 140,50 180,50"></polygon>
                            <path class="line" d="M140 12 V50 H180"></path>
                            <foreignObject x="14" y="56" width="162" height="150">
                                <div class="qa-form-paper-label" xmlns="http://www.w3.org/1999/xhtml">${escapeHtml(title)}</div>
                            </foreignObject>
                        </svg>
                    </${tag}>
                `;
            };

            const renderForms = (forms) => {
                const body = `
                    <div class="qa-form-grid">
                        ${(forms || []).map((form, index) => paperButtonHtml(form, index)).join('')}
                    </div>
                `;

                return blockHtml('Forms', body, '', 'qa-forms-block');
            };

            const renderWorkorder = (wo) => {
                currentWorkorder = wo;
                setCurrentWorkorder(wo.number, wo.url);
                result.innerHTML = `<div class="qa-workorder-layout">${[
                    `<div class="qa-top-row">${renderTop(wo)}${renderForms(wo.forms)}</div>`,
                    renderPhotos(wo.photos),
                    renderSubmitted(wo.submitted, wo.std_processes),
                    renderRepairOrders(wo.repair_orders),
                ].join('')}</div>`;
                fitPaperLabels(result);
                initSelect2Controls();
            };

            const updateEndpoint = (workorderId) => updateEndpointTemplate.replace('__WORKORDER_ID__', encodeURIComponent(workorderId));

            const formatUnitSelect2Option = (option) => {
                if (!option.id) {
                    return option.text;
                }

                const element = option.element;
                const partNumber = element?.dataset?.partNumber || option.text;
                const manual = element?.dataset?.manualLabel || '';

                return jQuery('<span></span>')
                    .append(document.createTextNode(partNumber + ' '))
                    .append(jQuery('<span></span>').addClass('qa-unit-manual-muted').text(manual));
            };

            const initSelect2Controls = () => {
                if (!window.jQuery || !jQuery.fn.select2) return;

                jQuery(result).find('.qa-unit-select').each(function () {
                    const select = jQuery(this);

                    if (select.data('select2')) {
                        select.select2('destroy');
                    }

                    select.select2({
                        width: '100%',
                        placeholder: '---',
                        allowClear: false,
                        dropdownAutoWidth: true,
                        templateResult: formatUnitSelect2Option,
                        templateSelection: formatUnitSelect2Option,
                    });

                    select.off('select2:open.qaUnitDropdown').on('select2:open.qaUnitDropdown', function () {
                        document.querySelectorAll('.select2-container--open .select2-dropdown').forEach((dropdown) => {
                            dropdown.classList.add('qa-unit-select-dropdown');
                        });
                    });

                    select.off('change.qaTopUnit').on('change.qaTopUnit', function () {
                        confirmAndSaveUnitField(this);
                    });
                });
            };

            const confirmAndSaveUnitField = async (select) => {
                const originalValue = String(select.dataset.originalValue || '');
                const value = String(select.value || '');

                if (value === originalValue) {
                    return;
                }

                const ok = typeof window.confirmDialog === 'function'
                    ? await window.confirmDialog({
                        title: 'Change Component PN',
                        message: 'Change Component PN for this workorder?',
                        okText: 'Change',
                        cancelText: 'Cancel',
                    })
                    : window.confirm('Change Component PN for this workorder?');

                if (!ok) {
                    select.value = originalValue;
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery(select).val(originalValue).trigger('change.select2');
                    }
                    select.classList.remove('is-invalid');
                    return;
                }

                saveTopField(select);
            };

            const saveTopField = async (input) => {
                if (!currentWorkorder) return;

                const field = input.dataset.qaTopField || '';
                const originalValue = input.dataset.originalValue || '';
                const value = input.value.trim();

                if (value === originalValue) {
                    return;
                }

                input.disabled = true;
                input.classList.remove('is-invalid');
                input.classList.add('is-saving');

                try {
                    const response = await fetch(updateEndpoint(currentWorkorder.id), {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ field, value }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.ok) {
                        throw new Error(data.message || 'Could not save field.');
                    }

                    currentWorkorder.top = data.top || currentWorkorder.top;
                    message.textContent = '';
                    renderWorkorder(currentWorkorder);
                } catch (error) {
                    input.disabled = false;
                    input.classList.remove('is-saving');
                    input.classList.add('is-invalid');
                    message.textContent = error.message || 'Could not save field.';
                    input.focus();
                }
            };

            const loadWorkorder = async () => {
                const normalized = normalizeWorkorderSearch(searchInput.value);
                searchInput.value = normalized;
                showClear();
                saveSearch(normalized);

                if (!/^\d{6}$/.test(normalized)) {
                    setCurrentWorkorder();
                    message.textContent = 'Enter full 6-digit workorder number.';
                    result.innerHTML = '<div class="qa-empty qa-block">Waiting for full workorder number.</div>';
                    return;
                }

                setLoading(true);
                message.innerHTML = spinnerHtml;

                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('q', normalized);

                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || !data.found) {
                        throw new Error(data.message || 'Workorder not found.');
                    }

                    message.textContent = '';
                    renderWorkorder(data.workorder);
                } catch (error) {
                    setCurrentWorkorder();
                    message.textContent = error.message || 'Could not load workorder.';
                    result.innerHTML = '<div class="qa-empty qa-block">No workorder loaded.</div>';
                } finally {
                    setLoading(false);
                }
            };

            searchInput.value = readSearch();
            showClear();
            serialSearchInput.value = readSerialSearch();
            showSerialClear();

            if (/^\d{6}$/.test(normalizeWorkorderSearch(searchInput.value))) {
                loadWorkorder();
            }

            searchInput.addEventListener('input', () => {
                saveSearch(normalizeWorkorderSearch(searchInput.value));
                setCurrentWorkorder();
                showClear();
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadWorkorder();
                }
            });

            serialSearchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchSerial();
                } else if (event.key === 'Escape') {
                    hideSerialPanel();
                }
            });

            serialSearchInput.addEventListener('input', () => {
                saveSerialSearch(serialSearchInput.value);
                showSerialClear();
                if (serialSearchInput.value.trim().length === 0) {
                    hideSerialPanel();
                }
            });

            serialPanel.addEventListener('click', (event) => {
                if (event.target.closest('[data-qa-serial-close]')) {
                    hideSerialPanel();
                    serialSearchInput.focus();
                }
            });

            document.addEventListener('click', (event) => {
                if (!serialSearchRow.contains(event.target)) {
                    hideSerialPanel();
                }
            });

            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                saveSearch('');
                setCurrentWorkorder();
                showClear();
                message.textContent = '';
                result.innerHTML = '<div class="qa-empty qa-block">Enter a full workorder number and press Enter.</div>';
                searchInput.focus();
            });

            serialClearButton.addEventListener('click', () => {
                serialSearchInput.value = '';
                saveSerialSearch('');
                showSerialClear();
                hideSerialPanel();
                serialSearchInput.focus();
            });

            unitManualSelect.innerHTML = [
                '<option value="">Select CMM</option>',
                ...manualOptions.map((manual) => {
                    const label = [manual.number, manual.title].filter(Boolean).join(' ');
                    const lib = manual.lib ? ` (${manual.lib})` : '';

                    return `<option value="${escapeHtml(manual.id)}">${escapeHtml(label + lib)}</option>`;
                }),
            ].join('');

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(unitManualSelect).select2({
                    width: '100%',
                    placeholder: 'Select CMM',
                    dropdownParent: jQuery(addUnitModalEl),
                });
            }

            const showAddUnitError = (text = '') => {
                addUnitError.textContent = text;
                addUnitError.classList.toggle('d-none', text === '');
            };

            const openAddUnitModal = () => {
                showAddUnitError();
                unitPartNumberInput.value = '';
                unitNameInput.value = '';
                unitDescriptionInput.value = '';
                unitManualSelect.value = currentWorkorder?.top?.unit_manual_id || '';
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(unitManualSelect).trigger('change.select2');
                }
                bootstrap.Modal.getOrCreateInstance(addUnitModalEl).show();
                window.setTimeout(() => unitPartNumberInput.focus(), 150);
            };

            const addOrReplaceUnitOption = (unit) => {
                const index = unitOptions.findIndex((option) => String(option.id) === String(unit.id));

                if (index === -1) {
                    unitOptions.push(unit);
                } else {
                    unitOptions[index] = unit;
                }

                unitOptions.sort((a, b) => String(a.part_number || '').localeCompare(String(b.part_number || '')));
            };

            const createUnit = async () => {
                const manualId = unitManualSelect.value;
                const partNumber = unitPartNumberInput.value.trim();
                const name = unitNameInput.value.trim();
                const description = unitDescriptionInput.value.trim();

                if (!manualId || !partNumber) {
                    showAddUnitError('Please select a CMM and enter a PN.');
                    return;
                }

                createUnitButton.disabled = true;
                showAddUnitError();

                try {
                    const body = { manual_id: manualId, part_number: partNumber };
                    if (name) body.name = name;
                    if (description) body.description = description;

                    const response = await fetch(storeUnitEndpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(body),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data?.errors?.part_number?.[0] || data?.message || data?.error || 'Failed to create unit.');
                    }

                    addOrReplaceUnitOption(data);
                    bootstrap.Modal.getOrCreateInstance(addUnitModalEl).hide();
                    renderWorkorder(currentWorkorder);

                    const select = result.querySelector('[data-qa-top-field="unit_id"]');
                    if (select) {
                        select.value = String(data.id);
                        await saveTopField(select);
                    }
                } catch (error) {
                    showAddUnitError(error.message || 'Failed to create unit.');
                } finally {
                    createUnitButton.disabled = false;
                }
            };

            result.addEventListener('click', (event) => {
                const addUnitButton = event.target.closest('[data-qa-add-unit]');
                if (addUnitButton) {
                    event.preventDefault();
                    openAddUnitModal();
                    return;
                }

                const scrollButton = event.target.closest('[data-qa-scroll]');
                if (scrollButton) {
                    const href = scrollButton.getAttribute('href');
                    if (href) {
                        return;
                    }

                    const targetId = scrollButton.getAttribute('data-qa-scroll');
                    const target = targetId ? document.getElementById(targetId) : null;

                    if (targetId === 'qaSubmittedInspectionCards') {
                        const cards = result.querySelectorAll('[data-qa-submitted-inspection]');
                        cards[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        cards.forEach((card) => {
                            card.classList.add('is-highlighted');
                            window.setTimeout(() => card.classList.remove('is-highlighted'), 1400);
                        });
                    } else if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        target.classList.add('is-highlighted');
                        window.setTimeout(() => target.classList.remove('is-highlighted'), 1400);
                    }

                    event.preventDefault();
                    return;
                }

                const button = event.target.closest('[data-photo-group]');
                if (!button) return;

                const group = currentPhotoGroups[Number(button.dataset.photoGroup)];
                if (!group) return;
                if (Number(group.count) === 0) return;

                photoModalTitle.textContent = `${group.label} (${group.count})`;
                photoModalBody.innerHTML = (group.items || []).map(item => `
                    <a href="${escapeHtml(item.big)}" data-fancybox="qa-${escapeHtml(group.collection)}" data-caption="${escapeHtml(item.name)}">
                        <img src="${escapeHtml(item.thumb || item.big)}" class="qa-photo-thumb" alt="${escapeHtml(item.name)}" data-full-src="${escapeHtml(item.big)}">
                    </a>
                `).join('');

                photoModalBody.querySelectorAll('img[data-full-src]').forEach((image) => {
                    image.addEventListener('error', () => {
                        if (image.src !== image.dataset.fullSrc) {
                            image.src = image.dataset.fullSrc;
                        }
                    }, { once: true });
                });

                bootstrap.Modal.getOrCreateInstance(photoModalEl).show();
            });

            createUnitButton.addEventListener('click', createUnit);

            [unitManualSelect, unitPartNumberInput, unitNameInput, unitDescriptionInput].forEach((field) => {
                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        createUnit();
                    }
                });
            });

            result.addEventListener('keydown', (event) => {
                const input = event.target.closest('[data-qa-top-field]');
                if (!input) return;

                if (input.tagName === 'SELECT') return;

                if (event.key === 'Enter') {
                    event.preventDefault();
                    saveTopField(input);
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    input.value = input.dataset.originalValue || '';
                    input.classList.remove('is-invalid');
                    input.blur();
                }
            });
        })();
    </script>
@endsection
