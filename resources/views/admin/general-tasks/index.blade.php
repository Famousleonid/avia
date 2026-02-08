@extends('admin.master')

@section('content')
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
    </style>



    <div class="card dir-panel">
        @include('components.status')
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('General task')}}( <span class="text-success">{{$general_tasks->count()}} </span>)</h5>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">{{ __('Add general task') }}</button>
            </div>
        </div>

{{--        <div class="d-flex my-2">--}}
{{--            <div class="clearable-input ps-2">--}}
{{--                <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">--}}
{{--                <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">--}}
{{--                    <i class="bi bi-x-circle"></i>--}}
{{--                </button>--}}
{{--            </div>--}}
{{--        </div>--}}

        @if(count($general_tasks))

            <div class="table-wrapper me-3 p-2 pt-0">

                <table id="cmmTable" class="display table table-sm table-hover table-bordered dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient " data-direction="asc">{{__('Name')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary sortable bg-gradient text-center" data-direction="asc">{{__('Sort line order')}}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary text-center bg-gradient">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($general_tasks as $general_tasks)
                        <tr>
                            <td class="">{{$general_tasks->name}}</td>
                            <td class="text-center">{{$general_tasks->sort_order}}</td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm me-2 js-edit-gt"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="{{ $general_tasks->id }}"
                                    data-name='@json($general_tasks->name)'
                                    data-sort="{{ $general_tasks->sort_order }}"
                                    data-hasstart="{{ (int)$general_tasks->has_start_date }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        onclick="populateDeleteModal({{ $general_tasks->id }}, '{{ $general_tasks->name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        @else
            <p>General task not created</p>
        @endif
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add General task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="POST" action="{{ route('general-tasks.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="createName" class="form-label">Name</label>
                            <input type="text" id="createName" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="createSort" class="form-label">Sort order</label>
                            <input type="text" id="createSort" name="sort_order" class="form-control" required>
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
                    <h5 class="modal-title">Edit General Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="editForm"
                          method="POST"
                          data-action="{{ route('general-tasks.update', 0) }}"
                          action="{{ route('general-tasks.update', 0) }}">
                        @csrf
                        @method('PUT')

                        <input type="hidden" id="editId" name="id">

                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" id="editName" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="sortName" class="form-label">Sort order</label>
                            <input type="number" id="sortName" name="sort_order" class="form-control" min="0" required>
                        </div>

                        <button type="submit" class="btn btn-primary" onclick="showLoadingSpinner()">Update</button>
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
                    <h5 id="deleteModalTitle" class="modal-title">Delete General Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this?</p>
                    <form id="deleteForm" method="POST" action="{{ route('general-tasks.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <button type="submit" class="btn btn-danger" onclick="showLoadingSpinner()">Delete</button>
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

            // =========================
            // SORTING
            // =========================
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    // icon
                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand ms-1';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc'
                        ? 'bi bi-arrow-up ms-1'
                        : 'bi bi-arrow-down ms-1';

                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });

                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // =========================
            // SEARCH
            // =========================
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const filter = searchInput.value.toLowerCase();
                    showLoadingSpinner?.();

                    setTimeout(() => {
                        const rows = table.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const text = row.innerText.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        });
                        hideLoadingSpinner?.();
                    }, 80);
                });
            }

            // =========================
            // EDIT MODAL: FILL FROM data-*
            // =========================
            const editModalEl = document.getElementById('editModal');
            if (editModalEl) {
                editModalEl.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const id = btn.getAttribute('data-id') ?? '';
                    const nameJson = btn.getAttribute('data-name') ?? '""';
                    const sort = btn.getAttribute('data-sort') ?? '';
                    const hasStart = btn.getAttribute('data-hasstart') ?? '0';

                    let name = '';
                    try { name = JSON.parse(nameJson); } catch (e) { name = nameJson; }

                    document.getElementById('editId').value = id;
                    document.getElementById('editName').value = name;
                    document.getElementById('sortName').value = sort;

                    // ✅ безопасно: не ломаемся если чекбокса нет
                    const hasStartEl = document.getElementById('hasStart');
                    if (hasStartEl) hasStartEl.checked = String(hasStart) === '1';

                    // ✅ правильная сборка action
                    const form = document.getElementById('editForm');
                    const baseAction = form.dataset.action; // .../general-tasks/0
                    form.action = baseAction.replace(/\/0$/, '/' + id);
                });

                // (опционально) чистим форму при закрытии
                editModalEl.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('editId').value = '';
                    document.getElementById('editName').value = '';
                    document.getElementById('sortName').value = '';

                    const hasStartEl = document.getElementById('hasStart');
                    if (hasStartEl) hasStartEl.checked = false;

                    const form = document.getElementById('editForm');
                    form.action = form.dataset.action;
                });
            }

        });

        // =========================
        // DELETE MODAL
        // =========================
        function populateDeleteModal(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action =
                `{{ route('general-tasks.destroy', ':id') }}`.replace(':id', id);

            document.getElementById('deleteModalTitle').innerText =
                `Delete general task (${name})`;
        }
    </script>


@endsection
