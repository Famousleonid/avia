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
        }
        .paint-table-scroll {
            max-height: calc(100dvh - 220px);
        }
        .paint-page-bottom {
            flex: 1 1 auto;
            min-height: 120px;
            margin-top: .75rem;
            border-radius: var(--dir-radius-lg, .75rem);
            border: 1px solid var(--dir-border);
            background: linear-gradient(180deg, rgba(0,0,0,.12), rgba(0,0,0,.42));
        }
        .paint-drag-handle {
            cursor: grab;
            color: var(--dir-muted, #adb5bd);
            user-select: none;
        }
        .paint-drag-handle:active { cursor: grabbing; }
        .paint-col-priority {
            min-width: 5.5rem;
            width: 6.5rem;
        }
        .js-paint-position-input {
            min-width: 4.75rem;
            max-width: 100%;
            width: 100%;
            padding: .35rem .5rem;
            text-align: center;
            font-size: 0.95rem;
            background: var(--dir-input-bg, rgba(33,37,41,.85)) !important;
            border-color: var(--dir-input-border) !important;
            color: var(--dir-text) !important;
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
        .paint-date-readonly.finish-input {
            cursor: default;
            pointer-events: none;
        }
        .paint-date-readonly.finish-input:not(.has-finish) {
            background-image: none !important;
            padding-right: 0.5rem !important;
        }
        /* Один календарь: только нативный индикатор; убираем SVG из .finish-input (mains) */
        #paint-wo-table input[type="date"].paint-native-date.finish-input,
        #paint-wo-table input[type="date"].paint-native-date.finish-input.has-finish {
            background-image: none !important;
            padding-right: 2rem !important;
        }
        #paint-wo-table input[type="date"].paint-native-date.has-finish {
            background-color: rgba(25, 135, 84, .1);
        }
        .paint-date-input-wrap {
            position: relative;
            min-width: 9rem;
        }
        #paint-wo-table input[type="date"].paint-native-date {
            color-scheme: dark;
            min-height: calc(1.8125rem + 2px);
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
            width: 100%;
        }
        /* Пустая дата: «…» вместо подсказки браузера дд.мм.гггг */
        .paint-date-input-wrap input.paint-native-date.paint-date-empty:not(:focus)::-webkit-datetime-edit-fields-wrapper {
            opacity: 0;
        }
        .paint-date-fake-ph {
            display: none;
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--dir-muted, #6c757d);
            font-size: 0.9rem;
            z-index: 0;
            line-height: 1;
        }
        .paint-date-input-wrap input.paint-native-date.paint-date-empty:not(:focus) + .paint-date-fake-ph {
            display: block;
        }
        #paint-wo-table td:has(input.paint-native-date) {
            position: relative;
            z-index: 1;
        }
        .paint-header-search {
            min-width: 0;
        }
    </style>
@endsection

@php
    $queueCount = $rows->count();
    $queuedCount = $queuedCount ?? 0;
@endphp

@section('content')
    <div class="container-fluid py-2 paint-page-root">
        <div class="card border-0 dir-page shadow-sm">
            <div class="card-header p-0 mx-0 bg-transparent border-0 dir-topbar">
                <div class="dir-topbar px-3 py-2">
                    <div class="row g-2 align-items-center flex-nowrap">
                        <div class="col-auto flex-shrink-0">
                            <h5 class="mb-0 text-info text-nowrap">
                                Paint
                                <span class="text-secondary">(</span>
                                <span class="text-success">{{ $queueCount }}</span>
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
                        @if($canReorderPaint ?? false)
                            <div class="col-auto flex-shrink-0">
                                <button type="button"
                                        class="btn btn-outline-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paintAddWoModal"
                                        title="Add workorder to paint queue">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body pt-1 px-3 m-0 flex-grow-1 d-flex flex-column">
                <div class="dir-panel border p-0 px-2 pb-2">
                    <div class="table-responsive dir-table-wrap paint-table-scroll">
                        <table class="table table-sm table-hover align-middle mb-0 dir-table paint-dir-table" id="paint-wo-table">
                            <thead>
                                <tr class="text-center">
                                    @if($canReorderPaint ?? false)
                                        <th style="width:36px;" title="Drag"></th>
                                    @endif
                                    <th class="paint-col-priority" title="Queue position">№</th>
                                    <th>WO #</th>
                                    <th>Customer</th>
                                    <th>AirCraft Type</th>
                                    <th>Part number</th>
                                    <th>Owner</th>
                                    <th style="min-width:120px;">Detail (P/N)</th>
                                    <th>Date start</th>
                                    <th>Date finish</th>
                                </tr>
                            </thead>
                            <tbody id="paint-sortable-tbody">
                            @forelse ($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $editTp = $row->edit_paint_process;
                                    $startStr = $row->date_start ? $row->date_start->format('d.m.Y') : '';
                                    $finishStr = $row->date_finish ? $row->date_finish->format('d.m.Y') : '';
                                    $paintSearchBlob = implode(' ', array_filter([
                                        'w' . $wo->number,
                                        (string) ($wo->customer?->name ?? ''),
                                        (string) ($wo->unit?->manual?->plane?->type ?? ''),
                                        (string) ($wo->unit?->part_number ?? ''),
                                        (string) ($wo->user?->name ?? ''),
                                        ! empty($row->detail_pns) ? implode(' ', $row->detail_pns) : '',
                                        $startStr,
                                        $finishStr,
                                    ]));
                                    $paintSearch = function_exists('mb_strtolower')
                                        ? mb_strtolower($paintSearchBlob, 'UTF-8')
                                        : strtolower($paintSearchBlob);
                                @endphp
                                <tr data-wo-id="{{ (int) $wo->id }}"
                                    data-paint-search="{{ $paintSearch }}"
                                    class="{{ $wo->paint_queue_order !== null ? 'paint-row-queued' : 'paint-row-unqueued' }}">
                                    @if($canReorderPaint ?? false)
                                        <td class="text-center {{ $wo->paint_queue_order !== null ? 'paint-drag-handle' : '' }}"
                                            @if($wo->paint_queue_order !== null) title="Drag" @endif>
                                            @if($wo->paint_queue_order !== null)
                                                <i class="bi bi-grid-3x3-gap fs-5" aria-hidden="true"></i>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center align-middle paint-col-priority">
                                        @if($canReorderPaint ?? false)
                                            @if($wo->paint_queue_order !== null)
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-paint-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="1"
                                                       data-was="{{ (int) $row->paint_queue_position }}"
                                                       value="{{ (int) $row->paint_queue_position }}"
                                                       title="Position in queue (0 = remove from queue)">
                                            @else
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-paint-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="0"
                                                       data-was="0"
                                                       value=""
                                                       title="Enter queue position (0 = not in queue)">
                                            @endif
                                        @else
                                            @if($wo->paint_queue_order !== null)
                                                {{ $row->paint_queue_position }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center text-light paint-wo-label">
                                        w{{ $wo->number }}
                                    </td>
                                    <td class="text-center small">{{ $wo->customer?->name ?? '' }}</td>
                                    <td class="text-center small">{{ $wo->unit?->manual?->plane?->type ?? '' }}</td>
                                    <td class="text-center">{{ $wo->unit?->part_number ?? '' }}</td>
                                    <td class="text-center">
                                        @if($wo->user_id && $wo->user)
                                            <button type="button"
                                                    class="btn btn-link btn-sm text-light p-0 js-paint-msg-owner"
                                                    data-user-id="{{ (int) $wo->user_id }}">
                                                {{ $wo->user->name }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if (!empty($row->detail_pns))
                                            {{ implode(', ', $row->detail_pns) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <div class="paint-date-input-wrap">
                                                    <input type="date"
                                                           name="date_start"
                                                           class="form-control form-control-sm finish-input paint-native-date {{ $editTp->date_start ? 'has-finish' : '' }} {{ $editTp->date_start ? '' : 'paint-date-empty' }}"
                                                           value="{{ $editTp->date_start?->format('Y-m-d') }}"
                                                           data-original="{{ $editTp->date_start?->format('Y-m-d') ?? '' }}">
                                                    <span class="paint-date-fake-ph" aria-hidden="true">…</span>
                                                </div>
                                            </form>
                                        @elseif ($startStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish paint-date-readonly w-100"
                                                   value="{{ $startStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input paint-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editTp)
                                            <form method="POST"
                                                  action="{{ route('tdrprocesses.updateDate', $editTp) }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                <div class="paint-date-input-wrap">
                                                    <input type="date"
                                                           name="date_finish"
                                                           class="form-control form-control-sm finish-input paint-native-date {{ $editTp->date_finish ? 'has-finish' : '' }} {{ $editTp->date_finish ? '' : 'paint-date-empty' }}"
                                                           value="{{ $editTp->date_finish?->format('Y-m-d') }}"
                                                           data-original="{{ $editTp->date_finish?->format('Y-m-d') ?? '' }}">
                                                    <span class="paint-date-fake-ph" aria-hidden="true">…</span>
                                                </div>
                                            </form>
                                        @elseif ($finishStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish paint-date-readonly w-100"
                                                   value="{{ $finishStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input paint-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
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
                <div class="paint-page-bottom" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    @if($canReorderPaint ?? false)
        <div class="modal fade" id="paintAddWoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-light border-secondary">
                    <div class="modal-header border-secondary">
                        <h6 class="modal-title">Add workorder to paint queue</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small text-secondary">Workorder number</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="paintAddWoNumber" min="1" placeholder="Number">
                        <div class="text-danger small mt-2 d-none" id="paintAddWoErr"></div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success btn-sm" id="paintAddWoBtn">Add</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    @include('admin.paint.partials.scripts')
@endsection
