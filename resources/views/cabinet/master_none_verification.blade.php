<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon.png')}}" type="image/png">
    <title>Personal page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{asset('/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.css')}}">


    @yield('link')

    <style>

        .container-checkbox {
            display: block;
            position: relative;
            padding-left: 35px;
            margin-bottom: 12px;
            cursor: pointer;
            font-size: 22px;
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
            top: 0;
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

        .win {
            z-index: 120;
            position: absolute;
            top: 45%;
            left: 50%;
            /*            margin: auto;*/
        }

        #loading img {
            height: 55px;
            width: 55px;
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

<body class="hold-transition sidebar-mini">

<div id="spinner-load">
    <i style="text-align: center;" class="fa fa-spinner fa-spin text-primary fa-3x win"></i>
</div>


<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-black navbar-light shadow ">
        <!-- Left navbar links -->
        <ul class="nav ">

            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" data-enable-remember="true" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>

        </ul>

    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" target="_blank" class="brand-link">
            <img src="{{asset('img/favicon.png')}}" width="20"
                 alt=" Logo"
                 class="brand-image img-circle elevation-1"
                 style="opacity: .7">
            <span class="brand-text font-weight-bold">Aviatechnik</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('logout') }}"
                           onclick="event.preventDefault();
                                document.getElementById('logout-form-menu').submit();">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                        <form id="logout-form-menu" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>

                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <div class="content-wrapper pt-3">

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
                        </div>
                    @endif
                    @if(session()->has('status'))
                        <div class="alert alert-info">
                            {{session('status')}}
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="alert alert-danger">
                            {{session('error')}}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @yield('content')
        {{--    --------------------------------------------------}}
        <div class="container">
            <div class="row justify-content-center mt-4 mb-4">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                        <div class="card-body">
                            @if (session('resent'))
                                <div class="alert alert-success" role="alert">
                                    {{ __('A fresh verification link has been sent to your email address.') }}
                                </div>
                            @endif

                            {{ __('Before proceeding, please check your email for a verification link.') }}
                            {{ __('If you did not receive the email') }},
                            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>
                                .
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <footer class="main-footer justify-content-around bg-dark">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0.2
        </div>
        <strong>Copyright &copy; 2024 <a href="https://aviatechnic.ca">Aviatechnik</a>.</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;All rights
        reserved.
    </footer>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('js/adminlte.min.js')}}"></script>

@yield('scripts')

<script>
    $(document).ready(function () {


        $('.nav-sidebar a').each(function () {
            let location = window.location.protocol + '//' + window.location.host + window.location.pathname;

            let link = this.href;

            if (link === location) {
                $(this).addClass('active');
                $(this).closest('.has-treeview').addClass('menu-open');
            }
        });


    });

    function showLoadingSpinner() {
        document.querySelector('#spinner-load').classList.remove('d-none');

    }

    function hideLoadingSpinner() {
        document.querySelector('#spinner-load').classList.add('d-none');

    }

    hideLoadingSpinner();


</script>

</body>
</html>



























