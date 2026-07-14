{{-- Minimal layout for iframe/embed (no sidebar, no status, no footer) --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @include('partials.user-scoped-storage')
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/custom_bootstrap.css') }}?v={{ filemtime(public_path('css/custom_bootstrap.css')) }}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
    <script>
        window.forceDarkTheme = {{ auth()->check() && auth()->user()->roleIs('Technician') ? 'true' : 'false' }};
        (function () {
            var theme = window.forceDarkTheme ? 'dark' : (window.UserScopedStorage.getItem('theme') || 'dark');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    @yield('style')
</head>
<body class="p-0 m-0 g-0" style="background-color: var(--avia-bg, #141b24);">
<div class="p-2">
    @yield('content')
</div>
<script src="{{asset('assets/jquery/jquery371min.js')}}"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/select2/js/select2.min.js')}}"></script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
@yield('scripts')
</body>
</html>
