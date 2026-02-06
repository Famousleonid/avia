@extends('admin.master')

@section('style')

        <style>
            :root{
                --roles-table-bg: rgba(33,37,41,.98); /* как твой thead */
                --roles-table-border: rgba(255,255,255,.12);
                --roles-table-text: rgba(248,249,250,.92);
                --roles-table-muted: rgba(248,249,250,.55);
            }

        .roles-topbar{
            border: 1px solid rgba(255,255,255,.08);
            border-radius: .75rem;
            background: linear-gradient(180deg, rgba(33,37,41,.92), rgba(52,58,64,.80));
        }

        /* панель вокруг таблицы */
        .roles-panel{
            border: 1px solid rgba(255,255,255,.10);
            border-radius: .75rem;
            background: linear-gradient(180deg, rgba(33,37,41,.92), rgba(52,58,64,.72));
            height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .roles-table-wrap{
            height: calc(100vh - 130px);
            overflow: auto;
            border-radius: .6rem;
        }


        #cmmTable{
            border-color: rgba(255,255,255,.12) !important;
        }
        #cmmTable th, #cmmTable td{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            border-color: rgba(255,255,255,.12) !important;
        }

        /* sticky header - строго тёмный (чтобы не белел) */
        #cmmTable thead th{
            position: sticky;
            top: 0;
            z-index: 3;
            background: rgba(33,37,41,.98) !important;   /* темно-серый */
            color: gray !important;
            box-shadow: 0 6px 10px rgba(0,0,0,.35);
        }

        /* hover/active в темной теме */
        #cmmTable tbody tr:hover{
            background: rgba(13,110,253,.10) !important;
        }
        #cmmTable tbody tr.is-active{
            background: rgba(13,110,253,.18) !important;
        }

        .btn-icon{
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        /* search input темный */
        .roles-search.form-control{
            background-color: rgba(33,37,41,.85);
            border-color: rgba(255,255,255,.15);
            color: rgba(248,249,250,.95);
        }
        .roles-search.form-control::placeholder{
            color: rgba(248,249,250,.45);
        }
        .roles-search.form-control:focus{
            border-color: rgba(13,110,253,.55);
            box-shadow: 0 0 0 .2rem rgba(13,110,253,.18);
        }

        #noResults{
            border: 1px dashed rgba(255,255,255,.15);
            border-radius: .6rem;
            background: rgba(33,37,41,.55);
            color: rgba(248,249,250,.85);
        }


        body.dark #cmmTable {
            background-color: #212529;
            color: #f8f9fa;
        }

        body.dark #cmmTable thead th {
            background: rgba(33,37,41,.98);
            color: #9ec5fe;
        }

        body.dark #cmmTable tbody tr:hover {
            background: rgba(13,110,253,.12);
        }

        body.light #cmmTable {
            background-color: #f8f9fa;
            color: #212529;
        }

        body.light #cmmTable thead th {
            background: #e9ecef;
            color: #0d6efd;
            box-shadow: 0 2px 6px rgba(0,0,0,.08);
        }

        body.light #cmmTable tbody tr:hover {
            background: rgba(13,110,253,.08);
        }

        body.dark .modal-theme {
            background: linear-gradient(180deg, #212529, #343a40);
            color: #f8f9fa;
            border: 1px solid rgba(255,255,255,.15);
        }

        body.dark .modal-theme .modal-header {
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        body.light .modal-theme {
            background: linear-gradient(180deg, #f8f9fa, #e9ecef);
            color: #212529;
            border: 1px solid rgba(0,0,0,.12);
        }

        body.light .modal-theme .modal-header {
            border-bottom: 1px solid rgba(0,0,0,.1);
        }
        body.dark .modal-theme .form-control {
            background-color: #212529;
            color: #f8f9fa;
            border-color: rgba(255,255,255,.2);
        }

        body.light .modal-theme .form-control {
            background-color: #fff;
            color: #212529;
            border-color: #ced4da;
        }

    </style>
@endsection

@section('content')

    <div class="card  border-0">

        <div class="card-header p-0  mx-3 bg-transparent border-0">
            <div class="roles-topbar px-3 py-1">
                <div class="row g-2 align-items-center">

                    <div class="col-12 col-lg-3">
                        <h5 class="mb-0 text-primary">
                            {{__('Roles')}}
                            <span class="text-secondary">(</span>
                            <span class="text-success">{{$roles->count()}}</span>
                            <span class="text-secondary">)</span>
                        </h5>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="d-flex justify-content-lg-center">
                            <div class="position-relative w-100" style="max-width:420px;">
                                <input id="searchInput" type="text" class="form-control pe-5 roles-search" placeholder="Search...">
                                <button type="button"
                                        class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 border-0 bg-transparent text-secondary"
                                        title="Clear"
                                        onclick="const i=document.getElementById('searchInput'); i.value=''; i.dispatchEvent(new Event('input')); i.focus();">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="d-flex justify-content-lg-end">
                            <button class="btn btn-outline-success btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="bi bi-plus-circle me-1"></i>{{ __('Add Role') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <div class="card-body pt-1 m-0">
            @if(count($roles))
                <div class="roles-panel p-1 ">
                    <div class="roles-table-wrap p-1 ">

                        <table id="cmmTable" class="table table-sm table-hover table-bordered mb-0 align-middle shadow-lg">
                            <thead>
                            <tr>
                                <th id="thName" class="text-primary sortable" data-direction="asc" style="min-width:240px;">
                                    {{__('Name')}} <i id="sortIcon" class="bi bi-chevron-expand ms-1"></i>
                                </th>
                                <th class="text-primary text-center" style="width:140px;">
                                    {{__('Action')}}
                                </th>
                            </tr>
                            </thead>

                            <tbody id="rolesTbody">
                            @foreach($roles as $role)
                                <tr data-row>
                                    <td title="{{ $role->name }}">{{ $role->name }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-outline-primary btn-sm btn-icon me-2"
                                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                                    onclick="populateEditModal({{ $role->id }}, @js($role->name))"
                                                    title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <button class="btn btn-outline-danger btn-sm btn-icon"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    onclick="populateDeleteModal({{ $role->id }}, @js($role->name))"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        {{-- No results --}}
                        <div id="noResults" class="d-none p-4 text-center">
                            <div class="text-secondary mb-2">
                                <i class="bi bi-search" style="font-size: 1.6rem;"></i>
                            </div>
                            <div class="fw-semibold text-dark">No results</div>
                            <div class="text-gray-500 small">Try another search term.</div>
                        </div>

                    </div>
                </div>
            @else
                <div class="alert alert-secondary mb-0">
                    Roles not created.
                </div>
            @endif
        </div>
    </div>

    {{-- MODALS: create --}}
    <div class="modal fade " id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-theme shadow">
                <div class="modal-header bg-body">
                    <h5 class="modal-title">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="createName" class="form-label">Name</label>
                            <input type="text" id="createName" name="name" class="form-control" required autofocus>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALS: Edit --}}
    <div class="modal fade " id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-theme shadow">
                <div class="modal-header bg-body">
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="{{ route('roles.update', ':id') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" id="editName" name="name" class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save2 me-1"></i>Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALS: Delete --}}
    <div class="modal fade " id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-theme shadow">
                <div class="modal-header bg-body">
                    <h5 id="deleteModalTitle" class="modal-title">Delete Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        Are you sure you want to delete this role?
                    </div>
                    <form id="deleteForm" method="POST" action="{{ route('roles.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1"></i>Delete
                            </button>
                        </div>
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
            if (!table) return;

            const tbody = document.getElementById('rolesTbody');
            const searchInput = document.getElementById('searchInput');
            const noResults = document.getElementById('noResults');

            const thName = document.getElementById('thName');
            const sortIcon = document.getElementById('sortIcon');

            tbody.addEventListener('click', (e) => {
                const tr = e.target.closest('tr[data-row]');
                if (!tr) return;
                tbody.querySelectorAll('tr.is-active').forEach(r => r.classList.remove('is-active'));
                tr.classList.add('is-active');
            });

            function applyFilter() {
                const filter = (searchInput.value || '').trim().toLowerCase();
                let visible = 0;

                tbody.querySelectorAll('tr[data-row]').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const show = text.includes(filter);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                const total = tbody.querySelectorAll('tr[data-row]').length;
                const showNo = total > 0 && visible === 0;

                table.classList.toggle('d-none', showNo);
                noResults.classList.toggle('d-none', !showNo);
            }

            function sortByName() {
                const direction = thName.dataset.direction === 'asc' ? 'desc' : 'asc';
                thName.dataset.direction = direction;

                sortIcon.className = (direction === 'asc')
                    ? 'bi bi-arrow-up ms-1'
                    : 'bi bi-arrow-down ms-1';

                const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
                rows.sort((a, b) => {
                    const aText = (a.querySelector('td:nth-child(1)')?.textContent || '').trim();
                    const bText = (b.querySelector('td:nth-child(1)')?.textContent || '').trim();
                    return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                });
                rows.forEach(r => tbody.appendChild(r));
                applyFilter();
            }

            searchInput?.addEventListener('input', applyFilter);
            thName?.addEventListener('click', sortByName);

            applyFilter();
        });

        function populateEditModal(id, name) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editForm').action =
                `{{ route('roles.update', ':id') }}`.replace(':id', id);
        }

        function populateDeleteModal(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action =
                `{{ route('roles.destroy', ':id') }}`.replace(':id', id);
            document.getElementById('deleteModalTitle').innerText = `Delete role (${name})`;
        }
    </script>
@endsection
