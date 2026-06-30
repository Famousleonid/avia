@extends('admin.master')

@section('style')
    <style>
        .sales-report-page {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            padding: .75rem;
        }

        .sales-report-toolbar {
            flex: 0 0 auto;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            padding: .75rem;
            background: var(--bs-body-bg);
        }

        .sales-report-toolbar .form-label {
            margin-bottom: .2rem;
            font-size: .72rem;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
            font-weight: 700;
        }

        .sales-report-sheet-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            background: #fff;
            color: #111;
        }

        .sales-report-sheet {
            min-width: 1120px;
            padding: 18px;
        }

        .sales-report-title {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .75rem;
            color: #111;
        }

        .sales-report-title h4 {
            margin: 0;
            font-weight: 700;
        }

        .sales-report-period {
            color: #333;
            font-size: .9rem;
        }

        .sales-report-table {
            width: 100%;
            border-collapse: collapse;
            color: #111;
            font-size: 12px;
        }

        .sales-report-table th,
        .sales-report-table td {
            border: 1px solid #222;
            padding: 5px 7px;
            vertical-align: top;
        }

        .sales-report-table th {
            background: #e9ecef;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .sales-report-table td {
            background: #fff;
        }

        .sales-report-table .sales-report-group {
            font-weight: 700;
            width: 17%;
        }

        .sales-report-table .sales-report-money {
            text-align: right;
            white-space: nowrap;
        }

        .sales-report-table .sales-report-total-label {
            text-align: right;
            font-weight: 700;
            background: #f8f9fa;
        }

        .sales-report-table .sales-report-total-amount {
            text-align: right;
            font-weight: 700;
            background: #f8f9fa;
            white-space: nowrap;
        }

        .sales-report-note {
            margin-top: .75rem;
            font-size: .9rem;
            color: #111;
        }

        .sales-report-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #555;
        }

        @media print {
            @page {
                size: letter landscape;
                margin: 8mm;
            }

            body,
            html,
            .container-fluid,
            .page-layout,
            .content,
            .content-inner {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                background: #fff !important;
            }

            #sidebarColumn,
            .no-print,
            footer,
            .navbar,
            #spinner-load {
                display: none !important;
            }

            .row.page-layout {
                display: block !important;
            }

            .sales-report-page {
                display: block !important;
                padding: 0 !important;
                height: auto !important;
            }

            .sales-report-sheet-wrap {
                border: none !important;
                overflow: visible !important;
                border-radius: 0 !important;
            }

            .sales-report-sheet {
                min-width: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $money = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            $amount = (float) $value;
            $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;

            return '$' . number_format($amount, $decimals);
        };

        $reportType = $filters['report_type'] ?? 'customer';
        $rows = $report['rows'] ?? [];
    @endphp

    <div class="sales-report-page">
        <div class="sales-report-toolbar no-print">
            <form method="GET" action="{{ route('sales-reports.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="run" value="1">

                <div class="col-12 col-md-2">
                    <label class="form-label" for="reportType">Report</label>
                    <select id="reportType" name="report_type" class="form-select form-select-sm">
                        <option value="customer" @selected($reportType === 'customer')>Customer</option>
                        <option value="component" @selected($reportType === 'component')>Components</option>
                    </select>
                </div>

                <div class="col-12 col-md-3 sales-report-filter sales-report-filter-customer">
                    <label class="form-label" for="customerId">Customer</label>
                    <select id="customerId" name="customer_id" class="form-select form-select-sm">
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((int) ($filters['customer_id'] ?? 0) === (int) $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3 sales-report-filter sales-report-filter-component">
                    <label class="form-label" for="planeId">A/C Type</label>
                    <select id="planeId" name="plane_id" class="form-select form-select-sm">
                        <option value="">Select A/C type</option>
                        @foreach($planes as $plane)
                            <option value="{{ $plane->id }}" @selected((int) ($filters['plane_id'] ?? 0) === (int) $plane->id)>
                                {{ $plane->type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label" for="dateFrom">From</label>
                    <input id="dateFrom" type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label" for="dateTo">To</label>
                    <input id="dateTo" type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Build
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()" @disabled(count($rows) === 0)>
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </form>
        </div>

        @if($report['warning'])
            <div class="alert alert-warning py-2 mb-0 no-print">
                {{ $report['warning'] }}
            </div>
        @endif

        <div class="sales-report-sheet-wrap">
            <div class="sales-report-sheet">
                <div class="sales-report-title">
                    <div>
                        <h4>{{ $report['title'] }}</h4>
                        <div class="sales-report-period">{{ $report['period_label'] }}</div>
                    </div>
                    <div class="sales-report-period">Generated: {{ format_project_date(now()) }}</div>
                </div>

                @if(count($rows))
                    <table class="sales-report-table dir-table">
                        <thead>
                        @if($report['report_type'] === 'component')
                            <tr>
                                <th>AC Type</th>
                                <th>Customer</th>
                                <th>WO#</th>
                                <th>P/N</th>
                                <th>S/N</th>
                                <th>Description</th>
                                <th>Invoiced Amount</th>
                                <th>Date</th>
                            </tr>
                        @else
                            <tr>
                                <th>Company Name</th>
                                <th>WO#</th>
                                <th>P/N</th>
                                <th>S/N</th>
                                <th>Description</th>
                                <th>Invoiced Amount</th>
                                <th>Date</th>
                            </tr>
                        @endif
                        </thead>
                        <tbody>
                        @php $lastGroup = null; @endphp
                        @foreach($rows as $row)
                            @php
                                $group = $report['report_type'] === 'component'
                                    ? ($row['aircraft_type'] ?: '-')
                                    : ($row['company'] ?: '-');
                                $showGroup = $group !== $lastGroup;
                                $lastGroup = $group;
                            @endphp
                            @if($report['report_type'] === 'component')
                                <tr>
                                    <td class="sales-report-group">{{ $showGroup ? $group : '' }}</td>
                                    <td>{{ $row['company'] }}</td>
                                    <td>{{ $row['wo_number'] }}</td>
                                    <td>{{ $row['part_number'] }}</td>
                                    <td>{{ $row['serial_number'] }}</td>
                                    <td>{{ $row['description'] }}</td>
                                    <td class="sales-report-money">{{ $money($row['invoiced_amount']) }}</td>
                                    <td>{{ $row['date_label'] }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="sales-report-group">{{ $showGroup ? $group : '' }}</td>
                                    <td>{{ $row['wo_number'] }}</td>
                                    <td>{{ $row['part_number'] }}</td>
                                    <td>{{ $row['serial_number'] }}</td>
                                    <td>{{ $row['description'] }}</td>
                                    <td class="sales-report-money">{{ $money($row['invoiced_amount']) }}</td>
                                    <td>{{ $row['date_label'] }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr>
                            <td colspan="{{ $report['report_type'] === 'component' ? 6 : 5 }}" class="sales-report-total-label">TOTAL</td>
                            <td class="sales-report-total-amount">{{ $money($report['total']) }}</td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="sales-report-note">*NOTE: {{ $report['note'] }}</div>
                @else
                    <div class="sales-report-empty">
                        @if($hasRun)
                            No rows found for selected filters.
                        @else
                            Select report filters and build a report.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const typeSelect = document.getElementById('reportType');
            const customerFilter = document.querySelector('.sales-report-filter-customer');
            const componentFilter = document.querySelector('.sales-report-filter-component');

            function syncReportType() {
                const isComponent = typeSelect && typeSelect.value === 'component';
                if (customerFilter) customerFilter.classList.toggle('d-none', isComponent);
                if (componentFilter) componentFilter.classList.toggle('d-none', !isComponent);
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', syncReportType);
            }

            syncReportType();
        })();
    </script>
@endsection
