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
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('WORK ORDER TEAR DOWN REPORT')}}( <span class="text-success">{{$orders->count()}} </span>)</h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                    </div>
                </div>
            </div>
        </div>

        @if(count($orders))
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="tdrTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                    <thead class="bg-gradient">
                        <tr>
                            <th class="text-center text-primary bg-gradient ">Number</th>
                            <th class="text-center text-primary bg-gradient ">Unit</th>
                            <th class="text-center text-primary bg-gradient ">Description</th>
                            <th class="text-center text-primary bg-gradient ">Serial number</th>
                            <th class="text-center text-primary bg-gradient ">Customer</th>
                            <th class="text-center text-primary bg-gradient ">Technik</th>
                            <th class="text-center text-primary bg-gradient ">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>

                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
        @else
            <p>Work Orders TDR not created</p>
        @endif
    </div>


@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Sorting
            const table = document.getElementById('tdrTable');
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

            // --------------- Delete modal -----------------------------------------------------------------------------------

            // const modal = document.getElementById('useConfirmDelete');
            // const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            // let deleteForm = null;
            // modal.addEventListener('show.bs.modal', function (event) {
            //     const button = event.relatedTarget;
            //     deleteForm = button.closest('form');
            //     const title = button.getAttribute('data-title');
            //     const modalTitle = modal.querySelector('#confirmDeleteLabel');
            //     modalTitle.textContent = title || 'Delete Confirmation';
            // });
            // confirmDeleteBtn.addEventListener('click', function () {
            //     if (deleteForm) {
            //         deleteForm.submit();
            //     }
            // });
            // --------------- Delete modal -----------------------------------------------------------------------------------

        });
    </script>


@endsection
