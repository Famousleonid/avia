@extends('admin.master')

@section('style')
    <style>
        .ec-page {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .ec-card {
            min-height: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .ec-card-body {
            min-height: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .ec-table-scroll {
            min-height: 0;
            flex: 1 1 auto;
            overflow: auto;
        }

        .ec-table {
            min-width: 1120px;
            margin-bottom: 0;
            table-layout: fixed;
        }

        .ec-table th,
        .ec-table td {
            vertical-align: middle;
        }

        .ec-table thead th {
            font-size: .78rem;
            line-height: 1.1;
            white-space: normal;
        }

        .ec-table tbody td {
            font-size: .8rem;
            line-height: 1.15;
        }

        .ec-col-description {
            width: 250px;
        }

        .ec-col-part,
        .ec-col-date,
        .ec-col-wo {
            width: 92px;
        }

        .ec-col-applicability {
            width: 170px;
        }

        .ec-col-approval {
            width: 120px;
        }

        .ec-col-notes {
            width: 220px;
        }

        .ec-approval-input {
            min-width: 0;
            height: 28px;
            padding: .125rem .35rem;
            font-size: .78rem;
        }

        .ec-pagination {
            flex: 0 0 auto;
            padding: .5rem .75rem;
            border-top: 1px solid var(--bs-border-color);
        }

        .ec-header-controls {
            min-width: 0;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3 ec-page">
        <div class="card bg-gradient ec-card">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3 ec-header-controls">
                    <h5 class="text-info mb-0">EC</h5>
                    <span class="text-secondary small">{{ $ecRows->total() }} rows</span>
                    <form method="GET" action="{{ route('ec.index') }}" class="m-0">
                        <div class="form-check form-switch mb-0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="ec-show-all"
                                name="show_all"
                                value="1"
                                @checked($showAll)
                            >
                            <label class="form-check-label small text-secondary" for="ec-show-all">Show all</label>
                        </div>
                    </form>
                </div>
                <span class="text-secondary small">{{ $showAll ? 'All' : 'In work' }}</span>
            </div>
            <div class="card-body ec-card-body">
                <div class="table-responsive dir-table-wrap ec-table-scroll">
                    <table class="table table-bordered table-sm table-hover dir-table dir-table--wrap ec-table">
                        <thead>
                        <tr>
                            <th class="ec-col-description">Repair / Modification Part Description</th>
                            <th class="text-center ec-col-part">End Assy/<br>Part Number</th>
                            <th class="text-center ec-col-part">Affected Part No.</th>
                            <th class="text-center ec-col-applicability">Applicability</th>
                            <th class="text-center ec-col-approval">Approval No.</th>
                            <th class="text-center ec-col-date">Request Date</th>
                            <th class="text-center ec-col-date">Issue Date</th>
                            <th class="text-center ec-col-wo">WO No.</th>
                            <th class="text-center ec-col-notes">NOTES</th>
                        </tr>
                        </thead>
                        <tbody id="ec-table-body">
                            @include('admin.ec.partials.rows', ['ecRows' => $ecRows])
                        </tbody>
                    </table>
                </div>

                <div
                    class="ec-pagination text-center text-secondary small"
                    id="ec-infinite-status"
                    data-next-page-url="{{ $ecRows->nextPageUrl() }}"
                >
                    @if($ecRows->hasMorePages())
                        Scroll to load more
                    @else
                        All EC rows loaded
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scrollArea = document.querySelector('.ec-table-scroll');
            const tbody = document.getElementById('ec-table-body');
            const status = document.getElementById('ec-infinite-status');
            const showAll = document.getElementById('ec-show-all');

            if (showAll && showAll.form) {
                showAll.addEventListener('change', function () {
                    showAll.form.submit();
                });
            }

            if (!scrollArea || !tbody || !status) return;

            let nextPageUrl = status.dataset.nextPageUrl || '';
            let loading = false;

            async function loadMoreEcRows() {
                if (loading || !nextPageUrl) return;

                loading = true;
                status.textContent = 'Loading...';

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load EC rows.');
                    }

                    const payload = await response.json();
                    tbody.insertAdjacentHTML('beforeend', payload.html || '');
                    nextPageUrl = payload.next_page_url || '';
                    status.dataset.nextPageUrl = nextPageUrl;
                    status.textContent = nextPageUrl ? 'Scroll to load more' : 'All EC rows loaded';
                } catch (error) {
                    status.textContent = 'Could not load more EC rows.';
                } finally {
                    loading = false;
                }
            }

            scrollArea.addEventListener('scroll', function () {
                const distanceToBottom = scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight;
                if (distanceToBottom <= 160) {
                    loadMoreEcRows();
                }
            });
        });
    </script>
@endsection
