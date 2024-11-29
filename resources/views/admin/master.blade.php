<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon_old.png')}}" type="image/png">
    <title>Personal page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('assets/jquery/jquery.fancybox.min.css')}}">
    <link href="{{asset('assets/dataTables/datatables.css')}}" rel="stylesheet">
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('css/custom_bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">

    @yield('links')

    <style>
        .firm-border {
            border-top: 5px solid #F8C50E;
        }
    </style>

</head>

<body class="p-0 m-0 g-0">

<div class="row vh-100 g-0">

    <div class="col-lg-2 ">
        @include('components.sidebar')
    </div>

    <div class="content col-12 col-lg-10">
        @yield('content')
    </div>

 @include('components.footer')

</div>

<script src="{{asset('assets/jquery/jquery371min.js')}}"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/dataTables/datatables.min.js')}}"></script>
<script src="{{asset('assets/select2/js/select2.min.js')}}"></script>
<script src="{{ asset('assets/jquery/jquery.fancybox.min.js') }}"></script>

@yield('scripts')

<script>
    const themeToggle = document.getElementById('themeToggle');
    const themeToggleMobile = document.getElementById('themeToggleMobile');

    function updateThemeIcon(theme) {
        const iconClass = theme === 'dark' ? 'bi-sun' : 'bi-moon';
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = `bi ${iconClass}`;
            }
        }
        if (themeToggleMobile) {
            const icon = themeToggleMobile.querySelector('i');
            if (icon) {
                icon.className = `bi ${iconClass}`;
            }
        }
    }

    function toggleTheme() {
        let currentTheme = document.documentElement.getAttribute('data-bs-theme');
        let newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function (e) {
            e.preventDefault();
            toggleTheme();
        });
    }

    if (themeToggleMobile) {
        themeToggleMobile.addEventListener('click', function (e) {
            e.preventDefault();
            toggleTheme();
        });
    }

    let storedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', storedTheme);
    updateThemeIcon(storedTheme);


    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $('.nav-sidebar a').each(function () {
        let location = window.location.protocol + '//' + window.location.host + window.location.pathname;

        let link = this.href;

        if (link === location) {
            $(this).addClass('active');
            $(this).closest('.has-treeview').addClass('menu-open');
        }
    });

</script>

</body>
</html>















