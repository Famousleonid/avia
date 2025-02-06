@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 900px;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">Add Process for Manual {{$manual->first()->number}} ({{$manual->first()->title}})</h4>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.processes.store') }}" enctype="multipart/form-data"
                      id="createCMMForm">
                @csrf
                    <div class="form-group d-flex">
                        <div class="mt-2 m-3 p-3">
                            <h5>Manual {{$manual->first()->number}}</h5>

                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>


@endsection
