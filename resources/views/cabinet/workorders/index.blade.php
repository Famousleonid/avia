@extends('admin.master')

@section('links')
    <style>
        .table-wrapper {
            height: calc(100vh - 170px);
            overflow-y: auto;
            overflow-x: hidden;
            display: none;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;

        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 400px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
        #currentUserCheckbox {
            cursor: pointer; /* Изменяем курсор при наведении */
        }
    </style>

@endsection

@section('content')

    <div class="card shadow">

        <div class="card-header my-1 ">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('Workorders')}}( <span class="text-success">{{$workorders->count()}} </span>)</h5>
                <a id="admin_new_firm_create" href={{route('cabinet.workorders.create')}} class=""><img src="{{asset('img/plus.png')}}" width="30px" alt="" data-toggle="tooltip" data-placement="top" title="Add new workorder"></a>
                <div class="d-flex align-items-center">
                    <input type="checkbox" id="currentUserCheckbox" style="width: 30px; height: 30px;" checked>
                    <label for="customCheckbox" class="ms-2">My workorders</label>
                </div>
                <div class="me-3">***</div>

            </div>
        </div>

        <div class="d-flex my-2">
            <div class="clearable-input ps-2">
                <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>

        @if(count($workorders))

            <div class="table-wrapper me-3 p-2 pt-0 ">

                <table id="show-workorder" class="display table-sm table-bordered table-striped table-hover w-100 shadow bg-body" >

                    {{--                    <thead style="background: linear-gradient(to bottom, #131313, #2E2E2E);">--}}

                    <thead class="bg-gradient">

                    <tr>
                        <th class="text-center text-primary bg-gradient sortable">Number<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary bg-gradient ">Approve</th>
                        <th class="text-center text-primary bg-gradient ">Unit</th>
                        <th class="text-center text-primary bg-gradient ">Description</th>
                        <th class="text-center text-primary bg-gradient ">Serial number</th>
                        <th class="text-center text-primary bg-gradient ">WO TDR</th>
                        <th class="text-center text-primary bg-gradient ">Manual</th>
                        <th class="text-center text-primary bg-gradient sortable">Customer<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary bg-gradient sortable">Instruction<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary bg-gradient sortable">Technik<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary bg-gradient ">Place</th>
                        <th class="text-center text-primary bg-gradient " data-orderable="false">Edit</th>
                        <th class="text-center text-primary bg-gradient ">Open Date</th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($workorders as $workorder)
                        <tr>
                            <td class="text-center">
                                <a class="text-decoration-none" href="{{route('cabinet.mains.show', ['main' => $workorder->id])}}">
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
                                    Amdt: {{$workorder->amdt}}
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('cabinet.tdrs.show', ['tdr' => $workorder->id]) }}"
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

    @include('components.delete')


@endsection

@section('scripts')


    <script>

        const currentUserName = "{{ auth()->user()->name }}";

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('show-workorder');
            const tableWrapper = document.querySelector('.table-wrapper');
            const searchInput = document.getElementById('searchInput');
            const headers = document.querySelectorAll('.sortable');
            const checkbox = document.getElementById('currentUserCheckbox');

            // Восстановление состояния чекбокса из localStorage
            const savedCheckboxState = localStorage.getItem('myWorkordersCheckbox');
            if (savedCheckboxState !== null) {
                checkbox.checked = savedCheckboxState === 'true';
            } else {
                checkbox.checked = true; // По умолчанию включён при первом входе
                localStorage.setItem('myWorkordersCheckbox', 'true'); // Сохраняем начальное состояние
            }

            function filterTable() {
                const filter = searchInput.value.toLowerCase();
                const showOnlyMyWorkorders = checkbox.checked;
                const rows = table.querySelectorAll('tbody tr');

               // showLoadingSpinner();

                    rows.forEach(row => {
                        const rowText = row.innerText.toLowerCase();
                        const technikName = row.querySelector('td:nth-child(10)').innerText.trim(); // Колонка Technik
                        const matchesSearch = rowText.includes(filter);
                        const matchesUser = showOnlyMyWorkorders ? technikName === currentUserName : true;
                        row.style.display = (matchesSearch && matchesUser) ? '' : 'none';
                    });

                //     hideLoadingSpinner();

            }

            // Sorting
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    // Icon
                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';

                    // Sorting row
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });

                    // Updating the table
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                    filterTable();
                });
            });

            // Search
            searchInput.addEventListener('input', filterTable);

            // Checkbox
            checkbox.addEventListener('change', () => {
                localStorage.setItem('myWorkordersCheckbox', checkbox.checked); // Сохраняем состояние
                filterTable(); // Фильтруем таблицу
            });

            // Применяем фильтр при загрузке страницы
            filterTable();
            tableWrapper.style.display = 'block';

            // --------------- Delete modal -----------------------------------------------------------------------------------
            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let deleteForm = null;
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                deleteForm = button.closest('form');
                const title = button.getAttribute('data-title');
                const modalTitle = modal.querySelector('#confirmDeleteLabel');
                modalTitle.textContent = title || 'Delete Confirmation';
            });
            confirmDeleteBtn.addEventListener('click', function () {
                if (deleteForm) {
                    deleteForm.submit();
                }
            });
            // --------------- End Delete modal -----------------------------------------------------------------------------------

        });

    </script>

@endsection


