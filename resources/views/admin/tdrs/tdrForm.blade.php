<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDR Form</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">

    <style>
        :root {
            --tdr-page-margin: 2mm;
            --tdr-sheet-width: 940px;
            --tdr-sheet-padding: 5px;
            --tdr-sheet-margin-left: 10px;
            --tdr-sheet-margin-right: 10px;
            --tdr-table-height: 676px;
            --tdr-data-font-size: 12px;
            --tdr-data-line-height: 1.15;
            --tdr-data-padding-left: 4px;
            --tdr-data-padding-right: 4px;
            --tdr-footer-width: 800px;
            --tdr-footer-font-size: 10px;
            --tdr-footer-padding: 3px 3px;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: "Times New Roman", Times, serif;
        }

        .tdr-toolbar {
            padding: 4px;
            font-family: Arial, sans-serif;
        }

        .tdr-sheet {
            width: min(var(--tdr-sheet-width), calc(100vw - var(--tdr-sheet-margin-left) - var(--tdr-sheet-margin-right)));
            max-width: calc(100vw - var(--tdr-sheet-margin-left) - var(--tdr-sheet-margin-right));
            margin: 0 var(--tdr-sheet-margin-right) 0 var(--tdr-sheet-margin-left);
            padding: var(--tdr-sheet-padding);
            background: #fff;
            color: #000;
            overflow-x: hidden;
        }

        .tdr-sheet,
        .tdr-sheet * {
            box-sizing: border-box;
        }

        .tdr-header {
            display: grid;
            grid-template-columns: 210px 1fr;
            align-items: start;
            min-height: 50px;
        }

        .tdr-logo {
            width: 120px;
            height: auto;
            margin: 6px 10px 0;
            display: block;
        }

        .tdr-title {
            margin: 18px 0 0;
            font-size: 18px;
            line-height: 1;
            font-weight: 700;
            text-align: left;
        }

        .tdr-meta-row {
            display: grid;
            grid-template-columns: 360px 1fr 110px;
            min-height: 32px;
        }

        .tdr-meta-row--part {
            grid-template-columns: 360px 1fr 110px;
        }

        .tdr-meta-label {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-right: 8px;
            font-size: 16px;
            line-height: 1;
        }

        .tdr-meta-value,
        .tdr-workorder-cell {
            display: flex;
            align-items: center;
            border: 1px solid #000;
            font-size: 16px;
            line-height: 1;
            font-weight: 700;
            padding: 2px 8px;
            min-width: 0;
        }

        .tdr-workorder-cell {
            border-left: 0;
            justify-content: center;
        }

        .tdr-meta-row--part .tdr-meta-value {
            border-top: 0;
        }

        .tdr-meta-row--part .tdr-workorder-cell {
            border: 0;
        }

        .tdr-section-head {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            margin: 8px 0 4px;
            min-height: 32px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
        }

        .tdr-section-left {
            display: grid;
            grid-template-columns: 8% 84% 8%;
        }

        .tdr-section-left::before,
        .tdr-section-left::after {
            content: "";
        }

        .tdr-section-title,
        .tdr-section-title-right {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #000;
        }

        .tdr-section-title-right {
            border-left-width: 2px;
        }

        .tdr-attention {
            display: grid;
            grid-template-columns: 42% 58%;
            min-height: 38px;
            border: 2px solid #000;
            font-weight: 700;
        }

        .tdr-attention-label {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-right: 8px;
            font-size: 14px;
        }

        .tdr-attention-text {
            display: flex;
            align-items: center;
            font-size: 11px;
            line-height: 1.05;
            padding: 2px 4px;
        }

        .tdr-confirm-grid {
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) minmax(0, 1fr) 36px;
            grid-auto-rows: 30px;
        }

        .tdr-confirm-left,
        .tdr-confirm-right {
            display: contents;
            min-height: 36px;
        }

        .tdr-confirm-left .tdr-confirm-check,
        .tdr-confirm-right .tdr-confirm-icon {
            display: none;
        }

        .tdr-confirm-icon,
        .tdr-confirm-check,
        .tdr-confirm-text,
        .tdr-instruction-text {
            border-bottom: 2px solid #000;
            min-height: 30px;
        }

        .tdr-confirm-icon {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tdr-confirm-left .tdr-confirm-text {
            border-left: 2px solid #000;
        }

        .tdr-confirm-check {
            border-left: 1px solid #000;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .tdr-confirm-right .tdr-confirm-check {
            border-right: 2px solid #000;
        }

        .tdr-confirm-text,
        .tdr-instruction-text {
            display: flex;
            align-items: center;
            padding: 0 6px;
            font-size: 11px;
            line-height: 1.05;
        }

        .tdr-instruction-text {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            justify-content: center;
        }

        .tdr-reqs {
            width: auto;
            height: 26px;
            display: block;
        }

        .tdr-reqs-bb {
            width: auto;
            height: 40px;
            display: block;
            margin-left: -16px;
        }

        .tdr-check {
            width: 32px;
            height: auto;
            max-width: none;
            display: block;
            margin-top: 2px;
        }

        .tdr-lines {
            margin-top: 0;
        }

        .tdr-page {
            page-break-after: always;
            position: relative;
        }

        .tdr-page:last-child {
            page-break-after: auto;
        }

        .tdr-table {
            display: flex;
            flex-direction: column;
            align-items: start;
            min-height: var(--tdr-table-height);
        }

        .tdr-table-row {
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) 36px 10px 36px minmax(0, 1fr) 36px;
            width: 100%;
            align-items: stretch;
        }

        .tdr-entry-cell {
            min-width: 0;
            border-bottom: 1px solid #000;
            display: flex;
            align-items: flex-start;
            min-height: 36px;
        }

        .tdr-entry-icon-left {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            align-items: center;
            justify-content: center;
        }

        .tdr-entry-icon-right {
            border-right: 1px solid #000;
            align-items: center;
            justify-content: center;
        }

        .tdr-entry-check-left {
            border-left: 1px solid #000;
            align-items: flex-start;
            justify-content: center;
        }

        .tdr-entry-check-right {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            align-items: flex-start;
            justify-content: center;
        }

        .tdr-separator {
            min-height: 36px;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            background: #d9d9d9;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            forced-color-adjust: none;
        }

        .tdr-entry-text {
            display: block;
            padding: 3px var(--tdr-data-padding-right) 2px var(--tdr-data-padding-left);
            font-size: var(--tdr-data-font-size);
            line-height: var(--tdr-data-line-height);
            overflow-wrap: break-word;
            word-break: normal;
            text-transform: uppercase;
            white-space: normal;
        }

        .tdr-entry-text p {
            margin: 0;
        }

        .tdr-entry-text b,
        .tdr-entry-text strong {
            font-weight: 700;
        }

        .tdr-footer {
            width: min(var(--tdr-footer-width), 100%);
            max-width: 100%;
            margin: 12px auto 0;
            padding: var(--tdr-footer-padding);
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            font-size: var(--tdr-footer-font-size);
            line-height: 1.1;
        }

        .tdr-footer-center {
            text-align: center;
        }

        .tdr-footer-right {
            text-align: right;
            padding-right: 24px;
        }

        .tdr-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 14px;
            border-radius: 4px;
            color: #fff;
            font-family: Arial, sans-serif;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
        }

        .tdr-notification--success {
            background: #198754;
        }

        .tdr-notification--error {
            background: #dc3545;
        }

        @media print {
            @page {
                size: letter;
                margin: var(--tdr-page-margin);
            }

            html,
            body {
                width: 100%;
                min-height: 100%;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .tdr-sheet {
                width: var(--tdr-sheet-width);
                margin-left: var(--tdr-sheet-margin-left);
                margin-right: var(--tdr-sheet-margin-right);
                padding: var(--tdr-sheet-padding);
            }

            .tdr-page {
                page-break-after: always;
                break-after: page;
            }

            .tdr-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>
@php
    $totalPages = 1;
@endphp

<div class="tdr-toolbar no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">Print Form</button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        Print Settings
    </button>
</div>

<template id="tdrFrontMatterTemplate">
    <section class="tdr-front-matter">
        <div class="tdr-header">
            <img class="tdr-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo">
            <h1 class="tdr-title">WORK ORDER TEAR DOWN REPORT</h1>
        </div>

        <div class="tdr-meta-row">
            <div class="tdr-meta-label">COMPONENT DESCRPTION:</div>
            <div class="tdr-meta-value">{{ $current_wo->description }}</div>
            <div class="tdr-workorder-cell">W{{ $current_wo->number }}</div>
        </div>

        <div class="tdr-meta-row tdr-meta-row--part">
            <div class="tdr-meta-label">COMPONENT PART NO.:</div>
            <div class="tdr-meta-value">{{ $current_wo->unit->part_number }}</div>
            <div class="tdr-workorder-cell"></div>
        </div>

        <div class="tdr-section-head">
            <div class="tdr-section-left">
                <div class="tdr-section-title">TEARDOWN INSPECTION &amp; CONDITION:</div>
            </div>
            <div class="tdr-section-title-right">TEARDOWN INSPECTION &amp; CONDITION:</div>
        </div>

        <div class="tdr-attention">
            <div class="tdr-attention-label">ATTENTION PRODUCTION DEPARTMENT:</div>
            <div class="tdr-attention-text">
                MAKE SURE TO ADD INFORMATION FROM WO COWER SHEET TO IDENTIFY PRELIMINARY INSPECTION
                DETAILS FOR STRIP REPORT
            </div>
        </div>

        <div class="tdr-confirm-grid">
            <div class="tdr-confirm-left">
                <div class="tdr-confirm-icon">
                    <img class="tdr-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs">
                </div>
                <div class="tdr-confirm-text">CUSTOMER SNAG CONFIRMED ?</div>
                <div class="tdr-confirm-check"></div>
            </div>
            <div class="tdr-confirm-right">
                <div class="tdr-confirm-icon"></div>
                <div class="tdr-instruction-text">{{ $current_wo->instruction->name }}</div>
                <div class="tdr-confirm-check">
                    <img class="tdr-check" src="{{ asset('img/icons/check.svg') }}" alt="Check">
                </div>
            </div>
        </div>

        <div class="tdr-confirm-grid">
            <div class="tdr-confirm-left">
                <div class="tdr-confirm-icon">
                    <img class="tdr-reqs" src="{{ asset('img/icons/reqs.png') }}" alt="reqs">
                </div>
                <div class="tdr-confirm-text">CUSTOMER SNAG <strong>&nbsp;NOT&nbsp;</strong> CONFIRMED ?</div>
                <div class="tdr-confirm-check"></div>
            </div>
            <div class="tdr-confirm-right">
                <div class="tdr-confirm-icon"></div>
                <div class="tdr-instruction-text"></div>
                <div class="tdr-confirm-check"></div>
            </div>
        </div>
    </section>
</template>

<main class="tdr-sheet">
    <section class="all-rows-container tdr-lines"></section>
</main>

<div class="modal fade print-settings-modal no-print" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">Print Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <div class="mb-4">
                        <h5 class="mb-3">Tables</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tdrTableHeight" class="form-label">TDR table height (px)</label>
                                <input type="number" class="form-control" id="tdrTableHeight" name="tdrTableHeight"
                                       min="200" max="1200" step="10" value="640">
                                <small class="form-text text-muted">Column fills to this height, then continues in the second column.</small>
                            </div>
                        </div>

                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#tableSettingsCollapse" aria-expanded="false"
                                            aria-controls="tableSettingsCollapse">
                                        Table Setting
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label">Max Width (px)</label>
                                                <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                       min="500" max="2000" step="10" value="940">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerPadding" class="form-label">Padding (px)</label>
                                                <input type="number" class="form-control" id="containerPadding" name="containerPadding"
                                                       min="0" max="50" step="1" value="5">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginLeft" class="form-label">Left Margin (px)</label>
                                                <input type="number" class="form-control" id="containerMarginLeft" name="containerMarginLeft"
                                                       min="0" max="50" step="1" value="10">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMarginRight" class="form-label">Right Margin (px)</label>
                                                <input type="number" class="form-control" id="containerMarginRight" name="containerMarginRight"
                                                       min="0" max="50" step="1" value="10">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tdrGridFontSize" class="form-label">TDR data font size (px)</label>
                                                <input type="number" class="form-control" id="tdrGridFontSize" name="tdrGridFontSize"
                                                       min="6" max="24" step="0.5" value="12">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tdrGridLineHeight" class="form-label">TDR data line height</label>
                                                <input type="number" class="form-control" id="tdrGridLineHeight" name="tdrGridLineHeight"
                                                       min="1" max="1.8" step="0.05" value="1.15">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tdrGridTextPaddingLeft" class="form-label">TDR text padding left (px)</label>
                                                <input type="number" class="form-control" id="tdrGridTextPaddingLeft" name="tdrGridTextPaddingLeft"
                                                       min="0" max="24" step="1" value="4">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tdrGridTextPaddingRight" class="form-label">TDR text padding right (px)</label>
                                                <input type="number" class="form-control" id="tdrGridTextPaddingRight" name="tdrGridTextPaddingRight"
                                                       min="0" max="24" step="1" value="4">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="accordion" id="pageSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="pageSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#pageSettingsCollapse" aria-expanded="false"
                                            aria-controls="pageSettingsCollapse">
                                        Page Setting
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label">Page Margin (mm)</label>
                                                <input type="number" class="form-control" id="pageMargin" name="pageMargin"
                                                       min="0" max="50" step="0.5" value="2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#footerSettingsCollapse" aria-expanded="false"
                                            aria-controls="footerSettingsCollapse">
                                        Footer Setting
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label">Width on page (px)</label>
                                                <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                       min="400" max="1200" step="10" value="800">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label">Font Size (px)</label>
                                                <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                       min="6" max="20" step="0.5" value="10">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label">Padding</label>
                                                <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                       placeholder="3px 3px" value="3px 3px">
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
    const PRINT_SETTINGS_KEY = 'tdrForm_print_settings';
    const PRINT_SETTINGS_LAYOUT_VERSION = 'pure-css-v4';

    const defaultSettings = {
        layoutVersion: PRINT_SETTINGS_LAYOUT_VERSION,
        tdrTableHeight: '676px',
        pageMargin: '2mm',
        containerMaxWidth: '940px',
        containerPadding: '5px',
        containerMarginLeft: '10px',
        containerMarginRight: '10px',
        footerWidth: '800px',
        footerFontSize: '10px',
        footerPadding: '3px 3px',
        tdrGridFontSize: '12px',
        tdrGridLineHeight: '1.15',
        tdrGridTextPaddingLeft: '4px',
        tdrGridTextPaddingRight: '4px'
    };

    const allInspections = @json($tdrInspections);
    const reqsIcon = @json(asset('img/icons/reqs.png'));
    const reqsBbIcon = @json(asset('img/icons/reqs_bb.png'));
    const checkIcon = @json(asset('img/icons/check.svg'));

    function loadPrintSettings() {
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (!saved) {
            return defaultSettings;
        }

        try {
            const parsed = JSON.parse(saved);

            if (parsed.layoutVersion !== PRINT_SETTINGS_LAYOUT_VERSION) {
                return Object.assign({}, defaultSettings, parsed, {
                    layoutVersion: PRINT_SETTINGS_LAYOUT_VERSION,
                    tdrTableHeight: defaultSettings.tdrTableHeight,
                    tdrGridFontSize: defaultSettings.tdrGridFontSize,
                    tdrGridLineHeight: defaultSettings.tdrGridLineHeight,
                    tdrGridTextPaddingLeft: defaultSettings.tdrGridTextPaddingLeft,
                    tdrGridTextPaddingRight: defaultSettings.tdrGridTextPaddingRight
                });
            }

            return Object.assign({}, defaultSettings, parsed);
        } catch (e) {
            return defaultSettings;
        }
    }

    function settingValue(id, fallback, suffix) {
        const element = document.getElementById(id);
        if (!element || element.value === '') {
            return fallback;
        }

        return element.value + (suffix || '');
    }

    window.savePrintSettings = function() {
        const settings = {
            layoutVersion: PRINT_SETTINGS_LAYOUT_VERSION,
            tdrTableHeight: settingValue('tdrTableHeight', defaultSettings.tdrTableHeight, 'px'),
            pageMargin: settingValue('pageMargin', defaultSettings.pageMargin, 'mm'),
            containerMaxWidth: settingValue('containerMaxWidth', defaultSettings.containerMaxWidth, 'px'),
            containerPadding: settingValue('containerPadding', defaultSettings.containerPadding, 'px'),
            containerMarginLeft: settingValue('containerMarginLeft', defaultSettings.containerMarginLeft, 'px'),
            containerMarginRight: settingValue('containerMarginRight', defaultSettings.containerMarginRight, 'px'),
            footerWidth: settingValue('footerWidth', defaultSettings.footerWidth, 'px'),
            footerFontSize: settingValue('footerFontSize', defaultSettings.footerFontSize, 'px'),
            footerPadding: settingValue('footerPadding', defaultSettings.footerPadding, ''),
            tdrGridFontSize: settingValue('tdrGridFontSize', defaultSettings.tdrGridFontSize, 'px'),
            tdrGridLineHeight: settingValue('tdrGridLineHeight', defaultSettings.tdrGridLineHeight, ''),
            tdrGridTextPaddingLeft: settingValue('tdrGridTextPaddingLeft', defaultSettings.tdrGridTextPaddingLeft, 'px'),
            tdrGridTextPaddingRight: settingValue('tdrGridTextPaddingRight', defaultSettings.tdrGridTextPaddingRight, 'px')
        };

        localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
        applyPrintSettings(settings);
        renderTdrRows(settings);

        const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
        if (modal) {
            modal.hide();
        }
    };

    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--tdr-page-margin', settings.pageMargin || defaultSettings.pageMargin);
        root.style.setProperty('--tdr-sheet-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--tdr-sheet-padding', settings.containerPadding || defaultSettings.containerPadding);
        root.style.setProperty('--tdr-sheet-margin-left', settings.containerMarginLeft || defaultSettings.containerMarginLeft);
        root.style.setProperty('--tdr-sheet-margin-right', settings.containerMarginRight || defaultSettings.containerMarginRight);
        root.style.setProperty('--tdr-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--tdr-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--tdr-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
        root.style.setProperty('--tdr-table-height', settings.tdrTableHeight || defaultSettings.tdrTableHeight);
        root.style.setProperty('--tdr-data-font-size', settings.tdrGridFontSize || defaultSettings.tdrGridFontSize);
        root.style.setProperty('--tdr-data-line-height', settings.tdrGridLineHeight || defaultSettings.tdrGridLineHeight);
        root.style.setProperty('--tdr-data-padding-left', settings.tdrGridTextPaddingLeft || defaultSettings.tdrGridTextPaddingLeft);
        root.style.setProperty('--tdr-data-padding-right', settings.tdrGridTextPaddingRight || defaultSettings.tdrGridTextPaddingRight);
    }

    function renderTdrRows(settings) {
        const container = document.querySelector('.all-rows-container');

        if (!container) {
            return;
        }

        container.innerHTML = '';

        const tableHeight = parseCssPixels(settings.tdrTableHeight || defaultSettings.tdrTableHeight, 640);
        const measureTable = createMeasurementTable(container);
        const pages = packInspectionsByColumnHeight(allInspections, tableHeight, measureTable);
        measureTable.remove();

        pages.forEach(function(pageData, index) {
            const page = createPage(index + 1);
            const table = page.querySelector('.tdr-table');
            const rowCount = Math.max(pageData.left.length, pageData.right.length);

            for (let rowIndex = 0; rowIndex < rowCount; rowIndex++) {
                table.appendChild(createTdrTableRow(pageData.left[rowIndex] || '', pageData.right[rowIndex] || '', rowIndex));
            }

            page.appendChild(createTdrFooter(index + 1, pages.length));
            container.appendChild(page);
            fillTdrTableToHeight(table, rowCount, tableHeight);
        });
    }

    function fillTdrTableToHeight(table, nextRowIndex, tableHeight) {
        let contentHeight = Array.from(table.children).reduce(function(total, row) {
            return total + Math.max(36, Math.ceil(row.getBoundingClientRect().height));
        }, 0);
        const maxBlankRows = Math.ceil(tableHeight / 36) + 2;

        for (let added = 0; contentHeight < tableHeight - 1 && added < maxBlankRows; added++) {
            const row = createTdrTableRow('', '', nextRowIndex + added);
            row.classList.add('tdr-empty-row');
            table.appendChild(row);
            contentHeight += Math.max(36, Math.ceil(row.getBoundingClientRect().height));
        }
    }

    function parseCssPixels(value, fallback) {
        const parsed = parseFloat(String(value || '').replace('px', ''));
        return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
    }

    function createPage(pageNumber) {
        const page = document.createElement('div');
        page.className = 'tdr-page data-page';
        page.dataset.pageIndex = String(pageNumber);

        const frontMatterTemplate = document.getElementById('tdrFrontMatterTemplate');
        if (frontMatterTemplate) {
            page.appendChild(frontMatterTemplate.content.cloneNode(true));
        }

        const table = document.createElement('div');
        table.className = 'tdr-table';
        page.appendChild(table);

        return page;
    }

    function createMeasurementTable(container) {
        const measure = document.createElement('div');
        measure.className = 'tdr-table';
        measure.style.position = 'absolute';
        measure.style.visibility = 'hidden';
        measure.style.pointerEvents = 'none';
        measure.style.width = getComputedStyle(document.querySelector('.tdr-sheet')).width;
        measure.style.left = '-10000px';
        measure.style.top = '0';
        container.appendChild(measure);
        return measure;
    }

    function packInspectionsByColumnHeight(items, tableHeight, measureTable) {
        const pages = [{ left: [], right: [] }];
        let page = pages[0];
        let side = 'left';
        let usedHeight = 0;

        items.forEach(function(item, index) {
            const itemHeight = measureEntryHeight(item, measureTable);
            const currentColumn = page[side];
            const wouldOverflow = usedHeight + itemHeight > tableHeight && currentColumn.length > 0;

            if (wouldOverflow) {
                if (side === 'left') {
                    side = 'right';
                    usedHeight = 0;
                } else {
                    page = { left: [], right: [] };
                    pages.push(page);
                    side = 'left';
                    usedHeight = 0;
                }
            }

            page[side].push(item);
            usedHeight += itemHeight;
        });

        return pages;
    }

    function measureEntryHeight(value, measureTable) {
        measureTable.innerHTML = '';
        const row = createTdrTableRow(value, '', 0);
        measureTable.appendChild(row);
        return Math.max(36, Math.ceil(row.getBoundingClientRect().height));
    }

    function createTdrTableRow(leftValue, rightValue, rowIndex) {
        const row = document.createElement('div');
        row.className = 'tdr-table-row';
        row.dataset.rowIndex = String(rowIndex);

        row.appendChild(createIconCell('tdr-entry-icon-left', false));
        row.appendChild(createTextCell(leftValue));
        row.appendChild(createCheckCell('tdr-entry-check-left', leftValue));
        row.appendChild(createSeparatorCell());
        row.appendChild(createIconCell('tdr-entry-icon-right', false));
        row.appendChild(createTextCell(rightValue));
        row.appendChild(createCheckCell('tdr-entry-check-right', rightValue));

        return row;
    }

    function createSeparatorCell() {
        const cell = document.createElement('div');
        cell.className = 'tdr-separator';
        return cell;
    }

    function createIconCell(className, doubleIcon) {
        const cell = document.createElement('div');
        cell.className = 'tdr-entry-cell ' + className;

        if (doubleIcon) {
            const bb = document.createElement('img');
            bb.className = 'tdr-reqs-bb';
            bb.src = reqsBbIcon;
            bb.alt = 'reqs';
            cell.appendChild(bb);
        }

        const reqs = document.createElement('img');
        reqs.className = 'tdr-reqs';
        reqs.src = reqsIcon;
        reqs.alt = 'reqs';
        cell.appendChild(reqs);

        return cell;
    }

    function createTextCell(value) {
        const cell = document.createElement('div');
        cell.className = 'tdr-entry-cell tdr-entry-text';
        cell.innerHTML = String(value || '').toUpperCase();
        return cell;
    }

    function createCheckCell(className, value) {
        const cell = document.createElement('div');
        cell.className = 'tdr-entry-cell ' + className;

        if (value) {
            const check = document.createElement('img');
            check.className = 'tdr-check';
            check.src = checkIcon;
            check.alt = 'Check';
            cell.appendChild(check);
        }

        return cell;
    }

    function createTdrFooter(pageNumber, totalPages) {
        const footer = document.createElement('footer');
        footer.className = 'tdr-footer';
        footer.innerHTML = '<div>Form #003</div>' +
            '<div class="tdr-footer-center">Page ' + pageNumber + ' of ' + totalPages + '</div>' +
            '<div class="tdr-footer-right">Rev#0, 15/Dec/2012</div>';
        return footer;
    }

    function loadSettingsToForm(settings) {
        const values = {
            tdrTableHeight: [settings.tdrTableHeight, 'px'],
            pageMargin: [settings.pageMargin, 'mm'],
            containerMaxWidth: [settings.containerMaxWidth, 'px'],
            containerPadding: [settings.containerPadding, 'px'],
            containerMarginLeft: [settings.containerMarginLeft, 'px'],
            containerMarginRight: [settings.containerMarginRight, 'px'],
            footerWidth: [settings.footerWidth, 'px'],
            footerFontSize: [settings.footerFontSize, 'px'],
            footerPadding: [settings.footerPadding, ''],
            tdrGridFontSize: [settings.tdrGridFontSize, 'px'],
            tdrGridLineHeight: [settings.tdrGridLineHeight, ''],
            tdrGridTextPaddingLeft: [settings.tdrGridTextPaddingLeft, 'px'],
            tdrGridTextPaddingRight: [settings.tdrGridTextPaddingRight, 'px']
        };

        Object.keys(values).forEach(function(id) {
            const element = document.getElementById(id);
            if (!element) {
                return;
            }

            const value = String(values[id][0] || '');
            const suffix = values[id][1];
            element.value = suffix ? value.replace(suffix, '') : value;
        });
    }

    window.resetPrintSettings = function() {
        if (!confirm('Reset all print settings to default values?')) {
            return;
        }

        localStorage.removeItem(PRINT_SETTINGS_KEY);
        applyPrintSettings(defaultSettings);
        renderTdrRows(defaultSettings);
        loadSettingsToForm(defaultSettings);
        showNotification('Settings reset to default.', 'success');
    };

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'tdr-notification tdr-notification--' + (type || 'success');
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(function() {
            notification.remove();
        }, 2400);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        renderTdrRows(settings);
        loadSettingsToForm(settings);

        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                loadSettingsToForm(loadPrintSettings());
            });
        }
    });
</script>

<script src="{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
