<style>
    body:has(#sendMsgModal.show) #aiAssistantWidget { visibility: hidden; }
    .sidebar-main {
        width: 240px;
        transition: width 0.5s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    nav.sidebar.sidebar-main,
    .sidebar.sidebar-main,
    .sidebar-main{
        position: relative;
        z-index: 1010;
    }
    nav.sidebar.sidebar-main {
        overflow: visible; /* allow bell dropdown to render */
    }

    .sidebar {
        overflow: visible;              /* ключ! */
        scrollbar-gutter: stable;
        background-color: var(--sidebar-bg, #192431);
        color: var(--nav-link-color, #b0becc);
    }
    .sidebar-scroll{
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }

    #sidebarMenu > .position-sticky {
        top: 0;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: visible; /* allow bell dropdown to render */
    }
    .user-panel {
        background-color: var(--sidebar-bg, #192431);
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
        background-color: var(--nav-link-hover-color-bg, #304154);
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
        background-color: var(--sidebar-bg, #192431);
    }

    .sidebar-toggle .btn {
        padding: .15rem .4rem;
        font-size: .8rem;
    }

    html[data-bs-theme="dark"] .sidebar .nav-link.bg-primary,
    html[data-bs-theme="dark"] .sidebar .nav-link.active {
        color: #fff !important;
        background-color: var(--nav-link-active-bg, #315f8c) !important;
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
    <div class="position-sticky d-flex flex-column" style="height: 100%;">

        <div class="border-bottom p-3 d-flex align-items-center gap-2">

            <a href="{{ route('cabinet.index') }}"
               class="flex-shrink-0 d-inline-flex bg-transparent text-decoration-none"
               style="background: transparent !important;"
               title="{{ __('Cabinet') }}">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Cabinet">
            </a>

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
                        <span>{{ Auth::user()->selection_name }}</span>
                        <span class="text-secondary fs-6 user-role-text">
                            {{ Auth::user()->roleName() ?? '—' }}
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
                {{-- Tests menu is temporarily hidden for everyone. --}}
                {{-- @if(Auth::user()?->isSystemAdmin())
                    <a class="nav-link" href="{{ route('admin.tests.index') }}">
                        <i class="bi bi-clipboard2-pulse me-2"></i>
                        <span>Tests</span>
                    </a>
                @endif --}}

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
                {{Auth::user()->selection_name}}
            </div>
        </div>
    </div>

    <div class="p-3 mt-auto border-top border-bottom border-1">
        {{-- Tests menu is temporarily hidden for everyone. --}}
        {{-- @if(Auth::user()?->isSystemAdmin())
            <a class="nav-link" href="{{ route('admin.tests.index') }}">
                <i class="bi bi-clipboard2-pulse me-2"></i>
                <span>Tests</span>
            </a>
        @endif --}}

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
    #sendMsgModal .modal-dialog {
        max-width: 980px;
        height: calc(100vh - 3.5rem);
        margin-top: 1.75rem;
        margin-bottom: 1.75rem;
    }
    #sendMsgModal .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #sendMsgModal .modal-body{
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    #sendMsgModal .msg-layout{
        display:flex; gap:14px;
        flex: 1 1 auto;
        min-height: 0;
        align-items: stretch;
    }
    #sendMsgModal .msg-left{
        flex:1 1 auto; min-width:0;
        display:flex; flex-direction:column;
        min-height: 0;
    }
    #sendMsgModal .msg-right{
        width:320px; flex:0 0 320px;
        display:flex; flex-direction:column;
        border-left:1px solid rgba(255,255,255,.12);
        padding-left:14px;
        min-height: 0;
    }
    #sendMsgModal .users-scroll{
        flex:1 1 auto; min-height:0;
        overflow:auto; padding-right:6px;
    }
    #sendMsgModal .msg-right { overflow-y: auto; }
    #sendMsgModal #msgGroupsList { flex: 0 0 auto; max-height: 180px; overflow-y: auto; }
    #sendMsgModal #msgUsersList { flex: 1 0 150px; }
    #sendMsgModal #msgGroupEditor { min-height: 0; height: 100%; flex-direction: column; gap: 12px; }
    #sendMsgModal #msgGroupEditor:not([hidden]) { display: flex; }
    #sendMsgModal #msgGroupMembers { min-height: 0; overflow-y: auto; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-content: start; }
    #sendMsgModal .msg-group-row { display: flex; align-items: center; gap: 6px; }
    #sendMsgModal .msg-group-row label { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    #sendMsgModal #msgScreenKinds { border: 0; padding: 0; margin-top: 14px; }
    #sendMsgModal #msgScreenKinds legend { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
    #sendMsgModal .msg-kind-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    #sendMsgModal .msg-kind-card {
        --kind-color: #ffd65a; --kind-background: #493e15;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        min-width: 0; min-height: 86px; padding: 10px 4px;
        border: 2px solid var(--kind-color); border-radius: 8px;
        color: var(--kind-color); background: transparent;
        font-size: 14px; font-weight: 600; cursor: pointer;
    }
    #sendMsgModal .msg-kind-card--alert { --kind-color: #ff7180; --kind-background: #571e29; }
    #sendMsgModal .msg-kind-card--message { --kind-color: #53d69c; --kind-background: #154332; }
    #sendMsgModal .msg-kind-card:has(input:checked) { background: var(--kind-background); color: #fff; }
    #sendMsgModal .msg-kind-card:has(input:focus-visible) { outline: 2px solid currentColor; outline-offset: 3px; }
    #sendMsgModal .msg-kind-card input { accent-color: var(--kind-color); width: 16px; height: 16px; flex-shrink: 0; margin: 0; }
    #sendMsgModal .msg-kind-help { margin-top: 6px; font-size: 12px; opacity: .8; }
    #sendMsgModal #msgUserSearch { min-width: 0; flex: 1 1 auto; }
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
        flex: 1 1 auto;
        min-height: 260px;
        max-height: 100%;
        resize: vertical;
        transition: opacity .28s ease, filter .28s ease, border-color .2s ease, background-color .2s ease;
    }
    #sendMsgModal .msg-compose{
        position: relative;
        flex: 1 1 auto;
        min-height: 260px;
        display: flex;
    }
    #sendMsgModal .msg-compose textarea#msgText{
        width: 100%;
    }
    #sendMsgModal .msg-compose-status{
        position: absolute;
        inset: 1px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        opacity: 0;
        transform: scale(.98);
        transition: opacity .22s ease, transform .22s ease;
        border-radius: .25rem;
        background: rgba(33, 37, 41, .84);
        backdrop-filter: blur(2px);
    }
    #sendMsgModal .msg-compose-status-inner{
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #75d39b;
        font-weight: 600;
        letter-spacing: 0;
    }
    #sendMsgModal .msg-compose-status-inner i{
        font-size: 1.15rem;
    }
    #sendMsgModal .msg-left.is-sending textarea#msgText{
        opacity: .62;
        filter: blur(.4px);
    }
    #sendMsgModal .msg-left.is-sent textarea#msgText{
        opacity: 0;
        filter: blur(2px);
    }
    #sendMsgModal .msg-left.is-sent .msg-compose-status{
        opacity: 1;
        transform: scale(1);
    }
    #sendMsgModal .msg-left.is-error textarea#msgText{
        border-color: rgba(220, 53, 69, .75) !important;
    }
    @media (max-width: 992px){
        #sendMsgModal .modal-dialog{
            max-width: 96vw;
            height: calc(100dvh - 1.5rem);
            margin-top: .75rem;
            margin-bottom: .75rem;
        }
        #sendMsgModal .modal-body { overflow-y: auto; }
        #sendMsgModal .msg-layout{ flex-direction:column; min-height:auto; flex: 0 0 auto; }
        #sendMsgModal .msg-right{
            width:100%; flex:0 0 auto;
            border-left:0; border-top:1px solid rgba(255,255,255,.12);
            padding-left:0; padding-top:12px;
        }
        #sendMsgModal .users-scroll{ max-height: 260px; }
        #sendMsgModal #msgGroupMembers { grid-template-columns: 1fr; }
    }
</style>

<div class="modal fade" id="sendMsgModal" tabindex="-1" aria-hidden="true"
     data-groups-url="{{ route('admin.messages.groups.index') }}" data-user-id="{{ auth()->id() }}"
     data-can-group-messages="{{ auth()->user()->canManageGroupMessages() ? '1' : '0' }}"
     data-preview-sender="{{ auth()->user()->selection_name }}" data-preview-date="{{ format_project_date(now()) }}">
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

                        <div class="msg-compose">
                            <textarea id="msgText"
                                      class="form-control bg-dark text-light border-secondary"
                                      rows="10" maxlength="1000"
                                      placeholder="Type here..."></textarea>
                            <div class="msg-compose-status" aria-hidden="true">
                                <div class="msg-compose-status-inner">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span id="msgSentText">Sent</span>
                                </div>
                            </div>
                        </div>

                        <div class="msg-actions mt-2">
                            <div class="text-secondary small">
                                <span id="msgLen">0</span>/1000
                            </div>

                            <div class="text-secondary small">
                                Selected: <span id="msgSelectedCount">0</span>
                            </div>
                        </div>

                        @if(auth()->user()->canManageGroupMessages())
                        <input type="checkbox" id="msgOnScreen" hidden>
                        <fieldset id="msgScreenKinds">
                            <legend>Show on screen</legend>
                            <div class="msg-kind-options">
                                <label class="msg-kind-card msg-kind-card--alert" for="msgKindAlert">
                                    <input type="checkbox" name="screen_kind" id="msgKindAlert" value="alert" autocomplete="off">Alert
                                </label>
                                <label class="msg-kind-card" for="msgKindInfo">
                                    <input type="checkbox" name="screen_kind" id="msgKindInfo" value="info" autocomplete="off">Info
                                </label>
                                <label class="msg-kind-card msg-kind-card--message" for="msgKindMessage">
                                    <input type="checkbox" name="screen_kind" id="msgKindMessage" value="message" autocomplete="off">Message
                                </label>
                            </div>
                            <div class="msg-kind-help">Select one type. No selection: personal notification.</div>
                        </fieldset>
                        @endif

                        <div id="msgErr" class="text-danger small d-none mt-2"></div>
                        <div id="msgWarn" class="text-warning small d-none mt-2"></div>
                        <div id="msgOk" class="text-success small d-none mt-2">Sent ✅</div>
                    </div>

                    {{-- RIGHT --}}
                    <div class="msg-right">
                        @if(auth()->user()->canManageGroupMessages())
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small">Recipient groups</span>
                            <button type="button" class="btn btn-outline-info btn-sm d-none" id="msgGroupNew">New group</button>
                        </div>
                        <div id="msgGroupsList" class="mb-3"><span class="text-secondary small">Loading…</span></div>
                        @endif
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <input id="msgUserSearch" type="search" class="form-control form-control-sm" placeholder="Search names…" aria-label="Search recipients by name" autocomplete="off">

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
                @if(auth()->user()->canManageGroupMessages())
                <div id="msgGroupEditor" hidden>
                    <h6 id="msgGroupEditorTitle">New recipient group</h6>
                    <label>Group name<input id="msgGroupName" class="form-control" maxlength="100" autocomplete="off"></label>
                    <input id="msgGroupSearch" class="form-control" type="search" placeholder="Find a user…" aria-label="Find a group member">
                    <div id="msgGroupMembers"></div>
                    <div id="msgGroupError" class="text-danger" role="alert" hidden></div>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <button type="button" id="msgGroupSave" class="btn btn-info">Save group</button>
                        <button type="button" id="msgGroupCancel" class="btn btn-outline-secondary">Back to message</button>
                        <button type="button" id="msgGroupDelete" class="btn btn-outline-danger ms-auto" hidden>Delete group</button>
                    </div>
                </div>
                @endif
            </div>

            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                @if(auth()->user()->canManageGroupMessages())
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnPreviewMsg"><i class="bi bi-eye me-1"></i> Preview</button>
                @endif
                <button type="button" class="btn btn-info btn-sm" id="btnSendMsg">
                    <i class="bi bi-send me-1"></i> Send
                </button>
            </div>

        </div>
    </div>
</div>

<script src="{{ asset('js/messages/groups.js') }}?v={{ filemtime(public_path('js/messages/groups.js')) }}"></script>
<script>
    (function () {
        if (window.__sendMsgModalBound) return;
        window.__sendMsgModalBound = true;

        const modalEl = document.getElementById('sendMsgModal');
        const listEl  = document.getElementById('msgUsersList');
        const taEl    = document.getElementById('msgText');
        const lenEl   = document.getElementById('msgLen');
        const selEl   = document.getElementById('msgSelectedCount');
        const msgLeft = modalEl?.querySelector('.msg-left');
        const sentTextEl = document.getElementById('msgSentText');

        const errEl   = document.getElementById('msgErr');
        const warnEl  = document.getElementById('msgWarn');
        const okEl    = document.getElementById('msgOk');

        const btnAll  = document.getElementById('msgPickAll');
        const btnNone = document.getElementById('msgPickNone');
        const btnSend = document.getElementById('btnSendMsg');
        const btnPreview = document.getElementById('btnPreviewMsg');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let sentResetTimer = null;

        function escapeHtml(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showErr(msg){
            msgLeft?.classList.remove('is-sending', 'is-sent');
            msgLeft?.classList.add('is-error');
            errEl.textContent = msg || 'Error';
            errEl.classList.remove('d-none');
            okEl.classList.add('d-none');
            warnEl?.classList.add('d-none');
            if (warnEl) warnEl.textContent = '';
        }
        function showWarn(msg){
            if (!warnEl) return;
            warnEl.textContent = msg || '';
            if (msg) warnEl.classList.remove('d-none');
            else warnEl.classList.add('d-none');
        }
        function showOk(text){
            okEl.textContent = 'Sent';
            const sentLabel = 'Sent';
            msgLeft?.classList.remove('is-sending', 'is-error');
            msgLeft?.classList.add('is-sent');
            if (sentTextEl) sentTextEl.textContent = sentLabel;
            okEl.textContent = sentLabel;
            okEl.classList.add('d-none');
            errEl.classList.add('d-none');
            // warn может быть вместе с ok
        }
        function clearMsg(){
            window.clearTimeout(sentResetTimer);
            msgLeft?.classList.remove('is-sending', 'is-sent', 'is-error');
            errEl.classList.add('d-none');
            okEl.classList.add('d-none');
            warnEl?.classList.add('d-none');
            errEl.textContent = '';
            if (warnEl) warnEl.textContent = '';
        }
        function resetComposeAfterSent(){
            window.clearTimeout(sentResetTimer);
            sentResetTimer = window.setTimeout(() => {
                msgLeft?.classList.remove('is-sent');
                okEl.classList.add('d-none');
            }, 1700);
        }

        function updateLen(){ lenEl.textContent = String(taEl.value.length); }

        const userSearch = document.getElementById('msgUserSearch');
        const screenOption = document.getElementById('msgOnScreen');
        const screenKinds = document.getElementById('msgScreenKinds');
        screenKinds?.addEventListener('change', event => {
            if (!event.target.matches('input[name="screen_kind"]')) return;
            if (event.target.checked) {
                screenKinds.querySelectorAll('input').forEach(input => { input.checked = input === event.target; });
            }
            screenOption.checked = !!screenKinds.querySelector('input:checked');
        });
        function filterUsers() {
            const tokens = userSearch.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
            let visible = 0;
            listEl.querySelectorAll('.user-row').forEach(row => {
                const name = row.textContent.toLocaleLowerCase();
                const matches = tokens.every(token => name.includes(token));
                row.classList.toggle('d-none', !matches);
                if (matches) visible++;
            });
            const empty = listEl.querySelector('[data-no-matching-users]');
            if (empty) empty.classList.toggle('d-none', visible > 0);
            btnAll.textContent = tokens.length ? 'Select shown' : 'All';
        }
        userSearch.addEventListener('input', filterUsers);

        function updateSelectedCount(){
            const n = window.MessageGroups.recipientCount(getSelectedIds());
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
            }).join('') + '<div class="p-2 text-secondary small d-none" data-no-matching-users>No matching users</div>';

            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
            filterUsers();
        }

        async function loadUsers(){
            const r = await fetch(@json(route('admin.messages.users')), {
                headers: {'Accept': 'application/json'},
                spinner: false
            });
            const data = await r.json();
            renderUsers(Array.isArray(data) ? data : (data.items || []));

            if (window.__msgPreselectUserIds && window.__msgPreselectUserIds.length) {
                const pre = new Set(window.__msgPreselectUserIds.map(Number).filter(Boolean));
                listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    if (pre.has(Number(cb.value))) cb.checked = true;
                });
                window.__msgPreselectUserIds = null;
                updateSelectedCount();
            }
        }

        btnAll?.addEventListener('click', () => {
            listEl.querySelectorAll('.user-row:not(.d-none) input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateSelectedCount();
        });

        btnNone?.addEventListener('click', () => {
            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            window.MessageGroups.clear();
            updateSelectedCount();
        });

        taEl.addEventListener('input', () => {
            updateLen();
            clearMsg();
        });

        modalEl?.addEventListener('shown.bs.modal', async () => {
            clearMsg();
            taEl.value = '';
            window.MessageGroups.clear();
            userSearch.value = '';
            btnAll.textContent = 'All';
            updateLen();
            listEl.innerHTML = `<div class="p-2 text-muted small">Loading…</div>`;
            if (screenOption) screenOption.checked = false;
            screenKinds?.querySelectorAll('input').forEach(input => { input.checked = false; });
            btnSend.disabled = true;
            try {
                await Promise.all([loadUsers(), window.MessageGroups.load()]);
                updateSelectedCount();
            } catch (_) { showErr('Could not load recipients. Close and try again.'); }
            finally { btnSend.disabled = false; }
        });
        modalEl.addEventListener('message-recipients-changed', updateSelectedCount);

        btnPreview?.addEventListener('click', () => {
            if (btnSend.disabled) return;
            clearMsg();
            const kind = screenKinds?.querySelector('input:checked')?.value;
            if (!kind) return showErr('Select Alert, Info or Message to preview.');
            const message = taEl.value.trim();
            if (!message) return showErr('Message is empty');
            if (!window.ScreenMessages?.preview({ kind, text: message,
                from_name: modalEl.dataset.previewSender, date: modalEl.dataset.previewDate })) {
                showErr('Preview is unavailable. Please try again.');
            }
        });

        btnSend?.addEventListener('click', async () => {
            if (btnSend.disabled) return;
            clearMsg();

            const userIds = getSelectedIds();
            const groupIds = window.MessageGroups.selectedIds();
            const message = (taEl.value || '').trim();

            if (window.MessageGroups.recipientCount(userIds) === 0) return showErr('Select at least one user or group');
            if (!message) return showErr('Message is empty');

            btnSend.disabled = true;
            btnSend.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Sending';
            if (btnPreview) btnPreview.disabled = true;
            msgLeft?.classList.remove('is-error', 'is-sent');
            msgLeft?.classList.add('is-sending');

            try{
                const r = await fetch(@json(route('admin.messages.send')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ user_ids: userIds, group_ids: groupIds, message,
                        on_screen: screenOption?.checked || false,
                        screen_kind: modalEl.querySelector('input[name="screen_kind"]:checked')?.value || 'info' }),
                    spinner: false
                });

                const data = await r.json().catch(() => ({}));

                if (!r.ok || data.ok === false){
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Send error');
                    showErr(msg);
                    return;
                }

                const sentCount = Array.isArray(data.sent) ? data.sent.length : 0;
                const blocked = Array.isArray(data.blocked) ? data.blocked : [];
                const blockedCount = blocked.length;

                if (sentCount > 0) {
                    taEl.value = '';
                    updateLen();
                    btnSend.innerHTML = '<i class="bi bi-check-circle me-1"></i> Sent';
                    resetComposeAfterSent();
                    showOk('Sent');
                } else {
                    showErr(data.message || 'Message was not sent');
                    return;
                }

                if (blockedCount > 0) {
                    const names = blocked.map(x => x?.name).filter(Boolean).join(', ');
                    showWarn(`Did not send to: ${names} (they blocked you)`);
                } else {
                    showWarn('');
                }

            } catch (e){
                showErr('Network error');
            } finally {
                btnSend.disabled = false;
                if (btnPreview) btnPreview.disabled = false;
                if (!msgLeft?.classList.contains('is-sent')) {
                    btnSend.innerHTML = '<i class="bi bi-send me-1"></i> Send';
                    msgLeft?.classList.remove('is-sending');
                } else {
                    window.setTimeout(() => {
                        btnSend.innerHTML = '<i class="bi bi-send me-1"></i> Send';
                    }, 900);
                }
            }
        });
    })();
</script>
