@extends('cabinet.master')

@section('content')

    <section class="container-fluid pl-5 pr-5 ">
        <div class="card firm-border px-2 bg-white shadow">
            <div class="card-body p-0 pt-2">

                <div class="row ">
                    <div class="ml-2 col-3  pt-1">
                        <span class="h5">For technik: </span> <span class="text-primary h5" id="orders_count" style="display:inline-block; min-width:4ch">{{$user->name }}</span>
                    </div>
                    <div class="col-3">
                        <form action="{{route('underway.technik')}}" name="form_technik" method="post">
                            @csrf
                            <select name="technik" id="sl_technik" class="form-control">
                                <option value="" selected disabled>--Select a technician from your team--</option>
                                @foreach ($team_techniks as $team_technik)
                                    <option value="{{$team_technik->id}}">{{$team_technik->name}}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>


                @if(count($mains))
                    <table id="underway-index" class="display table-sm table-bordered  table-hover" style="width:100%;">
                        @foreach($wos as $wo)
                            <thead>
                            <tr>
                                <th> W {{$wo->workorder->number}} @if($wo->workorder->approve) <img src="{{asset('img/ok.png')}}" data-toggle="tooltip" title='Approved' width="20px" alt=""> @endif
                                <th>General Task</th>
                                <th>Description</th>
                                <th class="text-center">Date Start</th>
                                <th class="text-center">Date Finish</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($mains as $index =>$main)
                                @if($main->workorder == $wo->workorder)
                                    <tr>
                                        <td></td>
                                        <td>{{$main->generaltask->name}}</td>
                                        <td>{{$main->description}}</td>
                                        <td class="text-center">@if ($main->date_start)<span>{{date('d-M-Y', strtotime($main->date_start))}}</span> @endif</td>
                                        @if($main->date_finish)
                                            <td class="text-center"><span>{{date('d-M-Y', strtotime($main->date_finish))}}</span></td>
                                        @else
                                            <td class="text-center">
                                                <form id="form_date_finish_{{$index}}" name="form_date_finish_{{$index}}" action="{{route('main.update', ['main' => $main->id])}}" method="post">
                                                    @csrf
                                                    @method('PUT')
                                                    <input id="date_finish_input" type="date" class="task_date_finish form-control border-primary " name="date_finish">
                                                    <input type="hidden" name="form_index" value="{{ $index }}">
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        @endforeach
                    </table>
                @else
                    <p style="color:red">No current job</p>
                @endif
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#underway-index').DataTable({
                "paging": false,
                "info": false,
                "searching": false,
                "ordering": false,
            })
            $('input[name="date_finish"]').change(function () {
                $(this).closest('form').submit();
            });
            $('select[name="technik"]').change(function () {
                $(this).closest('form').submit();
            });
        });
    </script>
@endsection
