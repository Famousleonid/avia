{{-- resources/views/partials/notifications-bell.blade.php --}}

@php
    $unread = auth()->user()->unreadNotifications()->count();
    $notifId = 'notifDropdown_' . (auth()->id() ?? '0');
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

    html[data-sidebar-collapsed="1"] #sidebarMenu img[alt="Logo"] {
        display: none !important;
    }

    html[data-sidebar-collapsed="1"] .sidebar-bell {
        display: flex !important;
        justify-content: center;
        width: 100%;
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

    html[data-sidebar-collapsed="1"] #notifBadge {
        display: flex !important;
    }

    .notif-row-clickable {
        cursor: pointer;
    }

    .notif-row { border-left: 4px solid transparent; }
    .notif-row.sev-info    { border-left-color: #0dcaf0; }
    .notif-row.sev-success { border-left-color: #198754; }
    .notif-row.sev-warning { border-left-color: #ffc107; }
    .notif-row.sev-danger  { border-left-color: #dc3545; }

    .sidebar-bell .dropdown-menu { z-index: 2080 !important; }

</style>

<div class="dropdown sidebar-bell">
    <a class="nav-link position-relative dropdown-toggle"
       href="#"
       id="{{ $notifId }}"
       role="button"
       data-bs-toggle="dropdown"
       data-bs-auto-close="outside"
       aria-expanded="false"
       data-no-spinner>
        <i class="bi bi-bell fs-5"></i>

        <span id="notifBadge"
              class="position-absolute badge rounded-pill bg-danger notif-badge"
              style="{{ $unread ? '' : 'display:none' }}">
            {{ $unread }}
        </span>
    </a>

    <div class="dropdown-menu dropdown-menu-end p-0 shadow notif-menu"
         aria-labelledby="{{ $notifId }}"
         style="min-width: 550px; max-width: 700px;">

        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <div class="fw-semibold">Notifications</div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary"
                        type="button"
                        id="notifSettingsBtn"
                        data-no-spinner>
                    <i class="bi bi-gear"></i>
                </button>

                <button class="btn btn-sm btn-outline-secondary"
                        id="notifReadAllBtn"
                        type="button"
                        data-no-spinner>
                    Read all
                </button>
            </div>
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
        if (window.__notifBellBound) return;
        window.__notifBellBound = true;

        const badge = document.getElementById('notifBadge');
        const list = document.getElementById('notifList');
        const readAllBtn = document.getElementById('notifReadAllBtn');

        if (!badge || !list) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        let notifPage = 1;
        let notifHasMore = false;
        let notifLoading = false;
        const notifPerPage = 10;

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
            try {
                const r = await fetch('{{ route('notifications.unreadCount') }}', {
                    headers: {'Accept': 'application/json'},
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));
                setBadge(data.count || 0);
            } catch (_) {
            }
        }

        function buildMessage(n) {
            const type = String(n?.type ?? '');
            const event = String(n?.event ?? '');
            const ui = n?.ui ?? {};

            const h = (v) => escapeHtml(v ?? '');
            const has = (v) => v !== null && v !== undefined && String(v).trim() !== '';

            if (type === 'workorder') {
                const woNoRaw = ui?.workorder?.no ?? n?.payload?.workorder_no ?? n?.payload?.workorder_number ?? '';
                const woNo = has(woNoRaw) ? `#${h(woNoRaw)}` : '';
                const actor = h(ui?.actor?.name ?? n?.by_user_name ?? n?.from_name ?? '');

                if (event === 'approved') {
                    return `
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge text-bg-success">APPROVED</span>
                            <span class="text-warning fw-semibold">WO ${woNo}</span>
                        </div>
                        ${actor ? `<div class="text-muted small mt-1">by ${actor}</div>` : ``}
                    `;
                }

                const label = h(String(event || 'update').toUpperCase());
                const text = h(n?.text ?? '');

                return `
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge text-bg-secondary">${label}</span>
                        <span class="text-warning fw-semibold">WO ${woNo}</span>
                    </div>
                    ${text ? `<div class="small mt-1">${text}</div>` : ``}
                `;
            }

            if (type === 'process' && event === 'overdue') {
                const woNoRaw = ui?.workorder?.no ?? '';
                const woNo = has(woNoRaw) ? `#${h(woNoRaw)}` : '';
                const ownerRaw = ui?.workorder?.owner_name ?? '';
                const owner = has(ownerRaw) ? h(String(ownerRaw).trim()) : '';
                const woWithOwner = woNo
                    ? (owner ? `WO ${woNo} (${owner})` : `WO ${woNo}`)
                    : '';
                const pName = h(ui?.process?.name ?? '');
                const partRaw = ui?.part?.number ?? '';
                const partNo = has(partRaw) ? h(String(partRaw).trim()) : '';
                const processLine = pName && partNo ? `${pName} - ${partNo}` : (pName || partNo);
                const start = h(ui?.dates?.start ?? ui?.start ?? '');
                const std = h(ui?.std_days ?? '');
                const od = h(ui?.overdue_days ?? '');

                return `
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge text-bg-danger">OVERDUE</span>
                        ${woWithOwner ? `<span class="text-warning fw-semibold">${woWithOwner}</span>` : ``}
                    </div>

                    ${processLine ? `<div class="small mt-1">${processLine}</div>` : ``}

                    <div class="text-muted small mt-1">
                        ${start ? `Start: ${start}` : ``}
                        ${(start && (std || od)) ? `&nbsp;•&nbsp;` : ``}
                        ${std ? `Std: ${std}d` : ``}
                        ${(std && od) ? `&nbsp;•&nbsp;` : ``}
                        ${od ? `Overdue: ${od}d` : ``}
                    </div>
                `;
            }

            if (type === 'message') {
                const text = String(n?.text ?? '');
                const safe = h(text).replace(/\n/g, '<br>');

                const decorated = safe
                    .replace(/\bWO\s*#?\s*\d+\b/gi, (m) => `<span class="text-warning fw-semibold">${h(m).replace(/\s+/g, ' ').trim()}</span>`)
                    .replace(/\boverdue\b/gi, `<span class="text-danger fw-semibold">Overdue</span>`);

                return `<div class="small">${decorated}</div>`;
            }

            const text = String(n?.text ?? '');
            const safe = h(text).replace(/\n/g, '<br>');
            return safe ? `<div class="small">${safe}</div>` : `<div class="text-muted small">—</div>`;
        }

        function renderItems(items, append = false) {
            if (!append) {
                list.innerHTML = '';
            }

            if ((!items || items.length === 0) && !append) {
                list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
                return;
            }

            const html = (items || []).map(n => {
                const id = escapeHtml(n.id);
                const from = n.from_name ? `From: ${escapeHtml(n.from_name)}` : '';
                const time = escapeHtml(n.created_at_human);
                const msg = buildMessage(n);
                const url = n.url ? escapeHtml(n.url) : '';

                return `
<div class="px-3 py-2 border-bottom notif-row ${url ? 'notif-row-clickable' : ''}"
     data-notif-id="${id}"
     ${url ? `data-url="${url}"` : ''}>
  <div class="d-flex justify-content-between align-items-start gap-2">
    <div class="w-100">
      <div class="d-flex align-items-center justify-content-between small">
        <div class="text-warning">${from}</div>
        <div class="text-muted">${time}</div>
      </div>
      ${msg ? `<div class="small mt-1">${msg}</div>` : ``}
    </div>
    <div class="d-flex flex-column gap-1">
      ${url ? `<a class="btn btn-sm btn-outline-primary" href="${url}">Open</a>` : ``}
      <button class="btn btn-sm btn-outline-secondary js-mark-read" type="button">Read</button>
    </div>
  </div>
</div>`;
            }).join('');

            if (append) {
                list.insertAdjacentHTML('beforeend', html);
            } else {
                list.innerHTML = html;
            }

            list.querySelectorAll('.notif-loadmore-wrap').forEach(el => el.remove());

            if (notifLoading) {
                list.insertAdjacentHTML('beforeend', `
                    <div class="p-2 text-center text-muted small notif-loadmore-wrap">
                        Loading…
                    </div>
                `);
            } else if (notifHasMore) {
                list.insertAdjacentHTML('beforeend', `
                    <div class="p-2 text-center text-muted small notif-loadmore-wrap">
                        Scroll down to load more…
                    </div>
                `);
            }

            list.querySelectorAll('.js-mark-read').forEach(btn => {
                if (btn.dataset.bound === '1') return;
                btn.dataset.bound = '1';

                btn.addEventListener('click', async (e) => {
                    const wrap = e.target.closest('[data-notif-id]');
                    const id = wrap?.dataset?.notifId;
                    if (!id) return;

                    try {
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
                    } catch (_) {
                    }

                    wrap.remove();

                    list.querySelectorAll('.notif-loadmore-wrap').forEach(el => el.remove());

                    if (!list.querySelector('[data-notif-id]')) {
                        list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
                    } else if (notifHasMore) {
                        list.insertAdjacentHTML('beforeend', `
                            <div class="p-2 text-center text-muted small notif-loadmore-wrap">
                                Scroll down to load more…
                            </div>
                        `);
                    }

                    await fetchCount();
                });
            });

            list.querySelectorAll('.notif-row-clickable').forEach(row => {
                if (row.dataset.bound === '1') return;
                row.dataset.bound = '1';

                row.addEventListener('click', (e) => {
                    if (e.target.closest('a, button')) return;

                    const url = row.dataset.url;
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        }

        async function loadLatest(reset = false) {
            if (notifLoading) return;

            if (reset) {
                notifPage = 1;
                notifHasMore = false;
                list.innerHTML = `<div class="p-3 text-muted small">Loading…</div>`;
            }

            notifLoading = true;

            try {
                const url = `{{ route('notifications.latest') }}?page=${notifPage}&per_page=${notifPerPage}`;

                const r = await fetch(url, {
                    headers: {'Accept': 'application/json'},
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));

                const items = data.items || [];
                const pagination = data.pagination || {};

                notifHasMore = !!pagination.has_more;

                renderItems(items, notifPage > 1);

                if (notifHasMore) {
                    notifPage = Number(pagination.next_page || (notifPage + 1));
                }
            } catch (e) {
                if (notifPage === 1) {
                    list.innerHTML = `<div class="p-3 text-danger small">Failed to load notifications</div>`;
                }
            } finally {
                notifLoading = false;

                const rows = list.querySelectorAll('[data-notif-id]');
                if (rows.length > 0) {
                    list.querySelectorAll('.notif-loadmore-wrap').forEach(el => el.remove());

                    if (notifHasMore) {
                        list.insertAdjacentHTML('beforeend', `
                            <div class="p-2 text-center text-muted small notif-loadmore-wrap">
                                Scroll down to load more…
                            </div>
                        `);
                    }
                }
            }
        }

        async function loadNextPageIfNeeded() {
            if (!notifHasMore || notifLoading) return;

            const threshold = 80;
            const remaining = list.scrollHeight - list.scrollTop - list.clientHeight;

            if (remaining <= threshold) {
                await loadLatest(false);
            }
        }

        const dropdownEl = document.getElementById('{{ $notifId }}');
        const settingsBtn = document.getElementById('notifSettingsBtn');

        settingsBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            try {
                const dd = bootstrap.Dropdown.getOrCreateInstance(dropdownEl);
                dd.hide();
            } catch (_) {
            }

            setTimeout(() => {
                document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));
                document.querySelectorAll('.dropdown.show').forEach(el => el.classList.remove('show'));
                document.querySelectorAll('.dropdown-backdrop').forEach(el => el.remove());
                dropdownEl?.setAttribute('aria-expanded', 'false');

                const settingsModalEl = document.getElementById('notifSettingsModal');
                if (!settingsModalEl) {
                    console.error('notifSettingsModal not found');
                    return;
                }

                if (settingsModalEl.parentElement !== document.body) {
                    document.body.appendChild(settingsModalEl);
                }

                const modal = bootstrap.Modal.getOrCreateInstance(settingsModalEl, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });

                modal.show();
            }, 120);
        });

        dropdownEl?.addEventListener('show.bs.dropdown', async () => {
            await loadLatest(true);
            await fetchCount();
        });

        list.addEventListener('scroll', () => {
            loadNextPageIfNeeded();
        });

        readAllBtn?.addEventListener('click', async () => {
            try {
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
            } catch (_) {
            }

            notifPage = 1;
            notifHasMore = false;
            list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
            setBadge(0);
        });

        window.__notifBellRefresh = async function () {
            try { await loadLatest(true); } catch (_) {}
            try { await fetchCount(); } catch (_) {}
        };

        setInterval(fetchCount, 20000);
    })();
</script>
