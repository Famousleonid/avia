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

        .vendor-tracking-filter-vnull {
            flex: 0 0 125px;
            margin-left: 2.75rem;
        }

        .vendor-tracking-type-grid {
            display: flex;
            align-items: center;
            gap: 24px;
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

        .vendor-tracking-headline {
            display: flex;
            align-items: baseline;
            gap: .85rem 1rem;
            flex-wrap: wrap;
        }

        .vendor-tracking-counts {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .vendor-tracking-count-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #adb5bd;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .vendor-tracking-count-badge strong {
            color: #fff;
            font-weight: 800;
        }

        .vendor-tracking-loadmore {
            display: flex;
            justify-content: center;
            padding: 1rem 0 .25rem;
            color: #adb5bd;
            font-size: .9rem;
        }

        .vendor-tracking-loadmore.is-hidden {
            display: none;
        }

        .vendor-tracking-inline-select,
        .vendor-tracking-inline-input {
            min-width: 120px;
            background: #171b22;
            color: #f8f9fa;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .vendor-tracking-inline-select:focus,
        .vendor-tracking-inline-input:focus {
            background: #1d2330;
            color: #fff;
            border-color: rgba(13, 110, 253, 0.6);
            box-shadow: 0 0 0 .16rem rgba(13, 110, 253, 0.18);
        }

        .vendor-tracking-save-cell {
            transition: background-color .18s ease, box-shadow .18s ease;
        }

        .vendor-tracking-save-cell.is-saving {
            background: rgba(13, 110, 253, 0.12);
        }

        .vendor-tracking-save-cell.is-saved {
            background: rgba(25, 135, 84, 0.14);
        }

        .vendor-tracking-save-cell.is-error {
            background: rgba(220, 53, 69, 0.16);
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
            } catch (error) {
                console.warn('Vendor tracking source restore failed.', error);
            }
        })();
    </script>

    <div class="container-fluid vendor-tracking-page py-3 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="vendor-tracking-headline">
                <h4 class="mb-0">Vendor Tracking</h4>
                <div class="text-muted small">STD, part, and bushing processes sent to vendors by repair order and dates.</div>
                <div class="vendor-tracking-counts">
                    <span class="vendor-tracking-count-badge">Selected:  &nbsp; <strong>{{ number_format($summary['filtered_total'] ?? 0) }}</strong></span>
                    <span class="vendor-tracking-count-badge">Total: &nbsp; <strong>{{ number_format($summary['total_rows'] ?? 0) }}</strong></span>
                </div>
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
                        <tbody id="vendorTrackingBody">
                            @forelse($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $sent = $row->date_start;
                                    $returned = $row->date_finish;
                                    $days = $sent ? $sent->diffInDays($returned ?: now()) : null;
                                    $woNumber = (string) ($wo?->number ?? '');
                                    $woDisplay = trim('w ' . preg_replace('/(\d{3})(?=\d)/', '$1 ', $woNumber));
                                @endphp
                                <tr data-row-id="{{ $row->id }}" data-source-key="{{ $row->source_key }}">
                                    <td class="vendor-tracking-save-cell">
                                        <select class="form-select form-select-sm vendor-tracking-inline-select js-vendor-tracking-vendor">
                                            <option value="">--</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected((int) ($row->vendor?->id ?? 0) === (int) $vendor->id)>{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><span class="badge text-bg-secondary">{{ $row->source }}</span></td>
                                    <td>
                                        @if($wo)
                                            <a href="{{ route('mains.show', $wo->id) }}" class="text-info vendor-tracking-wo-link">{{ $woDisplay }}</a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ $row->customer?->name ?? '--' }}</td>
                                    <td>{{ $row->ipl_num ?? '--' }}</td>
                                    <td>{{ $row->part_number ?? '--' }}</td>
                                    <td>{{ $row->serial ?: '--' }}</td>
                                    <td>{{ $row->process_name ?? '--' }}</td>
                                    <td class="vendor-tracking-save-cell">
                                        <input type="text" class="form-control form-control-sm vendor-tracking-inline-input js-vendor-tracking-repair-order" value="{{ $row->repair_order ?? '' }}">
                                    </td>
                                    <td class="text-center">{{ $sent ? format_project_date($sent) : '--' }}</td>
                                    <td class="text-center">{{ $returned ? format_project_date($returned) : '--' }}</td>
                                    <td class="text-center">{{ $days ?? '--' }}</td>
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

                <div class="mt-3 d-none" id="vendorTrackingPagination">
                    {{ $rows->links() }}
                </div>

                <div class="vendor-tracking-loadmore {{ $rows->hasMorePages() ? '' : 'is-hidden' }}" id="vendorTrackingLoadMore">
                    Loading more records...
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const updateUrl = @json(route('vendor-tracking.row.update'));
            const csrfToken = @json(csrf_token());
            const key = 'vendorTrackingSources';
            const vendorNullKey = 'vendorTrackingIncludeVendorNull';
            const form = document.querySelector('.vendor-tracking-page form');
            const boxes = Array.from(document.querySelectorAll('.vendor-source-checkbox'));
            const vendorNullBox = document.getElementById('vendorTrackingIncludeNull');
            const autoSubmitFields = Array.from(form?.querySelectorAll('select[name="vendor_id"], select[name="status"]') || []);
            const textFields = Array.from(form?.querySelectorAll('input[name="workorder"], input[name="part_number"], input[name="repair_order"]') || []);
            const tbody = document.getElementById('vendorTrackingBody');
            const paginationWrap = document.getElementById('vendorTrackingPagination');
            const loadMoreIndicator = document.getElementById('vendorTrackingLoadMore');

            if (!form || !boxes.length) {
                return;
            }

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

            autoSubmitFields.forEach(field => {
                field.addEventListener('change', function () {
                    persistSources();
                    form.submit();
                });
            });

            textFields.forEach(field => {
                field.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    persistSources();
                    form.requestSubmit();
                });
            });

            vendorNullBox?.addEventListener('change', function () {
                persistSources();
                form.submit();
            });

            if (!tbody || !paginationWrap || !loadMoreIndicator) {
                return;
            }

            const repairOrderTimers = new WeakMap();

            const setCellState = function (cell, state) {
                if (!cell) {
                    return;
                }

                cell.classList.remove('is-saving', 'is-saved', 'is-error');
                if (state) {
                    cell.classList.add(state);
                }
            };

            const markSaved = function (cell) {
                setCellState(cell, 'is-saved');
                window.clearTimeout(cell._vendorTrackingSavedStateTimer);
                cell._vendorTrackingSavedStateTimer = window.setTimeout(function () {
                    cell.classList.remove('is-saved');
                }, 900);
            };

            const rememberSavedValues = function (row) {
                row.querySelectorAll('.js-vendor-tracking-vendor').forEach(function (select) {
                    select.dataset.lastSavedValue = select.value;
                });

                row.querySelectorAll('.js-vendor-tracking-repair-order').forEach(function (input) {
                    input.dataset.lastSavedValue = input.value;
                });
            };

            const saveTrackingRow = async function (row, payload) {
                if (!row) {
                    return;
                }

                const vendorCell = row.querySelector('.js-vendor-tracking-vendor')?.closest('td');
                const repairOrderCell = row.querySelector('.js-vendor-tracking-repair-order')?.closest('td');
                const cells = [vendorCell, repairOrderCell].filter(Boolean);

                cells.forEach(function (cell) {
                    setCellState(cell, 'is-saving');
                });

                try {
                    const response = await fetch(updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            id: Number(row.dataset.rowId),
                            source_key: row.dataset.sourceKey,
                            vendor_id: payload.vendor_id,
                            repair_order: payload.repair_order,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save row');
                    }

                    const data = await response.json();
                    const repairInput = row.querySelector('.js-vendor-tracking-repair-order');
                    if (repairInput) {
                        repairInput.value = data.repair_order ?? '';
                    }

                    rememberSavedValues(row);
                    cells.forEach(markSaved);
                } catch (error) {
                    cells.forEach(function (cell) {
                        setCellState(cell, 'is-error');
                        window.setTimeout(function () {
                            cell.classList.remove('is-error');
                        }, 1500);
                    });
                } finally {
                    cells.forEach(function (cell) {
                        cell.classList.remove('is-saving');
                    });
                }
            };

            tbody.querySelectorAll('tr').forEach(rememberSavedValues);

            tbody.addEventListener('change', function (event) {
                const vendorSelect = event.target.closest('.js-vendor-tracking-vendor');
                if (!vendorSelect) {
                    return;
                }

                const row = vendorSelect.closest('tr');
                saveTrackingRow(row, {
                    vendor_id: vendorSelect.value === '' ? null : Number(vendorSelect.value),
                    repair_order: row.querySelector('.js-vendor-tracking-repair-order')?.value ?? '',
                });
            });

            const flushRepairOrderSave = function (input) {
                if (!input) {
                    return;
                }

                const previousValue = input.dataset.lastSavedValue ?? '';
                if (input.value === previousValue) {
                    return;
                }

                const row = input.closest('tr');
                const vendorSelect = row.querySelector('.js-vendor-tracking-vendor');

                saveTrackingRow(row, {
                    vendor_id: vendorSelect && vendorSelect.value !== '' ? Number(vendorSelect.value) : null,
                    repair_order: input.value,
                });
            };

            tbody.addEventListener('input', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input) {
                    return;
                }

                window.clearTimeout(repairOrderTimers.get(input));
                const timer = window.setTimeout(function () {
                    flushRepairOrderSave(input);
                }, 450);
                repairOrderTimers.set(input, timer);
            });

            tbody.addEventListener('blur', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input) {
                    return;
                }

                window.clearTimeout(repairOrderTimers.get(input));
                flushRepairOrderSave(input);
            }, true);

            tbody.addEventListener('keydown', function (event) {
                const input = event.target.closest('.js-vendor-tracking-repair-order');
                if (!input || event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(repairOrderTimers.get(input));
                flushRepairOrderSave(input);
                input.blur();
            });

            let nextPageUrl = paginationWrap.querySelector('.pagination .page-item.active + .page-item a, .pagination .page-item:first-child a[rel="next"], .pagination a[rel="next"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination .page-item:last-child a[rel="next"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination a[aria-label="Next »"]')?.getAttribute('href')
                || paginationWrap.querySelector('.pagination a[rel="next"]')?.getAttribute('href')
                || '';
            let isLoadingNextPage = false;

            const updateNextPageUrl = function (doc) {
                nextPageUrl = doc.querySelector('#vendorTrackingPagination .pagination .page-item.active + .page-item a')?.getAttribute('href')
                    || doc.querySelector('#vendorTrackingPagination .pagination a[rel="next"]')?.getAttribute('href')
                    || '';

                loadMoreIndicator.classList.toggle('is-hidden', !nextPageUrl);
            };

            const appendNextPage = async function () {
                if (!nextPageUrl || isLoadingNextPage) {
                    return;
                }

                isLoadingNextPage = true;
                loadMoreIndicator.textContent = 'Loading more records...';
                loadMoreIndicator.classList.remove('is-hidden');

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load next page');
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newRows = Array.from(doc.querySelectorAll('#vendorTrackingBody tr'));

                    newRows.forEach(function (row) {
                        rememberSavedValues(row);
                        tbody.appendChild(row);
                    });

                    updateNextPageUrl(doc);
                } catch (error) {
                    loadMoreIndicator.textContent = 'Failed to load more records.';
                    return;
                } finally {
                    isLoadingNextPage = false;
                }
            };

            updateNextPageUrl(document);

            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        appendNextPage();
                    }
                });
            }, {
                root: null,
                rootMargin: '250px 0px',
                threshold: 0.01,
            });

            observer.observe(loadMoreIndicator);
        });
    </script>
@endsection
