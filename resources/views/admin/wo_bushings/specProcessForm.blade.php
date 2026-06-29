<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.user-scoped-storage')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Process Form - Bushings</title>

    <style>
        :root {
            --container-max-width: 980px;
            --container-padding: 3px;
            --container-margin-left: 10px;
            --container-margin-right: 10px;
            --container-scale: 0.97;
            --print-page-margin: 2mm;
            --print-body-margin-left: 3px;
            --table-font-size: 14px;
            --component-header-font-size: 16px;
            --print-footer-width: 1060px;
            --print-footer-font-size: 12px;
            --print-footer-padding: 2px 2px;
            --spec-process-number-col-width: 30px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
        }

        .row.g-0 {
            gap: 0;
        }

        .col,
        .col-1,
        .col-2,
        .col-4,
        .col-6,
        .col-10,
        .col-11,
        .col-md-4,
        .col-md-5 {
            min-width: 0;
        }

        .col { flex: 1 0 0; }
        .col-1 { flex: 0 0 8.333333%; max-width: 8.333333%; }
        .col-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
        .col-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-6 { flex: 0 0 50%; max-width: 50%; }
        .col-10 { flex: 0 0 83.333333%; max-width: 83.333333%; }
        .col-11 { flex: 0 0 91.666667%; max-width: 91.666667%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-5 { flex: 0 0 41.666667%; max-width: 41.666667%; }

        .d-flex { display: flex; }
        .flex-column { flex-direction: column; }
        .align-items-center { align-items: center; }
        .align-items-stretch { align-items: stretch; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .m-3 { margin: 1rem; }
        .mt-1 { margin-top: .25rem; }
        .mt-2 { margin-top: .5rem; }
        .mt-3 { margin-top: 1rem; }
        .mb-1 { margin-bottom: .25rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .ms-2 { margin-left: .5rem; }
        .pe-3 { padding-right: 1rem; }
        .pe-4 { padding-right: 1.5rem; }
        .ps-1 { padding-left: .25rem; }
        .ps-2 { padding-left: .5rem; }
        .pt-1 { padding-top: .25rem; }
        .pt-2 { padding-top: .5rem; }
        .pt-3 { padding-top: 1rem; }
        .gap-2 { gap: .5rem; }

        button,
        input {
            font: inherit;
        }

        .btn {
            border: 1px solid #666;
            border-radius: 4px;
            background: #fff;
            color: #111;
            cursor: pointer;
            display: inline-block;
            line-height: 1.3;
            padding: .35rem .65rem;
        }

        .btn-sm {
            font-size: .85rem;
            padding: .2rem .45rem;
        }

        .btn-primary,
        .btn-outline-primary {
            border-color: #0d6efd;
            color: #084298;
        }

        .btn-secondary {
            border-color: #6c757d;
            color: #343a40;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .form-control {
            border: 1px solid #999;
            border-radius: 3px;
            display: block;
            padding: .25rem .35rem;
            width: 100%;
        }

        .input-group {
            width: 100%;
        }

        .print-settings-panel[hidden] {
            display: none;
        }

        .print-settings-panel {
            background: rgba(255, 255, 255, .98);
            border: 1px solid #777;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .18);
            left: 50%;
            max-height: calc(100vh - 40px);
            overflow: auto;
            padding: 12px;
            position: fixed;
            right: auto;
            top: 20px;
            transform: translateX(-50%);
            width: min(760px, calc(100vw - 40px));
            z-index: 50;
        }

        .modal-header,
        .modal-footer {
            align-items: center;
            display: flex;
            gap: .5rem;
            justify-content: space-between;
            margin-bottom: .75rem;
        }

        .modal-title {
            margin: 0;
        }

        .modal-footer {
            border-top: 1px solid #ddd;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: .75rem;
        }

        .btn-close {
            border: 0;
            background: transparent;
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            padding: .1rem .35rem;
        }

        .btn-close::before {
            content: "x";
        }

        .accordion {
            border: 1px solid #bbb;
            margin-bottom: .75rem;
        }

        .accordion-button {
            background: #f3f3f3;
            border: 0;
            border-bottom: 1px solid #bbb;
            display: block;
            font-weight: 700;
            padding: .45rem .6rem;
            text-align: left;
            width: 100%;
        }

        .accordion-collapse,
        .accordion-body {
            display: block;
        }

        .accordion-body {
            padding: .75rem;
        }

        .print-settings-notice {
            background: #fff;
            border: 1px solid #555;
            bottom: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .16);
            padding: .5rem .75rem;
            position: fixed;
            right: 18px;
            z-index: 60;
        }

        .print-settings-notice.success {
            border-color: #198754;
            color: #0f5132;
        }

        .print-settings-notice.error {
            border-color: #dc3545;
            color: #842029;
        }

        .legacy-print-setting-block {
            display: none !important;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            height: auto;
            transform: scale(var(--container-scale));
            transform-origin: top;
            padding: var(--container-padding);
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
        }

        .spec-process-footer {
            box-sizing: border-box;
            font-size: 12px;
            margin-left: var(--container-margin-left);
            margin-right: var(--container-margin-right);
            max-width: var(--container-max-width);
            padding: 2px 2px;
            transform: scale(var(--container-scale));
            transform-origin: top;
            width: 100%;
        }

        .container-fluid > .row.g-0 > .col-2,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > .col-2,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > .col-6 {
            align-items: center !important;
            display: flex !important;
            justify-content: center !important;
            padding-left: 2px !important;
            padding-right: 2px !important;
            text-align: center !important;
        }

        .container-fluid > .row.g-0 > .col-2 > div,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > span,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0,
        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > div > span {
            align-items: center !important;
            display: flex !important;
            justify-content: center !important;
            text-align: center !important;
            width: 100% !important;
        }

        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 {
            height: 100% !important;
        }

        .container-fluid > .row.g-0.border-tt-gr > .col-2 > div {
            justify-content: center !important;
            padding-right: 0 !important;
            text-align: center !important;
        }

        .spec-form-title {
            font-size: 1.35rem;
            line-height: 1;
            margin-bottom: 2px;
        }

        .container-fluid > .row.g-0 > .col-2.border-l-t.ps-1,
        .container-fluid > .row.g-0 > .col-2.border-l.ps-1,
        .container-fluid > .row.g-0 > .col-2.border-l-t-b.ps-1,
        .container-fluid > .row.g-0 > .col-2.spec-left-label {
            align-items: stretch !important;
            justify-content: flex-start !important;
            text-align: left !important;
        }

        .container-fluid > .row.g-0 > .col-2.border-l-t.ps-1 > div,
        .container-fluid > .row.g-0 > .col-2.border-l.ps-1 > div,
        .container-fluid > .row.g-0 > .col-2.border-l-t-b.ps-1 > div,
        .container-fluid > .row.g-0 > .col-2.spec-left-label > div {
            align-items: center !important;
            justify-content: flex-start !important;
            padding-left: 2px !important;
            text-align: left !important;
        }

        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > .col-2.border-r,
        .process-step-number-cell {
            align-items: center !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex: 0 0 var(--spec-process-number-col-width) !important;
            justify-content: center !important;
            line-height: 1 !important;
            max-width: var(--spec-process-number-col-width) !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: center !important;
        }

        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > .col-6 {
            flex: 1 1 0 !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        .container-fluid > .row.g-0 > .col-10 > .row.g-0 > .col > .row.g-0 > .col-2.border-r > span,
        .process-step-number-cell > span {
            align-items: center !important;
            display: flex !important;
            height: 100% !important;
            justify-content: center !important;
            line-height: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .spec-header-tech-row {
            align-items: center;
            width: 960px;
        }

        .spec-cat-one-label {
            align-items: center;
            display: flex;
            height: 25px;
            margin: 0;
            width: 60px;
        }

        .spec-technician-spacer {
            flex: 0 0 305px;
            height: 18px;
        }

        .spec-technician-label {
            align-items: flex-end;
            display: flex;
            height: 25px;
            justify-content: flex-end;
            padding-right: 4px;
            transform: translateY(-3px);
            width: 75px;
        }

        .spec-technician-name-line {
            align-items: flex-end;
            border-bottom: 1px solid black;
            display: flex;
            font-weight: 700;
            height: 25px;
            justify-content: center;
            line-height: 1;
            overflow: hidden;
            padding: 0 4px 3px;
            text-align: center;
            transform: translateY(-3px);
            white-space: nowrap;
            width: 120px;
        }

        .spec-group-label-box {
            align-items: center;
            border: 1px solid black;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            height: 20px;
            justify-content: center;
            line-height: 1;
            margin-left: 4px;
            min-width: 26px;
            padding: 0 3px;
        }

        .spec-header-square {
            border: 1px solid black;
            height: 40px;
            width: 40px;
        }

        .container-fluid .row.g-0,
        .container-fluid .row.g-0 strong,
        .container-fluid .row.g-0 span,
        .container-fluid .row.g-0 div,
        .container-fluid .row.g-0 h6,
        .container-fluid .parent,
        .container-fluid .parent div,
        .container-fluid .d-flex,
        .container-fluid .d-flex strong,
        .container-fluid .d-flex span,
        .container-fluid .d-flex div {
            font-size: var(--table-font-size) !important;
        }

        .spec-component-header-row > .col-10 > .row.g-0 > .col,
        .spec-component-header-row > .col-10 > .row.g-0 > .col span,
        .spec-component-header-row > .col-10 > .row.g-0 > .col div,
        .spec-component-header-row .part-no-data,
        .spec-component-header-row .part-no-data div {
            font-size: var(--component-header-font-size) !important;
        }

        .spec-visible-process-last > .col-2,
        .spec-visible-process-last > .col-10 > .row.g-0 > .col {
            border-bottom: 1px solid black !important;
        }

        .part-no-data {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-template-rows: repeat(3, 1fr) !important;
            gap: 0 1px !important;
            text-align: center !important;
            align-items: center !important;
            align-content: center !important;
            height: 100% !important;
            overflow: hidden !important;
            width: 100% !important;
        }
        .part-no-data div {
            display: block !important;
            line-height: 7.2px !important;
            max-width: 100% !important;
            overflow: hidden !important;
            overflow-wrap: normal !important;
            white-space: nowrap !important;
            word-break: normal !important;
        }
        .part-no-data div:only-child {
            grid-column: 1 / -1 !important;
        }

        .row.g-0 > .col-2 > div strong {
            font-size: var(--table-font-size) !important;
        }

        @media print {
            @page {
                size: 11in 8.5in;
                margin: var(--print-page-margin);
            }

            html, body {
                height: auto;
                width: auto;
                margin-left: var(--print-body-margin-left);
                padding: 0;
            }

            table, h1, p {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }

            .spec-process-footer {
                position: fixed;
                bottom: 0;
                width: var(--print-footer-width);
                max-width: none;
                margin-left: 0;
                margin-right: 0;
                transform: none;
                text-align: center;
                font-size: 12px;
                background-color: #fff;
                padding: 2px 2px;
            }

            .container {
                max-height: 100vh;
                overflow: hidden;
            }
            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container-fluid .row.g-0,
            .container-fluid .row.g-0 strong,
            .container-fluid .row.g-0 span,
            .container-fluid .row.g-0 div,
            .container-fluid .row.g-0 h6,
            .container-fluid .parent,
            .container-fluid .parent div,
            .container-fluid .d-flex,
            .container-fluid .d-flex strong,
            .container-fluid .d-flex span,
            .container-fluid .d-flex div {
                font-size: var(--table-font-size) !important;
            }

            .spec-component-header-row > .col-10 > .row.g-0 > .col,
            .spec-component-header-row > .col-10 > .row.g-0 > .col span,
            .spec-component-header-row > .col-10 > .row.g-0 > .col div,
            .spec-component-header-row .part-no-data,
            .spec-component-header-row .part-no-data div {
                font-size: var(--component-header-font-size) !important;
            }

            .part-no-data {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                grid-template-rows: repeat(3, 1fr) !important;
                gap: 0 1px !important;
                text-align: center !important;
                align-items: center !important;
                align-content: center !important;
                height: 100% !important;
                overflow: hidden !important;
                width: 100% !important;
            }
            .part-no-data div {
                display: block !important;
                line-height: 7.2px !important;
                max-width: 100% !important;
                overflow: hidden !important;
                overflow-wrap: normal !important;
                white-space: nowrap !important;
                word-break: normal !important;
            }
            .part-no-data div:only-child {
                grid-column: 1 / -1 !important;
            }

            .row.g-0 > .col-2 > div strong {
                font-size: var(--table-font-size) !important;
            }
        }

        .spec-component-header-row > .col-10 > .row.g-0 > .col .part-no-data,
        .spec-component-header-row > .col-10 > .row.g-0 > .col .part-no-data div {
            font-size: 7px !important;
        }

        .border-r {
            border-right: 1px solid black;
        }

        .border-all {
            border: 1px solid black;
        }
        .border-all-b {
            border: 2px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-lll-b-r {
            border-left: 8px  solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-rrr {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 5px solid black;
        }
        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
        }
        .border-ll-bb {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
        }
        .border-ll-bb-rr {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
            border-right: 2px solid black;
        }
        .border-bb {
            border-bottom: 2px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-t {
            border-top: 1px solid black;
        }
        .border-tt-gr {
            border-top: 1px solid black;
        }
        .border-r-b {
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .text-center {
            text-align: center;

        }

        .text-black {
            color: #000;
        }

        .topic-header {
            width: 100px;
        }

        .topic-content {
            width: 600px;
        }

        .topic-content-2 {
            width: 701px;
        }

        .hrs-topic, .trainer-init {
            width: 100px;
        }
        .hrs-topic-1,.trainer-init-1 {
            width: 98px;
        }
        .trainer-init-1 {
            width: 99px;
        }
        .fs-9 {
            font-size: 0.9rem;
        }
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-7 {
            font-size: 0.7rem;
        }
        .fs-4 {
            font-size: 0.4rem;
        }

        .details-row {
            display: flex;
            align-items: center;
            height: 36px;
        }
        .details-cell {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid black;
        }
        .check-icon {
            width: 30px;
            height: auto;
            margin: 0 5px;
        }
        .page-break {
            page-break-after: always;
        }


        .parent {
            display: grid;
            grid-template-columns: 518px 60px 155px ;
            /*grid-template-columns: repeat(12, 1fr);*/
            /*grid-template-rows: repeat(3, 1fr);*/
            gap: 0px;
        }

        /*.div2 {*/
        /*    grid-column: span 6 / span 6;*/
        /*    grid-column-start: 1;*/
        /*    grid-row-start: 2;*/
        /*}*/

        /*.div3 {*/
        /*    grid-row: span 3 / span 3;*/
        /*    grid-column-start: 7;*/
        /*    grid-row-start: 1;*/
        /*}*/

        /*.div4 {*/
        /*    grid-column: span 2 / span 2;*/
        /*    grid-column-start: 8;*/
        /*    grid-row-start: 2;*/
        /*}*/
        /*.div5 {*/
        /*    grid-column: span 3 / span 3;*/
        /*    grid-column-start: 10;*/
        /*    grid-row-start: 2;*/
        /*}*/



    </style>
</head>

<body>
@include('shared.print-mark.qr', ['printMarkWorkorder' => $current_wo ?? null])
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" onclick="togglePrintSettingsPanel(true)">
        ⚙️ Print Settings
    </button>
</div>

@php
    $componentsPerPage = 6;
    // Print up to six batch groups per page.
    $processGroups = array_values($processGroups ?? []);
    $componentChunks = collect($processGroups)->chunk($componentsPerPage);
    if ($componentChunks->isEmpty()) {
        $componentChunks = collect([collect()]);
    }
@endphp

@foreach($componentChunks as $processGroupChunk)
    @php
        $processGroups = $processGroupChunk->values()->all();
        $pageNumber = ($spPageOffset ?? 0) + $loop->iteration;
        $pageTotal = $combinedSpecPageTotal ?? $componentChunks->count();
        $partNoCellRows = max(1, min(7, (int) collect($processGroups)->max(
            fn (array $group): int => max(1, count($group['part_number_cells'] ?? []))
        )));
        $partNoExtraRows = max(0, $partNoCellRows - 1);
        $processTableRowsMax = max(1, 14 - $partNoExtraRows);
    @endphp
    <div class="container-fluid" data-process-table-rows-max="{{ $processTableRowsMax }}">
        <div class="row">
            <div class="col-1">
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 160px; margin: 6px 10px 0;">
            </div>
            <div class="col-11">
                <h5 class="pt-1 text-black text-center spec-form-title"><strong>Special Process Form</strong></h5>
            </div>
        </div>
        <div>
            <div class="row">
                <div class="col-6">
                    <div class="d-flex" style="width: 435px">
                        <div style="width: 92px"></div>
                        <div class=" pt-3" style="width: 25px">qty</div>
                        <div class=" pt-2" style="width: 114px;height: 20px">MPI</div>
                        <div class=" pt-2" style="width: 20px">FPI</div>
                        <div class=" pt-3" style="width: 22px">qty</div>
                        <div class=" text-center " style="width: 20px;height: 20px"></div>
                        <div class=" pt-2 text-end" style="width: 95px">CAD</div>
                        <div class=" pt-3 text-center" style="width: 33px">qty</div>
                    </div>
                </div>
                <div class="col-2 pt-2 border-b text-center"> <strong> W{{$current_wo->number}}</strong></div>
                <div class="col-md-5"></div>
            </div>
            <div class="d-flex spec-header-tech-row">
                <div class="text-end">
                    <h6 class="spec-cat-one-label"><strong>Cat #1</strong></h6>
                </div>
                <div class=" " >
                    <img src="{{ asset('img/icons/icons8-right-arrow.gif')}}" alt="arrow"
                         style="width: 30px;height: 20px">
                </div>
                <div class="border-l-t-b text-center fs-9 pt-0 5" style="width: 30px;height: 25px">
                    N/A</div>
                <div class="border-l-t-b ps-2  " style="width: 130px;height: 25px; color: lightgray; font-style: italic" >RO
                    No.</div>
                <div class="border-all text-center fs-9 pt-0 5" style="width: 30px;height: 25px">
                    N/A</div>
                <div class=" text-center " style="width: 20px;height: 20px"></div>
                <div class="border-l-t-b ps-2  " style="width: 100px;height: 25px; color: lightgray; font-style: italic" >RO
                    No.</div>
                <div class="border-all text-center pt-0 5 fs-9" style="width: 30px;height: 25px">
                    N/A
                </div>
                <div class="text-center spec-technician-spacer"></div>
                <div class="spec-technician-label">Technician</div>
                <div class="spec-technician-name-line">{{ $current_wo->user?->name }}</div>
                <div class="spec-header-square"></div>
            </div>

        </div>
        <div class="d-flex mb-1">
            <div class="" style="width: 80px"></div>
            <img src="{{ asset('img/icons/arrow_ld.png')}}" alt="arrow"
                 style="height: 10px;width: 60px" class="mt-2">
            <div class="border-b " style="width: 300px; height: 18px"><strong>Cat #2 (not included in NDT & Cad Cat #1)</strong>
            </div>
        </div>

        <div class="row g-0 spec-component-header-row">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 28px"><strong>Description</strong> </div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px">
                                <span class="">Bushings </span>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px">
                                <span class="">
                                </span>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r' }} text-center" style="height: 30px">
                                <span class="">
                                </span>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        @for($partNoRow = 0; $partNoRow < $partNoCellRows; $partNoRow++)
            <div class="row g-0 spec-component-header-row spec-part-no-row">
                <div class="col-2 border-l-t ps-1">
                    <div style="height: 30px">
                        @if($partNoRow === 0)
                            <strong> Part No.</strong>
                        @endif
                    </div>
                </div>
                <div class="col-10">
                    <div class="row g-0 ">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            @foreach($processGroups as $groupIndex => $group)
                                @php
                                    $partNumberCell = $group['part_number_cells'][$partNoRow] ?? [];
                                @endphp
                                <div class="col {{ ($groupIndex == count($processGroups) - 1 && count($processGroups) < 6) ?
                                'border-l-t-r' : 'border-l-t' }} text-center" style="height: 30px; padding: 1px;">
                                    @if(count($partNumberCell) > 0)
                                        <div class="part-no-data">
                                            @foreach($partNumberCell as $partNum)
                                                <div>{{ $partNum }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            @for($i = count($processGroups); $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px;
                                padding: 1px;">
                                    <span class="">
                                    </span>
                                </div>
                            @endfor
                        @else
                            @for($i = 0; $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r'}} text-center" style="height: 30px;
                                padding: 1px;">
                                    <span class="">
                                    </span>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
            </div>
        @endfor

        <div class="row g-0 spec-component-header-row">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 28px"><strong> Serial No</strong>.</div>
            </div>
            <div class="col-10">
                <div class="row g-0 ">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t'}} text-center" style="height: 30px">
                                <span class="">QTY: {{ $group['total_qty'] }}</span>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r'}} text-center" style="height: 30px">
                                <span class="">
                                </span>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-t-r'}} text-center" style="height: 30px">
                                <span class="">
                                </span>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 border-tt-gr">
            <div class="col-2 " >
                <div class=" text-end mb-1" style="height: 15px"><strong>Steps sequence</strong>
                    <img src="{{ asset('img/icons/arrow_rd.png')}}" alt="arrow"
                         style="height: 15px; margin-right: -15px" class="mt-2 ">
                </div>
            </div>
            <div class="col-10" >
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col  text-center " style="height: 24px">
                                <strong>RO No.</strong>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col  text-center " style="height: 24px">
                                <strong>RO No.</strong>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col  text-center " style="height: 15px">
                                <strong>RO No.</strong>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>N.D.T.</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('NDT', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['NDT'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('NDT', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Machining</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Machining', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Machining'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Machining', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Stress Relief</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Bake (Stress relief)', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Bake (Stress relief)'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Bake (Stress relief)', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Passivation</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Passivation', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Passivation'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Passivation', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>CAD</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('CAD', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['CAD'] }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('CAD', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Anodizing</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Anodizing', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Anodizing'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Anodizing', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-0 ">
            <div class="col-2 border-l-t ps-1">
                <div style="height: 30px"><strong>Xylan</strong></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                        @if(in_array('Xylan', $group['processes']))
                                            <span class="">{{ $group['process_numbers']['Xylan'] ?? '' }}</span>
                                        @endif
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                        @if(in_array('Xylan', $group['processes']))
                                            <span class="">
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        {{-- 7 fixed process rows + these 7 blanks = 14 selectable lower-table rows. --}}
        @for($row = 1; $row <= 7; $row++)
            <div class="row g-0 spec-extra-process-row">
                <div class="col-2 border-l-t ps-1">
                    <div style="height: 30px"></div>
                </div>
                <div class="col-10">
                    <div class="row g-0">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            @foreach($processGroups as $groupIndex => $group)
                                <div class="col {{ $groupIndex < 5 ? 'border-l-t-r' : 'border-l-t' }} text-center" style="height:
                            30px; position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center  border-r" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @for($i = count($processGroups); $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center border-r" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        @else
                            @for($i = 0; $i < 6; $i++)
                                <div class="col {{ $i < 5 ? 'border-l-t' : 'border-l-t-r' }} text-center" style="height: 30px;
                            position: relative;">
                                    <div class="row g-0">
                                        <div class="col-2 text-center" style="height: 30px;">
                                        </div>
                                        <div class="col-6 text-center" style="height: 30px;">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
            </div>
        @endfor
        <div class="row g-0 ">
            <div class="col-2 border-l-t-b ps-1">
                <div style="height: 22px"></div>
            </div>
            <div class="col-10">
                <div class="row g-0">
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @foreach($processGroups as $groupIndex => $group)
                            <div class="col {{ $groupIndex < 5 ? 'border-all' : 'border-l-t-b' }} text-center" style="height:
                            30px; position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @for($i = count($processGroups); $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        @for($i = 0; $i < 6; $i++)
                            <div class="col {{ $i < 5 ? 'border-l-t-b' : 'border-all' }} text-center" style="height: 30px;
                            position: relative;">
                                <div class="row g-0">
                                    <div class="col-2 text-center border-r" style="height: 30px;">
                                    </div>
                                    <div class="col-6 text-center" style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>

        <div class="parent mt-1">
            <div class="div2 text-end pe-4 mt-3" style="height: 24px">Quality Assurance Acceptance</div>
            <div class="div3 border-all text-center  3" style="width: 60px; align-content: center; height: 60px; color: grey">Q
                .A.
                STAMP</div>
            <div class="div4 border-t-r-b mt-3 ps-1" style="height: 24px; color: grey">Data</div>
        </div>

    </div>



    <footer class="spec-process-footer">
        <div class="row" style="width: 100%; padding: 5px 5px;">
            <div class="col-4 text-start">
                {{__("Form #012")}}
            </div>

            <div class="col-4 text-center">
                {{ $pageNumber }} of {{ $pageTotal }}
            </div>

            <div class="col-4 text-end pe-4 ">
                {{__('Rev#0, 15/Dec/2012   ')}}
            </div>
        </div>

    </footer>

    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif

@endforeach

<!-- Print settings panel -->
<div class="print-settings-panel print-settings-modal no-print" id="printSettingsModal" hidden>
    <div class="print-settings-dialog">
        <div class="print-settings-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">
                    ⚙️ Print Settings
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" onclick="togglePrintSettingsPanel(false)" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <div class="mb-4">
                        <h5 class="mb-3"
                            title="Only rows and table fonts are adjustable. Footer font is fixed at 12px."
                            data-tooltip-ru="Reguliruetsya tolko kolichestvo strok i shrifty tablits. Kolontitul zafiksirovan 12px."
                            data-tooltip-en="Only rows and table fonts are adjustable. Footer font is fixed at 12px.">
                            Tables
                        </h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="processTableRows" class="form-label"
                                       title="Number of process rows shown under Steps sequence."
                                       data-tooltip-ru="Kolichestvo strok process table pod Steps sequence."
                                       data-tooltip-en="Number of process rows shown under Steps sequence.">
                                    Rows
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="processTableRows" name="processTableRows"
                                           min="1" max="14" step="1" value="14">
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="tableDataFontSize" class="form-label"
                                       title="Font size for the process table."
                                       data-tooltip-ru="Shrift v process table."
                                       data-tooltip-en="Font size for the process table.">
                                    Table Font (px)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tableDataFontSize" name="tableDataFontSize"
                                           min="8" max="24" step="1" value="14">
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="componentHeaderFontSize" class="form-label"
                                       title="Font size for bushing header data cells: Description, Part No., Serial/QTY."
                                       data-tooltip-ru="Shrift dlya dannyh v header table: Description, Part No., Serial/QTY."
                                       data-tooltip-en="Font size for bushing header data cells: Description, Part No., Serial/QTY.">
                                    Header Data Font (px)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="componentHeaderFontSize" name="componentHeaderFontSize"
                                           min="8" max="24" step="1" value="16">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4 legacy-print-setting-block">
                        <h5 class="mb-3"
                            title="Настройки таблицы Special Process Form."
                            data-tooltip-ru="Настройки таблицы Special Process Form."
                            data-tooltip-en="Special Process Form table settings.">
                            📊 Tables
                        </h5>

                        <!-- Table Setting (collapse) -->
                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" aria-expanded="false"
                                            aria-controls="tableSettingsCollapse">
                                        <span
                                              title="Дополнительные настройки таблицы: ширина, отступы, масштаб и размер шрифта."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина, отступы, масштаб и размер шрифта."
                                              data-tooltip-en="Additional table settings: width, padding, scale and font size.">
                                            Table Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="tableSettingsHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label"
                                                       title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 980px."
                                                       data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 980px."
                                                       data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 980px.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="980">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerScale" class="form-label"
                                                       title="Масштаб контейнера (transform: scale). 0.97 - стандартное значение. Уменьшите для более компактного отображения."
                                                       data-tooltip-ru="Масштаб контейнера (transform: scale). 0.97 - стандартное значение. Уменьшите для более компактного отображения."
                                                       data-tooltip-en="Container scale (transform: scale). 0.97 - standard value. Decrease for more compact display.">
                                                    Scale
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerScale" name="containerScale"
                                                           min="0.5" max="1.5" step="0.01" value="0.97">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="tableFontSize" class="form-label"
                                                       title="Размер шрифта текста в таблице. Рекомендуемое значение: 0.9rem (14.4px). Увеличьте для лучшей читаемости."
                                                       data-tooltip-ru="Размер шрифта текста в таблице. Рекомендуемое значение: 0.9rem (14.4px). Увеличьте для лучшей читаемости."
                                                       data-tooltip-en="Font size for table text. Recommended value: 0.9rem (14.4px). Increase for better readability.">
                                                    Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="tableFontSize" name="tableFontSize"
                                                           min="0.5" max="2" step="0.05" value="0.9">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="partNoFontSize" class="form-label"
                                                       title="Размер шрифта для строки Part No. Длинные значения переносятся внутри одной строки детали, каждая деталь занимает всю ширину колонки."
                                                       data-tooltip-ru="Размер шрифта для строки Part No. Длинные значения переносятся внутри одной строки детали, каждая деталь занимает всю ширину колонки."
                                                       data-tooltip-en="Font size for Part No. Long values wrap inside one part row; each part uses the full column width.">
                                                    Part No. Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="partNoFontSize" name="partNoFontSize"
                                                           min="0.3" max="1.2" step="0.05" value="0.6">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label"
                                                       title="Внутренние отступы контейнера. По умолчанию: 3px."
                                                       data-tooltip-ru="Внутренние отступы контейнера. По умолчанию: 3px."
                                                       data-tooltip-en="Container inner padding. Default: 3px.">
                                                    Padding (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                           min="0" max="50" step="1" value="3">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label"
                                                       title="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                       data-tooltip-ru="Отступ контейнера с таблицей от левого края. По умолчанию: 10px."
                                                       data-tooltip-en="Table container margin from left edge. Default: 10px.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label"
                                                       title="Отступ контейнера с таблицей от правого края. По умолчанию: 10px."
                                                       data-tooltip-ru="Отступ контейнера с таблицей от правого края. По умолчанию: 10px."
                                                       data-tooltip-en="Table container margin from right edge. Default: 10px.">
                                                    Right Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                           min="0" max="50" step="1" value="10">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Setting (collapse) -->
                    <div class="mb-4 legacy-print-setting-block">
                        <div class="accordion" id="pageSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="pageSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" aria-expanded="false"
                                            aria-controls="pageSettingsCollapse">
                                        <span
                                              title="Настройки страницы: размер, поля и отступы."
                                              data-tooltip-ru="Настройки страницы: размер, поля и отступы."
                                              data-tooltip-en="Page settings: size, margins and padding.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="pageSettingsHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label"
                                                       title="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm."
                                                       data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 2mm."
                                                       data-tooltip-en="Margin from page edges when printing. Recommended value: 2mm.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                           min="0" max="50" step="0.5" value="2">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyMarginLeft" class="form-label"
                                                       title="Горизонтальный отступ основного контента от левого края. По умолчанию: 3px."
                                                       data-tooltip-ru="Горизонтальный отступ основного контента от левого края. По умолчанию: 3px."
                                                       data-tooltip-en="Horizontal margin of main content from left edge. Default: 3px.">
                                                    Left Margin (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyMarginLeft" name="bodyMarginLeft"
                                                           min="0" max="50" step="1" value="3">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Setting (collapse) -->
                    <div class="mb-4 legacy-print-setting-block">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" aria-expanded="false"
                                            aria-controls="footerSettingsCollapse">
                                        <span
                                              title="Настройки нижнего колонтитула формы."
                                              data-tooltip-ru="Настройки нижнего колонтитула формы."
                                              data-tooltip-en="Form footer settings.">
                                            Footer Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="footerSettingsHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label"
                                                       title="Ширина колонтитула в пикселях. 1060px - стандартное значение."
                                                       data-tooltip-ru="Ширина колонтитула в пикселях. 1060px - стандартное значение."
                                                       data-tooltip-en="Footer width in pixels. 1060px - standard value.">
                                                    Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="400" max="1200" step="10" value="1060">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label"
                                                       title="Размер шрифта текста в колонтитуле. 12px - стандартное значение."
                                                       data-tooltip-ru="Размер шрифта текста в колонтитуле. 12px - стандартное значение."
                                                       data-tooltip-en="Footer text font size. 12px - standard value.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="12">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label"
                                                       title="Внутренние отступы колонтитула. Например: '2px 2px'."
                                                       data-tooltip-ru="Внутренние отступы колонтитула. Например: '2px 2px'."
                                                       data-tooltip-en="Footer inner padding. Example: '2px 2px'.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="2px 2px" value="2px 2px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="resetPrintSettings()">Reset to Default</button>
                <button type="button" class="btn btn-primary" onclick="savePrintSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<script>
    const PRINT_SETTINGS_KEY = 'woBushingsSpecProcessForm_print_settings';
    const TOOLTIP_LANG_KEY = 'woBushingsSpecProcessForm_tooltip_lang';
    const PROCESS_TABLE_ROWS_MAX = 14;

    const defaultSettings = {
        pageMargin: '2mm',
        bodyMarginLeft: '3px',
        containerMaxWidth: '980px',
        containerScale: '0.97',
        containerPadding: '3px',
        containerMarginLeft: '10px',
        containerMarginRight: '10px',
        tableFontSize: '14px',
        componentHeaderFontSize: '16px',
        processTableRows: 14,
        footerWidth: '1060px',
        footerFontSize: '12px',
        footerPadding: '2px 2px'
    };

    function loadPrintSettings() {
        const saved = window.UserScopedStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try {
                return normalizePrintSettings(JSON.parse(saved));
            } catch (e) {
                console.error('Ошибка загрузки настроек:', e);
                return normalizePrintSettings(defaultSettings);
            }
        }
        return normalizePrintSettings(defaultSettings);
    }

    function clampNumber(value, min, max, fallback) {
        const numeric = parseFloat(value);
        if (!Number.isFinite(numeric)) {
            return fallback;
        }

        return Math.min(max, Math.max(min, numeric));
    }

    function valueToPixels(value, fallbackPx) {
        if (typeof value === 'number') {
            return value;
        }

        const text = String(value || '').trim().toLowerCase();
        const numeric = parseFloat(text);
        if (!Number.isFinite(numeric)) {
            return fallbackPx;
        }

        if (text.includes('rem')) {
            return numeric * 16;
        }

        return numeric;
    }

    function normalizePixelSetting(value, fallbackPx, min, max) {
        return Math.round(clampNumber(valueToPixels(value, fallbackPx), min, max, fallbackPx)) + 'px';
    }

    function normalizePrintSettings(settings) {
        const merged = Object.assign({}, defaultSettings, settings || {});

        return {
            pageMargin: defaultSettings.pageMargin,
            bodyMarginLeft: defaultSettings.bodyMarginLeft,
            containerMaxWidth: defaultSettings.containerMaxWidth,
            containerScale: defaultSettings.containerScale,
            containerPadding: defaultSettings.containerPadding,
            containerMarginLeft: defaultSettings.containerMarginLeft,
            containerMarginRight: defaultSettings.containerMarginRight,
            tableFontSize: normalizePixelSetting(merged.tableFontSize, 14, 8, 24),
            componentHeaderFontSize: normalizePixelSetting(merged.componentHeaderFontSize, 16, 8, 24),
            processTableRows: Math.round(clampNumber(merged.processTableRows, 1, PROCESS_TABLE_ROWS_MAX, 14)),
            footerWidth: defaultSettings.footerWidth,
            footerFontSize: defaultSettings.footerFontSize,
            footerPadding: defaultSettings.footerPadding
        };
    }

    window.savePrintSettings = function() {
        try {
            const getNumber = function(id, defaultValue) {
                const element = document.getElementById(id);
                if (!element) {
                    return defaultValue;
                }
                const value = parseFloat(element.value);
                return Number.isFinite(value) ? value : defaultValue;
            };

            const settings = normalizePrintSettings({
                processTableRows: getNumber('processTableRows', defaultSettings.processTableRows),
                tableFontSize: getNumber('tableDataFontSize', 14) + 'px',
                componentHeaderFontSize: getNumber('componentHeaderFontSize', 16) + 'px'
            });

            window.UserScopedStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);

            if (document.activeElement && document.activeElement.blur) {
                document.activeElement.blur();
            }

            togglePrintSettingsPanel(false);

        } catch (e) {
            console.error('Ошибка сохранения настроек:', e);
            showNotification('Error saving settings', 'error');
        }
    };

    function applyPrintSettings(settings) {
        settings = normalizePrintSettings(settings);
        const root = document.documentElement;
        root.style.setProperty('--print-page-margin', defaultSettings.pageMargin);
        root.style.setProperty('--print-body-margin-left', defaultSettings.bodyMarginLeft);
        root.style.setProperty('--container-max-width', defaultSettings.containerMaxWidth);
        root.style.setProperty('--container-scale', defaultSettings.containerScale);
        root.style.setProperty('--container-padding', defaultSettings.containerPadding);
        root.style.setProperty('--container-margin-left', defaultSettings.containerMarginLeft);
        root.style.setProperty('--container-margin-right', defaultSettings.containerMarginRight);
        root.style.setProperty('--table-font-size', settings.tableFontSize);
        root.style.setProperty('--component-header-font-size', settings.componentHeaderFontSize);
        root.style.setProperty('--print-footer-width', defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', '12px');
        root.style.setProperty('--print-footer-padding', defaultSettings.footerPadding);
        applyProcessTableRows(settings.processTableRows);
    }

    function applyProcessTableRows(rowCount) {
        document.querySelectorAll('.container-fluid').forEach(function(container) {
            const stepsRow = container.querySelector('.row.g-0.border-tt-gr');
            if (!stepsRow) {
                return;
            }

            const containerMaxRows = parseInt(container.dataset.processTableRowsMax || PROCESS_TABLE_ROWS_MAX, 10);
            const visibleRowCount = Math.min(
                Math.max(parseInt(rowCount, 10) || PROCESS_TABLE_ROWS_MAX, 1),
                Number.isFinite(containerMaxRows) ? containerMaxRows : PROCESS_TABLE_ROWS_MAX
            );

            const rows = [];
            let node = stepsRow.nextElementSibling;
            while (node && !node.classList.contains('parent')) {
                if (node.classList.contains('row') && node.classList.contains('g-0')) {
                    rows.push(node);
                }
                node = node.nextElementSibling;
            }

            rows.forEach(function(row) {
                row.classList.remove('spec-visible-process-last');
            });

            rows.forEach(function(row, index) {
                row.style.display = index < visibleRowCount ? '' : 'none';
            });

            if (rows.length > 0) {
                rows[Math.min(visibleRowCount, rows.length) - 1].classList.add('spec-visible-process-last');
            }
        });
    }

    function loadSettingsToForm(settings) {
        settings = normalizePrintSettings(settings);

        const processTableRows = document.getElementById('processTableRows');
        if (processTableRows) {
            processTableRows.value = settings.processTableRows;
        }

        const tableDataFontSize = document.getElementById('tableDataFontSize');
        if (tableDataFontSize) {
            tableDataFontSize.value = parseInt(settings.tableFontSize, 10);
        }

        const componentHeaderFontSize = document.getElementById('componentHeaderFontSize');
        if (componentHeaderFontSize) {
            componentHeaderFontSize.value = parseInt(settings.componentHeaderFontSize, 10);
        }
    }

    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            window.UserScopedStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            showNotification('Settings reset to default values!', 'success');
        }
    };

    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;

        let currentLang = window.UserScopedStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        currentLang = currentLang === 'ru' ? 'en' : 'ru';
        window.UserScopedStorage.setItem(TOOLTIP_LANG_KEY, currentLang);

        updateTooltipsLanguage(modal, currentLang);

        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }
    };

    function updateTooltipsLanguage(container, lang) {
        const tooltipElements = container.querySelectorAll('[data-tooltip-ru], [data-tooltip-en]');

        tooltipElements.forEach(function(el) {
            const ruText = el.getAttribute('data-tooltip-ru');
            const enText = el.getAttribute('data-tooltip-en');

            if (lang === 'ru' && ruText) {
                el.setAttribute('title', ruText);
            } else if (lang === 'en' && enText) {
                el.setAttribute('title', enText);
            }

        });
    }

    function initTooltipLanguage(modal) {
        const currentLang = window.UserScopedStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }

        setTimeout(function() {
            updateTooltipsLanguage(modal, currentLang);
        }, 100);
    }

    window.togglePrintSettingsPanel = function(open) {
        const panel = document.getElementById('printSettingsModal');
        if (!panel) return;

        const shouldOpen = typeof open === 'boolean' ? open : panel.hidden;
        panel.hidden = !shouldOpen;

        if (shouldOpen) {
            const currentSettings = loadPrintSettings();
            loadSettingsToForm(currentSettings);
            initTooltipLanguage(panel);
        }
    };

    function showNotification(message, type) {
        const existing = document.getElementById('printSettingsNotice');
        if (existing) {
            existing.remove();
        }

        const notice = document.createElement('div');
        notice.id = 'printSettingsNotice';
        notice.className = 'print-settings-notice ' + (type || 'success');
        notice.textContent = message;
        document.body.appendChild(notice);
        setTimeout(function() {
            notice.remove();
        }, 2200);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        loadSettingsToForm(settings);

        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            initTooltipLanguage(modal);
        }
    });
</script>

</div>
</body>
</html>
