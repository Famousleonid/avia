<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
{{--    <link rel="icon" href="{{asset('img/favicon_old.png')}}" type="image/png">--}}
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

    <style>
        .sidebar-main {
            min-width: 120px;
            max-width: 240px;
        }
        .sidebar {
            overflow-y: auto;
            background-color: #343A40;
            color: #B9BEC7;
            box-shadow: 0 0 15px 0 var(--shadow-top-color);
        }
        .user-panel {
            background-color: #343A40;
            color: var(--sidebar-color);
        }
        .sidebar .nav-link {
            color: var(--nav-link-color);
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover {
            color: var(--nav-link-hover-color);
        }
        .sidebar .nav-link.active {
            color: var(--nav-link-active-color);
            background-color: var(--nav-link-active-bg);
        }
        .colored-svg {
            color: #0DDDFD;
            fill: #fff;
        }
    </style>

</head>

<body>

<div class="row">
    <nav id="sidebarMenu" class="col-12 col-md-2 d-lg-block sidebar sidebar-main">
        <div class="position-sticky d-flex flex-column" style="height: 95vh;">

            <div class="border-bottom row p-3">
                <div class="col-3">
                    <img src="{{ asset('img/favicon.webp') }}" width="30" alt="Logo">
                </div>
                <div class="col-8">
                    <a href="{{ url('/') }}" target="_blank" class="brand-link">
                        @include('components.logo')
                    </a>
                </div>
            </div>

            <ul class="nav flex-column" data-accordion="false">
                <li class="nav-item">
                    <a href="{{route('materials.index')}}" class="nav-link" onclick="showLoadingSpinner()">
                        <i class="bi bi-body-text me-2"></i> Materials
                    </a>
                </li>
            </ul>

        </div>
    </nav>

    <div class="col-12 col-md-10">
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
                            <button type="submit"
                                    class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>
                            .
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

 @include('components.footer')

<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>

</body>
</html>



























