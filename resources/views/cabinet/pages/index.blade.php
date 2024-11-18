@extends('cabinet.master')

@section('link')

    <style>
        .context-menu ul {
            padding: 0;
            margin: 0;
            min-width: 150px;
            list-style: none;
        }

        .context-menu ul li {
            padding-bottom: 7px;
            padding-top: 7px;
            border: 1px solid black;
        }

        .context-menu ul li a {
            text-decoration: none;
            color: black;
        }

        .context-menu ul li:hover {
            background: darkgray;
        }

        .input-div {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification {
            position: absolute;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 5px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }

        .dataTables_wrapper {
            font-size: 14px;
        }

        #show-main tbody tr {
            display: none;
        }

    </style>

@endsection

@section('content')

    <section class="mt-1">

        <div class="card firm-border p-1 shadow">
            <div class="row ">

                <div class="col-12 col-md-2 input-div">
                    <span class="h6">All workorders: </span>&nbsp; <span class="text-primary h5 text-right" id="orders_count" style="display:inline-block; min-width:4ch">{{count($workorders)}}</span>
                </div>
                <div class="col-12 col-md-2 input-div ">
                    <label id="label-color-team" class="container-checkbox text-gray" for="checkbox-my-team">
                        My team
                        <input id="checkbox-my-team" type="checkbox">
                        <span class="checkmark"></span>
                    </label>
                    <div class="notification" id="notification-my-team">You are not a team leader!</div>
                </div>

                <div class="col-12 col-md-2 input-div ">
                    <label id="label-color-approve" class="checkbox-approve-name container-checkbox text-gray" for="checkbox-approve">
                        Approved only
                        <input id="checkbox-approve" type="checkbox">
                        <span class="checkmark"></span>
                    </label>
                </div>

                <div class="col-12 col-md-2 input-div ">
                    <label id="label-color-my" class="checkbox-my-name container-checkbox text-gray" for="checkbox-my-only">
                        My only
                        <input id="checkbox-my-only" type="checkbox">
                        <span class="checkmark"></span>
                    </label>
                </div>

                <div class="col-12 col-md-3 input-div ">
                    <a id="new_workorder_create" href={{route('workorder.create')}} class=""><img src="{{asset('img/plus.png')}}" width="33px" alt="" data-toggle="tooltip" data-placement="top" title="Add new workorder"></a>
                    <span class="ml-1 ">ADD new workorder</span>
                </div>
            </div>
        </div>

        @if(count($workorders))

            <table data-page-length='22' id="show-main" class="display table-sm table-bordered table-striped table-hover main-table responsive nowrap table-dark" style="width: 100%">
                <thead>
                <tr style="font-size: 12px">
                    <th hidden>Id</th>
                    <th class="text-center" data-sort="true">Number</th>
                    <th>Approve</th>
                    <th>Unit</th>
                    <th>Amdt</th>
                    <th>Ser.Num.</th>
                    <th>Instruction</th>
                    <th>Customer</th>
                    <th>Lib</th>
                    <th>Technik</th>
                    <th>Deccription</th>
                    <th>Place</th>
                    <th class="text-center" data-orderable="false">*</th>
                    <th class="text-center">Date</th>
                </tr>
                </thead>
                <tbody>

                @foreach ($workorders as $workorder)

                    <tr class="{{ implode(' ', $workorder->class) }}">
                        <td hidden>{{$workorder->id}}</td>
                        <td class="">
                            <a href="{{route('main.index', ['workorder_id' => $workorder->id])}}">
                                w<span style="font-size: 14px" id="" class="text-bold">{{$workorder->number}}</span>
                            </a>
                        </td>
                        <td class="">
                            <a class="change_approve" href="#">
                                @if($workorder->approve)
                                    <img data-toggle="tooltip" title="@if($workorder->approve_at) {{$workorder->approve_at->format('d.m.Y')}} @endif" src="{{asset('img/ok.png')}}" width="20px" alt="">
                                @else
                                    <img src="{{asset('img/icon_no.png')}}" width="12px" alt="">
                                @endif
                            </a>
                        </td>
                        <td class="">{{$workorder->unit->partnumber}}</td>
                        <td hidden>{{$workorder->amdt}}</td>
                        <td class="">{{$workorder->serial_number}}</td>
                        <td class="">{{$workorder->instruction->name}}</td>
                        <td class="">{{$workorder->customer->name}}</td>
                        <td class="">{{$workorder->lib}}</td>
                        <td class="">{{$workorder->user->name}}</td>
                        <td class="" >{{$workorder->description}}</td>
                        <td class="">{{$workorder->place}}</td>
                        <td class="text-center">
                            <a class="change_approve" href="#">
                                <img src="{{asset('img/icons/component.png')}}" width="25px" alt="">
                            </a>
                        </td>
                        @if(Auth()->user()->getRole() === 2)
                            <td class="text-center"><a href="{{route('workorder.edit',$workorder->id)}}"><img src="{{asset('img/set_active2.png')}}" data-toggle="tooltip" title="edit" width="25px" alt=""></a></td>
                        @else
                            <td class="text-center"><img src="{{asset('img/set.png')}}" data-toggle="tooltip" title="you are not allowed to edit" width="20px" alt=""></td>
                        @endif
                        <td class="text-center"><span style="display: none">{{$workorder->created_at->format('Ymd')}}</span>{{$workorder->created_at->format('d.m.Y')}}</td>
                    </tr>

                @endforeach

                </tbody>
            </table>
        @else
            <p>Workorders not created</p>
        @endif

    </section>

@endsection

@section('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function () {
                const mainTable = document.getElementById('show-main');
                /*------------------------------------------------------------------------------------------*/
                $('#confirmDelete').find('.modal-footer #confirm').on('click', function () {
                    $(this).data('form').submit();
                });
                $("#mainWorkorder").on('show.bs.modal', function (e) {
                    let number = $(e.relatedTarget).attr('data-message');
                    $(this).find('#title_workorder').text(number);
                });
                $(".change_approve").on('click', function () {
                    showLoadingSpinner();
                });
                /*------------------------------------------------------------------------------------------*/


                const myOnly = document.getElementById('checkbox-my-only');
                const savedMyOnly = localStorage.getItem('myOnly');
                const myTeam = document.getElementById('checkbox-my-team');
                const savedMyTeam = localStorage.getItem('myTeam');
                const approve = document.getElementById('checkbox-approve');
                const savedApprove = localStorage.getItem('approve');

                myOnly.checked = savedMyOnly === 'yes';
                myTeam.checked = savedMyTeam === 'yes';
                approve.checked = savedApprove === 'yes';

                let mTable = $(mainTable).DataTable({
                    "AutoWidth": true,
                    "scrollY": "600px",
                    "scrollX": false,
                    "scrollCollapse": true,
                    "paging": false,
                    "info": false,
                    "order": [[1, 'desc']],
                    "responsive": false,
                    "columnDefs": [
                        {"width": "0%", "targets": 0},
                        {"width": "5%", "targets": 1}, // -- number
                        {"width": "2%", "targets": 2}, // -- approve
                        {"width": "8%", "targets": 4}, // -- unit
                        {"width": "8%", "targets": 4}, // -- Amdt
                        {"width": "8%", "targets": 4}, // -- Serial num
                        {"width": "3%", "targets": 6},  // -- instruction
                        {"width": "2%", "targets": 7}, // -- customer
                        {"width": "12%", "targets": 9},  // -- lob
                        {"width": "10%", "targets": 8}, // -- technik
                        {"width": "20%", "targets": 5}, //-- description
                        {"width": "3%", "targets": 11}, // -- place
                        {"width": "2%", "targets": 13}, // -- edit
                        {"width": "3%", "targets": 14}, // -- date
                    ],
                });

                applyInitialFilters();

                /*------------------------------------------------------------------------------------------*/

                $(myTeam).click(function (e) {
                    if (userRole !== 1) {
                        e.preventDefault();
                        $('#notification-my-team').show();
                        setTimeout(function () {
                            $('#notification-my-team').hide();
                        }, 3000);
                        return false;
                    }
                    // Отключаем чекбокс "My only" при включении "My team"
                    if ($(this).is(':checked')) {
                        $(myOnly).prop('checked', false);
                        localStorage.setItem('myOnly', 'no');
                    }
                    localStorage.setItem('myTeam', myTeam.checked ? 'yes' : 'no');
                    updateTableRowsVisibility();
                });

                $(myOnly).click(function () {
                    // Отключаем чекбокс "My team" при включении "My only"
                    if ($(this).is(':checked')) {
                        $(myTeam).prop('checked', false);
                        localStorage.setItem('myTeam', 'no');
                    }
                    localStorage.setItem('myOnly', myOnly.checked ? 'yes' : 'no');
                    updateTableRowsVisibility();
                });

                $(approve).click(function () {
                    localStorage.setItem('approve', approve.checked ? 'yes' : 'no');
                    updateTableRowsVisibility();
                });

                /*------------------------------------------------------------------------------------------*/

                function applyInitialFilters() {
                    const approveChecked = savedApprove === 'yes';
                    const myOnlyChecked = savedMyOnly === 'yes';
                    const myTeamChecked = savedMyTeam === 'yes';

                    $(mainTable).find('tr').hide();

                    if (approveChecked && myOnlyChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-approve.row-my.row-my-team').show();
                    } else if (approveChecked && myOnlyChecked) {
                        $(mainTable).find('tr.row-approve.row-my').show();
                    } else if (approveChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-approve.row-my-team').show();
                    } else if (myOnlyChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-my.row-my-team').show();
                    } else if (approveChecked) {
                        $(mainTable).find('tr.row-approve').show();
                    } else if (myOnlyChecked) {
                        $(mainTable).find('tr.row-my').show();
                    } else if (myTeamChecked) {
                        $(mainTable).find('tr.row-my-team').show();
                    } else {
                        $(mainTable).find('tr').show();
                    }
                    const visibleRowsCount = $(mainTable).find('tr:visible').length;
                    $("#orders_count").text(visibleRowsCount);
                    mTable.draw();
                }

                /*------------------------------------------------------------------------------------------*/

                function updateTableRowsVisibility() {
                    const approveChecked = $('#checkbox-approve').is(':checked');
                    const myOnlyChecked = $('#checkbox-my-only').is(':checked');
                    const myTeamChecked = $(myTeam).is(':checked');

                    $(mainTable).find('tr').hide();

                    if (approveChecked && myOnlyChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-approve.row-my.row-my-team').show();
                    } else if (approveChecked && myOnlyChecked) {
                        $(mainTable).find('tr.row-approve.row-my').show();
                    } else if (approveChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-approve.row-my-team').show();
                    } else if (myOnlyChecked && myTeamChecked) {
                        $(mainTable).find('tr.row-my.row-my-team').show();
                    } else if (approveChecked) {
                        $(mainTable).find('tr.row-approve').show();
                    } else if (myOnlyChecked) {
                        $(mainTable).find('tr.row-my').show();
                    } else if (myTeamChecked) {
                        $(mainTable).find('tr.row-my-team').show();
                    } else {
                        $(mainTable).find('tr').show();
                    }
                    const visibleRowsCount = $(mainTable).find('tr:visible').length;
                    $("#orders_count").text(visibleRowsCount);
                    mTable.draw();
                }
            }
        );
    </script>

@endsection


