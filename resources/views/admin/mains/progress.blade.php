@extends('admin.master')

@section('links')

    <!-- jQuery UI Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <style>
        [data-bs-theme="dark"] #progress-index {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }
        [data-bs-theme="dark"] #progress-index th,
        [data-bs-theme="dark"] #progress-index td {
            background: transparent !important;
            color: #fff !important;
        }
        [data-bs-theme="dark"] #progress-index thead th {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
            color: #0DDDFD !important;
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }
        [data-bs-theme="dark"] .table-hover tbody tr:hover,
        [data-bs-theme="dark"] #progress-index.table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.1) !important;
        }
        /* Жёсткое ограничение высоты строк и input */
        #progress-index tr, #progress-index td, #progress-index th {
            height: 40px !important;
            max-height: 40px !important;
            min-height: 40px !important;
            overflow: hidden !important;
            line-height: 1 !important;
            vertical-align: middle !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        #progress-index td.td-date input[type="text"],
        #progress-index td.td-date input.datepicker {
            height: 28px !important;
            min-height: 28px !important;
            max-height: 28px !important;
            font-size: 0.85rem;
            padding: 0 4px !important;
            margin: 0 !important;
            box-sizing: border-box;
            display: block;
        }
    </style>
@endsection

@section('content')

    <section class="container-fluid pl-5 pr-5 ">
        <div class="card firm-border px-2 shadow">
            <div class="card-body p-0 pt-2">

                <div class="row mb-3">
                    <div class="ml-2 col-3  pt-1 ">
                        <span class="h5">For technik: </span> <span class="text-primary h5" id="orders_count" style="display:inline-block; min-width:4ch">{{$user->name }}</span>
                    </div>
                    <div class="col-3">
                        <form action="" name="form_technik" method="get">
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
                    <table id="progress-index" class="table table-bordered table-hover w-100">
                        @foreach($wos as $wo)
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center text-primary bg-gradient"> W {{$wo->workorder->number}} @if($wo->workorder->approve) <img src="{{asset('img/ok.png')}}" data-toggle="tooltip" title='Approved' width="20px" alt=""> @endif</th>
                                <th class="text-center text-primary bg-gradient">General Task</th>
                                <th class="text-center text-primary bg-gradient">Description</th>
                                <th class="text-center text-primary bg-gradient">Date Start</th>
                                <th class="text-center text-primary bg-gradient">Date Finish</th>
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
                                            <td class="td-date text-center">
                                                <form id="form_date_finish_{{$index}}" name="form_date_finish_{{$index}}" action="{{route('mains.update', ['main' => $main->id])}}" method="post">
                                                    @csrf
                                                    @method('PUT')
                                                    <input id="date_finish_input" type="text" class="task_date_finish form-control border-primary datepicker" name="date_finish" autocomplete="off">
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


@endsection

@section('scripts')
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('input[name="date_finish"]').change(function () {
                $(this).closest('form').submit();
            });
            $('select[name="technik"]').change(function () {
                $(this).closest('form').submit();
            });
        });
        // jQuery-скрипт для выравнивания высоты строк таблицы
        $(document).ready(function () {
            $('#progress-index tr').each(function () {
                $(this).css('height', '40px');
            });
            $('#progress-index th, #progress-index td').css({
                'height': '40px',
                'min-height': '40px',
                'max-height': '40px',
                'vertical-align': 'middle',
                'padding-top': '0',
                'padding-bottom': '0',
                'box-sizing': 'border-box',
                'line-height': '1'
            });
            // Инициализация datepicker
            $('.datepicker').datepicker({
                dateFormat: 'dd-M-yy'
            });
        });
    </script>



@endsection
