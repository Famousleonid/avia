@extends('admin.master')

@section('style')
    <style>
        .logs-page{height:calc(100vh - 70px);display:flex;flex-direction:column;min-height:0;}
        .logs-card{flex:1 1 auto;display:flex;flex-direction:column;min-height:0;}
        .logs-card .card-body{flex:1 1 auto;min-height:0;padding:0;}
        .logs-table-wrap{height:100%;overflow:auto;}
        .logs-table thead th{position:sticky;top:0;z-index:2;}
        .logs-table td, .logs-table th{white-space:nowrap;vertical-align:top;}
        .logs-table td.props{white-space:normal;min-width:520px;}
    </style>
@endsection

@section('content')
    <div class="logs-page dir-page">
        <div class="card logs-card dir-panel">

            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 text-primary">Activity log (all)</h5>

                <form method="GET" action="{{ route('admin.activity.index') }}" class="d-flex gap-2 align-items-center flex-wrap">

                    <input type="text" name="q" value="{{ request('q') }}"
                           class="form-control bg-dark text-light border-secondary"
                           placeholder="Search in all fields..." autocomplete="off" style="min-width:260px">

                    <select name="log_name" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                        <option value="all">All log_name</option>
                        @foreach($logNames as $ln)
                            <option value="{{ $ln }}" @selected(request('log_name','all')===$ln)>{{ $ln }}</option>
                        @endforeach
                    </select>

                    <select name="event" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                        <option value="all">All events</option>
                        @foreach(['created','updated','deleted'] as $ev)
                            <option value="{{ $ev }}" @selected(request('event','all')===$ev)>{{ $ev }}</option>
                        @endforeach
                    </select>

                    <select name="subject_type" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto; max-width:260px">
                        <option value="all">All subjects</option>
                        @foreach($subjectTypes as $st)
                            <option value="{{ $st }}" @selected(request('subject_type','all')===$st)>{{ class_basename($st) }}</option>
                        @endforeach
                    </select>

                    <select name="causer_id" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto; max-width:220px">
                        <option value="all">All users</option>
                        @foreach($causers as $u)
                            <option value="{{ $u->id }}" @selected((string)request('causer_id','all')===(string)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="from" value="{{ request('from') }}" class="form-control bg-dark text-light border-secondary" style="width:auto">
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control bg-dark text-light border-secondary" style="width:auto">

                    <select name="per_page" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                        @foreach([25,50,100,200] as $pp)
                            <option value="{{ $pp }}" @selected((int)request('per_page', $perPage)===$pp)>{{ $pp }}/page</option>
                        @endforeach
                    </select>

                    <button class="btn btn-sm btn-outline-info">Apply</button>

                    @if(request()->query())
                        <a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>

            <div class="card-body">
                <div class="logs-table-wrap">
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover table-bordered mb-0 logs-table dir-table">
                            <thead>
                            <tr class="text-muted small">
                                <th class="text-center">Date</th>
                                <th class="text-center">User</th>
                                <th class="text-center">log_name</th>
                                <th class="text-center">Event</th>
                                <th class="text-center">Subject</th>
                                <th class="text-center">ID</th>
                                <th class="text-start">Description</th>
                                <th class="text-start">Properties</th>
                            </tr>
                            </thead>

                            <tbody>
                            @forelse($activities as $a)
                                <tr>
                                    <td class="text-center small">{{ $a->created_at?->format('d.m.Y H:i') }}</td>
                                    <td class="text-center small">{{ $a->causer?->name ?? 'system' }}</td>
                                    <td class="text-center small text-info">{{ $a->log_name }}</td>
                                    <td class="text-center">
                                    <span class="badge
                                        @if($a->event === 'created') bg-success
                                        @elseif($a->event === 'updated') bg-warning text-dark
                                        @elseif($a->event === 'deleted') bg-danger
                                        @else bg-secondary
                                        @endif">
                                        {{ $a->event }}
                                    </span>
                                    </td>
                                    <td class="text-center small text-muted">{{ class_basename($a->subject_type) }}</td>
                                    <td class="text-center small text-muted">{{ $a->subject_id }}</td>
                                    <td class="small">{{ $a->description }}</td>

                                    <td class="props small">
                                    <pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">
{{ json_encode($a->properties, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
                                    </pre>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">No logs</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer py-2">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
@endsection
