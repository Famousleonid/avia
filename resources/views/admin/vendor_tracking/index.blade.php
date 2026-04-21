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

        .vendor-tracking-filters {
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
        }

        .vendor-tracking-filter-vendor {
            flex: 0 0 150px;
        }

        .vendor-tracking-filter-status {
            flex: 0 0 105px;
        }

        .vendor-tracking-filter-types {
            flex: 0 0 220px;
            margin-left: 1.25rem;
        }

        .vendor-tracking-filter-text {
            flex: 0 0 145px;
        }

        .vendor-tracking-filter-action {
            flex: 0 0 auto;
        }

        .vendor-tracking-filter-vnull {
            flex: 0 0 125px;
            margin-left: 2.75rem;
        }

        .vendor-tracking-type-grid {
            display: flex;
            align-items: center;
            gap: 24px;
            align-items: center;
        }

        .vendor-tracking-check {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin: 0 !important;
            padding-left: 0 !important;
            min-width: 0;
        }

        .vendor-tracking-check .form-check-input {
            margin: 0 !important;
            float: none !important;
        }

        .vendor-tracking-check .form-check-label {
            margin: 0;
        }

        .vendor-tracking-wo-link {
            text-decoration: none;
        }

        .vendor-tracking-wo-link:hover,
        .vendor-tracking-wo-link:focus {
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    <script>
        (function () {
            try {
                const key = 'vendorTrackingSources';
                const vendorNullKey = 'vendorTrackingIncludeVendorNull';
                const params = new URLSearchParams(window.location.search);
                if (!params.has('sources[]') && !params.has('sources')) {
                    const stored = JSON.parse(localStorage.getItem(key) || 'null');
                    if (Array.isArray(stored) && stored.length && stored.length < 3) {
                        stored.forEach(source => params.append('sources[]', source));
                    }
                }
                if (!params.has('include_vendor_null') && localStorage.getItem(vendorNullKey) === 'true') {
                    params.set('include_vendor_null', '1');
                }
                if (params.toString() !== window.location.search.replace(/^\?/, '')) {
                    window.location.replace(window.location.pathname + '?' + params.toString());
                }
            } catch (e) {}
        })();
    </script>
    <div class="container-fluid vendor-tracking-page py-3 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1">Vendor Tracking</h4>
                <div class="text-muted small">STD, part, and bushing processes sent to vendors by repair order and dates.</div>
            </div>
        </div>

        <div class="card bg-gradient mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('vendor-tracking.index') }}" class="vendor-tracking-filters d-flex gap-2 align-items-end">
                    <div class="vendor-tracking-filter-vendor">
                        <label class="form-label small text-muted">Vendor</label>
                        <select name="vendor_id" class="form-select form-select-sm">
                            <option value="0">All vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((int) $filters['vendor_id'] === (int) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="vendor-tracking-filter-status">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all" @selected($filters['status'] === 'all')>All</option>
                            <option value="open" @selected($filters['status'] === 'open')>At vendor</option>
                            <option value="returned" @selected($filters['status'] === 'returned')>Returned</option>
                        </select>
                    </div>
                    <div class="vendor-tracking-filter-text">
                        <label class="form-label small text-muted">Workorder</label>
                        <input name="workorder" class="form-control form-control-sm" value="{{ $filters['workorder'] }}" placeholder="WO no">
                    </div>
                    <div class="vendor-tracking-filter-text">
                        <label class="form-label small text-muted">Part number</label>
                        <input name="part_number" class="form-control form-control-sm" value="{{ $filters['part_number'] }}" placeholder="P/N">
                    </div>
                    <div class="vendor-tracking-filter-text">
                        <label class="form-label small text-muted">Repair order</label>
                        <input name="repair_order" class="form-control form-control-sm" value="{{ $filters['repair_order'] }}" placeholder="RO">
                    </div>
                    <div class="vendor-tracking-filter-action d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" type="submit">Filter</button>
                    </div>
                    <div class="vendor-tracking-filter-vnull">
                        <label class="form-label small text-muted d-block">Vendor</label>
                        <label class="form-check vendor-tracking-check mb-0">
                            <input class="form-check-input" type="checkbox" id="vendorTrackingIncludeNull" name="include_vendor_null" value="1" @checked($filters['include_vendor_null'] ?? false)>
                            <span class="form-check-label small">null</span>
                        </label>
                    </div>
                    <div class="vendor-tracking-filter-types">
                        <label class="form-label small text-muted d-block">Type</label>
                        <div class="vendor-tracking-type-grid">
                            <label class="form-check vendor-tracking-check mb-0">
                                <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="part" @checked(in_array('part', $filters['sources'], true))>
                                <span class="form-check-label small">Part</span>
                            </label>
                            <label class="form-check vendor-tracking-check mb-0">
                                <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="std" @checked(in_array('std', $filters['sources'], true))>
                                <span class="form-check-label small">STD</span>
                            </label>
                            <label class="form-check vendor-tracking-check mb-0">
                                <input class="form-check-input vendor-source-checkbox" type="checkbox" name="sources[]" value="bushing" @checked(in_array('bushing', $filters['sources'], true))>
                                <span class="form-check-label small">Bushing</span>
                            </label>
                        </div>
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
                            <th>Type</th>
                            <th>WO</th>
                            <th>Customer</th>
                            <th>IPL</th>
                            <th>Part number</th>
                            <th>Serial</th>
                            <th>Process</th>
                            <th>Repair order</th>
                            <th class="text-center">Sent</th>
                            <th class="text-center">Returned</th>
                            <th class="text-center">Days</th>
                            <th class="text-center">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $wo = $row->workorder;
                                $sent = $row->date_start;
                                $returned = $row->date_finish;
                                $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
                                $woNumber = (string) ($wo?->number ?? '');
                                $woDisplay = trim('w ' . preg_replace('/(\d{3})(?=\d)/', '$1 ', $woNumber));
                            @endphp
                            <tr>
                                <td>{{ $row->vendor?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $row->source }}</span></td>
                                <td>
                                    @if($wo)
                                        <a href="{{ route('mains.show', $wo->id) }}" class="text-info vendor-tracking-wo-link">{{ $woDisplay }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->customer?->name ?? '—' }}</td>
                                <td>{{ $row->ipl_num ?? '—' }}</td>
                                <td>{{ $row->part_number ?? '—' }}</td>
                                <td>{{ $row->serial ?: '—' }}</td>
                                <td>{{ $row->process_name ?? '—' }}</td>
                                <td>{{ $row->repair_order ?: '—' }}</td>
                                <td class="text-center">{{ $sent ? format_project_date($sent) : '—' }}</td>
                                <td class="text-center">{{ $returned ? format_project_date($returned) : '—' }}</td>
                                <td class="text-center">{{ $days ?? '—' }}</td>
                                <td class="text-center">
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
                                <td colspan="13" class="text-muted text-center py-4">No vendor process records found.</td>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const key = 'vendorTrackingSources';
            const vendorNullKey = 'vendorTrackingIncludeVendorNull';
            const form = document.querySelector('.vendor-tracking-page form');
            const boxes = Array.from(document.querySelectorAll('.vendor-source-checkbox'));
            const vendorNullBox = document.getElementById('vendorTrackingIncludeNull');
            if (!form || !boxes.length) return;

            function selectedSources() {
                const selected = boxes.filter(box => box.checked).map(box => box.value);
                return selected.length ? selected : boxes.map(box => box.value);
            }

            function persistSources() {
                localStorage.setItem(key, JSON.stringify(selectedSources()));
                if (vendorNullBox) {
                    localStorage.setItem(vendorNullKey, vendorNullBox.checked ? 'true' : 'false');
                }
            }

            boxes.forEach(box => {
                box.addEventListener('change', function () {
                    if (!boxes.some(item => item.checked)) {
                        box.checked = true;
                    }
                    persistSources();
                    form.submit();
                });
            });

            form.addEventListener('submit', persistSources);

            vendorNullBox?.addEventListener('change', function () {
                persistSources();
                form.submit();
            });
        });
    </script>
@endsection
