@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 170px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;
        }
        .clearable-input { position: relative; width: 400px; }
        .clearable-input .form-control { padding-right: 2.5rem; }
        .clearable-input .btn-clear {
            position: absolute; right: .5rem; top: 50%;
            transform: translateY(-50%); background: none; border: none; cursor: pointer;
        }
        .accordion-button { gap: .5rem; }

        #gtAccordion .accordion-body > table {
            margin-left: 40px;
        }

        .accordion-body table tbody tr:first-child td {
            border-top-width: 2px;
        }

        .modal-content {
            background-color: #262A2E;
            color: #f1f3f5;
        }
        .modal-header, .modal-footer {
            border-color: rgba(255,255,255,.08);
        }
        .modal-title { color: #e9ecef; }
        .btn-close { filter: invert(1) grayscale(1); }

        .modal-content .form-label { color: #ced4da; }
        .modal-content .form-control,
        .modal-content .form-select {
            background-color: #2f3439;
            color: #e9ecef;
            border-color: #3b4148;
        }
        .modal-content .form-control::placeholder { color: #9aa1a9; opacity: 1; }
        .modal-content .form-control:focus,
        .modal-content .form-select:focus {
            background-color: #343a40;
            color: #fff;
            border-color: #4c8bf5; /* подсветка */
            box-shadow: 0 0 0 .2rem rgba(76,139,245,.15);
        }


    </style>

    <div class="card dir-panel">
        @include('components.status')

        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="text-primary mb-0">
                    {{ __('Tasks') }}
                    <span class="text-success">({{ $tasks->count() }})</span>
                </h5>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                    {{ __('Add task') }}
                </button>
            </div>
        </div>

        <div class="d-flex my-2 align-items-center gap-2 ps-2">

{{--            <div class="clearable-input">--}}
{{--                <input id="searchInput" type="text" class="form-control w-100" placeholder="{{ __('Search...') }}">--}}
{{--                <button class="btn-clear text-secondary"--}}
{{--                        onclick="document.getElementById('searchInput').value=''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">--}}
{{--                    <i class="bi bi-x-circle"></i>--}}
{{--                </button>--}}
{{--            </div>--}}

            <div class="ms-auto d-flex gap-2 pe-3">
                <button id="toggleAll" type="button" class="btn btn-outline-secondary btn-sm">
                    {{ __('Expand all') }}
                </button>
            </div>
        </div>

        @if($tasks->count())

            <div class="table-wrapper me-3 p-2 pt-0 dir-panel">

                <div class="accordion dir-accordion" id="gtAccordion">

                    @foreach($groups as $group)
                        <div class="accordion-item dir-acc-item" data-gt-id="{{ $group->id }}">

                            <h2 class="accordion-header dir-acc-header" id="heading-{{ $group->id }}">
                                <button
                                    class="accordion-button collapsed py-2 dir-acc-button"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $group->id }}"
                                    aria-expanded="false"
                                    aria-controls="collapse-{{ $group->id }}"
                                    data-total="{{ $group->tasks->count() }}"
                                >
                        <span class="fw-semibold text-primary dir-acc-title">
                            {{ $group->name }}
                        </span>

                                    <span class="ms-2 small dir-acc-counter">
                            ({{ $group->tasks->count() }})
                        </span>
                                </button>
                            </h2>

                            <div
                                id="collapse-{{ $group->id }}"
                                class="accordion-collapse collapse dir-acc-collapse"
                                aria-labelledby="heading-{{ $group->id }}"
                                data-bs-parent="#gtAccordion"
                            >
                                <div class="accordion-body py-2 dir-acc-body">

                                    @if($group->tasks->count())
                                        <table class="table table-sm table-hover  table-bordered mb-0 dir-table">
                                            <colgroup>
                                                <col />
                                                <col style="width: 140px;" />
                                            </colgroup>

                                            <tbody>
                                            @foreach($group->tasks as $task)
                                                <tr data-task-row data-gt="{{ $group->id }}">
                                                    <td>{{ $task->name }}</td>

                                                    <td class="text-center">
                                                        <button
                                                            class="btn btn-outline-primary btn-sm me-2 dir-btn-icon"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editModal"
                                                            onclick="populateEditModal({{ $task->id }}, '{{ e($task->name) }}', {{ $group->id }})"
                                                        >
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>

                                                        <button
                                                            class="btn btn-outline-danger btn-sm dir-btn-icon"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteModal"
                                                            onclick="populateDeleteModal({{ $task->id }}, '{{ e($task->name) }}')"
                                                        >
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="small dir-muted">
                                            {{ __('No tasks in this group') }}
                                        </div>
                                    @endif

                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>
            </div>

        @else
            <p class="px-3 pb-3">{{ __('Task not created') }}</p>
        @endif
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Task') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="POST" action="{{ route('tasks.store') }}">
                        @csrf


                        <div class="mb-3">
                            <label for="createGeneralTask" class="form-label">{{ __('Category (General Task)') }}</label>
                            <select id="createGeneralTask" name="general_task_id" class="form-select">
                                <option value="">{{ __('— No category —') }}</option>
                                @foreach($general_tasks as $gt)
                                    <option value="{{ $gt->id }}">{{ $gt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="createName" class="form-label">{{ __('Name') }}</label>
                            <input type="text" id="createName" name="name" class="form-control" required>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="hasStart"
                                   name="task_has_start_date"
                                   value="1"
                                   checked>
                            <label class="form-check-label" for="hasStart">
                                {{ __('Has start date') }}
                            </label>
                        </div>


                        <button type="submit" class="btn btn-primary" onclick="showLoadingSpinner()">{{ __('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Task') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="{{ route('tasks.update', ':id') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId" name="id">

                        <div class="mb-3">
                            <label for="editName" class="form-label">{{ __('Name') }}</label>
                            <input type="text" id="editName" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="editGeneralTask" class="form-label">{{ __('Category (General Task)') }}</label>
                            <select id="editGeneralTask" name="general_task_id" class="form-select">
                                <option value="">{{ __('— No category —') }}</option>
                                @foreach($general_tasks as $gt)
                                    <option value="{{ $gt->id }}">{{ $gt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="hasStart"
                                   name="task_has_start_date"
                                   value="1"
                                   checked>
                            <label class="form-check-label" for="hasStart">
                                {{ __('Has start date') }}
                            </label>
                        </div>


                        <button type="submit" class="btn btn-primary" onclick="showLoadingSpinner()">{{ __('Update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="deleteModalTitle" class="modal-title">{{ __('Delete Task') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to delete this ?') }}</p>
                    <form id="deleteForm" method="POST" action="{{ route('tasks.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <button type="submit" class="btn btn-danger" onclick="showLoadingSpinner()">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const acc          = document.getElementById('gtAccordion');
            const toggleAllBtn = document.getElementById('toggleAll');
            const searchInput  = document.getElementById('searchInput');

            if (!acc) return; // страховка

            // чтобы по умолчанию всё было раскрыто
            // forEachNode(getAllCollapses(), c => bootstrap.Collapse.getOrCreateInstance(c, {toggle:false}).show());
            // setTimeout(updateToggleAllText, 160);

            // ===== Helpers =====
            function getAllCollapses() {
                return acc.querySelectorAll('.accordion-collapse');
            }
            function getOpenedCollapses() {
                return acc.querySelectorAll('.accordion-collapse.show');
            }
            function isAllExpanded() {
                const all = getAllCollapses();
                return all.length > 0 && getOpenedCollapses().length === all.length;
            }
            function forEachNode(list, cb) {
                Array.prototype.forEach.call(list, cb);
            }

            // Обновление текста кнопки
            function updateToggleAllText() {
                if (!toggleAllBtn) return;
                toggleAllBtn.textContent = isAllExpanded()
                    ? '{{ __("Collapse all") }}'
                    : '{{ __("Expand all") }}';
            }

            // Считать "всего" для группы из data-total у header-кнопки, либо по строкам
            function getGroupTotals(item) {
                const headerBtn = item.querySelector('.accordion-button');
                const totalAttr = headerBtn ? headerBtn.getAttribute('data-total') : null;
                const total = totalAttr ? parseInt(totalAttr, 10) : item.querySelectorAll('[data-task-row]').length;
                const visible = item.querySelectorAll('[data-task-row]:not([style*="display: none"])').length;
                return { total, visible, headerBtn };
            }

            // ===== Init =====
            updateToggleAllText();

            // ===== Toggle All =====
            if (toggleAllBtn) {
                toggleAllBtn.addEventListener('click', () => {
                    const expand = !isAllExpanded(); // если есть закрытые — раскрываем всё
                    forEachNode(getAllCollapses(), c => {
                        const inst = bootstrap.Collapse.getOrCreateInstance(c, {toggle: false});
                        expand ? inst.show() : inst.hide();
                    });
                    // дать Bootstrap применить классы
                    setTimeout(updateToggleAllText, 160);
                });
            }

            // ===== Search =====
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const q = searchInput.value.trim().toLowerCase();

                    safeShowSpinner();
                    setTimeout(() => {
                        // 1) фильтрация строк
                        const allRows = acc.querySelectorAll('[data-task-row]');
                        forEachNode(allRows, row => {
                            const text = row.innerText.toLowerCase();
                            row.style.display = text.includes(q) ? '' : 'none';
                        });

                        // 2) скрывать пустые группы, обновлять счётчики, авто-раскрытие при совпадениях
                        const items = acc.querySelectorAll('.accordion-item');
                        forEachNode(items, item => {
                            const body = item.querySelector('.accordion-collapse');
                            const counter = item.querySelector('.group-counter');
                            const { total, visible } = getGroupTotals(item);

                            // show/hide целиком группу
                            item.style.display = (visible > 0 || q === '') ? '' : 'none';

                            // авто-раскрыть если есть совпадения в группе
                            const inst = bootstrap.Collapse.getOrCreateInstance(body, {toggle: false});
                            if (q !== '' && visible > 0) inst.show();

                            // обновить счётчик: (visible/total) в поиске, иначе (total)
                            if (counter) counter.textContent = q ? `(${visible}/${total})` : `(${total})`;
                        });

                        safeHideSpinner();
                        setTimeout(updateToggleAllText, 160);
                    }, 60);
                });
            }

            // ===== Синхронизация текста кнопки при ручных действиях =====
            acc.addEventListener('shown.bs.collapse', updateToggleAllText);
            acc.addEventListener('hidden.bs.collapse', updateToggleAllText);
        });

        // ===== Modals helpers =====
        function populateEditModal(id, name, generalTaskId) {
            const form = document.getElementById('editForm');
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            if (form) form.action = `{{ route('tasks.update', ':id') }}`.replace(':id', id);

            const sel = document.getElementById('editGeneralTask');
            if (sel) sel.value = (generalTaskId ?? '');
        }

        function populateDeleteModal(id, name) {
            const form = document.getElementById('deleteForm');
            document.getElementById('deleteId').value = id;
            if (form) form.action = `{{ route('tasks.destroy', ':id') }}`.replace(':id', id);
            const title = document.getElementById('deleteModalTitle');
            if (title) title.innerText = `{{ __('Delete task') }} (${name})`;
        }
    </script>

@endsection
