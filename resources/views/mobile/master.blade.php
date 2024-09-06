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
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link href="https://cdn.datatables.net/v/bs4/dt-1.13.8/af-2.6.0/cr-1.7.0/fh-3.4.0/rr-1.4.1/sp-2.2.0/datatables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    @yield('style')
    <style>

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .vh-100 {
            min-height: 100vh; /* Высота контейнера на 100% видимой части экрана */
        }

        .spinner-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050; /* Убедитесь, что он выше других элементов */
            /*display: flex;*/
            justify-content: center;
            align-items: center;
        }


        .firm-border {
            border-top: 5px solid #F8C50E;
        }

        .gg {
            border: 2px solid green;
        }

        .rr {
            border: 2px solid red !important;
        }

        .ss {
            border: 2px solid blue !important;
        }

    </style>

</head>
<body>
<div class="container-fluid vh-100 d-flex flex-column ">

    <div class="spinner-container" id="spinnerContainer">
        <div class="spinner-border" style="color: blue" role="status"></div>
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
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="{{route('mobile.index')}}"><i class="fas fa-w"></i></a>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="{{route('mobile.profile')}}"><i class="fas fa-user"></i></a>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="{{route('mobile.materials')}}"><i class="fa-solid fa-layer-group"></i></a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a class="nav-link" style="color: white; font-size: 1.5em !important;" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </nav>
    </div>
</div>


<script src="{{asset('js/jquery371min.js')}}"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://kit.fontawesome.com/49f401fbd8.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.8/af-2.6.0/cr-1.7.0/fh-3.4.0/rr-1.4.1/sp-2.2.0/datatables.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.24/webcam.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        function showSpinner() {
            let fileInput = document.getElementById('avatarfileInput');
            if (fileInput.files.length > 0) {
                document.getElementById('spinnerContainer').style.display = 'flex';
            }
        }
    });
</script>


@yield('scripts')

</body>
</html>

