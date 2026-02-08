<style>
    .sidebar-main {
        width: 240px;
        transition: width 0.5s ease;

    }

    .sidebar {
        overflow-y: auto;
        scrollbar-gutter: stable; /* резерв под скроллбар, без прыжков */
        background-color: #343A40;
        color: #B9BEC7;
        /*box-shadow: 0 0 15px 0 var(--shadow-top-color);*/

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
</style>


<nav id="sidebarMenu" class="d-none d-lg-block sidebar sidebar-main">
    <div class="position-sticky d-flex flex-column" style="height: 95vh;">

        <div class="border-bottom row p-3">
            <div class="col-3">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
            </div>
            <div class="col-8 bg-transparent">
                <a href="{{ route('front.index') }}" class="brand-link">
                    @include('components.logo')
                </a>
            </div>
        </div>

        {{-- Кнопка сворачивания --}}
        <div class="sidebar-toggle text-end px-2 py-1 border-bottom">
            <button id="collapseSidebarBtn" class="btn btn-sm btn-outline-light" type="button">
                <i class="bi bi-chevron-left"></i>
            </button>
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
                <div class="h5 ms-2 mt-2 text-white user-info-text">
                    <span>{{Auth::user()->name}} </span>
                    <span class="text-secondary fs-6 user-role-text">
                        {{Auth::user()->role->name}}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex-grow-1 d-flex flex-column">
            @if(Auth()->user())
                @include('components.admin_menu_sidebar', ['themeToggleId' => 'themeToggle'])
            @endif
        </div>

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
