<style>

    .sf {
        font-size: 12px;
    }

    .fs-8 {
        font-size: .8rem;
    }
    /* Убираем красный фон на hover */
    .js-gt-btn.btn-outline-danger:hover {
        background-color: transparent !important;
        color: #dc3545 !important;
    }

    /* Убираем красный фон на active */
    .js-gt-btn.btn-outline-danger.active,
    .js-gt-btn.btn-outline-danger:active,
    .js-gt-btn.btn-outline-danger:focus,
    .js-gt-btn.btn-outline-danger:focus-visible,
    .js-gt-btn.btn-outline-danger.show {
        background-color: transparent !important;
        color: #dc3545 !important;
        box-shadow: none !important;
    }
    /* и зеленый фон на active */
    .js-gt-btn.btn-outline-success:active,
    .js-gt-btn.btn-outline-success.active,
    .js-gt-btn.btn-outline-success:focus,
    .js-gt-btn.btn-outline-success:focus-visible,
    .js-gt-btn.btn-outline-success.show {
        background-color: transparent !important;
        box-shadow: none !important;
    }


    .js-gt-btn.btn-outline-success {
        border-color: #28d27d !important;   /* ярче */
        color: #28d27d !important;
    }
    .js-gt-btn.btn-outline-success:hover {
        border-color: #4fffa0 !important;   /* чуть ярче */
        color: #4fffa0 !important;
        background-color: transparent !important;
    }
    /* Белая рамка на active */
    .js-gt-btn.btn-outline-danger.active  {
        border-width: 3px !important;
        border-color: #fff !important;
        box-shadow: 0 0 8px 3px rgba(255,255,255,0.6) !important;
    }
    .js-gt-btn.btn-outline-success.active  {
        border-width: 3px !important;
        border-color: #fff !important;
        box-shadow: 0 0 8px 3px rgba(255,255,255,0.6) !important;
    }

    /* hover  */
    .js-gt-btn.btn-outline-danger:hover {
        color: #ff6b6b !important;

    }


    /* чтобы не прыгала высота при смене рамки */
    .js-gt-btn.btn-outline-danger {
        border-width: 2px !important;
    }

    /*.flatpickr-input.form-control.form-control-sm.w-100.fp-alt {*/
    /*    width: 115px !important;     !* фиксируем ширину *!*/
    /*    min-width: 115px !important;*/
    /*    max-width: 115px !important;*/
    /*}*/

    .finish-input.noedit,
    .finish-input.noedit + .flatpickr-input {
        pointer-events: none !important;
        background-color: rgba(255,255,255,.08) !important;
    }


    input::placeholder,
    .flatpickr-input::placeholder {
        color: #6c757d;
        opacity: 1;
    }

    .gradient-pane,
    .gradient-table,
    .gradient-top {
        background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
        color: #f8f9fa;
    }

    .gradient-table {
        border-radius: .5rem;
        overflow: hidden;
    }

    /* =========================================================
    1) Flatpickr visibility / stacking
    ========================================================= */
    body.fp-ready [data-fp] {
        opacity: 0;
    }

    .flatpickr-input[readonly] {
        opacity: 1 !important;
    }

    .flatpickr-calendar {
        z-index: 2000 !important;
    }

    .fp-alt,
    .finish-input.fp-alt {
        cursor: pointer;
    }

    /* =========================================================
    2) Main layout (card -> vh-layout -> top + bottom)
    ========================================================= */

    .card-body {
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .vh-layout {
        flex: 1 1 auto;
        height: calc(100vh - 80px);
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    /* ---------- Top window (fixed by content) ---------- */
    .top-pane {
        flex: 0 0 auto;
        border: 1px solid rgba(0, 0, 0, .125);
        border-radius: .5rem;
        padding: 5px;
        overflow: hidden;
    }

    /* ---------- Bottom area (fills remaining height) ---------- */
    .bottom-row {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        gap: .75rem;
        margin-top: 5px;
        overflow: hidden;
    }

    .bottom-col {
        border: 1px solid rgba(0, 0, 0, .125);
        border-radius: .5rem;
        padding: 1rem;
        overflow: auto;

        display: flex;
        flex-direction: column;
        min-height: 0;

        /* равные колонки и разрешить сжиматься (таблица не раздувает ширину) */
        flex: 1 1 0 !important;
        min-width: 0 !important;
    }

    /* =========================================================
    3) Left window (Tasks)
    ========================================================= */
    .left-pane {
        height: auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    /* Tasks table */
    .tasks-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
    }

    .tasks-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: rgba(0, 0, 0, .25);
    }

    .tasks-table th,
    .tasks-table td {
        vertical-align: middle !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* col widths (from your <colgroup>) */
    .tasks-table col.col-ignore {width: 30px;}
    .tasks-table col.col-tech {width: 10%;}
    .tasks-table col.col-start {width: 22%;}
    .tasks-table col.col-finish {width: 22%;}
    .tasks-table col.col-log {width: 50px;}
    .tasks-table col.col-task {width: auto;}


    /* Flatpickr inputs inside table cells */
    .tasks-table .fp-alt,
    .table.table-dark .fp-alt {
        height: calc(1.8125rem + 2px) !important;
        padding: .25rem .5rem !important;
        line-height: 1.2 !important;
    }

    /* avoid table row height jumps by forms */
    .tasks-table td form {
        margin: 0 !important;
    }

    /* =========================================================
    4) Inputs: calendar icon + “has finish” state
    ========================================================= */
    .finish-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .5rem center;
        background-size: 1rem 1rem;
        padding-right: 3.5rem;
    }

    .finish-input.has-finish {
        background-color: rgba(25, 135, 84, .1);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E"),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23198754' viewBox='0 0 16 16'%3E%3Cpath d='M13.485 1.929a.75.75 0 010 1.06L6.818 9.657a.75.75 0 01-1.06 0L2.515 6.414a.75.75 0 111.06-1.06L6 7.778l6.425-6.425a.75.75 0 011.06 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat, no-repeat;
        background-position: right .5rem center, right 2rem center;
        background-size: 1rem 1rem, 1rem 1rem;
    }

    /* =========================================================
    5) Small UI pieces
    ========================================================= */
    /*.select-task {*/
    /*    border: 0;*/
    /*    width: 100%;*/
    /*    text-align: left;*/
    /*    padding: .5rem .75rem;*/
    /*    background: transparent;*/
    /*    border-radius: .5rem;*/
    /*}*/

    /*.select-task:hover {*/
    /*    background: rgba(0, 123, 255, .15);*/
    /*    cursor: pointer;*/
    /*}*/

    #taskTabContent {
        max-height: 40vh;
        overflow: auto;
    }

    #taskPickerBtn .picked {
        max-width: 55%;
        font-size: .8rem;
        opacity: .95;
        text-align: right;
        direction: rtl;
        unicode-bidi: plaintext;
        color: var(--bs-info);
    }

    .task-cell {
        background: linear-gradient(90deg, rgba(0, 123, 255, .1), rgba(0, 200, 255, .05));
        border-radius: .25rem;
        padding: .25rem .5rem;
        font-size: .8rem;
        line-height: 1.2;
    }

    .task-cell .general-name {
        font-weight: 600;
        color: #0d6efd;
    }

    .task-cell .task-name {
        font-weight: 400;
        color: #333;
    }

    .task-col {
        font-size: .8rem;
        font-weight: 500;
        color: #f8f9fa;
    }

    .task-col .arrow {
        margin: 0 .25rem;
        color: #adb5bd;
    }

    .eqh-sm {
        height: calc(1.8125rem + 2px);
    }

    .is-valid {
        box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .25);
    }

    #taskPickerBtn.eqh {
        height: calc(1.8125rem + 2px);
    }

    .parts-line .text-info {
        width: auto !important;
        display: inline !important;
    }

    #addBtn.btn-success {
        background-color: var(--bs-success) !important;
        border-color: var(--bs-success) !important;
        color: #fff !important;
        border-width: 1px;
    }

    #addBtn.btn-success:focus {
        box-shadow: 0 0 0 .2rem rgba(25, 135, 84, .35);
    }

    #addBtn:not(:disabled) {
        opacity: 1;
    }

    .photo-thumbnail {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
    }

    .log-entry {
        font-size: .85rem;
    }

    .log-entry .log-meta {
        font-size: .75rem;
        color: #adb5bd;
    }

    .log-entry pre {
        white-space: pre-wrap;
        word-break: break-word;
        font-size: .75rem;
        background: rgba(0, 0, 0, .15);
        padding: .25rem .5rem;
        border-radius: .25rem;
    }

    /* =========================================================
    6) Save indicator (Repair order)
    ========================================================= */
    .auto-submit-order {
        position: relative;
    }

    .auto-submit-order .save-indicator {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: .9rem;
        color: #ffc107;
        pointer-events: none;
    }

    .tasks-table .form-check-input,
    .table.table-dark .form-check-input {
        margin-top: 0;
        flex: 0 0 auto;
    }

    /* Ignore finish state */
    .finish-input.is-ignored {
        background-color: rgba(0, 0, 0, .35) !important;
        color: #adb5bd !important;
        cursor: not-allowed;
    }

    .finish-input.is-ignored::placeholder {
        color: #6c757d !important;
    }

    /* если input disabled */
    .finish-input:disabled {
        background-color: rgba(0, 0, 0, .35) !important;
        opacity: 1 !important; /* Bootstrap по умолчанию делает слишком бледным */
    }
    .is-ignored {
        opacity: 0.5;
    }

    .is-ignored-row {
        opacity: 0.5;
    }

    .table .form-control.is-invalid,
    .table-dark .form-control.is-invalid,
    .form-control.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .15rem rgba(220,53,69,.25) !important;
        background-image: none !important; /* чтобы bootstrap иконку не рисовал */
    }

    .is-ignored input,
    .is-ignored .form-check-input {
        pointer-events: none;
    }

    .lock-icon {
        position: absolute;
        top: 50%;
        right: 6px;
        transform: translateY(-50%);
        font-size: 14px;
        cursor: help;
        opacity: 0.7;
    }
    .lock-icon:hover {
        opacity: 1;
    }

    .is-saved-field {
        border-color: rgba(25, 135, 84, 0.9) !important;
        box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.15) !important;
    }

</style>
