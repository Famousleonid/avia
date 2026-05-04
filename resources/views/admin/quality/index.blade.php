@extends('admin.master')

@section('content')
    @php
        $badgeMap = [
            'ok' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            'na' => 'secondary',
        ];
        $tabUrl = function (string $tab, array $extra = []) {
            return route('quality.index', array_merge(request()->except(['tab', 'page']), ['tab' => $tab], $extra));
        };
        $statusOptions = [
            'all' => 'All',
            'ok' => 'OK',
            'warning' => 'Warning',
            'critical' => 'Critical',
        ];
    @endphp

    <style>
        .qa-summary-card {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: .9rem;
            background: rgba(255, 255, 255, 0.03);
        }

        .qa-summary-value {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1;
        }

        .qa-filter-card,
        .qa-tab-card {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: .9rem;
            background: rgba(255, 255, 255, 0.02);
        }

        .qa-warning-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .qa-warning-badges .badge {
            font-weight: 500;
        }

        .qa-doc-list {
            display: grid;
            gap: .75rem;
        }

        .qa-doc-item {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: .75rem;
            padding: .75rem;
            background: rgba(255, 255, 255, 0.02);
        }

        .qa-doc-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }
    </style>

    <div class="container-fluid px-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="mb-1 text-primary">Quality Assurance</h2>
                <div class="text-secondary small">QA dashboard for workorders, processes, photos, training and quality documents.</div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Open Workorders</div>
                    <div class="qa-summary-value text-primary">{{ $summary['open_workorders'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">WO with QA Warnings</div>
                    <div class="qa-summary-value text-warning">{{ $summary['qa_warnings'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Missing Photos</div>
                    <div class="qa-summary-value text-danger">{{ $summary['missing_photos'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Overdue / Incomplete Processes</div>
                    <div class="qa-summary-value text-warning">{{ $summary['incomplete_processes'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Missing RO</div>
                    <div class="qa-summary-value text-danger">{{ $summary['missing_ro'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Training Alerts</div>
                    <div class="qa-summary-value text-warning">{{ $summary['training_alerts'] }}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <div class="qa-summary-card h-100 p-3">
                    <div class="text-secondary small mb-2">Quality Documents</div>
                    <div class="qa-summary-value text-success">{{ $summary['quality_documents'] }}</div>
                </div>
            </div>
        </div>

        <div class="qa-filter-card p-3 mb-4">
            <form method="GET" action="{{ route('quality.index') }}">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">WO #</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Search by workorder number">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">QA Status</label>
                        <select name="status" class="form-select">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">All customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="row g-2">
                            <div class="col-6 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="missing_photos" name="missing_photos" value="1" @checked($filters['missing_photos'])>
                                    <label class="form-check-label" for="missing_photos">Missing photos</label>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="missing_ro" name="missing_ro" value="1" @checked($filters['missing_ro'])>
                                    <label class="form-check-label" for="missing_ro">Missing RO</label>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="incomplete_processes" name="incomplete_processes" value="1" @checked($filters['incomplete_processes'])>
                                    <label class="form-check-label" for="incomplete_processes">Incomplete processes</label>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="missing_quality_documents" name="missing_quality_documents" value="1" @checked($filters['missing_quality_documents'])>
                                    <label class="form-check-label" for="missing_quality_documents">Missing quality docs</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                        <a href="{{ route('quality.index', ['tab' => $activeTab]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="qa-tab-card">
            <ul class="nav nav-tabs px-3 pt-3 border-0">
                <li class="nav-item"><a class="nav-link @if($activeTab === 'overview') active @endif" href="{{ $tabUrl('overview') }}">Overview</a></li>
                <li class="nav-item"><a class="nav-link @if($activeTab === 'workorders') active @endif" href="{{ $tabUrl('workorders') }}">Workorders</a></li>
                <li class="nav-item"><a class="nav-link @if($activeTab === 'processes') active @endif" href="{{ $tabUrl('processes') }}">Processes</a></li>
                <li class="nav-item"><a class="nav-link @if($activeTab === 'photos') active @endif" href="{{ $tabUrl('photos') }}">Photos</a></li>
                <li class="nav-item"><a class="nav-link @if($activeTab === 'training') active @endif" href="{{ $tabUrl('training') }}">Training</a></li>
                <li class="nav-item"><a class="nav-link @if($activeTab === 'documents') active @endif" href="{{ $tabUrl('documents') }}">Quality Documents</a></li>
            </ul>

            <div class="p-3">
                @if($activeTab === 'overview')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Customer</th>
                                <th>Component PN</th>
                                <th>QA Status</th>
                                <th>Warnings</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($overviewRows as $row)
                                <tr>
                                    <td><a href="{{ $row['url'] }}" class="text-decoration-none">{{ $row['number'] }}</a></td>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td>{{ $row['component_pn'] }}</td>
                                    <td><span class="badge text-bg-{{ $row['status_badge'] }}">{{ strtoupper($row['status']) }}</span></td>
                                    <td>
                                        <div class="qa-warning-badges">
                                            @forelse($row['all_messages'] as $message)
                                                <span class="badge text-bg-{{ in_array($message, $row['criticals'], true) ? 'danger' : 'warning' }}">{{ $message }}</span>
                                            @empty
                                                <span class="badge text-bg-success">OK</span>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No workorders found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'workorders')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Customer</th>
                                <th>Component PN</th>
                                <th>Serial #</th>
                                <th>Manual</th>
                                <th>Open Date</th>
                                <th>Approved</th>
                                <th>Photos</th>
                                <th>Processes</th>
                                <th>Training</th>
                                <th>Quality Docs</th>
                                <th>QA Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($workorderRows as $row)
                                <tr>
                                    <td><a href="{{ $row['url'] }}" class="text-decoration-none">{{ $row['number'] }}</a></td>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td>{{ $row['component_pn'] }}</td>
                                    <td>{{ $row['serial_number'] }}</td>
                                    <td>
                                        <div>{{ $row['manual_number'] }}</div>
                                        <div class="small text-secondary">{{ $row['manual_lib'] }} / {{ $row['manual_revision'] }}</div>
                                    </td>
                                    <td>{{ $row['open_date'] ?? '—' }}</td>
                                    <td><span class="badge text-bg-{{ $row['approved'] ? 'success' : 'danger' }}">{{ $row['approved'] ? 'Present' : 'Missing' }}</span></td>
                                    <td><span class="badge text-bg-{{ $row['photos']['missing_any'] ? 'danger' : 'success' }}">{{ $row['photos']['count'] }}</span></td>
                                    <td><span class="badge text-bg-{{ $row['processes']['counts']['incomplete'] > 0 ? 'warning' : 'success' }}">{{ $row['processes']['counts']['total'] }}</span></td>
                                    <td><span class="badge text-bg-{{ $badgeMap[$row['training']['status']] ?? 'secondary' }}">{{ strtoupper($row['training']['status']) }}</span></td>
                                    <td><span class="badge text-bg-{{ $row['quality_documents']['missing'] ? 'danger' : 'success' }}">{{ $row['quality_documents']['count'] }}</span></td>
                                    <td><span class="badge text-bg-{{ $row['status_badge'] }}">{{ strtoupper($row['status']) }}</span></td>
                                    <td>
                                        <a href="{{ $row['url'] }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="13" class="text-center text-secondary py-4">No workorders found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'processes')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Component</th>
                                <th>Process Name</th>
                                <th>Date Start</th>
                                <th>Date Finish</th>
                                <th>RO</th>
                                <th>Status</th>
                                <th>Warning</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($processRows as $row)
                                <tr>
                                    <td><a href="{{ $row['wo_url'] }}" class="text-decoration-none">{{ $row['wo_number'] }}</a></td>
                                    <td>{{ $row['component'] }}</td>
                                    <td>{{ $row['process_name'] }}</td>
                                    <td>{{ $row['date_start'] ?? '—' }}</td>
                                    <td>{{ $row['date_finish'] ?? '—' }}</td>
                                    <td>{{ $row['repair_order'] }}</td>
                                    <td><span class="badge text-bg-{{ $badgeMap[$row['status']] ?? 'secondary' }}">{{ strtoupper($row['status']) }}</span></td>
                                    <td>{{ $row['warning'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-secondary py-4">No process rows found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'photos')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Photos count</th>
                                <th>Damage photos count</th>
                                <th>Logs count</th>
                                <th>Status</th>
                                <th>Action / View</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($photoRows as $row)
                                <tr>
                                    <td><a href="{{ $row['wo_url'] }}" class="text-decoration-none">{{ $row['wo_number'] }}</a></td>
                                    <td>{{ $row['photos_count'] }}</td>
                                    <td>{{ $row['damage_photos_count'] }}</td>
                                    <td>{{ $row['logs_count'] }}</td>
                                    <td><span class="badge text-bg-{{ $badgeMap[$row['status']] ?? 'secondary' }}">{{ strtoupper($row['status']) }}</span></td>
                                    <td>{{ $row['warning'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">No photo rows found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'training')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>User / Technician</th>
                                <th>WO #</th>
                                <th>Last Training</th>
                                <th>Days Since</th>
                                <th>Status</th>
                                <th>Warning</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($trainingRows as $row)
                                <tr>
                                    <td>{{ $row['user_name'] }}</td>
                                    <td><a href="{{ $row['wo_url'] }}" class="text-decoration-none">{{ $row['wo_number'] }}</a></td>
                                    <td>{{ $row['last_training'] ?? '—' }}</td>
                                    <td>{{ $row['days_since'] ?? '—' }}</td>
                                    <td><span class="badge text-bg-{{ $badgeMap[$row['status']] ?? 'secondary' }}">{{ strtoupper($row['status']) }}</span></td>
                                    <td>{{ $row['warning'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">No training rows found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'documents')
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Customer</th>
                                <th>Component PN</th>
                                <th>Serial #</th>
                                <th>Documents count</th>
                                <th>Latest document</th>
                                <th>Status</th>
                                <th>Upload</th>
                                <th>View</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($qualityDocumentRows as $row)
                                <tr>
                                    <td><a href="{{ $row['wo_url'] }}" class="text-decoration-none">{{ $row['wo_number'] }}</a></td>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td>{{ $row['component_pn'] }}</td>
                                    <td>{{ $row['serial_number'] }}</td>
                                    <td>{{ $row['documents_count'] }}</td>
                                    <td>
                                        <div>{{ $row['latest_document'] ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $row['latest_document_at'] ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $badgeMap[$row['status']] ?? 'secondary' }}">{{ strtoupper($row['status']) }}</span>
                                    </td>
                                    <td style="min-width: 260px;">
                                        <form method="POST" action="{{ route('quality.documents.store', $row['wo_id']) }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="file" name="files[]" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-upload me-1"></i>Upload document
                                            </button>
                                        </form>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <a href="{{ $tabUrl('documents', ['documents_for' => $row['wo_id']]) }}#quality-documents-list" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-folder2-open me-1"></i>View documents
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-secondary py-4">No quality document rows found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($openDocumentWorkorderId)
                        @php
                            $openDocumentRow = $qualityDocumentRows->firstWhere('wo_id', $openDocumentWorkorderId);
                        @endphp
                        @if($openDocumentRow)
                            <div id="quality-documents-list" class="mt-4">
                                <h5 class="mb-3 text-primary">WO {{ $openDocumentRow['wo_number'] }} quality documents</h5>
                                <div class="qa-doc-list">
                                    @forelse($openDocumentRow['documents'] as $document)
                                        <div class="qa-doc-item">
                                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $document->name ?: $document->file_name }}</div>
                                                    <div class="small text-secondary">
                                                        {{ $document->file_name }} · {{ number_format($document->size / 1024, 1) }} KB · {{ optional($document->created_at)->format('Y-m-d H:i') }}
                                                    </div>
                                                    <div class="small text-secondary">
                                                        Uploaded by: {{ $document->getCustomProperty('uploaded_by_name') ?: ('User #' . ($document->getCustomProperty('uploaded_by') ?: '—')) }}
                                                    </div>
                                                </div>
                                                <div class="qa-doc-actions">
                                                    <a href="{{ route('quality.documents.show', [$openDocumentRow['wo_id'], $document->id]) }}" target="_blank" class="btn btn-outline-primary btn-sm">Open</a>
                                                    <a href="{{ route('quality.documents.download', [$openDocumentRow['wo_id'], $document->id]) }}" class="btn btn-outline-secondary btn-sm">Download</a>
                                                    <form method="POST" action="{{ route('quality.documents.destroy', [$openDocumentRow['wo_id'], $document->id]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-secondary mb-0">No quality documents uploaded for this workorder.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
