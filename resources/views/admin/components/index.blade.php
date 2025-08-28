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
            min-width: 60px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 300px;
            /*max-width: 300px;*/
        }
        .table th:nth-child(5), .table td:nth-child(5) {
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
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary manage-header">{{__('Manuals')}}( <span class="text-success">{{$manuals->count()}}
                    </span>)
                </h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                <div>

                    <a href="{{ route('components.create') }}" class="btn btn-outline-primary " style="height: 40px">
                        {{__('Add Component')}}
                    </a>

                </div>
        </div>

        @if(count($manuals))
            <div class="table-wrapper me-3 p-2 pt-0 ">
                <table id="manualsTable" class="display table table-sm table-hover bg-gradient table-striped
                align-middle table-bordered">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center  sortable">{{__('Manual')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center  sortable">{{__('Title')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class=" text-center " style="width: 120px">{{__('Image ')}}</th>
                    <th class="text-center " style="width: 250px">{{__('Component CSV Files')}}</th>
                    <th class="text-center ">Action</th>
                </tr>
                </thead>
                    <tbody>
                        @foreach($manuals as $manual)
                            <tr>
                                <td class="text-center">{{$manual->number}}</td>
                                <td class="text-center">{{$manual->title}}</td>
                                <td class="text-center" style="width: 120px;">
                                    @if($manual->getMedia('manuals')->isNotEmpty())
                                        <a href="{{ $manual->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                                            <img class="rounded-circle" src="{{ $manual->getFirstMediaThumbnailUrl('manuals') }}" width="40"
                                                 height="40" alt="IMG"/>
                                        </a>
                                    @endif
                                </td>
                                <td class="text-center" style="width: 250px;">
                                    @if($manual->getMedia('component_csv_files')->isNotEmpty())
                                        @foreach($manual->getMedia('component_csv_files') as $csvFile)
                                            <div class="mb-1 d-flex">
                                                <div class="btn-group btn-group-sm me-1" role="group">
                                                    <a href="{{ route('components.view-csv', ['manual_id' => $manual->id, 'file_id' => $csvFile->id]) }}"
                                                       class="btn btn-outline-info"
                                                       title="View {{ $csvFile->file_name }}"
                                                       target="_blank">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </a>
{{--                                                    <a href="{{ $csvFile->getUrl() }}" --}}
{{--                                                       class="btn btn-outline-primary"--}}
{{--                                                       title="Download {{ $csvFile->file_name }}"--}}
{{--                                                       download>--}}
{{--                                                        <i class="bi bi-download"></i>--}}
{{--                                                    </a>--}}
                                                </div>
                                             <small class="d-block text-muted pt-1">{{ Str::limit($csvFile->file_name, 30)
                                             }}</small>
{{--                                                <small class="d-block text-success">--}}
{{--                                                    CSV uploaded--}}
{{--                                                </small>--}}
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">No CSV</span>
                                    @endif
                                </td>
                                <td class="text-center">

                                    <a href="{{ route('components.show',['component' => $manual->id]) }}"
                                       class="btn btn-outline-primary btn-sm">
{{--                                        <i class="bi bi-eye"></i>--}}
                                        <i class="bi bi-bar-chart-steps"></i>
                                    </a>

                                    <button type="button" class="btn btn-outline-success btn-sm ms-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#uploadCsvModal{{ $manual->id }}"
                                            title="Upload Components CSV">
                                        <i class="bi bi-upload"></i>
                                    </button>
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

    <!-- CSV Upload Modals for each Manual -->
    @foreach($manuals as $manual)
    <div class="modal fade" id="uploadCsvModal{{ $manual->id }}" tabindex="-1" aria-labelledby="uploadCsvModalLabel{{ $manual->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadCsvModalLabel{{ $manual->id }}">
                        Upload Components CSV for Manual: {{ $manual->number }} - {{ $manual->title }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('components.upload-csv') }}" method="POST" enctype="multipart/form-data" id="csvUploadForm{{ $manual->id }}">
                                @csrf
                                <input type="hidden" name="manual_id" value="{{ $manual->id }}">

                                <div class="mb-3">
                                    <label for="csv_file{{ $manual->id }}" class="form-label">Select CSV File</label>
                                    <input type="file" class="form-control" id="csv_file{{ $manual->id }}" name="csv_file" accept=".csv" required>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> Upload Components
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">CSV Format Requirements</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">Your CSV file should have the following columns:</p>
                                    <ul class="small text-muted">
                                        <li><strong>part_number</strong> - Part number (required)</li>
                                        <li><strong>assy_part_number</strong> - Assembly part number (optional)</li>
                                        <li><strong>name</strong> - Component name (required)</li>
                                        <li><strong>ipl_num</strong> - IPL number (required)</li>
                                        <li><strong>assy_ipl_num</strong> - Assembly IPL number (optional)</li>
                                        <li><strong>log_card</strong> - Log card (0 or 1, optional)</li>
                                        <li><strong>repair</strong> - Repair flag (0 or 1, optional)</li>
                                        <li><strong>is_bush</strong> - Is bushing (0 or 1, optional)</li>
                                        <li><strong>bush_ipl_num</strong> - Bushing IPL number (optional)</li>
                                    </ul>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small><i class="bi bi-info-circle"></i> <strong>Note:</strong> Exact duplicate components will be automatically skipped. Multiple components with the same part_number but different IPL numbers are allowed in the same manual. Uploaded CSV files will be saved and can be viewed later.</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('components.download-csv-template') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i> Download Template
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
    @endforeach


        <script>

        // Sorting
        const table = document.getElementById('manualsTable');
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

        // CSV Upload handling
        document.querySelectorAll('[id^="csvUploadForm"]').forEach(form => {
            form.addEventListener('submit', function(e) {
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
                        // Show success message
                        showAlert('success', data.message);

                        // Close modal
                        const modalId = this.id.replace('csvUploadForm', 'uploadCsvModal');
                        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                        if (modal) modal.hide();

                        // Reload page after 2 seconds to show new components
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
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });

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

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

    </script>
@endsection
