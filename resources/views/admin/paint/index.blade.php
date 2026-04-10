@extends('admin.master')

@section('style')
    @include('admin.mains.partials.styles')
    <style>
        .paint-page-root {
            min-height: calc(100dvh - 100px);
            display: flex;
            flex-direction: column;
        }
        .paint-page-root .dir-panel {
            height: auto;
            max-height: calc(100dvh - 120px);
            min-width: 0;
        }
        .paint-table-scroll {
            max-height: calc(100dvh - 220px);
        }
        .paint-table-outer.paint-table-scroll {
            max-height: calc(100dvh - 220px);
        }
        /* Нижний блок lost parts — одна рамка (без вложенного .paint-page-bottom) */
        .paint-lost-fieldset {
            flex: 1 1 auto;
            min-height: 120px;
            margin: .75rem 0 0;
            min-width: 0;
            border: 1px solid var(--dir-border, rgba(255, 255, 255, .18));
            border-radius: var(--dir-radius-lg, .75rem);
            padding: .55rem .65rem .65rem;
            background: linear-gradient(180deg, rgba(0, 0, 0, .12), rgba(0, 0, 0, .42));
        }
        .paint-lost-drawer-wrap {
            position: relative;
            margin-top: .55rem;
        }
        .paint-lost-drawer-toggle {
            position: absolute;
            right: .35rem;
            top: -.15rem;
            z-index: 5;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .paint-lost-drawer-wrap.is-collapsed .paint-lost-fieldset {
            max-height: 0;
            min-height: 0;
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-width: 0;
            opacity: 0;
            transform: translateY(14px);
            pointer-events: none;
        }
        .paint-lost-fieldset {
            max-height: 260px;
            opacity: 1;
            transform: translateY(0);
            overflow: hidden;
            transition-property: max-height, opacity, transform, margin, padding, border-width;
            transition-duration: 1s, 1s, 1s, 1s, 1s, 1s;
            transition-timing-function: cubic-bezier(.23,1,.32,1), cubic-bezier(.23,1,.32,1), cubic-bezier(.23,1,.32,1), cubic-bezier(.23,1,.32,1), cubic-bezier(.23,1,.32,1), cubic-bezier(.23,1,.32,1);
        }
        .paint-lost-drawer-wrap.is-collapsed .paint-lost-drawer-toggle i {
            transform: rotate(180deg);
        }
        .paint-lost-drawer-toggle i {
            transition: transform 1s cubic-bezier(.23,1,.32,1);
        }
        .paint-lost-fieldset .paint-lost-legend {
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
        .paint-lost-fieldset .paint-lost-search-input {
            flex: 1 1 10rem;
            min-width: 9rem;
            max-width: 22rem;
        }
        .paint-lost-scroll {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .paint-lost-thumb {
            object-fit: cover;
            display: block;
        }
        .paint-drag-handle {
            cursor: grab;
            color: var(--dir-muted, #adb5bd);
            user-select: none;
        }
        .paint-drag-handle:active { cursor: grabbing; }
        #paint-sortable-tbody tr.sortable-chosen td {
            background: rgba(13, 110, 253, .16) !important;
        }
        #paint-sortable-tbody tr.sortable-ghost td {
            opacity: .92;
            background: rgba(13, 110, 253, .24) !important;
        }
        #paint-sortable-tbody tr.sortable-drag td {
            opacity: 1 !important;
            background: rgba(13, 110, 253, .28) !important;
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, .45);
        }
        /* Ширина колонок — colgroup + table-layout:fixed; без горизонтальной прокрутки */
        .paint-table-outer {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }
        #paint-wo-table {
            table-layout: fixed;
            width: 100%;
            max-width: 100%;
        }
        /* Тело таблицы (без шапки): 14px; при ширине ≤1280px — 12px */
        #paint-wo-table tbody {
            font-size: 16px;
        }
        #paint-wo-table tbody td.small {
            font-size: 1em;
        }
        #paint-wo-table tbody .form-control,
        #paint-wo-table tbody .form-control-sm {
            font-size: 1em;
        }
        #paint-wo-table tbody .btn-sm {
            font-size: 1em;
        }
        @media (max-width: 1280px) {
            #paint-wo-table tbody {
                font-size: 12px;
            }
        }
        #paint-wo-table th,
        #paint-wo-table td {
            overflow: hidden;
            vertical-align: middle;
        }
        #paint-wo-table .paint-col-wrap {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        #paint-wo-table .paint-col-ellipsis {
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        /* Сумма ~100%: фиксированное распределение без min-width в rem → без горизонтального скролла */
        #paint-wo-table col.paint-col-drag { width: 35px;}
        #paint-wo-table col.paint-col-num { width: 60px; }
        #paint-wo-table col.paint-col-wo { width: 90px; }
        #paint-wo-table col.paint-col-customer { width: auto; }
        #paint-wo-table col.paint-col-aircraft { width: auto; }
        #paint-wo-table col.paint-col-owner { width: auto; }
        #paint-wo-table col.paint-col-detail { width: 140px; }
        #paint-wo-table col.paint-col-date { width: 142px !important; min-width: 142px !important; max-width: 142px !important; }
        #paint-wo-table th.paint-col-date-cell,
        #paint-wo-table td.paint-col-date-cell {
            width: 142px !important;
            min-width: 142px !important;
            max-width: 142px !important;
        }
        #paint-wo-table:not(.paint-table-has-drag) col.paint-col-num { width: 125px; }
        .paint-col-priority {
            max-width: 100%;
        }
        .js-paint-position-input {
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
        .paint-queue-position-input,
        .paint-queue-position-value {
            color: var(--bs-info) !important;
        }
        .js-paint-msg-owner {
            cursor: pointer;
            text-decoration: underline dotted;
            text-underline-offset: 2px;
        }
        .js-paint-msg-owner:hover { color: #0dcaf0 !important; }
        .paint-dir-table thead th {
            color: var(--bs-info) !important;
            background: var(--dir-thead-bg, #343A40) !important;
        }
        .paint-wo-label {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }
        .paint-wo-prefix {
            color: #8D9197 !important;
        }
        .paint-date-readonly.finish-input {
            cursor: default;
            pointer-events: none;
        }
        .paint-date-readonly.finish-input:not(.has-finish) {
            background-image: none !important;
            padding-right: 0.35em !important;
        }
        #paint-wo-table input.paint-date-readonly.finish-input.has-finish {
            background-image: none !important;
            background-color: rgba(25, 135, 84, .1);
            padding-right: 0.35em !important;
        }
        /* Видимая дата: dd.mon.yyyy; отступы в em — с масштабом tbody (≤1280px = 12px), без «запаса» mains 3.5rem под галочку */
        #paint-wo-table tbody input[type="text"].paint-date-display.finish-input {
            cursor: pointer;
            padding-right: 1.65em !important;
            padding-left: .72em !important;
            text-align: left;
            direction: ltr;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.15em center;
            background-size: 1em 1em;
        }
        #paint-wo-table tbody input[type="text"].paint-date-display.finish-input.has-finish {
            background-color: rgba(25, 135, 84, .1);
            /* только календарь, без галочки mains */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M3 0a1 1 0 0 0-1 1v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 14.5 2H14V1a1 1 0 0 0-2 0v1H4V1a1 1 0 0 0-1-1zM1 5h14v9.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.15em center;
            background-size: 1em 1em;
            padding-right: 1.65em !important;
            padding-left: .72em !important;
        }
        /* Служебный нативный input[type=date]: скрыт визуально, но открывается через showPicker по клику на display */
        .paint-date-input-wrap .js-paint-picker-aid {
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
        .paint-date-input-wrap .js-paint-picker-aid::-webkit-datetime-edit-fields-wrapper {
            opacity: 0;
        }
        .paint-date-input-wrap {
            position: relative;
            min-width: 0;
        }
        #paint-wo-table tbody input[type="text"].paint-date-display {
            color-scheme: dark;
            min-height: calc(1.8125rem + 2px);
            padding: 0.25em 1.65em 0.25em .72em !important;
            font-size: 1em;
            position: relative;
            z-index: 1;
            width: 100%;
            min-width: 0;
            max-width: none;
            box-sizing: border-box;
        }
        #paint-wo-table td.paint-col-date-cell {
            padding-left: 2px;
            padding-right: 2px;
        }
        .paint-date-fake-ph {
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
        .paint-date-input-wrap input.paint-date-display.paint-date-empty:not(:focus) + .paint-date-fake-ph {
            display: block;
        }
        #paint-wo-table td:has(.paint-date-display) {
            position: relative;
            z-index: 1;
        }
        .paint-header-search {
            min-width: 0;
        }
        .paint-header-search .form-control {
            max-width: 440px;
        }
        .paint-header-hide-closed {
            white-space: nowrap;
            font-size: .86rem;
            color: var(--dir-muted, #adb5bd);
        }
        #paint-wo-table .paint-col-owner .btn-link {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
        #paint-wo-table .paint-col-date-cell .paint-date-readonly {
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
    <div class="container-fluid py-2 paint-page-root">
        <div class="card border-0 dir-page shadow-sm">
            <div class="card-header p-0 mx-0 bg-transparent border-0 dir-topbar">
                <div class="dir-topbar px-3 py-2">
                    <div class="row g-2 align-items-center flex-nowrap">
                        <div class="col-auto flex-shrink-0">
                            <h5 class="mb-0 text-info text-nowrap"
                                title="{{ $queuedCount }} workorder(s) in paint queue">
                                Paint
                                <span class="text-secondary">(</span>
                                <span class="text-success">{{ $queuedCount }}</span>
                                <span class="text-secondary small"> in queue</span>
                                <span class="text-secondary">)</span>
                            </h5>
                        </div>
                        <div class="col min-w-0 paint-header-search">
                            <label for="paintTableSearch" class="visually-hidden">Search table</label>
                            <input type="search"
                                   id="paintTableSearch"
                                   class="form-control form-control-sm dir-input w-100"
                                   placeholder="Search (WO, customer, P/N, owner, dates…)"
                                   autocomplete="off">
                        </div>
                        <div class="col-auto flex-shrink-0">
                            <label class="d-inline-flex align-items-center gap-2 m-0 paint-header-hide-closed" for="paintHideClosedRows">
                                <input type="checkbox" id="paintHideClosedRows">
                                <span>Hide closed</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-1 px-3 m-0 flex-grow-1 d-flex flex-column">
                <div class="dir-panel border p-0 px-2 pb-2">
                    <div class="dir-table-wrap paint-table-scroll paint-table-outer">
                        <table class="table table-sm table-hover align-middle mb-0 dir-table paint-dir-table {{ ($canReorderPaint ?? false) ? 'paint-table-has-drag' : '' }} " id="paint-wo-table">
                            <colgroup>
                                @if($canReorderPaint ?? false)
                                    <col class="paint-col-drag">
                                @endif
                                <col class="paint-col-num">
                                <col class="paint-col-wo">
                                <col class="paint-col-customer">
                                <col class="paint-col-aircraft">
                                <col class="paint-col-owner">
                                <col class="paint-col-detail">
                                <col class="paint-col-date">
                                <col class="paint-col-date">
                            </colgroup>
                            <thead>
                                <tr class="text-center text-small text-nowrap" style="font-size: 12px">
                                    @if($canReorderPaint ?? false)
                                        <th class="paint-col-drag" title="Drag"></th>
                                    @endif
                                    <th class="paint-col-priority" title="Queue position (from workorder)">№</th>
                                    <th>WO #</th>
                                    <th>Customer</th>
                                    <th>AirCraft Type</th>
                                    <th>Owner</th>
                                    <th>Detail (P/N)</th>
                                    <th class="paint-col-date-cell">Date start</th>
                                    <th class="paint-col-date-cell">Date finish</th>
                                </tr>
                            </thead>
                            <tbody id="paint-sortable-tbody">
                            @forelse ($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $editTp = $row->edit_paint_process;
                                    $woDigits = (string) ((int) $wo->number);
                                    $woPrefix = mb_substr($woDigits, 0, 3);
                                    $woTail = mb_substr($woDigits, 3);
                                    $fmtPaintDisp = static function ($d) {
                                        if ($d === null) {
                                            return '';
                                        }

                                        return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
                                    };
                                    $startStr = $fmtPaintDisp($row->date_start);
                                    $finishStr = $fmtPaintDisp($row->date_finish);
                                    $tpStartYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                                    $tpStartDisp = $fmtPaintDisp($editTp?->date_start);
                                    $tpFinishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                                    $tpFinishDisp = $fmtPaintDisp($editTp?->date_finish);
                                    $paintSearchBlob = implode(' ', array_filter([
                                        'w' . $wo->number,
                                        (string) ($wo->customer?->name ?? ''),
                                        (string) ($wo->unit?->manual?->plane?->type ?? ''),
                                        (string) ($wo->unit?->part_number ?? ''),
                                        (string) ($wo->user?->name ?? ''),
                                        (string) ($row->detail_label ?? ''),
                                        $startStr,
                                        $finishStr,
                                    ]));
                                    $paintSearch = function_exists('mb_strtolower')
                                        ? mb_strtolower($paintSearchBlob, 'UTF-8')
                                        : strtolower($paintSearchBlob);
                                @endphp
                                <tr data-wo-id="{{ (int) $wo->id }}"
                                    data-paint-search="{{ $paintSearch }}"
                                    data-paint-closed="{{ ($startStr !== '' && $finishStr !== '') ? '1' : '0' }}"
                                    class="{{ $wo->paint_queue_order !== null ? 'paint-row-queued' : 'paint-row-unqueued' }} {{ ($row->is_queue_master ?? false) ? 'paint-row-master' : '' }}">
                                    @if($canReorderPaint ?? false)
                                        <td class="text-center {{ $wo->paint_queue_order !== null && ($row->is_queue_master ?? false) ? 'paint-drag-handle' : '' }}"
                                            @if($wo->paint_queue_order !== null && ($row->is_queue_master ?? false)) title="Drag" @endif>
                                            @if($wo->paint_queue_order !== null && ($row->is_queue_master ?? false))
                                                <i class="bi bi-three-dots-vertical " aria-hidden="true"></i>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center align-middle paint-col-priority">
                                        @if($canReorderPaint ?? false)
                                            @if($wo->paint_queue_order !== null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-paint-position-input dir-input paint-queue-position-input text-info"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="1"
                                                       data-was="{{ (int) $row->paint_queue_position }}"
                                                       value="{{ (int) $row->paint_queue_position }}"
                                                       title="Position in queue (0 = remove from queue)">
                                            @elseif($wo->paint_queue_order === null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-paint-position-input dir-input paint-queue-position-input text-info"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="0"
                                                       data-was="0"
                                                       value=""
                                                       title="Enter queue position (0 = not in queue)">
                                            @elseif($wo->paint_queue_order !== null)
                                                <span class="text-muted"> </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @else
                                            @if($wo->paint_queue_order !== null)
                                                @if($row->is_queue_master ?? false)
                                                    <span class="paint-queue-position-value text-info">{{ $row->paint_queue_position }}</span>
                                                @else
                                                    <span class="text-muted"> </span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center text-light paint-wo-label paint-col-ellipsis">
                                        <span class="text-light">w</span><span class="paint-wo-prefix">{{ $woPrefix }}</span><span class="text-light">{{ $woTail }}</span>
                                    </td>
                                    <td class="text-center small paint-col-wrap">{{ $wo->customer?->name ?? '' }}</td>
                                    <td class="text-center small paint-col-wrap">{{ $wo->unit?->manual?->plane?->type ?? '' }}</td>
                                    <td class="text-center paint-col-owner">
                                        @if($wo->user_id && $wo->user)
                                            <button type="button"
                                                    class="btn btn-link btn-sm text-light p-0 js-paint-msg-owner"
                                                    data-user-id="{{ (int) $wo->user_id }}">
                                                {{ $wo->user->name }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small paint-col-wrap">
                                        {{ $row->detail_label ?? 'List' }}
                                    </td>
                                    <td class="paint-col-date-cell">
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_paint_index" value="1">
                                                <div class="paint-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_start"
                                                           value="{{ $tpStartYmd }}"
                                                           class="js-paint-date-ymd"
                                                           data-original="{{ $tpStartYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpStartDisp }}"
                                                           class="form-control form-control-sm finish-input paint-native-date paint-date-display {{ $tpStartYmd !== '' ? 'has-finish' : '' }} {{ $tpStartYmd !== '' ? '' : 'paint-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="paint-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-paint-picker-aid"
                                                           value="{{ $tpStartYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($startStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish paint-date-readonly w-100"
                                                   value="{{ $startStr }}">
                                        @else
                                            <span class="text-muted small d-block py-1">—</span>
                                        @endif
                                    </td>
                                    <td class="paint-col-date-cell">
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_paint_index" value="1">
                                                <div class="paint-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_finish"
                                                           value="{{ $tpFinishYmd }}"
                                                           class="js-paint-date-ymd"
                                                           data-original="{{ $tpFinishYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpFinishDisp }}"
                                                           class="form-control form-control-sm finish-input paint-native-date paint-date-display {{ $tpFinishYmd !== '' ? 'has-finish' : '' }} {{ $tpFinishYmd !== '' ? '' : 'paint-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="paint-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-paint-picker-aid"
                                                           value="{{ $tpFinishYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($finishStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish paint-date-readonly w-100"
                                                   value="{{ $finishStr }}">
                                        @else
                                            <span class="text-muted small d-block py-1">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canReorderPaint ?? false) ? 10 : 9 }}" class="text-center text-muted py-4">No workorders (approved, open, not draft).</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @include('admin.paint.partials.lost-parts', ['lostParts' => $lostParts ?? collect()])
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @include('admin.paint.partials.scripts')
@endsection
