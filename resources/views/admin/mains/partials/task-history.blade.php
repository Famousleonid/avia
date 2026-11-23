<section class="border-top px-2 py-1" aria-label="Task history">
    <div class="table-responsive">
        <table class="table table-sm table-dark table-bordered align-middle mb-1">
            <thead>
                <tr><th scope="col">General Task</th><th scope="col">Task</th><th scope="col">Start</th><th scope="col">Finish</th><th scope="col">Date updated by</th></tr>
            </thead>
            <tbody>
                @foreach($taskHistory as $history)
                    <tr data-history-task-id="{{ $history->task_id }}" tabindex="-1">
                        <td>{{ $history->task->generalTask?->name }}</td>
                        <th scope="row">{{ $history->task->name }}</th>
                        <td data-history-field="date_start" tabindex="-1">{{ format_project_date($history->date_start) ?? '—' }}</td>
                        <td data-history-field="date_finish" tabindex="-1">{{ format_project_date($history->date_finish) ?? '—' }}</td>
                        <td>{{ $history->user?->selection_name ?? '—' }}@if($history->ignore_row) <span class="text-muted">(Ignored)</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
