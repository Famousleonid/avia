@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 900px;*/
        /*}*/
    </style>

    <div class="card bg-gradient">
        <div class="card-header my-1 shadow">
            <h5 class="text-primary">{{__('Edit TDR Records')}}
                <span class="text-success">Record: {{$current_tdr->id}}</span></h5>
                <span class="text-success">Work Order: {{$current_tdr->workorder->number}} </span></h5>

        </div>
    </div>


@endsection
