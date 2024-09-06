@extends('mobile.master')

@section('style')

    <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.1/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container--default .select2-results > .select2-results__options {
            max-height: 500px !important;
        }
    </style>
@endsection

@section('content')

    @php    $workorders = App\Models\Workorder::all(); @endphp


    <div class="container-fluid">
        <form id="form_wo" method="post" action="{{ route('mobile.show.workorder')}}">
            @csrf
            <div class="form-group" id="div_select">
                <label for="select_wo" class="font-weight-bold">Choice Workorders </label>
                <select class="form-control" id="select_wo" name="wo_id" size="10">
                    @foreach ($workorders as $workorder)
                        <option id="option_wo" value="{{$workorder->id}}">{{$workorder->number}}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

@endsection

@section('scripts')
    <script>

        $(document).ready(function () {
            $('#form_wo').change(function () {
                $('#form_wo').submit();
            });
        });


    </script>
@endsection

