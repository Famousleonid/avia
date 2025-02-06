@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 80px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 50px;
            max-width: 70px;
        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
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
    </style>

    <div class="card shadow">
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('Manage Processes')}}( <span class="text-success"> </span>)</h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';
                    document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

{{--                <a href="{{ route('admin.processes.create') }}" class="btn btn-outline-primary " style="height: 40px">{{--}}
{{--                __('Add Process')--}}
{{--                }}</a>--}}
            </div>
        </div>

        <div class="table-wrapper me-3 p-2 pt-0">
            <table id="processTable" class="display table table-hover table-striped align-middle table-bordered">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-primary sortable text-center" style="width: 300px">{{__('Manual')}}</th>
                    <th class="text-primary sortable text-center">{{__('Description')}}</th>
                    <th class="text-primary sortable text-center">{{__('Process')}}</th>
                    <th class="text-primary text-center">{{__('Action')}}</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($manuals as $manual)
                        <tr>
                            <td class="text-center" style="width: 300px" >
                                <a href="#" data-bs-toggle="modal"
                                   data-bs-target="#cmmModal{{$manual->id}}">
                                    {{ $manual->number }}
                                </a>
                            </td>
                            <td class="text-center" style="width: 150px">
                                {{$manual->title}} ( {{$manual->unit_name_training}})
                            </td>
                            <td class="text-center" ></td>
                            <td class="text-center" style="width: 150px">
                                <a href="{{ route('admin.processes.create',['manual'=>$manual->id]) }}" class="btn
                                btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-lg"></i>

                                </a>
                            </td>
                        </tr>






                        <div class="modal fade" id="cmmModal{{$manual->id }}" tabindex="-1"
                             role="dialog" aria-labelledby="cmmModalLabel{{$manual->id }}"
                             aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient">
                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title"
                                                id="imageModalLabel{{ $manual->id }}">
                                                {{ $manual->title }}{{__(': ')}}
                                            </h5 >
                                            <h6>{{ $manual->unit_name_training }}</h6>
                                        </div>
                                        <button type="button"
                                                class="btn-close pb-2"
                                                data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <img class="" src="{{ $manual->getBigImageUrl('manuals') }}"
                                                     width="200"  alt="Image"/>

                                            </div>
                                            <div>
                                                <p><strong>{{ __('CMM:') }}</strong> {{ $manual->number }}</p>
                                                <p><strong>{{ __('Description:') }}</strong>
                                                    {{ $manual->title }} </p>
                                                <p><strong>{{ __('Revision Date:')}}</strong> {{ $manual->revision_date }}</p>
                                                <p><strong>{{ __('Library:') }}</strong> {{$manual->lib }}</p>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    @endforeach
                </tbody>
            </table>
        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Sorting
            const table = document.getElementById('processTable');
            const headers = document.querySelectorAll('.sortable');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;
                    rows.sort((a, b) => {
                        const aText = a.cells[columnIndex].innerText.trim();
                        const bText = b.cells[columnIndex].innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // Search
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        });

    </script>
@endsection
