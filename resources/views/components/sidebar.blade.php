<style>
    .sidebar-main {
        width: 240px;
        transition: width 0.5s ease;

    }
    nav.sidebar.sidebar-main,
    .sidebar.sidebar-main,
    .sidebar-main{
        position: relative;
        z-index: 3000;
    }

    .sidebar {
        overflow: visible;              /* ключ! */
        scrollbar-gutter: stable;
        background-color:#343A40;
        color:#B9BEC7;

    }
    .sidebar-scroll{
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }
    .user-panel {
        background-color: #343A40;
        color: var(--sidebar-color);
    }

    .sidebar .nav-link {
        color: var(--nav-link-color);
        display: flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .75rem;
        transition: color .2s ease, background-color .2s ease;
    }

    .sidebar .nav-link:hover {
        color: var(--nav-link-hover-color);
    }

    .sidebar .nav-link.active {
        color: var(--nav-link-active-color);
        background-color: var(--nav-link-active-bg);
    }

    .colored-svg {
        color: #0DDDFD;
        fill: #fff;
        background-color: transparent;
    }

    .sidebar-toggle {
        background-color: #343A40;
    }

    .sidebar-toggle .btn {
        padding: .15rem .4rem;
        font-size: .8rem;
    }

    /* ================= СВЕРНУТОЕ СОСТОЯНИЕ (только иконки) ================= */

    html[data-sidebar-collapsed="1"] .sidebar-main {
        width: 64px;
    }

    /* прячем текст */
    html[data-sidebar-collapsed="1"] .brand-link,
    html[data-sidebar-collapsed="1"] .user-info-text,
    html[data-sidebar-collapsed="1"] .user-role-text,
    html[data-sidebar-collapsed="1"] .nav-link span,
    html[data-sidebar-collapsed="1"] .logout-text {
        display: none !important;
    }

    html[data-sidebar-collapsed="1"] .nav-link {
        justify-content: center;
        padding-left: .5rem;
        padding-right: .5rem;
    }

    html[data-sidebar-collapsed="1"] .nav-link i.bi,
    html[data-sidebar-collapsed="1"] .nav-link svg {
        margin-right: 0 !important;
    }

    html[data-sidebar-collapsed="1"] .user-panel {
        justify-content: center;
    }

    html[data-sidebar-collapsed="1"] .user-panel > div.me-2 {
        margin-right: 0 !important;
    }

    .send-msg-btn{
        padding: .20rem .45rem;
        line-height: 1;
    }

    /* ================= СВЕРНУТОЕ СОСТОЯНИЕ ================= */
    html[data-sidebar-collapsed="1"] .user-panel{
        justify-content: center !important;
    }

    /* скрываем всё в user-panel кроме send */
    html[data-sidebar-collapsed="1"] .user-panel .me-2,
    html[data-sidebar-collapsed="1"] .user-panel .user-info-text{
        display:none !important;
    }

    /* send по центру */
    html[data-sidebar-collapsed="1"] .user-panel .send-msg-btn{
        margin: 0 !important;
    }

</style>


<nav id="sidebarMenu" class="d-none d-lg-block sidebar sidebar-main">
    <div class="position-sticky d-flex flex-column" style="height: 95vh;">

        <div class="border-bottom p-3 d-flex align-items-center gap-2">

            <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo" class="flex-shrink-0">

            <a href="{{ route('front.index') }}" class="brand-link flex-grow-1">
                @include('components.logo')
            </a>

            <div class="sidebar-bell flex-shrink-0">
                @include('partials.notifications-bell')
            </div>
        </div>

        {{-- Кнопка сворачивания --}}
        <div class="sidebar-toggle text-end px-2 py-1 border-bottom">
            <button id="collapseSidebarBtn" class="btn btn-sm btn-outline-light" type="button">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>

        <div class="border-bottom border-1 p-2">
            <div class="user-panel mt-2 ml-3 pb-2 d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <a href="{{ Auth::user()->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                            <img class="rounded-circle"
                                 src="{{ Auth::user()->getFirstMediaThumbnailUrl('avatar') }}"
                                 width="40" height="40" alt="Image"/>
                        </a>
                    </div>

                    <div class="h5 ms-2 mt-2 text-white user-info-text">
                        <span>{{ Auth::user()->name }}</span>
                        <span class="text-secondary fs-6 user-role-text">
                    {{ Auth::user()->role->name }}
                </span>
                    </div>
                </div>

                {{----------- SEND MSG ---------------}}
                <button
                    type="button"
                    class="btn btn-sm btn-outline-info send-msg-btn flex-shrink-0"
                    data-bs-toggle="modal"
                    data-bs-target="#sendMsgModal"
                    title="Send message"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>

        <div class="flex-grow-1 sidebar-scroll">
            @if(Auth()->user())
                @include('components.admin_menu_sidebar', ['themeToggleId' => 'themeToggle'])
            @endif

                <div class="p-3 mt-auto border-top border-bottom border-1">
                    <a class="nav-link" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form-menu').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        <span class="logout-text">Logout</span>
                    </a>
                    <form id="logout-form-menu" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>

        </div>


    </div>
</nav>

{{----------------------- Mobile --------------------------------}}

<nav class="navbar navbar-light bg-body-tertiary d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon text-white"></span>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-start sidebar" style="max-height: 90%; width: 35%;" tabindex="-1" id="offcanvasSidebar">
    <div class="offcanvas-header border-bottom border-1">
        <div class="row p-2">
            <div class="col-3">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
            </div>
            <div class="col-8"></div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="border-bottom border-1 p-2">
        <div class="user-panel mt-2 ml-3 pb-2 d-flex">
            <div class="me-2">
                <a href="{{ Auth::user()->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                    <img class="rounded-circle"
                         src="{{ Auth::user()->getFirstMediaThumbnailUrl('avatar') }}"
                         width="40" height="40" alt="Image"/>
                </a>
            </div>
            <div class="h5 ms-2 mt-2">
                {{Auth::user()->name}}
            </div>
        </div>
    </div>

    <div class="p-3 mt-auto border-top border-bottom border-1">
        <a class="nav-link" href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form-menu-mobile').submit();">
            <i class="bi bi-box-arrow-right me-2"></i>
            <span class="logout-text">Logout</span>
        </a>
        <form id="logout-form-menu-mobile" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>
{{-- ---------------------------------- modal message (NEW layout) ------------------- --}}

<style>
    #sendMsgModal .modal-dialog { max-width: 980px; }
    #sendMsgModal .modal-content { max-height: 86vh; }

    #sendMsgModal .msg-layout{
        display:flex; gap:14px;
        min-height: 62vh;
    }
    #sendMsgModal .msg-left{
        flex:1 1 auto; min-width:0;
        display:flex; flex-direction:column;
    }
    #sendMsgModal .msg-right{
        width:320px; flex:0 0 320px;
        display:flex; flex-direction:column;
        border-left:1px solid rgba(255,255,255,.12);
        padding-left:14px;
    }
    #sendMsgModal .users-scroll{
        flex:1 1 auto; min-height:0;
        overflow:auto; padding-right:6px;
    }
    #sendMsgModal .user-row{
        display:flex; align-items:center; gap:10px;
        padding:6px 8px; border-radius:8px;
        cursor:pointer; user-select:none;
    }
    #sendMsgModal .user-row:hover{ background: rgba(255,255,255,.05); }
    #sendMsgModal .msg-actions{
        display:flex; align-items:center; justify-content:space-between; gap:10px;
    }
    #sendMsgModal textarea#msgText{
        resize: vertical;
        min-height: 260px;
    }
    @media (max-width: 992px){
        #sendMsgModal .modal-dialog{ max-width: 96vw; }
        #sendMsgModal .msg-layout{ flex-direction:column; min-height:auto; }
        #sendMsgModal .msg-right{
            width:100%; flex:0 0 auto;
            border-left:0; border-top:1px solid rgba(255,255,255,.12);
            padding-left:0; padding-top:12px;
        }
        #sendMsgModal .users-scroll{ max-height: 260px; }
    }
</style>

<div class="modal fade" id="sendMsgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">

            <div class="modal-header border-secondary">
                <h5 class="modal-title">Send message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="msg-layout">

                    {{-- LEFT --}}
                    <div class="msg-left">
                        <label class="form-label text-secondary small mb-1">Message</label>

                        <textarea id="msgText"
                                  class="form-control bg-dark text-light border-secondary"
                                  rows="10" maxlength="1000"
                                  placeholder="Type here..."></textarea>

                        <div class="msg-actions mt-2">
                            <div class="text-secondary small">
                                <span id="msgLen">0</span>/1000
                            </div>

                            <div class="text-secondary small">
                                Selected: <span id="msgSelectedCount">0</span>
                            </div>
                        </div>

                        <div id="msgErr" class="text-danger small d-none mt-2"></div>
                        <div id="msgOk" class="text-success small d-none mt-2">Sent ✅</div>
                    </div>

                    {{-- RIGHT --}}
                    <div class="msg-right">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-secondary small">To (one / many / all)</div>

                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="msgPickAll">All</button>
                                <button type="button" class="btn btn-outline-secondary" id="msgPickNone">None</button>
                            </div>
                        </div>

                        <div class="users-scroll" id="msgUsersList">
                            <div class="text-muted small">Loading…</div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info btn-sm" id="btnSendMsg">
                    <i class="bi bi-send me-1"></i> Send
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    (function () {
        if (window.__sendMsgModalBound) return;
        window.__sendMsgModalBound = true;

        const modalEl = document.getElementById('sendMsgModal');
        const listEl  = document.getElementById('msgUsersList');
        const taEl    = document.getElementById('msgText');
        const lenEl   = document.getElementById('msgLen');
        const selEl   = document.getElementById('msgSelectedCount');

        const errEl   = document.getElementById('msgErr');
        const okEl    = document.getElementById('msgOk');

        const btnAll  = document.getElementById('msgPickAll');
        const btnNone = document.getElementById('msgPickNone');
        const btnSend = document.getElementById('btnSendMsg');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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

        function updateLen(){ lenEl.textContent = String(taEl.value.length); }

        function updateSelectedCount(){
            const n = listEl.querySelectorAll('input[type="checkbox"]:checked').length;
            selEl.textContent = String(n);
        }

        function getSelectedIds(){
            return Array.from(listEl.querySelectorAll('input[type="checkbox"]:checked'))
                .map(i => Number(i.value))
                .filter(Boolean);
        }

        function renderUsers(users){
            if (!users || users.length === 0){
                listEl.innerHTML = `<div class="p-2 text-muted small">No users</div>`;
                updateSelectedCount();
                return;
            }

            listEl.innerHTML = users.map(u => {
                const id = escapeHtml(u.id);
                const name = escapeHtml(u.name ?? ('User #' + id));
                return `
                <label class="user-row">
                    <input class="form-check-input m-0" type="checkbox" value="${id}">
                    <div class="flex-grow-1">
                        <div class="small text-light">${name}</div>
                    </div>
                </label>
            `;
            }).join('');

            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        }

        async function loadUsers(){
            const r = await fetch(@json(route('admin.messages.users')), {
                headers: {'Accept': 'application/json'},
                spinner: false
            });
            const data = await r.json();
            // твой контроллер возвращает массив [{id,name}]
            renderUsers(Array.isArray(data) ? data : (data.items || []));
        }

        btnAll?.addEventListener('click', () => {
            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateSelectedCount();
        });

        btnNone?.addEventListener('click', () => {
            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateSelectedCount();
        });

        taEl.addEventListener('input', () => {
            updateLen();
            clearMsg();
        });

        modalEl?.addEventListener('shown.bs.modal', async () => {
            clearMsg();
            updateLen();
            listEl.innerHTML = `<div class="p-2 text-muted small">Loading…</div>`;
            await loadUsers();
        });

        btnSend?.addEventListener('click', async () => {
            clearMsg();

            const userIds = getSelectedIds();
            const message = (taEl.value || '').trim();

            if (userIds.length === 0) return showErr('Select at least one user');
            if (!message) return showErr('Message is empty');

            btnSend.disabled = true;

            try{
                const r = await fetch(@json(route('admin.messages.send')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ user_ids: userIds, message }),
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));

                if (!r.ok || data.ok === false){
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Send error');
                    showErr(msg);
                } else {
                    showOk();
                    // можно очистить текст, а выбранных оставить или снять — как хочешь:
                    // taEl.value = ''; updateLen();
                }
            } catch (e){
                showErr('Network error');
            } finally {
                btnSend.disabled = false;
            }
        });
    })();
</script>
