<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="keywords" content="avia, landing gear">
    <meta name="robots" content="none"> <!-- Выключение поисковых роботов  -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6777ef"/>
    <link rel="apple-touch-icon" href="{{ asset('/img/plane.webp') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <title>{{ config('app.name', 'Aviatechnik') }}</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}">

    <style>
        body {
            height: 100%;
            width: 100%;
            padding: 0;
            margin: 0 auto;
            /*background: linear-gradient(blue, deepskyblue);*/
            background: url("/public/img/avia190.png"), linear-gradient(blue, deepskyblue);
            background-size: 700px auto, cover; /* 1-е — PNG, 2-е — градиент */
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center center;
        }
        @media (max-width: 768px) {
            body {
                background-size: 300px auto, cover;
            }
        }
    </style>
</head>
<body>

<main class="py-1">
    @yield('content')
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>

@yield('scripts')


</body>
</html>

