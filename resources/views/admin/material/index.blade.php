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
            min-width: 80px;
            max-width: 190px;
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
                <h5 class="text-primary">{{__('Materials')}}</h5>
                <a href="{{ route('admin.materials.create') }}"
                   class="btn btn-primary btn-sm ">{{ __('Add materials') }}</a>
            </div>
        </div>

        <div class="d-flex my-2">
            <div class="clearable-input ps-2">
                <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';
                    document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>

        @if(count($materials))

            <div class="table-wrapper me-3 p-2 pt-0">

                <table id="cmmTable" class="display table table-sm table-hover table-striped">
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
                            <td class="text-center">*</td>
                            <td class="text-center">
                                <a href="{{ route('admin.materials.edit', $material->id) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.materials.destroy', $material->id) }}" method="POST"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                    <p>Materials not created</p>
                @endif
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
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        });
    </script>

@endsection
