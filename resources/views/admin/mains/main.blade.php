@extends('admin.master')

@section('style')
    <style>
        .sf {
            font-size: 12px;
        }
    </style>
    <style>
        .select-task {
            border: 0;
            width: 100%;
            text-align: left;
            padding: .5rem .75rem;
            background: transparent;
            border-radius: .5rem;
        }

        .select-task:hover {
            background: blue;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')


    <div class="card shadow">
        <div class="card-body p-0">
            <div class="row g-3 flex-column flex-md-row align-items-md-start">
                <!-- Заголовок -->
                <div class="col-12 col-md-auto d-flex flex-column align-items-start">
                    <h5 class="modal-title text-info text-bold ms-2 mb-1">w {{$current_workorder->number}}</h5>
                    @if($current_workorder->approve_at)
                        <div class="d-flex align-items-center">
                            <img class="ms-2" src="{{asset('img/ok.png')}}" width="20px" alt="">
                            <span class="sf ms-1" style="color: #8AF466">approved</span>
                        </div>
                    @else
                        <span class="sf ms-2" style="color: #8AF466">not approved</span>
                    @endif
                </div>

                <!-- Форма -->
                <div class="col-12 col-md">
                    <form id="general_task_form" action="{{route('mains.create')}}" class="row g-3 align-items-end mx-md-3">
                        @csrf
                        <input type="text" hidden name="workorder_id" value="{{$current_workorder->id}}">


                        {{-- скрытые поля для формы --}}
                        <input type="hidden" name="general_task_id" id="general_task_id" value="{{ old('general_task_id') }}">
                        <input type="hidden" name="task_id" id="task_id" value="{{ old('task_id') }}">

                        <div class="mb-2">
                            <span class="text-muted">Selected:</span>
                            <span id="sel_general" class="badge bg-secondary">—</span>
                            <span class="mx-1">/</span>
                            <span id="sel_task" class="badge bg-secondary">—</span>
                        </div>

                        <div class="dropdown">
                            <button id="taskPickerBtn"
                                    class="btn btn-outline-primary dropdown-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false">
                                Choose task
                            </button>

                            <div class="dropdown-menu p-3" style="min-width: 600px;">
                                <div class="row g-3">
                                    {{-- Левое меню: General Tasks --}}
                                    <div class="col-5">
                                        <ul class="nav nav-pills flex-column" id="generalTab" role="tablist">
                                            @foreach ($general_tasks as $general)
                                                <li class="nav-item">
                                                    <button class="nav-link @if($loop->first) active @endif w-100 text-start"
                                                            id="tab-g-{{ $general->id }}"
                                                            data-bs-toggle="pill"
                                                            data-bs-target="#pane-g-{{ $general->id }}"
                                                            type="button"
                                                            role="tab"
                                                            aria-controls="pane-g-{{ $general->id }}"
                                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                            data-general-id="{{ $general->id }}">
                                                        {{ $general->name }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    {{-- Правое подменю: Tasks --}}
                                    <div class="col-7">
                                        <div class="tab-content" id="taskTabContent" style="max-height: 50vh; overflow:auto;">
                                            @foreach ($general_tasks as $general)
                                                <div class="tab-pane fade @if($loop->first) show active @endif"
                                                     id="pane-g-{{ $general->id }}"
                                                     role="tabpanel"
                                                     aria-labelledby="tab-g-{{ $general->id }}">
                                                    @php $group = $tasks->where('general_task_id', $general->id); @endphp
                                                    @forelse ($group as $task)
                                                        <button type="button"
                                                                class="select-task list-group-item list-group-item-action mb-1"
                                                                data-task-id="{{ $task->id }}"
                                                                data-task-name="{{ $task->name }}"
                                                                data-general-id="{{ $general->id }}">
                                                            {{ $task->name }}
                                                        </button>
                                                    @empty
                                                        <div class="text-muted small">No tasks</div>
                                                    @endforelse
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </form>
                </div>
            </div>

        </div>
    </div>


    @include('components.delete')

@endsection

@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const generalInput = document.getElementById('general_task_id');
            const taskInput    = document.getElementById('task_id');
            const selGeneral   = document.getElementById('sel_general');
            const selTask      = document.getElementById('sel_task');
            const pickerBtn    = document.getElementById('taskPickerBtn');

            const tabButtons = Array.from(document.querySelectorAll('#generalTab .nav-link[data-general-id]'));
            const panes      = Array.from(document.querySelectorAll('#taskTabContent .tab-pane'));

            function showPaneFor(btn) {
                const gid = btn.dataset.generalId;

                // снять активность со всех
                tabButtons.forEach(b => b.classList.remove('active'));
                panes.forEach(p => p.classList.remove('show', 'active'));

                // активировать нужные
                btn.classList.add('active');
                const pane = document.getElementById('pane-g-' + gid);
                if (pane) {
                    pane.classList.add('active', 'show');
                }

                // обновить выбранный general, сбросить task
                generalInput.value = gid;
                selGeneral.textContent = btn.textContent.trim();
                taskInput.value = '';
                selTask.textContent = '—';
            }

            // Переключаем ПАНЕЛИ по наведению на General
            tabButtons.forEach(btn => {
                btn.addEventListener('mouseenter', () => showPaneFor(btn));
                // отключаем клик, чтобы не было «режима по клику»
                btn.addEventListener('click', (e) => e.preventDefault());
            });

            // Клик по Task — зафиксировать выбор и закрыть dropdown
            document.querySelectorAll('.select-task').forEach(item => {
                item.addEventListener('click', () => {
                    const taskId   = item.dataset.taskId;
                    const taskName = item.dataset.taskName;
                    const gid      = item.dataset.generalId;

                    generalInput.value = gid;
                    taskInput.value    = taskId;
                    selGeneral.textContent = (document.getElementById('tab-g-' + gid)?.textContent || '').trim();
                    selTask.textContent    = taskName;

                    // закрыть дропдаун
                    const dd = bootstrap.Dropdown.getOrCreateInstance(pickerBtn);
                    dd.hide();
                });
            });

            // Инициализация: если уже что-то выбрано (old())
            const initG = generalInput.value;
            if (initG) {
                const initBtn = document.getElementById('tab-g-' + initG);
                if (initBtn) showPaneFor(initBtn);
            } else if (tabButtons[0]) {
                // иначе показываем первую группу при открытии
                showPaneFor(tabButtons[0]);
            }
        });
    </script>


@endsection
