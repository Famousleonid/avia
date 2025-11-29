@extends('admin.master')

@section('title', 'Edit CSV File')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">
                            <i class="bi bi-pencil-square"></i>
                            Edit CSV File: {{ $csvFile->file_name }}
                        </h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addRowBtn">
                                <i class="bi bi-plus-circle"></i> Add New Row
                            </button>
                            <button type="submit" form="csvEditForm" class="btn btn-primary btn-sm">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <a href="{{ route('components.csv-components') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <a href="{{ route('components.csv-components') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <form action="{{ route('components.update-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}" method="POST" id="csvEditForm">
                        @csrf
                        @method('POST')

                        @if(!empty($headers) && !empty($csvData))
                            <style>
                                .csv-edit-table-wrapper {
                                    height: calc(100vh - 250px);
                                    overflow-y: auto;
                                    overflow-x: auto;
                                }
                                
                                .csv-edit-table-wrapper table thead th {
                                    position: sticky;
                                    top: 0;
                                    z-index: 10;
                                    background-color: #212529 !important;
                                }
                            </style>
                            <div class="csv-edit-table-wrapper">
                                <table class="table table-sm table-striped table-bordered mb-0" id="csvEditTable">
                                    <thead class="table-dark">
                                        <tr>
                                            @foreach($headers as $headerIndex => $header)
                                                <th class="text-center">
                                                    <input type="hidden" name="headers[]" value="{{ $header }}">
                                                    {{ $header }}
                                                </th>
                                            @endforeach
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($csvData as $rowIndex => $row)
                                            <tr data-row-index="{{ $rowIndex }}">
                                                @foreach($headers as $colIndex => $header)
                                                    <td class="text-center">
                                                        @if(in_array($header, ['log_card', 'repair', 'is_bush']))
                                                            <select name="rows[{{ $rowIndex }}][]" class="form-select form-select-sm">
                                                                <option value="0" {{ (isset($row[$colIndex]) && ($row[$colIndex] == '0' || $row[$colIndex] == 'false' || $row[$colIndex] == '')) ? 'selected' : '' }}>0</option>
                                                                <option value="1" {{ (isset($row[$colIndex]) && ($row[$colIndex] == '1' || $row[$colIndex] == 'true')) ? 'selected' : '' }}>1</option>
                                                            </select>
                                                        @else
                                                            <input type="text" 
                                                                   name="rows[{{ $rowIndex }}][]" 
                                                                   class="form-control form-control-sm" 
                                                                   value="{{ isset($row[$colIndex]) ? htmlspecialchars($row[$colIndex]) : '' }}">
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove row">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning m-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                No data found in CSV file or file is empty.
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('csvEditTable');
    const addRowBtn = document.getElementById('addRowBtn');
    const tbody = table.querySelector('tbody');
    
    // Get headers count
    const headersCount = {{ count($headers ?? []) }};
    const headers = @json($headers ?? []);
    
    // Проверяем, что headers является массивом
    if (!Array.isArray(headers) || headers.length === 0) {
        console.error('Headers is not a valid array');
        return;
    }

    // Add new row
    if (addRowBtn) {
        addRowBtn.addEventListener('click', function() {
            const rowIndex = tbody.children.length;
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-row-index', rowIndex);

            // Add cells for each header
            headers.forEach((header, colIndex) => {
                const cell = document.createElement('td');
                cell.className = 'text-center';
                
                if (['log_card', 'repair', 'is_bush'].includes(header)) {
                    cell.innerHTML = `
                        <select name="rows[${rowIndex}][]" class="form-select form-select-sm">
                            <option value="0">0</option>
                            <option value="1">1</option>
                        </select>
                    `;
                } else {
                    cell.innerHTML = `<input type="text" name="rows[${rowIndex}][]" class="form-control form-control-sm" value="">`;
                }
                
                newRow.appendChild(cell);
            });

            // Add action cell
            const actionCell = document.createElement('td');
            actionCell.className = 'text-center';
            actionCell.innerHTML = `
                <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove row">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            newRow.appendChild(actionCell);

            tbody.appendChild(newRow);
        });
    }

    // Remove row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('tr');
            if (confirm('Вы уверены, что хотите удалить эту строку?')) {
                row.remove();
                // Reindex rows
                Array.from(tbody.children).forEach((tr, index) => {
                    tr.setAttribute('data-row-index', index);
                    tr.querySelectorAll('input, select').forEach((input) => {
                        if (input.name && input.name.includes('rows[')) {
                            input.name = input.name.replace(/rows\[\d+\]/, `rows[${index}]`);
                        }
                    });
                });
            }
        }
    });

    // Form submission
    const form = document.getElementById('csvEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm('Вы уверены, что хотите сохранить изменения в CSV файле?')) {
                this.submit();
            }
        });
    }
});
</script>
@endsection

