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

    /* ------------ notifSettingsModal ----------------------*/
    #notifSettingsModal .modal-dialog{ max-width: 920px; }
    #notifSettingsModal .modal-content{ max-height: 86vh; }

    #notifSettingsModal .ns-layout{
        display:flex; gap:14px;
        min-height: 54vh;
    }
    #notifSettingsModal .ns-left{
        flex: 0 0 360px;
        width: 360px;
        border-right: 1px solid rgba(255,255,255,.12);
        padding-right: 14px;
        display:flex; flex-direction:column;
    }
    #notifSettingsModal .ns-right{
        flex: 1 1 auto;
        min-width: 0;
        display:flex; flex-direction:column;
    }

    #notifSettingsModal .ns-scroll{
        flex: 1 1 auto;
        min-height: 0;
        overflow:auto;
        padding-right: 6px;
    }

    #notifSettingsModal .user-row{
        display:flex; align-items:center; gap:10px;
        padding:6px 8px;
        border-radius: 8px;
        cursor:pointer;
        user-select:none;
    }
    #notifSettingsModal .user-row:hover{ background: rgba(255,255,255,.05); }

    #notifSettingsModal .mini-help{ font-size: 12px; opacity: .75; }

    @media (max-width: 992px){
        #notifSettingsModal .modal-dialog{ max-width: 96vw; }
        #notifSettingsModal .ns-layout{ flex-direction: column; min-height: auto; }
        #notifSettingsModal .ns-left{
            width: 100%;
            flex: 0 0 auto;
            border-right: 0;
            border-bottom: 1px solid rgba(255,255,255,.12);
            padding-right: 0;
            padding-bottom: 12px;
        }
        #notifSettingsModal .ns-scroll{ max-height: 260px; }
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

            <div class="d-flex align-items-center gap-2">
                {{-- ✅ ВАЖНО: id есть, data-bs-toggle убрано (открываем modal вручную после hide dropdown) --}}
                <button class="btn btn-sm btn-outline-secondary"
                        type="button"
                        id="notifSettingsBtn">
                    <i class="bi bi-gear"></i>
                </button>

                <button class="btn btn-sm btn-outline-secondary" id="notifReadAllBtn" type="button">
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

{{-- ------------------ Notification Settings Modal ------------------ --}}
<div class="modal fade" id="notifSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">

            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="bi bi-gear me-2"></i> Notification settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="ns-layout">

                    {{-- LEFT: global + workorders --}}
                    <div class="ns-left">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="nsMuteAll">
                            <label class="form-check-label" for="nsMuteAll">
                                Mute all notifications
                            </label>
                            <div class="text-secondary mini-help mt-1">
                                If enabled, you won't receive any notifications.
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-secondary small mb-1">Mute by Workorder #</label>

                            <div class="input-group input-group-sm">
                                <input type="number" min="1"
                                       class="form-control bg-dark text-light border-secondary"
                                       id="nsWoInput" placeholder="e.g. 1205">
                                <button class="btn btn-outline-secondary" type="button" id="nsWoAddBtn">
                                    Add
                                </button>
                            </div>

                            <div class="text-secondary mini-help mt-1">
                                Add WO numbers you don't want to receive notifications about.
                            </div>
                        </div>

                        <div class="ns-scroll mt-2" id="nsWoList">
                            <div class="text-muted small">No muted workorders</div>
                        </div>
                    </div>

                    {{-- RIGHT: users list --}}
                    <div class="ns-right">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <div class="text-secondary small">Mute messages from users</div>
                                <div class="text-secondary mini-help">Select users to ignore.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <input type="text"
                                       class="form-control form-control-sm bg-dark text-light border-secondary"
                                       id="nsUserSearch" placeholder="Search user...">

                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" type="button" id="nsUserAll">All</button>
                                    <button class="btn btn-outline-secondary" type="button" id="nsUserNone">None</button>
                                </div>
                            </div>
                        </div>

                        <div class="ns-scroll" id="nsUserList">
                            <div class="text-muted small">Loading…</div>
                        </div>

                        <div id="nsErr" class="text-danger small d-none mt-2"></div>
                        <div id="nsOk" class="text-success small d-none mt-2">Saved ✅</div>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info btn-sm" id="nsSaveBtn">
                    <i class="bi bi-save me-1"></i> Save
                </button>
            </div>
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

                    wrap.remove();

                    if (!list.querySelector('[data-notif-id]')) {
                        list.innerHTML = `<div class="p-3 text-muted small">No unread notifications</div>`;
                    }

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

        const dropdownEl = document.getElementById('{{ $notifId }}');

        // ✅ settings: закрыть dropdown -> после hidden открыть modal
        const settingsBtn = document.getElementById('notifSettingsBtn');
        const settingsModalEl = document.getElementById('notifSettingsModal');

        settingsBtn?.addEventListener('click', () => {
            try {
                const dd = bootstrap.Dropdown.getInstance(dropdownEl) || new bootstrap.Dropdown(dropdownEl);

                // вешаем handler ОДИН раз на текущее нажатие
                dropdownEl.addEventListener('hidden.bs.dropdown', function handler() {
                    dropdownEl.removeEventListener('hidden.bs.dropdown', handler);
                    try {
                        const modal = bootstrap.Modal.getInstance(settingsModalEl) || new bootstrap.Modal(settingsModalEl);
                        modal.show();
                    } catch (_) {}
                });

                dd.hide();
            } catch (_) {}
        });

        // при открытии dropdown обновляем список и счётчик
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

        window.__notifBellRefresh = async function () {
            try { await loadLatest(); } catch (_) {}
            try { await fetchCount(); } catch (_) {}
        };

        setInterval(fetchCount, 20000);
    })();
</script>

<script>
    (function () {
        if (window.__notifSettingsBound) return;
        window.__notifSettingsBound = true;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const modalEl = document.getElementById('notifSettingsModal');
        const muteAllEl = document.getElementById('nsMuteAll');

        const woInput = document.getElementById('nsWoInput');
        const woAddBtn = document.getElementById('nsWoAddBtn');
        const woList = document.getElementById('nsWoList');

        const userList = document.getElementById('nsUserList');
        const userSearch = document.getElementById('nsUserSearch');
        const userAll = document.getElementById('nsUserAll');
        const userNone = document.getElementById('nsUserNone');

        const saveBtn = document.getElementById('nsSaveBtn');
        const errEl = document.getElementById('nsErr');
        const okEl = document.getElementById('nsOk');

        if (!modalEl) return;

        let ALL_USERS = [];
        let PREFS = { mute_all:false, muted_users:[], muted_workorders:[] };

        function escapeHtml(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showErr(msg){
            errEl.textContent = msg || 'Error';
            errEl.classList.remove('d-none');
            okEl.classList.add('d-none');
        }
        function showOk(){
            okEl.classList.remove('d-none');
            errEl.classList.add('d-none');
        }
        function clearMsg(){
            errEl.classList.add('d-none');
            okEl.classList.add('d-none');
            errEl.textContent = '';
        }

        function normalizeIntArray(arr){
            return Array.from(new Set((arr || [])
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n) && n > 0)
            ));
        }

        function renderWo(){
            const wos = normalizeIntArray(PREFS.muted_workorders);

            if (!wos.length){
                woList.innerHTML = `<div class="text-muted small p-2">No muted workorders</div>`;
                return;
            }

            woList.innerHTML = wos.map(n => `
<div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom border-secondary">
  <div class="small text-light">WO #${escapeHtml(n)}</div>
  <button class="btn btn-sm btn-outline-danger py-0 px-2" type="button" data-wo="${escapeHtml(n)}">
    <i class="bi bi-x-lg"></i>
  </button>
</div>
        `).join('');
        }

        function renderUsers(){
            const q = (userSearch.value || '').trim().toLowerCase();
            const muted = new Set((PREFS.muted_users || []).map(Number));

            const html = (ALL_USERS || [])
                .filter(u => !q || String(u.name || '').toLowerCase().includes(q))
                .map(u => {
                    const id = escapeHtml(u.id);
                    const name = escapeHtml(u.name ?? ('User #' + id));
                    const checked = muted.has(Number(u.id)) ? 'checked' : '';
                    return `
<label class="user-row">
  <input class="form-check-input m-0" type="checkbox" value="${id}" ${checked}>
  <div class="flex-grow-1">
    <div class="small text-light">${name}</div>
  </div>
</label>
                `;
                }).join('');

            userList.innerHTML = html || `<div class="text-muted small p-2">No users</div>`;

            userList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', () => {
                    const id = parseInt(cb.value, 10);
                    if (!Number.isFinite(id)) return;

                    let arr = normalizeIntArray(PREFS.muted_users);
                    if (cb.checked) arr.push(id);
                    else arr = arr.filter(x => x !== id);

                    PREFS.muted_users = normalizeIntArray(arr);
                });
            });
        }

        woAddBtn?.addEventListener('click', () => {
            clearMsg();
            const n = parseInt(woInput.value, 10);
            if (!Number.isFinite(n) || n <= 0) return;

            PREFS.muted_workorders = normalizeIntArray([...(PREFS.muted_workorders || []), n]);
            woInput.value = '';
            renderWo();
        });

        woInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter'){
                e.preventDefault();
                woAddBtn.click();
            }
        });

        woList?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-wo]');
            if (!btn) return;

            const n = parseInt(btn.dataset.wo, 10);
            PREFS.muted_workorders = normalizeIntArray((PREFS.muted_workorders || []).filter(x => x !== n));
            renderWo();
        });

        userSearch?.addEventListener('input', renderUsers);

        userAll?.addEventListener('click', () => {
            PREFS.muted_users = normalizeIntArray(ALL_USERS.map(u => u.id));
            renderUsers();
        });

        userNone?.addEventListener('click', () => {
            PREFS.muted_users = [];
            renderUsers();
        });

        muteAllEl?.addEventListener('change', () => {
            PREFS.mute_all = !!muteAllEl.checked;
            clearMsg();
        });

        async function loadSettings(){
            clearMsg();
            userList.innerHTML = `<div class="text-muted small">Loading…</div>`;
            woList.innerHTML = `<div class="text-muted small p-2">Loading…</div>`;

            const r = await fetch(@json(route('notifications.settings.show')), {
                headers: {'Accept':'application/json'},
                spinner: false
            });

            const data = await r.json();

            if (!r.ok || data.ok === false){
                showErr(data.message || 'Cannot load settings');
                return;
            }

            ALL_USERS = Array.isArray(data.users) ? data.users : [];
            PREFS = data.prefs || { mute_all:false, muted_users:[], muted_workorders:[] };

            PREFS.mute_all = !!PREFS.mute_all;
            PREFS.muted_users = normalizeIntArray(PREFS.muted_users);
            PREFS.muted_workorders = normalizeIntArray(PREFS.muted_workorders);

            muteAllEl.checked = PREFS.mute_all;

            renderWo();
            renderUsers();
        }

        async function saveSettings(){
            clearMsg();

            const payload = {
                mute_all: !!PREFS.mute_all,
                muted_users: normalizeIntArray(PREFS.muted_users),
                muted_workorders: normalizeIntArray(PREFS.muted_workorders),
            };

            saveBtn.disabled = true;

            try{
                const r = await fetch(@json(route('notifications.settings.save')), {
                    method: 'POST',
                    headers: {
                        'Accept':'application/json',
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(payload),
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));

                if (!r.ok || data.ok === false){
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Save error');
                    showErr(msg);
                    return;
                }

                PREFS = data.prefs || payload;
                showOk();

                try { window.__notifBellRefresh?.(); } catch(_){}
            } finally {
                saveBtn.disabled = false;
            }
        }

        saveBtn?.addEventListener('click', saveSettings);

        modalEl.addEventListener('shown.bs.modal', loadSettings);
        window.__notifSettingsReload = loadSettings;
    })();
</script>
