@extends('mobile.master')

@section('style')
    <style>
        .paint-mobile-wrap {
            padding: 0 0 max(11rem, calc(env(safe-area-inset-bottom, 0px) + 8rem));
            box-sizing: border-box;
        }
        .paint-mobile-bottom-spacer {
            min-height: 5.5rem;
            height: max(5.5rem, calc(env(safe-area-inset-bottom, 0px) + 2rem));
            flex-shrink: 0;
            background: linear-gradient(to bottom, transparent, rgba(52, 58, 64, 0.55));
            border-radius: 0 0 0.35rem 0.35rem;
            pointer-events: none;
        }
        .paint-mobile-card .table-responsive {
            padding-bottom: 1.25rem;
        }
        .paint-mobile-card {
            background: rgba(20, 24, 28, .9);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .35rem;
            padding: .35rem;
            margin: 0;
        }
        .paint-mobile-table {
            color: #e9ecef;
            font-size: .88rem;
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }
        .paint-mobile-table thead th {
            color: #9fb0c0;
            font-size: .78rem;
            white-space: nowrap;
            padding: .28rem .22rem;
        }
        .paint-mobile-col-queue {
            text-align: center;
            width: 28px;
            max-width: 36px;
        }
        .paint-mobile-col-detail {
            font-size: .8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 0;
            padding-left: .42rem !important;
        }
        .paint-mobile-wo-label {
            display: inline-flex;
            max-width: 100%;
            align-items: baseline;
            font-weight: 700;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .paint-mobile-wo-label.paint-mobile-wo-open-details {
            cursor: pointer;
            border-radius: .35rem;
            padding: .02rem .12rem;
            margin-left: -.12rem;
        }
        .paint-mobile-wo-label.paint-mobile-wo-open-details:active {
            background: rgba(13, 202, 240, .12);
        }
        .paint-mobile-wo-prefix {
            color: #8D9197;
        }
        .paint-mobile-wo-tail {
            color: #f8f9fa;
        }
        .paint-mobile-detail-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: .35rem 0;
            font-size: .85rem;
        }
        .paint-mobile-detail-row:last-child {
            border-bottom: 0;
        }
        .paint-mobile-detail-label {
            color: #94a3b8;
            flex: 0 0 auto;
        }
        .paint-mobile-detail-value {
            color: #e2e8f0;
            min-width: 0;
            text-align: right;
            overflow-wrap: anywhere;
        }
        .paint-mobile-table td, .paint-mobile-table th {
            vertical-align: middle;
            padding: .26rem .22rem;
        }
        tr.paint-mobile-group-start td {
            border-top: 2px solid rgba(255, 255, 255, .14);
        }
        tr.paint-mobile-group-follow td {
            border-top: 1px solid rgba(255, 255, 255, .06);
        }
        .lost-carousel-img {
            width: 100%;
            max-height: 270px;
            object-fit: contain;
            background: #111;
            border-radius: .45rem;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        .lost-carousel-title {
            font-size: .88rem;
            font-weight: 700;
            color: #e9ecef;
            text-align: center;
            letter-spacing: .01em;
        }
        .lost-carousel-subtitle {
            font-size: .72rem;
            color: #97a8b8;
            text-align: center;
            margin-top: .12rem;
            min-height: 1.05rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lost-coverflow-wrap {
            position: relative;
            margin: 0 -.45rem;
            padding: .75rem 0 .45rem;
            border-radius: .95rem;
            background:
                radial-gradient(120% 140% at 50% -20%, rgba(48, 119, 207, .35) 0%, rgba(12, 18, 26, 0) 55%),
                linear-gradient(180deg, rgba(8, 12, 18, .97), rgba(9, 14, 20, .82));
            border: 1px solid rgba(165, 197, 225, .24);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 14px 34px rgba(0,0,0,.45);
        }
        .lost-coverflow-track {
            display: flex;
            flex-direction: row;
            gap: .95rem;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: 9%;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: .55rem 9% 1.25rem;
            touch-action: pan-x pinch-zoom;
            overscroll-behavior-x: contain;
        }
        .lost-coverflow-track::-webkit-scrollbar {
            display: none;
        }
        .lost-coverflow-slide {
            flex: 0 0 82%;
            max-width: 420px;
            scroll-snap-align: center;
            scroll-snap-stop: always;
        }
        .lost-coverflow-card {
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(12, 20, 28, .95);
            border: 1px solid rgba(173, 203, 225, .2);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            transform: scale(.86);
            opacity: .45;
            transition: transform .34s cubic-bezier(.22,.61,.36,1), opacity .34s ease, box-shadow .34s ease, border-color .34s ease, filter .34s ease;
            filter: saturate(.85);
        }
        .lost-coverflow-slide.is-active .lost-coverflow-card {
            transform: scale(1);
            opacity: 1;
            border-color: rgba(95, 173, 255, .65);
            box-shadow: 0 22px 46px rgba(0, 0, 0, 0.72);
            filter: none;
            z-index: 2;
        }
        .lost-coverflow-slide:not(.is-active) .lost-carousel-img {
            filter: saturate(.45) brightness(.58) blur(.4px);
        }
        .lost-coverflow-nav {
            position: absolute;
            top: 44%;
            transform: translateY(-50%);
            z-index: 5;
            width: 2.45rem;
            height: 2.45rem;
            border: 0;
            border-radius: 50%;
            padding: 0;
            background: rgba(8, 14, 22, .94);
            color: #e4f2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .5);
            border: 1px solid rgba(176, 211, 240, .3);
        }
        .lost-coverflow-nav:disabled {
            opacity: 0.25;
            pointer-events: none;
        }
        .lost-coverflow-prev {
            left: .3rem;
        }
        .lost-coverflow-next {
            right: .3rem;
        }
        .lost-coverflow-nav .bi {
            font-size: 1.2rem;
            line-height: 1;
        }
        .lost-del-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            padding: 0;
            line-height: 20px;
            font-size: 13px;
            z-index: 20;
            pointer-events: auto;
        }
        .paint-mobile-date {
            min-width: 76px;
            white-space: nowrap;
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .paint-mobile-date-head {
            text-align: right;
            padding-left: .1rem !important;
            padding-right: .1rem !important;
        }
        .paint-mobile-date-native-wrap {
            position: relative;
            width: 100%;
            min-height: 2rem;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .paint-mobile-date .js-mobile-date-display {
            width: 100%;
            max-width: 100%;
            font-size: .8rem;
            padding: .2rem .28rem;
            min-height: 2rem;
            margin-left: auto;
            text-align: center;
            pointer-events: none;
        }
        .paint-mobile-date .js-mobile-date-picker {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            font-size: 16px;
            box-sizing: border-box;
        }
        .paint-mobile-date .js-mobile-date-display.has-finish {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }
        .paint-mobile-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            padding: 0 .15rem .35rem;
            font-size: .78rem;
            color: #9fb0c0;
        }
        .paint-mobile-lost-pane {
            padding-top: 1.45rem;
            min-height: calc(100dvh - 145px);
            display: flex;
            flex-direction: column;
            background: #323C4D;
            border-radius: .7rem;
            padding-left: .2rem;
            padding-right: .2rem;
        }
        .paint-mobile-lost-pane .paint-mobile-card {
            background: transparent;
            border-color: rgba(190, 209, 228, .2);
        }
        .paint-mobile-lost-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin: 0 0 .45rem;
            padding: 0 .2rem;
        }
        .paint-mobile-lost-badge {
            font-size: .74rem;
            letter-spacing: .03em;
            color: #a8c7e1;
            background: rgba(10, 19, 28, .74);
            border: 1px solid rgba(132, 175, 206, .26);
            border-radius: 999px;
            padding: .18rem .5rem;
        }
        .paint-mobile-lost-hint {
            font-size: .7rem;
            color: #8ea5ba;
            white-space: nowrap;
        }
        .paint-mobile-lost-dots {
            display: flex;
            justify-content: center;
            gap: .35rem;
            margin-top: .25rem;
            padding-bottom: .15rem;
        }
        .paint-mobile-lost-dot {
            width: .4rem;
            height: .4rem;
            border-radius: 50%;
            background: rgba(146, 175, 201, .34);
            transition: transform .2s ease, background-color .2s ease;
        }
        .paint-mobile-lost-dot.is-active {
            background: #61b2ff;
            transform: scale(1.25);
        }
        .paint-mobile-lost-actions {
            margin-top: 50px;
            padding-top: 0;
        }
        .paint-mobile-lost-actionbar {
            border-radius: .9rem;
            border: 1px solid rgba(160, 197, 226, .24);
            background:
                linear-gradient(180deg, rgba(10, 16, 24, .96), rgba(9, 14, 21, .86));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.05), 0 10px 28px rgba(0,0,0,.35);
            padding: .45rem;
        }
        .paint-mobile-lost-add-btn {
            width: 100%;
            border-radius: .68rem;
            border: 1px solid rgba(96, 178, 255, .42);
            background: linear-gradient(135deg, #17324a, #254f75);
            color: #e8f4ff;
            font-weight: 600;
            letter-spacing: .02em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 2.2rem;
        }
        .paint-mobile-lost-add-btn:active,
        .paint-mobile-lost-add-btn:focus {
            color: #fff;
            border-color: rgba(120, 198, 255, .62);
            box-shadow: 0 0 0 .15rem rgba(70, 157, 235, .24);
        }
    </style>
@endsection

@section('content')
    @php
        $activeTab = $activeTab ?? 'wo';
        $rows = $rows ?? collect();
        $lostParts = $lostParts ?? collect();
        $fmt = static function ($d) {
            if (!$d) {
                return '—';
            }

            return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
        };
    @endphp

    <div class="container-fluid paint-mobile-wrap">
        @if($activeTab === 'wo')
            <div class="paint-mobile-card">
                <div class="paint-mobile-toolbar">
                    <label class="d-inline-flex align-items-center gap-1 m-0">
                        <input type="checkbox" id="js-hide-closed-rows">
                        <span>Hide closed rows</span>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-sm paint-mobile-table" id="js-mobile-paint-table">
                        <colgroup>
                            <col style="width:34px">
                            <col style="width:64px">
                            <col>
                            <col style="width:104px">
                            <col style="width:104px">
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="paint-mobile-col-queue" aria-label="Position"></th>
                            <th>WO</th>
                            <th>Detail</th>
                            <th class="paint-mobile-date-head">Start</th>
                            <th class="paint-mobile-date-head">Finish</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $wo = $row->workorder;
                                $editTp = $row->edit_paint_process ?? null;
                                $startYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                                $finishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                                $startDisp = $editTp?->date_start
                                    ? ($editTp->date_start->format('d') . '.' . strtolower($editTp->date_start->format('M')) . '.' . $editTp->date_start->format('Y'))
                                    : '';
                                $finishDisp = $editTp?->date_finish
                                    ? ($editTp->date_finish->format('d') . '.' . strtolower($editTp->date_finish->format('M')) . '.' . $editTp->date_finish->format('Y'))
                                    : '';
                                $lineStart = $editTp?->date_start ?? $row->date_start;
                                $lineFinish = $editTp?->date_finish ?? $row->date_finish;
                                $dataStartYmd = $lineStart ? $lineStart->format('Y-m-d') : '';
                                $dataFinishYmd = $lineFinish ? $lineFinish->format('Y-m-d') : '';
                                $qp = $wo->paint_queue_order !== null ? $row->paint_queue_position : null;
                                $isMaster = (bool) ($row->is_queue_master ?? false);
                                $queueDisplay = ($qp !== null && $isMaster) ? str_pad((string) $qp, 2, '0', STR_PAD_LEFT) : ($qp !== null ? '' : '—');
                                $woDigits = (string) ((int) $wo->number);
                                $woPrefix = mb_substr($woDigits, 0, 3);
                                $woTail = mb_substr($woDigits, 3);
                            @endphp
                            <tr class="js-paint-row {{ $isMaster ? 'paint-mobile-group-start' : 'paint-mobile-group-follow' }}"
                                data-wo-number="{{ (int) $wo->number }}"
                                data-wo-id="{{ (int) $wo->id }}"
                                data-is-master="{{ $isMaster ? 1 : 0 }}"
                                data-owner-user-id="{{ $isMaster && $wo->user_id ? (int) $wo->user_id : '' }}"
                                data-owner-name="{{ $isMaster && $wo->user ? e($wo->user->name) : '' }}"
                                data-detail-label="{{ e($row->detail_label ?? '') }}"
                                data-sort-order="{{ (int) $loop->index }}"
                                data-queue-pos="{{ $qp !== null ? (int) $qp : '' }}"
                                data-start-ymd="{{ $dataStartYmd }}"
                                data-finish-ymd="{{ $dataFinishYmd }}">
                                <td class="paint-mobile-col-queue text-info js-queue-cell">{{ $queueDisplay }}</td>
                                <td>
                                    <span class="paint-mobile-wo-label {{ $isMaster ? 'paint-mobile-wo-open-details js-mobile-paint-open-details' : '' }}">
                                        <span class="paint-mobile-wo-tail">w</span><span class="paint-mobile-wo-prefix">{{ $woPrefix }}</span><span class="paint-mobile-wo-tail">{{ $woTail }}</span>
                                    </span>
                                </td>
                                <td class="paint-mobile-col-detail text-secondary" title="{{ $row->detail_label ?? '' }}">{{ $row->detail_label ?? '—' }}</td>
                                <td class="paint-mobile-date">
                                    @if($editTp)
                                        <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-paint-date-form m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="from_paint_index" value="1">
                                            <input type="hidden" name="date_start" value="{{ $startYmd }}" class="js-mobile-date-real">
                                            <div class="paint-mobile-date-native-wrap" aria-label="Set start date">
                                                <input type="text"
                                                       class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $startYmd !== '' ? 'has-finish' : '' }}"
                                                       value="{{ $startDisp }}"
                                                       placeholder="Tap"
                                                       readonly
                                                       inputmode="none"
                                                       autocomplete="off"
                                                       tabindex="-1">
                                                <input type="date"
                                                       value="{{ $startYmd }}"
                                                       class="js-mobile-date-picker"
                                                       tabindex="0"
                                                       aria-label="Start date">
                                            </div>
                                        </form>
                                    @else
                                        {{ $fmt($row->date_start) }}
                                    @endif
                                </td>
                                <td class="paint-mobile-date">
                                    @if($editTp)
                                        <form method="POST" action="{{ route('tdrprocesses.updateDate', $editTp) }}" class="js-mobile-paint-date-form m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="from_paint_index" value="1">
                                            <input type="hidden" name="date_finish" value="{{ $finishYmd }}" class="js-mobile-date-real">
                                            <div class="paint-mobile-date-native-wrap" aria-label="Set finish date">
                                                <input type="text"
                                                       class="form-control form-control-sm bg-dark text-light js-mobile-date-display {{ $finishYmd !== '' ? 'has-finish' : '' }}"
                                                       value="{{ $finishDisp }}"
                                                       placeholder="Tap"
                                                       readonly
                                                       inputmode="none"
                                                       autocomplete="off"
                                                       tabindex="-1">
                                                <input type="date"
                                                       value="{{ $finishYmd }}"
                                                       class="js-mobile-date-picker"
                                                       tabindex="0"
                                                       aria-label="Finish date">
                                            </div>
                                        </form>
                                    @else
                                        {{ $fmt($row->date_finish) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-3">No paint workorders.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal fade" id="mobilePaintDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light border-secondary">
                        <div class="modal-header border-secondary py-2">
                            <h6 class="modal-title">
                                WO <span class="js-mobile-details-wo text-info"></span>
                            </h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="mobilePaintOwnerUserId">
                            <div class="mb-3">
                                <div class="paint-mobile-detail-row">
                                    <span class="paint-mobile-detail-label">Owner</span>
                                    <span class="paint-mobile-detail-value js-mobile-details-owner">—</span>
                                </div>
                                <div class="paint-mobile-detail-row">
                                    <span class="paint-mobile-detail-label">Detail</span>
                                    <span class="paint-mobile-detail-value js-mobile-details-detail">—</span>
                                </div>
                            </div>
                            <textarea id="mobilePaintOwnerMessage"
                                      class="form-control bg-dark text-light border-secondary"
                                      rows="4"
                                      maxlength="1000"
                                      placeholder="Message to owner..."></textarea>
                            <div class="small text-danger mt-2 d-none js-mobile-owner-msg-error"></div>
                            <div class="small text-success mt-2 d-none js-mobile-owner-msg-ok">Sent</div>
                        </div>
                        <div class="modal-footer border-secondary py-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-info btn-sm js-mobile-owner-msg-send">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="paint-mobile-lost-pane">
            <div class="paint-mobile-card mb-1">
                @if($lostParts->isEmpty())
                    <div class="text-secondary small">No lost parts recorded.</div>
                @else
                    <div class="paint-mobile-lost-header">
                        <span class="paint-mobile-lost-badge">{{ $lostParts->count() }} {{ $lostParts->count() === 1 ? 'item' : 'items' }}</span>
                        <span class="paint-mobile-lost-hint">Swipe cards</span>
                    </div>
                    <div class="lost-coverflow-wrap">
                        <div class="lost-coverflow-track js-lost-coverflow-track" id="mobileLostCoverflow">
                            @foreach($lostParts as $lost)
                                @php
                                    $big = $lost->getFirstMediaBigUrl('lost');
                                    $thumb = $lost->getFirstMediaThumbnailUrl('lost');
                                    $imgSrc = ($thumb !== null && $thumb !== '') ? $thumb : $big;
                                    $caption = trim($lost->part_number . (($lost->serial_number ?? '') !== '' ? ' · S/N: ' . $lost->serial_number : ''));
                                @endphp
                                <div class="lost-coverflow-slide {{ $loop->first ? 'is-active' : '' }}">
                                    <div class="lost-coverflow-card">
                                        <div class="position-relative">
                                            <img src="{{ $imgSrc }}"
                                                 draggable="false"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="lost-carousel-img js-lost-fancybox-trigger"
                                                 alt="{{ $lost->part_number }}"
                                                 data-big="{{ $big }}"
                                                 data-caption="{{ $caption }}">
                                            <form method="POST"
                                                  action="{{ route('mobile.paint.lost.destroy', $lost) }}"
                                                  class="m-0 js-lost-delete-form"
                                                  data-no-spinner>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger lost-del-btn js-lost-delete-btn"
                                                        aria-label="Delete"
                                                        title="Delete">&times;</button>
                                            </form>
                                        </div>
                                        <div class="px-2 pt-2 pb-2">
                                            <div class="lost-carousel-title">{{ $lost->part_number }}</div>
                                            <div class="lost-carousel-subtitle">
                                                @if(($lost->serial_number ?? '') !== '')
                                                    S/N: {{ $lost->serial_number }}
                                                @elseif(($lost->comment ?? '') !== '')
                                                    {{ $lost->comment }}
                                                @else
                                                    &nbsp;
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="paint-mobile-lost-dots js-lost-coverflow-dots" aria-hidden="true">
                            @foreach($lostParts as $lost)
                                <span class="paint-mobile-lost-dot {{ $loop->first ? 'is-active' : '' }}"></span>
                            @endforeach
                        </div>
                        <button type="button"
                                class="lost-coverflow-nav lost-coverflow-prev js-lost-coverflow-prev"
                                aria-label="Previous slide">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </button>
                        <button type="button"
                                class="lost-coverflow-nav lost-coverflow-next js-lost-coverflow-next"
                                aria-label="Next slide">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </div>

            <div class="paint-mobile-lost-actions">
                <div class="paint-mobile-lost-actionbar">
                    <button type="button"
                            class="paint-mobile-lost-add-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#mobilePaintLostAddModal">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add lost part</span>
                    </button>
                </div>
            </div>

            </div>

            <div class="modal fade" id="mobilePaintLostAddModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light border-secondary">
                        <form id="mobilePaintLostAddForm"
                              method="POST"
                              action="{{ route('mobile.paint.lost.store') }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header border-secondary py-2">
                                <h6 class="modal-title">Add lost part</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Part number</label>
                                    <input type="text" name="part_number" class="form-control form-control-sm" required maxlength="255">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Serial #</label>
                                    <input type="text" name="serial_number" class="form-control form-control-sm" maxlength="255">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Comment</label>
                                    <input type="text" name="comment" class="form-control form-control-sm" maxlength="2000">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Photo</label>
                                    <input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm" required>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary py-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-sm js-mobile-paint-lost-save">
                                    <span class="js-mobile-paint-lost-save-label">Save</span>
                                    <span class="js-mobile-paint-lost-save-progress spinner-border spinner-border-sm d-none ms-1" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
        <div class="paint-mobile-bottom-spacer" aria-hidden="true"></div>
    </div>
@endsection

@section('scripts')
    <script>
        function formatMobilePaintDateYmd(ymd) {
            const s = String(ymd || '').trim();
            if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                return '';
            }
            const parts = s.split('-');
            const y = Number.parseInt(parts[0], 10);
            const m = Number.parseInt(parts[1], 10) - 1;
            const d = Number.parseInt(parts[2], 10);
            const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            if (m < 0 || m > 11 || Number.isNaN(y) || Number.isNaN(d)) {
                return '';
            }
            return String(d).padStart(2, '0') + '.' + months[m] + '.' + y;
        }

        function mobilePaintLocalTodayYmd() {
            const n = new Date();
            return n.getFullYear()
                + '-' + String(n.getMonth() + 1).padStart(2, '0')
                + '-' + String(n.getDate()).padStart(2, '0');
        }

        function mobilePaintRevertPhantomEmptyChange(input, real, display) {
            input.value = '';
            if (real) {
                real.value = '';
            }
            if (display) {
                display.value = '';
                display.classList.remove('has-finish');
            }
            delete input.dataset.openedAt;
        }

        (function initMobilePaintLostModal() {
            const el = document.getElementById('mobilePaintLostAddModal');
            if (!el || !window.bootstrap || typeof window.bootstrap.Modal !== 'function') {
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(el, {
                focus: false,
                backdrop: true
            });
        })();

        (function initMobilePaintLostSave() {
            const btn = document.querySelector('.js-mobile-paint-lost-save');
            const form = document.getElementById('mobilePaintLostAddForm');
            if (!btn || !form) {
                return;
            }
            const labelEl = btn.querySelector('.js-mobile-paint-lost-save-label');
            const progressEl = btn.querySelector('.js-mobile-paint-lost-save-progress');

            form.addEventListener('submit', function (e) {
                if (form.dataset.mobileLostSubmitting === '1') {
                    e.preventDefault();
                    return;
                }
                form.dataset.mobileLostSubmitting = '1';
                btn.disabled = true;
                if (labelEl) {
                    labelEl.textContent = 'Saving…';
                }
                if (progressEl) {
                    progressEl.classList.remove('d-none');
                }
            });
        })();

        document.addEventListener('pointerdown', function (e) {
            const picker = e.target.closest('.js-mobile-date-picker');
            if (!picker) {
                return;
            }
            picker.dataset.openedAt = String(Date.now());
        }, true);

        document.addEventListener('focusin', function (e) {
            const picker = e.target.closest('.js-mobile-date-picker');
            if (!picker) {
                return;
            }
            if (!picker.dataset.openedAt) {
                picker.dataset.openedAt = String(Date.now());
            }
        }, true);

        document.addEventListener('change', async function (e) {
            const input = e.target;
            if (!input || !input.classList.contains('js-mobile-date-picker')) return;

            const form = input.closest('.js-mobile-paint-date-form');
            if (!form) return;
            const row = form.closest('.js-paint-row');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const real = form.querySelector('.js-mobile-date-real');
            const display = form.querySelector('.js-mobile-date-display');
            const prevRealValue = real ? real.value : '';
            const prevDisplayValue = display ? display.value : '';
            const prevHasFinish = display ? display.classList.contains('has-finish') : false;
            const openedAt = parseInt(String(input.dataset.openedAt || '0'), 10);
            const msSinceOpen = openedAt ? (Date.now() - openedAt) : 999999;
            // Почему «второй раз» ведёт себя нормально: prevRealValue уже не пустой — это не первый
            // ввод с пустого состояния, условия ниже не выполняются, уходит только реальный выбор.
            //
            // На iOS/WebKit при ПЕРВОМ вводе в пустое поле часто приходит ложный change с «сегодня»
            // сразу после открытия календаря (дефолт колеса), иногда через 200–400 ms — короткого
            // порога 80 ms недостаточно. Отсекаем быстрый «сегодня» из пустого; осознанный выбор
            // «сегодня» обычно позже ~450 ms или со второго открытия.
            if (!prevRealValue && input.value) {
                const todayYmd = mobilePaintLocalTodayYmd();
                if (input.value === todayYmd && msSinceOpen < 450) {
                    mobilePaintRevertPhantomEmptyChange(input, real, display);
                    return;
                }
                if (msSinceOpen < 85) {
                    mobilePaintRevertPhantomEmptyChange(input, real, display);
                    return;
                }
            }
            if (real) real.value = input.value || '';
            if (display) {
                display.value = input.value ? formatMobilePaintDateYmd(input.value) : '';
                display.classList.toggle('has-finish', !!input.value);
            }
            const formData = new FormData(form);

            if (typeof safeShowSpinner === 'function') {
                safeShowSpinner();
            }

            input.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.success === false) {
                    let msg = 'Error';
                    if (payload?.errors) {
                        const firstKey = Object.keys(payload.errors)[0];
                        if (firstKey && payload.errors[firstKey]?.[0]) {
                            msg = payload.errors[firstKey][0];
                        }
                    }
                    throw new Error(msg);
                }

                if (payload.paint_queue_changed) {
                    window.location.reload();
                    return;
                }

                if (row) {
                    const startReal = row.querySelector('input[name="date_start"].js-mobile-date-real');
                    const finishReal = row.querySelector('input[name="date_finish"].js-mobile-date-real');
                    row.dataset.startYmd = startReal?.value || '';
                    row.dataset.finishYmd = finishReal?.value || '';

                    if ((real?.name || '') === 'date_finish' && (real?.value || '') !== '') {
                        const woId = row?.dataset?.woId;
                        const targets = woId
                            ? document.querySelectorAll('tr.js-paint-row[data-wo-id="' + woId + '"]')
                            : [row];
                        targets.forEach((r) => {
                            r.dataset.queuePos = '';
                            const queueCell = r.querySelector('.js-queue-cell');
                            if (queueCell) {
                                queueCell.textContent = r.dataset.isMaster === '1' ? '—' : '';
                            }
                        });
                    }
                    normalizeAndSortPaintRows();
                }
            } catch (err) {
                if (real) real.value = prevRealValue;
                if (input) {
                    input.value = prevRealValue;
                    delete input.dataset.openedAt;
                }
                if (display) {
                    display.value = prevDisplayValue;
                    display.classList.toggle('has-finish', prevHasFinish);
                }
                if (row) {
                    const startReal = row.querySelector('input[name="date_start"].js-mobile-date-real');
                    const finishReal = row.querySelector('input[name="date_finish"].js-mobile-date-real');
                    row.dataset.startYmd = startReal?.value || '';
                    row.dataset.finishYmd = finishReal?.value || '';
                }
                const msg = err?.message || 'Save failed';
                if (typeof window.notifyError === 'function') {
                    window.notifyError(msg, 3500);
                } else {
                    console.error(msg);
                }
            } finally {
                input.disabled = false;
                if (typeof safeHideSpinner === 'function') {
                    safeHideSpinner();
                }
            }
        });

        (function initMobilePaintDetailsModal() {
            const modalEl = document.getElementById('mobilePaintDetailsModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const userIdEl = document.getElementById('mobilePaintOwnerUserId');
            const textEl = document.getElementById('mobilePaintOwnerMessage');
            const woEl = modalEl.querySelector('.js-mobile-details-wo');
            const ownerEl = modalEl.querySelector('.js-mobile-details-owner');
            const detailEl = modalEl.querySelector('.js-mobile-details-detail');
            const errEl = modalEl.querySelector('.js-mobile-owner-msg-error');
            const okEl = modalEl.querySelector('.js-mobile-owner-msg-ok');
            const sendBtn = modalEl.querySelector('.js-mobile-owner-msg-send');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function showError(message) {
                if (!errEl) return;
                errEl.textContent = message || 'Send failed';
                errEl.classList.remove('d-none');
                okEl?.classList.add('d-none');
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-mobile-paint-open-details');
                if (!btn) return;
                e.preventDefault();

                const row = btn.closest('.js-paint-row');
                if (!row) return;

                const ownerId = row.getAttribute('data-owner-user-id') || '';
                const ownerName = row.getAttribute('data-owner-name') || '—';
                const detail = row.getAttribute('data-detail-label') || '—';

                if (userIdEl) userIdEl.value = ownerId;
                if (textEl) textEl.value = '';
                if (woEl) woEl.textContent = row.getAttribute('data-wo-number') || '';
                if (ownerEl) ownerEl.textContent = ownerName;
                if (detailEl) detailEl.textContent = detail;
                if (sendBtn) sendBtn.disabled = !ownerId;
                errEl?.classList.add('d-none');
                okEl?.classList.add('d-none');
                modal.show();
            }, true);

            sendBtn?.addEventListener('click', async function () {
                const userId = userIdEl?.value || '';
                const message = (textEl?.value || '').trim();
                if (!userId) return showError('Owner is missing');
                if (!message) return showError('Type a message');

                sendBtn.disabled = true;
                errEl?.classList.add('d-none');
                okEl?.classList.add('d-none');

                try {
                    const res = await fetch('/admin/messages/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({user_ids: [userId], message})
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                        return showError(data.message || 'Send failed');
                    }

                    okEl?.classList.remove('d-none');
                    if (textEl) textEl.value = '';
                    setTimeout(function () {
                        modal.hide();
                    }, 650);
                } catch (_) {
                    showError('Send failed');
                } finally {
                    sendBtn.disabled = false;
                }
            });
        })();

        function normalizeAndSortPaintRows() {
            const tbody = document.querySelector('#js-mobile-paint-table tbody');
            if (!tbody) return;

            function queuePosNum(r) {
                const n = Number.parseInt(String(r.dataset.queuePos || '').trim(), 10);
                return Number.isFinite(n) ? n : 0;
            }

            const rows = Array.from(tbody.querySelectorAll('tr.js-paint-row'));
            function tieBreakQueued(a, b) {
                const d = queuePosNum(a) - queuePosNum(b);
                if (d !== 0) {
                    return d;
                }
                const woCmp = Number.parseInt(String(a.dataset.woNumber || '0'), 10) - Number.parseInt(String(b.dataset.woNumber || '0'), 10);
                if (woCmp !== 0) {
                    return woCmp;
                }
                return (parseInt(String(a.dataset.sortOrder || '0'), 10) || 0) - (parseInt(String(b.dataset.sortOrder || '0'), 10) || 0);
            }

            const queued = rows
                .filter((r) => queuePosNum(r) > 0)
                .sort(tieBreakQueued);
            const unqueued = rows
                .filter((r) => queuePosNum(r) <= 0)
                .sort((a, b) => {
                    const woCmp = Number.parseInt(String(b.dataset.woNumber || '0'), 10) - Number.parseInt(String(a.dataset.woNumber || '0'), 10);
                    if (woCmp !== 0) {
                        return woCmp;
                    }
                    return (parseInt(String(a.dataset.sortOrder || '0'), 10) || 0) - (parseInt(String(b.dataset.sortOrder || '0'), 10) || 0);
                });

            let queueSlot = 0;
            let lastWoId = null;
            queued.forEach((r) => {
                const wid = String(r.dataset.woId || '');
                if (wid !== lastWoId) {
                    lastWoId = wid;
                    queueSlot++;
                }
                r.dataset.queuePos = String(queueSlot);
                const queueCell = r.querySelector('.js-queue-cell');
                if (queueCell) {
                    queueCell.textContent = r.dataset.isMaster === '1' ? String(queueSlot).padStart(2, '0') : '';
                }
            });

            unqueued.forEach((r) => {
                r.dataset.queuePos = '';
                const queueCell = r.querySelector('.js-queue-cell');
                if (queueCell) queueCell.textContent = r.dataset.isMaster === '1' ? '—' : '';
            });

            const ordered = queued.concat(unqueued);
            ordered.forEach((r) => tbody.appendChild(r));
            applyClosedFilter();
        }

        function applyClosedFilter() {
            const hideClosed = document.getElementById('js-hide-closed-rows');
            const needHide = !!hideClosed?.checked;
            document.querySelectorAll('#js-mobile-paint-table tbody tr.js-paint-row').forEach((row) => {
                const closed = (row.dataset.startYmd || '') !== '' && (row.dataset.finishYmd || '') !== '';
                row.style.display = (needHide && closed) ? 'none' : '';
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'js-hide-closed-rows') {
                try {
                    sessionStorage.setItem('mobile_paint_hide_closed', e.target.checked ? '1' : '0');
                } catch (_) {}
                applyClosedFilter();
            }
        });

        (function initLostFancyboxDoubleTap() {
            let lastTapAt = 0;
            document.addEventListener('click', function (e) {
                const img = e.target.closest('.js-lost-fancybox-trigger');
                if (!img) return;

                const now = Date.now();
                const delta = now - lastTapAt;
                lastTapAt = now;

                if (delta > 350) return; // open only on second tap

                if (typeof Fancybox !== 'undefined' && typeof Fancybox.show === 'function') {
                    Fancybox.show([{
                        src: img.dataset.big || img.src,
                        type: 'image',
                        caption: img.dataset.caption || ''
                    }]);
                }
            });
        })();

        document.addEventListener('submit', async function (e) {
            const form = e.target;
            if (!form || !form.classList.contains('js-lost-delete-form')) return;

            e.preventDefault();

            let confirmed = false;
            if (typeof window.confirmDialog === 'function') {
                confirmed = await window.confirmDialog({
                    title: 'Delete image',
                    message: 'Delete this lost image?',
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    danger: true
                });
            } else {
                confirmed = window.confirm('Delete this lost image?');
            }

            if (!confirmed) {
                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                }
                return;
            }

            if (typeof window.safeShowSpinner === 'function') {
                window.safeShowSpinner();
            }
            form.submit();
        });

        // Не даем карусели/свайпу перехватывать нажатие на удаление.
        ['pointerdown', 'touchstart', 'click'].forEach((evtName) => {
            document.addEventListener(evtName, function (e) {
                const delBtn = e.target.closest('.js-lost-delete-btn');
                if (!delBtn) return;
                e.stopPropagation();
            }, { passive: false });
        });

        (function restoreHideClosedState() {
            const checkbox = document.getElementById('js-hide-closed-rows');
            if (!checkbox) return;
            try {
                checkbox.checked = sessionStorage.getItem('mobile_paint_hide_closed') === '1';
            } catch (_) {
                checkbox.checked = false;
            }
        })();

        normalizeAndSortPaintRows();

        (function initLostCoverflows() {
            const tracks = document.querySelectorAll('.js-lost-coverflow-track');
            if (!tracks.length) {
                return;
            }

            tracks.forEach(function (track) {
                const wrap = track.closest('.lost-coverflow-wrap');
                const slides = track.querySelectorAll('.lost-coverflow-slide');
                if (!wrap || !slides.length) {
                    return;
                }

                function updateActive() {
                    const tr = track.getBoundingClientRect();
                    const mid = tr.left + tr.width / 2;
                    let best = null;
                    let bestDist = Infinity;
                    let bestIndex = 0;
                    slides.forEach(function (slide) {
                        const r = slide.getBoundingClientRect();
                        const c = r.left + r.width / 2;
                        const d = Math.abs(c - mid);
                        if (d < bestDist) {
                            bestDist = d;
                            best = slide;
                            bestIndex = Array.prototype.indexOf.call(slides, slide);
                        }
                    });
                    slides.forEach(function (s) {
                        s.classList.toggle('is-active', s === best);
                    });
                    const dotsWrap = wrap.querySelector('.js-lost-coverflow-dots');
                    if (dotsWrap) {
                        const dots = dotsWrap.querySelectorAll('.paint-mobile-lost-dot');
                        dots.forEach(function (dot, i) {
                            dot.classList.toggle('is-active', i === bestIndex);
                        });
                    }
                }

                let raf = 0;
                function schedule() {
                    if (raf) {
                        cancelAnimationFrame(raf);
                    }
                    raf = requestAnimationFrame(updateActive);
                }

                track.addEventListener('scroll', schedule, { passive: true });
                window.addEventListener('resize', schedule);

                function scrollByStep(dir) {
                    const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth - 1);
                    const step = Math.max(120, Math.floor(track.clientWidth * 0.45));
                    if (maxScroll <= 2) {
                        return;
                    }
                    if (dir > 0) {
                        if (track.scrollLeft >= maxScroll - 6) {
                            track.scrollTo({ left: 0, behavior: 'smooth' });
                        } else {
                            track.scrollBy({ left: step, behavior: 'smooth' });
                        }
                    } else {
                        if (track.scrollLeft <= 6) {
                            track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                        } else {
                            track.scrollBy({ left: -step, behavior: 'smooth' });
                        }
                    }
                    window.setTimeout(updateActive, 450);
                }

                const prevBtn = wrap.querySelector('.js-lost-coverflow-prev');
                const nextBtn = wrap.querySelector('.js-lost-coverflow-next');
                if (prevBtn) {
                    prevBtn.addEventListener('click', function () {
                        scrollByStep(-1);
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', function () {
                        scrollByStep(1);
                    });
                }

                if (slides.length <= 1) {
                    if (prevBtn) {
                        prevBtn.style.display = 'none';
                    }
                    if (nextBtn) {
                        nextBtn.style.display = 'none';
                    }
                }

                schedule();
                window.setTimeout(schedule, 80);
                window.setTimeout(schedule, 350);
            });
        })();

    </script>
@endsection
