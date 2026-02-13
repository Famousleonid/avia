{{-- resources/views/partials/notifications-bell.blade.php --}}

@php
    $unread = auth()->user()->unreadNotifications()->count();
    $notifId = 'notifDropdown_' . (auth()->id() ?? '0'); // чтобы не конфликтовало, если где-то ещё вставишь
@endphp

<style>
    .sidebar-bell i {
        color: #FFCC00;
        transition: transform .15s ease, color .15s ease;
        position: relative;
    }

    .sidebar-bell .dropdown-toggle::after {
        display: none !important;
    }

    .sidebar-bell a:hover i {
        color: #FFD54F;
        transform: scale(1.2);

    }

    html[data-sidebar-collapsed="1"] .brand-link {
        display: none !important;
    }

    html[data-sidebar-collapsed="1"] #sidebarMenu .border-bottom.p-3 {
        justify-content: center !important;
    }

    /* favicon тоже можно убрать */
    html[data-sidebar-collapsed="1"] #sidebarMenu img[alt="Logo"] {
        display: none !important;
    }

    /* колокольчик центрируем */
    html[data-sidebar-collapsed="1"] .sidebar-bell {
        display: flex !important;
        justify-content: center;
        width: 100%;
    }

    .sidebar-bell .dropdown-menu {
        z-index: 999999 !important;
    }

    .notif-badge {
        top: 0;
        right: -2px;
        font-size: 9px;
        padding: 2px 5px;
        min-width: 16px;
        height: 16px;
        line-height: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    html[data-sidebar-collapsed="1"] #notifBadge{
        display: flex !important;
    }

</style>


<div class="dropdown sidebar-bell">
    <a class="nav-link position-relative dropdown-toggle"
       href="#"
       id="{{ $notifId }}"
       role="button"
       data-bs-toggle="dropdown"
       data-bs-auto-close="outside"
       aria-expanded="false"
       data-no-spinner
       onclick="return false;">
        <i class="bi bi-bell fs-5"></i>

        <span id="notifBadge"
              class="position-absolute badge rounded-pill bg-danger notif-badge"
              style="{{ $unread ? '' : 'display:none' }}">
              {{ $unread }}
        </span>
    </a>

    <div class="dropdown-menu dropdown-menu-end p-0 shadow notif-menu"
         aria-labelledby="{{ $notifId }}"
         style="min-width: 360px; max-width: 420px;">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <div class="fw-semibold">Notifications</div>
            <button class="btn btn-sm btn-outline-secondary" id="notifReadAllBtn" type="button">
                Read all
            </button>
        </div>

        <div id="notifList" style="max-height: 380px; overflow:auto;">
            <div class="p-3 text-muted small">Loading…</div>
        </div>

        <div class="border-top">
            <a class="dropdown-item text-center py-2" href="{{ route('notifications.index') }}">
                View all
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        // защита от двойного подключения partial (напр. desktop+mobile)
        if (window.__notifBellBound) return;
        window.__notifBellBound = true;

        const badge = document.getElementById('notifBadge');
        const list = document.getElementById('notifList');
        const readAllBtn = document.getElementById('notifReadAllBtn');

        if (!badge || !list) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function setBadge(count) {
            const n = Number(count || 0);
            if (n > 0) {
                badge.style.display = '';
                badge.textContent = String(n);
            } else {
                badge.style.display = 'none';
                badge.textContent = '0';
            }
        }

        function escapeHtml(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        async function fetchCount() {
            const r = await fetch('{{ route('notifications.unreadCount') }}', {
                headers: {'Accept': 'application/json'},
                spinner: false
            });
            const data = await r.json();
            setBadge(data.count || 0);
        }

        function renderItems(items) {
            if (!items || items.length === 0) {
                list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
                return;
            }

            list.innerHTML = items.map(n => {
                const id   = escapeHtml(n.id);
                const from = n.from_name ? `From: ${escapeHtml(n.from_name)}` : '';
                const time = escapeHtml(n.created_at_human);
                const msg  = escapeHtml(n.text);
                const url  = n.url ? escapeHtml(n.url) : '';

                return `
  <div class="px-3 py-2 border-bottom" data-notif-id="${id}">
    <div class="d-flex justify-content-between align-items-start gap-2">
      <div class="w-100">
        <div class="d-flex align-items-center justify-content-between small">
          <div class="text-warning">${from}</div>
          <div class="text-muted">${time}</div>
        </div>
        ${msg ? `<div class="text-light small mt-1">${msg}</div>` : ``}
      </div>
      <div class="d-flex flex-column gap-1">
        ${url ? `<a class="btn btn-sm btn-outline-primary" href="${url}">Open</a>` : ``}
        <button class="btn btn-sm btn-outline-secondary js-mark-read" type="button">Read</button>
      </div>
    </div>
  </div>`;
            }).join('');

            list.querySelectorAll('.js-mark-read').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const wrap = e.target.closest('[data-notif-id]');
                    const id = wrap?.dataset?.notifId;
                    if (!id) return;

                    // 1) пометили прочитанным
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

                    // 2) сразу убрали из списка (без лишней перезагрузки)
                    wrap.remove();

                    // 3) если список стал пустой — покажем заглушку
                    if (!list.querySelector('[data-notif-id]')) {
                        list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
                    }

                    // 4) обновили счётчик
                    await fetchCount();
                });
            });
        }

        async function loadLatest() {
            const r = await fetch('{{ route('notifications.latest') }}', {
                headers: {'Accept': 'application/json'},
                spinner: false
            });
            const data = await r.json();
            renderItems(data.items || []);
        }

        // при открытии dropdown обновляем список и счётчик
        const dropdownEl = document.getElementById('{{ $notifId }}');
        dropdownEl?.addEventListener('show.bs.dropdown', async () => {
            list.innerHTML = `<div class="p-3 text-muted small">Loading…</div>`;
            await loadLatest();
            await fetchCount();
        });

        readAllBtn?.addEventListener('click', async () => {
            await fetch('{{ route('notifications.readAll') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({}),
                spinner: false
            });

            await loadLatest();
            setBadge(0);
        });

        // polling только счётчика
        setInterval(fetchCount, 20000);
    })();
</script>
