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

<nav class="navbar navbar-expand-lg navbar-dark  p-0" id="i-menu">
    <div class="container p-0">
        <div class="d-flex flex-grow-1">
            <a class="navbar-brand ml-5" href="/"><img class="" src="{{asset('img/favicon.webp')}}" width="40" alt="logo">
                <span class="text-bold white "><img src="{{asset('img/icons/AT_logo-rb.svg')}}" alt="Logo" class="colored-svg"> </span></a>
            <div class="w-100 text-right">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#myMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
        <div class="collapse navbar-collapse flex-grow-1 text-right" id="myMenu">
            <ul class="navbar-nav ml-auto flex-nowrap white">
                @guest
                    <li class="nav-item  pr-lg-3 pl-lg-3"><a href="{{ route('login') }}" class="nav-link">{{ __('Login') }}</a></li>
                    @if (Route::has('register'))
                        <li class="nav-item pr-lg-3"><a href="{{ route('register') }}" class="nav-link">{{ __('Register') }}</a></li>
                    @endif
                @else
                    <div class="dropdown">
                        <ul class="navbar-nav">
                            <a id="navbarDropdown" class="dropdown-toggle white" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }} {{ Auth::user()->isAdmin() }}
                            </a>
                            <div class="dropdown-menu text-right">
                                @if ( Auth::user()->isAdmin())
                                    <li><a class="dropdown-item text-right" href="{{route('admin.index')}}">{{ __('Admin Area') }}</a>{{Auth::user()->isAdmin()}}</li>
                                @else
                                    <li><a class="dropdown-item text-right" href="{{route('cabinet.index')}}">{{ __('Personal Area') }}</a>{{Auth::user()->isAdmin()}}</li>
                                @endif
                                <li><a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">Logout</a></li>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </ul>
                    </div>
                @endguest
            </ul>
        </div>
    </div>
</nav>
