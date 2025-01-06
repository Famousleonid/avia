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
            top: 0;
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
                <h5 class="text-primary">{{__('Manage CMMs')}}</h5>
                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';
                    document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                <a href="{{ route('admin.manuals.create') }}" class="btn btn-outline-primary" style="height: 40px">{{ __('Add CMM') }}</a>

            </div>
        </div>



        @if(count($cmms))

            <div class="table-wrapper me-3 p-2 pt-0">

                <table id="cmmTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient " data-direction="asc">{{__('Number')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient">{{__('Title')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient">{{__('Units PN')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary text-center bg-gradient">{{__('Image')}}</th>
                        <th class="text-primary text-center bg-gradient">{{__('Rev.Date')}}</th>
                        <th class="text-primary text-center  sortable bg-gradient" data-direction="asc">{{__('Lib')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary text-center bg-gradient">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cmms as $cmm)
                        <tr>
                            <td>{{$cmm->number}}</td>
                            <td title="{{$cmm->title}}">{{$cmm->title}}</td>
                            <td title="{{$cmm->unit_name}}">{{$cmm->unit_name}}</td>
                            <td class="text-center">
                                <a href="{{ $cmm->getBigImageUrl('manuals') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $cmm->getThumbnailUrl('manuals') }}" width="40" height="40" alt="Image"/>
                                </a>
                            </td>
                            <td class="text-center">{{$cmm->revision_date}}</td>
                            <td class="text-center">{{$cmm->lib}}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.manuals.edit', ['manual' => $cmm->id]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form id="deleteForm_{{$cmm->id}}" action="{{ route('admin.manuals.destroy', ['manual' => $cmm->id]) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="button" name="btn_delete" data-bs-toggle="modal"
                                            data-bs-target="#useConfirmDelete" data-title="Delete Confirmation row {{$cmm->number}}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                    <p>Manuals not created</p>
                @endif
            </div>
    </div>

    @include('components.delete')


@endsection

@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Sorting
            const table = document.getElementById('cmmTable');
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
// --------------- Delete modal -----------------------------------------------------------------------------------

        });
    </script>

@endsection
