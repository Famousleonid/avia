@extends('cabinet.master')

@section('link')
    <style>
        .sf {
            font-size: 12px;
        }
    </style>

    <style>
        .image-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .image-wrapper span {
            margin-bottom: 5px; /* Отступ между названием и картинкой */
        }
    </style>


@endsection

@section('content')

    <section class="container">
        <div class="card firm-border pb-1 bg-white shadow ">
            <div class="card-body p-0 row ">
                <div class="col-lg-2 ">

                    <div class="col">
                        <h5 class="modal-title text-primary text-bold">&nbsp;w {{$current_workorder->number}}</h5>
                        @if($current_workorder->approve)
                            &nbsp;<img class="" src="{{asset('img/ok.png')}}" width="20px" alt=""><span class="sf text-success">&nbsp;approved</span>
                        @else
                            &nbsp;<span class="sf text-gray">&nbsp;not approved</span>
                        @endif
                    </div>
                    <span class="h6 text-primary text-bold ml-1">Component item</span>
                </div>
                <div class="form-group col-lg-10 ">
                    <form id="component_main_form" action="{{route('component_main.create', ['workorder_id' => $current_workorder->id])}}" class="col-lg-12 row">
                        @csrf
                        <input type="text" hidden name="workorder_id" value="{{$current_workorder->id}}">
                        <div class="form-group col-lg">
                            <label class="sf" for="select_component">select component </label>
                            <select name="select_component" id="select_component" class="form-control">
                                <option disabled selected value=""> -- select an option --</option>
                                @foreach ($components as $component)
                                    <option data-custom="{{$component->name}}">{{$component->name}} </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3">
                            <label class="sf" for="write_component">Write component <span style="color:red; font-size: x-small">(required)</span></label>
                            <input type="text" class="form-control" id="write_component" name="component">
                        </div>
                        <div class="form-group col-lg ">
                            <label class="sf" for="select_task_id">Task <span style="color:red; font-size: x-small">(required)</span></label>
                            <select name="task_id" id="select_task_component" class="form-control">
                                <option disabled selected value=""> -- select an option --</option>
                                @foreach ($tasks as $task)
                                    <option value="{{$task->id}}">{{$task->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg ">
                            <label class="sf" for="user_id">Technik</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option selected value="{{ Auth::user()->id }}">{{ Auth::user()->name }}</option>
                                @foreach ($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3">
                            <label class="sf" for="description">Description:</label>
                            <input id="description" class="form-control" name="description" maxlength="256" size="20"/>
                        </div>
                        <div class="form-group col-lg-1 mt-4">
                            <button id="component_main_confirm" type="submit" class="btn btn-info" style="margin-top: 5px">Add</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-footer row ">

                <div class="col-1">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold">NDT</span>
                        <a href="{{route('ndt.excel.export',['workorder_id' => $current_workorder->id])}}">
                            <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">
                        </a>
                    </div>
                </div>

                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold">CAD</span>
                        <a href="{{route('cad.excel.export',['workorder_id' => $current_workorder->id])}}">
                            <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">
                        </a>
                    </div>
                </div>

                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold text-gray">Special</span>

                        <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">

                    </div>
                </div>
                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold text-gray">Part List</span>

                        <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">

                    </div>
                </div>
                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold">...</span>

                        <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">

                    </div>
                </div>
                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold">...</span>

                        <img src="{{asset('img/icons/icon-excel.png')}}" width="40" alt="">

                    </div>
                </div>
                <div class="col-1 ">
                    <div class="image-wrapper shadow p-1">
                        <span class="text-sm text-bold">...</span>
                        <a href="#">
                            <img src="" width="40" alt="">
                        </a>
                    </div>
                </div>
            </div>
            <div class="container-fluid" style="padding-left: 18%;">
                @if(count($component_mains))
                    <table id="main-component" class="display table-sm table-bordered table-striped table-hover" style="width:100%;">
                        <thead>
                        <tr>
                            <th hidden>*</th>
                            <th>Component</th>
                            <th>Task</th>
                            <th>Technik</th>
                            <th>Description</th>
                            <th class="text-center">Date Start</th>
                            <th class="text-center">Date Finish</th>
                            <th class="text-center">Excel</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($component_mains as $index2 => $component_main)
                            <tr>
                                <td hidden>{{$component_main->created_at}}</td>
                                <td>{{$component_main->component}}</td>
                                <td>{{$component_main->task->name}}</td>
                                <td>{{$component_main->user->name}}</td>
                                <td>{{$component_main->description}}</td>
                                <td class="text-center"><span>{{date('d-M-Y',strtotime($component_main->date_start))}}</span></td>

                                @if($component_main->date_finish)
                                    <td class="text-center"><span>{{date('d-M-Y', strtotime($component_main->date_finish))}}</span></td>
                                @else
                                    <td class="text-center">
                                        <form id="form_date_finish_item_{{$index2}}" name="form_date_finish_item_{{$index2}}" action="{{route('component_main.update', ['component_main' => $component_main->id])}}" method="post">
                                            @csrf
                                            @method('PUT')
                                            <input type="date" class="task_date_finish form-control border-primary " name="date_finish">
                                            <input type="hidden" name="form_index" value="{{ $index2 }}">
                                        </form>
                                    </td>
                                @endif

                                <td class="text-center">
                                    <a href=""><img src="{{asset('img/icons/icon-excel.png')}}" alt="" width="40px"></a>
                                </td>

                                <td class="text-center"><img src="{{asset('img/set.png')}}" width="20px" alt=""></td>

                                <td class="text-center">
                                    <form action="{{route('component_main.destroy', ['component_main' => $component_main->id])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-xs btn-danger" type="button" data-toggle="modal" data-target="#confirmDelete" data-title="Delete component task row" data-message="Are you sure you want to delete this row?">
                                            <i class="glyphicon glyphicon-trash"></i>&nbsp;x&nbsp;
                                        </button>
                                    </form>
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                @else
                    <p style="color:red; padding-left: 9%">Workorders has no component</p>
                @endif
            </div>
        </div>
    </section>

    @include('components.delete');

@endsection

@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            let componentTable = $('#main-component').DataTable({
                "order": [[1, 'asc'], [0, 'asc']],
                "AutoWidth": true,
                "scrollY": "600px",
                "scrollX": false,
                "scrollCollapse": true,
                "paging": false,
                "info": false,
                "searching": false,
                "ordering": false,
            });

            //----------------------------- Ajax date_finish Component Item Update ---------------------

            let dateItems = document.querySelectorAll('.task_date_finish');
            dateItems.forEach(function (input) {
                input.addEventListener('change', function (event) {
                    showLoadingSpinner()
                    let formIndex = event.target.parentNode.querySelector('[name="form_index"]').value;
                    document.getElementById('form_date_finish_item_' + formIndex).submit();
                });
            });

            // write input form component -----------------------------------------------------------------------

            let select = document.getElementById("select_component");
            select.onchange = function (event) {
                document.getElementById("write_component").value = event.target.options[event.target.selectedIndex].dataset.custom;
            };

            document.getElementById("component_main_confirm").addEventListener("click", function (event) {
                let form = document.getElementById("component_main_form");
                if (!(write_component() && select_task_component())) {
                    event.preventDefault();
                } else {
                    showLoadingSpinner()
                    form.submit();
                }
            });

            //---------------------------------------------------------------------------------------------------------

            function write_component() {
                if ($('#write_component').val() == "") {
                    $(('#write_component')).addClass('is-invalid');
                    setTimeout("$(('#write_component')).removeClass('is-invalid')", 3000);
                    return false;
                } else {
                    return true
                }
            }

            function select_task_component() {
                if ($('#select_task_component').val() == null) {
                    $(('#select_task_component')).addClass('is-invalid');
                    setTimeout("$(('#select_task_component')).removeClass('is-invalid')", 3000);
                    return false;
                } else {
                    return true
                }
            }

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


        });
    </script>

@endsection
