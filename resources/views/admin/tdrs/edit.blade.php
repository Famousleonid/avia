@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 900px;*/
        /*}*/
    </style>

    <div class="card bg-gradient">
        <div class="card-header my-1 shadow">
            <h5 class="text-primary">{{__('Work Order')}} <span class="text-success">{{$current_wo->number}} </span></h5>

        </div>
    </div>


@endsection
