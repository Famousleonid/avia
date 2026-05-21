<style>
    :root {
        --container-max-width: {{ (int) ($tdrFormConfig['container_max_width'] ?? 920) }}px;
        --container-padding: {{ (int) ($tdrFormConfig['container_padding'] ?? 6) }}px;
        --container-margin-left: {{ (int) ($tdrFormConfig['container_margin_left'] ?? 10) }}px;
        --container-margin-right: {{ (int) ($tdrFormConfig['container_margin_right'] ?? 10) }}px;
        --print-page-margin: {{ $tdrFormConfig['page_margin'] ?? 1 }}mm;
        --print-body-height: {{ (int) ($tdrFormConfig['body_height'] ?? 99) }}%;
        --print-body-width: {{ (int) ($tdrFormConfig['body_width'] ?? 98) }}%;
        --print-body-margin-left: {{ (int) ($tdrFormConfig['body_margin_left'] ?? 2) }}px;
        --print-footer-width: {{ (int) ($tdrFormConfig['footer_width'] ?? 800) }}px;
        --print-footer-font-size: {{ (int) ($tdrFormConfig['footer_font_size'] ?? 10) }}px;
        --print-footer-padding: {{ $tdrFormConfig['footer_padding'] ?? '3px 3px' }};
        --print-user-scale: 1;
        --component-name-font-size: {{ (int) ($tdrFormConfig['component_name_font_size'] ?? 12) }}px;
        --std-header-data-font-size: {{ (float) ($tdrFormConfig['header_data_font_size'] ?? 11) }}px;
        --std-table-data-font-size: {{ (float) ($tdrFormConfig['table_data_font_size'] ?? 12) }}px;
        --std-font-size-title: 20px;
        --std-font-size-section: 16px;
        --std-font-size-label: 14px;
        --std-font-size-body: 13px;
        --std-font-size-small: 12px;
        --std-label-width-left: 180px;
        --std-label-width-right: 112px;
        --std-row-min-height: 32px;
        --std-header-row-height: 42px;
        --std-grid-gap: 10px;
        --std-line-color: #000;
        --std-page-gap: 18px;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        color: #000;
        background: #fff;
        font-family: "Times New Roman", serif;
    }

    body {
        min-height: 100vh;
        font-size: var(--std-font-size-body);
    }

    .std-print-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 6px;
    }

    .container-fluid.std-sheet-container {
        max-width: var(--container-max-width);
        padding: var(--container-padding);
        margin-left: var(--container-margin-left);
        margin-right: var(--container-margin-right);
        box-sizing: border-box;
    }

    .container-fluid.std-sheet-container + .container-fluid.std-sheet-container {
        margin-top: var(--std-page-gap);
    }

    .std-page {
        width: 100%;
        box-sizing: border-box;
    }

    .page-rows-container {
        width: 100%;
    }

    .std-header {
        display: grid;
        gap: 12px;
    }

    .std-header-top {
        display: grid;
        grid-template-columns: 188px minmax(0, 1fr);
        align-items: center;
        column-gap: 10px;
    }

    .std-header-logo {
        width: 180px;
        max-width: 100%;
        height: auto;
        display: block;
    }

    .std-header-title {
        margin: 0;
        text-align: center;
        font-size: var(--std-font-size-title);
        font-weight: 700;
        line-height: 1.05;
    }

    .std-header-title--ndt {
        font-size: 30px;
        line-height: 1;
    }

    .std-header-title--cad {
        font-size: 24px;
        line-height: 1;
    }

    .std-meta-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        column-gap: 36px;
        row-gap: 12px;
        align-items: start;
    }

    .std-meta-column {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .std-meta-row {
        display: grid;
        grid-template-columns: var(--std-label-width-left) minmax(0, 1fr);
        align-items: end;
        column-gap: 12px;
        min-height: 32px;
    }

    .std-meta-row--right {
        grid-template-columns: var(--std-label-width-right) minmax(0, 1fr);
    }

    .std-meta-label {
        text-align: right;
        font-size: var(--std-font-size-label);
        font-weight: 700;
        line-height: 1.1;
    }

    .std-meta-value {
        min-height: 26px;
        display: flex;
        align-items: flex-end;
        padding: 0 6px 2px 8px;
        border-bottom: 1px solid var(--std-line-color);
        box-sizing: border-box;
        font-size: var(--std-header-data-font-size);
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .std-component-name {
        font-size: var(--component-name-font-size);
        line-height: 1.1;
        letter-spacing: 0;
    }

    .std-component-name[data-long="1"] {
        letter-spacing: -0.2px;
    }

    .std-instruction {
        font-size: var(--std-font-size-label);
        font-weight: 700;
        line-height: 1.2;
    }

    .std-instruction-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 126px 180px;
        align-items: center;
        column-gap: 10px;
    }

    .std-manual-ref-label {
        text-align: right;
        font-weight: 700;
    }

    .std-manual-ref-box {
        min-height: 54px;
        border: 1px solid var(--std-line-color);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        box-sizing: border-box;
        font-weight: 700;
        text-align: center;
    }

    .std-ndt-grid {
        display: grid;
        grid-template-columns: minmax(0, 5fr) minmax(0, 3fr) minmax(0, 4fr);
        gap: 24px;
        align-items: start;
    }

    .std-ndt-column {
        display: grid;
        gap: 6px;
    }

    .std-ndt-grid--aligned {
        grid-template-columns: minmax(0, 5fr) minmax(0, 3fr) minmax(0, 4fr);
        grid-template-rows: auto 26px auto 26px auto 54px;
        column-gap: 24px;
        row-gap: 4px;
        align-items: end;
    }

    .std-ndt-title {
        margin: 0;
        font-size: var(--std-font-size-section);
        font-weight: 700;
        line-height: 1.15;
    }

    .std-ndt-title--magnetic {
        grid-column: 1;
        grid-row: 1;
    }

    .std-ndt-title--liquid {
        grid-column: 1;
        grid-row: 3;
    }

    .std-ndt-title--ultrasound {
        grid-column: 1;
        grid-row: 5;
    }

    .std-ndt-title--eddy {
        grid-column: 3;
        grid-row: 3;
    }

    .std-ndt-line {
        display: grid;
        grid-template-columns: 28px minmax(0, 1fr);
        align-items: end;
        column-gap: 10px;
        min-height: 26px;
    }

    .std-ndt-line--one {
        grid-column: 1;
        grid-row: 2;
    }

    .std-ndt-line--two {
        grid-column: 2;
        grid-row: 2;
    }

    .std-ndt-line--three {
        grid-column: 3;
        grid-row: 2;
    }

    .std-ndt-line--four {
        grid-column: 1;
        grid-row: 4;
    }

    .std-ndt-line--five {
        grid-column: 2;
        grid-row: 4;
    }

    .std-ndt-line--six {
        grid-column: 3;
        grid-row: 4;
    }

    .std-ndt-line--seven {
        grid-column: 1;
        grid-row: 6;
        align-self: start;
    }

    .std-ndt-cmm-label {
        grid-column: 2;
        grid-row: 6;
        align-self: center;
        margin: 0;
    }

    .std-ndt-cmm-box {
        grid-column: 3;
        grid-row: 6;
        align-self: stretch;
    }

    .std-ndt-index {
        text-align: right;
        line-height: 1.1;
    }

    .std-ndt-value {
        min-height: 24px;
        display: flex;
        align-items: flex-end;
        padding: 0 4px 2px 6px;
        border-bottom: 1px solid var(--std-line-color);
        box-sizing: border-box;
        line-height: 1.12;
        overflow-wrap: anywhere;
    }

    .std-process-long,
    .std-description-long {
        display: inline-block;
        line-height: 1.1;
        letter-spacing: -0.2px;
    }

    .std-table {
        margin-top: 12px;
    }

    .std-grid-row {
        display: grid;
        grid-template-columns: var(--std-table-columns);
        width: 100%;
    }

    .std-grid-row > .std-cell {
        min-height: var(--std-row-min-height);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px 6px;
        box-sizing: border-box;
        text-align: center;
        font-size: var(--std-font-size-small);
        line-height: 1.15;
        white-space: pre-line;
        overflow-wrap: anywhere;
        border-right: 1px solid var(--std-line-color);
        border-bottom: 1px solid var(--std-line-color);
    }

    .std-grid-row > .std-cell:first-child {
        border-left: 1px solid var(--std-line-color);
    }

    .std-grid-row--header > .std-cell {
        min-height: var(--std-header-row-height);
        border-top: 1px solid var(--std-line-color);
        font-size: var(--std-font-size-small);
        font-weight: 700;
    }

    .page-rows-container .std-grid-row:not(.std-grid-row--header) > .std-cell {
        font-size: var(--std-table-data-font-size);
    }

    .std-grid-row--manual > .std-cell {
        font-weight: 700;
    }

    .std-grid-row--full > .std-cell {
        grid-column: 1 / -1;
        justify-content: center;
        text-align: center;
    }

    .std-grid-row--empty > .std-cell {
        min-height: var(--std-row-min-height);
    }

    .std-cell--left {
        justify-content: flex-start;
        text-align: left;
    }

    .std-cell--multiline {
        white-space: pre-line;
        line-height: 1.15;
    }

    .std-grid-row > .std-cell.std-cell-fit {
        overflow: hidden;
        white-space: nowrap;
        overflow-wrap: normal;
        padding-left: 3px;
        padding-right: 3px;
    }

    .std-cell-fit > span {
        display: block;
        max-width: 100%;
        min-width: 0;
        white-space: nowrap;
        line-height: 1.05;
        letter-spacing: 0;
    }

    .std-cell-fit--sm > span {
        font-size: max(10px, calc(var(--std-table-data-font-size) - 1px));
    }

    .std-cell-fit--xs > span {
        font-size: max(9px, calc(var(--std-table-data-font-size) - 1.5px));
    }

    .std-table-summary {
        width: 100%;
        margin: 6px 0 4px;
        text-align: center;
        font-size: var(--std-table-data-font-size);
        line-height: 1.2;
    }

    .std-footer {
        margin-top: 10px;
    }

    .std-footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        align-items: end;
        column-gap: 10px;
        width: 100%;
        font-size: var(--std-font-size-small);
        padding: 5px 0;
        box-sizing: border-box;
    }

    .std-footer-left {
        text-align: left;
    }

    .std-footer-center {
        text-align: center;
    }

    .std-footer-right {
        text-align: right;
        line-height: 1.2;
    }

    .no-print {
        display: block;
    }

    .print-hide-row,
    .tdr-source-row-off {
        display: none !important;
    }

    @media (max-width: 900px) {
        .std-meta-grid,
        .std-ndt-grid,
        .std-instruction-row,
        

        .std-meta-row,
        .std-meta-row--right {
            grid-template-columns: 140px minmax(0, 1fr);
        }

        .std-manual-ref-label {
            text-align: left;
        }
    }

    @media print {
        @page {
            size: letter;
            margin: var(--print-page-margin);
        }

        html,
        body {
            height: auto;
            min-height: auto;
            width: auto;
            max-width: none;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .no-print {
            display: none !important;
        }

        .container-fluid.std-sheet-container {
            height: auto !important;
            max-height: none !important;
        }

        .container-fluid.std-sheet-container.tdr-primary-sheet,
        .container-fluid.std-sheet-container.dynamic-page-wrapper,
        .container-fluid.std-sheet-container {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            box-sizing: border-box;
            zoom: var(--print-user-scale);
        }

        .container-fluid.std-sheet-container {
            break-after: page !important;
            page-break-after: always !important;
        }

        .container-fluid.std-sheet-container:last-of-type {
            break-after: auto !important;
            page-break-after: auto !important;
        }

        .container-fluid.std-sheet-container + .container-fluid.std-sheet-container {
            margin-top: 0 !important;
        }

        .dynamic-page-wrapper {
            break-before: page !important;
            page-break-before: always !important;
            display: block !important;
        }

        .tdr-print-force-page-end {
            page-break-after: always !important;
            break-after: page !important;
        }

        .std-page .std-header,
        .std-page .std-table {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .std-page {
            min-height: calc(279.4mm - (var(--print-page-margin) * 2));
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-sizing: border-box;
        }

        .page-rows-container {
            flex: 1 1 auto;
        }

        .std-grid-row > .std-cell {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .std-grid-row--header > .std-cell {
            min-height: 34px;
        }

        .std-table-summary {
            margin-top: 5px;
            margin-bottom: 3px;
        }

        .std-header {
            gap: 9px;
        }

        .std-header-top {
            grid-template-columns: 168px minmax(0, 1fr);
            column-gap: 8px;
        }

        .std-header-logo {
            width: 160px;
        }

        .std-header-title {
            font-size: 18px;
        }

        .std-header-title--ndt {
            font-size: 26px;
        }

        .std-header-title--cad {
            font-size: 22px;
        }

        .std-meta-grid {
            grid-template-columns: minmax(0, 57%) minmax(0, 43%);
            column-gap: 18px;
            row-gap: 8px;
        }

        .std-meta-row {
            grid-template-columns: 118px minmax(0, 1fr);
            column-gap: 8px;
            min-height: 26px;
        }

        .std-meta-row--right {
            grid-template-columns: 74px minmax(0, 1fr);
        }

        .std-meta-label {
            font-size: 12px;
            line-height: 1.05;
        }

        .std-meta-value {
            min-height: 22px;
            padding: 0 4px 2px 4px;
            font-size: var(--std-header-data-font-size);
            line-height: 1.05;
        }

        .std-component-name {
            font-size: 11px !important;
            line-height: 1.05;
        }

        .std-ndt-grid {
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.15fr) minmax(0, 1.35fr);
            gap: 12px;
        }

        .std-ndt-grid--aligned {
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.15fr) minmax(0, 1.35fr);
            grid-template-rows: auto 22px auto 22px auto 42px;
            column-gap: 12px;
            row-gap: 3px;
        }

        .std-ndt-title {
            font-size: 13px;
            line-height: 1.05;
        }

        .std-ndt-line {
            grid-template-columns: 22px minmax(0, 1fr);
            column-gap: 6px;
            min-height: 22px;
        }

        .std-ndt-index,
        .std-ndt-value {
            font-size: 11px;
            line-height: 1.05;
        }

        .std-manual-ref-box {
            min-height: 42px;
            padding: 3px 8px;
        }

        .std-instruction-row--cad {
            grid-template-columns: minmax(0, 1fr) 96px 180px;
            column-gap: 4px;
            align-items: center;
        }

        .std-instruction-row--cad .std-instruction-text {
            white-space: nowrap;
            font-size: 10px;
            line-height: 1.05;
            overflow: hidden;
            text-overflow: clip;
        }

        .std-instruction-row--cad .std-manual-ref-label {
            font-size: 12px;
            white-space: nowrap;
        }

        .std-footer {
            position: static !important;
            width: 100%;
            max-width: none;
            margin-top: auto;
            background: #fff;
            padding: 3px 0 0;
            box-sizing: border-box;
        }
    }
</style>
