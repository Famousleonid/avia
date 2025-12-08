@extends('mobile.master')

@section('style')
    <style>

        .table.tasks-table {
            font-size: 0.66rem;   /* общий размер текста */
        }
        .app-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .tasks-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            padding: 5px;
        }

        .task-card {
            position: relative;
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            background-color: #2b3035;
            border-radius: 10px;
            border: 1px solid #495057;
            padding: 12px;
            color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        .tasks-table-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: auto;
            margin-top: 4px;
        }

        .card-header-line {
            border: 1px solid #0d6efd;
            border-radius: 8px;
            padding: 6px 10px;
            margin-bottom: 10px;
            background: #0b1525;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            font-size: .9rem;
        }

        .card-header-line span.title {
            font-weight: 600;
            color: #0d6efd;
        }

        .form-label {
            font-size: .75rem;
            margin-bottom: 2px;
        }

        .form-control-sm,
        .form-select-sm {
            font-size: .8rem;
            padding: 3px 6px;
            height: 32px;
        }

        .btn-add-task {
            white-space: nowrap;
            height: 32px;
            padding-inline: 10px;
            font-size: .8rem;
        }

        .row-tasks-line .col-category {
            flex: 0 0 40%;
            max-width: 40%;
        }
        .row-tasks-line .col-task {
            flex: 0 0 40%;
            max-width: 40%;
        }
        .row-tasks-line .col-add {
            flex: 0 0 20%;
            max-width: 20%;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
        }

        .date-cell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: .66rem;
            user-select: none;
        }

        .date-cell > * {
            position: relative;
            z-index: 1;
        }

        .date-cell-empty {
            background-color: #050608;
            color: #6c757d;
        }

        .date-cell-filled {
            background-color: rgba(25, 135, 84, 0.25); /* лёгкий success */
            color: #cfe9dd;
        }

        .date-text {
            flex: 1 1 auto;
            font-family: monospace;
            letter-spacing: .5px;
        }

        .date-check {
            flex: 0 0 auto;
            font-size: .9rem;
            color: #198754;
        }

        .date-calendar {
            flex: 0 0 auto;
            padding: 0 4px;
            border: none;
            background: transparent;
            color: #adb5bd;
        }

        .date-calendar i {
            font-size: 1rem;
        }

        .date-cell-empty .date-calendar {
            color: #6c757d;
        }

        .date-picker-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            z-index: 2;
            cursor: pointer;
        }

        .tasks-table th:nth-child(1),
        .tasks-table td:nth-child(1) {
            width: 15%; /* Technik */
        }

        .tasks-table th:nth-child(2),
        .tasks-table td:nth-child(2) {
            width: 20%; /* Task */
        }

        .tasks-table th:nth-child(3),
        .tasks-table td:nth-child(3),
        .tasks-table th:nth-child(4),
        .tasks-table td:nth-child(4) {
            width: 25%; /* Start и Finish по 30% */
        }

        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

    </style>
@endsection

@section('content')
    <div class="tasks-wrapper bg-dark p-0">

        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold  fs-2 ms-3">
                @if(!$workorder->isDone())
                    <span class="text-info">W {{ $workorder->number }}</span>
                @else
                    <span class="text-secondary"> {{ $workorder->number }}</span>
                @endif

                @if($workorder->open_at)<span class="text-secondary fw-normal fs-6 me-4">Open at: {{ $workorder->open_at->format('d-M-Y') }}</span>@endif
            </div>

        </div>

        <hr class="border-secondary opacity-50 my-2">

        <div class="task-card " id="task-card">

            <div class="row g-2 mb-2">
                <div class="col-12">
                    <select id="user_id" class="form-select form-select-sm">
                        @php $me = auth()->user(); @endphp
                        @if($currentUserId && $me)
                            <option value="{{ $currentUserId }}" selected>
                                {{ $me->name }}
                            </option>
                        @endif
                        @foreach($users as $user)
                            @if(!$currentUserId || $user->id !== $currentUserId)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-2 row-tasks-line">
                <div class="col-category">
{{--                    <label class="form-label" for="general_task_id">Category</label>--}}
                    <select id="general_task_id" class="form-select form-select-sm">
                        <option value=""> - choose category - </option>
                        @foreach($generalTasks as $gt)
                            <option value="{{ $gt->id }}">{{ $gt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-task">
{{--                    <label class="form-label" for="task_id">Task</label>--}}
                    <select id="task_id" class="form-select form-select-sm" disabled>
                        <option value=""> - choose task - </option>
                    </select>
                </div>

                <div class="col-add">
                    <button type="button" id="add-task-btn" class="btn btn-success btn-sm btn-add-task">
                        Add task
                    </button>
                </div>
            </div>

            <div id="task-info" class="text-secondary small mb-2">
                Select user, category and task, then press <b>Add task</b>.
                Dates can be edited via datepicker in the table.
            </div>

            <div id="tasks-table-wrapper" class="tasks-table-wrapper">
                <div id="tasks-table"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

@section('scripts')
    <script>
        // Текущий воркордер – один на всю страницу
        const WORKORDER_ID = {{ $workorder->id }};

        const generalTasks = @json($generalTasks);

        function populateTaskSelect() {
            const generalId = $('#general_task_id').val();
            const $taskSelect = $('#task_id');

            $taskSelect.empty().append('<option value="">-- choose task --</option>');

            if (!generalId) {
                $taskSelect.prop('disabled', true);
                return;
            }

            const found = generalTasks.find(gt => String(gt.id) === String(generalId));

            if (!found || !found.tasks) {
                $taskSelect.prop('disabled', true);
                return;
            }

            found.tasks.forEach(t => {
                $taskSelect.append(`<option value="${t.id}">${t.name}</option>`);
            });

            $taskSelect.prop('disabled', false);
        }

        function loadTasks() {
            $('#tasks-table').html('');

            // карточку никогда не скрываем – воркордер уже выбран
            $('#task-card').removeClass('d-none');
            $('#task-info').text('').addClass('d-none');

            showLoadingSpinner();

            $.ajax({
                url: '{{ route('mobile.tasks.byWorkorder') }}',
                type: 'POST',
                data: {
                    workorder_id: WORKORDER_ID,
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    $('#tasks-table').html(res.html);
                },
                error: function () {
                    $('#task-info')
                        .text('Error loading tasks.')
                        .removeClass('d-none');
                },
                complete: function () {
                    hideLoadingSpinner();
                }
            });
        }

        function formatDatePretty(value) {
            if (!value) return '...';
            const [y, m, d] = value.split('-');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const month = months[parseInt(m, 10) - 1] || '???';
            const yy = y.slice(-2);
            return `${d}-${month}-${yy}`;
        }

        function refreshDateCell($cell, value) {
            const $text  = $cell.find('.date-text');
            const $check = $cell.find('.date-check');

            if (value) {
                $cell.removeClass('date-cell-empty').addClass('date-cell-filled');
                $text.text(formatDatePretty(value));
                $check.html('<i class="bi bi-check2"></i>');
            } else {
                $cell.removeClass('date-cell-filled').addClass('date-cell-empty');
                $text.text('...');
                $check.html('');
            }
        }

        $(document).ready(function () {

            // выбор задач по категории
            $('#general_task_id').on('change', populateTaskSelect);

            // грузим задачи для текущего воркордера сразу при открытии страницы
            loadTasks();

            // Добавление новой записи Main
            $('#add-task-btn').on('click', function () {
                const userId = $('#user_id').val();
                const taskId = $('#task_id').val();

                if (!userId) {
                    alert('Select technik.');
                    return;
                }
                if (!taskId) {
                    alert('Select task.');
                    return;
                }

                showLoadingSpinner();

                $.ajax({
                    url: '{{ route('mobile.tasks.store') }}',
                    type: 'POST',
                    data: {
                        workorder_id: WORKORDER_ID,
                        user_id: userId,
                        task_id: taskId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function () {
                        loadTasks();
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        $('#task-info')
                            .text('Error saving task.')
                            .removeClass('d-none');
                    },
                    complete: function () {
                        hideLoadingSpinner();
                    }
                });
            });

            // Клик по ячейке даты / иконке календаря
            $(document).on('click', '.date-cell, .date-calendar', function () {
                const $cell = $(this).closest('.date-cell');
                const input = $cell.find('.date-picker-input')[0];
                if (!input) return;

                try {
                    if (typeof input.showPicker === 'function') {
                        input.showPicker();
                    } else {
                        input.focus();
                        input.click();
                    }
                } catch (err) {
                    input.focus();
                    input.click();
                }
            });

            // Изменение даты
            $(document).on('change', '.date-picker-input', function () {
                const $input = $(this);
                const mainId = $input.data('id');
                const field  = $input.data('field');
                const value  = $input.val(); // YYYY-MM-DD или ""

                showLoadingSpinner();

                $.ajax({
                    url: '{{ route('mobile.tasks.updateDates') }}',
                    type: 'POST',
                    data: {
                        main_id: mainId,
                        field: field,
                        value: value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function () {
                        loadTasks();
                    },
                    error: function () {
                        alert('Error updating date.');
                    },
                    complete: function () {
                        hideLoadingSpinner();
                    }
                });
            });
        });
    </script>
@endsection


@endsection
