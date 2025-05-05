{{--
@extends('mobile.master')
@section('style')
    <style>
        .animate-slide-up {
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.5s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
@section('content')
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 bg-dark p-4">

        <div class="login-form-container animate-slide-up">
            <form method="POST" action="{{ route('login') }}" class="w-100" style="max-width: 400px;">
                @csrf
                <h2 class="text-white text-center mb-4">Login</h2>

                <div class="mb-3">
                    <input id="email" type="email" name="email" class="form-control" required autofocus placeholder="Email">
                </div>

                <div class="mb-3">
                    <input id="password" type="password" name="password" class="form-control" required placeholder="Password">
                </div>

                <div class="mb-3 text-center">
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </div>
            </form>
        </div>

    </div>
@endsection
--}}
