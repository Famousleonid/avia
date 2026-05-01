<style>

    .dir-page {
        --main-pane-bg: linear-gradient(135deg, #212529 0%, #2c3035 100%);
        --main-pane-soft-bg: rgba(255,255,255,0.03);
        --main-pane-head-bg: rgba(0, 0, 0, .12);
        --main-pane-border: rgba(13, 202, 240, .35);
        --main-pane-border-soft: rgba(108,117,125,.35);
        --main-pane-text: #f8f9fa;
        --main-pane-muted: rgba(248,249,250,.65);
        --main-pane-control-bg: #212529;
        --main-pane-control-text: #f8f9fa;
        --main-tab-text: #adb5bd;
        --main-tab-hover-text: #f8f9fa;
        --main-tab-active-text: #f8f9fa;
        --main-tab-active-bg: var(--main-pane-bg);
        --main-top-card-bg: #212529;
        --main-top-card-text: #f8f9fa;
        --main-bush-strip-bg: rgba(255,255,255,.04);
        --main-bush-strip-active-bg: rgba(13, 202, 240, .12);
        --main-bush-strip-text: #e9ecef;
    }

    html[data-bs-theme="light"] .dir-page {
        --main-pane-bg: linear-gradient(135deg, #f8f9fa 0%, #e4e9ee 100%);
        --main-pane-soft-bg: rgba(0,0,0,0.025);
        --main-pane-head-bg: rgba(255, 255, 255, .58);
        --main-pane-border: rgba(13, 110, 253, .28);
        --main-pane-border-soft: rgba(33,37,41,.18);
        --main-pane-text: #212529;
        --main-pane-muted: rgba(33,37,41,.62);
        --main-pane-control-bg: #ffffff;
        --main-pane-control-text: #212529;
        --main-tab-text: #495057;
        --main-tab-hover-text: #0d6efd;
        --main-tab-active-text: #212529;
        --main-tab-active-bg: linear-gradient(135deg, #ffffff 0%, #edf1f5 100%);
        --main-top-card-bg: rgba(255,255,255,.82);
        --main-top-card-text: #212529;
        --main-bush-strip-bg: rgba(255,255,255,.72);
        --main-bush-strip-active-bg: rgba(13, 110, 253, .10);
        --main-bush-strip-text: #212529;
    }

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
        border-width: 1px !important;
        border-color: #fff !important;
        box-shadow: 0 0 8px 3px rgba(255,255,255,0.6) !important;
    }
    .js-gt-btn.btn-outline-success.active  {
        border-width: 1px !important;
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

    /*.flatpickr-input.fp-alt {*/
    /*    width: 115px !important;     !* фиксируем ширину *!*/
    /*    min-width: 115px !important;*/
    /*    max-width: 115px !important;*/
    /*}*/

    .finish-input.noedit,
    .finish-input.noedit + .flatpickr-input {
        pointer-events: none !important;
        background-color: var(--main-pane-soft-bg) !important;
        color: var(--main-pane-muted) !important;
    }


    input::placeholder,
    .flatpickr-input::placeholder {
        color: #6c757d;
        opacity: 1;
    }

    .gradient-pane,
    .gradient-table,
    .gradient-top {
        background: var(--main-pane-bg);
        color: var(--main-pane-text);
    }

    .gradient-table {
        border-radius: .5rem;
        overflow: hidden;
    }

    .vh-layout .card.bg-dark,
    .vh-layout .list-group-item.bg-transparent,
    .vh-layout .modal-content.bg-dark {
        background-color: var(--main-top-card-bg) !important;
        color: var(--main-top-card-text) !important;
    }

    .vh-layout .text-light,
    .vh-layout .text-white {
        color: var(--main-pane-text) !important;
    }

    html[data-bs-theme="light"] .vh-layout .text-white-50 {
        color: rgba(33,37,41,.55) !important;
    }

    html[data-bs-theme="light"] .vh-layout .border-secondary {
        border-color: var(--main-pane-border-soft) !important;
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

    .main-tabs-shell {
        --main-tab-panel-width: 100%;
        --main-date-col-width: 7.75rem;
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        position: relative;
        margin-top: 5px;
        padding: 0;
        border: 0;
        border-radius: 0;
        overflow: hidden;
    }

    .main-tabs-shell > .bottom-row {
        margin-top: -1px;
    }

    .main-tabs-nav {
        flex: 0 0 auto;
        display: flex;
        align-items: flex-end;
        gap: .25rem;
        min-width: 0;
        padding-top: .35rem;
        padding-left: .45rem;
        padding-right: 8.75rem;
        margin-bottom: -1px;
        background: transparent;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .main-tabs-toolbar {
        position: absolute;
        top: .45rem;
        right: .55rem;
        z-index: 20;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        min-height: 0;
        padding: 0;
        color: var(--main-tab-text);
    }

    .main-tab-btn {
        flex: 0 0 auto;
        min-height: 2rem;
        padding: .32rem .7rem;
        border: 1px solid transparent;
        border-bottom: 0;
        border-top: 1px solid transparent;
        border-radius: 6px 6px 0 0;
        background: transparent;
        color: #adb5bd;
        font-size: .84rem;
        line-height: 1.15;
        white-space: nowrap;
    }

    .main-tab-btn:hover {
        color: var(--main-tab-hover-text);
        border-top-color: rgba(13, 202, 240, .45);
    }

    html[data-main-tab="overview"] .main-tab-btn[data-main-tab="overview"],
    html[data-main-tab="tasks"] .main-tab-btn[data-main-tab="tasks"],
    html[data-main-tab="std"] .main-tab-btn[data-main-tab="std"],
    html[data-main-tab="parts"] .main-tab-btn[data-main-tab="parts"],
    html[data-main-tab="bushings"] .main-tab-btn[data-main-tab="bushings"] {
        position: relative;
        z-index: 2;
        margin-bottom: -1px;
        color: var(--main-tab-active-text);
        border-color: rgba(13, 202, 240, .75);
        border-bottom-color: transparent;
        background: var(--main-tab-active-bg);
        box-shadow: 0 -2px 8px rgba(0, 0, 0, .45), 0 0 12px rgba(0, 0, 0, .35);
    }

    html[data-main-tab="overview"] .main-tab-btn[data-main-tab="overview"]::after,
    html[data-main-tab="tasks"] .main-tab-btn[data-main-tab="tasks"]::after,
    html[data-main-tab="std"] .main-tab-btn[data-main-tab="std"]::after,
    html[data-main-tab="parts"] .main-tab-btn[data-main-tab="parts"]::after,
    html[data-main-tab="bushings"] .main-tab-btn[data-main-tab="bushings"]::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2px;
        background: var(--main-tab-active-bg);
    }

    .main-right-title-std,
    .main-right-title-parts {
        display: none;
    }

    html[data-main-tab="tasks"] .main-tabs-shell .bottom-col.right,
    html[data-main-tab="bushings"] .main-tabs-shell .bottom-col.right,
    html[data-main-tab="std"] .main-tabs-shell .bottom-col.left,
    html[data-main-tab="parts"] .main-tabs-shell .bottom-col.left {
        display: none !important;
    }

    html[data-main-tab="tasks"] .main-tabs-shell .bottom-col.left,
    html[data-main-tab="bushings"] .main-tabs-shell .bottom-col.left,
    html[data-main-tab="std"] .main-tabs-shell .bottom-col.right,
    html[data-main-tab="parts"] .main-tabs-shell .bottom-col.right {
        flex: 0 0 var(--main-tab-panel-width) !important;
        width: var(--main-tab-panel-width);
        max-width: 100%;
    }

    html[data-main-tab="tasks"] .main-tabs-shell .wo-bushings-box {
        display: none !important;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .main-tasks-notes-window {
        display: none !important;
    }

    html[data-main-tab="std"] .main-tabs-shell .main-parts-processes-block,
    html[data-main-tab="parts"] .main-tabs-shell .main-std-processes-block {
        display: none !important;
    }

    html[data-main-tab="std"] .main-tabs-shell .main-right-title-overview,
    html[data-main-tab="parts"] .main-tabs-shell .main-right-title-overview {
        display: none !important;
    }

    html[data-main-tab="std"] .main-tabs-shell .main-right-title-std,
    html[data-main-tab="parts"] .main-tabs-shell .main-right-title-parts {
        display: inline-flex;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .main-gt-scroll-area,
    html[data-main-tab="bushings"] .main-tabs-shell .main-gt-scroll-inner,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-box {
        flex: 1 1 0% !important;
        min-height: 0;
        height: 100%;
    }

    html[data-main-tab="tasks"] .main-tabs-shell .bottom-row,
    html[data-main-tab="bushings"] .main-tabs-shell .bottom-row,
    html[data-main-tab="std"] .main-tabs-shell .bottom-row,
    html[data-main-tab="parts"] .main-tabs-shell .bottom-row {
        gap: 0;
    }

    .main-parts-processes-block {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .main-parts-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }

    .main-tab-resize-handle {
        display: none;
        position: absolute;
        top: 2.35rem;
        bottom: .35rem;
        left: calc(min(var(--main-tab-panel-width), 100%) - 18px);
        width: 16px;
        z-index: 20;
        cursor: ew-resize;
        touch-action: none;
    }

    html:not([data-main-tab="overview"]) .main-tabs-shell .main-tab-resize-handle {
        display: block;
    }

    .main-tab-resize-handle::before,
    .main-tab-resize-handle::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 2px;
        height: 34px;
        border-radius: 2px;
        background: rgba(13, 202, 240, .9);
        transform: translateY(-50%);
        box-shadow: 0 0 4px rgba(0, 0, 0, .65);
    }

    .main-tab-resize-handle::before {
        left: 4px;
    }

    .main-tab-resize-handle::after {
        right: 4px;
    }

    .main-tab-resize-handle:hover::before,
    .main-tab-resize-handle:hover::after,
    .main-tabs-shell.is-resizing-main-tab .main-tab-resize-handle::before,
    .main-tabs-shell.is-resizing-main-tab .main-tab-resize-handle::after {
        background: #9eeaf9;
    }

    .bottom-col {
        border: 0 !important;
        border-radius: .5rem;
        padding: 0 !important;
        overflow: hidden;

        display: flex;
        flex-direction: column;
        min-height: 0;

        /* равные колонки и разрешить сжиматься (таблица не раздувает ширину) */
        flex: 1 1 0 !important;
        min-width: 0 !important;
    }

    .main-section-window {
        flex: 1 1 0%;
        min-height: 0;
        overflow: auto;
        background: var(--main-pane-soft-bg);
    }

    .main-std-processes-block.main-section-window {
        flex: 0 0 auto;
        min-height: auto;
        overflow: visible;
    }

    .main-parts-processes-block.main-section-window {
        flex: 1 1 0%;
    }

    .main-section-head {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .35rem .5rem;
        border-bottom: 1px solid var(--main-pane-border);
        background: var(--main-pane-head-bg);
    }

    /* =========================================================
    3) Left window (Tasks)
    ========================================================= */
    .left-pane {
        flex: 1 1 0%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: .75rem;
        overflow: hidden;
    }

    .main-left-loading{
        min-height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main-left-loading-dots{
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .main-left-loading-dot{
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: rgba(173, 181, 189, .9);
        animation: mainLeftDotWave 1s infinite ease-in-out;
    }

    .main-left-loading-dot:nth-child(2){ animation-delay: .12s; }
    .main-left-loading-dot:nth-child(3){ animation-delay: .24s; }

    @keyframes mainLeftDotWave{
        0%, 80%, 100% { transform: translateY(0); opacity: .45; }
        40% { transform: translateY(-4px); opacity: 1; }
    }

    /* flex-basis 0% — иначе блок не сжимается ниже высоты контента и не появляется overflow/scroll */
    .js-gt-container,
    .js-gt-pane {
        flex: 1 1 0% !important;
        min-height: 0;
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
        background: var(--main-pane-head-bg);
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
    .tasks-table col.col-start {width: var(--main-date-col-width);}
    .tasks-table col.col-finish {width: var(--main-date-col-width);}
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

    .main-std-processes-block .dir-table,
    .main-parts-processes-block .dir-table {
        table-layout: fixed;
    }

    .main-std-processes-block col.main-col-ignore { width: 2.25rem; }
    .main-std-processes-block col.main-col-tech { width: 7.25rem; }
    .main-std-processes-block col.main-col-name { width: auto; }
    .main-std-processes-block col.main-col-ro { width: 9rem; }
    .main-std-processes-block col.main-col-vendor { width: 10rem; }
    .main-parts-processes-block col.main-col-tech { width: 7.75rem; }
    .main-parts-processes-block col.main-col-name { width: auto; }
    .main-parts-processes-block col.main-col-ro { width: 9rem; }
    .main-parts-processes-block col.main-col-vendor { width: 10rem; }

    .main-std-processes-block col.main-col-date,
    .main-parts-processes-block col.main-col-date {
        width: var(--main-date-col-width);
    }

    .main-std-processes-block .main-date-cell,
    .main-parts-processes-block .main-date-cell {
        width: var(--main-date-col-width);
        min-width: var(--main-date-col-width);
        max-width: var(--main-date-col-width);
    }

    .main-std-processes-block .main-date-cell form,
    .main-parts-processes-block .main-date-cell form {
        margin: 0;
        min-width: 0;
    }

    .tasks-table .fp-alt-wrap,
    .main-std-processes-block .fp-alt-wrap,
    .main-parts-processes-block .fp-alt-wrap {
        width: 100%;
        min-width: 0;
    }

    /* =========================================================
    4) Inputs: calendar icon + “has finish” state
    ========================================================= */
    .finish-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .2rem center;
        background-size: 1rem 1rem;
        padding-right: 3.5rem;
    }

    .finish-input.has-finish {
        background-color: rgba(25, 135, 84, .1);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23198754' viewBox='0 0 16 16'%3E%3Cpath d='M13.485 1.929a.75.75 0 010 1.06L6.818 9.657a.75.75 0 01-1.06 0L2.515 6.414a.75.75 0 111.06-1.06L6 7.778l6.425-6.425a.75.75 0 011.06 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem, 60%;
        background-size: 1rem 1rem;
        padding-right: 3.5rem;
    }

    /* Flatpickr: hide extra calendar trigger when the field already shows the green “has date” check */
    .tasks-table .finish-input,
    .tasks-table .fp-alt,
    .main-std-processes-block .finish-input,
    .main-std-processes-block .fp-alt,
    .main-parts-processes-block .finish-input,
    .main-parts-processes-block .fp-alt,
    .wo-bushings-table .wo-bush-col-dt .finish-input,
    .wo-bushings-table .wo-bush-col-dt .fp-alt {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box;
        font-size: .78rem;
        font-variant-numeric: tabular-nums;
        padding-left: .35rem !important;
        padding-right: 1.75rem !important;
    }

    .fp-alt-wrap:has(.finish-input.has-finish) .fp-cal-btn {
        display: none !important;
    }

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
        color: var(--main-pane-text);
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

    /* =========================================================
    Top compact header
    ========================================================= */
    .dir-top-compact {
        padding: .35rem .55rem !important;
        gap: .15rem;
    }

    .dir-top-actions h6 {
        font-size: 1rem;
    }

    .dir-top-actions .badge {
        font-size: .72rem;
        padding: .28rem .42rem;
    }

    .dir-top-actions .btn {
        line-height: 1;
    }

    .dir-top-square-btn {
        width: 30px;
        height: 30px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .dir-top-square-btn i {
        font-size: .95rem;
        line-height: 1;
    }

    .dir-top-help {
        font-size: 1rem;
        line-height: 1;
        color: #8fd4ff;
    }

    .dir-top-desc {
        max-width: 340px;
        font-size: .78rem;
        color: #adb5bd;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .dir-top-info-block {
        background: var(--main-pane-soft-bg);
    }

    .dir-top-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .2rem .6rem;
    }

    .dir-top-cell {
        min-width: 0;
    }

    .dir-top-line {
        display: flex;
        align-items: baseline;
        gap: .2rem;
        min-width: 0;
        font-size: .8rem;
        line-height: 1.1rem;
        white-space: nowrap;
        color: var(--main-pane-text);
        margin-bottom: .06rem;
    }

    .dir-top-line:last-child {
        margin-bottom: 0;
    }

    .dir-top-k {
        flex: 0 0 auto;
        color: #8fd4ff;
        font-weight: 500;
    }

    .dir-top-v {
        flex: 1 1 auto;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--main-pane-text);
        font-weight: 400;
    }

    .dir-top-v-fit {
        flex: 0 1 auto;
    }

    .dir-top-parts-btn {
        --bs-btn-padding-y: .06rem;
        --bs-btn-padding-x: .45rem;
        --bs-btn-font-size: .7rem;
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
        background: var(--main-pane-soft-bg);
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
        background-color: var(--main-pane-soft-bg) !important;
        color: var(--main-pane-muted) !important;
        cursor: not-allowed;
    }

    .finish-input.is-ignored::placeholder {
        color: #6c757d !important;
    }

    /* если input disabled */
    .finish-input:disabled {
        background-color: var(--main-pane-soft-bg) !important;
        color: var(--main-pane-muted) !important;
        opacity: 1 !important; /* Bootstrap по умолчанию делает слишком бледным */
    }
    .is-ignored {
        opacity: 0.5;
    }

    .is-ignored-row {
        opacity: 0.5;
    }

    .row-ignored > td {
        opacity: 0.5;
    }

    .row-ignored > td.task-ignore-cell {
        opacity: 1;
    }

    .task-ignore-cell .form-check-input {
        cursor: pointer !important;
        opacity: 1 !important;
    }

    .row-ignored input,
    .row-ignored .form-control,
    .row-ignored .finish-input,
    .row-ignored .flatpickr-input,
    .row-ignored .fp-alt {
        cursor: not-allowed !important;
    }

    /* For ignored task rows: keep hover active on disabled/readonly date inputs
       so the "not-allowed" cursor is visible (red prohibition icon). */
    .row-ignored .js-start,
    .row-ignored .js-finish,
    .row-ignored .fp-alt {
        pointer-events: auto !important;
    }

    .row-ignored .task-ignore-cell .form-check-input {
        cursor: pointer !important;
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

    .std-ignored-row .form-control:disabled,
    .std-ignored-row .flatpickr-input,
    .std-ignored-row .flatpickr-input[readonly] {
        cursor: not-allowed !important;
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

    .bottom-col.left,
    .bottom-col.right {
        font-size: .85rem;
    }

    html:not([data-main-tab="overview"]) .main-tabs-shell .bottom-col.left,
    html:not([data-main-tab="overview"]) .main-tabs-shell .bottom-col.right {
        font-size: 1rem;
    }

    .tasks-table td,
    .tasks-table th {
        padding: 4px 6px !important;
        line-height: 1.25;
        vertical-align: middle;
    }

    .main-std-processes-block .dir-table td,
    .main-std-processes-block .dir-table th,
    .main-parts-processes-block .dir-table td,
    .main-parts-processes-block .dir-table th {
        line-height: 1.28;
        padding-top: .32rem;
        padding-bottom: .32rem;
        vertical-align: middle;
    }

    .tasks-table .lock-icon {
        font-size: .85rem;
    }

    .main-tasks-notes-window {
        flex: 0 1 auto;
        max-height: 50%;
        min-height: 0;
        overflow: auto;
        background: var(--main-pane-soft-bg);
    }

    .main-tasks-notes-window .main-gt-buttons {
        padding: .45rem .5rem .5rem;
        border-bottom: 1px solid var(--main-pane-border);
    }

    .main-tasks-notes-window .main-tasks-block {
        flex: 0 0 auto;
        overflow: visible;
    }

    .wo-notes-box{
        background: var(--main-pane-soft-bg);
    }

    .main-tasks-notes-window .wo-notes-box {
        border-top: 1px solid var(--main-pane-border);
        border-radius: 0;
    }

    .wo-notes-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding: 4px 8px;            /* узкая шапка */
        border-bottom: 1px solid rgba(108,117,125,.35);
    }

    .wo-notes-title{
        font-size: .85rem;
        color: #0dcaf0; /* text-info */
        line-height: 1;
        margin: 0;
    }

    .wo-notes-right{
        display:flex;
        align-items:center;
        gap: 8px;
    }

    .wo-notes-hint{
        font-size: .75rem;
        color: var(--main-pane-muted);
        line-height: 1;
        white-space: nowrap;
    }

    .wo-notes-textarea{
        font-size: .85rem;
        min-height: 8.5rem;
        resize: vertical;
        background-color: var(--main-pane-control-bg) !important;
        color: var(--main-pane-control-text) !important;
        border-color: var(--main-pane-border-soft) !important;
    }

    /* Левая колонка: высоту ограничивает родитель; scroll живет внутри окон Tasks/Notes и Bushing отдельно. */
    .main-gt-scroll-area{
        flex: 1 1 0%;
        border: 0 !important;
        border-radius: 0 !important;
        min-height: 0;
        max-height: 100%;
        overflow: hidden;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .main-gt-scroll-inner{
        height: 100%;
        min-height: 0;
        box-sizing: border-box;
    }

    .wo-bushings-box{
        background: var(--main-pane-soft-bg);
    }

    .wo-bushings-list{
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }

    /* WO bushing: одна «карусель» из 7 полос (аккордеон), без вложенных полос */
    .wo-bush-strip-accordion{
        border: 1px solid var(--main-pane-border-soft);
        border-radius: .35rem;
        overflow: hidden;
        min-width: 0;
    }
    .wo-bush-strip-accordion .accordion-body{
        min-width: 0;
        max-width: 100%;
    }

    .wo-bush-strip-accordion .wo-bush-strip-item{
        margin: 0;
        border: 0 !important;
        border-bottom: 1px solid var(--main-pane-border-soft) !important;
        border-radius: 0 !important;
    }

    .wo-bush-strip-accordion .wo-bush-strip-item:last-child{
        border-bottom: 0 !important;
    }

    .wo-bush-strip-btn{
        width: 100%;
        display: flex !important;
        align-items: center !important;
        background: var(--main-bush-strip-bg) !important;
        color: var(--main-bush-strip-text) !important;
        border: 0 !important;
        box-shadow: none !important;
        min-height: 2.5rem;
    }

    .wo-bush-strip-btn:not(.collapsed){
        color: #9eeaf9 !important;
        background: var(--main-bush-strip-active-bg) !important;
    }

    html[data-bs-theme="light"] .wo-bush-strip-btn:not(.collapsed) {
        color: #0d6efd !important;
    }

    .wo-bush-strip-btn::after{
        flex-shrink: 0;
        filter: invert(80%) sepia(8%) saturate(355%) hue-rotate(170deg) brightness(92%) contrast(86%);
    }

    .wo-bush-strip-btn-inner{
        min-width: 0;
    }

    .wo-bush-strip-title{
        flex: 1 1 auto;
        min-width: 0;
    }

    /* Счётчик справа, одна вертикальная линия по правому краю полосы */
    .wo-bush-strip-count{
        flex: 0 0 auto;
        min-width: 3.75rem;
        text-align: right;
        font-size: .8125rem;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
        background: transparent !important;
        border: 0 !important;
        padding: 0 !important;
    }

    .wo-bush-strip-count--sm{
        min-width: 3.25rem;
        font-size: .75rem;
    }

    .wo-bush-strip-count .wo-bush-strip-count-a{
        color: var(--main-pane-text);
    }

    .wo-bush-strip-count .wo-bush-strip-count-sep{
        color: var(--main-pane-muted);
        padding: 0 .1rem;
    }

    .wo-bush-strip-count .wo-bush-strip-count-b{
        color: var(--main-pane-muted);
    }

    .wo-bush-strip-count--done .wo-bush-strip-count-a,
    .wo-bush-strip-count--done .wo-bush-strip-count-sep,
    .wo-bush-strip-count--done .wo-bush-strip-count-b{
        color: #2ea043 !important;
    }

    .wo-bush-process-block:last-child{
        margin-bottom: 0 !important;
    }

    /* WO bushing: таблица целиком в ширину колонки, без уезда за край (flex + fixed layout) */
    .wo-bush-process-block{
        min-width: 0;
        max-width: 100%;
    }
    .wo-bush-process-block .table-responsive{
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
    }
    .wo-bushings-table{
        table-layout: fixed;
        width: 100%;
        max-width: 100%;
    }
    .wo-bush-batch-nested{
        table-layout: fixed;
        width: 100%;
        max-width: 100%;
    }

    /*
     * Доли колонок основной таблицы (сумма 100%). При узкой колонке всё сжимается пропорционально.
     */
    .wo-bushings-table th,
    .wo-bushings-table td{
        vertical-align: middle;
        min-width: 0;
        box-sizing: border-box;
    }
    .wo-bushings-table .wo-bush-col-part{ width: 8.5rem; }
    .wo-bushings-table .wo-bush-col-ipl{ width: 4.25rem; }
    .wo-bushings-table .wo-bush-col-process{
        width: auto;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .wo-bushings-table .wo-bush-col-qty{
        width: 3.25rem;
        white-space: nowrap;
        text-align: center;
    }
    .wo-bushings-table .wo-bush-col-ro{
        width: 8.5rem;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .wo-bushings-table .wo-bush-col-vendor{
        width: 10rem;
    }
    .wo-bushings-table .wo-bush-col-dt{
        width: var(--main-date-col-width);
        min-width: var(--main-date-col-width);
        max-width: var(--main-date-col-width);
    }

    .wo-bushings-table .wo-bush-col-part,
    .wo-bushings-table .wo-bush-col-ipl{
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wo-bushings-table tr.wo-bush-batch-row > td.wo-bush-batch-toggle{
        white-space: normal;
        word-break: break-word;
    }
    .wo-bushings-table .wo-bush-col-ro .form-control,
    .wo-bushings-table .wo-bush-col-vendor .form-select{
        max-width: 100%;
        min-width: 0;
    }

    .wo-bushings-table .wo-bush-col-dt form{
        margin: 0;
        min-width: 0;
    }
    .wo-bushings-table .wo-bush-col-dt .finish-input,
    .wo-bushings-table .wo-bush-col-dt .fp-alt{
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box;
        font-size: .78rem;
        padding-left: .35rem !important;
        padding-right: 1.75rem !important;
    }
    .wo-bushings-table .wo-bush-col-dt .fp-alt-wrap{
        width: 100%;
        min-width: 0;
    }

    /* Вложенная таблица batch */
    .wo-bush-batch-nested .wo-bush-col-part{ width: 20%; }
    .wo-bush-batch-nested .wo-bush-col-ipl{ width: 15%; }
    .wo-bush-batch-nested .wo-bush-col-process{width: 50%; white-space: normal; word-wrap: break-word; overflow-wrap: anywhere;}
    .wo-bush-batch-nested .wo-bush-col-qty{ width: 5%;  }
    .wo-bush-batch-nested th,
    .wo-bush-batch-nested td{min-width: 0;}

    /* Вложенная таблица batch: одинаковый зазор со всех сторон (не только сверху) */
    .wo-bush-batch-nested-wrap{
        box-sizing: border-box;
        padding-top: 2px;
        padding-right: 2px;
        padding-bottom: 2px;
        padding-left: 2px;
    }

    /* WO bushing: после раскрытия аккордеона — шапка таблицы мельче, не жирная; ячейки px-1 (0.25rem) */
    .wo-bush-strip-accordion .accordion-body .wo-bushings-table thead th {
        font-weight: 400;
        font-size: 0.7rem;
        line-height: 1.25;
        padding: 0.25rem !important;
    }
    .wo-bush-strip-accordion .accordion-body .wo-bushings-table tbody td,
    .wo-bush-strip-accordion .accordion-body .wo-bushings-table tbody th {
        padding: 0.25rem !important;
    }
    .wo-bush-strip-accordion .accordion-body .wo-bush-batch-nested tbody td {
        padding: 0.25rem !important;
    }

    /* ~1280: плотнее только основная таблица (одиночные строки без batch-группы, шапка, строка BATCH).
       Раскрытая .wo-bush-batch-nested — как при большом разрешении (не трогаем). */
    @media (max-width: 1280px) {
        .wo-bush-strip-accordion .accordion-body {
            padding: 0.35rem 0.4rem !important;
        }
        .wo-bush-strip-accordion .accordion-body .wo-bush-process-block {
            margin-bottom: 0.5rem !important;
        }
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table {
            font-size: 0.68rem;
        }
        /* Вложенная batch не наследует ужимание шрифта основной таблицы */
        .wo-bush-strip-accordion .accordion-body .wo-bush-batch-nested {
            font-size: 0.875rem;
        }
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > thead > tr > th {
            font-size: 0.62rem;
            padding: 0.12rem 0.15rem !important;
            line-height: 1.15;
        }
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr:not(.collapse) > td,
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr:not(.collapse) > th {
            padding: 0.12rem 0.15rem !important;
            line-height: 1.2;
        }
        /* Rep order — компактнее; Sent/Return как при большом разрешении */
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr > td.wo-bush-col-ro .form-control-sm {
            font-size: 0.65rem;
            padding: 0.08rem 0.2rem;
            min-height: 1.45rem;
            line-height: 1.2;
        }
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr > td.wo-bush-col-dt .finish-input,
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr > td.wo-bush-col-dt .fp-alt {
            font-size: .78rem !important;
            padding-left: .35rem !important;
            padding-right: 1.75rem !important;
            min-height: unset !important;
        }
        /* Строка BATCH */
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr.wo-bush-batch-row > td.wo-bush-batch-toggle,
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr.wo-bush-batch-row > td.wo-bush-col-qty {
            font-size: 0.88rem;
            line-height: 1.4;
        }
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr.wo-bush-batch-row > td.wo-bush-batch-toggle .fw-bold {
            font-size: 0.9rem;
        }
        /* Process: ellipsis только в строках основной таблицы (не во вложенной batch) */
        .wo-bush-strip-accordion .accordion-body table.wo-bushings-table > tbody > tr > td.wo-bush-col-process {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }

    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bush-batch-nested {
        font-size: .95rem !important;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table thead th {
        font-size: .85rem !important;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table tbody td,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table tbody th,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bush-batch-nested tbody td,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .small {
        font-size: .9rem !important;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .form-control-sm,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .form-select-sm,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .finish-input,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .fp-alt {
        font-size: .875rem !important;
    }

    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .wo-bush-col-dt .finish-input,
    html[data-main-tab="bushings"] .main-tabs-shell .wo-bushings-table .wo-bush-col-dt .fp-alt {
        font-size: .78rem !important;
    }

</style>
