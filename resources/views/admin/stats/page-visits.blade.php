@extends('admin.master')

@section('style')
    <style>
        .page-visit-stat {
            height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            min-height: 0;
            color: #d9e6e8;
        }

        .page-visit-panel {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            background: #1f2224;
            border: 1px solid rgba(86, 203, 224, .22);
            border-radius: 6px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, .28);
        }

        .page-visit-header {
            flex: 0 0 auto;
            border-bottom: 1px solid rgba(86, 203, 224, .18);
            padding: .75rem .9rem;
        }

        .page-visit-title {
            color: #49c7e5;
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .page-visit-filter {
            display: grid;
            grid-template-columns: minmax(220px, 320px) repeat(2, minmax(145px, 170px)) auto auto;
            gap: .5rem;
            align-items: end;
        }

        .page-visit-filter label {
            color: #8fa6ab;
            font-size: .72rem;
            margin-bottom: .18rem;
        }

        .page-visit-filter .form-control,
        .page-visit-filter .form-select {
            background-color: #17191b;
            border-color: rgba(86, 203, 224, .25);
            color: #eaf7f9;
            min-height: 32px;
        }

        .page-visit-summary {
            color: #91a8ad;
            font-size: .78rem;
            white-space: nowrap;
        }

        .page-visit-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .page-visit-table {
            margin: 0;
            min-width: 980px;
        }

        .page-visit-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #17191b;
            color: #49c7e5;
            border-color: rgba(86, 203, 224, .22);
            font-weight: 600;
            white-space: nowrap;
        }

        .page-visit-table td {
            border-color: rgba(148, 163, 166, .18);
            vertical-align: top;
        }

        .page-visit-user {
            color: #f2f7f8;
            font-weight: 600;
        }

        .page-visit-email,
        .page-visit-route {
            color: #8fa6ab;
            font-size: .75rem;
        }

        .page-list {
            display: grid;
            gap: .32rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .page-list-item {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr);
            gap: .5rem;
            align-items: start;
            min-width: 0;
        }

        .page-visit-time {
            color: #f5c542;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .page-visit-path {
            color: #e8f7fa;
            overflow-wrap: anywhere;
        }

        .page-visit-datetime {
            color: #f2f7f8;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        @media (max-width: 980px) {
            .page-visit-filter {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-visit-stat p-2">
        <div class="page-visit-panel">
            <div class="page-visit-header">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-2">
                    <h5 class="page-visit-title">Page visit stats</h5>
                    <div class="page-visit-summary">
                        Showing {{ $totalVisits }} visits{{ $selectedUserId ? ' for selected user' : ' grouped by date' }}
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.page-visits.index') }}" class="page-visit-filter">
                    <div>
                        <label for="stat_user_id">User</label>
                        <select name="user_id" id="stat_user_id" class="form-select form-select-sm">
                            <option value="">All users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>
                                    {{ $user->selection_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="stat_from">From</label>
                        <input type="date" name="from" id="stat_from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                    </div>

                    <div>
                        <label for="stat_to">To</label>
                        <input type="date" name="to" id="stat_to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                    </div>

                    <button type="submit" class="btn btn-outline-info btn-sm">Apply</button>

                    @if(request()->query())
                        <a href="{{ route('admin.page-visits.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </form>
            </div>

            <div class="page-visit-body">
                <table class="table table-sm table-dark table-hover page-visit-table">
                    <thead>
                    <tr>
                        @if($selectedUserId)
                            <th style="width: 240px;">User</th>
                            <th style="width: 140px;">Date</th>
                            <th style="width: 90px;" class="text-center">Visits</th>
                            <th>Pages</th>
                        @else
                            <th style="width: 140px;">Date</th>
                            <th style="width: 90px;" class="text-center">Visits</th>
                            <th style="width: 110px;">Time</th>
                            <th style="width: 240px;">User</th>
                            <th>Page</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @if($selectedUserId)
                        @forelse($dateGroups as $group)
                            <tr>
                                <td>
                                    <div class="page-visit-user">{{ $group->user->selection_name ?? 'Unknown user' }}</div>
                                    @if($group->user?->email)
                                        <div class="page-visit-email">{{ $group->user->email }}</div>
                                    @endif
                                </td>
                                <td>{{ format_project_date($group->date) ?? '-' }}</td>
                                <td class="text-center">{{ $group->visits_count }}</td>
                                <td>
                                    <ul class="page-list">
                                        @foreach($group->visits as $visit)
                                            <li class="page-list-item">
                                                <span class="page-visit-time">{{ $visit->time }}</span>
                                                <span>
                                                    <span class="page-visit-path">{{ $visit->path }}</span>
                                                    @if($visit->route_name)
                                                        <span class="page-visit-route">({{ $visit->route_name }})</span>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No page visits found.</td>
                            </tr>
                        @endforelse
                    @else
                        @forelse($dateGroups as $group)
                            @foreach($group->visits as $visit)
                                <tr>
                                    @if($loop->first)
                                        <td rowspan="{{ $group->visits->count() }}">{{ format_project_date($group->date) ?? '-' }}</td>
                                        <td rowspan="{{ $group->visits->count() }}" class="text-center">{{ $group->visits_count }}</td>
                                    @endif
                                    <td><span class="page-visit-time">{{ $visit->time }}</span></td>
                                    <td><div class="page-visit-user">{{ $visit->user->selection_name ?? 'Unknown user' }}</div></td>
                                    <td>
                                        <span class="page-visit-path">{{ $visit->path }}</span>
                                        @if($visit->route_name)
                                            <span class="page-visit-route">({{ $visit->route_name }})</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No page visits found.</td>
                            </tr>
                        @endforelse
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
