<table class="table table-sm stage-catalog-table mb-0">
    <thead>
        <tr>
            <th scope="col">Work Stage</th>
            <th scope="col" class="stage-color-col">Color</th>
            <th scope="col" class="stage-number-col">Order</th>
            <th scope="col" class="stage-actions-col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($workStages as $stage)
            <tr data-stage-row data-name="{{ $stage->name }}" data-order="{{ $stage->sort_order }}">
                <th scope="row" class="work-stage-color" @if($stage->text_color) style="--work-stage-color: {{ $stage->text_color }}" @endif>{{ $stage->name }}</th>
                <td>
                    <div class="stage-row-actions stage-color-controls">
                        <input type="color" class="form-control form-control-color stage-color-input" data-stage-color
                               data-url="{{ route('library.work-stages.update', $stage) }}" data-saved-color="{{ $stage->text_color }}"
                               value="{{ $stage->text_color ?? '#e8eef5' }}" aria-label="Text color for {{ $stage->name }}" title="Choose text color">
                        <button type="button" class="btn btn-sm btn-outline-secondary stage-color-reset" data-stage-color-reset
                                aria-label="Restore default color for {{ $stage->name }}" title="Use default color" @if(!$stage->text_color) hidden @endif>
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        </button>
                    </div>
                </td>
                <td>{{ $stage->sort_order }}</td>
                <td>
                    <div class="stage-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-stage-edit="work" data-update data-url="{{ route('library.work-stages.update', $stage) }}" aria-label="Edit {{ $stage->name }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                        @if($stage->workflow_key === null)
                            <button type="button" class="btn btn-sm btn-outline-danger" data-stage-delete data-url="{{ route('library.work-stages.destroy', $stage) }}" aria-label="Delete {{ $stage->name }}" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr class="stage-empty"><td colspan="4" class="text-muted">No work stages</td></tr>
        @endforelse
    </tbody>
</table>
