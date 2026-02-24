{{-- Общие стили для форм процессов --}}
<style>
body { margin: 0; padding: 0; font-family: "Times New Roman", serif; }
.container-fluid {
    max-width: var(--container-max-width, 960px);
    width: 100% !important;
    height: 98%;
    padding: var(--container-padding, 5px);
    margin-left: var(--container-margin-left, 10px);
    margin-right: var(--container-margin-right, 10px);
    position: relative;
}
@media print {
    @page { size: letter; margin: var(--print-page-margin, 1mm); }
    html, body { height: var(--print-body-height, 99%); width: var(--print-body-width, 98%); margin-left: var(--print-body-margin-left, 2px); padding: 0; }
    .parent { max-width: 100% !important; width: 100% !important; grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    table, h1, p { page-break-inside: avoid; }
    .no-print { display: none; }
    .form-wrapper footer, .container-fluid footer {
        position: relative; width: var(--print-footer-width, 800px); margin: 20px auto 0 auto;
        text-align: center; font-size: var(--print-footer-font-size, 10px);
        background-color: #fff; padding: var(--print-footer-padding, 3px 3px);
        page-break-before: avoid; page-break-inside: avoid;
    }
    .form-wrapper { position: relative; min-height: 100vh; page-break-after: always; }
    .form-page-block { page-break-inside: avoid; }
    .form-page-block-continuation { page-break-before: always; }
    .form-wrapper:last-child { page-break-after: auto; }
    .form-wrapper + .form-wrapper { page-break-before: always; }
    .data-page { page-break-inside: auto; }
    .ndt-data-container { page-break-inside: auto; }
    .print-page-break-after { page-break-after: always; }
    .table-header { page-break-after: avoid; }
    .container-fluid footer { page-break-before: avoid; page-break-inside: avoid; }
    .container { max-height: var(--print-container-max-height, 100vh); overflow: hidden; }
    .container-fluid { max-width: var(--print-container-max-width, 1200px); width: 100% !important; padding: var(--print-container-padding, 5px); }
    table { width: 100% !important; max-width: 100% !important; min-width: 100% !important; table-layout: auto !important; }
    table td, table th { padding: 2px 4px !important; }
    .print-hide-row { display: none !important; }
}
.print-hide-row { display: none !important; }
.border-all { border: 1px solid black; }
.border-l-t-b { border-left: 1px solid black; border-top: 1px solid black; border-bottom: 1px solid black; }
.border-l-b-r { border-left: 1px solid black; border-bottom: 1px solid black; border-right: 1px solid black; }
.border-l-t-r { border-left: 1px solid black; border-top: 1px solid black; border-right: 1px solid black; }
.border-l-b { border-left: 1px solid black; border-bottom: 1px solid black; }
.border-t-r { border-top: 1px solid black; border-right: 1px solid black; }
.border-t-b { border-top: 1px solid black; border-bottom: 1px solid black; }
.border-t-r-b { border-top: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; }
.border-r-b { border-right: 1px solid black; border-bottom: 1px solid black; }
.border-b { border-bottom: 1px solid black; }
.process-text-long { font-size: 0.8em; line-height: 1; letter-spacing: -0.5px; transform-origin: left; display: inline-block; vertical-align: middle; }
.description-text-long { font-size: 0.9rem; line-height: 1.1; letter-spacing: -0.3px; display: inline-block; vertical-align: top; }
.header-page .component-name-value { font-size: var(--component-name-font-size, 12px) !important; }
.header-page .component-name-value[data-long="1"] { line-height: 1.1; letter-spacing: -0.3px; }
.text-center { text-align: center; }
.text-black { color: #000; }
.fs-7 { font-size: 0.9rem; }
.fs-75 { font-size: 0.8rem; }
.fs-85 { font-size: 0.85rem; }
.fs-8 { font-size: 0.8rem; }
.ndt-data-container .data-row-ndt,
.parent ~ .table-header .row { font-size: var(--ndt-table-data-font-size, 9px); }
.data-page .data-row[data-stress="true"],
.table-header:has(+ .data-page .data-row[data-stress="true"]) .row { font-size: var(--stress-table-data-font-size, 9px); }
.data-page .data-row:not([data-stress="true"]),
.table-header:has(+ .data-page .data-row:not([data-stress="true"])) .row { font-size: var(--other-table-data-font-size, 9px); }
.details-row { display: flex; justify-content: center; align-items: center; height: 36px; }
/* ITEM No. — уменьшенный межстрочный интервал */
.ndt-data-container .data-row-ndt > div:first-child,
.data-page .data-row > div:first-child,
.table-header .row > div:first-child { line-height: 1.1; }
.details-cell { display: flex; justify-content: center; align-items: center; }
.parent { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; width: 100%; font-size: var(--ndt-process-font-size, 10px); }
.parent > div { padding: 0 5px; }
.ndt-process-row { min-height: 26px; line-height: 1; }
.ndt-process-row-tall { height: 30px; }
.ndt-process-row-cmm { height: 56px; }
.ndt-process-label { min-height: 26px; }
.print-settings-modal .form-label { font-weight: 500; margin-bottom: 0.5rem; }
.print-settings-modal .form-control { margin-bottom: 1rem; }
</style>
