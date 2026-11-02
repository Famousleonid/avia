@extends('admin.master')
@section('content')
<div class="p-3 overflow-auto">
    <div class="d-flex align-items-center gap-3 mb-3">
        <a class="btn btn-outline-info btn-sm" href="{{ route('bushing-guide.show', $workorder) }}">← Back to form</a>
        <h1 class="h5 mb-0">{{ $guide->title }} — History</h1>
    </div>
    @forelse($entries as $entry)
        <section class="card mb-3">
            <header class="card-header">Rev {{ $entry->revision }} · {{ $entry->user_name }} · {{ \Carbon\Carbon::parse($entry->created_at)->format('d/M/Y H:i:s') }} {{ config('app.timezone') }}</header>
            <div class="card-body overflow-auto">
                @php($changes = json_decode($entry->changes, true))
                @if($entry->event === 'imported')
                    Imported {{ $changes['source'] }} — {{ implode(', ', $changes['sheets']) }}
                @else
                    <table class="table table-sm table-bordered"><thead><tr><th>Sheet / Cell</th><th>Before</th><th>After</th></tr></thead><tbody>
                    @foreach($changes as $change)
                        <tr><td>{{ $change['sheet'] }} / {{ $change['cell'] }}</td><td style="white-space:pre-wrap;min-width:200px">{{ $change['old'] }}</td><td style="white-space:pre-wrap;min-width:200px">{{ $change['new'] }}</td></tr>
                    @endforeach
                    </tbody></table>
                @endif
            </div>
        </section>
    @empty
        <p>No changes recorded.</p>
    @endforelse
    <div class="d-flex gap-3">
        @if($entries->previousPageUrl())<a class="btn btn-outline-info" href="{{ $entries->previousPageUrl() }}">Newer</a>@endif
        @if($entries->nextPageUrl())<a class="btn btn-outline-info" href="{{ $entries->nextPageUrl() }}">Older</a>@endif
    </div>
</div>
@endsection
