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
                <h5 class="text-primary manage-header">{{__('Manage Components')}}( <span class="text-success">{{$components->count()}}
                    </span>)
                </h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
{{--                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';--}}
{{--                        document.getElementById('searchInput').dispatchEvent(new Event('input'))">--}}
{{--                            <i class="bi bi-x-circle"></i>--}}
{{--                        </button>--}}
                    </div>
                </div>
{{--                <button class="btn btn-outline-primary mb-1" style="height: 40px"--}}
{{--                        data-bs-toggle="modal"--}}
{{--                        data-bs-target="#addUnitModal">{{__('Add Component')}}--}}
{{--                </button>--}}
            </div>
        </div>

        @if(count($components))
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="componentTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center text-primary bg-gradient ">Unit</th>
                    <th class="text-center text-primary bg-gradient ">Description</th>
                    <th class="text-center text-primary bg-gradient ">Part number</th>
                    <th class="text-center text-primary bg-gradient ">Manual</th>
                    <th class="text-center text-primary bg-gradient ">IPL Number</th>
                    <th class="text-center text-primary bg-gradient ">Action</th>
                </tr>
                </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>

        @else
            <H5 CLASS="text-center">{{__('COMPONENTS NOT CREATED')}}</H5>

        @endif

    </div>
    <script>
        // Sorting
        const table = document.getElementById('unitTable');
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

    </script>
@endsection
