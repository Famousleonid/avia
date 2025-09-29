{{-- resources/views/admin/workorders/show.blade.php --}}
@extends('admin.master')

@section('style')
    <style>
        .sf { font-size: 12px; }

        .gradient-pane{
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
        }

        /* ====== ВЕРХ/НИЗ ВЕРСТКА ПО ВЫСОТЕ ЭКРАНА ====== */
        .vh-layout {
            /* подгони отступ, если сверху есть фикс-хедер/браузерные панели */
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .top-pane {
            flex: 0 0 25%;           /* 25% высоты экрана */
            min-height: 160px;       /* чтобы не было слишком мелко на низких экранах */
            border: 1px solid rgba(0,0,0,.125);
            border-radius: .5rem;
            padding: 1rem;
            overflow: auto;          /* если данных больше — внутри будет скролл */
        }
        .bottom-row {
            flex: 1 1 auto;          /* остальная высота (≈75%) */
            display: flex;
            gap: 0.75rem;
            min-height: 260px;
        }

        /* на ≥LG — две колонки; на мобильных — стекаются */
        .bottom-col {
            border: 1px solid rgba(0,0,0,.125);
            border-radius: .5rem;
            padding: 1rem;
            overflow: hidden;        /* содержимое внутри само прокручивается */
            display: flex;
            flex-direction: column;
            min-height: 200px;
        }
        @media (min-width: 992px) {
            .bottom-col.left  { width: 50%; }
            .bottom-col.right { width: 50%; }
        }
        @media (max-width: 991.98px) {
            .bottom-row { flex-direction: column; }
            .bottom-col { width: 100%; }
        }

        /* ====== содержимое левой панели (выбор + таблица) ====== */
        .select-task {
            border: 0; width: 100%; text-align: left;
            padding: .5rem .75rem; background: transparent; border-radius: .5rem;
        }
        .select-task:hover { background: rgba(0, 123, 255, .15); cursor: pointer; }
        #taskTabContent { max-height: 40vh; overflow:auto; } /* ограничение списка задач */

        /* единая высота контролов в строке */
        .eqh-sm { height: calc(1.8125rem + 2px); } /* высота под form-control-sm */
        .is-valid { box-shadow: 0 0 0 .2rem rgba(25,135,84,.25); }
        #taskPickerBtn.eqh { height: calc(1.8125rem + 2px); }

        /* левая колонка — вертикальный флекс: сверху форма, снизу таблица со скроллом */
        .left-pane { display: flex; flex-direction: column; gap: .75rem; height: 100%; }
        .table-wrap { flex: 1 1 auto; min-height: 180px; }
        .table-wrap .table-responsive { height: 100%; max-height: 100%; overflow: auto; }

        /* мобильные улучшения */
        @media (max-width: 991.98px) {
            #taskTabContent { max-height: 50vh; }
            .table-wrap .table-responsive { max-height: 50vh; }
            .table td, .table th { white-space: nowrap; }
        }

        /* колонка general->task */
        .task-cell {
            background: linear-gradient(90deg, rgba(0,123,255,.1), rgba(0,200,255,.05));
            border-radius: .25rem;
            padding: .25rem .5rem;
            font-size: 0.8rem;
            line-height: 1.2;
        }
        .task-cell .general-name {
            font-weight: 600;
            color: #0d6efd; /* синий */
        }
        .task-cell .task-name {
            font-weight: 400;
            color: #333;
        }
        .gradient-table {
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa; /* светлый текст */
            border-radius: .5rem;
            overflow: hidden;
        }

        .gradient-table th {
            background-color: rgba(0,0,0,.25);
            color: #dee2e6;
            font-size: 0.8rem;
        }

        .gradient-table td {
            background-color: rgba(255,255,255,.02);
            font-size: 0.85rem;
            vertical-align: middle;
        }

        /* колонка General → Task */
        .task-col {
            font-size: 0.8rem;
            font-weight: 500;
            color: #f8f9fa;
        }
        .task-col .arrow {
            margin: 0 .25rem;
            color: #adb5bd; /* посветлее для стрелки */
        }

        .finish-input.has-finish {
            background-color: rgba(25,135,84,.1); /* нежно-зелёный фон, как у даты начала */

            color: #f8f9fa;                       /* светлый текст под градиентную таблицу */
            font-weight: 500;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23198754' viewBox='0 0 16 16'%3E%3Cpath d='M13.485 1.929a.75.75 0 010 1.06L6.818 9.657a.75.75 0 01-1.06 0L2.515 6.414a.75.75 0 111.06-1.06L6 7.778l6.425-6.425a.75.75 0 011.06 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .5rem center;
            background-size: 1rem 1rem;
            padding-right: 2rem;
        }

        #taskPickerBtn .picked{
            max-width: 55%;
            font-size: .8rem;
            opacity: .95;
            text-align: right;
            direction: rtl;
            unicode-bidi: plaintext;
            color: var(--bs-info);
        }

        @media (max-width: 575.98px){
            #taskPickerBtn .picked{ max-width: 60%; font-size: .8rem; }
        }
        .gradient-top{
            background: linear-gradient(135deg, #212529 0%, #2c3035 100%);
            color: #f8f9fa;
        }

        #addBtn.btn-success{
            background-color: var(--bs-success) !important;
            border-color: var(--bs-success) !important;
            color: #fff !important;
            border-width: 1px;
        }
        #addBtn.btn-success:focus{
            box-shadow: 0 0 0 .2rem rgba(25,135,84,.35);
        }
        /* Когда кнопка была disabled, Bootstrap делает её бледной — после включения уберём эффект: */
        #addBtn:not(:disabled){ opacity: 1; }

    </style>
@endsection

@section('content')

    {{-- <div class="d-flex align-items-center">
                            <h5 class="modal-title text-info fw-bold mb-0 me-3">w {{ $current_workorder->number }}</h5>
                            @if($current_workorder->approve_at)
                                <div class="d-flex align-items-center">
                                    <img class="ms-1" src="{{ asset('img/ok.png') }}" width="20" alt="">
                                    <span class="sf ms-1" style="color:#8AF466">approved</span>
                                </div>
                            @else
                                <span class="sf" style="color:#ff7676">not approved</span>
                            @endif
                        </div>--}}

    <div class="card shadow">
        <div class="card-body">
            <div class="vh-layout ">

                {{------------------------------------------------------------------------------------------}}

                <div class="top-pane border-info gradient-pane">
                    <div class="row g-3 align-items-stretch">

                        <div class="col-12 col-md-3 col-lg-2 d-flex">
                            <div class="card h-100 w-100 bg-dark text-light border-secondary d-flex align-items-center justify-content-center p-3">
                                @if($imgFull)
                                    <a href="{{ $imgFull }}" data-fancybox="wo-manual" title="Manual">
                                        <img class="rounded-circle" src="{{ $imgThumb }}" width="80" height="80" alt="Manual preview">
                                    </a>
                                @else
                                    <img class="rounded-circle" src="{{ $imgThumb }}" width="80" height="80" alt="No image">
                                @endif
                            </div>
                        </div>


                        <div class="col-12 col-md-9 col-lg-10">
                            <div class="card bg-dark text-light border-secondary h-100">
                                <div class="card-body py-3 d-flex flex-column">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <h5 class="mb-0 text-info">WO: {{ $current_workorder->number }}</h5>
                                            @if($current_workorder->approve_at)
                                                <span class="badge bg-success">
                                    Approved {{ $current_workorder->approve_at?->format('d-M-y') ?? '—' }}
                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">Not approved</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            Open: <span class="text-light">{{ $current_workorder->open_at?->format('d-M-y') ?? '—' }}</span>
                                            @if($current_workorder->created_at)
                                                <span class="mx-2">•</span>
                                                Created: <span class="text-light">{{ $current_workorder->created_at?->format('d-M-y') ?? '—' }}</span>
                                            @endif
                                            @if($current_workorder->amdt)
                                                <span class="mx-2">•</span>
                                                AMDT: <span class="text-light">{{ e($current_workorder->amdt) }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row g-3 flex-fill">
                                        {{-- CUSTOMER --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="fw-semibold text-info mb-1">Customer</div>
                                                <div class="small">
                                                    <div><span class="text-secondary">Name:</span> {{ $current_workorder->customer->name ?? '—' }}</div>
                                                    @if(optional($current_workorder->customer)->email)
                                                        <div><span class="text-secondary">Email:</span>
                                                            <a class="link-info" href="mailto:{{ $current_workorder->customer->email }}">{{ $current_workorder->customer->email }}</a>
                                                        </div>
                                                    @endif
                                                    @if(optional($current_workorder->customer)->phone)
                                                        <div><span class="text-secondary">Phone:</span> {{ $current_workorder->customer->phone }}</div>
                                                    @endif
                                                    @if(optional($current_workorder->customer)->address)
                                                        <div><span class="text-secondary">Address:</span> {{ $current_workorder->customer->address }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- UNIT --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="fw-semibold text-info mb-1">Unit</div>
                                                <div class="small">
                                                    <div><span class="text-secondary">Part number:</span> {{ $current_workorder->unit->part_number ?? '—' }}</div>
                                                    @if(optional($current_workorder->unit)->model)
                                                        <div><span class="text-secondary">Model:</span> {{ $current_workorder->unit->model }}</div>
                                                    @endif
                                                    <div><span class="text-secondary">Serial:</span>
                                                        {{ $current_workorder->serial_number ?? ($current_workorder->unit->serial_number ?? '—') }}
                                                    </div>
                                                    @if($current_workorder->place)
                                                        <div><span class="text-secondary">Place:</span> {{ $current_workorder->place }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- DETAILS --}}
                                        <div class="col-12 col-lg-4 d-flex">
                                            <div class="border rounded p-2 h-100 w-100">
                                                <div class="fw-semibold text-info mb-1">Details</div>
                                                <div class="small">
                                                    <div><span class="text-secondary">Owner:</span> {{ $current_workorder->user->name ?? '—' }}</div>
                                                    <div><span class="text-secondary">Instruction:</span>
                                                        {{ $current_workorder->instruction->name ?? '—' }}
                                                        @if(optional($current_workorder->instruction)->code)
                                                            <span class="text-secondary">({{ $current_workorder->instruction->code }})</span>
                                                        @endif
                                                    </div>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($current_workorder->description)
                                        <div class="mt-3 small">
                                            <div class="fw-semibold text-info mb-1">Description</div>
                                            <div class="text-light">{{ $current_workorder->description }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                {{------------------------------------------------------------------------------------------}}

                <div class="bottom-row ">

                    <div class="bottom-col left gradient-pane border-info">
                        <div class="left-pane">


                            <form id="general_task_form" method="POST" action="{{ route('mains.store') }}" class="w-100">
                                @csrf
                                <input type="hidden" name="workorder_id" value="{{ $current_workorder->id }}">
                                <input type="hidden" name="task_id" id="task_id" value="{{ old('task_id') }}">


                                <div class="dropdown mb-2">
                                    <button id="taskPickerBtn"
                                            class="btn btn-outline-primary eqh w-100 d-flex align-items-center justify-content-between dropdown-toggle"
                                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <span>Choose task</span>
                                        <span id="pickedSummary" class="picked text-truncate"></span>
                                    </button>

                                    <div class="dropdown-menu p-3" style="min-width:100%;max-width:100%;">
                                        <div class="row g-3">
                                            <div class="col-5">
                                                <ul class="nav nav-pills flex-column" id="generalTab" role="tablist">
                                                    @foreach ($general_tasks as $general)
                                                        <li class="nav-item">
                                                            <button class="nav-link @if($loop->first) active @endif w-100 text-start"
                                                                    id="tab-g-{{ $general->id }}"
                                                                    data-bs-toggle="pill"
                                                                    data-bs-target="#pane-g-{{ $general->id }}"
                                                                    type="button" role="tab"
                                                                    aria-controls="pane-g-{{ $general->id }}"
                                                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                                    data-general-id="{{ $general->id }}">
                                                                {{ $general->name }}
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div class="col-7">
                                                <div class="tab-content" id="taskTabContent">
                                                    @foreach ($general_tasks as $general)
                                                        <div class="tab-pane fade @if($loop->first) show active @endif"
                                                             id="pane-g-{{ $general->id }}" role="tabpanel"
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

                                {{-- одна строка: User / Start / Finish / Add --}}
                                <div class="row g-2 align-items-stretch ">
                                    <div class="col-12 col-sm-6 col-xl-4 border-secondary">
                                        <select name="user_id" class="form-select-sm eqh-sm">
                                            <option value="">Current ({{ auth()->user()->name ?? 'You' }})</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}" @selected(old('user_id')==$u->id)>{{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-xl-3">
                                        <input type="date" name="date_start" class="form-control eqh"
                                               value="{{ old('date_start', now()->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-6 col-xl-3">
                                        <input type="date" name="date_finish" class="form-control-sm eqh-sm"
                                               value="{{ old('date_finish') }}">
                                    </div>
                                    <div class="col-12 col-xl-2 d-grid">
                                        <button type="submit" id="addBtn" class="btn btn-success" disabled>Add</button>
                                    </div>

                                </div>
                            </form>

                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle gradient-table table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th>Technik</th>
                                            <th>Task</th>
                                            <th>Start</th>
                                            <th>Finish (edit)</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($mains as $i => $m)
                                            <tr id="main-row-{{ $m->id }}">
                                                <td>{{ $m->user->name ?? '—' }}</td>
                                                <td class="task-col text-info">
                                                    {{ $m->task->generalTask->name ?? '—' }}
                                                    <span class="arrow">→</span>
                                                    {{ $m->task->name ?? '—' }}
                                                </td>
                                                <td>{{ optional($m->date_start)->format('d-M-y') }}</td>
                                                <td style="min-width:180px;">
                                                    <input type="date"
                                                           class="form-control form-control-sm finish-input {{ $m->date_finish ? 'has-finish' : '' }}"
                                                           data-id="{{ $m->id }}"
                                                           data-update-url="{{ route('mains.update', $m) }}"
                                                           value="{{ optional($m->date_finish)->format('Y-m-d') }}">
                                                </td>
                                                <td class="text-end">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm btn-icon-compact"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#useConfirmDelete"
                                                            data-action="{{ route('mains.destroy', $m) }}"
                                                            title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-muted">No tasks yet</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                {{------------------------------------------------------------------------------------------}}

                    <div class="bottom-col right border-info gradient-pane ">
                        <div class="text-muted">Right panel  — под компоненты</div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    @include('components.delete')


@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            function getCsrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            }
            const CSRF = getCsrfToken();

            function safeShowSpinner() {
                try { if (typeof showLoadingSpinner === 'function') showLoadingSpinner(); } catch(_) {}
            }
            function safeHideSpinner() {
                try { if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner(); } catch(_) {}
            }

            function debounce(fn, ms) {
                let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
            }

            async function fetchJSON(url, options = {}) {
                const res = await fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...options.headers
                    },
                    ...options
                });
                return res;
            }

            window.addEventListener('pageshow', safeHideSpinner);

            const form          = document.getElementById('general_task_form');
            const taskInput     = document.getElementById('task_id');
            const addBtn        = document.getElementById('addBtn');
            const pickerBtn     = document.getElementById('taskPickerBtn');
            const pickedSummary = document.getElementById('pickedSummary');

            const generalTabs = Array.from(document.querySelectorAll('#generalTab .nav-link[data-general-id]'));
            const taskPanes   = Array.from(document.querySelectorAll('#taskTabContent .tab-pane'));
            const taskButtons = Array.from(document.querySelectorAll('.select-task'));


            function showPaneForGeneral(btn) {
                const gid = btn.dataset.generalId;
                generalTabs.forEach(b => b.classList.remove('active'));
                taskPanes.forEach(p => p.classList.remove('show', 'active'));
                btn.classList.add('active');
                const pane = document.getElementById('pane-g-' + gid);
                if (pane) pane.classList.add('active', 'show');
            }

            function generalNameById(gid) {
                const b = document.getElementById('tab-g-' + gid);
                return (b ? b.textContent : '').trim();
            }

            function updatePickedSummary(gName, tName) {
                if (!pickedSummary) return;
                pickedSummary.textContent = (gName && tName) ? `${gName} → ${tName}` : (tName || '');
            }

            function activateAddButton() {
                if (!addBtn) return;
                addBtn.removeAttribute('disabled');
                addBtn.classList.remove('disabled');
            }

            function initTaskPicker() {
                // Переключение панелей по hover
                generalTabs.forEach(btn => {
                    btn.addEventListener('mouseenter', () => showPaneForGeneral(btn));
                    btn.addEventListener('click', e => e.preventDefault());
                });

                taskButtons.forEach(item => {
                    item.addEventListener('click', () => {
                        const taskId   = item.dataset.taskId;
                        const taskName = item.dataset.taskName;
                        const gid      = item.dataset.generalId;

                        if (taskInput) taskInput.value = taskId;
                        updatePickedSummary(generalNameById(gid), taskName);
                        activateAddButton();

                        if (pickerBtn && window.bootstrap?.Dropdown) {
                            const dd = bootstrap.Dropdown.getOrCreateInstance(pickerBtn);
                            dd?.hide();
                        }
                    });
                });

                if (generalTabs[0]) showPaneForGeneral(generalTabs[0]);
                if (taskInput?.value) activateAddButton();
            }

            /**
             * Подключает обработчик сабмита формы добавления — проверка task_id, спиннер, анти-двойной клик.
             */
            function bindFormSubmit() {
                if (!form) return;
                form.addEventListener('submit', (e) => {
                    if (!taskInput?.value) {
                        e.preventDefault();
                        alert('Please choose a task first');
                        return;
                    }
                    safeShowSpinner();
                    if (addBtn) {
                        addBtn.setAttribute('disabled', 'disabled');
                        addBtn.classList.add('disabled');
                    }
                });
            }

            /**
             * Навешивает обработчик на все инпуты .finish-input
             * По изменению отправляет PATCH { date_finish } на data-update-url.
             * Подсвечивает сохранение и добавляет/снимает класс has-finish.
             */
            function initFinishInlineEditing() {
                document.querySelectorAll('.finish-input').forEach(inp => {
                    inp.addEventListener('change', debounce(async (e) => {
                        const url   = e.target.dataset.updateUrl;
                        const value = e.target.value || null;

                        try {
                            const res = await fetchJSON(url, {
                                method: 'PATCH',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ date_finish: value })
                            });

                            if (!res.ok) {
                                safeHideSpinner();
                                let msg = `Failed to update (HTTP ${res.status})`;
                                try { const j = await res.json(); if (j?.message) msg = j.message; } catch(_) {}
                                alert(msg);
                                return;
                            }

                            e.target.classList.add('is-valid');
                            value ? e.target.classList.add('has-finish') : e.target.classList.remove('has-finish');
                            setTimeout(() => e.target.classList.remove('is-valid'), 800);

                        } catch (err) {
                            safeHideSpinner();
                            console.error(err);
                            alert('Network error while updating');
                        }
                    }, 250));
                });
            }

            const modalEl   = document.getElementById('useConfirmDelete');
            const confirmBt = document.getElementById('confirmDeleteBtn');
            const delForm   = document.getElementById('deleteForm'); // <- было form

            let pendingAction = null;

            modalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                pendingAction = trigger?.getAttribute('data-action') || null;
            });

            confirmBt.addEventListener('click', function () {
                if (!pendingAction) return;
                delForm.setAttribute('action', pendingAction); // <- используем delForm
                // опционально: покажем спиннер на всякий случай (если не вызываешь в onclick)
                try { if (typeof showLoadingSpinner === 'function') showLoadingSpinner(); } catch(_) {}
                delForm.submit();
            });


            initTaskPicker();
            bindFormSubmit();
            initFinishInlineEditing();
            initDeleteModal();
        });
    </script>
@endsection


