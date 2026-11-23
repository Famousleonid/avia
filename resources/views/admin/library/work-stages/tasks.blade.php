<table class="table table-sm stage-catalog-table mb-0">
    <colgroup><col class="stage-main-col"><col><col class="stage-number-col"><col class="stage-actions-col"></colgroup>
    <thead>
        <tr><th scope="col" colspan="2">General Task / Task</th><th scope="col">Order / Date</th><th scope="col">Actions</th></tr>
    </thead>
    @forelse($groups as $group)
        @php($groupTasks = $tasksByGroup->get($group->id, collect()))
        <tbody data-stage-group="{{ $group->id }}">
            <tr class="stage-group-row" data-stage-row data-name="{{ $group->name }}" data-order="{{ $group->sort_order }}">
                <th scope="rowgroup" colspan="2">
                    {{ $group->name }}
                    @if($group->technician_stage)
                        <span class="stage-role-label">Technician</span>
                    @endif
                </th>
                <td>{{ $group->sort_order }}</td>
                <td>
                    <div class="stage-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-info" data-stage-edit="task" data-url="{{ route('tasks.store') }}" data-group="{{ $group->id }}" data-next-order="{{ min(4294967295, (int) $groupTasks->max('sort_order') + 10) }}">Add task</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-stage-edit="general" data-update data-url="{{ route('general-tasks.update', $group) }}" aria-label="Edit {{ $group->name }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                        @if(!$group->technician_stage && $groupTasks->isEmpty())
                            <button type="button" class="btn btn-sm btn-outline-danger" data-stage-delete data-history-preserved data-url="{{ route('general-tasks.destroy', $group) }}" aria-label="Delete {{ $group->name }}">Delete</button>
                        @endif
                    </div>
                </td>
            </tr>
            @foreach($groupTasks as $task)
                @include('admin.library.work-stages.task-row')
            @endforeach
        </tbody>
    @empty
        <tbody><tr class="stage-empty"><td colspan="4" class="text-muted">No general tasks</td></tr></tbody>
    @endforelse
    @if($tasksByGroup->has(''))
        <tbody>
            <tr class="stage-group-row"><th scope="rowgroup" colspan="4">Unassigned tasks</th></tr>
            @foreach($tasksByGroup->get('') as $task)
                @include('admin.library.work-stages.task-row')
            @endforeach
        </tbody>
    @endif
</table>
@if($archivedTasks->isNotEmpty())
    @php($groupNames = $groups->pluck('name', 'id'))
    <details class="stage-archive" data-stage-archive-list>
        <summary>Archived tasks ({{ $archivedTasks->count() }})</summary>
        <table class="table table-sm stage-catalog-table mb-0">
            <tbody>
                @foreach($archivedTasks as $task)
                    <tr data-stage-row data-name="{{ $task->name }}">
                        <th scope="row">{{ $task->name }}</th>
                        <td class="text-muted small">{{ $groupNames->get($task->general_task_id, 'Unassigned') }} · {{ $task->sort_order }}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-secondary" data-stage-action="restore" data-url="{{ route('tasks.restore', $task) }}" aria-label="Restore {{ $task->name }}">Restore</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
@endif
<template data-general-task-options>
    <option value="">Select a general task</option>
    @foreach($groups as $group)
        <option value="{{ $group->id }}">{{ $group->name }}</option>
    @endforeach
</template>
