<style>
    .sidebar-main {
        min-width: 120px;
        max-width: 240px;
    }
    .sidebar {
        overflow: hidden;
        background-color: #343A40;
        color: #B9BEC7;
        box-shadow: 0 0 15px 0 var(--shadow-top-color);
    }
    .user-panel {
        background-color: #343A40;
        color: var(--sidebar-color);
    }
    .sidebar .nav-link {
        color: var(--nav-link-color);
        display: flex;
        align-items: center;
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
    }
</style>

<nav id="sidebarMenu" class="d-none d-lg-block sidebar sidebar-main">
    <div class="position-sticky d-flex flex-column" style="height: 95vh;">

        <div class="border-bottom row p-3">
            <div class="col-3">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
            </div>
            <div class="col-8">
                <a href="{{ url('/') }}" target="_blank" class="brand-link">
                    @include('components.logo')
                </a>
            </div>
        </div>

        <div class="border-bottom border-1 p-2">
            <div class="user-panel mt-2 ml-3 pb-2 d-flex">
                <div class="me-2">
                    <?php
                    $user = Auth()->user();
                    $avatar = $user->getMedia('avatar')->first();
                    $avatarThumbUrl = $avatar
                        ? route('image.show.thumb', [
                            'mediaId' => $avatar->id,
                            'modelId' => $user->id,
                            'mediaName' => 'avatar'
                        ])
                        : asset('img/avatar.jpeg');
                    $avatarBigUrl = $avatar
                        ? route('image.show.big', [
                            'mediaId' => $avatar->id,
                            'modelId' => $user->id,
                            'mediaName' => 'avatar'
                        ])
                        : asset('img/avatar.jpeg');
                    ?>
                    <a href="{{ $avatarBigUrl }}" data-fancybox="gallery">
                        <img class="rounded-circle" src="{{ $avatarThumbUrl }}" alt="User Avatar" style="width: 45px"/>
                    </a>
                </div>
                <div class="h5 ms-2 mt-2">
                    {{Auth::user()->name}}
                </div>
            </div>
        </div>

        <div class="flex-grow-1 d-flex flex-column">
            @include('components.menu_sidebar')
        </div>

        <div class="user-section p-3 mt-auto border-top border-bottom border-1">
            <a class="nav-link" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-menu').submit();">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
            <form id="logout-form-menu" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
</nav>

<!------------------------------ Button X ----------------------------------------->
<nav class="navbar navbar-light bg-body-tertiary d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!---------------------------------- Mobile menu  ----------------------------------->
<div class="offcanvas offcanvas-start w-50 sidebar" style="height: 95vh;" tabindex="-1" id="offcanvasSidebar">

    <div class="offcanvas-header border-bottom border-1">
        <div class="row p-2">
            <div class="col-3">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
            </div>
            <div class="col-8">
                <a href="{{ url('/') }}" target="_blank" class="brand-link">
                    @include('components.logo')
                </a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="border-bottom border-1 p-2">
        <div class="user-panel mt-2 ml-3 pb-2 d-flex">
            <div class="me-2">
                <a href="{{ $avatarBigUrl }}" data-fancybox="gallery">
                    <img class="rounded-circle" src="{{ $avatarThumbUrl }}" alt="User Avatar" style="width: 45px"/>
                </a>
            </div>
            <div class="h5 ms-2 mt-2">
                {{Auth::user()->name}}
            </div>
        </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column">
        @include('components.menu_sidebar')
    </div>

    <div class="user-section p-3 mt-auto border-top border-bottom border-1">
        <a class="nav-link" href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form-menu').submit();">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
        <form id="logout-form-menu" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</div>
