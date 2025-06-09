@extends('front.master')

@section('content')

    @include('components.menu')

    <style>
       .wave {
           color: #fff;
           font-size: 3vw;
           font-weight: bold;
           display: flex;
           justify-content: center;
           margin-top:40px;
           -webkit-box-reflect: below -15px linear-gradient(transparent, rgba(255, 255, 255, 0.2));
       }
        .wave span {
            display: inline-block;
            font-size: 3vw;
            animation: wave 2s infinite calc(.1s * var(--i));

        }
        @keyframes wave {
            0%, 40%, 100% {
                transform: translateY(0);
            }
            20% {
                transform: translateY(-20px);
            }
        }
    </style>

{{--    <div class="container-fluid  w-100 h-100">--}}

{{--        <div class="wave">--}}
{{--            <span style="--i:1">A</span>--}}
{{--            <span style="--i:2">v</span>--}}
{{--            <span style="--i:3">i</span>--}}
{{--            <span style="--i:4">a</span>--}}
{{--            <span style="--i:5">T</span>--}}
{{--            <span style="--i:6">e</span>--}}
{{--            <span style="--i:7">c</span>--}}
{{--            <span style="--i:8">h</span>--}}
{{--            <span style="--i:9">n</span>--}}
{{--            <span style="--i:10">i</span>--}}
{{--            <span style="--i:11">k</span>--}}
{{--        </div>--}}

{{--    </div>--}}
@endsection


