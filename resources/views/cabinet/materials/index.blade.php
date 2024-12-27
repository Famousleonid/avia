@extends('cabinet.master')

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
            top: -2px;
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
        .editable {
            cursor: pointer;
            border: 1px dashed transparent;
        }
        .editable:hover {
            border-color: #007bff;
        }
        .editing {
            background-color: #f8f9fa;
        }
    </style>

    <div class="card shadow">

        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('Materials')}} <span class="ms-1 text-white">{{count($materials)}}</span></h5>
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
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($materials as $material)
                        <tr>
                            <td class="">{{$material->code}}</td>
                            <td class="">{{$material->material}}</td>
                            <td class="" data-bs-toggle="tooltip" data-bs-placement="top" title="{{$material->specification}}">{{$material->specification}}</td>
                            <td class="editable" data-id="{{$material->id}}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{$material->description}}" data-field="description">{{$material->description}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        @else
            <p>Materials not created</p>
        @endif
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

            // Edit description
            document.querySelectorAll('.editable').forEach(cell => {
                cell.addEventListener('click', function () {
                    if (this.classList.contains('editing')) return;

                    const originalText = this.innerText.trim();
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = originalText;
                    input.className = 'form-control';

                    this.innerHTML = '';
                    this.appendChild(input);
                    this.classList.add('editing');

                    input.focus();

                    input.addEventListener('blur', () => {
                        const newText = input.value.trim();
                        this.innerText = newText || originalText;
                        this.classList.remove('editing');

                        if (newText !== originalText) {
                            const id = this.dataset.id;
                            const field = this.dataset.field;

                            // Send the updated value to the server
                            fetch(`/cabinet/materials/${id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ description: newText })
                            })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Failed to update description');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log('Update successful:', data);
                                })
                                .catch(error => {
                                    console.error('Error updating description:', error);
                                    this.innerText = originalText; // Revert to original text on error
                                });
                        }
                    });
                });
            });
        });
    </script>
@endsection
