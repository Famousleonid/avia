@extends('admin.master')

@section('content')
    <style>
        .notif-page-wrap{
            height: calc(100vh - 140px);
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .notif-scroll{
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            border-radius: 8px;
            background: rgba(0,0,0,.15);
        }

        .notif-item{
            padding: 12px 14px;
        }

        .notif-hr{
            margin: 0;
            opacity: .15;
        }

        .notif-unread{
            background: rgba(255,255,255,.04);
        }

        .notif-chip-info{
            color: #0dcaf0;
            font-weight: 700;
        }

        .notif-text{
            white-space: normal;
            word-break: break-word;
        }
    </style>

    <div class="card shadow">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Notifications</div>

            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnReadAll">
                    Read all
                </button>

                <button class="btn btn-sm btn-outline-danger" type="button" id="btnDeleteAll">
                    Delete all ({{ $notifications->total() }})
                </button>
            </div>
        </div>

        <div class="card-body p-2 notif-page-wrap">
            <div class="notif-scroll" id="notifPageList">
                @forelse($notifications as $n)
                    @php
                        $isUnread = is_null($n->read_at);
                        $fromName = trim((string) ($n->from_name ?: 'System'));
                        $toName = trim((string) ($n->to_name ?? ''));
                        $time = $n->created_at_human ?? optional($n->created_at)->diffForHumans();
                        $text = $n->text ?? '';
                        $ui = is_array($n->ui ?? null) ? $n->ui : [];
                        $payload = is_array($n->payload ?? null) ? $n->payload : [];
                        $isProcessReady = ($n->type ?? null) === 'workorder' && ($n->event ?? null) === 'process_ready_for_next';
                        $woNo = data_get($ui, 'workorder.no') ?? data_get($payload, 'workorder_no');
                        $woOwner = data_get($ui, 'workorder.owner_name')
                            ?? data_get($ui, 'workorder.user_name')
                            ?? data_get($payload, 'workorder_user_name')
                            ?? data_get($payload, 'workorder_owner_name');
                        $processName = data_get($ui, 'process.name') ?? data_get($payload, 'process_name');
                        $previousName = data_get($ui, 'process.previous_name') ?? data_get($payload, 'previous_process_name');
                        $detail = data_get($ui, 'part.label') ?? data_get($payload, 'detail_label');
                    @endphp

                    <div class="notif-item {{ $isUnread ? 'notif-unread' : '' }}" data-notif-id="{{ $n->id }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="w-100">
                                <div class="d-flex align-items-center justify-content-between small">
                                    <div class="text-warning">
                                        From: {{ $fromName }}
                                        @if($toName)
                                            <span class="text-muted mx-1">to</span>{{ $toName }}
                                        @endif
                                    </div>
                                    <div class="text-muted">{{ $time }}</div>
                                </div>

                                @if($isProcessReady)
                                    <div class="notif-text text-light small mt-1">
                                        <span class="badge text-bg-info">PROCESS READY</span>
                                        @if($woNo)
                                            <span class="text-warning fw-semibold ms-1">WO #{{ $woNo }}{{ $woOwner ? ' '.$woOwner : '' }}</span>
                                        @endif
                                    </div>
                                    @if($detail)
                                        <div class="notif-text text-light small mt-1">{{ $detail }}</div>
                                    @endif
                                    <div class="notif-text text-light small mt-1">
                                        Send to
                                        @if($processName)
                                            <span class="notif-chip-info">{{ $processName }}</span>
                                        @else
                                            next process
                                        @endif
                                        .
                                        @if($previousName)
                                            Previous process <span class="notif-chip-info">{{ $previousName }}</span> was returned.
                                        @endif
                                    </div>
                                @elseif($text)
                                    <div class="notif-text text-light small mt-1">
                                        {{ $text }}
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex flex-column gap-1">
                                @if($isUnread)
                                    <button class="btn btn-sm btn-outline-secondary js-read" type="button">
                                        Read
                                    </button>
                                @endif

                                <button class="btn btn-sm btn-outline-danger js-del" type="button">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="notif-hr">
                @empty
                    <div class="p-3 text-muted small">No notifications</div>
                @endforelse
            </div>

            <div class="pt-2 px-1">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wrap = document.getElementById('notifPageList');
            if (!wrap) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            async function postRead(id) {
                await fetch(`{{ url('/notifications') }}/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({}),
                    spinner: false
                });
            }

            async function delNotif(id) {
                await fetch(`{{ url('/notifications') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    spinner: false
                });
            }

            // Read (на странице просто делает визуально "прочитанным", не удаляем)
            wrap.addEventListener('click', async (e) => {
                const readBtn = e.target.closest('.js-read');
                if (!readBtn) return;

                const item = e.target.closest('[data-notif-id]');
                const id = item?.dataset?.notifId;
                if (!id) return;

                await postRead(id);

                // визуально: снять подсветку, убрать кнопку Read
                item.classList.remove('notif-unread');
                readBtn.remove();
            });

            // Delete
            wrap.addEventListener('click', async (e) => {
                const delBtn = e.target.closest('.js-del');
                if (!delBtn) return;

                const item = e.target.closest('[data-notif-id]');
                const id = item?.dataset?.notifId;
                if (!id) return;

                if (!confirm('Delete this notification?')) return;

                await delNotif(id);

                // удалить item + hr после него
                const hr = item.nextElementSibling && item.nextElementSibling.classList.contains('notif-hr')
                    ? item.nextElementSibling
                    : null;

                item.remove();
                if (hr) hr.remove();

                // если список пустой
                if (!wrap.querySelector('[data-notif-id]')) {
                    wrap.innerHTML = `<div class="p-3 text-muted small">No notifications</div>`;
                }
            });

            // Read all
            document.getElementById('btnReadAll')?.addEventListener('click', async () => {
                await fetch(`{{ route('notifications.readAll') }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({}),
                    spinner: false
                });

                // снять подсветку и убрать все кнопки Read
                wrap.querySelectorAll('.notif-item').forEach(item => item.classList.remove('notif-unread'));
                wrap.querySelectorAll('.js-read').forEach(btn => btn.remove());
            });

            // Delete all
            document.getElementById('btnDeleteAll')?.addEventListener('click', async () => {
                if (!confirm('Delete ALL notifications?')) return;

                const r = await fetch(`{{ route('notifications.deleteAll') }}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    spinner: false
                });

                if (!r.ok) {
                    console.error('Delete all failed', r.status);
                    return;
                }

                wrap.innerHTML = `<div class="p-3 text-muted small">No notifications</div>`;
            });



        })();
    </script>



@endsection
