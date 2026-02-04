@extends('admin.master')

@section('content')
    <style>
        .card shadow {
            max-width: 1200px;
        }

        .card-header{
            display: flex;
        }
        .card-body{
            height: 80vh;
        }
    </style>
    <div class="card shadow">
        <div class="card-header m-2 justify-content-between">
            <div class="me-2 d-flex ">
                <a href="{{ $cmm->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                    <img class="rounded-circle" src="{{ $cmm->getFirstMediaThumbnailUrl('manuals') }}" width="60" height="60"
                         alt="Image"/>
                </a>

                <div class="ms-3">
                    <h5 class="ms-2 "><strong class="text-secondary">{{__('CMM:')}}</strong> {{ $cmm->number }}</h5>
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Description:')}}</strong> {{ $cmm->title }}</h5>
                </div>
            </div>
            <div class="ms-3">
                <h5 class="ms-2"><strong class="text-secondary">{{__('Component PNs:')}}</strong> {{ $cmm->unit_name_training }}</h5>
                <div class="d-flex">
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Revision Date:')}}</strong> {{ $cmm->revision_date }}</h5>
                        <h5 class="ms-4"><strong class="text-secondary">{{__('Lib:')}}</strong> {{ $cmm->lib }}</h5>
                </div>
            </div>
            <div class="ms-3 me-5">
                <h5 class="ms-2"><strong class="text-secondary">{{__('AirCraft Type:')}}</strong>
                        @foreach($planes as $plane)
                            @if($plane->id == $cmm->planes_id )
                                {{$plane->type}}
                            @endif
                        @endforeach
                </h5>
                <h5 class="ms-2"><strong class="text-secondary">{{__('MFR:')}}</strong>
                        @foreach($builders as $builder)
                            @if($builder->id == $cmm->builders_id )
                                {{$builder->name}}
                            @endif
                        @endforeach
                </h5>
            </div>
        </div>

        <div class="card-body">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-components-tab" data-bs-toggle="tab" data-bs-target="#nav-components"
                            type="button" role="tab" aria-controls="nav-components" aria-selected="true">Components</button>
                    <button class="nav-link" id="nav-parts-tab" data-bs-toggle="tab" data-bs-target="#nav-parts"
                            type="button" role="tab" aria-controls="nav-parts" aria-selected="false">Parts</button>
                    <button class="nav-link" id="nav-processes-tab" data-bs-toggle="tab" data-bs-target="#nav-processes"
                            type="button" role="tab" aria-controls="nav-processes" aria-selected="false">Processes</button>
                    <button class="nav-link" id="nav-disabled-tab" data-bs-toggle="tab" data-bs-target="#nav-disabled"
                            type="button" role="tab" aria-controls="nav-disabled" aria-selected="false" disabled>Disabled </button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-components" role="tabpanel" aria-labelledby="nav-home-tab"
                     tabindex="0">
                    .1.
                </div>
                <div class="tab-pane fade" id="nav-parts" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                    .2
                    .
                </div>
                <div class="tab-pane fade" id="nav-processes" role="tabpanel" aria-labelledby="nav-contact-tab" tabindex="0">
                    .3
                    .
                </div>
                <div class="tab-pane fade" id="nav-disabled" role="tabpanel" aria-labelledby="nav-disabled-tab" tabindex="0">
                    .4
                    .
                </div>
            </div>


        </div>

    </div>


@endsection
