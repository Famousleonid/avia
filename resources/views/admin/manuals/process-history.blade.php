@extends('admin.master')
@section('content')
<div class="container-fluid py-3">
    <h4>Process history — {{ $link->process?->process_name?->name }}</h4>
    <a class="btn btn-outline-info mb-3" href="{{ route('manuals.show', ['manual' => $link->manual_id, 'tab' => 'processes']) }}">Back to manual</a>
    <p>Saved templates and Travelers retain their selected specification. Changing a template requires selecting and saving the new specification explicitly.</p>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>Version</th><th>Specification</th><th>Comment</th><th>Required</th><th>Recorded</th></tr></thead>
        <tbody>@forelse($versions as $version)<tr>
            <td>{{ $version->version }}{{ $version->process_id == $link->processes_id ? ' (current)' : '' }}</td>
            <td style="white-space: pre-wrap">{{ $version->process?->process }}</td>
            <td style="white-space: pre-wrap">{{ $version->process_comment }}</td>
            <td>{{ $version->requires_fig ? 'FIG ' : '' }}{{ $version->requires_zone ? 'ZONE' : '' }}</td>
            <td>@projectDate($version->created_at)<br>{{ $version->createdBy?->selection_name ?? 'Baseline' }}</td>
        </tr>@empty<tr><td>1 (current)</td><td style="white-space: pre-wrap">{{ $link->process?->process }}</td><td>{{ $link->process_comment }}</td><td>{{ $link->requires_fig ? 'FIG ' : '' }}{{ $link->requires_zone ? 'ZONE' : '' }}</td><td>Original version</td></tr>@endforelse</tbody>
    </table></div>
</div>
@endsection
