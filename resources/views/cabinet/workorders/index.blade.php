@extends('admin.master')

@section('links')
    <style>
        .table-wrapper {
            /*height: calc(100vh - 170px);*/
            /*overflow-y: auto;*/
            overflow-x: hidden;
            /*display: table; !* Устанавливает div как контейнер для таблицы *!*/
            /*width: 100%; !* Устанавливаем ширину на 100% *!*/
            /*height: auto; !* Высота будет зависеть от содержимого *!*/
            /*overflow: hidden; !* Не нужно скрывать контент *!*/
            /*padding: 0; !* Убираем лишние отступы *!*/

            display: flex;
            flex-direction: column;
            width: 100%;
            height: auto; /* Высота автоматически подстраивается */
            overflow-y: auto; /* Добавление вертикальной прокрутки */
            max-height: calc(100vh - 170px);

        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;

        }

        input[type="checkbox"] {
            width: 80px;
            height: 40px;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: #f08282;
            outline: none;
            border-radius: 50px;
            box-shadow: inset 0 0 5px rgba(0, 0, 0, .2);
            transition: 0.5s;
            position: relative;
        }

        input:checked[type="checkbox"] {
            background: #42a50d;
        }

        input[type="checkbox"]::before {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid darkgray;
            top: 0;
            left: 0;
            background: #fff;
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
            transition: 0.5s;
        }

        input:checked[type="checkbox"]::before {
            left: 40px;
        }


        @media (prefers-color-scheme: dark) {
            .bg-body {
                --bs-bg-opacity: 1;
                background-color: #212529 !important;
            }
        }

    </style>
@endsection

@section('content')



    <section class="container-fluid p-0 m-0 g-0">

        <div class="card shadow bg-body">

            <div class="row align-items-center py-2 border-bottom">
                <div class="col-4">
                    <h5 class="card-title text-bold ps-2">List of workorders ( <span class="text-primary">{{count($workorders)}}</span> ) </h5>
                </div>
                <div class="col-4">
                    <a id="admin_new_firm_create" href={{route('cabinet.workorders.create')}} class=""><img src="{{asset('img/plus.png')}}" width="30px" alt="" data-toggle="tooltip" data-placement="top" title="Add new workorder"></a>
                </div>

                <div class="card-tools ml-auto pr-2">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                            title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>


            @if(count($workorders))

                <div class="table-wrapper pe-3  bg-body">

                    <table id="show-workorder" class="display table-sm table-bordered table-striped table-hover w-100" style="background: linear-gradient(to bottom, #131313, #2E2E2E);">

                        <thead>
                        <tr>
                            <th class="text-center text-primary bg-gradient ">Number</th>
                            <th class="text-center text-primary bg-gradient ">Approve</th>
                            <th class="text-center text-primary bg-gradient ">Unit</th>
                            <th class="text-center text-primary bg-gradient ">Description</th>
                            <th class="text-center text-primary bg-gradient ">Serial number</th>
                            <th class="text-center text-primary bg-gradient ">WO TDR</th>
                            <th class="text-center text-primary bg-gradient ">Manual</th>
                            <th class="text-center text-primary bg-gradient ">Customer</th>
                            <th class="text-center text-primary bg-gradient ">Instruction</th>
                            <th class="text-center text-primary bg-gradient ">Technik</th>
                            <th class="text-center text-primary bg-gradient ">Place</th>
                            <th class="text-center text-primary bg-gradient " data-orderable="false">Edit</th>
                            <th class="text-center text-primary bg-gradient ">Open Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($workorders as $workorder)
                            <tr>
                                <td class="text-center">
                                    <a class="text-decoration-none" href="">
                                        <span style="font-size: 16px; color: #0DDDFD;  " id="" class="text-bold">w&nbsp; {{$workorder->number}}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a class="change_approve" href="{{route("cabinet.workorders.approve", ['id' => $workorder->id])}}" onclick="showLoadingSpinner()">
                                        @if($workorder->approve_at)
                                            <img data-toggle="tooltip" title="@if($workorder->approve_at) {{$workorder->approve_at->format('d.m.Y')}}&nbsp; {{$workorder->approve_name}} @endif" src="{{asset('img/ok.png')}}" width="20px" alt="">
                                        @else
                                            <img src="{{asset('img/icon_no.png')}}" width="12px" alt="">
                                        @endif
                                    </a>
                                </td>
                                <td class="text-center">{{$workorder->unit->part_number}}</td>
                                <td class="text-center">{{$workorder->unit->manuals->title}}</td>
                                <td class="text-center">{{$workorder->serial_number}}
                                    @if($workorder->amdt>0)
                                        Amdt {{$workorder->amdt}}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href=""
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-journal-richtext"></i>
                                    </a>
                                </td>
                                <td class="text-center">{{$workorder->unit->manuals->number}}</td>
                                <td class="text-center">{{$workorder->customer->name}}</td>
                                <td class="text-center">{{$workorder->instruction->name}}</td>
                                <td class="text-center">{{$workorder->user->name}}</td>
                                <td class="">{{$workorder->place}}</td>
                                <td class="text-center">
                                    <a href="{{route('cabinet.workorders.edit', ['workorder' => $workorder->id])}}"><img src="{{asset('img/set.png')}}" width="30px" alt=""></a>
                                </td>
                                @if($workorder->open_at)
                                    <td class="text-center"><span style="display: none">{{$workorder->open_at->format('Ymd')}}</span>{{$workorder->open_at->format('d.m.Y')}}</td>
                                @else
                                    <td class="text-center"><span style="display: none">{{$workorder->open_at}}</span>{{$workorder->open_at}}</td>
                                @endif
                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                </div>
            @else
                <p class="ms-2">Workorders not created</p>
            @endif


        </div>

    </section>

@endsection

@section('scripts')


    <script>

        document.addEventListener('DOMContentLoaded', function () {



        });

    </script>

@endsection


