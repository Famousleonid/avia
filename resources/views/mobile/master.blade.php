<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <link rel="manifest" href="{{asset('manifest.json')}}">
    <meta name="keywords" content="avia, repair">
    <meta name="robots" content="none"> <!-- Выключение поисковых роботов  -->
    {{--<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aviatechnik') }}</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('css/main.css')}}">

    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        })();
    </script>

    @include('components.status')
    @yield('style')

    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #343A40;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .app-header {
            flex-shrink: 0; /* Запрет меню сжиматься */
        }

        .app-content {
            flex-grow: 1; /* Растягивается на все оставшееся место */
            overflow-y: auto; /* прокрутку ТОЛЬКО для контента, если он не помещается */
            min-height: 0; /* правильная работы flex-grow */
        }
    </style>

</head>
<body class="fade-page">
<div class="app-container">

    <div id="spinner-load" class=" spinner-border text-warning spinner-win" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>

    <div class="app-header">
        @include('components.mobile-menu', ['position'   => 'top','workorder'  => $workorder ?? null,])
    </div>

    <main class="app-content">
        @yield('content')
    </main>

    <div style="height: 10px;"></div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="{{asset('assets/select2/js/select2.min.js')}}"></script>
<script src="{{ asset('js/main.js') }}"></script>

<script>

    function formatWo(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function initAfterLoad() {
        hideLoadingSpinner();

        // сюда добавить общий код для всех моб.страниц общие слушатели и т.д

    }

    window.addEventListener('load', initAfterLoad);
</script>

@yield('scripts')


</body>
</html>

