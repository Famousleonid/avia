<style>
    .colored-svg {
        width: 160px;
        height: auto;
        filter: brightness(0) saturate(100%) invert(100%) sepia(100%) saturate(0%) hue-rotate(283deg) brightness(110%) contrast(101%);
    }

    .navbar-nav .nav-link {
        color: white !important;
    }

</style>

<nav class="navbar navbar-expand-lg navbar-dark p-0" id="i-menu">
    <div class="container p-0">
        <div class="d-flex flex-grow-1">
            <a class="navbar-brand ms-5" href="/">
                <img src="{{ asset('img/favicon.webp') }}" width="40" alt="logo">
                <span class="text-bold">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo" class="colored-svg">
                </span>
            </a>
            <div class="w-100 text-end">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#myMenu" aria-controls="myMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
        <div class="collapse navbar-collapse flex-grow-1 text-end" id="myMenu">
            <ul class="navbar-nav ms-auto flex-nowrap me-3">
                @guest
                    <li class="nav-item px-lg-3">
                        <a href="{{ route('login') }}" class="nav-link">{{ __('Login') }}</a>
                    </li>
{{--                    @if (Route::has('register'))--}}
{{--                        <li class="nav-item px-lg-3">--}}
{{--                            <a href="{{ route('register') }}" class="nav-link">{{ __('Register') }}</a>--}}
{{--                        </li>--}}
{{--                    @endif--}}
                @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end text-end me-1 bg-transparent border-0" aria-labelledby="navbarDropdown">
                            @if (Auth::user()->isAdmin())
                                <li>
                                    <a class="dropdown-item text-white bg-transparent border-0" href="{{ route('admin.index') }}">{{ __('Admin Area') }}</a>
                                </li>
                            @else
                                <li>
                                    <a class="dropdown-item text-white bg-transparent border-0" href="{{ route('cabinet.index') }}">{{ __('Personal Area') }}</a>
                                </li>
                            @endif
                            <li>
                                <a class="dropdown-item text-white bg-transparent border-0" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                            </li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>


