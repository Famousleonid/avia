@extends('admin.master')

@section('style')
    <style>
        .cabinet-aircraft-stage {
            position: relative;
            flex: 1 1 0;
            min-height: 0;
        }

        .cabinet-aircraft {
            position: absolute;
            width: 40%;
            height: auto;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .cabinet-aircraft-stage--photo .cabinet-aircraft {
            width: 20%;
            left: 75%;
            top: 25%;
        }
    </style>
@endsection

@section('content')

    <div class="cabinet-aircraft-stage {{ !empty($userProjectBackgroundUrl) ? 'cabinet-aircraft-stage--photo' : '' }}">
        <img src="{{ asset('img/avia190.png') }}" alt="ERJ-190" class="cabinet-aircraft">
    </div>

@endsection
