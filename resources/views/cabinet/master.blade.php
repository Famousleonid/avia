<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon_old.png')}}" type="image/png">
    <title>Personal page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <link rel="stylesheet" href="{{asset('/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.css')}}">
    <link rel="stylesheet" href="{{asset('assets/jquery.fancybox.min.css')}}">
    <link href="https://cdn.datatables.net/v/bs4/dt-1.13.8/af-2.6.0/cr-1.7.0/fh-3.4.0/rr-1.4.1/sp-2.2.0/datatables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

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

        .colored-svg {
            width: 150px;
            height: auto;
            filter: brightness(0) saturate(100%) invert(100%) sepia(100%) saturate(0%) hue-rotate(283deg) brightness(110%) contrast(101%);
        }
    </style>

</head>

<body class="hold-transition sidebar-mini">

<div id="spinner-load">
    <i style="text-align: center;" class="fa fa-spinner fa-spin text-primary fa-3x win"></i>
</div>

<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-black navbar-light shadow ">
        <ul class="nav ">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" data-enable-remember="true" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <form class="form-inline ml-5">
                <div class="input-group input-group-sm">
                    <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-navbar" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" href="{{route('mobile.index')}}">
                    <i class="far fa-bell"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
        <a href="{{ url('/') }}" target="_blank" class="brand-link">
            <img src="{{asset('img/favicon.webp')}}" width="20"
                 alt=" Logo"
                 class="brand-image img-circle elevation-1"
                 style="opacity: .7">
            <span class="brand-text font-weight-bold"><img src="{{asset('img/icons/AT_logo-rb.svg')}}" alt="Logo" class="colored-svg" style="width: 120px;"></span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-2 ml-3 pb-2 mb-2 d-flex">
                <div>
                    <?php
                    $user = Auth()->user();
                    $avatar = $user->getMedia('avatar')->first();
                    $avatarThumbUrl = $avatar
                        ? route('image.show.thumb', [
                            'mediaId' => $avatar->id,
                            'modelId' => $user->id,
                            'mediaName' => 'avatar'
                        ])
                        : asset('img/avatar.jpeg');
                    $avatarBigUrl = $avatar
                        ? route('image.show.big', [
                            'mediaId' => $avatar->id,
                            'modelId' => $user->id,
                            'mediaName' => 'avatar'
                        ])
                        : asset('img/avatar.jpeg');
                    ?>
                    <a href="{{ $avatarBigUrl }}" data-fancybox="gallery">
                        <img class="rounded-circle" src="{{ $avatarThumbUrl }}" alt="User Avatar"/>
                    </a>
                </div>

                <div class="h5 ml-3 mt-2" style="color: white">
                    {{Auth::user()->name}}
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <li class="nav-item">
                        <a href="{{route('cabinet.index')}}" class="nav-link" onclick="showLoadingSpinner()">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Main</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('underway.index')}}" class="nav-link" onclick="showLoadingSpinner()">
                            <i class="nav-icon fa-solid fa-screwdriver-wrench"></i>
                            <p>Work in Progress</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('cabinet.profile')}}" class="nav-link" onclick="showLoadingSpinner()">
                            <i class="white nav-icon far fa-address-card"></i>
                            <p>Profile</p>
                        </a>
                    </li>
                    @if(Auth()->user()->getRole() == 1)
                        <li class="nav-item">
                            <a href="{{route('cabinet.customer.index')}}" class="nav-link" onclick="showLoadingSpinner()">
                                <i class="nav-icon fa-solid fa-person-military-pointing"></i>
                                <p>Customers</p>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{route('cabinet.techniks.view')}}" class="nav-link" onclick="showLoadingSpinner()">
                            <i class="nav-icon fa-regular fa-user"></i>
                            <p>Techniks</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('cabinet.materials')}}" class="nav-link" onclick="showLoadingSpinner()">
                            <i class="white nav-icon ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" class="bi bi-collection" viewBox="0 0 16 16">
                                    <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5z"/>
                                </svg>
                            </i>
                            <p>Materials</p>
                        </a>
                    </li>
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
        </div>
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

        @yield('content')

    </div>

    @include('components.footer')

</div>

<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('js/adminlte.min.js')}}"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.8/af-2.6.0/cr-1.7.0/fh-3.4.0/rr-1.4.1/sp-2.2.0/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://kit.fontawesome.com/49f401fbd8.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/jquery.fancybox.min.js') }}"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.17.4/package/dist/xlsx.full.min.js"></script>

@yield('scripts')

<script>
    $(document).ready(function () {

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















