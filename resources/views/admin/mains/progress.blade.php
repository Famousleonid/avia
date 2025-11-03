@extends('admin.master')

@section('links')
    <style>

        [data-bs-theme="dark"] #stats-by-wo {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }

        [data-bs-theme="dark"] #stats-by-wo th,
        [data-bs-theme="dark"] #stats-by-wo td {
            background: transparent !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] #stats-by-wo thead th {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
            color: #0DDDFD !important;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        #stats-by-wo tr, #stats-by-wo td, #stats-by-wo th {
            height: 44px !important;
            max-height: 44px !important;
            min-height: 44px !important;
            overflow: hidden !important;
            line-height: 1 !important;
            vertical-align: middle !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .stat-badge {
            font-weight: 600;
        }

        @media (max-width: 768px) {
            #stats-by-wo th:nth-child(3), #stats-by-wo td:nth-child(3) {
                display: none;
            }

            /* User */
            #stats-by-wo th:nth-child(6), #stats-by-wo td:nth-child(6) {
                display: none;
            }

            /* Closed */
        }

    </style>
@endsection

@section('content')
    <section class="container-fluid pl-5 pr-5">
        <div class="card shadow">
            <div class="card-body p-3">

                <form method="get" action="{{ route('progress.index') }}" id="progress-filters" class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-4">
                        <label for="sl_technik" class="form-label mb-1">Technician</label>
                        <select name="technik" id="sl_technik" class="form-control">
                            <option value="">— All users —</option>
                            @foreach ($team_techniks as $t)
                                <option value="{{ $t->id }}" @selected((int)$technikId === (int)$t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="sl_customer" class="form-label mb-1">Customer</label>
                        <select name="customer" id="sl_customer" class="form-control">
                            <option value="">— All customers —</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected((int)$customerId === (int)$c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4 d-flex gap-3 align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="hide_done" name="hide_done"
                                   value="1" @checked($hideDone)>
                            <label class="form-check-label" for="hide_done">Hide Done</label>
                        </div>

                        <div class="flex-fill p-2 border rounded text-center">
                            <div class="text-secondary small">Total</div>
                            <div class="h5 mb-0 stat-badge">{{ $totals->total ?? 0 }}</div>
                        </div>
                        <div class="flex-fill p-2 border rounded text-center">
                            <div class="text-secondary small">Open</div>
                            <div class="h5 mb-0 stat-badge text-warning">{{ $totals->open ?? 0 }}</div>
                        </div>
                        <div class="flex-fill p-2 border rounded text-center">
                            <div class="text-secondary small">Closed</div>
                            <div class="h5 mb-0 stat-badge text-success">{{ $totals->closed ?? 0 }}</div>
                        </div>
                    </div>
                </form>

                {{-- Таблица сводки по воркдрам --}}
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-hover table-striped w-100" id="stats-by-wo">
                        <thead>
                        <tr>
                            <th class="text-primary text-center">Wo Number</th>
                            <th class="text-primary">Customer</th>
                            <th class="text-primary">User</th>
                            <th class="text-primary text-center">Total</th>
                            <th class="text-primary text-center">Open</th>
                            <th class="text-primary text-center">Closed</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($byWorkorder as $row)
                            <tr>
                                <td class="text-center">
                                    <a href="{{ route('mains.show', $row->wo_id) }}" class="text-decoration-none">

                                        @if($row->has_done)
                                            <span class="text-muted">{{ $row->number }}</span>
                                        @else
                                            <span>w&nbsp;{{ $row->number }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td>{{ $row->customer_name }}</td>
                                <td>{{ $row->user_names ?: '—' }}</td>
                                <td class="text-center">
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                          title="{{ $row->task_names ?? '' }}">{{ $row->total_tasks }}</span>
                                </td>

                                <td class="text-center text-warning">{{ $row->open_tasks }}</td>
                                <td class="text-center text-success">{{ $row->closed_tasks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No data</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            try {
                if (typeof window.hideLoadingSpinner === 'function') hideLoadingSpinner();
            } catch (e) {
            }

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) {
                return new bootstrap.Tooltip(el, {trigger: 'hover focus'});
            });

            const form = document.getElementById('progress-filters');
            const hideDone = document.getElementById('hide_done');
            const selUser = document.getElementById('sl_technik');
            const selCustomer = document.getElementById('sl_customer');

            function submitWithSpinner() {
                try {
                    if (typeof window.showLoadingSpinner === 'function') showLoadingSpinner();
                } catch (e) {
                }
                form.submit();
            }

            hideDone?.addEventListener('change', submitWithSpinner);
            selUser?.addEventListener('change', submitWithSpinner);
            selCustomer?.addEventListener('change', submitWithSpinner);
        });

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                try {
                    if (typeof window.hideLoadingSpinner === 'function') hideLoadingSpinner();
                } catch (e) {
                }
            }
        });
    </script>

@endsection
