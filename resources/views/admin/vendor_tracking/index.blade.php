@extends('admin.master')

@section('style')
    <style>
        .content {
            overflow-y: auto !important;
        }

        .content-inner {
            display: block !important;
            height: auto !important;
            min-height: 100%;
        }

        .vendor-tracking-page .card,
        .vendor-tracking-page .btn,
        .vendor-tracking-page .form-control,
        .vendor-tracking-page .form-select {
            border-radius: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid vendor-tracking-page py-3 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1">Vendor Tracking</h4>
                <div class="text-muted small">Parts sent to vendors by TDR process, repair order, and dates.</div>
            </div>
        </div>

        <div class="card bg-gradient mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('vendor-tracking.index') }}" class="row g-2 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label small text-muted">Vendor</label>
                        <select name="vendor_id" class="form-select form-select-sm">
                            <option value="0">All vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((int) $filters['vendor_id'] === (int) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="open" @selected($filters['status'] === 'open')>At vendor</option>
                            <option value="returned" @selected($filters['status'] === 'returned')>Returned</option>
                            <option value="all" @selected($filters['status'] === 'all')>All</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted">Workorder</label>
                        <input name="workorder" class="form-control form-control-sm" value="{{ $filters['workorder'] }}" placeholder="WO no">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted">Part number</label>
                        <input name="part_number" class="form-control form-control-sm" value="{{ $filters['part_number'] }}" placeholder="P/N">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small text-muted">Repair order</label>
                        <input name="repair_order" class="form-control form-control-sm" value="{{ $filters['repair_order'] }}" placeholder="RO">
                    </div>
                    <div class="col-lg-1 d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-gradient">
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-bordered table-hover align-middle mb-0">
                        <thead>
                        <tr class="text-muted small">
                            <th>Vendor</th>
                            <th>WO</th>
                            <th>Customer</th>
                            <th>IPL</th>
                            <th>Part number</th>
                            <th>Serial</th>
                            <th>Process</th>
                            <th>Repair order</th>
                            <th>Sent</th>
                            <th>Returned</th>
                            <th>Days</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $tdr = $row->tdr;
                                $wo = $tdr?->workorder;
                                $component = $tdr?->component;
                                $sent = $row->date_start;
                                $returned = $row->date_finish;
                                $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
                            @endphp
                            <tr>
                                <td>{{ $row->vendor?->name ?? '—' }}</td>
                                <td>
                                    @if($wo)
                                        <a href="{{ route('mains.show', $wo->id) }}" class="text-info">WO {{ $wo->number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $wo?->customer?->name ?? '—' }}</td>
                                <td>{{ $component?->ipl_num ?? '—' }}</td>
                                <td>{{ $component?->part_number ?? '—' }}</td>
                                <td>{{ $tdr?->serial_number ?: ($tdr?->assy_serial_number ?: '—') }}</td>
                                <td>{{ $row->processName?->name ?? '—' }}</td>
                                <td>{{ $row->repair_order ?: '—' }}</td>
                                <td>{{ $sent ? format_project_date($sent) : '—' }}</td>
                                <td>{{ $returned ? format_project_date($returned) : '—' }}</td>
                                <td>{{ $days ?? '—' }}</td>
                                <td>
                                    @if($sent && ! $returned)
                                        <span class="badge text-bg-warning">At vendor</span>
                                    @elseif($returned)
                                        <span class="badge text-bg-success">Returned</span>
                                    @else
                                        <span class="badge text-bg-secondary">Planned</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-muted text-center py-4">No vendor process records found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
