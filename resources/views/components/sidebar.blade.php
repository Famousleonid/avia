<style>
    .sidebar {
        min-width: 120px;
        max-width: 240px;
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

<nav id="sidebarMenu" class="d-none d-lg-block sidebar">
    <div class="position-sticky d-flex flex-column" style="height: 95vh;">

        <div class="border-bottom row p-3 ">
            <div class="col-3">
                <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
            </div>
            <div class="col-8">
                <a href="{{ url('/') }}" target="_blank" class="brand-link">
                    @include('components.logo')
                </a>
            </div>
        </div>

        <div class="border-bottom border-1 p-2 ">
            <div class="user-panel mt-2 ml-3 pb-2  d-flex">
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
                <div class="h5 ms-2 mt-2 ">
                    {{Auth::user()->name}}
                </div>
            </div>
        </div>

        <div class="flex-grow-1 d-flex flex-column">
            <ul class="nav flex-column" data-accordion="false">
                <li class="nav-item">
                    <a class="nav-link" href="{{route('cabinet.index')}}" onclick="showLoadingSpinner()">
                        <i class="bi bi-house"> </i>
                        Main
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{route('progress.index')}}" onclick="showLoadingSpinner()">
                        <i class="bi bi-graph-up-arrow"></i>
                        Progress
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{route('cabinet.profile')}}" onclick="showLoadingSpinner()">
                        <i class="bi bi-person-bounding-box"></i>
                        Profile
                    </a>
                </li>
                @if(Auth()->user()->getRole() == 1)
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('cabinet.customer.index')}}" onclick="showLoadingSpinner()">
                            <i class="bi bi-person-workspace"></i>
                            Customers
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="{{route('technik.index')}}" onclick="showLoadingSpinner()">
                        <i class="bi bi-airplane"></i>
                        Techniks
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{route('materials.index')}}" class="nav-link" onclick="showLoadingSpinner()">
                        <i class="bi bi-body-text"></i>
                        Materials
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="themeToggle">
                        <i class="bi bi-moon"></i>
                        Thema
                    </a>
                </li>
            </ul>
        </div>

        <div class="user-section p-3 mt-auto border-top border-bottom border-1">
            <a class="nav-link" href="{{ route('logout') }}"
               onclick="event.preventDefault();
                                            document.getElementById('logout-form-menu').submit();">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
            <form id="logout-form-menu" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
</nav>

<!------------------------------------ button mobile ----------------------------------->

<nav class="navbar navbar-light bg-body-tertiary d-lg-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!---------------------------------------- Menu mobile ------------------------------->

<div class="offcanvas offcanvas-start w-50" tabindex="-1" id="offcanvasSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <a href="{{ url('/') }}" target="_blank" class="brand-link">
                @include('components.logo')
            </a>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="flex-grow-1">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-house"></i>Main</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="themeToggleMobile">
                        <i class="bi bi-moon"></i>Thema</a>
                </li>
            </ul>
        </div>
        <div class="user-section mt-auto">
            <ul class="nav flex-column">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                                                {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="userDropdownMobile">
                        <li><a class="dropdown-item" href="">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="">
                                @csrf
                                <button class="dropdown-item" type="submit">LogOut</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
