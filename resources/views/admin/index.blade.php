@extends('admin.master')

@section('styles')
    <style>
        .placeholder-logo {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endsection

@section('content')

    <div class="d-flex justify-content-center align-items-center vh-100">
        <img src="{{ asset('img/avia190.png') }}" alt="Company Logo" class="img-fluid " style="max-width: 40%;">
    </div>

@endsection
