@extends('admin.master')
@section('content')
<div class="container-fluid py-3">
    <h4>Archived processes — {{ $manual->number }}</h4>
    <a class="btn btn-outline-info mb-3" href="{{ route('manuals.show', ['manual' => $manual, 'tab' => 'processes']) }}">Back to manual</a>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>Process</th><th>Specification</th><th>Archived</th><th>Action</th></tr></thead>
        <tbody>@forelse($links as $link)<tr>
            <td>{{ $link->process?->process_name?->name }}</td>
            <td style="white-space: pre-wrap">{{ $link->process?->process }}</td>
            <td>@projectDate($link->deleted_at)</td>
            <td><a href="{{ route('manual_processes.history', $link->id) }}">History</a>
                <form method="post" action="{{ route('manual_processes.restore', $link->id) }}" class="process-restore">@csrf
                    <button class="btn btn-outline-info btn-sm">Restore</button>
                </form>
            </td>
        </tr>@empty<tr><td colspan="4">No archived processes.</td></tr>@endforelse</tbody>
    </table></div>
</div>
<script>
document.querySelectorAll('.process-restore').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    if (typeof window.confirmDialog !== 'function') { window.showNotification?.('Confirmation is unavailable.', 'error'); return; }
    if (await window.confirmDialog({message: 'Restore this process in this manual?', okText: 'Restore'})) HTMLFormElement.prototype.submit.call(form);
}));
</script>
@endsection
