@extends('mobile.master')

{{--@section('content')
    <div class="container">


        <h2>Загрузить фото</h2>
        <form action="{{ route('photos.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="title">Заголовок:</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="image">Выберите изображение:</label>
                <input type="file" name="image" id="image" class="form-control-file" required accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Загрузить</button>
        </form>
    </div>
@endsection--}}


@section('content')
    <div class="container">

        <h2>Фотографии для <span>w{{$workorder}}</span></h2>
        <form method="POST" action="{{ route('photos.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div id="my_camera"></div>
                    <br/>
                    <input type=button value="Take Snapshot" onClick="take_snapshot()">
                    {{--<input type="file" name="image">--}}
                    <input type="hidden" name="wo_id" value="{{$workorder}}">
                </div>
                <div class="col-md-12 text-center">
                    <span class="text-danger">{{ $errors->first('image') }}</span>
                    <br/>
                    <button type="submit" class="btn btn-success">Submit</button>
                </div>
            </div>
        </form>
    </div>




    <script>
        Webcam.set({
            width: 490,
            height: 350,
            image_format: 'jpeg',
            jpeg_quality: 90
        });

        Webcam.attach('#my_camera');

        function take_snapshot() {
            Webcam.snap(function (data_uri) {
                $(".image-tag").val(data_uri);
                document.getElementById('results').innerHTML = '<img src="'
                data_uri
                '"/>';
            });
        }
    </script>

@endsection

