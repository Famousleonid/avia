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
            min-width: 150px;
            max-width: 200px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 200px;
            max-width: 300px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 250px;
            max-width: 400px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 200px;
            max-width: 300px;
        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: -1px;
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

    <div class="card shadow">
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="text-primary manage-header">{{__('Manuals CSV Components')}} ( <span class="text-success" id="manualsCount">{{$manuals->count()}}</span>)</h5>

                <div class="d-flex my-2 gap-2 flex-wrap">
                    <!-- Upload CSV -->
                    <button type="button" class="btn btn-outline-success" style="height: 40px" data-bs-toggle="modal" data-bs-target="#uploadCsvModal">
                        <i class="bi bi-upload"></i> {{__('Upload CSV')}}
                    </button>

                    <!-- Search -->
                    <div class="clearable-input">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>



                    <!-- Back -->
                    <a href="{{ route('components.index') }}" class="btn btn-outline-secondary" style="height: 40px">
                        <i class="bi bi-arrow-left"></i> {{__('Back')}}
                    </a>
                </div>
            </div>
        </div>

        @if(count($manuals))
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="manualsTable" class="display table table-sm table-hover bg-gradient table-striped align-middle table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-center sortable">{{__('Manual')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center sortable">{{__('Title')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center">{{__('Component CSV Files')}}</th>
                        <th class="text-center">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($manuals as $manual)
                            <tr>
                                <td class="text-center">
                                    <a href="#"
                                       data-bs-toggle="modal"
                                       data-bs-target="#manualModal{{ $manual->id }}">
                                        {{$manual->number}}
                                    </a>
                                </td>
                                <td class="text-center">{{$manual->title}}</td>
                                <td class="text-center" style="width: 250px;">
                                    @if($manual->getMedia('component_csv_files')->isNotEmpty())
                                        @foreach($manual->getMedia('component_csv_files') as $csvFile)
                                            <div class="mb-1 d-flex align-items-center">
                                                <small class="text-muted">{{ Str::limit($csvFile->file_name, 30) }}</small>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">No CSV</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($manual->getMedia('component_csv_files')->isNotEmpty())
                                        @foreach($manual->getMedia('component_csv_files') as $csvFile)
                                            <div class="btn-group btn-group-sm mb-1" role="group">
                                                <a href="{{ route('components.view-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}"
                                                   class="btn btn-outline-info"
                                                   title="View {{ $csvFile->file_name }}">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="{{ route('components.edit-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}"
                                                   class="btn btn-outline-primary"
                                                   title="Edit {{ $csvFile->file_name }}">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                                <a href="{{ route('components.download-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}"
                                                   class="btn btn-outline-success"
                                                   title="Download {{ $csvFile->file_name }}">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                                <form action="{{ route('components.delete-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}"
                                                      method="POST"
                                                      style="display:inline-block;"
                                                      onsubmit="return confirm('Вы уверены, что хотите удалить этот CSV файл?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete {{ $csvFile->file_name }}">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <H5 CLASS="text-center">{{__('MANUALS NOT FOUND')}}</H5>
        @endif
    </div>

    <!-- CSV Upload Modal -->
    <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadCsvModalLabel">
                        {{__('Upload Components CSV')}}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('components.upload-csv') }}" method="POST" enctype="multipart/form-data" id="csvUploadForm">
                                @csrf

                                <div class="mb-3">
                                    <label for="manual_id_csv" class="form-label">{{__('Select Manual')}}</label>
                                    <select name="manual_id" id="manual_id_csv" class="form-select" required>
                                        <option value="">{{__('Select Manual')}}</option>
                                        @foreach($manuals as $manual)
                                            <option value="{{ $manual->id }}">{{ $manual->number }} - {{ $manual->title }}
                                                @if($manual->unit_name_training)
                                                ({{ Str::limit($manual->unit_name_training, 10) }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">{{__('Select CSV File')}}</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> {{__('Upload Components')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">{{__('CSV Format Requirements')}}</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">{{__('Your CSV file should have the following columns:')}}</p>
                                    <ul class="small text-muted">
                                        <li><strong>part_number</strong> - {{__('Part number (required)')}}</li>
                                        <li><strong>assy_part_number</strong> - {{__('Assembly part number (optional)')}}</li>
                                        <li><strong>name</strong> - {{__('Component name (required)')}}</li>
                                        <li><strong>ipl_num</strong> - {{__('IPL number (required)')}}</li>
                                        <li><strong>assy_ipl_num</strong> - {{__('Assembly IPL number (optional)')}}</li>
                                        <li><strong>log_card</strong> - {{__('Log card (0 or 1, optional)')}}</li>
                                        <li><strong>repair</strong> - {{__('Repair flag (0 or 1, optional)')}}</li>
                                        <li><strong>is_bush</strong> - {{__('Is bushing (0 or 1, optional)')}}</li>
                                        <li><strong>bush_ipl_num</strong> - {{__('Bushing IPL number (optional)')}}</li>
                                    </ul>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small><i class="bi bi-info-circle"></i> <strong>{{__('Note:')}}</strong> {{__('Exact duplicate components will be automatically skipped. Multiple components with the same part_number but different IPL numbers are allowed in the same manual. Uploaded CSV files will be saved and can be viewed later.')}}</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('components.download-csv-template') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i> {{__('Download Template')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Modals -->
    @foreach($manuals as $manual)
        @php
            $thumbUrl = $manual->getFirstMediaThumbnailUrl('manuals') ?: asset('img/no-image.png');
            $bigUrl = $manual->getFirstMediaBigUrl('manuals') ?: asset('img/no-image.png');
        @endphp
        <div class="modal fade" id="manualModal{{ $manual->id }}" tabindex="-1"
             role="dialog" aria-labelledby="manualModalLabel{{ $manual->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="manualModalLabel{{ $manual->id }}">
                                {{ $manual->title }}{{ __(': ') }}
                            </h5>
                            <h6 style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $manual->unit_name_training ?? '' }}</h6>
                        </div>
                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="d-flex">
                            <div class="me-2">
                                <img src="{{ $bigUrl }}" width="200" alt="Image"/>
                            </div>
                            <div>
                                <p><strong>{{ __('CMM:') }}</strong> {{ $manual->number }}</p>
                                <p><strong>{{ __('Description:') }}</strong> {{ $manual->title }}</p>
                                <p><strong>{{ __('Revision Date:') }}</strong> {{ $manual->revision_date ?? 'N/A' }}</p>
                                <p><strong>{{ __('AirCraft Type:') }}</strong> {{ $planes[$manual->planes_id] ?? 'N/A' }}</p>
                                <p><strong>{{ __('MFR:') }}</strong> {{ $builders[$manual->builders_id] ?? 'N/A' }}</p>
                                <p><strong>{{ __('Scope:') }}</strong> {{ $scopes[$manual->scopes_id] ?? 'N/A' }}</p>
                                <p><strong>{{ __('Library:') }}</strong> {{ $manual->lib ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('manualsTable');
            const searchInput = document.getElementById('searchInput');
            const manualsCount = document.getElementById('manualsCount');

            // Кэшируем данные строк для быстрого поиска
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const rowDataCache = rows.map(row => ({
                element: row,
                searchText: row.innerText.toLowerCase()
            }));

            // Sorting
            const headers = document.querySelectorAll('.sortable');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const visibleRows = rowDataCache.filter(data => data.element.style.display !== 'none');
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    // Update icon
                    const icon = header.querySelector('i');
                    icon.className = direction === 'asc' ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1';

                    visibleRows.sort((a, b) => {
                        const aText = a.element.cells[columnIndex].innerText.trim();
                        const bText = b.element.cells[columnIndex].innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });

                    // Переупорядочиваем только видимые строки
                    const tbody = table.querySelector('tbody');
                    visibleRows.forEach(data => tbody.appendChild(data.element));
                });
            });

            // Search function
            function filterTable() {
                const searchFilter = searchInput.value.toLowerCase();
                let visibleCount = 0;

                rowDataCache.forEach(data => {
                    const matchesSearch = !searchFilter || data.searchText.includes(searchFilter);

                    if (matchesSearch) {
                        data.element.style.display = '';
                        visibleCount++;
                    } else {
                        data.element.style.display = 'none';
                    }
                });

                // Update count
                if (manualsCount) {
                    manualsCount.textContent = visibleCount;
                }
            }

            // Debounce функция для оптимизации поиска
            let searchTimeout;
            function debounce(func, wait) {
                return function(...args) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Search с debounce (300ms задержка)
            searchInput.addEventListener('input', debounce(filterTable, 300));

            // CSV Upload handling
            const csvUploadForm = document.getElementById('csvUploadForm');
            if (csvUploadForm) {
                csvUploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;

                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('success', data.message);

                            const modal = bootstrap.Modal.getInstance(document.getElementById('uploadCsvModal'));
                            if (modal) modal.hide();

                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showAlert('danger', data.message || 'Upload failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('danger', 'An error occurred during upload');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            }

            // Alert function
            function showAlert(type, message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);

                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        });
    </script>
@endsection

