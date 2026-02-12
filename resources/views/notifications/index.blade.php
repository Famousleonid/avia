@extends('admin.master')

@section('content')
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="m-0">Notifications</h5>
            <form method="post" action="{{ route('notifications.readAll') }}" onsubmit="event.preventDefault();">
                <button class="btn btn-sm btn-outline-secondary" id="readAllPageBtn" type="button">Read all</button>
            </form>
        </div>

        <div class="card shadow-sm">
            <div class="list-group list-group-flush">
                @forelse($notifications as $n)
                    @php
                        $title = data_get($n->data, 'title', 'Notification');
                        $message = data_get($n->data, 'message', '');
                        $url = data_get($n->data, 'url', null);
                        $isUnread = is_null($n->read_at);
                    @endphp

                    <div class="list-group-item {{ $isUnread ? 'bg-body-tertiary' : '' }}">
                        <div class="d-flex justify-content-between gap-3">
                            <div class="w-100">
                                <div class="fw-semibold">{{ $title }}</div>
                                @if($message)
                                    <div class="text-muted small">{{ $message }}</div>
                                @endif
                                <div class="text-muted" style="font-size:12px;">{{ $n->created_at?->diffForHumans() }}</div>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                @if($url)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $url }}">Open</a>
                                @endif

                                @if($isUnread)
                                    <button class="btn btn-sm btn-outline-secondary js-read-one" data-id="{{ $n->id }}">Read</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No notifications</div>
                @endforelse
            </div>
        </div>

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    </div>

    <script>
        (function(){
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            document.querySelectorAll('.js-read-one').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    await fetch(`{{ url('/notifications') }}/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({})
                    });
                    location.reload();
                });
            });

            document.getElementById('readAllPageBtn')?.addEventListener('click', async () => {
                await fetch(`{{ route('notifications.readAll') }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({})
                });
                location.reload();
            });
        })();
    </script>
@endsection
