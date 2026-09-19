@extends('admin.master')

@section('content')
<style>
    .traveler-notes-page { flex: 1 1 0; min-height: 0; min-width: 0; overflow: auto; }
    .traveler-notes-page .card-body, .traveler-notes-page .row > * { min-width: 0; }
    .traveler-notes-page .select2-container { width: 100% !important; }
    .traveler-notes-page .note-text { white-space: pre-wrap; overflow-wrap: anywhere; min-width: 180px; }
</style>
<div class="traveler-notes-page p-3">
    <h4 class="mb-3">Notes Traveler</h4>
    <div class="card mb-3">
        <div class="card-header">{{ $editing ? 'Edit note' : 'Add note' }}</div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ $editing ? route('library.traveler-notes.update', $editing) : route('library.traveler-notes.store') }}">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label for="note-manual" class="form-label text-info">Manual</label>
                        <select id="note-manual" name="manual_id" class="form-select" required>
                            <option value=""></option>
                            @if($selectedManual)<option value="{{ $selectedManual->id }}" selected>{{ $selectedManual->number }} — {{ $selectedManual->title }}</option>@endif
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="note-part" class="form-label text-info">Part Number</label>
                        <select id="note-part" name="part_number" class="form-select" required @disabled(!$selectedManual)>
                            <option value=""></option>
                            @if(old('part_number', $editing?->part_number))<option selected value="{{ old('part_number', $editing?->part_number) }}">{{ old('part_number', $editing?->part_number) }}</option>@endif
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="note-process" class="form-label text-info">Process Name</label>
                        <select id="note-process" name="process_names_id" class="form-select" required>
                            <option value=""></option>
                            @if($selectedProcess)<option selected value="{{ $selectedProcess->id }}">{{ $selectedProcess->name }}</option>@endif
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="note-text" class="form-label text-info">Notes</label>
                        <textarea id="note-text" name="notes" class="form-control" rows="3" maxlength="2000" required>{{ old('notes', $editing?->notes) }}</textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Add note' }}</button>
                        @if($editing)<a href="{{ route('library.traveler-notes.index') }}" class="btn btn-outline-secondary">Cancel</a>@endif
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 mb-3" role="search">
                <input name="q" value="{{ $q }}" class="form-control" placeholder="Search manual, P/N, process or notes" aria-label="Search notes">
                <button class="btn btn-outline-primary">Search</button>
                @if($q !== '')<a class="btn btn-outline-secondary" href="{{ route('library.traveler-notes.index') }}">Reset</a>@endif
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Manual</th><th>Part Number</th><th>Process Name</th><th>Notes</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->manual?->number }}</td><td>{{ $template->part_number }}</td>
                            <td>{{ $template->processName?->name }}</td><td class="note-text">{{ $template->notes }}</td>
                            <td><div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('library.traveler-notes.index', ['edit' => $template->id]) }}">Edit</a>
                                <form method="POST" action="{{ route('library.traveler-notes.destroy', $template) }}" class="note-mutation" data-no-spinner data-confirm="Delete this Traveler note?" data-danger="1">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No Traveler notes found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $templates->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const optionsUrl = @json(route('library.traveler-notes.options'));
    function initSelect(selector, type, placeholder) {
        $(selector).select2({
            width: '100%', placeholder, allowClear: true, minimumResultsForSearch: 0,
            ajax: {
                url: optionsUrl, dataType: 'json', delay: 200,
                data: params => ({ type, q: params.term || '', page: params.page || 1, manual_id: $('#note-manual').val() || undefined }),
                processResults: data => data
            }
        });
    }
    initSelect('#note-manual', 'manual', 'Search manual');
    initSelect('#note-part', 'part', 'Search part number');
    initSelect('#note-process', 'process', 'Search process name');
    $('#note-manual').on('change', function () {
        $('#note-part').empty().append(new Option('', '')).val(null).prop('disabled', !this.value).trigger('change');
    });
    document.querySelectorAll('.note-mutation').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (form.dataset.pending === '1') return;
            if (typeof window.confirmDialog !== 'function') {
                window.showNotification('Confirmation dialog is unavailable. Please reload the page.', 'error');
                return;
            }
            form.dataset.pending = '1';
            try {
                if (await window.confirmDialog({ title: 'Notes Traveler', message: form.dataset.confirm, danger: form.dataset.danger === '1' })) {
                    window.safeShowSpinner?.();
                    HTMLFormElement.prototype.submit.call(form);
                }
            } finally { form.dataset.pending = '0'; }
        });
    });
});
</script>
@endsection
