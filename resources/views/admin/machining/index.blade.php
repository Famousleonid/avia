@extends('admin.master')

@section('style')
    @include('admin.mains.partials.styles')
    <style>
        .machining-page-root {
            min-height: calc(100dvh - 10px);
            display: flex;
            flex-direction: column;
        }
        .machining-page-root .dir-panel {
            height: auto;
            max-height: calc(100dvh - 120px);
            min-width: 0;
        }
        .machining-table-scroll {
            max-height: calc(100dvh - 50px);
        }
        .machining-table-outer.machining-table-scroll {
            max-height: calc(100dvh - 50px);
        }
        .machining-header-toolbar #machiningTableSearch {
            width: 200px;
            max-width: 100%;
        }
        .machining-header-toolbar .machining-header-hide-closed {
            padding-bottom: 0.2rem;
        }
        /* Нижний блок lost parts — одна рамка (без вложенного .paint-page-bottom) */
        .machining-lost-fieldset {
            flex: 1 1 auto;
            min-height: 120px;
            margin: .75rem 0 0;
            min-width: 0;
            border: 1px solid var(--dir-border, rgba(255, 255, 255, .18));
            border-radius: var(--dir-radius-lg, .75rem);
            padding: .55rem .65rem .65rem;
            background: linear-gradient(180deg, rgba(0, 0, 0, .12), rgba(0, 0, 0, .42));
        }
        .machining-lost-fieldset .machining-lost-legend {
            float: none;
            width: auto;
            max-width: 100%;
            margin: -.85rem 0 .4rem -.1rem;
            padding: 0 .4rem;
            font-size: .875rem;
            line-height: 1.3;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem .85rem;
            /* фон как у блока — «разрыв» линии рамки у легенды */
            background: linear-gradient(180deg, rgba(0, 0, 0, .12), rgba(0, 0, 0, .42));
        }
        .machining-lost-fieldset .machining-lost-search-input {
            flex: 1 1 10rem;
            min-width: 9rem;
            max-width: 22rem;
        }
        .machining-lost-scroll {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .machining-lost-thumb {
            object-fit: cover;
            display: block;
        }
        .machining-drag-handle {
            cursor: grab;
            color: var(--dir-muted, #adb5bd);
            user-select: none;
        }
        .machining-drag-handle:active { cursor: grabbing; }
        /* Ширина колонок — colgroup + table-layout:fixed; без горизонтальной прокрутки */
        .machining-table-outer {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }
        #machining-wo-table {
            table-layout: fixed;
            width: 100%;
            max-width: 100%;
        }
        /* Тело таблицы (без шапки): 14px; при ширине ≤1280px — 12px */
        #machining-wo-table tbody {
            font-size: 16px;
        }
        #machining-wo-table tbody td.small {
            font-size: 1em;
        }
        #machining-wo-table tbody .form-control,
        #machining-wo-table tbody .form-control-sm {
            font-size: 1em;
        }
        #machining-wo-table tbody .btn-sm {
            font-size: 1em;
        }
        @media (max-width: 1280px) {
            #machining-wo-table tbody {
                font-size: 12px;
            }
        }
        #machining-wo-table th,
        #machining-wo-table td {
            overflow: hidden;
            vertical-align: middle;
        }
        #machining-wo-table .machining-col-wrap {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        /* Processes: не более двух строк; полный текст — в title ячейки */
        #machining-wo-table td.machining-col-processes-td {
            min-width: 0;
            max-width: 0;
        }
        #machining-wo-table .machining-processes-clamp {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.28;
            text-align: center;
            margin: 0 auto;
            max-width: 100%;
        }
        #machining-wo-table .machining-col-ellipsis {
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        /* Сумма ~100%: фиксированное распределение без min-width в rem → без горизонтального скролла */
        #machining-wo-table col.machining-col-drag { width: 35px;}
        #machining-wo-table col.machining-col-num { width: 6%; }
        #machining-wo-table col.machining-col-wo { width: 11%; }
        #machining-wo-table col.machining-col-customer { width: 15%; }
        #machining-wo-table col.machining-col-aircraft { width: 17%; }
        #machining-wo-table col.machining-col-pn { width: 15%; }
        #machining-wo-table col.machining-col-processes { width: 12%; }
        #machining-wo-table col.machining-col-work { width: 11%; }
        #machining-wo-table .machining-steps-controls {
            min-width: 0;
            justify-content: center;
        }
        #machining-wo-table .machining-steps-controls .machining-steps-n-input {
            width: 3rem;
            min-width: 2.5rem;
            flex: 0 0 auto;
        }
        #machining-wo-table .machining-step-lead-cell {
            text-align: end;
            vertical-align: middle;
        }
        /* Step note + Step N — одна строка, без переноса flex-элементов */
        #machining-wo-table .machining-step-lead-cell .machining-step-lead-row {
            justify-content: flex-start;
            text-align: start;
            min-width: 0;
        }
        #machining-wo-table .machining-step-lead-cell .js-machining-step-description {
            flex: 1 1 0;
            min-width: 0;
            width: 0; /* вместе с flex-grow даёт сжатие в узкой ячейке без переноса строки flex */
        }
        #machining-wo-table col.machining-col-date { width: 145px; }
        #machining-wo-table:not(.machining-table-has-drag) col.machining-col-num { width: 5.5rem; max-width: 6rem; }
        .machining-col-priority {
            max-width: 100%;
        }
        .js-machining-position-input {
            min-width: 0;
            max-width: 100%;
            width: 100%;
            padding: .35rem .5rem;
            text-align: center;
            font-size: 1em;
            background: var(--dir-input-bg, rgba(33,37,41,.85)) !important;
            border-color: var(--dir-input-border) !important;
            color: var(--dir-text) !important;
        }
        .js-machining-msg-owner {
            cursor: pointer;
            text-decoration: underline dotted;
            text-underline-offset: 2px;
        }
        .js-machining-msg-owner:hover { color: #0dcaf0 !important; }
        .machining-dir-table thead th {
            color: var(--bs-info) !important;
            background: var(--dir-thead-bg, #343A40) !important;
        }
        .machining-wo-label {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }
        .machining-date-readonly.finish-input {
            cursor: default;
            pointer-events: none;
        }
        .machining-date-readonly.finish-input:not(.has-finish) {
            background-image: none !important;
            padding-right: 0.35em !important;
        }
        #machining-wo-table input.machining-date-readonly.finish-input.has-finish {
            background-image: none !important;
            background-color: rgba(25, 135, 84, .1);
            padding-right: 0.35em !important;
        }
        /* Видимая дата: dd.mon.yyyy; отступы в em — с масштабом tbody (≤1280px = 12px), без «запаса» mains 3.5rem под галочку */
        #machining-wo-table tbody input[type="text"].machining-date-display.finish-input {
            cursor: pointer;
            padding-right: 1.65em !important;
            text-align: left;
            direction: ltr;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.15em center;
            background-size: 1em 1em;
        }
        #machining-wo-table tbody input[type="text"].machining-date-display.finish-input.has-finish {
            background-color: rgba(25, 135, 84, .1);
            /* только календарь, без галочки mains */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.15em center;
            background-size: 1em 1em;
            padding-right: 1.65em !important;
        }
        /* Служебный нативный input[type=date]: скрыт визуально, но открывается через showPicker по клику на display */
        .machining-date-input-wrap .js-machining-picker-aid {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
            opacity: 0;
            pointer-events: none;
        }
        .machining-date-input-wrap .js-machining-picker-aid::-webkit-datetime-edit-fields-wrapper {
            opacity: 0;
        }
        .machining-date-input-wrap {
            position: relative;
            min-width: 0;
        }
        #machining-wo-table tbody input[type="text"].machining-date-display {
            color-scheme: dark;
            min-height: calc(1.8125rem + 2px);
            padding: 0.25em 1.65em 0.25em 0.35em !important;
            font-size: 1em;
            position: relative;
            z-index: 1;
            width: 100%;
        }
        .machining-date-fake-ph {
            display: none;
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--dir-muted, #6c757d);
            font-size: 0.9em;
            z-index: 0;
            line-height: 1;
        }
        .machining-date-input-wrap input.machining-date-display.machining-date-empty:not(:focus) + .machining-date-fake-ph {
            display: block;
        }
        #machining-wo-table td:has(.machining-date-display) {
            position: relative;
            z-index: 1;
        }
        .machining-header-search {
            min-width: 0;
        }
        #machining-wo-table .machining-wo-label .btn-link {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
        #machining-wo-table .machining-col-date-cell .machining-date-readonly {
            font-size: 1em;
            padding-left: 0.35em !important;
            padding-right: 0.35em !important;
        }
    </style>
@endsection

@php
    $queuedCount = $queuedCount ?? 0;
@endphp

@section('content')
    <div class="container-fluid py-2 ">
        <div class="card border-0 dir-page shadow-sm">
            <div class="card-header p-0 mx-0 bg-transparent border-0 dir-topbar">
                <div class="dir-topbar px-3 py-2">
                    <div class="row g-2 align-items-end flex-wrap machining-header-toolbar pb-1">
                        <div class="col-auto flex-shrink-0 align-self-center">
                            <h5 class="mb-0 text-info text-nowrap"
                                title="{{ $queuedCount }} workorder(s) in machining queue">
                                Machining
                                <span class="text-secondary">(</span>
                                <span class="text-success js-machining-queued-count">{{ $queuedCount }}</span>
                                <span class="text-secondary small"> in queue</span>
                                <span class="text-secondary">)</span>
                            </h5>
                        </div>
                        <div class="col-auto flex-shrink-0 machining-header-search">
                            <label for="machiningTableSearch" class="visually-hidden">Search table</label>
                            <input type="search"
                                   id="machiningTableSearch"
                                   class="form-control form-control-sm dir-input"
                                   placeholder="Search (WO, customer, P/N, owner, dates…)"
                                   autocomplete="off">
                        </div>
                        <div class="col-auto flex-shrink-0 machining-header-hide-closed">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" id="machiningHideClosed" value="1" autocomplete="off" checked>
                                <label class="form-check-label text-nowrap small" for="machiningHideClosed">Hide closed</label>
                            </div>
                        </div>
                        <div class="col-auto flex-shrink-0">
                            <label for="machiningCompletedMachinistFilter" class="form-label small mb-0 text-secondary text-nowrap">Machinist (completed)</label>
                            <select id="machiningCompletedMachinistFilter"
                                    class="form-select form-select-sm dir-input"
                                    style="min-width: 11rem"
                                    autocomplete="off">
                                <option value="">Any</option>
                                @foreach($machiningMachinists ?? [] as $mu)
                                    <option value="{{ (int) $mu->id }}">{{ $mu->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto flex-shrink-0">
                            <label for="machiningCompletedDateFrom" class="form-label small mb-0 text-secondary text-nowrap">Finish from</label>
                            <input type="date"
                                   id="machiningCompletedDateFrom"
                                   class="form-control form-control-sm dir-input"
                                   autocomplete="off">
                        </div>
                        <div class="col-auto flex-shrink-0">
                            <label for="machiningCompletedDateTo" class="form-label small mb-0 text-secondary text-nowrap">to</label>
                            <input type="date"
                                   id="machiningCompletedDateTo"
                                   class="form-control form-control-sm dir-input"
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-2 px-3 m-0 flex-grow-1 d-flex flex-column">
                <div class="dir-panel border pt-2 px-2 pb-2">
                    <div class="dir-table-wrap machining-table-scroll machining-table-outer">
                        <table class="table table-sm table-hover align-middle mb-0 dir-table machining-dir-table {{ ($canReorderMachining ?? false) ? 'machining-table-has-drag' : '' }} " id="machining-wo-table">
                            <colgroup>
                                @if($canReorderMachining ?? false)
                                    <col class="machining-col-drag">
                                @endif
                                <col class="machining-col-num">
                                <col class="machining-col-wo">
                                <col class="machining-col-customer">
                                <col class="machining-col-aircraft">
                                <col class="machining-col-pn">
                                <col class="machining-col-processes">
                                <col class="machining-col-date">
                                <col class="machining-col-work">
                                <col class="machining-col-date">
                                <col class="machining-col-date">
                            </colgroup>
                            <thead>
                                <tr class="text-center text-small text-nowrap" style="font-size: 12px">
                                    @if($canReorderMachining ?? false)
                                        <th class="machining-col-drag" title="Drag"></th>
                                    @endif
                                    <th class="machining-col-priority" title="Queue position (from workorder)">№</th>
                                    <th>WO #</th>
                                    <th>Customer</th>
                                    <th>Component PN <br> AirCraft Type</th>
                                    <th>Part</th>
                                    <th>Processes</th>
                                    <th>Date send</th>
                                    <th>Working Steps <br> Machinist</th>
                                    <th>Date start</th>
                                    <th>Date finish</th>
                                    <th>_</th>
                                </tr>
                            </thead>
                            <tbody id="machining-sortable-tbody">
                            @include('admin.machining.partials.table-body')
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        window.__machiningTableFragmentUrl = @json(route('machining.table_fragment'));
        window.__machiningCanReorder = @json($canReorderMachining ?? false);
    </script>
    @include('admin.machining.partials.scripts')
@endsection
