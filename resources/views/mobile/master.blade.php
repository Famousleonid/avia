<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{asset('img/favicon.png')}}" type="image/png">
    <link rel="manifest" href="{{asset('manifest.json')}}">
    <meta name="keywords" content="avia, repair">
    <meta name="robots" content="none"> <!-- Выключение поисковых роботов  -->
    {{--<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aviatechnik') }}</title>

    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">

    @yield('style')
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
    </style>

</head>
<body>
<div class="container-fluid vh-100 d-flex flex-column ">

    <div class="spinner-container" id="spinnerContainer">
        <div class="spinner-border d-none" style="color: blue" role="status"></div>
    </div>

    <div class="row flex-grow-1">
        <div class="h-100 d-flex align-items-center justify-content-center">
            @yield('content')
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <nav class="navbar navbar-dark bg-primary fixed-bottom" style="padding: 1.5rem">
            <div class="container-fluid justify-content-around ">
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="#"><i class="bi bi-house"></i></a>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="#"><i class="bi bi-hand-index"></i></a>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="#"><i class="bi bi-apple"></i></a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="#"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </nav>
    </div>
</div>


<script src="{{asset('assets/jquery/jquery371min.js')}}"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>


<script>
</script>

@yield('scripts')

</body>
</html>

