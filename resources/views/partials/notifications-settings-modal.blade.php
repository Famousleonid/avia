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
                                    <button class="btn btn-outline-secondary" type="button" id="nsUserNone">None
                                    </button>
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

<style>
    #notifSettingsModal .modal-dialog {
        max-width: 920px;
    }

    #notifSettingsModal .modal-content {
        max-height: 86vh;
    }

    #notifSettingsModal .ns-layout {
        display: flex;
        gap: 14px;
        min-height: 54vh;
    }

    #notifSettingsModal .ns-left {
        flex: 0 0 360px;
        width: 360px;
        border-right: 1px solid rgba(255, 255, 255, .12);
        padding-right: 14px;
        display: flex;
        flex-direction: column;
    }

    #notifSettingsModal .ns-right {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    #notifSettingsModal .ns-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 6px;
        max-height: 420px;
    }

    #notifSettingsModal .user-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 8px;
        border-radius: 8px;
        cursor: pointer;
        user-select: none;
    }

    #notifSettingsModal .user-row:hover {
        background: rgba(255, 255, 255, .05);
    }

    #notifSettingsModal .mini-help {
        font-size: 12px;
        opacity: .75;
    }

    @media (max-width: 992px) {
        #notifSettingsModal .modal-dialog {
            max-width: 96vw;
        }

        #notifSettingsModal .ns-layout {
            flex-direction: column;
            min-height: auto;
        }

        #notifSettingsModal .ns-left {
            width: 100%;
            flex: 0 0 auto;
            border-right: 0;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
            padding-right: 0;
            padding-bottom: 12px;
        }

    }
</style>

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
        let PREFS = {mute_all: false, muted_users: [], muted_workorders: []};

        function escapeHtml(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showErr(msg) {
            errEl.textContent = msg || 'Error';
            errEl.classList.remove('d-none');
            okEl.classList.add('d-none');
        }

        function showOk() {
            okEl.classList.remove('d-none');
            errEl.classList.add('d-none');
        }

        function clearMsg() {
            errEl.classList.add('d-none');
            okEl.classList.add('d-none');
            errEl.textContent = '';
        }

        function normalizeIntArray(arr) {
            return Array.from(new Set((arr || [])
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n) && n > 0)
            ));
        }

        function renderWo() {
            const wos = normalizeIntArray(PREFS.muted_workorders);

            if (!wos.length) {
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

        function renderUsers() {

            const q = (userSearch.value || '').trim().toLowerCase();
            const muted = new Set((PREFS.muted_users || []).map(Number));

            if (!Array.isArray(ALL_USERS) || ALL_USERS.length === 0) {
                userList.innerHTML = `<div class="text-muted small p-2">No users</div>`;
                return;
            }

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
            if (e.key === 'Enter') {
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

        async function loadSettings() {
            clearMsg();
            userList.innerHTML = `<div class="text-muted small">Loading…</div>`;
            woList.innerHTML = `<div class="text-muted small p-2">Loading…</div>`;

            try {
                const r = await fetch(@json(route('notifications.settings.show')), {
                    headers: {'Accept': 'application/json'},
                    spinner: false
                });

                const text = await r.text();

                let data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showErr('Invalid JSON response');
                    userList.innerHTML = `<div class="text-danger small p-2">Invalid JSON response</div>`;
                    woList.innerHTML = `<div class="text-danger small p-2">Invalid JSON response</div>`;
                    return;
                }

                if (!r.ok || data.ok === false) {
                    showErr(data.message || 'Cannot load settings');
                    userList.innerHTML = `<div class="text-danger small p-2">Cannot load users</div>`;
                    woList.innerHTML = `<div class="text-danger small p-2">Cannot load workorders</div>`;
                    return;
                }

                ALL_USERS =
                    Array.isArray(data.users) ? data.users :
                        Array.isArray(data.data?.users) ? data.data.users :
                            [];

                PREFS =
                    data.prefs ||
                    data.data?.prefs ||
                    { mute_all: false, muted_users: [], muted_workorders: [] };

                PREFS.mute_all = !!PREFS.mute_all;
                PREFS.muted_users = normalizeIntArray(PREFS.muted_users);
                PREFS.muted_workorders = normalizeIntArray(PREFS.muted_workorders);

                muteAllEl.checked = PREFS.mute_all;

                renderWo();
                renderUsers();
            } catch (e) {
                console.error('loadSettings failed:', e);
                showErr('Cannot load settings');
                userList.innerHTML = `<div class="text-danger small p-2">Cannot load users</div>`;
                woList.innerHTML = `<div class="text-danger small p-2">Cannot load workorders</div>`;
            }
        }

        async function saveSettings() {
            clearMsg();

            const payload = {
                mute_all: !!PREFS.mute_all,
                muted_users: normalizeIntArray(PREFS.muted_users),
                muted_workorders: normalizeIntArray(PREFS.muted_workorders),
            };

            saveBtn.disabled = true;

            try {
                const r = await fetch(@json(route('notifications.settings.save')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(payload),
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));

                if (!r.ok || data.ok === false) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Save error');
                    showErr(msg);
                    return;
                }

                PREFS = data.prefs || payload;
                showOk();

                try {
                    window.__notifBellRefresh?.();
                } catch (_) {
                }
            } finally {
                saveBtn.disabled = false;
            }
        }

        saveBtn?.addEventListener('click', saveSettings);
        modalEl.addEventListener('shown.bs.modal', loadSettings);
        window.__notifSettingsReload = loadSettings;
    })();
</script>
