@extends('admin.master')

@section('style')
    @include('admin.mains.partials.styles')
    <style>
        .machining-page-root {
            min-height: calc(100dvh - 100px);
            display: flex;
            flex-direction: column;
        }
        .machining-page-root .dir-panel {
            height: auto;
            max-height: calc(100dvh - 120px);
            min-width: 0;
        }
        .machining-table-scroll {
            max-height: calc(100dvh - 220px);
        }
        .machining-table-outer.machining-table-scroll {
            max-height: calc(100dvh - 220px);
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
        #machining-wo-table .machining-col-ellipsis {
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        /* Сумма ~100%: фиксированное распределение без min-width в rem → без горизонтального скролла */
        #machining-wo-table col.machining-col-drag { width: 35px;}
        #machining-wo-table col.machining-col-num { width: 8%; }
        #machining-wo-table col.machining-col-wo { width: 14%; }
        #machining-wo-table col.machining-col-customer { width: 20%; }
        #machining-wo-table col.machining-col-aircraft { width: 15%; }
        #machining-wo-table col.machining-col-pn { width: 18%; }
        #machining-wo-table col.machining-col-owner { width: 10%; }
        #machining-wo-table col.machining-col-detail { width: 10%; }
        #machining-wo-table col.machining-col-date { width: 145px; }
        #machining-wo-table:not(.machining-table-has-drag) col.machining-col-num { width: 145px; }
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
        #machining-wo-table .machining-col-owner .btn-link {
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
    <div class="container-fluid py-2 machining-page-root">
        <div class="card border-0 dir-page shadow-sm">
            <div class="card-header p-0 mx-0 bg-transparent border-0 dir-topbar">
                <div class="dir-topbar px-3 py-2">
                    <div class="row g-2 align-items-center flex-nowrap">
                        <div class="col-auto flex-shrink-0">
                            <h5 class="mb-0 text-info text-nowrap"
                                title="{{ $queuedCount }} workorder(s) in machining queue">
                                Machining
                                <span class="text-secondary">(</span>
                                <span class="text-success">{{ $queuedCount }}</span>
                                <span class="text-secondary small"> in queue</span>
                                <span class="text-secondary">)</span>
                            </h5>
                        </div>
                        <div class="col min-w-0 machining-header-search">
                            <label for="machiningTableSearch" class="visually-hidden">Search table</label>
                            <input type="search"
                                   id="machiningTableSearch"
                                   class="form-control form-control-sm dir-input w-100"
                                   placeholder="Search (WO, customer, P/N, owner, dates…)"
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body pt-1 px-3 m-0 flex-grow-1 d-flex flex-column">
                <div class="dir-panel border p-0 px-2 pb-2">
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
                                <col class="machining-col-owner">
                                <col class="machining-col-detail">
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
                                    <th>AirCraft Type</th>
                                    <th>Part number</th>
                                    <th>Owner</th>
                                    <th>Detail (P/N)</th>
                                    <th>Date start</th>
                                    <th>Date finish</th>
                                </tr>
                            </thead>
                            <tbody id="machining-sortable-tbody">
                            @forelse ($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $editTp = $row->edit_machining_process;
                                    $fmtMachiningDisp = static function ($d) {
                                        if ($d === null) {
                                            return '';
                                        }

                                        return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
                                    };
                                    $startStr = $fmtMachiningDisp($row->date_start);
                                    $finishStr = $fmtMachiningDisp($row->date_finish);
                                    $tpStartYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                                    $tpStartDisp = $fmtMachiningDisp($editTp?->date_start);
                                    $tpFinishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                                    $tpFinishDisp = $fmtMachiningDisp($editTp?->date_finish);
                                    $machiningSearchBlob = implode(' ', array_filter([
                                        'w' . $wo->number,
                                        (string) ($wo->customer?->name ?? ''),
                                        (string) ($wo->unit?->manual?->plane?->type ?? ''),
                                        (string) ($wo->unit?->part_number ?? ''),
                                        (string) ($wo->user?->name ?? ''),
                                        (string) ($row->detail_label ?? ''),
                                        $startStr,
                                        $finishStr,
                                    ]));
                                    $machiningSearch = function_exists('mb_strtolower')
                                        ? mb_strtolower($machiningSearchBlob, 'UTF-8')
                                        : strtolower($machiningSearchBlob);
                                @endphp
                                <tr data-wo-id="{{ (int) $wo->id }}"
                                    data-machining-search="{{ $machiningSearch }}"
                                    class="{{ $wo->machining_queue_order !== null ? 'machining-row-queued' : 'machining-row-unqueued' }} {{ ($row->is_queue_master ?? false) ? 'machining-row-master' : '' }}">
                                    @if($canReorderMachining ?? false)
                                        <td class="text-center {{ $wo->machining_queue_order !== null && ($row->is_queue_master ?? false) ? 'machining-drag-handle' : '' }}"
                                            @if($wo->machining_queue_order !== null && ($row->is_queue_master ?? false)) title="Drag" @endif>
                                            @if($wo->machining_queue_order !== null && ($row->is_queue_master ?? false))
                                                <i class="bi bi-three-dots-vertical " aria-hidden="true"></i>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center align-middle machining-col-priority">
                                        @if($canReorderMachining ?? false)
                                            @if($wo->machining_queue_order !== null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-machining-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="1"
                                                       data-was="{{ (int) $row->machining_queue_position }}"
                                                       value="{{ (int) $row->machining_queue_position }}"
                                                       title="Position in queue (0 = remove from queue)">
                                            @elseif($wo->machining_queue_order === null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-machining-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="0"
                                                       data-was="0"
                                                       value=""
                                                       title="Enter queue position (0 = not in queue)">
                                            @elseif($wo->machining_queue_order !== null)
                                                {{ (int) $row->machining_queue_position }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @else
                                            @if($wo->machining_queue_order !== null)
                                                {{ $row->machining_queue_position }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center text-light machining-wo-label machining-col-ellipsis">
                                        w{{ $wo->number }}
                                    </td>
                                    <td class="text-center small machining-col-wrap">{{ $wo->customer?->name ?? '' }}</td>
                                    <td class="text-center small machining-col-wrap">{{ $wo->unit?->manual?->plane?->type ?? '' }}</td>
                                    <td class="text-center machining-col-wrap">{{ $wo->unit?->part_number ?? '' }}</td>
                                    <td class="text-center machining-col-owner">
                                        @if($wo->user_id && $wo->user)
                                            <button type="button"
                                                    class="btn btn-link btn-sm text-light p-0 js-machining-msg-owner"
                                                    data-user-id="{{ (int) $wo->user_id }}">
                                                {{ $wo->user->name }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small machining-col-wrap">
                                        {{ $row->detail_label ?? 'List' }}
                                    </td>
                                    <td class="machining-col-date-cell">
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_machining_index" value="1">
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_start"
                                                           value="{{ $tpStartYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpStartYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpStartDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpStartYmd !== '' ? 'has-finish' : '' }} {{ $tpStartYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpStartYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($startStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $startStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                    </td>
                                    <td class="machining-col-date-cell">
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_machining_index" value="1">
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_finish"
                                                           value="{{ $tpFinishYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpFinishYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpFinishDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpFinishYmd !== '' ? 'has-finish' : '' }} {{ $tpFinishYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpFinishYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($finishStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $finishStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canReorderMachining ?? false) ? 10 : 9 }}" class="text-center text-muted py-4">No workorders (approved, open, not draft).</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @include('admin.machining.partials.scripts')
@endsection
