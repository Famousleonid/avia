@extends('admin.master')

@section('links')
    <style>
        .table-wrapper {
            height: calc(100vh - 170px);
            overflow-y: auto;
            overflow-x: hidden;
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

    </style>
@endsection

@section('content')



    <section class="container-fluid p-0 m-0 g-0">

        <div class="card shadow">

            <div class="row align-items-center py-2 border-bottom">
                <div class="col-4">
                    <h5 class="card-title text-bold ps-2">List of workorders ( <span class="text-primary">{{count($workorders)}}</span> ) </h5>
                </div>
                <div class="col-4">
                    <a id="admin_new_firm_create" href={{route('admin.workorders.create')}} class=""><img src="{{asset('img/plus.png')}}" width="30px" alt="" data-toggle="tooltip" data-placement="top" title="Add new workorder"></a>
                </div>

                <div class="card-tools ml-auto pr-2">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                            title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>


                @if(count($workorders))

                    <div class="table-wrapper me-3 p-2 pt-0">

                        <table id="show-workorder" class="display table-sm table-bordered table-striped table-hover w-100">

                            <thead>
                            <tr>
                                <th class="text-center text-primary bg-gradient ">Number</th>
                                <th class="text-center text-primary bg-gradient ">Approve</th>
                                <th class="text-center text-primary bg-gradient ">Unit</th>
                                <th class="text-center text-primary bg-gradient ">Serial number</th>
                                <th class="text-center text-primary bg-gradient ">Amdt</th>
                                <th class="text-center text-primary bg-gradient ">Manual</th>
                                <th class="text-center text-primary bg-gradient ">Customer</th>
                                <th class="text-center text-primary bg-gradient ">Instruction</th>
                                <th class="text-center text-primary bg-gradient ">Technik</th>
                                <th class="text-center text-primary bg-gradient ">Place</th>
                                <th class="text-center text-primary bg-gradient " data-orderable="false">Edit</th>
                                <th class="text-center text-primary bg-gradient ">create Date</th>
                                <th class="text-center text-primary bg-gradient " data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($workorders as $workorder)
                                <tr>
                                    <td>{{$workorder->number}}</td>
                                    @if($workorder->approve)
                                        <td class="text-center"><img src="{{asset('img/ok.png')}}" width="30px" alt=""></td>
                                    @else
                                        <td class="text-center"><img src="{{asset('img/icon_no.png')}}" width="15px" alt=""></td>
                                    @endif
                                    <td class="">{{$workorder->unit->partnumber}}</td>
                                    <td class="text-center">{{$workorder->serial_number}}</td>
                                    <td class="text-center">{{$workorder->amdt}}</td>
                                    <td class="text-center">{{$workorder->unit_id}}</td>
                                    <td class="">{{$workorder->customer->name}}</td>
                                    <td class="">{{$workorder->instruction->name}}</td>
                                    <td class="">{{$workorder->user->name}}</td>
                                    <td class="">{{$workorder->place}}</td>
                                    <td class="text-center">
                                        <a href="{{route('admin.workorders.edit', ['workorder' => $workorder->id])}}"><img src="{{asset('img/set.png')}}" width="30px" alt=""></a>
                                    </td>
                                    <td class="text-center"><span style="display: none">{{$workorder->created_at->format('Ymd')}}</span>{{$workorder->created_at->format('d.m.Y')}}</td>
                                    <td class="text-center">
                                        <form action="{{route('admin.workorders.destroy', ['workorder' => $workorder->id])}}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-title="Delete workorder {{$workorder->number}}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
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

    @include('components.delete')


@endsection

@section('scripts')


    <script>

        // let mainTable = $('#show-workorder').DataTable({
        //     "AutoWidth": false,
        //     "scrollY": "550px",
        //     "scrollX": false,
        //     "scrollCollapse": true,
        //     "paging": false,
        //     "ordering": true,
        //     "info": false,
        //     "order": [[1, 'desc']],
        //     "bAutoWidth": false,
        //
        //     columnDefs: [
        //
        //         {"width": "70px", "targets": [2]},
        //
        //     ],
        // });


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


