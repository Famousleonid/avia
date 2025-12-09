@extends('admin.master')

@section('links')
    <style>
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

    </style>
@endsection

@section('content')



    <section class="container pl-3 pr-3 mt-2">

        <div class="card firm-border p-2 bg-white shadow">

            <div class="card-header row align-items-center p-2">
                <div class="col-4">
                    <h3 class="card-title text-bold">List of workorders ( <span style="color: blue">{{count($workorders)}}</span> pieces ) </h3>
                </div>
                <div class="col-4">
                    <a id="admin_new_firm_create" href={{route('admin-workorders.create')}} class=""><img src="{{asset('img/plus.png')}}" width="50px" alt="" data-toggle="tooltip" data-placement="top" title="Add new workorder"></a>
                </div>

                <div class="card-tools ml-auto pr-2">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                            title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>


            <div class="card-body p-0 pt-2">

                <div class="box-body ">
                    @if(count($workorders))

                        <table id="show-workorder" class="display table-sm table-bordered table-striped table-hover " style="width:100%;">

                            <thead>
                            <tr>
                                <th hidden>Id</th>
                                <th class="text-center">Number</th>
                                <th class="text-center">Approve</th>
                                <th>Customer</th>
                                <th>Unit</th>
                                <th class="text-center">Manual</th>
                                <th>Instruction</th>
                                <th>Technik</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th class="text-center">create Date</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($workorders as $workorder)
                                <tr>
                                    <td hidden>{{$workorder->id}}</td>
                                    <td>{{$workorder->number}}</td>

                                    @if($workorder->approve)
                                        <td class="text-center"><img src="{{asset('img/ok.png')}}" width="30px" alt=""></td>
                                    @else
                                        <td class="text-center"><img src="{{asset('img/icon_no.png')}}" width="15px" alt=""></td>
                                    @endif

                                    <td class="">{{$workorder->customer->name}}</td>
                                    <td class="">{{$workorder->unit->partnumber}}</td>
                                    <td class="text-center">{{$workorder->manual}}</td>
                                    <td class="">{{$workorder->instruction->name}}</td>
                                    <td class="">{{$workorder->user->name}}</td>

                                    <td class="text-center">
                                        <a href="{{route('admin-workorders.edit', ['admin_workorder' => $workorder->id])}}"><img src="{{asset('img/set.png')}}" width="30px" alt=""></a>
                                    </td>
                                    <td class="text-center"><span style="display: none">{{$workorder->created_at->format('Ymd')}}</span>{{$workorder->created_at->format('d.m.Y')}}</td>
                                    <td class="text-center">
                                        <form action="{{route('admin-workorders.destroy', ['admin_workorder' => $workorder->id])}}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-danger" type="button" data-toggle="modal"
                                                    data-target="#confirmDelete" data-title="Delete workorder {{$workorder->number}}"
                                                    data-message="Are you sure you want to delete this workorder?"
                                                    title="Contact the Administrator"
                                                    @if (!Auth()->user()->is_admin) disabled @endif
                                            <i class="glyphicon glyphicon-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                            @endforeach

                            </tbody>
                        </table>
                    @else
                        <p>Workorders not created</p>
                    @endif
                </div>
            </div>
        </div>

    </section>

    @include('components.delete');


@endsection

@section('scripts')


    <script>

        let mainTable = $('#show-workorder').DataTable({
            "AutoWidth": false,
            "scrollY": "550px",
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "ordering": true,
            "info": false,
            "order": [[1, 'desc']],
            "bAutoWidth": false,

            columnDefs: [

                {"width": "70px", "targets": [2]},

            ],
        });


        document.addEventListener('DOMContentLoaded', function () {

            // delete form confirm
            $('#confirmDelete').on('show.bs.modal', function (e) {

                let message = $(e.relatedTarget).attr('data-message');
                $(this).find('.modal-body p').text(message);
                let title = $(e.relatedTarget).attr('data-title');
                $(this).find('.modal-title').text(title);

                let form = $(e.relatedTarget).closest('form');

                $(this).find('.modal-footer #buttonConfirm').data('form', form);
            });

            $('#confirmDelete').find('.modal-footer #buttonConfirm').on('click', function () {
                $(this).data('form').submit();
            });


        });

    </script>

@endsection


