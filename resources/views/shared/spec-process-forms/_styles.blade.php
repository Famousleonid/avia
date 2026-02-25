{{-- CSS variables and styles for Print Settings (specProcessForm, specProcessFormEmp) --}}
<style>
:root {
    --spec-process-row-height: 22px;
    --spec-process-name-font-size: 11px;
    --spec-component-description-font-size: 11px;
    --spec-component-part-no-font-size: 11px;
    --spec-component-serial-no-font-size: 11px;
}
.spec-process-name-cell .spec-process-name-inner { height: var(--spec-process-row-height); min-height: 1em; }
.spec-process-row-cell .spec-process-row-inner {
    height: var(--spec-process-row-height);
    width: 30px;
}
.spec-process-empty-divider {
    position: absolute;
    left: 29px;
    top: 0;
    bottom: 0;
    width: 1px;
    border-left: 1px solid black;
}
.spec-process-table-body,
.spec-process-table-body .spec-process-name-cell,
.spec-process-table-body .spec-process-row-cell,
.spec-process-table-body .spec-process-row-inner,
.spec-process-table-body .spec-process-empty-row {
    font-size: var(--spec-process-name-font-size);
}
.spec-component-description { font-size: var(--spec-component-description-font-size); }
.spec-component-part-no { font-size: var(--spec-component-part-no-font-size); }
.spec-component-serial-no { font-size: var(--spec-component-serial-no-font-size); }
.print-settings-modal .form-label { font-weight: 500; margin-bottom: 0.5rem; }
.print-settings-modal .form-control { margin-bottom: 1rem; }
</style>
