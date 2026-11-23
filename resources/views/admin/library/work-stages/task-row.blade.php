<tr data-stage-row data-name="{{ $task->name }}" data-order="{{ $task->sort_order }}" data-start="{{ (int) $task->task_has_start_date }}" data-group="{{ $task->general_task_id }}" @if($task->main_rows_exists) data-linked @endif>
    <td></td>
    <th scope="row">{{ $task->name }}</th>
    <td>
        <div>{{ $task->sort_order }}</div>
        <div class="text-muted small">{{ $task->task_has_start_date ? 'Start / Finish' : 'Finish' }}</div>
    </td>
    <td>
        <div class="stage-row-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-stage-edit="task" data-update data-url="{{ route('tasks.update', $task) }}" aria-label="Edit {{ $task->name }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></button>
            @if($task->isRequiredByWorkflow())
                <span class="stage-workflow-lock text-muted" title="Used by the workorder workflow" aria-label="Used by the workorder workflow"><i class="bi bi-lock" aria-hidden="true"></i></span>
            @elseif($task->main_rows_exists)
                <button type="button" class="btn btn-sm btn-outline-secondary" data-stage-action="archive" data-url="{{ route('tasks.archive', $task) }}" aria-label="Archive {{ $task->name }}" title="Archive and keep WO history"><i class="bi bi-archive" aria-hidden="true"></i></button>
            @else
                <button type="button" class="btn btn-sm btn-outline-danger" data-stage-delete data-url="{{ route('tasks.destroy', $task) }}" aria-label="Delete {{ $task->name }}" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button>
            @endif
        </div>
    </td>
</tr>
