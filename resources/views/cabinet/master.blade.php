<!DOCTYPE html>
<html lang="en">
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

    @yield('link')

    <style>

        .container-checkbox {
            display: block;
            position: relative;
            padding-left: 35px;
            cursor: pointer;
            font-size: 16px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .container-checkbox input {
            position: absolute;
            z-index: -1;
            opacity: 0;
        }

        .checkmark {
            position: absolute;
            top: 3px;
            left: 0;
            height: 25px;
            width: 25px;
            background-color: #eee;
        }

        .container-checkbox:hover input ~ .checkmark {
            background-color: #ccc;
        }

        .container-checkbox input:checked ~ .checkmark {
            background-color: #75AA18;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .container-checkbox input:checked ~ .checkmark:after {
            display: block;
        }

        .container-checkbox .checkmark:after {
            left: 8px;
            top: 5px;
            width: 9px;
            height: 12px;
            border: solid white;
            border-width: 0 3px 3px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }

        .spinner-win {
            z-index: 120;
            position: absolute;
            top: 45%;
            left: 50%;
            text-align: center;
        }

        #loading img {
            height: 55px;
            width: 55px;
        }
    </style>
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
</head>

<body class="p-0 m-0 g-0">

<div id="spinner-load">
    <i class="fa fa-spinner fa-spin text-primary fa-3x spinner-win"></i>
</div>

<div class="row vh-100 g-0">

    <div class="col-lg-2 col-12">
        @include('components.sidebar')
    </div>

    <div class="col-lg-10 col-12">
        <div class="container-fluid ">
            <div class="row">
                <div class="col-12">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="list-unstyled">
                                @foreach($errors->all() as $error)
                                    <li>{{$error}}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{session('success')}}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if(session()->has('status'))
                        <div class="alert alert-info">
                            {{session('status')}}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="alert alert-danger">
                            {{session('error')}}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="content">
            @yield('content')
        </div>
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
    document.addEventListener('DOMContentLoaded', function () {

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

        function showLoadingSpinner() {
            document.querySelector('#spinner-load').classList.remove('d-none');
        }

        function hideLoadingSpinner() {
            document.querySelector('#spinner-load').classList.add('d-none');
        }

        hideLoadingSpinner();

    });
</script>

</body>
</html>















