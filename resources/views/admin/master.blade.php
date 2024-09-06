<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon.png')}}" type="image/png">
    <title>Admin avia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/adminlte.min.css')}}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="{{asset('plugins/fontawesome-free/css/all.css')}}">
    <link rel="stylesheet" href="{{asset('assets/jquery.fancybox.min.css')}}">

    @yield('links')

    <style>
        .firm-border {
            border-top: 5px solid #F8C50E;
        }
    </style>

</head>

<body class="hold-transition sidebar-mini  layout-fixed ">

<div class="wrapper ">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light shadow">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" data-enable-remember="true" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- SEARCH FORM -->
        <form class="form-inline ml-3 mr-5">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>


        @yield('add-menu')

        <ul class="navbar-nav ml-auto">

            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>

            <li class="nav-item d-none d-sm-inline-block">
                <a class="nav-link" href="{{ route('logout') }}"
                   onclick="event.preventDefault();
                                document.getElementById('logout-form-admin').submit();">Logout</a>
                <form id="logout-form-admin" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </li>

        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" target="_blank" class="brand-link">
            <img src="{{asset('img/favicon.webp')}}" width="40" height="40">
            <span class="brand-text "> Aviatechnik <span style="color: yellow">(Admin)</span></span>
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

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">

                    <li class="nav-item">
                        <a href="{{route('admin-workorders.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-w"></i>
                            <p>Workorders</p>
                        </a>
                    </li>

                    <li class="nav-item ">
                        <a href="{{route('techniks.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Techniks</p>
                        </a>
                    </li>

                    <li class="nav-item ">
                        <a href="{{route('customers.index')}}" class="nav-link">
                            <i class="nav-icon fa-solid fa-person-military-pointing"></i>
                            <p>Customers</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('unit.index')}}" class="nav-link">
                            <i class=" nav-icon fa-solid fa-cubes"></i>
                            <p>Units</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('component.index')}}" class="nav-link">
                            <i class="nav-icon fa-solid fa-cube"></i>
                            <p>Components</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('task.index')}}" class="nav-link">
                            <i class="nav-icon fa-solid fa-people-arrows"></i>
                            <p>Tasks</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('log.activity')}}" class="nav-link">
                            <i class="nav-icon fa-solid fa-circle-info"></i>
                            <p>LOG Information</p>
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
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <div class="content-wrapper ">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <ul class="list-unstyled">
                                @foreach($errors->all() as $error)
                                    <li>{{$error}}</li>
                                @endforeach
                            </ul>

                        </div>
                    @endif
                    @if(session()->has('success'))
                        <div class="alert alert-success text-white">
                            {{session('success')}}
                            <button type="button" class="close test-white" data-dismiss="alert" aria-label="Close">
                                <span class="text-bold ">X</span>
                            </button>
                        </div>
                    @endif
                    @if(session()->has('info'))
                        <div class="alert alert-info">
                            {{session('info')}}
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

    <x-footer/>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('js/adminlte.min.js')}}"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.8/af-2.6.0/cr-1.7.0/fh-3.4.0/rr-1.4.1/sp-2.2.0/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/49f401fbd8.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/jquery.fancybox.min.js') }}"></script>

@yield('scripts')


<script>

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















