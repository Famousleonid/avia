{{--customer --}}

@extends('admin.master')

@section('content')
    <style>
        .table-wrapper{
            flex: 1 1 auto;
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
            height: 32px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        .table th.sortable { cursor: pointer; }

        /* dir-* helpers */
        .dir-header-row { align-items: center; }
        .dir-title { margin: 0; }
        .dir-actions { display:flex; justify-content:flex-end; gap:.5rem; }

        .dir-search-wrap { display:flex; justify-content:center; }

        .dir-search {
            position: relative;
            width: 520px;
            max-width: 100%;
        }

        .dir-search .form-control { padding-right: 2.25rem; }

        .dir-search .btn-clear {
            position: absolute;
            right: .45rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
    </style>

    <div class="card dir-page ">

        {{-- HEADER: left title / center search / right add --}}
        <div class="card-header my-1 dir-header">
            <div class="row g-2 dir-header-row">

                {{-- Left: title --}}
                <div class="col-12 col-md-3">
                    <h5 class="text-primary dir-title">
                        {{__('Customers')}} (
                        <span class="text-success">{{$customers->count()}}</span>
                        )
                    </h5>
                </div>

                {{-- Center: search --}}
                <div class="col-12 col-md-6 dir-search-wrap">
                    <div class="dir-search">
                        <input id="searchInput" type="text" class="form-control form-control-sm w-100"
                               placeholder="Search...">
                        <button type="button" class="btn-clear text-secondary" id="clearSearchBtn" title="Clear">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- Right: actions --}}
                <div class="col-12 col-md-3">
                    <div class="dir-actions">
                        <button class="btn btn-outline-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#createModal">
                            {{ __('Add customer') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>

        @if(count($customers))
            <div class="table-wrapper me-3 p-2 pt-0 dir-panel">
                <table id="cmmTable" class="display table table-sm table-hover table-bordered dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient" data-direction="asc">
                            {{__('Name')}}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm me-2"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        onclick="populateEditModal({{ $customer->id }}, @js($customer->name))">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        onclick="populateDeleteModal({{ $customer->id }}, @js($customer->name))">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="p-3 mb-0">Customer not created</p>
        @endif
    </div>

    {{-- Create Modal --}}
    <div class="modal fade dir-modal" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog dir-modal-dialog">
            <div class="modal-content dir-modal-content">
                <div class="modal-header dir-modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body dir-modal-body">
                    <form id="createForm" method="POST" action="{{ route('customers.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="createName" class="form-label">Name</label>
                            <input type="text" id="createName" name="name" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade dir-modal" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog dir-modal-dialog">
            <div class="modal-content dir-modal-content">
                <div class="modal-header dir-modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body dir-modal-body">
                    <form id="editForm" method="POST" action="{{ route('customers.update', ':id') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" id="editName" name="name" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary" onclick="showLoadingSpinner()">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade dir-modal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog dir-modal-dialog">
            <div class="modal-content dir-modal-content">
                <div class="modal-header dir-modal-header">
                    <h5 id="deleteModalTitle" class="modal-title">Delete Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body dir-modal-body">
                    <p>Are you sure you want to delete this customer?</p>
                    <form id="deleteForm" method="POST" action="{{ route('customers.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <button type="submit" class="btn btn-outline-danger" onclick="showLoadingSpinner()">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function debounce(fn, ms) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), ms);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            const tbody = table.querySelector('tbody');
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const headers = document.querySelectorAll('.sortable');

            // Sorting
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand ms-1';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc'
                        ? 'bi bi-arrow-up ms-1'
                        : 'bi bi-arrow-down ms-1';

                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => tbody.appendChild(row));

                    // применим поиск после сортировки
                    applyFilter(searchInput.value);
                });
            });

            // Search (обычный) + debounce
            let rowCache = [];

            function buildCache() {
                rowCache = Array.from(tbody.querySelectorAll('tr')).map(tr => ({
                    tr,
                    text: tr.innerText.toLowerCase()
                }));
            }

            function applyFilter(raw) {
                const q = (raw || '').trim().toLowerCase();

                if (typeof window.showLoadingSpinner === 'function') window.showLoadingSpinner();

                requestAnimationFrame(() => {
                    rowCache.forEach(r => {
                        r.tr.style.display = (!q || r.text.includes(q)) ? '' : 'none';
                    });

                    if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
                });
            }

            const debouncedFilter = debounce(applyFilter, 200);

            buildCache();

            searchInput.addEventListener('input', (e) => debouncedFilter(e.target.value));

            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                applyFilter('');
                searchInput.focus();
            });

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    applyFilter('');
                }
            });
        });

        function populateEditModal(id, name) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name ?? '';
            document.getElementById('editForm').action = `{{ route('customers.update', ':id') }}`.replace(':id', id);
        }

        function populateDeleteModal(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action = `{{ route('customers.destroy', ':id') }}`.replace(':id', id);
            document.getElementById('deleteModalTitle').innerText = `Delete customer (${name})`;
        }
    </script>
@endsection
