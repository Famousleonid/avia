@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 140px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;
            /*min-width: 80px;*/
            /*max-width: 190px;*/
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 50px;
            max-width: 70px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 80px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(5), .table td:nth-child(5) {
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
            .table th:nth-child(2), .table td:nth-child(2) {
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
                <h5 class="text-primary">{{__('Materials')}}( <span class="text-success">{{$materials->count()}} </span>)</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">{{ __('Add materials') }}</button>
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

        @if(count($materials))

            <div class="table-wrapper me-3 p-2 pt-0">

                <table id="cmmTable" class="display table table-sm table-hover table-striped table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient " data-direction="asc">{{__('Code')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient">{{__('Material')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient">{{__('Specification')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient">{{__('Description')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary text-center bg-gradient">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($materials as $material)
                        <tr>
                            <td class="">{{$material->code}}</td>
                            <td class="">{{$material->material}}</td>
                            <td class="">{{$material->specification}}</td>
                            <td class="">{{$material->description}}</td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal"
                                        data-bs-target="#editModal" onclick="populateEditModal({{ $material->id }}, '{{ $material->code }}', '{{ $material->material }}', '{{ $material->specification }}', '{{ $material->description }}')">
                                    <i class="bi bi-pencil-square" title="Edit"></i>
                                </button>
                                @roles('Admin|Manager')
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            onclick="populateDeleteModal({{ $material->id }}, '{{ $material->code }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endroles
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        @else
            <p>Materials not created</p>
        @endif
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="POST" action="{{ route('materials.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="createCode" class="form-label">Code</label>
                            <input type="text" id="createCode" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="createMaterial" class="form-label">Material</label>
                            <input type="text" id="createMaterial" name="material" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="createSpecification" class="form-label">Specification</label>
                            <input type="text" id="createSpecification" name="specification" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="createDescription" class="form-label">Description</label>
                            <textarea id="createDescription" name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="{{ route('materials.update', ':id') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editCode" class="form-label">Code</label>
                            <input type="text" id="editCode" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMaterial" class="form-label">Material</label>
                            <input type="text" id="editMaterial" name="material" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSpecification" class="form-label">Specification</label>
                            <input type="text" id="editSpecification" name="specification" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea id="editDescription" name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="deleteModalTitle" class="modal-title">Delete Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this material?</p>
                    <form id="deleteForm" method="POST" action="{{ route('materials.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            const searchInput = document.getElementById('searchInput');
            const headers = document.querySelectorAll('.sortable');

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
                });
            });

            // Search
            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();

                if (!filter || filter.length < 2) {
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                    hideLoadingSpinner();
                    return;
                }

                showLoadingSpinner();
                setTimeout(() => {
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                    hideLoadingSpinner();
                }, 100);
            });
        });

        function populateEditModal(id, code, material, specification, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editCode').value = code;
            document.getElementById('editMaterial').value = material;
            document.getElementById('editSpecification').value = specification;
            document.getElementById('editDescription').value = description;
            document.getElementById('editForm').action = `{{ route('materials.update', ':id') }}`.replace(':id', id);
        }

        function populateDeleteModal(id, code) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action = `{{ route('materials.destroy', ':id') }}`.replace(':id', id);
            document.getElementById('deleteModalTitle').innerText = `Delete Material (Code: ${code})`;
        }
    </script>
@endsection
