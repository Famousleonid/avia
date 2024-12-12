@extends('mobile.master')



@section('content')

    <h2>Фотографии для <span>w{{$workorder->number}}</span></h2>
    <a href="{{ route('photos.create',['wo_id'=>$workorder->id]) }}" class="btn btn-primary mb-3">Загрузить новое фото</a>


    <div class="row">
        @if($photos)
            @foreach($photos as $photo)
                <div class="col mb-2">
                    <div class="card">

                        <img src="{{$photo->getUrl('preview')}}" width="100" height="100">

                    </div>
                </div>
            @endforeach
        @endif
    </div>


@endsection
