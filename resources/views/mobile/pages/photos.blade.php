@extends('mobile.master')


@section('content')

    <div id="my_camera"></div>

    <form id="myForm" action="{{route('photos.store')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('post')
        <input type="hidden" name="workorder" id="" value="27">
        <input type="hidden" name="image" id="imageData">
       
    </form>
    <button id="snap" type="button">Take Snapshot</button>
    <div id="results"></div>

    <script>


        document.addEventListener('DOMContentLoaded', function () {
            Webcam.set({
                width: 300,
                height: 200,
                image_format: 'jpeg',
                jpeg_quality: 100
            });

            Webcam.attach('#my_camera');

            $('#snap').click(function () {
                takeSnapshot();
            });

            function takeSnapshot() {
                Webcam.snap(function (data_uri) {
                    document.getElementById('results').innerHTML =
                        '<img src="' + data_uri + '"/>';

                    document.getElementById('imageData').value = data_uri;
                });
            }


        });


    </script>

@endsection

