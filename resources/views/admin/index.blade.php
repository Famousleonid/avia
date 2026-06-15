@extends('admin.master')

@section('style')
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
        <img src="{{ asset('img/nodus.png') }}" alt="Company Logo" class="img-fluid " style="max-width: 40%;">
    </div>

@endsection
