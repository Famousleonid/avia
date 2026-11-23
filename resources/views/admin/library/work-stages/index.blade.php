@extends('admin.master')

@section('style')
    <link rel="stylesheet" href="{{ asset('css/library/work-stages.css') }}?v={{ filemtime(public_path('css/library/work-stages.css')) }}">
@endsection

@section('content')
    <div class="stage-library" data-stage-library data-url="{{ route('library.work-stages.index') }}">
        <h1 class="h5 stage-library-title">Tasks &amp; Work Stages</h1>
        <div class="stage-library-grid">
            <section class="stage-catalog card" aria-labelledby="stage-tasks-heading">
                <header class="stage-catalog-head">
                    <h2 id="stage-tasks-heading" class="h6">General Tasks &amp; Tasks</h2>
                    <button type="button" class="btn btn-sm btn-outline-info" data-stage-edit="general" data-url="{{ route('general-tasks.store') }}">Add general task</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-stage-refresh="tasks" hidden>Refresh table</button>
                </header>
                <div class="stage-catalog-scroll" data-section="tasks">
                    @include('admin.library.work-stages.tasks')
                </div>
            </section>
            <section class="stage-catalog card" aria-labelledby="work-stages-heading">
                <header class="stage-catalog-head">
                    <h2 id="work-stages-heading" class="h6">Work Stage</h2>
                    <button type="button" class="btn btn-sm btn-outline-info" data-stage-edit="work" data-url="{{ route('library.work-stages.store') }}">Add stage</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-stage-refresh="work-stages" hidden>Refresh table</button>
                </header>
                <div class="stage-catalog-scroll" data-section="work-stages">
                    @include('admin.library.work-stages.work-stages')
                </div>
            </section>
        </div>

        <template data-stage-editor>
            <tr class="stage-editor-row">
                <td colspan="1">
                    <form class="stage-editor" data-no-spinner>
                        <label class="stage-edit-name">
                            <span>Name</span>
                            <input class="form-control form-control-sm" name="name" required>
                        </label>
                        <label data-order-field>
                            <span>Order</span>
                            <input class="form-control form-control-sm" type="number" name="sort_order" min="0" max="4294967295" required>
                        </label>
                        <label data-group-field>
                            <span>General Task</span>
                            <select class="form-select form-select-sm" name="general_task_id"></select>
                        </label>
                        <label class="form-check stage-edit-start" data-start-field>
                            <input class="form-check-input" type="checkbox" name="task_has_start_date" value="1">
                            <span class="form-check-label">Start date</span>
                        </label>
                        <div class="stage-row-actions">
                            <button type="submit" class="btn btn-sm btn-info">Save</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-stage-cancel>Cancel</button>
                        </div>
                        <div class="stage-editor-message text-danger small" role="alert" hidden></div>
                    </form>
                </td>
            </tr>
        </template>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/library/work-stages.js') }}?v={{ filemtime(public_path('js/library/work-stages.js')) }}" defer></script>
@endsection
