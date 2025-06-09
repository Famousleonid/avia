<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <title>Admin page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
</head>
<style>
    .spinner-win {
        z-index: 9999;
        position: absolute;
        top: 45%;
        left: 50%;
        text-align: center;
    }
</style>
<body class="p-0 m-0 g-0 " style="background-color: #343A40;">

<div id="spinner-load" class=" spinner-border text-warning spinner-win" role="status">
    <span class="visually-hidden">Loading...</span>
</div>

<div class="container min-vh-100 d-flex justify-content-center align-items-center">
    <div class="col-md-8 col-lg-7">
        <div class="card bg-dark bg-gradient shadow-lg">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ __('Email address verification') }}</h5>
            </div>
            <div class="card-body bg-gradient py-2">
                @if (session('resent'))
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ __('A new confirmation link has been sent to your email.') }}
                    </div>
                @endif
                <p class="card-text text-white ">
                    {{ __('Before proceeding, please check your email for a confirmation link.') }}
                </p>
                <p class="card-text text-white">
                    {{ __("If you haven't received the letter") }},
                </p>
                <form class="d-inline " method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary mb-3" onclick="showLoadingSpinner()">
                        <i class="bi bi-envelope-fill me-2"></i>
                        {{ __('click here to request another') }}
                    </button>
                </form>
            </div>
            <div class="card-footer text-white-50 small">
                Don't see the letter? Check your spam folder or make sure you entered the correct email when registering.
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/main.js') }}"></script>

<script>

    window.addEventListener('load', function () {
        hideLoadingSpinner();
    });
</script>
</body>
</html>



