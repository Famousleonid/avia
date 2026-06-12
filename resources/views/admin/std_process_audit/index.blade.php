@extends('admin.master')

@section('style')
    <style>
        .std-audit-page {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 16px;
        }
        .std-audit-toolbar {
            display: grid;
            grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr) auto;
            gap: 8px;
            align-items: end;
        }
        .std-audit-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .std-audit-table-wrap {
            overflow: auto;
            min-height: 0;
        }
        .std-audit-rows-table {
            margin-bottom: 0;
        }
        .std-audit-rows-table td,
        .std-audit-rows-table th {
            padding: .35rem .5rem;
        }
        @media (max-width: 900px) {
            .std-audit-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="std-audit-page">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">STD Process Audit</h1>
                <div class="text-muted small">Mixed process values by manual, STD type, and numeric IPL.</div>
            </div>
            <div class="std-audit-summary">
                <span class="badge text-bg-warning">{{ $conflicts->count() }} conflicts</span>
                @foreach($stdLabels as $key => $label)
                    <span class="badge text-bg-secondary">{{ $label }}: {{ $totalsByStd[$key] ?? 0 }}</span>
                @endforeach
            </div>
        </div>

        <form method="get" action="{{ route('admin.std-process-audit.index') }}" class="std-audit-toolbar mb-3">
            <div>
                <label for="std-audit-std" class="form-label small mb-1">STD</label>
                <select id="std-audit-std" name="std" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($stdLabels as $key => $label)
                        <option value="{{ $key }}" @selected($std === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="std-audit-search" class="form-label small mb-1">Search</label>
                <input id="std-audit-search"
                       type="search"
                       name="q"
                       value="{{ $search }}"
                       class="form-control form-control-sm"
                       placeholder="Manual, IPL, process, part no.">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                <a href="{{ route('admin.std-process-audit.index') }}" class="btn btn-outline-secondary btn-sm">
                    Reset
                </a>
            </div>
        </form>

        @if($conflicts->isEmpty())
            <div class="alert alert-success mb-0">
                No mixed STD process groups found.
            </div>
        @else
            <div class="std-audit-table-wrap">
                <table class="table table-sm table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 18%;">Manual</th>
                        <th style="width: 8%;" class="text-center">STD</th>
                        <th style="width: 12%;" class="text-center">Numeric IPL</th>
                        <th style="width: 18%;">Processes</th>
                        <th>Rows</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($conflicts as $conflict)
                        <tr class="table-warning">
                            <td>
                                <a href="{{ route('manuals.show', ['manual' => $conflict['manual_id'], 'tab' => 'std', 'std_inner' => $conflict['std']]) }}">
                                    {{ $conflict['manual_number'] ?: ('Manual #' . $conflict['manual_id']) }}
                                </a>
                                @if($conflict['manual_title'])
                                    <div class="small text-muted">{{ $conflict['manual_title'] }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $stdLabels[$conflict['std']] ?? strtoupper($conflict['std']) }}</td>
                            <td class="text-center fw-semibold">{{ $conflict['base_ipl'] }}</td>
                            <td>
                                @foreach($conflict['processes'] as $process)
                                    <span class="badge text-bg-warning me-1">{{ $process }}</span>
                                @endforeach
                            </td>
                            <td>
                                <table class="table table-sm table-bordered std-audit-rows-table">
                                    <thead>
                                    <tr>
                                        <th style="width: 14%;">IPL</th>
                                        <th style="width: 20%;">Part No.</th>
                                        <th>Description</th>
                                        <th style="width: 14%;" class="text-center">Process</th>
                                        <th style="width: 8%;" class="text-center">Qty</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($conflict['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['ipl_num'] }}</td>
                                            <td>{{ $row['part_number'] }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($row['description'], 90) }}</td>
                                            <td class="text-center fw-semibold">{{ $row['process'] }}</td>
                                            <td class="text-center">{{ $row['qty'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
