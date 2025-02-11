@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 900px;*/
        /*}*/
    </style>

    <div class="card bg-gradient">
        <div class="card-header my-1 shadow">
            <h4 class="text-primary">{{__('Edit')}}
                <span class="text-primary-emphasis">Work Order: {{$current_tdr->workorder->number}} </span>
            </h4>

            <span class="text-success">Component: {{$current_tdr->component->name}}</span>

        </div>
        <div class="card-body">
            {{$current_tdr->workorder->unit->manual_id}}

        </div>

    </div>


@endsection
