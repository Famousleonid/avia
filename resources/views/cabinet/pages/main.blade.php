@extends('cabinet.master')

@section('link')
    <style>
        .sf {
            font-size: 12px;
        }
    </style>

@endsection

@section('content')

    <section class="container">
        <div class="card firm-border pb-1 bg-white shadow ">
            <div class="card-body p-0 row">

                <div class="col">
                    <h5 class="modal-title text-primary text-bold">&nbsp;w{{$current_workorder->number}}</h5>
                    @if($current_workorder->approve)
                        &nbsp;<img class="" src="{{asset('img/ok.png')}}" width="20px" alt=""><span class="sf text-success">&nbsp;approved</span>
                    @else
                        &nbsp;<span class="sf text-gray">&nbsp;not approved</span>
                    @endif
                </div>

                <div class="form-group col-lg-10 mb-0">
                    <form id="general_task_form" action="{{route('main.create', ['workorder_id' => $current_workorder->id])}}" class="col-lg-12 row">
                        @csrf
                        <input type="text" hidden name="workorder_id" value="{{$current_workorder->id}}">
                        <div class="form-group col ">
                            <label class="sf" for="general_task_id">General Task <span style="color:red; font-size: x-small">(required)</span></label>
                            <select name="general_task_id" id="general_task_id" class="form-control">
                                <option disabled selected value=""> -- select an option --</option>
                                @foreach ($general_tasks as $general_task)
                                    <option value="{{$general_task->id}}">{{$general_task->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col ">
                            <label class="sf" for="user_id">Technik</label>
                            <select name="user_id" id="user_id" class="form-control ">
                                <option selected value="{{ Auth::user()->id }}">{{ Auth::user()->name }}</option>
                                @foreach ($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col">
                            <label class="sf" for="description">Description:</label>
                            <input id="description" class="form-control" name="description" maxlength="256" size="20"/>
                        </div>
                        <div class="form-group col">
                            <label class="sf" for="date_start">Date Start</label>
                            <input type="date" id="date_start" class="form-control" name="date_start" value="{{ date('Y-m-d') }}"/>
                        </div>
                        <div class="form-group col-1 mt-4">
                            <button id="general_task_confirm" name="btn_main" type="submit" class="btn btn-info" style="margin-top: 5px">Add</button>
                        </div>
                    </form>
                </div>

            </div>

            <div class="container-fluid" style="padding-left: 18%;">

                @if(count($mains))
                    <table id="main-index" class="display table-sm table-bordered table-striped table-hover" style="width:100%;">
                        <thead>
                        <tr>
                            <th hidden>*</th>
                            <th>General Task</th>
                            <th>Technik</th>
                            <th data-orderable="false">Description</th>
                            <th class="text-center">Date Start</th>
                            <th class="text-center">Date Finish</th>
                            <th class="text-center" data-orderable="false">Delete</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($mains as $index =>$main)
                            <tr>
                                <td hidden>{{$main->created_at}}</td>
                                <td>{{$main->generaltask->name}}</td>
                                <td>{{$main->user->name}}</td>
                                <td>{{$main->description}}</td>
                                <td class="text-center">
                                    @if ($main->date_start)<span>{{date('d-M-Y', strtotime($main->date_start))}}</span> @endif
                                </td>
                                @if($main->date_finish)
                                    <td class="text-center"><span>{{date('d-M-Y', strtotime($main->date_finish))}}</span></td>
                                @else
                                    <td class="text-center">
                                        <form id="form_date_finish_{{$index}}" name="form_date_finish_{{$index}}" action="{{route('main.update', ['main' => $main->id])}}" method="post">
                                            @csrf
                                            @method('PUT')
                                            <input type="date" class="task_date_finish form-control border-primary " name="date_finish">
                                            <input type="hidden" name="form_index" value="{{ $index }}">
                                        </form>
                                    </td>
                                @endif
                                <td class="text-center">
                                    <form action="{{route('main.destroy', ['main' => $main->id])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-xs btn-danger" type="button" name="btn_delete" data-toggle="modal" data-target="#confirmDelete" data-title="Delete general task row" data-message="Are you sure you want to delete this row?">
                                            <i class="glyphicon glyphicon-trash"></i>&nbsp;x&nbsp;
                                        </button>
                                    </form>
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                @else
                    <p style="color:red">Workorders has no general tasks</p>
                @endif
            </div>
        </div>

    </section>



    @include('components.delete');

@endsection

@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            let mainTable = $('#main-index').DataTable({
                "order": [[0, 'asc']],
                "AutoWidth": true,
                "scrollY": "600px",
                "scrollX": false,
                "scrollCollapse": true,
                "paging": false,
                "info": false,
                "searching": false,
                "ordering": false,
            });

            //----------------------------- Ajax date_finish Geberal Task Update ---------------------

            let dateInputs = document.querySelectorAll('.task_date_finish');
            dateInputs.forEach(function (input) {
                input.addEventListener('change', function (event) {
                    showLoadingSpinner()
                    let formIndex = event.target.parentNode.querySelector('[name="form_index"]').value;
                    document.getElementById('form_date_finish_' + formIndex).submit();
                });
            });

            // delete form mainConfirm ------------------------------------------------------------------------------

            $('#confirmDelete').on('show.bs.modal', function (e) {
                let form = $(e.relatedTarget).closest('form');
                let message = $(e.relatedTarget).attr('data-message');
                $(this).find('.modal-body p').text(message);
                let title = $(e.relatedTarget).attr('data-title');
                $(this).find('.modal-title').text(title);
                $(this).find('.modal-footer #buttonConfirm').data('form', form);
                $('#buttonConfirm').on('click', function () {
                    $(this).data('form').submit();
                });
            });

            document.getElementById('general_task_form').addEventListener('submit', function (event) {
                let submitButton = document.getElementById('general_task_confirm');
                submitButton.disabled = true; // Отключаем кнопку отправки

            });


        });

    </script>

@endsection