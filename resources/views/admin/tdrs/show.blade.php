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
            <div class="d-flex ">
                <div style="width: 150px">{{'Component Name: '}}</div>
                <div style="width: 150px">{{$current_wo->description}}</div>
            </div>
            <div class="d-flex ">
                <div style="width: 150px">{{'Part Number: '}}</div>
                <div style="width: 150px">{{$current_wo->unit->part_number}}</div>
            </div>
            <div class="d-flex ">
                <div style="width: 150px">{{'Serial Number: '}}</div>
                <div style="width: 150px">{{$current_wo->serial_number}}</div>
            </div>
            <div class="d-flex ">
                <div style="width: 150px">{{'CMM: '}}</div>
                <div style="width: 150px">{{$current_wo->unit->manuals->number}}</div>
            </div>
            <div class="d-flex ">
                <div style="width: 150px">{{'MFR: '}}</div>
                <div style="width: 150px">{{$current_wo->unit->manuals->builder->name}}</div>
            </div>

        </div>
    </div>


@endsection
