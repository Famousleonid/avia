<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="avia, repair">
    <meta name="robots" content="none"> <!-- Выключение поисковых роботов  -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6777ef"/>
    <link rel="apple-touch-icon" href="{{ asset('/img/plane.webp') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <title>{{ config('app.name', 'Aviatechnik') }}</title>
    <link rel="stylesheet" href="{{asset('/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('/css/app.css')}}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">

    <style>
        body {
            height: 100%;
            width: 100%;
            padding: 0;
            margin: 0;
            background: url("/public/img/dolphin.png"), linear-gradient(blue, deepskyblue);
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center center;
        }
    </style>
</head>
<body>


<main class="py-1">
    @yield('content')
</main>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function (registrations) {
            for (let registration of registrations) {
                registration.unregister();
            }
        }).catch(function (err) {
            console.log('Service Worker unregister failed: ', err);
        });
    }
</script>

<script src="{{asset('js/jquery371min.js')}}"></script>
<script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>

@yield('scripts')


</body>
</html>

